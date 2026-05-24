// Real-time Notifications System
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.init();
    }

    init() {
        this.createNotificationUI();
        this.loadNotifications();
        this.startPolling();
    }

    createNotificationUI() {
        // Use existing navbar notification bell if present.
        // If not present, you can still fall back to auto-creating it.
        const navbar = document.querySelector('.navbar-nav');

        // If a bell already exists in the layout (by id), do NOT inject another one.
        const existingBell =
            document.getElementById('notificationDropdown') ||
            document.getElementById('notificationBell');

        if (existingBell) {
            // Make sure clicking the bell does not navigate the page,
            // only toggles the Bootstrap dropdown.
            existingBell.addEventListener('click', (e) => {
                e.preventDefault();
            });
            return;
        }

        // Fallback: create a notification bell if there is no existing one
        if (navbar) {
            const notificationHTML = `
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notificationBell" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <li class="px-2 py-1">
                            <div id="notificationList"></div>
                        </li>
                    </ul>
                </li>
            `;
            navbar.insertAdjacentHTML('beforeend', notificationHTML);

            const bell = document.getElementById('notificationBell');
            if (bell) {
                bell.addEventListener('click', (e) => {
                    e.preventDefault();
                });
            }
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('../../api/notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.updateUI();
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('../../api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&notification_id=${notificationId}`
            });
            
            const data = await response.json();
            if (data.success) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.is_read = true;
                    this.updateUI();
                }
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('../../api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            });
            
            const data = await response.json();
            if (data.success) {
                this.notifications.forEach(n => n.is_read = true);
                this.updateUI();
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    updateUI() {
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');
        
        if (!badge || !list) return;

        // Count unread notifications
        this.unreadCount = this.notifications.filter(n => !n.is_read).length;
        
        // Update badge
        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }

        // Update notification list
        if (this.notifications.length === 0) {
            list.innerHTML = '';
        } else {
            list.innerHTML = this.notifications.slice(0, 10).map(notification => `
                <li>
                    <a class="dropdown-item ${notification.is_read ? '' : 'bg-light'}" 
                       href="#" onclick="notificationManager.markAsRead(${notification.id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${notification.title}</h6>
                                <p class="mb-1 small">${notification.message}</p>
                                <small class="text-muted">${this.formatTime(notification.created_at)}</small>
                            </div>
                            ${!notification.is_read ? '<span class="badge bg-primary">New</span>' : ''}
                        </div>
                    </a>
                </li>
            `).join('');
        }
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        return Math.floor(diff / 86400000) + 'd ago';
    }

    startPolling() {
        // Poll for new notifications every 30 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }

    showToast(title, message, type = 'info') {
        // Create toast notification
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" style="position: fixed; top: 100px; right: 20px; z-index: 1060;">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.querySelector('.toast:last-child');
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in
    if (document.body.dataset.userId || document.querySelector('.navbar-nav a[href*="dashboard"]')) {
        window.notificationManager = new NotificationManager();
    }
});

// Real-time stock update for products
function updateStockDisplay(productId) {
    fetch('../../api/stock_update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_stock&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stockElements = document.querySelectorAll(`[data-product-id="${productId}"] .stock-count`);
            stockElements.forEach(element => {
                element.textContent = data.quantity;
                if (data.quantity === 0) {
                    element.parentElement.classList.add('text-danger');
                    element.textContent = 'Out of Stock';
                }
            });
        }
    });
}

// Auto-update stock every 60 seconds
setInterval(() => {
    const productElements = document.querySelectorAll('[data-product-id]');
    productElements.forEach(element => {
        const productId = element.dataset.productId;
        if (productId) {
            updateStockDisplay(productId);
        }
    });
}, 60000);