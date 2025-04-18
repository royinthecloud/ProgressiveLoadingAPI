<?php
/**
 * test-db.php - Test database connection
 * 
 * This file is used to test the database connection directly,
 * bypassing the API layer to isolate connection issues.
 */

// Enable detailed error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/db-test-errors.log');

// Function to log diagnostic information
function logDiagnostic($message, $data = null) {
    echo "<div style='margin-bottom: 10px; padding: 10px; border-bottom: 1px solid #ccc;'>";
    echo "<strong>{$message}</strong>";
    
    if ($data !== null) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
    echo "</div>";
}

// Output as HTML
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            background-color: #f5f7fa;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .test-section {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .step {
            border-left: 4px solid #3498db;
            padding-left: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>
    
    <div class="test-section">
        <h2>System Information</h2>
        <div class="step">
            <?php
            // Display PHP version
            logDiagnostic("PHP Version", phpversion());
            
            // Display loaded PHP extensions
            logDiagnostic("Loaded Extensions", implode(', ', get_loaded_extensions()));
            
            // Check for required extensions
            $requiredExtensions = ['mysqli', 'json', 'pdo', 'pdo_mysql'];
            $missingExtensions = [];
            
            foreach ($requiredExtensions as $ext) {
                if (!extension_loaded($ext)) {
                    $missingExtensions[] = $ext;
                }
            }
            
            if (empty($missingExtensions)) {
                echo "<div class='success'>All required extensions are loaded.</div>";
            } else {
                echo "<div class='error'>Missing required extensions: " . implode(', ', $missingExtensions) . "</div>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>File Existence Check</h2>
        <div class="step">
            <?php
            // Check if conn.php exists
            $connFilePath = __DIR__ . '/conn.php';
            if (file_exists($connFilePath)) {
                echo "<div class='success'>conn.php file exists at: {$connFilePath}</div>";
                
                // Check if conn.php is readable
                if (is_readable($connFilePath)) {
                    echo "<div class='success'>conn.php is readable</div>";
                } else {
                    echo "<div class='error'>conn.php exists but is not readable. Check file permissions.</div>";
                }
                
                // Display file size and modification time
                echo "<p>File size: " . filesize($connFilePath) . " bytes</p>";
                echo "<p>Last modified: " . date("Y-m-d H:i:s", filemtime($connFilePath)) . "</p>";
            } else {
                echo "<div class='error'>conn.php file does not exist at: {$connFilePath}</div>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Connection File Content Check</h2>
        <div class="step">
            <?php
            if (file_exists($connFilePath) && is_readable($connFilePath)) {
                // Get the first few lines of conn.php (avoid displaying credentials)
                $connFileContent = file_get_contents($connFilePath);
                $lines = explode("\n", $connFileContent);
                $safeLines = [];
                
                foreach ($lines as $line) {
                    // Redact lines containing passwords or sensitive information
                    if (preg_match('/(password|passwd|pwd|secret|key)/i', $line)) {
                        $safeLines[] = preg_replace('/(["\']\s*=>\s*["\']).*(["\']\s*,?)/', '$1*****$2', $line);
                    } else {
                        $safeLines[] = $line;
                    }
                }
                
                echo "<strong>First 20 lines of conn.php (with credentials redacted):</strong>";
                echo "<pre>" . htmlspecialchars(implode("\n", array_slice($safeLines, 0, 20))) . "</pre>";
                
                // Check if the file contains the required functions
                if (strpos($connFileContent, 'function openDBconn') !== false) {
                    echo "<div class='success'>openDBconn function found in conn.php</div>";
                } else {
                    echo "<div class='error'>openDBconn function not found in conn.php</div>";
                }
                
                if (strpos($connFileContent, 'function closeDBconn') !== false) {
                    echo "<div class='success'>closeDBconn function found in conn.php</div>";
                } else {
                    echo "<div class='error'>closeDBconn function not found in conn.php</div>";
                }
            } else {
                echo "<div class='error'>Cannot check conn.php content - file not accessible</div>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Direct Database Connection Test</h2>
        <div class="step">
            <?php
            // Test direct database connection
            if (file_exists($connFilePath) && is_readable($connFilePath)) {
                try {
                    // Include the conn.php file
                    require_once $connFilePath;
                    
                    // Check if functions exist
                    if (!function_exists('openDBconn')) {
                        throw new Exception("openDBconn function does not exist after including conn.php");
                    }
                    
                    if (!function_exists('closeDBconn')) {
                        throw new Exception("closeDBconn function does not exist after including conn.php");
                    }
                    
                    // Attempt to open a connection
                    echo "<p>Attempting to connect to database...</p>";
                    $startTime = microtime(true);
                    
                    $conn = null;
                    try {
                        $conn = openDBconn();
                        
                        if (!$conn) {
                            throw new Exception("openDBconn returned null or false");
                        }
                        
                        // Check if it's a valid MySQL connection
                        if (!($conn instanceof mysqli)) {
                            throw new Exception("Connection is not a valid mysqli object: " . gettype($conn));
                        }
                        
                        // Check for connection errors
                        if ($conn->connect_error) {
                            throw new Exception("Connection error: " . $conn->connect_error);
                        }
                        
                        $endTime = microtime(true);
                        $connectionTime = round(($endTime - $startTime) * 1000, 2);
                        
                        echo "<div class='success'>Successfully connected to the database in {$connectionTime}ms</div>";
                        
                        // Get server info
                        echo "<p>MySQL Server Info: " . $conn->server_info . "</p>";
                        echo "<p>MySQL Host Info: " . $conn->host_info . "</p>";
                        
                        // Test a simple query
                        echo "<p>Testing a simple query...</p>";
                        
                        $result = $conn->query("SELECT 1 AS test");
                        if ($result) {
                            $row = $result->fetch_assoc();
                            if (isset($row['test']) && $row['test'] == 1) {
                                echo "<div class='success'>Simple query successful</div>";
                            } else {
                                echo "<div class='error'>Simple query returned unexpected result</div>";
                            }
                            $result->free();
                        } else {
                            echo "<div class='error'>Simple query failed: " . $conn->error . "</div>";
                        }
                        
                        // Test a query on the Integration database
                        echo "<p>Testing a query on the Integration database...</p>";
                        
                        $testQuery = "SHOW TABLES FROM Integration LIMIT 10";
                        $result = $conn->query($testQuery);
                        
                        if ($result) {
                            $tables = [];
                            while ($row = $result->fetch_row()) {
                                $tables[] = $row[0];
                            }
                            
                            echo "<p>Found " . count($tables) . " tables in Integration database:</p>";
                            echo "<pre>" . implode(", ", $tables) . "</pre>";
                            $result->free();
                        } else {
                            echo "<div class='error'>Integration database query failed: " . $conn->error . "</div>";
                        }
                        
                        // Close connection
                        closeDBconn($conn);
                        echo "<div class='success'>Connection closed successfully</div>";
                        
                    } catch (Exception $e) {
                        if ($conn) {
                            closeDBconn($conn);
                        }
                        throw $e;
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>Database connection test failed: " . $e->getMessage() . "</div>";
                    
                    // Log the error
                    error_log("Database connection test failed: " . $e->getMessage());
                }
            } else {
                echo "<div class='error'>Cannot test database connection - conn.php file not accessible</div>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>API File Check</h2>
        <div class="step">
            <?php
            // Check if API directories and files exist
            $apiDir = __DIR__ . '/api';
            
            if (is_dir($apiDir)) {
                echo "<div class='success'>API directory exists at: {$apiDir}</div>";
                
                // Check for key API files
                $keyFiles = [
                    'index.php',
                    'config.php',
                    'utils/Request.php',
                    'utils/Response.php',
                    'controllers/SearchController.php',
                    'controllers/CarController.php',
                    'services/SearchService.php',
                    'services/CarService.php'
                ];
                
                $missingFiles = [];
                
                foreach ($keyFiles as $file) {
                    $filePath = $apiDir . '/' . $file;
                    if (!file_exists($filePath)) {
                        $missingFiles[] = $file;
                    }
                }
                
                if (empty($missingFiles)) {
                    echo "<div class='success'>All required API files exist</div>";
                } else {
                    echo "<div class='error'>Missing API files: " . implode(', ', $missingFiles) . "</div>";
                }
                
                // Check API config.php
                $configPath = $apiDir . '/config.php';
                if (file_exists($configPath) && is_readable($configPath)) {
                    echo "<div class='success'>API config.php exists and is readable</div>";
                    
                    // Check if config.php includes conn.php
                    $configContent = file_get_contents($configPath);
                    if (strpos($configContent, 'require_once') !== false && 
                        strpos($configContent, 'conn.php') !== false) {
                        echo "<div class='success'>API config.php includes conn.php</div>";
                    } else {
                        echo "<div class='error'>API config.php may not properly include conn.php</div>";
                    }
                } else {
                    echo "<div class='error'>API config.php does not exist or is not readable</div>";
                }
            } else {
                echo "<div class='error'>API directory does not exist at: {$apiDir}</div>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>API Test</h2>
        <div class="step">
            <?php
            // Test the API directly
            $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/search/initialize';
            
            echo "<p>Testing API endpoint: {$apiUrl}</p>";
            
            // Create a context for the request
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => '{}'
                ]
            ]);
            
            try {
                // Attempt the request with error suppressed
                $response = @file_get_contents($apiUrl, false, $context);
                
                if ($response === false) {
                    $error = error_get_last();
                    echo "<div class='error'>API request failed: " . ($error['message'] ?? 'Unknown error') . "</div>";
                } else {
                    echo "<div class='success'>API request completed</div>";
                    
                    // Try to decode the response
                    $data = json_decode($response, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        echo "<div class='error'>Failed to parse API response as JSON: " . json_last_error_msg() . "</div>";
                        echo "<p>Raw response:</p>";
                        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . (strlen($response) > 1000 ? '...' : '') . "</pre>";
                    } else {
                        echo "<p>API Response (JSON):</p>";
                        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
                    }
                }
            } catch (Exception $e) {
                echo "<div class='error'>Exception during API test: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>Recommendations</h2>
        <div class="step">
            <h3>Based on the tests above, here are some potential fixes:</h3>
            <ol>
                <li>If conn.php is missing, create it using the correct database credentials.</li>
                <li>If conn.php exists but the database connection fails, check:
                    <ul>
                        <li>Database credentials (username, password)</li>
                        <li>Database server hostname</li>
                        <li>Network connectivity to the database server</li>
                        <li>If the database server is running</li>
                    </ul>
                </li>
                <li>If API files are missing, check that you've deployed all necessary files to the server.</li>
                <li>Check file permissions - ensure PHP can read all necessary files.</li>
                <li>Check for PHP errors in the server error log.</li>
            </ol>
        </div>
    </div>
</body>
</html>