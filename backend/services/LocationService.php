<?php
/*
 * LocationService – places where houses have been built.
 *
 * When a location is created, or its city or country changes, the service asks
 * the GeocodingService (OpenStreetMap Nominatim) for its coordinates.
 * If Nominatim is unavailable, the location is still saved – just without
 * coordinates – and the response says so in "geocoding". The coordinates can
 * be filled in later with POST /locations/{id}/geocode.
 *
 * Coordinates can also be entered by hand (latitude + longitude); then Nominatim is not called.
 */

require_once __DIR__ . '/BaseService.php';

class LocationService extends BaseService
{
    private ProjectDao $projectDao;
    private GeocodingService $geocoder;

    public function __construct()
    {
        parent::__construct(new LocationDao(), 'Location');
        $this->projectDao = new ProjectDao();
        $this->geocoder = new GeocodingService();
    }

    public function getAll(): array
    {
        return $this->dao->getAll('city');
    }

    /** POST – saves the location, then tries to find its coordinates. */
    public function create(array $data): array
    {
        $location = parent::create($data);
        return $this->geocodeIfNeeded($location, $data);
    }

    /** PUT/PATCH – if the city or country changed, the old coordinates are removed and looked up again. */
    public function update(int $id, array $data, bool $partial = false): array
    {
        $before = $this->getById($id);
        $location = parent::update($id, $data, $partial);

        $placeChanged = $location['city'] !== $before['city'] || $location['country'] !== $before['country'];
        $coordinatesSent = array_key_exists('latitude', $data) || array_key_exists('longitude', $data);

        if ($placeChanged && !$coordinatesSent) {
            $location = $this->dao->update($id, ['latitude' => null, 'longitude' => null, 'geocoded_at' => null]);
            return $this->geocode($location);
        }
        return $location + ['geocoding' => ['status' => 'skipped', 'message' => 'Coordinates were not looked up.']];
    }

    /** POST /locations/{id}/geocode – looks up the coordinates again. */
    public function geocodeById(int $id): array
    {
        return $this->geocode($this->getById($id));
    }

    /**
     * GET /geocode?city=...&country=... – asks Nominatim for a place without saving anything,
     * e.g. so the admin form can show the place on a map before saving it.
     */
    public function lookup(array $query): array
    {
        $clean = (new Validator($query))
            ->string('city', required: true, max: 100)
            ->string('country', required: true, max: 100)
            ->validate();

        $place = $this->geocoder->search($clean['city'], $clean['country']); // 503 if Nominatim is unavailable
        if ($place === null) {
            throw new NotFoundException('OpenStreetMap does not know this place.');
        }
        return $place;
    }

    /** POST /locations/geocode-missing – looks up every location without coordinates (1 request per second). */
    public function geocodeMissing(): array
    {
        $summary = ['found' => 0, 'not_found' => 0, 'failed' => 0, 'locations' => []];
        foreach ($this->dao->getWithoutCoordinates() as $location) {
            $result = $this->geocode($location);
            $status = $result['geocoding']['status'];
            $summary[$status]++;
            $summary['locations'][] = $result;
        }
        return $summary;
    }

    protected function validate(array $data, bool $partial, ?int $id): array
    {
        $clean = (new Validator($data, $partial))
            ->string('city', required: true, max: 100)
            ->string('region', max: 100)
            ->string('country', required: true, max: 100)
            ->decimal('latitude', min: -90, max: 90, decimals: 6)
            ->decimal('longitude', min: -180, max: 180, decimals: 6)
            ->validate();

        // Coordinates only make sense as a pair.
        if (array_key_exists('latitude', $clean) !== array_key_exists('longitude', $clean)
            || (($clean['latitude'] ?? null) === null) !== (($clean['longitude'] ?? null) === null)) {
            $this->fieldError('latitude', 'Send latitude and longitude together.');
        }
        if (isset($clean['latitude'])) {
            $clean['geocoded_at'] = date('Y-m-d H:i:s');
        }
        return $clean;
    }

    /** City + country must be unique. */
    protected function checkRules(array $clean, ?int $id): void
    {
        $current = $id !== null ? $this->getById($id) : [];
        $city = $clean['city'] ?? $current['city'] ?? null;
        $country = $clean['country'] ?? $current['country'] ?? null;

        if ($city !== null && $country !== null) {
            $existing = $this->dao->getByCity($city, $country);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new ConflictException('This location already exists.');
            }
        }
    }

    protected function beforeDelete(array $row): void
    {
        $projects = $this->projectDao->getForLocation((int) $row['id']);
        if ($projects !== []) {
            throw new ConflictException(sprintf(
                'This location has %d project(s), so it cannot be deleted. Delete or move the projects first.',
                count($projects)
            ));
        }
    }

    // ---------------------------------------------------------------
    // Geocoding
    // ---------------------------------------------------------------

    private function geocodeIfNeeded(array $location, array $data): array
    {
        if (array_key_exists('latitude', $data)) {
            return $location + ['geocoding' => ['status' => 'skipped', 'message' => 'Coordinates were entered by hand.']];
        }
        return $this->geocode($location);
    }

    /**
     * Asks Nominatim for the coordinates and saves them.
     * Never throws because of Nominatim: the result is described in $location['geocoding'].
     */
    private function geocode(array $location): array
    {
        try {
            $place = $this->geocoder->search($location['city'], $location['country']);
        } catch (ExternalServiceException $e) {
            return $location + ['geocoding' => [
                'status'  => 'failed',
                'message' => 'The location was saved without coordinates. ' . $e->getMessage(),
            ]];
        }

        if ($place === null) {
            return $location + ['geocoding' => [
                'status'  => 'not_found',
                'message' => 'OpenStreetMap does not know this place. You can enter the coordinates by hand.',
            ]];
        }

        $location = $this->dao->updateCoordinates((int) $location['id'], $place['latitude'], $place['longitude']);
        return $location + ['geocoding' => [
            'status'  => 'found',
            'message' => 'Coordinates found: ' . $place['display_name'],
        ]];
    }
}
