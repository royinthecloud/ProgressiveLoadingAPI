<?php
/**
 * CarLoading.php - Core functionality for progressive car loading
 * 
 * This class handles the progressive loading algorithm for car data,
 * separated from the UI layer for better maintainability.
 */

class CarLoading {
    // Database connection
    private $conn;
    
    // Configuration variables
    private $pauseTime = 10;   // Seconds to pause between iterations
    private $exitTime = 60;    // Maximum execution time in seconds
    
    // Status tracking
    private $resultId;
    private $fetchedUID = 0;
    private $allCars = [];
    private $startTime;
    private $runCount = 0;
    private $processingCount = 1;
    private $completeCount = 0;
    private $lastUID = 0;
    
    // Callback for reporting progress
    private $progressCallback = null;
    
    /**
     * Constructor - Initialize with result ID and optional configuration
     */
    public function __construct($resultId, $config = []) {
        // Require database connection functions
        require_once 'conn.php';
        
        // Set result ID
        $this->resultId = intval($resultId);
        
        // Apply configuration if provided
        if (isset($config['pauseTime'])) {
            $this->pauseTime = intval($config['pauseTime']);
        }
        
        if (isset($config['exitTime'])) {
            $this->exitTime = intval($config['exitTime']);
        }
        
        // Initialize database connection
        $this->conn = openDBconn();
    }
    
    /**
     * Destructor - Clean up resources
     */
    public function __destruct() {
        // Close database connection if open
        if ($this->conn) {
            closeDBconn($this->conn);
        }
    }
    
    /**
     * Set a callback function to receive progress updates
     */
    public function setProgressCallback($callback) {
        if (is_callable($callback)) {
            $this->progressCallback = $callback;
        }
    }
    
    /**
     * Call the wrapper service to get a new resultID
     */
    public static function callWrapperService() {
        $wrapperUrl = "https://search.auto-shay.com/integration/8_51/v3_2/wrapperCaller_v1.php";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $wrapperUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = "cURL error: " . curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'error' => $error];
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => "JSON parsing error: " . json_last_error_msg()];
        }
        
        if (isset($data['resultID'])) {
            return ['success' => true, 'resultID' => intval($data['resultID'])];
        } else {
            return ['success' => false, 'error' => "No resultID in response", 'response' => $data];
        }
    }
    
    /**
     * Check if cars exist for a result ID
     */
    public function checkCarsExist() {
        $sql = "SELECT COUNT(*) as count FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $this->resultId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return intval($row['count']);
    }
    
    /**
     * Report progress via the callback function
     */
    private function reportProgress($newCars) {
        if ($this->progressCallback) {
            $progress = [
                'iteration' => $this->runCount,
                'timestamp' => date('Y-m-d H:i:s'),
                'lastUID' => $this->lastUID,
                'processingCount' => $this->processingCount,
                'completeCount' => $this->completeCount,
                'fetchedUID' => $this->fetchedUID,
                'newCarsCount' => count($newCars),
                'totalCarsCount' => count($this->allCars),
                'elapsedTime' => time() - $this->startTime,
                'newCars' => $newCars,
            ];
            
            call_user_func($this->progressCallback, $progress);
        }
    }
    
    /**
     * Execute a single iteration of the data fetching algorithm
     */
    private function executeIteration() {
        // SQL query to get data
        $sql = "WITH LastUIDValue AS (
                    SELECT MAX(UID) AS MaxUID 
                    FROM Integration.WSResult_1002_Sim2_V2 
                    WHERE AutoShayResultID = ?
                ),
                StatusCounts AS (
                    SELECT 
                        COUNT(CASE WHEN Status = 'Processing' THEN 1 END) AS processing_count,
                        COUNT(CASE WHEN Status = 'Complete' THEN 1 END) AS complete_count
                    FROM Integration.IntegrationLogHeader_V1
                    WHERE ResultID = ?
                )
                SELECT 
                    LastUIDValue.MaxUID AS LastUID,
                    StatusCounts.processing_count,
                    StatusCounts.complete_count,
                    w.*
                FROM 
                    Integration.WSResult_1002_Sim2_V2 w
                CROSS JOIN
                    LastUIDValue
                CROSS JOIN
                    StatusCounts
                WHERE 
                    w.AutoShayResultID = ?
                    AND
                    w.UID > ?
                ORDER BY 
                    w.PriceAfterDiscountShekelsWithVAT ASC";
        
        // Execute the query
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiii", $this->resultId, $this->resultId, $this->resultId, $this->fetchedUID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Process the results
        $newCars = [];
        
        $row = $result->fetch_assoc();
        if ($row) {
            // Extract metadata from the first row
            $this->lastUID = isset($row['LastUID']) ? (int)$row['LastUID'] : $this->fetchedUID;
            $this->processingCount = isset($row['processing_count']) ? (int)$row['processing_count'] : 0;
            $this->completeCount = isset($row['complete_count']) ? (int)$row['complete_count'] : 0;
            
            // Remove metadata columns
            unset($row['LastUID']);
            unset($row['processing_count']);
            unset($row['complete_count']);
            
            // Add to cars collection
            $newCars[] = $row;
            
            // Process remaining rows
            while ($row = $result->fetch_assoc()) {
                $newCars[] = $row;
            }
        } else {
            // If no results, get status directly
            $statusSql = "SELECT 
                COUNT(CASE WHEN Status = 'Processing' THEN 1 END) AS processing_count,
                COUNT(CASE WHEN Status = 'Complete' THEN 1 END) AS complete_count
            FROM Integration.IntegrationLogHeader_V1
            WHERE ResultID = ?";
            
            $statusStmt = $this->conn->prepare($statusSql);
            $statusStmt->bind_param("i", $this->resultId);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();
            $statusRow = $statusResult->fetch_assoc();
            
            $this->processingCount = isset($statusRow['processing_count']) ? (int)$statusRow['processing_count'] : 0;
            $this->completeCount = isset($statusRow['complete_count']) ? (int)$statusRow['complete_count'] : 0;
            
            // Get max UID directly
            $uidSql = "SELECT MAX(UID) AS MaxUID FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?";
            $uidStmt = $this->conn->prepare($uidSql);
            $uidStmt->bind_param("i", $this->resultId);
            $uidStmt->execute();
            $uidResult = $uidStmt->get_result();
            $uidRow = $uidResult->fetch_assoc();
            $this->lastUID = isset($uidRow['MaxUID']) ? (int)$uidRow['MaxUID'] : 0;
        }
        
        // Add new cars to our collection
        if (!empty($newCars)) {
            $this->allCars = array_merge($this->allCars, $newCars);
        }
        
        // Update fetchedUID for the next iteration
        $this->fetchedUID = $this->lastUID;
        
        return $newCars;
    }
    
    /**
     * Execute the progressive loading algorithm
     */
    public function execute() {
        // Initialize tracking variables
        $this->startTime = time();
        $this->runCount = 0;
        $this->fetchedUID = 0;
        $this->allCars = [];
        
        // Get initial car count
        $initialCarCount = $this->checkCarsExist();
        
        // First iteration setup
        $this->processingCount = 1; // Initialize as processing
        $this->completeCount = 0;   // Initialize as not complete
        
        $summary = [
            'resultId' => $this->resultId,
            'initialCarCount' => $initialCarCount,
            'startTime' => $this->startTime,
            'iterations' => []
        ];
        
        // Main algorithm loop
        do {
            $this->runCount++;
            
            // Execute a single iteration
            $newCars = $this->executeIteration();
            
            // Report progress
            $this->reportProgress($newCars);
            
            // Add iteration data to summary
            $summary['iterations'][] = [
                'iteration' => $this->runCount,
                'timestamp' => date('Y-m-d H:i:s', time()),
                'lastUID' => $this->lastUID,
                'processingCount' => $this->processingCount,
                'completeCount' => $this->completeCount,
                'fetchedUID' => $this->fetchedUID,
                'newCarsCount' => count($newCars),
                'totalCarsCount' => count($this->allCars)
            ];
            
            // Pause for specified time if still processing
            if (($this->processingCount > 0 || $this->completeCount === 0) || $this->runCount === 1) {
                sleep($this->pauseTime);
            }
            
        } while ((time() - $this->startTime < $this->exitTime) && 
                 (($this->processingCount > 0 || $this->completeCount === 0) || $this->runCount === 1));
        
        // Complete the summary with final stats
        $summary['endTime'] = time();
        $summary['elapsedTime'] = $summary['endTime'] - $summary['startTime'];
        $summary['totalIterations'] = $this->runCount;
        $summary['finalProcessingCount'] = $this->processingCount;
        $summary['finalCompleteCount'] = $this->completeCount;
        $summary['totalCarsCount'] = count($this->allCars);
        $summary['isComplete'] = ($this->processingCount == 0 && $this->completeCount > 0);
        
        return [
            'summary' => $summary,
            'cars' => $this->allCars
        ];
    }
    
    /**
     * Get all the cars collected so far
     */
    public function getCars() {
        return $this->allCars;
    }
    
    /**
     * Get the current status (processing/complete counts)
     */
    public function getStatus() {
        return [
            'processingCount' => $this->processingCount,
            'completeCount' => $this->completeCount,
            'isComplete' => ($this->processingCount == 0 && $this->completeCount > 0)
        ];
    }
}
?>