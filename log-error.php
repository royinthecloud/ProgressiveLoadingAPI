<?php
/**
 * api-tester.php - Simple client tool to test the API backend services
 * With enhanced error handling and logging
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Error log function
function logError($message, $data = null) {
    $logFile = __DIR__ . '/api-tester-errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $logMessage .= " - Data: " . json_encode($data);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Log the start of the script
try {
    logError("API Tester started - " . $_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    // Silent fail for logging itself
}

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
            margin-right: 10px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .button-group {
            display: flex;
            margin-bottom: 10px;
        }
        .preview-button {
            background-color: #7f8c8d;
        }
        .preview-button:hover {
            background-color: #6c7a7c;
        }
        .response-container, .request-preview-container {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .response-header, .request-header {
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
        .status-info {
            background-color: #3498db;
            color: white;
        }
        .response-body, .request-body {
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
        .request-preview-container {
            display: none;
        }
        .hidden {
            display: none;
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
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f7f9fa;
            border: 1px dashed #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            display: none;
        }
        .show-debug {
            background-color: #95a5a6;
            font-size: 12px;
            margin-top: 10px;
        }
        .show-debug:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <h1>Car Search API Tester</h1>
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
                    <strong>Endpoint:</strong> /api/search/initialize<br>
                    <strong>Method:</strong> POST<br>
                    <strong>Description:</strong> Start a new search and get a result ID
                </div>
                <div class="parameters">
                    No parameters required
                </div>
                <div class="button-group">
                    <button id="initializePreviewBtn">Preview Request</button>
                    <button id="initializeBtn">Send Request</button>
                </div>
                <div class="error-message" id="initializeError"></div>
                <span class="loading" id="initializeLoading">Loading...</span>
                <div class="request-preview-container" id="initializeRequestPreview">
                    <div class="request-header">
                        <span>Request Preview</span>
                        <span class="status status-info">POST</span>
                    </div>
                    <div class="request-body" id="initializeRequestBody"></div>
                </div>
                <div class="response-container" id="initializeResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="initializeStatus"></span>
                    </div>
                    <div class="response-body" id="initializeResponseBody"></div>
                </div>
                <button class="show-debug" id="initializeDebugBtn">Show Debug Info</button>
                <div class="debug-info" id="initializeDebugInfo"></div>
            </div>
        </div>
        
        <!-- Check Status -->
        <div class="tab-content" id="status">
            <div class="api-section">
                <h2>Check Search Status</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /api/search/status/{resultId}<br>
                    <strong>Method:</strong> GET<br>
                    <strong>Description:</strong> Check if a search is complete or still processing
                </div>
                <div class="input-group">
                    <label for="statusResultId">Result ID:</label>
                    <input type="number" id="statusResultId" placeholder="Enter result ID">
                </div>
                <div class="button-group">
                    <button id="statusPreviewBtn">Preview Request</button>
                    <button id="statusBtn">Send Request</button>
                </div>
                <div class="error-message" id="statusError"></div>
                <span class="loading" id="statusLoading">Loading...</span>
                <div class="request-preview-container" id="statusRequestPreview">
                    <div class="request-header">
                        <span>Request Preview</span>
                        <span class="status status-info">GET</span>
                    </div>
                    <div class="request-body" id="statusRequestBody"></div>
                </div>
                <div class="response-container" id="statusResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="statusStatus"></span>
                    </div>
                    <div class="response-body" id="statusResponseBody"></div>
                </div>
                <button class="show-debug" id="statusDebugBtn">Show Debug Info</button>
                <div class="debug-info" id="statusDebugInfo"></div>
            </div>
        </div>
        
        <!-- Execute Search -->
        <div class="tab-content" id="execute">
            <div class="api-section">
                <h2>Execute Search</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /api/search/execute/{resultId}<br>
                    <strong>Method:</strong> POST<br>
                    <strong>Description:</strong> Execute a search with default configuration
                </div>
                <div class="input-group">
                    <label for="executeResultId">Result ID:</label>
                    <input type="number" id="executeResultId" placeholder="Enter result ID">
                </div>
                <div class="button-group">
                    <button id="executePreviewBtn">Preview Request</button>
                    <button id="executeBtn">Send Request</button>
                </div>
                <div class="error-message" id="executeError"></div>
                <span class="loading" id="executeLoading">Loading...</span>
                <div class="request-preview-container" id="executeRequestPreview">
                    <div class="request-header">
                        <span>Request Preview</span>
                        <span class="status status-info">POST</span>
                    </div>
                    <div class="request-body" id="executeRequestBody"></div>
                </div>
                <div class="response-container" id="executeResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="executeStatus"></span>
                    </div>
                    <div class="response-body" id="executeResponseBody"></div>
                </div>
                <button class="show-debug" id="executeDebugBtn">Show Debug Info</button>
                <div class="debug-info" id="executeDebugInfo"></div>
            </div>
        </div>
        
        <!-- Get Cars -->
        <div class="tab-content" id="getCars">
            <div class="api-section">
                <h2>Get All Cars</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /api/cars/get/{resultId}<br>
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
                <div class="button-group">
                    <button id="getCarsPreviewBtn">Preview Request</button>
                    <button id="getCarsBtn">Send Request</button>
                </div>
                <div class="error-message" id="getCarsError"></div>
                <span class="loading" id="getCarsLoading">Loading...</span>
                <div class="request-preview-container" id="getCarsRequestPreview">
                    <div class="request-header">
                        <span>Request Preview</span>
                        <span class="status status-info">GET</span>
                    </div>
                    <div class="request-body" id="getCarsRequestBody"></div>
                </div>
                <div class="response-container" id="getCarsResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="getCarsStatus"></span>
                    </div>
                    <div class="response-body" id="getCarsResponseBody"></div>
                </div>
                <button class="show-debug" id="getCarsDebugBtn">Show Debug Info</button>
                <div class="debug-info" id="getCarsDebugInfo"></div>
            </div>
        </div>
        
        <!-- Incremental Cars -->
        <div class="tab-content" id="incremental">
            <div class="api-section">
                <h2>Get Incremental Car Data</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /api/cars/incremental/{resultId}<br>
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
                <div class="button-group">
                    <button id="incrementalPreviewBtn">Preview Request</button>
                    <button id="incrementalBtn">Send Request</button>
                </div>
                <div class="error-message" id="incrementalError"></div>
                <span class="loading" id="incrementalLoading">Loading...</span>
                <div class="request-preview-container" id="incrementalRequestPreview">
                    <div class="request-header">
                        <span>Request Preview</span>
                        <span class="status status-info">GET</span>
                    </div>
                    <div class="request-body" id="incrementalRequestBody"></div>
                </div>
                <div class="response-container" id="incrementalResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="incrementalStatus"></span>
                    </div>
                    <div class="response-body" id="incrementalResponseBody"></div>
                </div>
                <button class="show-debug" id="incrementalDebugBtn">Show Debug Info</button>
                <div class="debug-info" id="incrementalDebugInfo"></div>
            </div>
        </div>
        
        <!-- Configure Search -->
        <div class="tab-content" id="configure">
            <div class="api-section">
                <h2>Configure Search</h2>
                <div class="endpoint-description">
                    <strong>Endpoint:</strong> /api/search/configure<br>
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
                <div class="button-group">
                    <button id="configurePreviewBtn">Preview Request</button>
                    <button id="configureBtn">Send Request</button>
                </div>
                <div class="error-message" id="configureError"></div>
                <span class="loading" id="configureLoading">Loading...</span>
                <div class="request-preview-container" id="configureRequestPreview">
                    <div class="request-header">
                        <span>Request Preview</span>
                        <span class="status status-info">POST</span>
                    </div>
                    <div class="request-body" id="configureRequestBody"></div>
                </div>
                <div class="response-container" id="configureResponse" style="display: none;">
                    <div class="response-header">
                        <span>Response</span>
                        <span class="status" id="configureStatus"></span>
                    </div>
                    <div class="response-body" id="configureResponseBody"></div>
                </div>
                <button class="show-debug" id="configureDebugBtn">Show Debug Info</button>
                <div class="debug-info" id="configureDebugInfo"></div>
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
        
        // API base URL
        const apiBaseUrl = 'api';
        
        // Server and browser information for debug
        const debugInfo = {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            timestamp: new Date().toISOString()
        };
        
        // Helper function to log client-side errors to server
        async function logClientError(section, error, additionalInfo = {}) {
            try {
                const errorData = {
                    section: section,
                    message: error instanceof Error ? error.message : String(error),
                    stack: error instanceof Error ? error.stack : null,
                    additionalInfo: additionalInfo,
                    debugInfo: debugInfo
                };
                
                // Log to console
                console.error(`Error in ${section}:`, error);
                
                // Log to server
                await fetch('log-error.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(errorData)
                });
            } catch (e) {
                // Silently fail if error logging fails
                console.error('Failed to log error:', e);
            }
        }
        
        // Helper function to build request preview
        function buildRequestPreview(endpoint, method, params = null, queryParams = null) {
            try {
                let url = new URL(endpoint, window.location.origin);
                
                // Add query parameters if provided
                if (queryParams) {
                    Object.keys(queryParams).forEach(key => {
                        if (queryParams[key] !== null && queryParams[key] !== '') {
                            url.searchParams.append(key, queryParams[key]);
                        }
                    });
                }
                
                const preview = {
                    method: method,
                    url: url.toString(),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                };
                
                // Add body for POST requests
                if (method === 'POST' && params) {
                    preview.body = params;
                }
                
                return preview;
            } catch (error) {
                logClientError('buildRequestPreview', error, { endpoint, method, params, queryParams });
                throw error;
            }
        }
        
        // Helper function to display request preview
        function displayRequestPreview(sectionId, preview) {
            try {
                const previewContainer = document.getElementById(`${sectionId}RequestPreview`);
                const previewBody = document.getElementById(`${sectionId}RequestBody`);
                
                if (!previewContainer || !previewBody) {
                    throw new Error(`Preview elements not found for section: ${sectionId}`);
                }
                
                previewContainer.style.display = 'block';
                previewBody.textContent = JSON.stringify(preview, null, 2);
                
                // Update debug info
                const debugInfoEl = document.getElementById(`${sectionId}DebugInfo`);
                if (debugInfoEl) {
                    const requestDebugInfo = {
                        ...debugInfo,
                        requestPreview: preview,
                        timestamp: new Date().toISOString()
                    };
                    debugInfoEl.textContent = JSON.stringify(requestDebugInfo, null, 2);
                }
            } catch (error) {
                logClientError('displayRequestPreview', error, { sectionId, preview });
                showError(sectionId, `Error displaying request preview: ${error.message}`);
            }
        }
        
        // Helper function to make API requests
        async function makeApiRequest(endpoint, method, params = null, queryParams = null) {
            try {
                const url = new URL(endpoint, window.location.origin);
                
                // Add query parameters if provided
                if (queryParams) {
                    Object.keys(queryParams).forEach(key => {
                        if (queryParams[key] !== null && queryParams[key] !== '') {
                            url.searchParams.append(key, queryParams[key]);
                        }
                    });
                }
                
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
                
                // Capture request start time for performance monitoring
                const startTime = performance.now();
                
                // Make the request
                const response = await fetch(url.toString(), options);
                
                // Calculate request duration
                const duration = performance.now() - startTime;
                
                // Try to parse as JSON first
                let data;
                let responseText;
                
                try {
                    responseText = await response.text();
                    data = JSON.parse(responseText);
                } catch (e) {
                    // If not JSON, return as text with error details
                    data = { 
                        success: false, 
                        error: { 
                            message: "Failed to parse response as JSON", 
                            originalError: e.message,
                            responseText: responseText 
                        } 
                    };
                    
                    // Log the parsing error
                    logClientError('parseResponse', e, { 
                        responseText, 
                        status: response.status,
                        endpoint: url.toString() 
                    });
                }
                
                return {
                    status: response.status,
                    data: data,
                    duration: duration,
                    headers: Object.fromEntries(response.headers.entries()),
                    url: response.url,
                    responseText: responseText
                };
            } catch (error) {
                // Log network or other errors
                logClientError('makeApiRequest', error, { endpoint, method, params, queryParams });
                
                return {
                    status: 0,
                    data: { 
                        success: false, 
                        error: { 
                            message: error.message, 
                            type: error.name
                        } 
                    },
                    duration: 0,
                    networkError: true
                };
            }
        }
        
        // Helper function to show errors
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
        
        // Helper function to display response
        function displayResponse(sectionId, response) {
            try {
                const responseContainer = document.getElementById(`${sectionId}Response`);
                const responseBody = document.getElementById(`${sectionId}ResponseBody`);
                const statusEl = document.getElementById(`${sectionId}Status`);
                
                if (!responseContainer || !responseBody || !statusEl) {
                    throw new Error(`Response elements not found for section: ${sectionId}`);
                }
                
                responseContainer.style.display = 'block';
                
                // Format the response for display
                const displayData = {
                    ...response.data,
                    _meta: {
                        duration: `${Math.round(response.duration)}ms`,
                        status: response.status,
                        timestamp: new Date().toISOString()
                    }
                };
                
                responseBody.textContent = JSON.stringify(displayData, null, 2);
                
                if (response.status >= 200 && response.status < 300) {
                    statusEl.textContent = `${response.status} OK`;
                    statusEl.className = 'status status-success';
                } else {
                    statusEl.textContent = `${response.status} Error`;
                    statusEl.className = 'status status-error';
                    
                    // Show error message if there is one
                    if (response.data && response.data.error && response.data.error.message) {
                        showError(sectionId, response.data.error.message);
                    } else if (response.networkError) {
                        showError(sectionId, 'Network error: Unable to connect to the API');
                    } else {
                        showError(sectionId, `Error with status code: ${response.status}`);
                    }
                }
                
                // Update debug info
                const debugInfoEl = document.getElementById(`${sectionId}DebugInfo`);
                if (debugInfoEl) {
                    const responseDebugInfo = {
                        ...debugInfo,
                        response: {
                            status: response.status,
                            headers: response.headers,
                            url: response.url,
                            duration: response.duration,
                            timestamp: new Date().toISOString()
                        },
                        responseText: response.responseText
                    };
                    debugInfoEl.textContent = JSON.stringify(responseDebugInfo, null, 2);
                }
            } catch (error) {
                logClientError('displayResponse', error, { sectionId, response });
                showError(sectionId, `Error displaying response: ${error.message}`);
            }
        }
        
        // Setup debug buttons
        document.querySelectorAll('.show-debug').forEach(button => {
            button.addEventListener('click', function() {
                const sectionId = this.id.replace('DebugBtn', '');
                const debugInfoEl = document.getElementById(`${sectionId}DebugInfo`);
                
                if (debugInfoEl) {
                    const isVisible = debugInfoEl.style.display === 'block';
                    debugInfoEl.style.display = isVisible ? 'none' : 'block';
                    this.textContent = isVisible ? 'Show Debug Info' : 'Hide Debug Info';
                }
            });
        });
        
        // Initialize Search Preview
        document.getElementById('initializePreviewBtn').addEventListener('click', function() {
            try {
                const preview = buildRequestPreview(`${apiBaseUrl}/search/initialize`, 'POST');
                displayRequestPreview('initialize', preview);
            } catch (error) {
                showError('initialize', `Error generating preview: ${error.message}`);
                logClientError('initializePreview', error);
            }
        });
        
        // Initialize Search
        document.getElementById('initializeBtn').addEventListener('click', async function() {
            const loadingEl = document.getElementById('initializeLoading');
            loadingEl.style.display = 'inline';
            
            try {
                // Log that we're making the request
                console.log('Sending initialize search request');
                
                const response = await makeApiRequest(`${apiBaseUrl}/search/initialize`, 'POST');
                displayResponse('initialize', response);
                
                // Populate result ID fields in other tabs if successful
                if (response.status >= 200 && response.status < 300 && 
                    response.data && response.data.success && 
                    response.data.data && response.data.data.resultId) {
                    
                    const resultId = response.data.data.resultId;
                    document.getElementById('statusResultId').value = resultId;
                    document.getElementById('executeResultId').value = resultId;
                    document.getElementById('getCarsResultId').value = resultId;
                    document.getElementById('incrementalResultId').value = resultId;
                    document.getElementById('configureResultId').value = resultId;
                }
            } catch (error) {
                showError('initialize', `Error: ${error.message}`);
                logClientError('initialize', error, { endpoint: `${apiBaseUrl}/search/initialize` });
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Check Status Preview
        document.getElementById('statusPreviewBtn').addEventListener('click', function() {
            try {
                const resultId = document.getElementById('statusResultId').value;
                if (!resultId) {
                    showError('status', 'Please enter a Result ID');
                    return;
                }
                
                const preview = buildRequestPreview(`${apiBaseUrl}/search/status/${resultId}`, 'GET');
                displayRequestPreview('status', preview);
            } catch (error) {
                showError('status', `Error generating preview: ${error.message}`);
                logClientError('statusPreview', error);
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
                const response = await makeApiRequest(`${apiBaseUrl}/search/status/${resultId}`, 'GET');
                displayResponse('status', response);
            } catch (error) {
                showError('status', `Error: ${error.message}`);
                logClientError('status', error, { resultId, endpoint: `${apiBaseUrl}/search/status/${resultId}` });
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Execute Search Preview
        document.getElementById('executePreviewBtn').addEventListener('click', function() {
            try {
                const resultId = document.getElementById('executeResultId').value;
                if (!resultId) {
                    showError('execute', 'Please enter a Result ID');
                    return;
                }
                
                const preview = buildRequestPreview(`${apiBaseUrl}/search/execute/${resultId}`, 'POST');
                displayRequestPreview('execute', preview);
            } catch (error) {
                showError('execute', `Error generating preview: ${error.message}`);
                logClientError('executePreview', error);
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
                const response = await makeApiRequest(`${apiBaseUrl}/search/execute/${resultId}`, 'POST');
                displayResponse('execute', response);
            } catch (error) {
                showError('execute', `Error: ${error.message}`);
                logClientError('execute', error, { resultId, endpoint: `${apiBaseUrl}/search/execute/${resultId}` });
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Get Cars Preview
        document.getElementById('getCarsPreviewBtn').addEventListener('click', function() {
            try {
                const resultId = document.getElementById('getCarsResultId').value;
                if (!resultId) {
                    showError('getCars', 'Please enter a Result ID');
                    return;
                }
                
                const limit = document.getElementById('getCarsLimit').value;
                const offset = document.getElementById('getCarsOffset').value;
                const sortBy = document.getElementById('getCarsSortBy').value;
                const sortDir = document.getElementById('getCarsSortDir').value;
                
                const preview = buildRequestPreview(
                    `${apiBaseUrl}/cars/get/${resultId}`, 
                    'GET',
                    null,
                    { limit, offset, sortBy, sortDir }
                );
                displayRequestPreview('getCars', preview);
            } catch (error) {
                showError('getCars', `Error generating preview: ${error.message}`);
                logClientError('getCarsPreview', error);
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
                    `${apiBaseUrl}/cars/get/${resultId}`, 
                    'GET',
                    null,
                    { limit, offset, sortBy, sortDir }
                );
                displayResponse('getCars', response);
            } catch (error) {
                showError('getCars', `Error: ${error.message}`);
                logClientError('getCars', error, { 
                    resultId, limit, offset, sortBy, sortDir,
                    endpoint: `${apiBaseUrl}/cars/get/${resultId}`
                });
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Incremental Cars Preview
        document.getElementById('incrementalPreviewBtn').addEventListener('click', function() {
            try {
                const resultId = document.getElementById('incrementalResultId').value;
                if (!resultId) {
                    showError('incremental', 'Please enter a Result ID');
                    return;
                }
                
                const lastUID = document.getElementById('incrementalLastUID').value;
                
                const preview = buildRequestPreview(
                    `${apiBaseUrl}/cars/incremental/${resultId}`, 
                    'GET',
                    null,
                    { lastUID }
                );
                displayRequestPreview('incremental', preview);
            } catch (error) {
                showError('incremental', `Error generating preview: ${error.message}`);
                logClientError('incrementalPreview', error);
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
                    `${apiBaseUrl}/cars/incremental/${resultId}`, 
                    'GET',
                    null,
                    { lastUID }
                );
                displayResponse('incremental', response);
                
                // Update lastUID field if successful
                if (response.status >= 200 && response.status < 300 && 
                    response.data && response.data.success && 
                    response.data.data && response.data.data.lastUID) {
                    
                    document.getElementById('incrementalLastUID').value = response.data.data.lastUID;
                }
            } catch (error) {
                showError('incremental', `Error: ${error.message}`);
                logClientError('incremental', error, { 
                    resultId, lastUID,
                    endpoint: `${apiBaseUrl}/cars/incremental/${resultId}`
                });
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Configure Search Preview
        document.getElementById('configurePreviewBtn').addEventListener('click', function() {
            try {
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
                
                const preview = buildRequestPreview(
                    `${apiBaseUrl}/search/configure`, 
                    'POST',
                    params
                );
                displayRequestPreview('configure', preview);
            } catch (error) {
                showError('configure', `Error generating preview: ${error.message}`);
                logClientError('configurePreview', error);
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
                const response = await makeApiRequest(`${apiBaseUrl}/search/configure`, 'POST', params);
                displayResponse('configure', response);
            } catch (error) {
                showError('configure', `Error: ${error.message}`);
                logClientError('configure', error, { 
                    params,
                    endpoint: `${apiBaseUrl}/search/configure`
                });
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Global error handler for unhandled Promise rejections
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled Promise Rejection:', event.reason);
            logClientError('unhandledRejection', event.reason);
            alert('An unexpected error occurred. Please check the browser console for details.');
        });
        
        // Initialize debug information
        document.querySelectorAll('.debug-info').forEach(el => {
            el.textContent = JSON.stringify(debugInfo, null, 2);
        });