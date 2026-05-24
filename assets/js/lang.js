// Powerful Translation System - Translates Everything
if (typeof translateReady === 'undefined') {
    var translateReady = false;
    var currentLang = localStorage.getItem('lang') || 'am';
    
    // Custom translation dictionary for cultural terms
    var customTranslations = {
        'en_to_am': {
            'jebena': 'ጀበና',
            'coffee pot': 'ጀበና',
            'mitad': 'ምጣድ',
            'injera': 'እንጀራ',
            'pottery': 'ሸክላ ስራ',
            'weaving': 'ሽመና',
            'traditional': 'ባህላዊ',
            'heritage': 'ቅርስ',
            'culture': 'ባህል',
            'artisan': 'ባለሙያ',
            'craft': 'እጅ ስራ'
        },
        'am_to_en': {
            'ጀበና': 'coffee pot',
            'jebena': 'coffee pot',
            'ምጣድ': 'mitad',
            'እንጀራ': 'injera',
            'ሸክላ ስራ': 'pottery',
            'ሽመና': 'weaving',
            'ባህላዊ': 'traditional',
            'ቅርስ': 'heritage',
            'ባህል': 'culture',
            'ባለሙያ': 'artisan',
            'እጅ ስራ': 'craft'
        }
    };
}

function initLanguage() {
    // Remove old toggles
    document.querySelectorAll('.language-toggle, .language-toggle-container, .lang-toggle-fixed, .lang-toggle').forEach(el => el.remove());
    
    // Create new toggle
    const toggle = document.createElement('div');
    toggle.innerHTML = `
        <div class="lang-switch">
            <div class="lang-label">Language</div>
            <div class="toggle-button" onclick="quickToggleLang()" id="lang-toggle">
                <div class="toggle-inner">
                    <span class="toggle-text" id="toggle-text">አማ</span>
                </div>
            </div>
        </div>
        <div id="google_translate_element"></div>
        <style>
            .lang-switch {
                position: fixed;
                top: 15px;
                right: 15px;
                z-index: 9999;
                text-align: center;
            }
            .lang-label {
                color: #dc3545;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 5px;
                text-shadow: 0 1px 2px rgba(255,255,255,0.8);
                background: #FFCD00;
                padding: 2px 8px;
                border-radius: 8px;
                display: inline-block;
            }
            .toggle-button {
                width: 80px;
                height: 40px;
                background: linear-gradient(135deg, #009639 0%, #FFCD00 100%);
                border: 2px solid #fff;
                border-radius: 20px;
                cursor: pointer;
                position: relative;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: all 0.2s ease;
                user-select: none;
            }
            .toggle-button:hover {
                transform: translateY(-2px) scale(1.05);
                box-shadow: 0 6px 20px rgba(0,0,0,0.25);
            }
            .toggle-button:active {
                transform: translateY(1px) scale(0.95);
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            .toggle-button.off {
                background: linear-gradient(225deg, #dc3545 0%, #6c757d 100%);
            }
            .toggle-button.on {
                background: linear-gradient(135deg, #009639 0%, #FFCD00 100%);
            }
            .toggle-inner {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.15s ease;
            }
            .toggle-button.off .toggle-inner {
                transform: translateX(-2px) translateY(2px);
            }
            .toggle-button.on .toggle-inner {
                transform: translateX(2px) translateY(-2px);
            }
            .toggle-text {
                color: white;
                font-weight: 700;
                font-size: 11px;
                text-shadow: 0 1px 2px rgba(0,0,0,0.3);
                transition: all 0.15s ease;
            }
            .toggle-button.off .toggle-text {
                transform: rotate(-5deg);
            }
            .toggle-button.on .toggle-text {
                transform: rotate(5deg);
            }
            #google_translate_element {
                display: none !important;
            }
            .goog-te-banner-frame { display: none !important; }
            .goog-te-menu-value { display: none !important; }
            .goog-te-gadget { display: none !important; }
            .goog-te-combo { display: none !important; }
            body { top: 0 !important; }
            html { margin-top: 0 !important; }
            .skiptranslate { display: none !important; }
            @media (max-width: 768px) {
                .lang-switch { top: 8px; right: 8px; }
                .toggle-button { width: 70px; height: 35px; }
                .toggle-text { font-size: 10px; }
            }
        </style>
    `;
    document.body.appendChild(toggle);
    
    // Initialize Google Translate
    window.googleTranslateElementInit = () => {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,am',
            autoDisplay: false
        }, 'google_translate_element');
        
        setTimeout(() => {
            translateReady = true;
            updateToggleDisplay();
            // Apply saved language
            if (currentLang === 'am') {
                forceTranslate('am');
            }
            // Start monitoring for inappropriate translations
            startTranslationMonitoring();
        }, 1000);
    };
    
    // Load Google Translate
    if (!document.querySelector('script[src*="translate.google.com"]')) {
        const script = document.createElement('script');
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        document.head.appendChild(script);
    } else {
        googleTranslateElementInit();
    }
    
    updateToggleDisplay();
}

function quickToggleLang() {
    currentLang = currentLang === 'en' ? 'am' : 'en';
    updateToggleDisplay();
    localStorage.setItem('lang', currentLang);
    
    if (translateReady) {
        // Apply custom translations first
        applyCustomTranslations(currentLang);
        // Then apply Google Translate
        forceTranslate(currentLang);
    }
}

function applyCustomTranslations(targetLang) {
    const dictionary = targetLang === 'am' ? customTranslations.en_to_am : customTranslations.am_to_en;
    
    // Find and replace custom terms in text nodes
    const walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode: function(node) {
                // Skip script, style, and translate elements
                const parent = node.parentElement;
                if (parent && (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE' || 
                    parent.classList.contains('lang-switch') || parent.classList.contains('notranslate'))) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        }
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    textNodes.forEach(textNode => {
        let text = textNode.textContent;
        let modified = false;
        
        Object.keys(dictionary).forEach(term => {
            const regex = new RegExp('\\b' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
            if (regex.test(text)) {
                text = text.replace(regex, dictionary[term]);
                modified = true;
            }
        });
        
        if (modified) {
            textNode.textContent = text;
        }
    });
}

function updateToggleDisplay() {
    const button = document.querySelector('.toggle-button');
    const text = document.getElementById('toggle-text');
    
    if (button && text) {
        if (currentLang === 'am') {
            button.className = 'toggle-button on';
            text.textContent = 'ኢንግሊዘኛ';
        } else {
            button.className = 'toggle-button off';
            text.textContent = 'Amharic';
        }
    }
}

function forceTranslate(lang) {
    const select = document.querySelector('select.goog-te-combo');
    if (select) {
        // Clear any existing translation
        if (lang === 'en') {
            select.value = 'en';
        } else {
            select.value = 'am';
        }
        
        // Trigger translation
        const event = new Event('change', { bubbles: true });
        select.dispatchEvent(event);
        
        // Clean up inappropriate translations after Google Translate finishes
        setTimeout(() => {
            cleanupInappropriateTranslations();
        }, 1500);
        
        // Force page refresh if needed
        setTimeout(() => {
            if (select.value !== lang) {
                select.value = lang;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                setTimeout(() => {
                    cleanupInappropriateTranslations();
                }, 1000);
            }
        }, 500);
    }
}

function cleanupInappropriateTranslations() {
    // Dictionary of inappropriate translations to fix
    const fixes = {
        'fucking': 'coffee pot',
        'fuck': 'coffee pot',
        'jebena': 'coffee pot',
        'ጀበና': 'coffee pot'
    };
    
    // Find all text nodes and fix inappropriate translations
    const walker = document.createTreeWalker(
        document.body,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode: function(node) {
                const parent = node.parentElement;
                if (parent && (parent.tagName === 'SCRIPT' || parent.tagName === 'STYLE' || 
                    parent.classList.contains('lang-switch'))) {
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        }
    );
    
    const textNodes = [];
    let node;
    while (node = walker.nextNode()) {
        textNodes.push(node);
    }
    
    textNodes.forEach(textNode => {
        let text = textNode.textContent;
        let modified = false;
        
        Object.keys(fixes).forEach(badWord => {
            const regex = new RegExp('\\b' + badWord.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi');
            if (regex.test(text)) {
                text = text.replace(regex, fixes[badWord]);
                modified = true;
            }
        });
        
        if (modified) {
            textNode.textContent = text;
        }
    });
    
    // Also check for the word in specific heritage content
    document.querySelectorAll('.heritage-details, .card-text, .card-title, p, h1, h2, h3, h4, h5, h6').forEach(element => {
        if (element.textContent.toLowerCase().includes('fucking') || element.textContent.toLowerCase().includes('fuck')) {
            element.textContent = element.textContent.replace(/fucking/gi, 'coffee pot').replace(/fuck/gi, 'coffee pot');
        }
    });
}

function startTranslationMonitoring() {
    // Monitor for inappropriate translations every 2 seconds
    setInterval(() => {
        cleanupInappropriateTranslations();
    }, 2000);
    
    // Also monitor when DOM changes (new content loaded)
    const observer = new MutationObserver(() => {
        setTimeout(() => {
            cleanupInappropriateTranslations();
        }, 500);
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        characterData: true
    });
}

// Auto-init
if (typeof window.langInitialized === 'undefined') {
    window.langInitialized = true;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLanguage);
    } else {
        initLanguage();
    }
}