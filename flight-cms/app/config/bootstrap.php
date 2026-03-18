<?php

use Tracy\Debugger;
use FlightCms\App\Views\Template;
use FlightCms\App\Middleware\HeaderSecurity;

// --- Essential Constants ---
define('DS', DIRECTORY_SEPARATOR);
// Assuming bootstrap.php is in app/config, BASE_PATH is one level up from app/
define('BASE_PATH', dirname(__DIR__, 2));

// --- Composer Autoloader ---
$autoloader = BASE_PATH . '/lib/autoload.php';
if (!file_exists($autoloader)) {
    die("Composer autoload file not found. Please run 'composer install'.");
}
require $autoloader;

// --- Load Configuration ---
$configLoader = __DIR__ . '/config.php';
if (!file_exists($configLoader)) {
    die("Main configuration loader not found.");
}
$config = require $configLoader;

// --- Error Handling & Debugging (Tracy) ---
if (!empty($config['tracy']['enabled'])) {
    Debugger::enable(
        false, // Do not detect environment automatically, use config
        $config['tracy']['logDirectory'] ?? BASE_PATH . '/storage/logs'
    );
    Debugger::$strictMode = $config['tracy']['strictMode'] ?? true;
    Debugger::$maxDepth = $config['tracy']['maxDepth'] ?? 4;
    Debugger::$maxLength = $config['tracy']['maxLength'] ?? 150;

    // Disable Flight's content length calculation if Tracy bar is shown
    if (!empty($config['tracy']['showBar'])) {
        $config['flight']['content_length'] = false;
        // Optional: Load Flight Tracy extensions if installed and needed
        // if (class_exists('\flight\debug\tracy\TracyExtensionLoader')) {
        //     new \flight\debug\tracy\TracyExtensionLoader(Flight::app(), $config);
        // }
    }
} else {
    // Production error handling (configure as needed)
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    // ini_set('error_log', BASE_PATH . '/storage/logs/php_error.log'); // Example
}

// --- Configure Flight ---
foreach ($config['flight'] as $key => $value) {
    Flight::set($key, $value);
}

// --- Register Core Services ---
// Template Engine (adjust class name if different)
Flight::register('template', Template::class, [], function($template) use ($config) {
    // Pass config or specific settings to the template engine if needed
    // $template->setBasePath($config['flight']['views.path']);
    // $template->setCachePath(BASE_PATH . '/storage/cache/views'); // Example
    // $template->setDebug($config['flight']['debug']); // Example for Latte
});

// Database Connection (using Flight's Container and Active Record)
// Example for Active Record setup (adjust based on your chosen DB library)
if (class_exists('\flight\database\PdoWrapper')) {
    Flight::register('db', \flight\database\PdoWrapper::class, [$config['database']['type'].':'.$config['database']['file']], function($db) {
        // Potentially configure the wrapper further if needed
        // $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    });
}

// --- Setting Security Headers ---
$securityHeaders = new HeaderSecurity();
$securityHeaders->before();

// --- Load Application Routes ---
$routesFile = $config['routing']['routes_file'] ?? BASE_PATH . '/app/routes/web.php';
if (file_exists($routesFile)) {
    require $routesFile;
} else {
    // Use the default handler from the configuration
    $fallbackHandler = $config['routing']['fallback_handler'] ?? function() {
        echo 'Welcome to FlightCMS! Please define routes in app/routes/web.php';
    };
    $defaultRoute = $config['routing']['default_route'] ?? '/';
    
    Flight::route($defaultRoute, $fallbackHandler);
}

// --- Additional Bootstrap Tasks ---
// Start sessions, load helpers, etc.
// session_start(); // If using native sessions