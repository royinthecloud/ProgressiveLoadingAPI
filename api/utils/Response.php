<?php
/**
 * Response Utility Class
 * 
 * Handles formatting and sending API responses.
 */

class Response {
    /**
     * Send a success response
     * 
     * @param mixed $data The data to include in the response
     * @param int $statusCode HTTP status code (default: 200)
     */
    public static function success($data, $statusCode = 200) {
        self::send([
            'success' => true,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code (default: 400)
     * @param array $details Optional additional error details
     */
    public static function error($message, $statusCode = 400, $details = null) {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message
            ]
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        self::send($response, $statusCode);
    }
    
    /**
     * Send the response as JSON
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    private static function send($data, $statusCode) {
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Add timestamp to response
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        // Output the response as JSON
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
?>