<?php
/**
 * Car Service
 * 
 * Business logic for car data operations.
 */

class CarService {
    // Database connection
    private $conn;
    
    /**
     * Constructor - Initialize the database connection
     */
    public function __construct() {
        $this->conn = openDBconn();
    }
    
    /**
     * Destructor - Close the database connection
     */
    public function __destruct() {
        if ($this->conn) {
            closeDBconn($this->conn);
        }
    }
    
    /**
     * Get all cars for a result ID with optional pagination and sorting
     * 
     * @param int $resultId The search result ID
     * @param int $limit Limit the number of results
     * @param int $offset Offset for pagination
     * @param string $sortBy Field to sort by
     * @param string $sortDir Sort direction ('ASC' or 'DESC')
     * @return array The cars data and total count
     */
    public function getCars($resultId, $limit = null, $offset = 0, $sortBy = 'PriceAfterDiscountShekelsWithVAT', $sortDir = 'ASC') {
        $resultId = (int)$resultId;
        
        if ($resultId <= 0) {
            throw new Exception("Invalid result ID");
        }
        
        // Validate and sanitize sort parameters
        $validSortFields = [
            'PriceAfterDiscountShekelsWithVAT', 
            'CompanyName', 
            'GROUPNAME',
            'PassangersNum'
        ];
        
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'PriceAfterDiscountShekelsWithVAT';
        }
        
        $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
        
        // Build the query
        $sql = "SELECT w.* FROM Integration.WSResult_1002_Sim2_V2 w 
                WHERE w.AutoShayResultID = ? 
                ORDER BY w.{$sortBy} {$sortDir}";
        
        // Add limit and offset if specified
        if ($limit !== null) {
            $sql .= " LIMIT ?, ?";
        }
        
        // Prepare and execute the query
        $stmt = $this->conn->prepare($sql);
        
        if ($limit !== null) {
            $stmt->bind_param("iii", $resultId, $offset, $limit);
        } else {
            $stmt->bind_param("i", $resultId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch the results
        $cars = [];
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as totalCount FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->bind_param("i", $resultId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $totalCount = $countRow['totalCount'] ?? 0;
        
        return [
            'cars' => $cars,
            'totalCount' => $totalCount
        ];
    }
    
    /**
     * Get incremental car data since last UID
     * 
     * @param int $resultId The search result ID
     * @param int $lastUID The last UID retrieved by the client
     * @return array New cars and updated status
     */
    public function getIncrementalCars($resultId, $lastUID) {
        $resultId = (int)$resultId;
        $lastUID = (int)$lastUID;
        
        if ($resultId <= 0) {
            throw new Exception("Invalid result ID");
        }
        
        // SQL query to get new data since lastUID
        $sql = "WITH StatusCounts AS (
                    SELECT 
                        COUNT(CASE WHEN Status = 'Processing' THEN 1 END) AS processing_count,
                        COUNT(CASE WHEN Status = 'Complete' THEN 1 END) AS complete_count
                    FROM Integration.IntegrationLogHeader_V1
                    WHERE ResultID = ?
                )
                SELECT 
                    (SELECT MAX(UID) FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?) AS LastUID,
                    StatusCounts.processing_count,
                    StatusCounts.complete_count,
                    w.*
                FROM 
                    Integration.WSResult_1002_Sim2_V2 w
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
        $stmt->bind_param("iiii", $resultId, $resultId, $resultId, $lastUID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Process the results
        $newCars = [];
        $resultLastUID = $lastUID;
        $processingCount = 0;
        $completeCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Extract metadata from first row
            if (empty($newCars)) {
                $resultLastUID = isset($row['LastUID']) ? (int)$row['LastUID'] : $lastUID;
                $processingCount = isset($row['processing_count']) ? (int)$row['processing_count'] : 0;
                $completeCount = isset($row['complete_count']) ? (int)$row['complete_count'] : 0;
                
                // Remove metadata columns
                unset($row['LastUID']);
                unset($row['processing_count']);
                unset($row['complete_count']);
            }
            
            $newCars[] = $row;
        }
        
        // If no results, get status directly
        if (empty($newCars)) {
            $statusSql = "SELECT 
                COUNT(CASE WHEN Status = 'Processing' THEN 1 END) AS processing_count,
                COUNT(CASE WHEN Status = 'Complete' THEN 1 END) AS complete_count
            FROM Integration.IntegrationLogHeader_V1
            WHERE ResultID = ?";
            
            $statusStmt = $this->conn->prepare($statusSql);
            $statusStmt->bind_param("i", $resultId);
            $statusStmt->execute();
            $statusResult = $statusStmt->get_result();
            $statusRow = $statusResult->fetch_assoc();
            
            $processingCount = isset($statusRow['processing_count']) ? (int)$statusRow['processing_count'] : 0;
            $completeCount = isset($statusRow['complete_count']) ? (int)$statusRow['complete_count'] : 0;
            
            // Get max UID directly
            $uidSql = "SELECT MAX(UID) AS MaxUID FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?";
            $uidStmt = $this->conn->prepare($uidSql);
            $uidStmt->bind_param("i", $resultId);
            $uidStmt->execute();
            $uidResult = $uidStmt->get_result();
            $uidRow = $uidResult->fetch_assoc();
            $resultLastUID = isset($uidRow['MaxUID']) ? (int)$uidRow['MaxUID'] : 0;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as totalCount FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->bind_param("i", $resultId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $totalCount = $countRow['totalCount'] ?? 0;
        
        return [
            'newCars' => $newCars,
            'lastUID' => $resultLastUID,
            'processingCount' => $processingCount,
            'completeCount' => $completeCount,
            'isComplete' => ($processingCount == 0 && $completeCount > 0),
            'totalCount' => $totalCount,
            'newCarsCount' => count($newCars)
        ];
    }
}
?>