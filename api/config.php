<?php
/**
 * API Configuration
 * 
 * This file contains configuration settings for the API.
 */

// Base configuration
define('API_VERSION', '1.0.0');
define('DEBUG_MODE', true);




// Default search parameters
define('DEFAULT_PAUSE_TIME', 10); // Seconds to pause between iterations
define('DEFAULT_EXIT_TIME', 60);  // Maximum execution time in seconds

// Database connection is handled by including conn.php
 $apiBaseDir = dirname(__DIR__);

$apiBaseDir= "/var/www/html/integration/8_55/v9b";


// require_once $apiBaseDir . '/conn.php';

// Error reporting configuration
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Include path for CarLoading class
 require_once $apiBaseDir . '/CarLoading.php';
?>