<?php
/**
 * api-tester.php - Simple client tool to test the API backend services
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if log850 function exists, if not define a simple version
if (!function_exists('log850')) {
    function log850($entry, $mode = 'a', $file = 'api-tester-log') {
        $upload_dir = dirname(__FILE__);
        $file_path = $upload_dir . '/' . $file . '.txt';
        
        // Prepare the log entry
        if (empty($entry)) {
            $entry = 'object is not set';
        } elseif (is_array($entry) || is_object($entry)) {
            $entry = json_encode($entry, JSON_PRETTY_PRINT);
        }
        
        // Format with timestamp
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] :: " . $entry . "\n";
        
        // Append to file
        file_put_contents($file_path, $logMessage, FILE_APPEND);
        
        return true;
    }
}

// Log start of script
log850("API Tester started - " . $_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Search API Tester</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .api-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #3498db;
        }
        .endpoint-description {
            margin-bottom: 15px;
            color: #555;
        }
        .input-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #2980b9;
        }
        .response-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .response-header {
            background-color: #f1f1f1;
            padding: 10px;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
        }
        .status-success {
            background-color: #27ae60;
            color: white;
        }
        .status-error {
            background-color: #e74c3c;
            color: white;
        }
        .response-body {
            padding: 15px;
            background-color: #f8f9fa;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 14px;
        }
        .tabbed-container {
            display: flex;
            flex-direction: column;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            background-color: #f1f1f1;
            margin-bottom: 5px;
        }
        .tab.active {
            background-color: white;
            border-color: #ddd;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .parameters {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-family: monospace;
        }
        .loading {
            display: none;
            margin-left: 10px;
            font-style: italic;
            color: #777;
        }
        .request-details {
            margin-top: 10px;
            padding: 10px;
            background-color: #f0f7ff;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        .error-message {
            color: #e74c3c;
            margin-top: 10px;
            padding: 10px;
            background-color: #fdf1f0;
            border-left: 4px solid #e74c3c;
            margin-bottom: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <h1>Car Search API Tester</h1>
    
    <div class="api-section">
        <h2>API Configuration</h2>
        <p>API Base URL: <code id="apiBaseDisplay">api</code></p>
        <div class="input-group">
            <label for="apiBaseInput">Change API Base URL (advanced):</label>
            <input type="text" id="apiBaseInput" placeholder="api" value="api">
            <p style="font-size: 0.8em; color: #555;">Note: For direct testing using index.php, use: <code>api/index.php?path=</code></p>
        </div>
        <button id="updateApiBaseBtn">Update API Base</button>
    </div>

    <div class="tabbed-container">
        <div class="tabs">
            <div class="tab active" data-target="initialize">Initialize Search</div>
            <div class="tab" data-target="status">Check Status</div>
            <div class="tab" data-target="execute">Execute Search</div>
            <div class="tab" data-target="getCars">Get Cars</div>
            <div class="tab" data-target="incremental">Incremental Cars</div>
            <div class="tab" data-target="configure">Configure Search</div>
        </div>
        
        <!-- Initialize Search -->
        <div class="tab-content active" id="initialize">
            <div class="api-section">
                <h2>Initialize a New Search</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /search/initialize<br>
                    <strong>Method:</strong> POST<br>
                    <strong>Description:</strong> Start a new search and get a result ID
                </div>
                <div class="parameters">
                    No parameters required
                </div>
                <div class="request-details">
                    This will make a <strong>POST</strong> request to initialize a new search. 
                    Method type is important because this endpoint requires POST.
                </div>
                <button id="initializeBtn">Send Request</button>
                <span class="loading" id="initializeLoading">Loading...</span>
                <div class="error-message" id="initializeError"></div>
                <div class="response-container" id="initializeResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="initializeStatus"></span>
                    </div>
                    <div class="response-body" id="initializeResponseBody"></div>
                </div>
            </div>
        </div>
        
        <!-- Check Status -->
        <div class="tab-content" id="status">
            <div class="api-section">
                <h2>Check Search Status</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /search/status/{resultId}<br>
                    <strong>Method:</strong> GET<br>
                    <strong>Description:</strong> Check if a search is complete or still processing
                </div>
                <div class="input-group">
                    <label for="statusResultId">Result ID:</label>
                    <input type="number" id="statusResultId" placeholder="Enter result ID">
                </div>
                <button id="statusBtn">Send Request</button>
                <span class="loading" id="statusLoading">Loading...</span>
                <div class="error-message" id="statusError"></div>
                <div class="response-container" id="statusResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="statusStatus"></span>
                    </div>
                    <div class="response-body" id="statusResponseBody"></div>
                </div>
            </div>
        </div>
        
        <!-- Execute Search -->
        <div class="tab-content" id="execute">
            <div class="api-section">
                <h2>Execute Search</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /search/execute/{resultId}<br>
                    <strong>Method:</strong> POST<br>
                    <strong>Description:</strong> Execute a search with default configuration
                </div>
                <div class="input-group">
                    <label for="executeResultId">Result ID:</label>
                    <input type="number" id="executeResultId" placeholder="Enter result ID">
                </div>
                <div class="request-details">
                    This will make a <strong>POST</strong> request to execute the search.
                </div>
                <button id="executeBtn">Send Request</button>
                <span class="loading" id="executeLoading">Loading...</span>
                <div class="error-message" id="executeError"></div>
                <div class="response-container" id="executeResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="executeStatus"></span>
                    </div>
                    <div class="response-body" id="executeResponseBody"></div>
                </div>
            </div>
        </div>
        
        <!-- Get Cars -->
        <div class="tab-content" id="getCars">
            <div class="api-section">
                <h2>Get All Cars</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /cars/get/{resultId}<br>
                    <strong>Method:</strong> GET<br>
                    <strong>Description:</strong> Get all cars for a search result with optional pagination and sorting
                </div>
                <div class="input-group">
                    <label for="getCarsResultId">Result ID:</label>
                    <input type="number" id="getCarsResultId" placeholder="Enter result ID">
                </div>
                <div class="input-group">
                    <label for="getCarsLimit">Limit (optional):</label>
                    <input type="number" id="getCarsLimit" placeholder="Number of results">
                </div>
                <div class="input-group">
                    <label for="getCarsOffset">Offset (optional):</label>
                    <input type="number" id="getCarsOffset" placeholder="Offset for pagination" value="0">
                </div>
                <div class="input-group">
                    <label for="getCarsSortBy">Sort By (optional):</label>
                    <select id="getCarsSortBy">
                        <option value="PriceAfterDiscountShekelsWithVAT">Price (default)</option>
                        <option value="CompanyName">Company Name</option>
                        <option value="GROUPNAME">Car Type</option>
                        <option value="PassangersNum">Passengers</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="getCarsSortDir">Sort Direction (optional):</label>
                    <select id="getCarsSortDir">
                        <option value="ASC">Ascending (default)</option>
                        <option value="DESC">Descending</option>
                    </select>
                </div>
                <button id="getCarsBtn">Send Request</button>
                <span class="loading" id="getCarsLoading">Loading...</span>
                <div class="error-message" id="getCarsError"></div>
                <div class="response-container" id="getCarsResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="getCarsStatus"></span>
                    </div>
                    <div class="response-body" id="getCarsResponseBody"></div>
                </div>
            </div>
        </div>
        
        <!-- Incremental Cars -->
        <div class="tab-content" id="incremental">
            <div class="api-section">
                <h2>Get Incremental Car Data</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /cars/incremental/{resultId}<br>
                    <strong>Method:</strong> GET<br>
                    <strong>Description:</strong> Get only new cars since the last fetch
                </div>
                <div class="input-group">
                    <label for="incrementalResultId">Result ID:</label>
                    <input type="number" id="incrementalResultId" placeholder="Enter result ID">
                </div>
                <div class="input-group">
                    <label for="incrementalLastUID">Last UID:</label>
                    <input type="number" id="incrementalLastUID" placeholder="Enter last UID" value="0">
                </div>
                <button id="incrementalBtn">Send Request</button>
                <span class="loading" id="incrementalLoading">Loading...</span>
                <div class="error-message" id="incrementalError"></div>
                <div class="response-container" id="incrementalResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="incrementalStatus"></span>
                    </div>
                    <div class="response-body" id="incrementalResponseBody"></div>
                </div>
            </div>
        </div>
        
        <!-- Configure Search -->
        <div class="tab-content" id="configure">
            <div class="api-section">
                <h2>Configure Search</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /search/configure<br>
                    <strong>Method:</strong> POST<br>
                    <strong>Description:</strong> Configure and execute a search with custom parameters
                </div>
                <div class="input-group">
                    <label for="configureResultId">Result ID:</label>
                    <input type="number" id="configureResultId" placeholder="Enter result ID">
                </div>
                <div class="input-group">
                    <label for="configurePauseTime">Pause Time (seconds):</label>
                    <input type="number" id="configurePauseTime" placeholder="Default: 10" value="10">
                </div>
                <div class="input-group">
                    <label for="configureExitTime">Exit Time (seconds):</label>
                    <input type="number" id="configureExitTime" placeholder="Default: 60" value="60">
                </div>
                <div class="request-details">
                    This will make a <strong>POST</strong> request to configure the search.
                </div>
                <button id="configureBtn">Send Request</button>
                <span class="loading" id="configureLoading">Loading...</span>
                <div class="error-message" id="configureError"></div>
                <div class="response-container" id="configureResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="configureStatus"></span>
                    </div>
                    <div class="response-body" id="configureResponseBody"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab navigation
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and content
                document.querySelectorAll('.tab, .tab-content').forEach(el => {
                    el.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const target = this.getAttribute('data-target');
                document.getElementById(target).classList.add('active');
            });
        });
        
        // API base URL configuration
        let apiBaseUrl = 'api';
        
        // Update API base URL
        document.getElementById('updateApiBaseBtn').addEventListener('click', function() {
            apiBaseUrl = document.getElementById('apiBaseInput').value;
            document.getElementById('apiBaseDisplay').textContent = apiBaseUrl;
            console.log('API base URL updated to:', apiBaseUrl);
        });
        
        // Helper function to show error message
        function showError(sectionId, message) {
            const errorEl = document.getElementById(`${sectionId}Error`);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = 'block';
                
                // Hide error after 8 seconds
                setTimeout(() => {
                    errorEl.style.display = 'none';
                }, 8000);
            }
        }
        
        // Helper function to build URL based on current API base
        function buildApiUrl(endpoint) {
            // Handle special case for direct file access with path parameter
            if (apiBaseUrl.includes('index.php?path=')) {
                return apiBaseUrl + endpoint;
            }
            // Regular case - just append endpoint to base URL
            return apiBaseUrl + endpoint;
        }
        
        // Helper function to make API requests
        async function makeApiRequest(endpoint, method, params = null, queryParams = null) {
            // Build the full URL
            let url;
            
            // Check if we're using index.php with path parameter
            if (apiBaseUrl.includes('index.php?path=')) {
                // Remove leading slash if present for path parameter
                const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
                url = new URL(apiBaseUrl + cleanEndpoint, window.location.origin);
            } else {
                // Normal URL construction
                url = new URL(apiBaseUrl + endpoint, window.location.origin);
                
                // Add query parameters if provided
                if (queryParams) {
                    Object.keys(queryParams).forEach(key => {
                        if (queryParams[key] !== null && queryParams[key] !== '') {
                            url.searchParams.append(key, queryParams[key]);
                        }
                    });
                }
            }
            
            console.log(`Making ${method} request to:`, url.toString());
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };
            
            // Add body for POST requests
            if (method === 'POST' && params) {
                options.body = JSON.stringify(params);
            }
            
            try {
                const response = await fetch(url.toString(), options);
                let data;
                
                try {
                    const text = await response.text();
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        // If not valid JSON, return the text
                        return {
                            status: response.status,
                            data: { 
                                success: false, 
                                error: { 
                                    message: "Invalid JSON response",
                                    rawResponse: text
                                }
                            }
                        };
                    }
                } catch (e) {
                    return {
                        status: response.status,
                        data: { 
                            success: false, 
                            error: { 
                                message: "Failed to read response",
                                details: e.message
                            }
                        }
                    };
                }
                
                return {
                    status: response.status,
                    data: data
                };
            } catch (error) {
                console.error("Request error:", error);
                return {
                    status: 500,
                    data: { success: false, error: { message: error.message } }
                };
            }
        }
        
        // Helper function to display response
        function displayResponse(sectionId, response) {
            const responseContainer = document.getElementById(`${sectionId}Response`);
            const responseBody = document.getElementById(`${sectionId}ResponseBody`);
            const statusEl = document.getElementById(`${sectionId}Status`);
            
            responseContainer.style.display = 'block';
            responseBody.textContent = JSON.stringify(response.data, null, 2);
            
            if (response.status >= 200 && response.status < 300) {
                statusEl.textContent = `${response.status} OK`;
                statusEl.className = 'status status-success';
            } else {
                statusEl.textContent = `${response.status} Error`;
                statusEl.className = 'status status-error';
                
                // Show error message if there is one
                if (response.data && response.data.error && response.data.error.message) {
                    showError(sectionId, response.data.error.message);
                }
            }
        }
        
        // Initialize Search
        document.getElementById('initializeBtn').addEventListener('click', async function() {
            const loadingEl = document.getElementById('initializeLoading');
            loadingEl.style.display = 'inline';
            
            try {
                // Make a POST request to the initialize endpoint
                const response = await makeApiRequest('/search/initialize', 'POST');
                displayResponse('initialize', response);
                
                // Populate result ID fields in other tabs if successful
                if (response.data.success && response.data.data && response.data.data.resultId) {
                    const resultId = response.data.data.resultId;
                    document.getElementById('statusResultId').value = resultId;
                    document.getElementById('executeResultId').value = resultId;
                    document.getElementById('getCarsResultId').value = resultId;
                    document.getElementById('incrementalResultId').value = resultId;
                    document.getElementById('configureResultId').value = resultId;
                }
            } catch (error) {
                console.error('Error:', error);
                showError('initialize', `Request error: ${error.message}`);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Check Status
        document.getElementById('statusBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('statusResultId').value;
            if (!resultId) {
                showError('status', 'Please enter a Result ID');
                return;
            }
            
            const loadingEl = document.getElementById('statusLoading');
            loadingEl.style.display = 'inline';
            
            try {
                const response = await makeApiRequest(`/search/status/${resultId}`, 'GET');
                displayResponse('status', response);
            } catch (error) {
                console.error('Error:', error);
                showError('status', `Request error: ${error.message}`);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Execute Search
        document.getElementById('executeBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('executeResultId').value;
            if (!resultId) {
                showError('execute', 'Please enter a Result ID');
                return;
            }
            
            const loadingEl = document.getElementById('executeLoading');
            loadingEl.style.display = 'inline';
            
            try {
                // Using POST for execute
                const response = await makeApiRequest(`/search/execute/${resultId}`, 'POST');
                displayResponse('execute', response);
            } catch (error) {
                console.error('Error:', error);
                showError('execute', `Request error: ${error.message}`);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Get Cars
        document.getElementById('getCarsBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('getCarsResultId').value;
            if (!resultId) {
                showError('getCars', 'Please enter a Result ID');
                return;
            }
            
            const limit = document.getElementById('getCarsLimit').value;
            const offset = document.getElementById('getCarsOffset').value;
            const sortBy = document.getElementById('getCarsSortBy').value;
            const sortDir = document.getElementById('getCarsSortDir').value;
            
            const loadingEl = document.getElementById('getCarsLoading');
            loadingEl.style.display = 'inline';
            
            try {
                const response = await makeApiRequest(
                    `/cars/get/${resultId}`, 
                    'GET',
                    null,
                    { limit, offset, sortBy, sortDir }
                );
                displayResponse('getCars', response);
            } catch (error) {
                console.error('Error:', error);
                showError('getCars', `Request error: ${error.message}`);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Incremental Cars
        document.getElementById('incrementalBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('incrementalResultId').value;
            if (!resultId) {
                showError('incremental', 'Please enter a Result ID');
                return;
            }
            
            const lastUID = document.getElementById('incrementalLastUID').value;
            
            const loadingEl = document.getElementById('incrementalLoading');
            loadingEl.style.display = 'inline';
            
            try {
                const response = await makeApiRequest(
                    `/cars/incremental/${resultId}`, 
                    'GET',
                    null,
                    { lastUID }
                );
                displayResponse('incremental', response);
                
                // Update lastUID field if successful
                if (response.data.success && response.data.data && response.data.data.lastUID) {
                    document.getElementById('incrementalLastUID').value = response.data.data.lastUID;
                }
            } catch (error) {
                console.error('Error:', error);
                showError('incremental', `Request error: ${error.message}`);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Configure Search
        document.getElementById('configureBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('configureResultId').value;
            if (!resultId) {
                showError('configure', 'Please enter a Result ID');
                return;
            }
            
            const pauseTime = document.getElementById('configurePauseTime').value;
            const exitTime = document.getElementById('configureExitTime').value;
            
            const params = {
                resultId: parseInt(resultId),
                pauseTime: pauseTime ? parseInt(pauseTime) : undefined,
                exitTime: exitTime ? parseInt(exitTime) : undefined
            };
            
            const loadingEl = document.getElementById('configureLoading');
            loadingEl.style.display = 'inline';
            
            try {
                // Using POST for configure
                const response = await makeApiRequest('/search/configure', 'POST', params);
                displayResponse('configure', response);
            } catch (error) {
                console.error('Error:', error);
                showError('configure', `Request error: ${error.message}`);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Set the initial API base input field to possibly use the index.php path parameter approach
        document.getElementById('apiBaseInput').value = apiBaseUrl;
    </script>
</body>
</html>