<?php
/**
 * load6.php - PHP helper functions for loading indicators
 */

/**
 * Create the HTML structure for the loading indicators
 * This is included in tester6.php
 */
function getLoadingIndicatorsHTML() {
    return '<link rel="stylesheet" href="load6.css">' . PHP_EOL .
           '<script src="load6.js" defer></script>';
}

/**
 * Format progress data for the loading indicators
 * @param array $progress The progress data from CarLoading
 * @return array Formatted data for the loading indicators
 */
function formatProgressData($progress) {
    return [
        'iteration' => $progress['iteration'],
        'timestamp' => $progress['timestamp'],
        'lastUID' => $progress['lastUID'],
        'processingCount' => $progress['processingCount'],
        'completeCount' => $progress['completeCount'],
        'fetchedUID' => $progress['fetchedUID'],
        'newCarsCount' => $progress['newCarsCount'],
        'totalCarsCount' => $progress['totalCarsCount'],
        'elapsedTime' => $progress['elapsedTime']
    ];
}

/**
 * Generate JavaScript code to update loading indicators
 * @param array $progress The progress data from CarLoading
 * @return string JavaScript code to update the indicators
 */
function getProgressUpdateJS($progress) {
    $progressJSON = json_encode(formatProgressData($progress));
    
    return "<script>
        if (window.loadingIndicators) {
            window.loadingIndicators.processLoadingUpdate({$progressJSON});
        }
    </script>";
}