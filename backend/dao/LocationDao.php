<?php
/* LocationDao – places where houses have been built. */

require_once __DIR__ . '/BaseDao.php';

class LocationDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('locations', ['city', 'region', 'country', 'latitude', 'longitude', 'geocoded_at']);
    }

    public function getByCity(string $city, string $country): ?array
    {
        return $this->queryOne('SELECT * FROM locations WHERE city = ? AND country = ?', [$city, $country]);
    }

    /** Saves map coordinates (from the Nominatim integration in a later milestone). */
    public function updateCoordinates(int $id, float $latitude, float $longitude): ?array
    {
        return $this->update($id, [
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'geocoded_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Locations that do not have coordinates yet. */
    public function getWithoutCoordinates(): array
    {
        return $this->query('SELECT * FROM locations WHERE latitude IS NULL OR longitude IS NULL ORDER BY id');
    }
}
