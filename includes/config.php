<?php
// Environment detection
$is_production = getenv('ENVIRONMENT') === 'production' || getenv('DATABASE_URL') !== false;

// Database configuration
if ($is_production && getenv('DATABASE_URL')) {
    // Neon PostgreSQL configuration
    $database_url = getenv('DATABASE_URL');
    $parsed_url = parse_url($database_url);
    
    define('DB_HOST', $parsed_url['host']);
    define('DB_NAME', ltrim($parsed_url['path'], '/'));
    define('DB_USER', $parsed_url['user']);
    define('DB_PASS', $parsed_url['pass']);
    define('DB_PORT', $parsed_url['port'] ?? 5432);
    define('DB_SSLMODE', 'require');
} else {
    // Local PostgreSQL configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'rasa_db');
    define('DB_USER', 'postgres');
    define('DB_PASS', 'numugisha');
    define('DB_PORT', 5432);
    define('DB_SSLMODE', 'prefer');
}

// Site configuration
if ($is_production) {
    define('SITE_NAME', 'RASA RP MUSANZE COLLEGE');
    define('SITE_URL', 'https://your-production-domain.com');
} else {
    define('SITE_NAME', 'RASA RP MUSANZE COLLEGE');
    define('SITE_URL', 'http://localhost/rasa');
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
