/**
 * tester6.js - Client-side JavaScript for the car search results page
 * Updated to show loading indicators immediately
 */

// DOM Elements
const carResults = document.getElementById('carResults');
const noResults = document.getElementById('noResults');
const carCount = document.getElementById('carCount');
const searchStatus = document.getElementById('searchStatus');
const loadingIndicator = document.getElementById('loadingIndicator');

// State variables
let isSearchComplete = false;
let pollInterval = null;
let statusCheckInterval = null;
let previousTotalCars = 0;
let currentCarsInView = 0;

// Initialize the page
document.addEventListener('DOMContentLoaded', initPage);

/**
 * Initialize the page
 */
function initPage() {
    if (resultId === 0) {
        showError('No result ID provided');
        return;
    }
    
    // For new searches, show the initial loading indicator immediately
    if (isNewSearch && window.loadingIndicators) {
        // Show the loading indicator right away
        window.loadingIndicators.showInitialLoading(true);
        
        // Set initial loading message
        window.loadingIndicators.updateInitialLoading({
            iteration: 1,
            timestamp: new Date().toISOString(),
            processingCount: 1,
            completeCount: 0,
            totalCarsCount: 0,
            newCarsCount: 0
        });
        
        // Start the search via AJAX
        startSearch();
    } else {
        // For existing resultIds, just load the cars
        loadAllCars();
    }
}

/**
 * Start the backend search process via AJAX
 */
async function startSearch() {
    // First, load any initial cars
    await loadAllCars();
    
    // Set up polling for status and cars
    statusCheckInterval = setInterval(checkSearchStatus, 3000);
    
    // Refresh cars every 'pauseTime' seconds
    pollInterval = setInterval(() => {
        loadAllCars(true); // true = is refresh
    }, pauseTime * 1000);
    
    // Set a timeout to stop polling based on exitTime
    setTimeout(() => {
        stopPolling();
        // Do one final refresh
        loadAllCars();
        isSearchComplete = true;
        updateSearchStatus(false, isSearchComplete);
    }, exitTime * 1000);
}

/**
 * Load all cars from the server
 * @param {boolean} isRefresh - Whether this is a refresh of existing data
 */
async function loadAllCars(isRefresh = false) {
    updateLoadingState(true);
    
    try {
        const response = await fetch(`tester6.php?action=getCars&resultid=${resultId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.cars.length > 0) {
            // Calculate new cars for toast notification
            const newCarsCount = data.cars.length - previousTotalCars;
            
            // Only show toast if this is a refresh and we have new cars
            if (isRefresh && newCarsCount > 0 && window.loadingIndicators && !window.loadingIndicators.isInitialLoading) {
                window.loadingIndicators.showNewCarsToast(newCarsCount);
            }
            
            // Clear existing cars if this is a refresh and we have new data
            if (data.cars.length !== previousTotalCars) {
                // Only clear and re-render if we have new cars
                carResults.innerHTML = '';
                renderCars(data.cars);
                
                // Set the current car count
                currentCarsInView = data.cars.length;
                previousTotalCars = data.cars.length;
            }
            
            // Update car count
            updateCarCount(data.totalCount);
            
            // Hide no results message
            noResults.style.display = 'none';
            
            // If this is the first time we're showing cars, hide the initial loading indicator
            if (window.loadingIndicators && window.loadingIndicators.isInitialLoading && currentCarsInView > 0) {
                setTimeout(() => {
                    window.loadingIndicators.showInitialLoading(false);
                }, 1000);
            }
        } else {
            // No cars found
            noResults.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading cars:', error);
        showError('Failed to load cars. Please try again.');
    } finally {
        updateLoadingState(false);
    }
}

/**
 * Check the search status
 */
async function checkSearchStatus() {
    try {
        const response = await fetch(`tester6.php?action=getStatus&resultid=${resultId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const status = await response.json();
        
        // Update the loading indicators with status info
        if (window.loadingIndicators) {
            window.loadingIndicators.processLoadingUpdate({
                processingCount: status.processingCount,
                completeCount: status.completeCount,
                totalCarsCount: previousTotalCars,
                iteration: window.loadingIndicators.currentIteration + 1,
                timestamp: new Date().toISOString(),
                newCarsCount: 0 // Will be updated in loadAllCars when we know
            });
        }
        
        // Update the UI based on status
        isSearchComplete = status.isComplete;
        updateSearchStatus(status.processingCount > 0, status.isComplete);
        
        // If search is complete, stop polling
        if (status.isComplete) {
            stopPolling();
            // Do one final refresh to get all cars
            loadAllCars();
        }
    } catch (error) {
        console.error('Error checking status:', error);
    }
}

/**
 * Render car cards from the data
 */
function renderCars(cars) {
    cars.forEach(car => {
        const carCard = carCardTemplate.content.cloneNode(true);
        
        // Car title - use GROUP NAME and CAR TYPE ID
        carCard.querySelector('.car-title').textContent = car.GROUPNAME || 'Unknown Car Type';
        
        // Company name
        carCard.querySelector('.company-name').textContent = car.CompanyName || 'Unknown Company';
        
        // Car image
        const carImage = carCard.querySelector('.car-image img');
        if (car.InternetPhoto && car.InternetPhoto.trim() !== '') {
            carImage.src = car.InternetPhoto;
            carImage.alt = car.GROUPNAME || 'Car Image';
        } else {
            carImage.src = 'https://via.placeholder.com/120x90?text=No+Image';
            carImage.alt = 'No Image Available';
        }
        
        // Car specs
        carCard.querySelector('.passengers-num').textContent = car.PassangersNum || 'N/A';
        carCard.querySelector('.transmission').textContent = 
            car.IsAutomatic === '1' ? 'Automatic' : 
            car.IsManual === '1' ? 'Manual' : 'N/A';
        carCard.querySelector('.has-ac').textContent = 
            car.IsAirconditioning === '1' ? 'Yes' : 'No';
        
        // Suitcases
        const largeSuitcases = car.LargeSuitcaseNum || '0';
        const smallSuitcases = car.SmallSuitcaseNum || '0';
        carCard.querySelector('.suitcases').textContent = 
            `${largeSuitcases} large, ${smallSuitcases} small`;
        
        // Price
        const price = parseFloat(car.PriceAfterDiscountShekelsWithVAT || 0).toLocaleString();
        carCard.querySelector('.price-value').textContent = price;
        carCard.querySelector('.price-currency').textContent = ' ILS';
        
        // Branch name
        carCard.querySelector('.branch-name').textContent = car.BranchName || 'Unknown Location';
        
        // Append the card to the results
        carResults.appendChild(carCard);
    });
}

/**
 * Update the car count display
 */
function updateCarCount(totalCount) {
    carCount.textContent = `${totalCount} cars found`;
}

/**
 * Update the loading state of the UI
 */
function updateLoadingState(isActive) {
    if (isSearchComplete) {
        // Don't show loading indicator if search is already complete
        loadingIndicator.classList.remove('active');
        return;
    }
    
    loadingIndicator.classList.toggle('active', isActive);
}

/**
 * Update the search status display
 */
function updateSearchStatus(isSearching, isComplete) {
    searchStatus.classList.toggle('searching', isSearching);
    searchStatus.classList.toggle('complete', isComplete);
    
    if (isComplete) {
        searchStatus.textContent = 'Search complete';
        loadingIndicator.classList.remove('active');
        
        // Hide the initial loading indicator if it's still showing
        if (window.loadingIndicators && window.loadingIndicators.isInitialLoading) {
            window.loadingIndicators.showInitialLoading(false);
        }
    } else if (isSearching) {
        searchStatus.textContent = 'Searching...';
    } else {
        searchStatus.textContent = 'Showing results';
    }
}

/**
 * Show an error message
 */
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `<p>${message}</p>`;
    
    const container = document.querySelector('.search-container');
    container.innerHTML = '';
    container.appendChild(errorDiv);
}

/**
 * Stop all polling intervals
 */
function stopPolling() {
    if (statusCheckInterval) {
        clearInterval(statusCheckInterval);
        statusCheckInterval = null;
    }
    
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}