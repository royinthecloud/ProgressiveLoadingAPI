<?php
/**
 * API Configuration
 * 
 * This file contains configuration settings for the API.
 */

// Enable error reporting for debugging
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', true);
}

// Configure error reporting based on debug mode
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Base configuration
define('API_VERSION', '1.0.0');

// Default search parameters
define('DEFAULT_PAUSE_TIME', 10); // Seconds to pause between iterations
define('DEFAULT_EXIT_TIME', 60);  // Maximum execution time in seconds

// Log the current directory and path information for debugging
if (function_exists('log850')) {
    log850("API config.php loaded - Current directory: " . __DIR__);
    log850("API config.php - Parent directory: " . dirname(__DIR__));
    log850("API config.php - Requested URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not available'));
}

// Determine if this is the initialize endpoint (which doesn't need DB access)
$isInitializeEndpoint = false;
if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $isInitializeEndpoint = (strpos($requestUri, '/api/search/initialize') !== false);
    
    if (function_exists('log850')) {
        log850("API config.php - Request URI: {$requestUri}");
        log850("API config.php - Is initialize endpoint: " . ($isInitializeEndpoint ? 'Yes' : 'No'));
    }
}

// Set the base directory for includes
$apiBaseDir = dirname(__DIR__);

// Only include database connection if not accessing the initialize endpoint
if (!$isInitializeEndpoint) {
    if (function_exists('log850')) {
        log850("API config.php - Including database connection (not initialize endpoint)");
    }
    
    // Check if conn.php exists before trying to include it
    $connFilePath = $apiBaseDir . '/conn.php';
    if (file_exists($connFilePath)) {
        if (function_exists('log850')) {
            log850("API config.php - conn.php exists at: {$connFilePath}");
        }
        require_once $connFilePath;
    } else {
        if (function_exists('log850')) {
            log850("API config.php - ERROR: conn.php does not exist at: {$connFilePath}");
        }
        // Don't throw error for missing conn.php, as this could break the initialize endpoint
    }
} else {
    if (function_exists('log850')) {
        log850("API config.php - Skipping database connection for initialize endpoint");
    }
}

// Include CarLoading class (required for all endpoints, including initialize)
$carLoadingPath = $apiBaseDir . '/CarLoading.php';
if (file_exists($carLoadingPath)) {
    if (function_exists('log850')) {
        log850("API config.php - Including CarLoading.php from: {$carLoadingPath}");
    }
    require_once $carLoadingPath;
} else {
    if (function_exists('log850')) {
        log850("API config.php - ERROR: CarLoading.php does not exist at: {$carLoadingPath}");
    }
    die("Critical error: CarLoading.php not found");
}
?>