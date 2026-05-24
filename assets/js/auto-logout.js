// Auto-logout functionality for customer pages
class AutoLogout {
    constructor() {
        this.timeoutId = null;
        this.timeoutDuration = localStorage.getItem('sessionTimeout') || 'off';
        this.init();
    }
    
    init() {
        // Only run if session timeout is set
        if (this.timeoutDuration === 'off') {
            return;
        }
        
        this.resetTimeout();
        this.bindEvents();
    }
    
    resetTimeout() {
        clearTimeout(this.timeoutId);
        
        if (this.timeoutDuration !== 'off') {
            const duration = parseInt(this.timeoutDuration) * 1000;
            this.timeoutId = setTimeout(() => {
                this.logout();
            }, duration);
        }
    }
    
    logout() {
        // Save current page for resumption
        localStorage.setItem('lastPage', window.location.href);
        
        // Auto logout without user confirmation
        window.location.href = '../../controllers/AuthController.php?action=logout&timeout=1';
    }
    
    bindEvents() {
        // Reset timeout on user activity
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, () => this.resetTimeout(), true);
        });
    }
    
    updateTimeout(newDuration) {
        this.timeoutDuration = newDuration;
        localStorage.setItem('sessionTimeout', newDuration);
        this.resetTimeout();
    }
}

// Initialize auto-logout when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.autoLogout = new AutoLogout();
});

// Global function to update timeout from dashboard
function updateTimeout() {
    const setting = document.getElementById('timeoutSetting').value;
    if (window.autoLogout) {
        window.autoLogout.updateTimeout(setting);
    }
}