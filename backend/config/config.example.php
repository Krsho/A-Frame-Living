<?php
/*
 * Configuration TEMPLATE – this file is committed to Git.
 *
 * To run the backend:
 *   1. Copy this file to backend/config/config.php
 *   2. Fill in your own values there.
 *
 * config.php is git-ignored, so passwords and secrets never end up on GitHub.
 */

return [
    // MySQL connection (used by backend/config/Database.php)
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'aframe_living',
        'user'     => 'root',
        'password' => '',          // XAMPP's default root user has no password
        'charset'  => 'utf8mb4',
    ],

    // JSON Web Tokens – used from Milestone 4 (authentication)
    'jwt' => [
        'secret'     => 'replace-with-a-long-random-secret',
        'expires_in' => 3600,      // seconds
    ],
];
