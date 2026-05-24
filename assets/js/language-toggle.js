// Universal Language Toggle System - Amharic Default
class LanguageToggle {
    constructor() {
        this.currentLang = 'am'; // Default to Amharic
        this.isReady = false;
        this.init();
    }

    init() {
        this.createToggleButton();
        this.initGoogleTranslate();
        this.bindEvents();
        this.loadSavedLanguage();
    }

    createToggleButton() {
        // Remove any existing language toggles
        document.querySelectorAll('.language-toggle, .language-toggle-container, .lang-toggle').forEach(el => el.remove());

        const toggleHTML = `
            <div class="lang-toggle-fixed">
                <button class="lang-btn" data-lang="am" title="አማርኛ">
                    <span class="flag">🇪🇹</span>
                    <span class="text">አማ</span>
                </button>
                <button class="lang-btn" data-lang="en" title="English">
                    <span class="flag">🇺🇸</span>
                    <span class="text">En</span>
                </button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', toggleHTML);
        this.addStyles();
    }

    addStyles() {
        if (document.getElementById('lang-toggle-styles')) return;

        const styles = `
            <style id="lang-toggle-styles">
                .lang-toggle-fixed {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    background: rgba(255,255,255,0.95);
                    backdrop-filter: blur(10px);
                    border-radius: 25px;
                    padding: 4px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                    border: 2px solid #FFCD00;
                }

                .lang-btn {
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    padding: 6px 12px;
                    border: none;
                    background: transparent;
                    border-radius: 20px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    font-weight: bold;
                    color: #009639;
                    min-width: 60px;
                    justify-content: center;
                }

                .lang-btn:hover {
                    background: rgba(255,205,0,0.2);
                    transform: scale(1.05);
                }

                .lang-btn.active {
                    background: linear-gradient(135deg, #009639, #FFCD00);
                    color: white;
                    box-shadow: 0 2px 8px rgba(0,150,57,0.3);
                }

                .lang-btn .flag {
                    font-size: 14px;
                }

                .lang-btn .text {
                    font-size: 11px;
                    font-weight: 700;
                }

                @media (max-width: 768px) {
                    .lang-toggle-fixed {
                        top: 10px;
                        right: 10px;
                        padding: 3px;
                    }
                    .lang-btn {
                        padding: 4px 8px;
                        min-width: 50px;
                    }
                    .lang-btn .text {
                        font-size: 10px;
                    }
                }

                /* Hide Google Translate elements */
                .goog-te-banner-frame { display: none !important; }
                .goog-te-menu-value { display: none !important; }
                body { top: 0 !important; }
                #google_translate_element { display: none !important; }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    initGoogleTranslate() {
        // Remove existing Google Translate element
        const existing = document.getElementById('google_translate_element');
        if (existing) existing.remove();

        // Create new Google Translate element
        const translateDiv = document.createElement('div');
        translateDiv.id = 'google_translate_element';
        translateDiv.style.display = 'none';
        document.body.appendChild(translateDiv);

        // Initialize Google Translate
        window.googleTranslateElementInit = () => {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,am',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');

            setTimeout(() => {
                this.isReady = true;
                this.setDefaultLanguage();
            }, 500);
        };

        // Load Google Translate script if not already loaded
        if (!document.querySelector('script[src*="translate.google.com"]')) {
            const script = document.createElement('script');
            script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
            document.head.appendChild(script);
        } else {
            googleTranslateElementInit();
        }
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.lang-btn')) {
                const btn = e.target.closest('.lang-btn');
                const lang = btn.dataset.lang;
                this.switchLanguage(lang);
            }
        });
    }

    switchLanguage(lang) {
        if (!this.isReady) {
            setTimeout(() => this.switchLanguage(lang), 100);
            return;
        }

        const selectField = document.querySelector('select.goog-te-combo');
        if (selectField) {
            selectField.value = lang;
            selectField.dispatchEvent(new Event('change'));
            this.currentLang = lang;
            this.updateActiveButton(lang);
            localStorage.setItem('preferred_language', lang);
        }
    }

    updateActiveButton(lang) {
        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`[data-lang="${lang}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }

    setDefaultLanguage() {
        // Set Amharic as default
        this.switchLanguage('am');
    }

    loadSavedLanguage() {
        const saved = localStorage.getItem('preferred_language');
        if (saved && this.isReady) {
            this.switchLanguage(saved);
        } else {
            // Default to Amharic after Google Translate loads
            setTimeout(() => {
                if (this.isReady) {
                    this.switchLanguage('am');
                }
            }, 1000);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.languageToggle = new LanguageToggle();
});

// Fallback initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.languageToggle) {
            window.languageToggle = new LanguageToggle();
        }
    });
} else {
    if (!window.languageToggle) {
        window.languageToggle = new LanguageToggle();
    }
}