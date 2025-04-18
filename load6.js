/**
 * load6.js - Loading indicators for progressive car loading
 */

class LoadingIndicators {
    constructor() {
        // Initial loading elements
        this.initialLoadingContainer = null;
        this.loadingMessage = null;
        this.loadingDetails = null;
        this.progressFill = null;
        this.iterationStats = null;
        this.carStats = null;
        
        // Toast notification elements
        this.toastContainer = null;
        this.activeToasts = [];
        
        // State tracking
        this.isInitialLoading = true;
        this.currentIteration = 0;
        this.totalCarsLoaded = 0;
        this.lastUIDs = [];
        
        // Create and append the loading elements to the DOM
        this.createElements();
    }
    
    /**
     * Create the loading indicator elements
     */
    createElements() {
        // Create toast container
        this.toastContainer = document.createElement('div');
        this.toastContainer.className = 'toast-container';
        document.body.appendChild(this.toastContainer);
        
        // Create initial loading container
        this.initialLoadingContainer = document.createElement('div');
        this.initialLoadingContainer.className = 'initial-loading-container';
        this.initialLoadingContainer.style.display = 'none';
        
        // Create loader animation
        const loaderAnimation = document.createElement('div');
        loaderAnimation.className = 'loader-animation';
        
        const carLoader = document.createElement('div');
        carLoader.className = 'car-loader';
        
        const wheelLeft = document.createElement('div');
        wheelLeft.className = 'wheel wheel-left';
        
        const wheelRight = document.createElement('div');
        wheelRight.className = 'wheel wheel-right';
        
        const road = document.createElement('div');
        road.className = 'road';
        
        carLoader.appendChild(wheelLeft);
        carLoader.appendChild(wheelRight);
        loaderAnimation.appendChild(carLoader);
        loaderAnimation.appendChild(road);
        
        // Create loading message
        this.loadingMessage = document.createElement('div');
        this.loadingMessage.className = 'loading-message';
        this.loadingMessage.textContent = 'Searching for the best car deals...';
        
        // Create loading details
        this.loadingDetails = document.createElement('div');
        this.loadingDetails.className = 'loading-details';
        this.loadingDetails.textContent = 'This may take a few moments';
        
        // Create progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'progress-bar';
        
        this.progressFill = document.createElement('div');
        this.progressFill.className = 'progress-fill';
        progressBar.appendChild(this.progressFill);
        
        // Create loading stats
        const loadingStats = document.createElement('div');
        loadingStats.className = 'loading-stats';
        
        this.iterationStats = document.createElement('span');
        this.iterationStats.textContent = 'Iteration: 0';
        
        this.carStats = document.createElement('span');
        this.carStats.textContent = 'Cars: 0';
        
        loadingStats.appendChild(this.iterationStats);
        loadingStats.appendChild(this.carStats);
        
        // Append all elements to the container
        this.initialLoadingContainer.appendChild(loaderAnimation);
        this.initialLoadingContainer.appendChild(this.loadingMessage);
        this.initialLoadingContainer.appendChild(this.loadingDetails);
        this.initialLoadingContainer.appendChild(progressBar);
        this.initialLoadingContainer.appendChild(loadingStats);
        
        // Find the car results container and insert our loading container before it
        const carResults = document.getElementById('carResults');
        if (carResults) {
            carResults.parentNode.insertBefore(this.initialLoadingContainer, carResults);
        } else {
            // Fallback if carResults isn't available yet
            document.querySelector('.search-container').appendChild(this.initialLoadingContainer);
        }
    }
    
    /**
     * Show the initial loading indicator
     * @param {boolean} show - Whether to show or hide the indicator
     */
    showInitialLoading(show) {
        if (this.initialLoadingContainer) {
            this.initialLoadingContainer.style.display = show ? 'flex' : 'none';
        }
        
        // Update the isInitialLoading state
        this.isInitialLoading = show;
    }
    
    /**
     * Update the initial loading indicator with new data
     * @param {Object} data - The loading progress data
     */
    updateInitialLoading(data) {
        if (!data) return;
        
        // Update iteration number
        this.currentIteration = data.iteration || this.currentIteration;
        
        // Update the progress fill (max 6 iterations based on the table example)
        const progressPercent = Math.min((this.currentIteration / 6) * 100, 100);
        this.progressFill.style.width = `${progressPercent}%`;
        
        // Update the iteration stats
        this.iterationStats.textContent = `Iteration: ${this.currentIteration}`;
        
        // Update car stats
        const totalCars = data.totalCarsCount || 0;
        this.carStats.textContent = `Cars: ${totalCars}`;
        
        // Update loading details based on search progress
        if (data.processingCount === 0 && data.completeCount > 0) {
            this.loadingDetails.textContent = 'Search complete, loading cars...';
        } else if (data.processingCount > 0) {
            this.loadingDetails.textContent = `Processing... (${data.processingCount} remaining)`;
        }
        
        // Store the lastUID
        if (data.lastUID) {
            this.lastUIDs.push(data.lastUID);
        }
        
        // If we have any cars and this is still the initial loading phase, we'll transition
        // to the toast notifications for subsequent updates
        if (totalCars > 0 && this.isInitialLoading) {
            this.totalCarsLoaded = totalCars;
            
            // Wait a moment to let the user see the cars, then hide the initial loader
            setTimeout(() => {
                this.showInitialLoading(false);
            }, 1500);
        }
    }
    
    /**
     * Show a toast notification for new cars
     * @param {number} newCarsCount - Number of new cars being loaded
     */
    showNewCarsToast(newCarsCount) {
        if (newCarsCount <= 0) return;
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        
        // Icon
        const iconDiv = document.createElement('div');
        iconDiv.className = 'toast-icon';
        
        const spinner = document.createElement('div');
        spinner.className = 'toast-spinner';
        iconDiv.appendChild(spinner);
        
        // Content
        const contentDiv = document.createElement('div');
        contentDiv.className = 'toast-content';
        
        const message = document.createElement('div');
        message.className = 'toast-message';
        message.textContent = `Loading ${newCarsCount} new car${newCarsCount > 1 ? 's' : ''}`;
        
        const detail = document.createElement('div');
        detail.className = 'toast-detail';
        detail.textContent = 'Please wait a moment...';
        
        contentDiv.appendChild(message);
        contentDiv.appendChild(detail);
        
        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', () => this.removeToast(toast));
        
        // Assemble toast
        toast.appendChild(iconDiv);
        toast.appendChild(contentDiv);
        toast.appendChild(closeBtn);
        
        // Add to container
        this.toastContainer.appendChild(toast);
        this.activeToasts.push(toast);
        
        // Trigger animation after a small delay
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            this.removeToast(toast);
        }, 5000);
    }
    
    /**
     * Remove a toast notification
     * @param {HTMLElement} toast - The toast element to remove
     */
    removeToast(toast) {
        toast.classList.remove('show');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            // Remove from active toasts array
            this.activeToasts = this.activeToasts.filter(t => t !== toast);
        }, 300);
    }
    
    /**
     * Process new loading data and update indicators accordingly
     * @param {Object} data - The loading progress data
     */
    processLoadingUpdate(data) {
        if (!data) return;
        
        // Get new cars count from data
        const newCarsCount = data.newCarsCount || 0;
        
        if (this.isInitialLoading) {
            // If we're still in initial loading phase, update the initial loading indicator
            this.updateInitialLoading(data);
        } else if (newCarsCount > 0) {
            // If we've already shown cars and there are new ones, show a toast
            this.showNewCarsToast(newCarsCount);
        }
        
        // Check if search is complete
        if (data.processingCount === 0 && data.completeCount > 0) {
            // Clear all toasts when search is complete
            this.activeToasts.forEach(toast => this.removeToast(toast));
        }
    }
}

// Initialize the loading indicators
const loadingIndicators = new LoadingIndicators();

// Show initial loading if we're doing a new search
if (typeof isNewSearch !== 'undefined' && isNewSearch) {
    loadingIndicators.showInitialLoading(true);
}

// Export the loadingIndicators instance for use in tester6.js
window.loadingIndicators = loadingIndicators;