// Checkout Error Monitor - Inject this into checkout.php to catch all errors
(function() {
    'use strict';
    
    // Error collection
    let errors = [];
    let networkRequests = [];
    let userActions = [];
    
    // Create error display panel
    function createErrorPanel() {
        const panel = document.createElement('div');
        panel.id = 'errorPanel';
        panel.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            width: 400px;
            max-height: 500px;
            background: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            border: 2px solid #ff0000;
            z-index: 9999;
            overflow-y: auto;
            padding: 10px;
            display: none;
        `;
        
        panel.innerHTML = `
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 10px;">
                <h4 style="color: #ff0000; margin: 0;">🔥 ERROR MONITOR</h4>
                <button onclick="toggleErrorPanel()" style="background: #ff0000; color: white; border: none; padding: 5px;">Hide</button>
            </div>
            <div id="errorContent"></div>
        `;
        
        document.body.appendChild(panel);
        
        // Toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.innerHTML = '🚨 ERRORS';
        toggleBtn.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: #ff0000;
            color: white;
            border: none;
            padding: 10px;
            z-index: 10000;
            cursor: pointer;
            font-weight: bold;
        `;
        toggleBtn.onclick = () => toggleErrorPanel();
        document.body.appendChild(toggleBtn);
        
        return panel;
    }
    
    window.toggleErrorPanel = function() {
        const panel = document.getElementById('errorPanel');
        const isVisible = panel.style.display !== 'none';
        panel.style.display = isVisible ? 'none' : 'block';
        updateErrorDisplay();
    };
    
    function logError(type, message, details = {}) {
        const timestamp = new Date().toLocaleTimeString();
        const error = {
            timestamp,
            type,
            message,
            details,
            url: window.location.href,
            userAgent: navigator.userAgent
        };
        
        errors.push(error);
        console.error(`[${timestamp}] ${type}: ${message}`, details);
        updateErrorDisplay();
        
        // Send to server for logging
        sendErrorToServer(error);
    }
    
    function updateErrorDisplay() {
        const content = document.getElementById('errorContent');
        if (!content) return;
        
        content.innerHTML = `
            <div style="margin-bottom: 10px;">
                <strong>Total Errors: ${errors.length}</strong> | 
                <strong>Network Requests: ${networkRequests.length}</strong> | 
                <strong>User Actions: ${userActions.length}</strong>
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
                ${errors.slice(-20).map(error => `
                    <div style="border-bottom: 1px solid #333; padding: 5px; margin: 5px 0;">
                        <div style="color: #ff6666;">[${error.timestamp}] ${error.type}</div>
                        <div style="color: #ffff66;">${error.message}</div>
                        ${Object.keys(error.details).length > 0 ? 
                            `<div style="color: #66ff66; font-size: 10px;">${JSON.stringify(error.details, null, 2)}</div>` 
                            : ''}
                    </div>
                `).join('')}
            </div>
            <button onclick="downloadErrorLog()" style="background: #00ff00; color: black; border: none; padding: 5px; margin-top: 10px;">
                📥 Download Log
            </button>
        `;
    }
    
    window.downloadErrorLog = function() {
        const logData = {
            errors,
            networkRequests,
            userActions,
            pageInfo: {
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            }
        };
        
        const blob = new Blob([JSON.stringify(logData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `checkout-errors-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
    };
    
    function sendErrorToServer(error) {
        fetch('../debug/log_error.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(error)
        }).catch(e => console.warn('Failed to send error to server:', e));
    }
    
    // Capture JavaScript errors
    window.addEventListener('error', function(e) {
        logError('JavaScript Error', e.message, {
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno,
            stack: e.error ? e.error.stack : 'No stack trace'
        });
    });
    
    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        logError('Unhandled Promise Rejection', e.reason, {
            promise: e.promise
        });
    });
    
    // Monitor fetch requests
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const startTime = Date.now();
        const url = args[0];
        
        return originalFetch.apply(this, args)
            .then(response => {
                const duration = Date.now() - startTime;
                networkRequests.push({
                    timestamp: new Date().toLocaleTimeString(),
                    url,
                    method: args[1]?.method || 'GET',
                    status: response.status,
                    duration,
                    success: response.ok
                });
                
                if (!response.ok) {
                    logError('Network Error', `HTTP ${response.status} for ${url}`, {
                        status: response.status,
                        statusText: response.statusText,
                        duration
                    });
                }
                
                return response;
            })
            .catch(error => {
                const duration = Date.now() - startTime;
                networkRequests.push({
                    timestamp: new Date().toLocaleTimeString(),
                    url,
                    method: args[1]?.method || 'GET',
                    error: error.message,
                    duration,
                    success: false
                });
                
                logError('Network Failure', `Failed to fetch ${url}`, {
                    error: error.message,
                    duration
                });
                
                throw error;
            });
    };
    
    // Monitor form submissions
    document.addEventListener('submit', function(e) {
        userActions.push({
            timestamp: new Date().toLocaleTimeString(),
            action: 'form_submit',
            form: e.target.id || e.target.className,
            data: new FormData(e.target)
        });
    });
    
    // Monitor button clicks
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'BUTTON' || e.target.type === 'submit') {
            userActions.push({
                timestamp: new Date().toLocaleTimeString(),
                action: 'button_click',
                button: e.target.id || e.target.textContent.trim(),
                disabled: e.target.disabled
            });
        }
    });
    
    // Monitor input changes
    document.addEventListener('input', function(e) {
        if (e.target.type === 'email' || e.target.type === 'tel' || e.target.type === 'text') {
            userActions.push({
                timestamp: new Date().toLocaleTimeString(),
                action: 'input_change',
                field: e.target.id || e.target.name,
                value: e.target.value.length > 0 ? '[REDACTED]' : '[EMPTY]',
                valid: e.target.checkValidity()
            });
        }
    });
    
    // Checkout-specific monitoring
    function monitorCheckoutFlow() {
        // Monitor step changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('payment-step') && target.classList.contains('active')) {
                        userActions.push({
                            timestamp: new Date().toLocaleTimeString(),
                            action: 'step_change',
                            step: target.id
                        });
                    }
                }
            });
        });
        
        document.querySelectorAll('.payment-step').forEach(step => {
            observer.observe(step, { attributes: true });
        });
        
        // Monitor payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                userActions.push({
                    timestamp: new Date().toLocaleTimeString(),
                    action: 'payment_method_selected',
                    method: this.dataset.method
                });
            });
        });
        
        // Monitor bank selection
        document.querySelectorAll('.bank-option').forEach(bank => {
            bank.addEventListener('click', function() {
                userActions.push({
                    timestamp: new Date().toLocaleTimeString(),
                    action: 'bank_selected',
                    bank: this.dataset.bank
                });
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            createErrorPanel();
            monitorCheckoutFlow();
            logError('Info', 'Error monitor initialized');
        });
    } else {
        createErrorPanel();
        monitorCheckoutFlow();
        logError('Info', 'Error monitor initialized');
    }
    
    // Periodic health check
    setInterval(function() {
        // Check if critical elements exist
        const criticalElements = [
            '#sendInstructionsBtn',
            '#verifyCodeBtn',
            '#submitReceiptBtn'
        ];
        
        criticalElements.forEach(selector => {
            const element = document.querySelector(selector);
            if (!element) {
                logError('DOM Error', `Critical element missing: ${selector}`);
            }
        });
        
        // Check for JavaScript errors in console
        if (window.console && console.error) {
            const originalError = console.error;
            console.error = function(...args) {
                logError('Console Error', args.join(' '));
                originalError.apply(console, args);
            };
        }
    }, 5000);
    
})();