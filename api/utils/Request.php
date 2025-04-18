<?php
/**
 * Request Utility Class
 * 
 * Handles request validation and parsing.
 */

class Request {
    /**
     * Get and validate a GET parameter
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value if parameter is not set
     * @param string $type Type to validate ('int', 'string', etc.)
     * @param bool $required Whether the parameter is required
     * @return mixed The parameter value
     */
    public static function getParam($name, $default = null, $type = null, $required = false) {
        // Check if parameter exists
        if (!isset($_GET[$name])) {
            if ($required) {
                Response::error("Required parameter '{$name}' is missing", 400);
                exit;
            }
            return $default;
        }
        
        $value = $_GET[$name];
        
        // Validate type if specified
        if ($type !== null) {
            switch ($type) {
                case 'int':
                    if (!is_numeric($value) || (int)$value != $value) {
                        Response::error("Parameter '{$name}' must be an integer", 400);
                        exit;
                    }
                    $value = (int)$value;
                    break;
                    
                case 'float':
                    if (!is_numeric($value)) {
                        Response::error("Parameter '{$name}' must be a number", 400);
                        exit;
                    }
                    $value = (float)$value;
                    break;
                    
                case 'bool':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                    
                case 'string':
                    $value = (string)$value;
                    break;
            }
        }
        
        return $value;
    }
    
    /**
     * Get and validate data from the request body (JSON)
     * 
     * @return array The parsed JSON data
     */
    public static function getJsonBody() {
        $json = file_get_contents('php://input');
        
        if (empty($json)) {
            return [];
        }
        
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON in request body: ' . json_last_error_msg(), 400);
            exit;
        }
        
        return $data;
    }
    
    /**
     * Validate that a parameter exists in the JSON body
     * 
     * @param array $data The JSON data
     * @param string $name Parameter name
     * @param string $type Type to validate ('int', 'string', etc.)
     * @return mixed The parameter value
     */
    public static function validateJsonParam($data, $name, $type = null) {
        if (!isset($data[$name])) {
            Response::error("Required parameter '{$name}' is missing from request body", 400);
            exit;
        }
        
        $value = $data[$name];
        
        // Validate type if specified
        if ($type !== null) {
            switch ($type) {
                case 'int':
                    if (!is_numeric($value) || (int)$value != $value) {
                        Response::error("Parameter '{$name}' must be an integer", 400);
                        exit;
                    }
                    $value = (int)$value;
                    break;
                    
                case 'float':
                    if (!is_numeric($value)) {
                        Response::error("Parameter '{$name}' must be a number", 400);
                        exit;
                    }
                    $value = (float)$value;
                    break;
                    
                case 'bool':
                    if (!is_bool($value)) {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    }
                    break;
                    
                case 'string':
                    if (!is_string($value)) {
                        Response::error("Parameter '{$name}' must be a string", 400);
                        exit;
                    }
                    break;
            }
        }
        
        return $value;
    }
}
?>