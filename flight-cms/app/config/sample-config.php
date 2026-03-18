<?php

// --- Core Settings ---
// Set the default timezone (Adjust as needed)
date_default_timezone_set('UTC');

// Set the error reporting level for development
// In production, consider E_ALL & ~E_NOTICE & ~E_DEPRECATED or just E_ERROR
error_reporting(E_ALL);

// Set the default character encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Set the default locale (Adjust as needed)
if (function_exists('setlocale')) {
    setlocale(LC_ALL, 'en_US.UTF-8');
}

// --- Application Environment ---
// Define the application environment (e.g., 'development', 'production', 'testing')
// This should ideally be set via server environment variables in production.
define('ENVIRONMENT', 'development'); // CHANGE FOR PRODUCTION

// --- FlightPHP Framework Settings ---
$config['flight'] = [
    'base_url'                        => null, // Auto-detect, or set manually like '/' or '/myapp/'
    'case_sensitive'                  => false,
    'log_errors'                      => true,
    'handle_errors'                   => ENVIRONMENT === 'development', // Let Tracy handle in dev, Flight in prod?
    'views.path'                      => BASE_PATH . '/app/views',
    'views.extension'                 => '.latte', // Assuming Latte based on composer.json
    'content_length'                  => true, // Let Flight handle Content-Length
    'debug'                           => ENVIRONMENT === 'development', // Enable Flight's debug mode in dev
    // Add other Flight configurations as needed
];

// --- Routing Configuration ---
$config['routing'] = [
    'routes_file'                     => BASE_PATH . '/app/routes/web.php',
    'default_route'                   => '/',
    'fallback_handler'                => function() {
        return 'Welcome to FlightCMS! Please define routes in app/routes/web.php';
    },
    // You can add other routing settings as needed
];

// --- Database Settings (Example for SQLite) ---
$config['database'] = [
    'type'     => 'sqlite',
    'file'     => BASE_PATH . '/storage/database/flightcms.db',
    // Add other adapters (mysql, pgsql) as needed:
    // 'mysql' => [
    //     'host' => 'localhost',
    //     'port' => 3306,
    //     'user' => 'db_user',
    //     'password' => 'db_pass',
    //     'database' => 'flightcms',
    //     'charset' => 'utf8mb4',
    // ],
];

// --- Tracy Debugger Settings ---
$config['tracy'] = [
    'enabled'         => ENVIRONMENT === 'development', // Enable only in development
    'showBar'         => ENVIRONMENT === 'development',
    'logDirectory'    => BASE_PATH . '/storage/logs',
    'strictMode'      => true, // Show all errors in development
    'maxDepth'        => 4,    // Default BDump depth
    'maxLength'       => 150,  // Default BDump string length
    // Add IPs for production debugging if needed: 'allowedIPs' => ['your_ip']
];

// --- Security Settings ---
$config['security'] = [
    'secret_key' => 'CHANGE_THIS_IN_PRODUCTION_CONFIG!', // Used for hashing, sessions, etc.
    // Add other security-related configs
];

// --- Custom Application Settings ---
$config['app'] = [
    'name' => 'FlightCMS',
    'default_language' => 'en',
    // Add other app-specific settings
];


// Make the config array available
return $config; 