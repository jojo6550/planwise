/**
 * PlanWise Main JavaScript Application
 * Centralized AJAX handler and utility functions
 * CS334 Module 1 - AJAX Integration (10 marks)
 */

// Application namespace
const PlanWise = {
    // Configuration
    config: {
        baseUrl: '/planwise',
        apiTimeout: 30000, // 30 seconds
        csrfToken: null
    },

    /**
     * Initialize application
     */
    init: function() {
        console.log('PlanWise Application Initialized');
        this.setupCSRFToken();
        this.setupGlobalEventListeners();
        this.setupFormValidation();
    },

    /**
     * Setup CSRF token for AJAX requests
     */
    setupCSRFToken: function() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            this.config.csrfToken = tokenMeta.content;
        }
    },

    /**
     * Centralized AJAX Request Handler
     * @param {string} url - Request URL
     * @param {object} options - Request options
     * @returns {Promise}
     */
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        // Add CSRF token if available
        if (this.config.csrfToken) {
            defaults.headers['X-CSRF-Token'] = this.config.csrfToken;
        }

        // Merge options
        const config = { ...defaults, ...options };

        // Convert body to JSON if it's an object
        if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
            config.body = JSON.stringify(config.body);
        }

        // Show loading indicator
        this.showLoading();

        return fetch(url, config)
            .then(response => {
                // Hide loading indicator
                this.hideLoading();

                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Parse JSON response
                return response.json();
            })
            .catch(error => {
                this.hideLoading();
                console.error('AJAX Error:', error);
                this.showError('An error occurred. Please try again.');
                throw error;
            });
    },

    /**
     * GET request
     */
    get: function(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.ajax(fullUrl, { method: 'GET' });
    },

    /**
     * POST request
     */
    post: function(url, data = {}) {
        return this.ajax(url, {
            method: 'POST',
            body: data
        });
    },

    /**
     * PUT request
     */
    put: function(url, data = {}) {
        return this.ajax(url, {
            method: 'PUT',
            body: data
        });
    },

    /**
     * DELETE request
     */
    delete: function(url, data = {}) {
        return this.ajax(url, {
            method: 'DELETE',
            body: data
        });
    },

    /**
     * Show loading indicator
     */
    showLoading: function() {
        let loader = document.getElementById('global-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.className = 'global-loader';
            loader.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            document.body.appendChild(loader);
        }
        loader.style.display = 'flex';
    },

    /**
     * Hide loading indicator
     */
    hideLoading: function() {
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    },

    /**
     * Show success message
     */
    showSuccess: function(message) {
        this.showAlert(message, 'success');
    },

    /**
     * Show error message
     */
    showError: function(message) {
        this.showAlert(message, 'danger');
    },

    /**
     * Show warning message
     */
    showWarning: function(message) {
        this.showAlert(message, 'warning');
    },

    /**
     * Show alert message
     */
    showAlert: function(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container') || this.createAlertContainer();
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.role = 'alert';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    },

    /**
     * Create alert container
     */
    createAlertContainer: function() {
        const container = document.createElement('div');
        container.id = 'alert-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '400px';
        document.body.appendChild(container);
        return container;
    },

    /**
     * Setup global event listeners
     */
    setupGlobalEventListeners: function() {
        // AJAX form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleAjaxFormSubmit(e.target);
            }
        });

        // Confirm delete actions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('confirm-delete')) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            }
        });
    },

    /**
     * Handle AJAX form submission
     */
    handleAjaxFormSubmit: function(form) {
        const formData = new FormData(form);
        const data = this.formDataToObject(formData);
        const url = form.action;
        const method = form.method.toUpperCase();

        this.ajax(url, { method, body: data })
            .then(response => {
                if (response.success) {
                    this.showSuccess(response.message || 'Operation successful');
                    
                    // Redirect if specified
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                    
                    // Reload if specified
                    if (response.reload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    this.showError(response.message || 'Operation failed');
                    
                    // Display field errors
                    if (response.errors) {
                        this.displayFieldErrors(form, response.errors);
                    }
                }
            })
            .catch(error => {
                this.showError('An error occurred. Please try again.');
            });
    },

    /**
     * Convert FormData to nested object (handles array notation)
     */
    formDataToObject: function(formData) {
        const object = {};
        
        formData.forEach((value, key) => {
            // Handle array notation like sections[0][title]
            const matches = key.match(/^([^\[]+)(\[.+\])$/);
            
            if (matches) {
                const baseName = matches[1];
                const keys = matches[2].match(/\[([^\]]+)\]/g).map(k => k.slice(1, -1));
                
                // Initialize base object/array if needed
                if (!object[baseName]) {
                    object[baseName] = isNaN(keys[0]) ? {} : [];
                }
                
                // Navigate through nested structure
                let current = object[baseName];
                for (let i = 0; i < keys.length - 1; i++) {
                    const currentKey = keys[i];
                    const nextKey = keys[i + 1];
                    
                    if (!current[currentKey]) {
                        current[currentKey] = isNaN(nextKey) ? {} : [];
                    }
                    current = current[currentKey];
                }
                
                // Set the final value
                current[keys[keys.length - 1]] = value;
            } else {
                // Simple key-value pair
                object[key] = value;
            }
        });
        
        return object;
    },

    /**
     * Display field-level validation errors
     */
    displayFieldErrors: function(form, errors) {
        // Clear previous errors
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                
                input.parentNode.appendChild(feedback);
            }
        });
    },

    /**
     * Setup form validation
     */
    setupFormValidation: function() {
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    },

    /**
     * Confirm action with modal
     */
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    /**
     * Format date
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    /**
     * Format time
     */
    formatTime: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    PlanWise.init();
});

// Export for use in other scripts
window.PlanWise = PlanWise;
