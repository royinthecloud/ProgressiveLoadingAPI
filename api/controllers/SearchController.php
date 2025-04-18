<?php
/**
 * Search Controller
 * 
 * Handles search-related API endpoints.
 */

require_once __DIR__ . '/../services/SearchService.php';

class SearchController {
    // Search service instance
    private $searchService;
    
    /**
     * Constructor - Initialize the search service
     */
    public function __construct() {
        $this->searchService = new SearchService();
    }
    
    /**
     * Initialize a new search
     * Endpoint: /api/search/initialize (POST)
     */
    public function initializeAction() {
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed. Use POST.', 405);
            exit;
        }
        
        try {
            $result = $this->searchService->initialize();
            
            if ($result['success']) {
                Response::success([
                    'resultId' => $result['resultID']
                ]);
            } else {
                Response::error('Failed to initialize search: ' . ($result['error'] ?? 'Unknown error'), 500);
            }
        } catch (Exception $e) {
            Response::error('Error initializing search: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get search status
     * Endpoint: /api/search/status/{resultId} (GET)
     * 
     * @param int $resultId The search result ID
     */
    public function statusAction($resultId) {
        // Only allow GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            Response::error('Method not allowed. Use GET.', 405);
            exit;
        }
        
        if (!$resultId || !is_numeric($resultId) || $resultId <= 0) {
            Response::error('Invalid result ID', 400);
            exit;
        }
        
        try {
            $status = $this->searchService->getStatus($resultId);
            
            // Check if cars exist
            $carCount = $this->searchService->checkCarsExist($resultId);
            
            // Add car count to status
            $status['carCount'] = $carCount;
            
            Response::success($status);
        } catch (Exception $e) {
            Response::error('Error getting search status: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Configure and execute a search
     * Endpoint: /api/search/configure (POST)
     */
    public function configureAction() {
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed. Use POST.', 405);
            exit;
        }
        
        // Get JSON body
        $data = Request::getJsonBody();
        
        // Validate required parameters
        $resultId = Request::validateJsonParam($data, 'resultId', 'int');
        
        // Optional parameters
        $config = [];
        
        if (isset($data['pauseTime'])) {
            $config['pauseTime'] = (int)$data['pauseTime'];
            if ($config['pauseTime'] <= 0) {
                Response::error('pauseTime must be a positive integer', 400);
                exit;
            }
        }
        
        if (isset($data['exitTime'])) {
            $config['exitTime'] = (int)$data['exitTime'];
            if ($config['exitTime'] <= 0) {
                Response::error('exitTime must be a positive integer', 400);
                exit;
            }
        }
        
        try {
            $summary = $this->searchService->executeSearch($resultId, $config);
            Response::success($summary);
        } catch (Exception $e) {
            Response::error('Error configuring search: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Execute a search with default configuration
     * Endpoint: /api/search/execute/{resultId} (POST)
     * 
     * @param int $resultId The search result ID
     */
    public function executeAction($resultId) {
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed. Use POST.', 405);
            exit;
        }
        
        if (!$resultId || !is_numeric($resultId) || $resultId <= 0) {
            Response::error('Invalid result ID', 400);
            exit;
        }
        
        try {
            $summary = $this->searchService->executeSearch($resultId);
            Response::success($summary);
        } catch (Exception $e) {
            Response::error('Error executing search: ' . $e->getMessage(), 500);
        }
    }
}
?>