<?php
/**
 * Car Controller
 * 
 * Handles car data-related API endpoints.
 */

require_once __DIR__ . '/../services/CarService.php';

class CarController {
    // Car service instance
    private $carService;
    
    /**
     * Constructor - Initialize the car service
     */
    public function __construct() {
        $this->carService = new CarService();
    }
    
    /**
     * Get cars for a search result
     * Endpoint: /api/cars/get/{resultId} (GET)
     * 
     * @param int $resultId The search result ID
     */
    public function getAction($resultId) {
        // Only allow GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed. Use GET.', 405);
            exit;
        }
        
        if (!$resultId || !is_numeric($resultId) || $resultId <= 0) {
            Response::error('Invalid result ID', 400);
            exit;
        }
        
        // Get optional parameters
        $limit = Request::getParam('limit', null, 'int');
        $offset = Request::getParam('offset', 0, 'int');
        $sortBy = Request::getParam('sortBy', 'PriceAfterDiscountShekelsWithVAT', 'string');
        $sortDir = Request::getParam('sortDir', 'ASC', 'string');
        
        try {
            $result = $this->carService->getCars($resultId, $limit, $offset, $sortBy, $sortDir);
            Response::success($result);
        } catch (Exception $e) {
            Response::error('Error getting cars: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get incremental car data since last UID
     * Endpoint: /api/cars/incremental/{resultId} (GET)
     * 
     * @param int $resultId The search result ID
     */
    public function incrementalAction($resultId) {
        // Only allow GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed. Use GET.', 405);
            exit;
        }
        
        if (!$resultId || !is_numeric($resultId) || $resultId <= 0) {
            Response::error('Invalid result ID', 400);
            exit;
        }
        
        // Get lastUID parameter
        $lastUID = Request::getParam('lastUID', 0, 'int', true);
        
        try {
            $result = $this->carService->getIncrementalCars($resultId, $lastUID);
            Response::success($result);
        } catch (Exception $e) {
            Response::error('Error getting incremental cars: ' . $e->getMessage(), 500);
        }
    }
}
?>