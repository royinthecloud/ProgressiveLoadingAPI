
<?php

function openDBconn() {

 // Connect to database
    $servername = "autoshay-mysql-new.cilucdyakkvg.us-east-1.rds.amazonaws.com";
    $username = "Autoshay";
    $password = "Autoshay2020";
    $dbname = "Integration";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  return $conn;

}


function closeDBconn($conn) {

// Close database connection
  $conn->close();

}




function log850($entry, $mode = 'a', $file = 'apiV1log') 
{
    $debug = 'Y';
    if ($debug == 'Y') {
        $upload_dir = '/var/www/html/integration/8_55/v9b';
     
        // Prepare the log entry
        if (empty($entry)) {
            $entry = 'object is not set';
        } elseif (is_array($entry) || is_object($entry)) {
            $entry = json_encode($entry);
        }
        
        // Get caller information
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]) ? $backtrace[1] : [];
        
        $callerFunction = isset($caller['function']) ? $caller['function'] : 'unknown_function';
        $callerClass = isset($caller['class']) ? $caller['class'] : '';
        $callerType = isset($caller['type']) ? $caller['type'] : '';
        
        // Format caller info
        $callerInfo = $callerClass . $callerType . $callerFunction;
        
        // Construct the log message with timestamp and caller information
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = $timestamp . " [" . $callerInfo . "] :: " . $entry . "\n";
        
        // File path - use a single consistent file path
        $file_path = $upload_dir . '/' . $file . '.txt';
        
        // Use file locking to prevent race conditions
        $fp = fopen($file_path, 'c+');  // Open for reading and writing, create if doesn't exist
        
        if ($fp) {
            // Acquire an exclusive lock
            if (flock($fp, LOCK_EX)) {
                try {
                    if ($mode == 'w') {
                        // Write mode: clear file and write new entry
                        ftruncate($fp, 0);  // Clear the file
                        rewind($fp);  // Go back to the beginning
                        fwrite($fp, $logMessage);  // Write the new log entry
                    } else {
                        // Append mode with prepending (new entries at the top)
                        $filesize = filesize($file_path);
                        $existingContent = '';
                        
                        // If file has content, read it
                        if ($filesize > 0) {
                            $existingContent = fread($fp, $filesize);
                        }
                        
                        // Go back to beginning and write combined content
                        rewind($fp);
                        fwrite($fp, $logMessage . $existingContent);
                        
                        // Ensure all content is written
                        fflush($fp);
                    }
                    
                    // Release the lock
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    return true;
                } catch (Exception $e) {
                    // Handle any exceptions
                    error_log("Error in log850: " . $e->getMessage());
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    return false;
                }
            } else {
                // Could not get a lock
                fclose($fp);
                error_log("Could not get a lock on file: " . $file_path);
                return false;
            }
        } else {
            // Could not open the file
            error_log("Could not open log file: " . $file_path);
            return false;
        }
    }
    
    return false;
}
