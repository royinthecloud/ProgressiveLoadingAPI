<?php
/**
 * Search Service
 * 
 * Business logic for search-related operations.
 */

class SearchService {
    /**
     * Initialize a new search by calling the wrapper service
     * 
     * @return array Result with resultID or error
     */
    public function initialize() {
        return CarLoading::callWrapperService();
    }
    
    /**
     * Get the status of a search
     * 
     * @param int $resultId The search result ID
     * @return array Status information
     */
    public function getStatus($resultId) {
        $resultId = (int)$resultId;
        
        if ($resultId <= 0) {
            throw new Exception("Invalid result ID");
        }
        
        $carLoader = new CarLoading($resultId);
        return $carLoader->getStatus();
    }
    
    /**
     * Execute a search in the background
     * 
     * @param int $resultId The search result ID
     * @param array $config Configuration options
     * @return array Summary of the search execution
     */
    public function executeSearch($resultId, $config = []) {
        $resultId = (int)$resultId;
        
        if ($resultId <= 0) {
            throw new Exception("Invalid result ID");
        }
        
        // Set default configuration if not provided
        if (!isset($config['pauseTime'])) {
            $config['pauseTime'] = DEFAULT_PAUSE_TIME;
        }
        
        if (!isset($config['exitTime'])) {
            $config['exitTime'] = DEFAULT_EXIT_TIME;
        }
        
        // Create CarLoading instance
        $carLoader = new CarLoading($resultId, $config);
        
        // Start the progressive loading algorithm
        $result = $carLoader->execute();
        
        return $result['summary'];
    }
    
    /**
     * Check if cars exist for a given result ID
     * 
     * @param int $resultId The search result ID
     * @return int Number of cars
     */
    public function checkCarsExist($resultId) {
        $resultId = (int)$resultId;
        
        if ($resultId <= 0) {
            throw new Exception("Invalid result ID");
        }
        
        $carLoader = new CarLoading($resultId);
        return $carLoader->checkCarsExist();
    }
}
?>