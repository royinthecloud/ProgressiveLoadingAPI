<?php
/**
 * api-tester.php - Simple client tool to test the API backend services
 */
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
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            background-color: #f1f1f1;
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
                <button id="initializeBtn">Send Request</button>
                <span class="loading" id="initializeLoading">Loading...</span>
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
                    <strong>Endpoint:</strong> /api/search/status/{resultId}<br>
                    <strong>Method:</strong> GET<br>
                    <strong>Description:</strong> Check if a search is complete or still processing
                </div>
                <div class="input-group">
                    <label for="statusResultId">Result ID:</label>
                    <input type="number" id="statusResultId" placeholder="Enter result ID">
                </div>
                <button id="statusBtn">Send Request</button>
                <span class="loading" id="statusLoading">Loading...</span>
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
                    <strong>Endpoint:</strong> /api/search/execute/{resultId}<br>
                    <strong>Method:</strong> POST<br>
                    <strong>Description:</strong> Execute a search with default configuration
                </div>
                <div class="input-group">
                    <label for="executeResultId">Result ID:</label>
                    <input type="number" id="executeResultId" placeholder="Enter result ID">
                </div>
                <button id="executeBtn">Send Request</button>
                <span class="loading" id="executeLoading">Loading...</span>
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
                <button id="getCarsBtn">Send Request</button>
                <span class="loading" id="getCarsLoading">Loading...</span>
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
                <button id="incrementalBtn">Send Request</button>
                <span class="loading" id="incrementalLoading">Loading...</span>
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
                <button id="configureBtn">Send Request</button>
                <span class="loading" id="configureLoading">Loading...</span>
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
        
        // API base URL
        const apiBaseUrl = 'api';
        
        // Helper function to make API requests
        async function makeApiRequest(endpoint, method, params = null, queryParams = null) {
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
            
            try {
                const response = await fetch(url.toString(), options);
                const data = await response.json();
                return {
                    status: response.status,
                    data: data
                };
            } catch (error) {
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
            }
        }
        
        // Initialize Search
        document.getElementById('initializeBtn').addEventListener('click', async function() {
            const loadingEl = document.getElementById('initializeLoading');
            loadingEl.style.display = 'inline';
            
            try {
                const response = await makeApiRequest(`${apiBaseUrl}/search/initialize`, 'POST');
                displayResponse('initialize', response);
                
                // Populate result ID fields in other tabs if successful
                if (response.data.success && response.data.data.resultId) {
                    const resultId = response.data.data.resultId;
                    document.getElementById('statusResultId').value = resultId;
                    document.getElementById('executeResultId').value = resultId;
                    document.getElementById('getCarsResultId').value = resultId;
                    document.getElementById('incrementalResultId').value = resultId;
                    document.getElementById('configureResultId').value = resultId;
                }
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Check Status
        document.getElementById('statusBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('statusResultId').value;
            if (!resultId) {
                alert('Please enter a Result ID');
                return;
            }
            
            const loadingEl = document.getElementById('statusLoading');
            loadingEl.style.display = 'inline';
            
            try {
                const response = await makeApiRequest(`${apiBaseUrl}/search/status/${resultId}`, 'GET');
                displayResponse('status', response);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Execute Search
        document.getElementById('executeBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('executeResultId').value;
            if (!resultId) {
                alert('Please enter a Result ID');
                return;
            }
            
            const loadingEl = document.getElementById('executeLoading');
            loadingEl.style.display = 'inline';
            
            try {
                const response = await makeApiRequest(`${apiBaseUrl}/search/execute/${resultId}`, 'POST');
                displayResponse('execute', response);
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Get Cars
        document.getElementById('getCarsBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('getCarsResultId').value;
            if (!resultId) {
                alert('Please enter a Result ID');
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
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Incremental Cars
        document.getElementById('incrementalBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('incrementalResultId').value;
            if (!resultId) {
                alert('Please enter a Result ID');
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
                if (response.data.success && response.data.data.lastUID) {
                    document.getElementById('incrementalLastUID').value = response.data.data.lastUID;
                }
            } finally {
                loadingEl.style.display = 'none';
            }
        });
        
        // Configure Search
        document.getElementById('configureBtn').addEventListener('click', async function() {
            const resultId = document.getElementById('configureResultId').value;
            if (!resultId) {
                alert('Please enter a Result ID');
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
            } finally {
                loadingEl.style.display = 'none';
            }
        });
    </script>
</body>
</html>