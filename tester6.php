<?php
/**
 * tester6.php - Streamlined car loading interface with immediate loading indicator display
 */

// Start output buffering to control when HTML is sent to browser
ob_start();

// Include necessary files
require_once 'load6.php';

// Configuration
$PAUSE_TIME = 10;
$EXIT_TIME = 60;

// Check if a resultID was provided
$resultId = isset($_GET['resultid']) ? intval($_GET['resultid']) : 0;
$isNewSearch = $resultId === 0;

// For AJAX requests - handle them immediately
if (isset($_GET['action'])) {
    ob_end_clean(); // Clear output buffer
    
    if ($_GET['action'] === 'getCars') {
        header('Content-Type: application/json');
        
        // Connect to DB
        require_once 'conn.php';
        $conn = openDBconn();
        
        // Query for all cars (no limit)
        $sql = "SELECT 
                    w.*
                FROM 
                    Integration.WSResult_1002_Sim2_V2 w 
                WHERE   
                    w.AutoShayResultID = ?
                ORDER BY 
                    w.PriceAfterDiscountShekelsWithVAT ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resultId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cars = [];
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as totalCount FROM Integration.WSResult_1002_Sim2_V2 WHERE AutoShayResultID = ?";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $resultId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $totalCount = $countRow['totalCount'] ?? 0;
        
        closeDBconn($conn);
        
        echo json_encode([
            'cars' => $cars,
            'totalCount' => $totalCount
        ]);
        exit;
    } else if ($_GET['action'] === 'getStatus') {
        header('Content-Type: application/json');
        
        require_once 'CarLoading.php';
        $carLoader = new CarLoading($resultId);
        $status = $carLoader->getStatus();
        
        echo json_encode($status);
        exit;
    } else if ($_GET['action'] === 'startSearch') {
        // This action starts the search in the background
        header('Content-Type: application/json');
        
        require_once 'CarLoading.php';
        
        // Get the resultId from the request
        $searchResultId = intval($_GET['resultid']);
        
        // Create CarLoading instance
        $carLoader = new CarLoading($searchResultId, [
            'pauseTime' => $PAUSE_TIME,
            'exitTime' => $EXIT_TIME
        ]);
        
        // Start the progressive loading algorithm
        $result = $carLoader->execute();
        
        echo json_encode(['success' => true, 'summary' => $result['summary']]);
        exit;
    }
}

// If this is a new search, get a new resultId but don't start processing yet
$searchStarted = false;
$searchError = null;

if ($isNewSearch) {
    require_once 'CarLoading.php';
    $wrapperResponse = CarLoading::callWrapperService();
    
    if ($wrapperResponse['success'] && isset($wrapperResponse['resultID'])) {
        $resultId = $wrapperResponse['resultID'];
        $searchStarted = true;
    } else {
        $searchError = isset($wrapperResponse['error']) ? $wrapperResponse['error'] : 'Unknown error';
    }
}

// Output the HTML immediately, then start background processing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Search Results</title>
    <link rel="stylesheet" href="tester6.css">
    <?php echo getLoadingIndicatorsHTML(); ?>
</head>
<body>
    <div class="container">
        <header>
            <h1>Car Search Results</h1>
            <?php if ($resultId > 0): ?>
                <div class="result-info">
                    <span class="result-id">Result ID: <?php echo $resultId; ?></span>
                    <?php if ($isNewSearch): ?>
                        <span class="search-status">(New Search)</span>
                    <?php else: ?>
                        <span class="search-status">(Existing Search)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>
        
        <?php if ($searchError): ?>
            <div class="error-message">
                <p>Failed to start search: <?php echo htmlspecialchars($searchError); ?></p>
            </div>
        <?php else: ?>
            <div class="search-container">
                <div class="search-status-bar">
                    <div class="status-indicators">
                        <span id="carCount">0 cars found</span>
                        <span id="searchStatus" class="<?php echo $isNewSearch ? 'searching' : ''; ?>">
                            <?php echo $isNewSearch ? 'Searching...' : 'Showing all results'; ?>
                        </span>
                    </div>
                    <div id="loadingIndicator" class="loading-indicator <?php echo $isNewSearch ? 'active' : ''; ?>">
                        <div class="spinner"></div>
                    </div>
                </div>
                
                <!-- The initial loading indicator will be created by load6.js -->
                
                <div id="carResults" class="car-results">
                    <!-- Cars will be loaded here -->
                    <div id="noResults" class="no-results" style="display: none;">
                        <p>No cars found for this search.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Templates -->
    <template id="carCardTemplate">
        <div class="car-card">
            <div class="car-header">
                <h3 class="car-title"></h3>
                <div class="company-info">
                    <span class="company-name"></span>
                </div>
            </div>
            <div class="car-details">
                <div class="car-image">
                    <img src="" alt="Car Image">
                </div>
                <div class="car-specs">
                    <div class="spec-item">
                        <span class="spec-label">Passengers:</span>
                        <span class="passengers-num"></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Transmission:</span>
                        <span class="transmission"></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">A/C:</span>
                        <span class="has-ac"></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Suitcases:</span>
                        <span class="suitcases"></span>
                    </div>
                </div>
            </div>
            <div class="car-footer">
                <div class="price-container">
                    <span class="price-label">Price:</span>
                    <span class="price-value"></span>
                    <span class="price-currency"></span>
                </div>
                <div class="location-info">
                    <span class="branch-name"></span>
                </div>
            </div>
        </div>
    </template>
    
    <script>
        // Pass PHP variables to JavaScript
        const resultId = <?php echo $resultId; ?>;
        const isNewSearch = <?php echo $isNewSearch ? 'true' : 'false'; ?>;
        const pauseTime = <?php echo $PAUSE_TIME; ?>;
        const exitTime = <?php echo $EXIT_TIME; ?>;
    </script>
    <script src="tester6.js"></script>
</body>
</html>
<?php
// Flush the output buffer to send HTML to browser immediately
ob_end_flush();
flush();

// For new searches, start the backend search process via AJAX instead of blocking
if ($isNewSearch && $resultId > 0) {
    // The JavaScript will handle starting the search
}
?>