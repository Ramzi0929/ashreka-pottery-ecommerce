// Translatable Messages System
const messages = {
    en: {
        'login_required': 'Please login to purchase products',
        'login_required_title': 'Login Required',
        'login_required_desc': 'Please login to access shopping features',
        'login_btn': 'Login',
        'register_btn': 'Register',
        'added_to_cart': 'Added to cart!',
        'please_login': 'Please login as a customer to rate products',
        'error': 'Error',
        'success': 'Success',
        'loading': 'Loading...',
        'close': 'Close'
    },
    am: {
        'login_required': 'ምርቶችን ለመግዛት እባክዎ ይግቡ',
        'login_required_title': 'መግቢያ ያስፈልጋል',
        'login_required_desc': 'የግዢ አገልግሎቶችን ለመጠቀም እባክዎ ይግቡ',
        'login_btn': 'ግባ',
        'register_btn': 'ተመዝገብ',
        'added_to_cart': 'ወደ ጋሪ ተጨምሯል!',
        'please_login': 'ምርቶችን ለመገምገም እንደ ደንበኛ እባክዎ ይግቡ',
        'error': 'ስህተት',
        'success': 'ተሳክቷል',
        'loading': 'በመጫን ላይ...',
        'close': 'ዝጋ'
    }
};

function getCurrentLang() {
    // Check if page is translated to Amharic
    const body = document.body;
    const isAmharic = body.classList.contains('translated-ltr') || 
                     document.querySelector('.goog-te-combo')?.value === 'am' ||
                     localStorage.getItem('lang') === 'am';
    return isAmharic ? 'am' : 'en';
}

function t(key) {
    const lang = getCurrentLang();
    return messages[lang][key] || messages.en[key] || key;
}

// Override alert function
window.originalAlert = window.alert;
window.alert = function(message) {
    // Try to translate common messages
    const translations = {
        'Please login to purchase products': t('login_required'),
        'Please login as a customer to rate products': t('please_login')
    };
    
    const translatedMessage = translations[message] || message;
    window.originalAlert(translatedMessage);
};

// Enhanced showLoginModal with translation
function showLoginModal() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div class="auth-modal-backdrop">
            <div class="auth-modal">
                <div class="auth-modal-header">
                    <h4>${t('login_required_title')}</h4>
                    <button onclick="closeAuthModal()" class="close-btn">&times;</button>
                </div>
                <div class="auth-modal-body">
                    <p>${t('login_required_desc')}</p>
                    <div class="auth-buttons">
                        <a href="views/auth/login.php" class="btn btn-primary">${t('login_btn')}</a>
                        <a href="views/auth/register.php" class="btn btn-outline-primary">${t('register_btn')}</a>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .auth-modal-backdrop {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.7); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
            }
            .auth-modal {
                background: white; border-radius: 15px; max-width: 400px; width: 90%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .auth-modal-header {
                background: linear-gradient(135deg, #009639, #FFCD00);
                color: white; padding: 20px; border-radius: 15px 15px 0 0;
                display: flex; justify-content: space-between; align-items: center;
            }
            .auth-modal-body { padding: 30px; text-align: center; }
            .auth-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
            .close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
            .btn { padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: bold; }
            .btn-primary { background: linear-gradient(135deg, #009639, #FFCD00); color: white; border: none; }
            .btn-outline-primary { background: transparent; color: #009639; border: 2px solid #009639; }
        </style>
    `;
    document.body.appendChild(modal);
}