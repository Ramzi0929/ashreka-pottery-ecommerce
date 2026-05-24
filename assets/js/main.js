// Enhanced JavaScript for Ashreka Pottery System

// Global variables
let currentLanguage = 'en';
let cartItems = [];
let notifications = [];

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    loadNotifications();
    initializeSearch();
    initializeCart();

});

// Initialize application
function initializeApp() {
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Add loading states to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                submitBtn.disabled = true;
            }
        });
    });
}

// Language Translation Functions
function translatePage(lang) {
    currentLanguage = lang;
    localStorage.setItem('preferred_language', lang);
    
    // Use Google Translate
    const selectField = document.querySelector("select.goog-te-combo");
    if (selectField) {
        selectField.value = lang;
        selectField.dispatchEvent(new Event('change'));
    }
    
    // Update language buttons
    document.querySelectorAll('.language-toggle .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[onclick="translatePage('${lang}')"]`).classList.add('active');
}

// Load saved language preference
function loadLanguagePreference() {
    const savedLang = localStorage.getItem('preferred_language');
    if (savedLang) {
        translatePage(savedLang);
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchProducts, 300));
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', searchProducts);
    }
}

function searchProducts() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const category = document.getElementById('categoryFilter')?.value || '';
    const products = document.querySelectorAll('.product-card');

    let visibleCount = 0;

    products.forEach(product => {
        const title = product.querySelector('.card-title')?.textContent.toLowerCase() || '';
        const description = product.querySelector('.card-text')?.textContent.toLowerCase() || '';
        const productCategory = product.dataset.category || '';
        
        const matchesSearch = !searchTerm || title.includes(searchTerm) || description.includes(searchTerm);
        const matchesCategory = !category || productCategory === category;
        
        if (matchesSearch && matchesCategory) {
            product.style.display = 'block';
            product.classList.add('fade-in');
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });

    // Show no results message
    updateSearchResults(visibleCount);
}

function updateSearchResults(count) {
    let resultsMessage = document.getElementById('searchResults');
    if (!resultsMessage) {
        resultsMessage = document.createElement('div');
        resultsMessage.id = 'searchResults';
        resultsMessage.className = 'text-center mt-3';
        document.getElementById('productsContainer')?.parentNode.appendChild(resultsMessage);
    }

    if (count === 0) {
        resultsMessage.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-search me-2"></i>
                <span class="translate">No products found matching your criteria</span>
            </div>
        `;
    } else {
        resultsMessage.innerHTML = `
            <p class="text-muted">
                <span class="translate">Showing</span> ${count} <span class="translate">products</span>
            </p>
        `;
    }
}

// Product functions
function playVideo(videoPath) {
    const videoModal = document.getElementById('videoModal');
    const productVideo = document.getElementById('productVideo');
    
    if (productVideo && videoModal) {
        productVideo.src = videoPath;
        const modal = new bootstrap.Modal(videoModal);
        modal.show();
        
        // Pause video when modal is closed
        videoModal.addEventListener('hidden.bs.modal', function() {
            productVideo.pause();
            productVideo.currentTime = 0;
        });
    }
}

function buyProduct(productId) {
    // Check if user is logged in
    if (!isUserLoggedIn()) {
        showLoginPrompt();
        return;
    }

    // Show loading
    showLoading('Processing your order...');

    // Send purchase request
    fetch('controllers/OrderController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=buy_catalog&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            // Redirect to payment
            window.location.href = data.payment_url;
        } else {
            showAlert('error', data.message || 'Failed to process order');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showAlert('error', 'An error occurred while processing your order');
    });
}

function addToCart(productId, quantity = 1) {
    const existingItem = cartItems.find(item => item.productId === productId);
    
    if (existingItem) {
        existingItem.quantity += quantity;
    } else {
        cartItems.push({ productId, quantity });
    }
    
    updateCartUI();
    saveCartToStorage();
    showAlert('success', 'Product added to cart');
}

function removeFromCart(productId) {
    cartItems = cartItems.filter(item => item.productId !== productId);
    updateCartUI();
    saveCartToStorage();
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartTotal = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    
    if (cartCount) {
        cartCount.textContent = cartTotal;
        cartCount.style.display = cartTotal > 0 ? 'inline' : 'none';
    }
}

function initializeCart() {
    loadCartFromStorage();
    updateCartUI();
}

function saveCartToStorage() {
    localStorage.setItem('cart_items', JSON.stringify(cartItems));
}

function loadCartFromStorage() {
    const saved = localStorage.getItem('cart_items');
    if (saved) {
        cartItems = JSON.parse(saved);
    }
}

// Notification functions
function loadNotifications() {
    if (!isUserLoggedIn()) return;

    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notifications = data.notifications;
                updateNotificationUI();
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function updateNotificationUI() {
    const notificationBadge = document.getElementById('notificationBadge');
    const unreadCount = notifications.filter(n => !n.is_read).length;
    
    if (notificationBadge) {
        notificationBadge.textContent = unreadCount;
        notificationBadge.style.display = unreadCount > 0 ? 'inline' : 'none';
    }
}

function markNotificationAsRead(notificationId) {
    fetch('api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_read&notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notification = notifications.find(n => n.id === notificationId);
            if (notification) {
                notification.is_read = true;
                updateNotificationUI();
            }
        }
    });
}

// Utility functions
function debounce(func, wait) {
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

function isUserLoggedIn() {
    // Check if user session exists (you might need to adjust this based on your session handling)
    return document.body.dataset.userId !== undefined;
}

function showLoginPrompt() {
    const modal = `
        <div class="modal fade" id="loginPromptModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title translate">Login Required</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="translate">Please login to purchase products and access all features.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="translate">Cancel</span>
                        </button>
                        <a href="views/auth/login.php" class="btn btn-primary">
                            <span class="translate">Login</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
    new bootstrap.Modal(document.getElementById('loginPromptModal')).show();
}

function showLoading(message = 'Loading...') {
    const loadingModal = `
        <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner mb-3"></div>
                        <p class="translate">${message}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', loadingModal);
    new bootstrap.Modal(document.getElementById('loadingModal')).show();
}

function hideLoading() {
    const loadingModal = document.getElementById('loadingModal');
    if (loadingModal) {
        bootstrap.Modal.getInstance(loadingModal).hide();
        setTimeout(() => loadingModal.remove(), 300);
    }
}

function showAlert(type, message, duration = 5000) {
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';

    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alert);

    // Auto remove after duration
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, duration);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ET', {
        style: 'currency',
        currency: 'ETB',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-ET', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^\+?251[0-9]{9}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// File upload functions
function handleFileUpload(input, preview, maxSize = 10 * 1024 * 1024) { // 10MB default
    const file = input.files[0];
    if (!file) return;

    // Check file size
    if (file.size > maxSize) {
        showAlert('error', `File size must be less than ${maxSize / (1024 * 1024)}MB`);
        input.value = '';
        return;
    }

    // Show preview for images
    if (file.type.startsWith('image/') && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Show preview for videos
    if (file.type.startsWith('video/') && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Initialize file upload handlers
document.addEventListener('DOMContentLoaded', function() {
    // Image upload handlers
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        const previewId = input.dataset.preview;
        const preview = previewId ? document.getElementById(previewId) : null;
        
        input.addEventListener('change', function() {
            handleFileUpload(this, preview);
        });
    });

    // Video upload handlers
    document.querySelectorAll('input[type="file"][accept*="video"]').forEach(input => {
        const previewId = input.dataset.preview;
        const preview = previewId ? document.getElementById(previewId) : null;
        
        input.addEventListener('change', function() {
            handleFileUpload(this, preview);
        });
    });
});

// Real-time form validation
function initializeFormValidation() {
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validateEmail(this.value)) {
                this.classList.add('is-invalid');
                showFieldError(this, 'Please enter a valid email address');
            } else {
                this.classList.remove('is-invalid');
                hideFieldError(this);
            }
        });
    });

    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value && !validatePhone(this.value)) {
                this.classList.add('is-invalid');
                showFieldError(this, 'Please enter a valid Ethiopian phone number');
            } else {
                this.classList.remove('is-invalid');
                hideFieldError(this);
            }
        });
    });
}

function showFieldError(field, message) {
    hideFieldError(field);
    const error = document.createElement('div');
    error.className = 'invalid-feedback';
    error.textContent = message;
    field.parentNode.appendChild(error);
}

function hideFieldError(field) {
    const error = field.parentNode.querySelector('.invalid-feedback');
    if (error) {
        error.remove();
    }
}

// Initialize form validation when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeFormValidation);

// Export functions for use in other scripts
window.AshrekaPottery = {
    translatePage,
    searchProducts,
    playVideo,
    buyProduct,
    addToCart,
    removeFromCart,
    showAlert,
    showLoading,
    hideLoading,
    formatCurrency,
    formatDate,
    validateEmail,
    validatePhone
};