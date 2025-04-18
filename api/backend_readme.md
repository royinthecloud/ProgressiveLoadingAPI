# Car Search API Documentation

This API provides a RESTful interface to the car search functionality. It allows different UI implementations to use the same backend services for searching and retrieving car data.

## Installation

1. Place the API files in the appropriate directory:
   ```
   /var/www/html/integration/8_55/v9b/api/
   ```

2. Ensure the following files are accessible:
   - `conn.php` (for database connection)
   - `CarLoading.php` (core functionality)

3. Verify that the API is working by accessing:
   ```
   https://search.auto-shay.com/integration/8_55/v9b/api/search/status/12345
   ```
   (Replace 12345 with a valid result ID)

## API Endpoints

All endpoints are relative to the base URL, for example:
`https://search.auto-shay.com/integration/8_55/v9b/api/`

### Search Endpoints

#### Initialize a New Search

- **URL**: `/search/initialize`
- **Method**: POST
- **Description**: Starts a new search and returns a result ID
- **Response Example**:
  ```json
  {
    "success": true,
    "data": {
      "resultId": 12345
    },
    "timestamp": "2025-04-17 12:34:56"
  }
  ```

#### Get Search Status

- **URL**: `/search/status/{resultId}`
- **Method**: GET
- **Description**: Checks the status of a search
- **URL Parameters**:
  - `resultId`: The search result ID
- **Response Example**:
  ```json
  {
    "success": true,
    "data": {
      "processingCount": 1,
      "completeCount": 0,
      "isComplete": false,
      "carCount": 42
    },
    "timestamp": "2025-04-17 12:34:56"
  }
  ```

#### Configure and Execute a Search

- **URL**: `/search/configure`
- **Method**: POST
- **Description**: Configures and executes a search with custom parameters
- **Request Body**:
  ```json
  {
    "resultId": 12345,
    "pauseTime": 5,
    "exitTime": 30
  }
  ```
- **Response Example**:
  ```json
  {
    "success": true,
    "data": {
      "resultId": 12345,
      "initialCarCount": 0,
      "startTime": 1713434096,
      "endTime": 1713434126,
      "elapsedTime": 30,
      "totalIterations": 6,
      "finalProcessingCount": 0,
      "finalCompleteCount": 1,
      "totalCarsCount": 42,
      "isComplete": true,
      "iterations": [...]
    },
    "timestamp": "2025-04-17 12:34:56"
  }
  ```

#### Execute a Search with Default Configuration

- **URL**: `/search/execute/{resultId}`
- **Method**: POST
- **Description**: Executes a search with default parameters
- **URL Parameters**:
  - `resultId`: The search result ID
- **Response Example**: Same as `/search/configure`

### Car Data Endpoints

#### Get All Cars

- **URL**: `/cars/get/{resultId}`
- **Method**: GET
- **Description**: Gets all cars for a search result with optional pagination and sorting
- **URL Parameters**:
  - `resultId`: The search result ID
- **Query Parameters**:
  - `limit`: Optional limit on number of results
  - `offset`: Optional offset for pagination (default: 0)
  - `sortBy`: Optional field to sort by (default: 'PriceAfterDiscountShekelsWithVAT')
  - `sortDir`: Optional sort direction ('ASC' or 'DESC', default: 'ASC')
- **Response Example**:
  ```json
  {
    "success": true,
    "data": {
      "cars": [...],
      "totalCount": 42
    },
    "timestamp": "2025-04-17 12:34:56"
  }
  ```

#### Get Incremental Car Data

- **URL**: `/cars/incremental/{resultId}`
- **Method**: GET
- **Description**: Gets only new cars since the last fetch
- **URL Parameters**:
  - `resultId`: The search result ID
- **Query Parameters**:
  - `lastUID`: The last UID retrieved by client
- **Response Example**:
  ```json
  {
    "success": true,
    "data": {
      "newCars": [...],
      "lastUID": 789,
      "processingCount": 0,
      "completeCount": 1,
      "isComplete": true,
      "totalCount": 42,
      "newCarsCount": 5
    },
    "timestamp": "2025-04-17 12:34:56"
  }
  ```

## Error Handling

All API endpoints return a standardized error format:

```json
{
  "success": false,
  "error": {
    "message": "Error message",
    "details": {} // Optional additional details
  },
  "timestamp": "2025-04-17 12:34:56"
}
```

Common HTTP status codes:
- 200: Success
- 400: Bad Request (invalid parameters)
- 404: Not Found (endpoint not found)
- 405: Method Not Allowed (wrong HTTP method)
- 500: Internal Server Error

## Usage Examples

### Starting a New Search

1. Initialize a new search:
   ```javascript
   fetch('https://search.auto-shay.com/integration/8_55/v9b/api/search/initialize', {
     method: 'POST'
   })
   .then(response => response.json())
   .then(data => {
     const resultId = data.data.resultId;
     // Start the search with the result ID
   });
   ```

2. Execute the search with default configuration:
   ```javascript
   fetch(`https://search.auto-shay.com/integration/8_55/v9b/api/search/execute/${resultId}`, {
     method: 'POST'
   });
   ```

3. Check search status periodically:
   ```javascript
   const checkStatus = setInterval(() => {
     fetch(`https://search.auto-shay.com/integration/8_55/v9b/api/search/status/${resultId}`)
     .then(response => response.json())
     .then(data => {
       if (data.data.isComplete) {
         clearInterval(checkStatus);
         // Search is complete, load all cars
       }
     });
   }, 3000);
   ```

4. Load cars incrementally:
   ```javascript
   let lastUID = 0;
   
   function loadIncrementalCars() {
     fetch(`https://search.auto-shay.com/integration/8_55/v9b/api/cars/incremental/${resultId}?lastUID=${lastUID}`)
     .then(response => response.json())
     .then(data => {
       // Process new cars
       const newCars = data.data.newCars;
       
       // Update lastUID
       lastUID = data.data.lastUID;
       
       // If search is not complete, schedule next fetch
       if (!data.data.isComplete) {
         setTimeout(loadIncrementalCars, 5000);
       }
     });
   }
   
   // Start loading cars
   loadIncrementalCars();
   ```

## Troubleshooting

If you encounter issues with the API:

1. Check that the API files are in the correct location
2. Verify that `conn.php` and `CarLoading.php` are accessible
3. Check the server error logs for more information
4. Ensure that the database connection parameters are correct