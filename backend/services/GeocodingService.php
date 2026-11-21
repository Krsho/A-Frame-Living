<?php
/*
 * GeocodingService – the external API of the project: OpenStreetMap Nominatim.
 *
 * Nominatim turns a place name into map coordinates:
 *   "Kupres, Bosnia and Herzegovina"  ->  latitude 43.99, longitude 17.27
 * The coordinates are stored on the location, so the Locations page can show
 * every completed project on a map without calling Nominatim on every visit.
 *
 * Failures are handled so the rest of the application keeps working:
 * - the request has a timeout (config: nominatim.timeout);
 * - timeouts, connection problems, HTTP errors and invalid answers are written
 *   to backend/logs/error.log;
 * - the caller gets an ExternalServiceException (HTTP 503) with a friendly message.
 *
 * Nominatim usage policy: an identifying User-Agent and at most 1 request per second.
 * https://operations.osmfoundation.org/policies/nominatim/
 */

class GeocodingService
{
    public const UNAVAILABLE_MESSAGE = 'The map service (OpenStreetMap) is not available right now. Please try again later.';

    /** Time of the last request (microseconds), to wait at least 1 second between requests. */
    private static float $lastRequestAt = 0;

    private array $settings;

    public function __construct()
    {
        $this->settings = AppConfig::get('nominatim');
    }

    /**
     * Looks up the coordinates of a place.
     *
     * @return array|null ['latitude' => float, 'longitude' => float, 'display_name' => string],
     *                    or null if Nominatim knows no such place
     * @throws ExternalServiceException if Nominatim cannot be reached or answers with an error
     */
    public function search(string $city, string $country): ?array
    {
        $query = "{$city}, {$country}";
        $params = [
            'q'               => $query,
            'format'          => 'jsonv2',
            'limit'           => 1,
            'accept-language' => 'en',
        ];
        if (!empty($this->settings['email'])) {
            $params['email'] = $this->settings['email'];
        }
        $url = rtrim($this->settings['base_url'], '/') . '/search?' . http_build_query($params);

        $this->waitForRateLimit();
        $started = microtime(true);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) $this->settings['timeout'],
            CURLOPT_TIMEOUT        => (int) $this->settings['timeout'],
            CURLOPT_USERAGENT      => $this->settings['user_agent'],
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        $timedOut = curl_errno($curl) === CURLE_OPERATION_TIMEDOUT;
        curl_close($curl);

        $ms = (int) round((microtime(true) - $started) * 1000);
        $logContext = sprintf('q="%s" %dms', $query, $ms);

        // 1. No answer at all (timeout, no internet, wrong address).
        if ($body === false) {
            $reason = $timedOut ? "timeout after {$this->settings['timeout']} s" : "connection failed: {$curlError}";
            Logger::error("Nominatim request failed: {$reason} {$logContext}");
            throw new ExternalServiceException(self::UNAVAILABLE_MESSAGE);
        }

        // 2. An HTTP error, e.g. 429 Too Many Requests or 503.
        if ($status !== 200) {
            Logger::error("Nominatim returned HTTP {$status} {$logContext}");
            throw new ExternalServiceException(self::UNAVAILABLE_MESSAGE);
        }

        // 3. An answer that is not the expected JSON list.
        $results = json_decode($body, true);
        if (!is_array($results)) {
            Logger::error("Nominatim returned invalid JSON {$logContext}");
            throw new ExternalServiceException(self::UNAVAILABLE_MESSAGE);
        }

        // 4. Nominatim answered, but does not know the place.
        if ($results === [] || !isset($results[0]['lat'], $results[0]['lon'])) {
            Logger::info("Nominatim GET /search 200 not found {$logContext}");
            return null;
        }

        $place = [
            'latitude'     => round((float) $results[0]['lat'], 6),
            'longitude'    => round((float) $results[0]['lon'], 6),
            'display_name' => (string) ($results[0]['display_name'] ?? $query),
        ];
        Logger::info(sprintf('Nominatim GET /search 200 found %.6f,%.6f %s', $place['latitude'], $place['longitude'], $logContext));
        return $place;
    }

    /** Waits so that at least 1 second passes between two requests (Nominatim usage policy). */
    private function waitForRateLimit(): void
    {
        $elapsed = microtime(true) - self::$lastRequestAt;
        if ($elapsed < 1.0) {
            usleep((int) ((1.0 - $elapsed) * 1_000_000));
        }
        self::$lastRequestAt = microtime(true);
    }
}
