<?php
/**
 * API Router - Main entry point for RESTful API
 * 
 * This file handles all API requests, routing them to appropriate controllers.
 */

// Set headers for JSON API
header('Content-Type: application/json');

// Include configuration
require_once __DIR__ . '/config.php';

// Include utility classes
require_once __DIR__ . '/utils/Request.php';
require_once __DIR__ . '/utils/Response.php';

// Get request method and path
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Extract the path relative to the API folder
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));

// Remove trailing slash if present
$path = rtrim($path, '/');

// Parse path segments
$segments = explode('/', ltrim($path, '/'));

// Check if we have enough segments
if (count($segments) < 1) {
    Response::error('Invalid API endpoint', 404);
    exit;
}

// Extract controller and action from segments
$controllerName = isset($segments[0]) ? ucfirst($segments[0]) . 'Controller' : '';
$action = isset($segments[1]) ? $segments[1] : '';
$param = isset($segments[2]) ? $segments[2] : null;

// Map controller names to files
$controllerMap = [
    'SearchController' => __DIR__ . '/controllers/SearchController.php',
    'CarsController' => __DIR__ . '/controllers/CarController.php'
];

// Check if the controller exists
if (!isset($controllerMap[$controllerName])) {
    Response::error('Controller not found: ' . $controllerName, 404);
    exit;
}

// Include the controller file
require_once $controllerMap[$controllerName];

// Create controller instance
$controller = new $controllerName();

// Process the request
try {
    // Convert action to method name (e.g., 'status' becomes 'statusAction')
    $methodName = $action . 'Action';
    
    // Check if the method exists
    if (!method_exists($controller, $methodName)) {
        Response::error('Action not found: ' . $action, 404);
        exit;
    }
    
    // Call the controller method with the appropriate parameters
    $controller->$methodName($param);
    
} catch (Exception $e) {
    // Handle exceptions
    Response::error('API Error: ' . $e->getMessage(), 500);
}
?>