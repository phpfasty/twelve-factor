<?php

// In a real application, you might check an environment variable
// to load different config files (e.g., production-config.php)
// $environment = getenv('APP_ENV') ?: 'development';
// $configFile = __DIR__ . '/' . $environment . '-config.php';

$configFile = __DIR__ . '/sample-config.php';

if (!file_exists($configFile)) {
    die("Configuration file not found: " . htmlspecialchars($configFile));
}

// Load the configuration array
$config = require $configFile;

// You could potentially merge with a local config file here for overrides
// if (file_exists(__DIR__ . '/local-config.php')) {
//     $localConfig = require __DIR__ . '/local-config.php';
//     $config = array_replace_recursive($config, $localConfig);
// }

return $config; 