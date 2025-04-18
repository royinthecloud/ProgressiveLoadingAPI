<?php
/**
 * API Router - Main entry point for RESTful API
 * 
 * This file handles all API requests, routing them to appropriate controllers.
 */

// Check if log850 function exists
if (function_exists('log850')) {
    log850("API Router - Request received: " . $_SERVER['REQUEST_URI']);
    log850("Request method: " . $_SERVER['REQUEST_METHOD']);
}

// Set headers for JSON API
header('Content-Type: application/json');

try {
    // Include configuration - using try/catch to handle potential errors
    require_once __DIR__ . '/config.php';
    
    // Include utility classes
    require_once __DIR__ . '/utils/Request.php';
    require_once __DIR__ . '/utils/Response.php';
    
    // Get request method and path
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $requestUri = $_SERVER['REQUEST_URI'];
    
    if (function_exists('log850')) {
        log850("Processing URI: " . $requestUri);
        log850("Script Name: " . $_SERVER['SCRIPT_NAME']);
    }
    
    // Check if path is provided as a query parameter (for direct access)
    if (isset($_GET['path'])) {
        $path = $_GET['path'];
        if (function_exists('log850')) {
            log850("Using path from query parameter: " . $path);
        }
    } else {
        // Extract the path relative to the API folder (for pretty URLs)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Extract the path relative to the API folder
        if (strpos($requestUri, $scriptDir) === 0) {
            $path = substr($requestUri, strlen($scriptDir));
        } else {
            $path = parse_url($requestUri, PHP_URL_PATH);
        }
        
        // Remove query string if present
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        // Remove trailing slash if present
        $path = rtrim($path, '/');
    }
    
    if (function_exists('log850')) {
        log850("Extracted path: " . $path);
    }
    
    // Parse path segments
    $segments = array_values(array_filter(explode('/', $path)));
    
    if (function_exists('log850')) {
        log850("Path segments: " . json_encode($segments));
    }
    
    // Check if we have enough segments
    if (count($segments) < 1) {
        Response::error('Invalid API endpoint', 404);
        exit;
    }
    

    // Extract controller and action from segments
    $controllerName = isset($segments[0]) ? ucfirst($segments[0]) . 'Controller' : '';

 


    $action = isset($segments[1]) ? $segments[1] : '';
    $param = isset($segments[2]) ? $segments[2] : null;
    
    if (function_exists('log850')) {
        log850("Controller: " . $controllerName);
        log850("Action: " . $action);
        log850("Parameter: " . ($param ?? 'none'));
    }
    
    // Special case for search/initialize to avoid DB connection
    $isInitializeEndpoint = ($controllerName === 'SearchController' && $action === 'initialize');
    if ($isInitializeEndpoint && function_exists('log850')) {
        log850("Detected initialize endpoint - skipping DB connection");
    }
    
    // Map controller names to files
    $controllerMap = [
        'SearchController' => __DIR__ . '/controllers/SearchController.php',
        'CarsController' => __DIR__ . '/controllers/CarController.php'
    ];
    
    // Check if the controller exists
    if (!isset($controllerMap[$controllerName])) {
        if (function_exists('log850')) {
            log850("Error: Controller not found: " . $controllerName);
        }
        Response::error('Controller not found: ' . $controllerName, 404);
        exit;
    }
    
    // Check if controller file exists
    if (!file_exists($controllerMap[$controllerName])) {
        if (function_exists('log850')) {
            log850("Error: Controller file not found: " . $controllerMap[$controllerName]);
        }
        Response::error('Controller file not found', 404);
        exit;
    }
    
    // Include the controller file
    require_once $controllerMap[$controllerName];
    
    // Check if controller class exists
    if (!class_exists($controllerName)) {
        if (function_exists('log850')) {
            log850("Error: Controller class not found: " . $controllerName);
        }
        Response::error('Controller class not found: ' . $controllerName, 404);
        exit;
    }
    
    // Create controller instance
    $controller = new $controllerName();
    
    // Convert action to method name (e.g., 'status' becomes 'statusAction')
    $methodName = $action . 'Action';
    
    // Check if the method exists
    if (!method_exists($controller, $methodName)) {
        if (function_exists('log850')) {
            log850("Error: Action not found: " . $action);
        }
        Response::error('Action not found: ' . $action, 404);
        exit;
    }
    
    if (function_exists('log850')) {
        log850("Calling controller method: " . $controllerName . "->" . $methodName);
    }
    
    // Call the controller method with the appropriate parameters
    $controller->$methodName($param);
    
} catch (Exception $e) {
    // Log the exception
    if (function_exists('log850')) {
        log850("API Error Exception: " . $e->getMessage());
        log850("Stack trace: " . $e->getTraceAsString());
    }
    
    // Handle exceptions
    Response::error('API Error: ' . $e->getMessage(), 500);
}
?>