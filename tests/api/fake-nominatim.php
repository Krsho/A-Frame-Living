<?php
/*
 * A fake Nominatim server for the API tests, so the tests never call the real
 * OpenStreetMap service. It answers GET /search like Nominatim does:
 *
 *   q contains "Nowhere"      -> []                       (place not found)
 *   q contains "Brokenville"  -> HTTP 500                 (Nominatim error)
 *   anything else             -> one place with fixed coordinates
 *
 * The User-Agent that the backend sent is put into display_name, so the tests
 * can check that it identifies the application (Nominatim usage policy).
 */

$query = $_GET['q'] ?? '';
header('Content-Type: application/json');

if (str_contains($query, 'Nowhere')) {
    echo '[]';
    return;
}
if (str_contains($query, 'Brokenville')) {
    http_response_code(500);
    echo 'Internal error';
    return;
}

echo json_encode([[
    'lat'          => '43.990123',
    'lon'          => '17.271234',
    'display_name' => $query . ' (fake) UA=' . ($_SERVER['HTTP_USER_AGENT'] ?? ''),
]]);
