// Enhanced Slideshow Controller for Landing Page

class HeroSlideshow {
    constructor() {
        this.currentSlide = 0;
        this.slides = [];
        this.slideInterval = null;
        this.autoPlayDelay = 2500; // 2.5 seconds
        this.isPlaying = true;
        this.init();
    }

    init() {
        this.createSlideshow();
        this.bindEvents();
        this.startAutoPlay();
        this.preloadImages();
    }

    createSlideshow() {
        const heroSection = document.getElementById('home');
        if (!heroSection) return;

        // Slideshow data with your assets
        this.slides = [
            {
                background: 'slide-1',
                title: 'የኢትዮጵያ ባህላዊ ሥራዎች',
                subtitle: 'Authentic Ethiopian Handcrafts',
                description: 'Discover traditional pottery and weaving from Sebeta Mazoria artisans',
                primaryBtn: { text: 'Shop Now', link: '#catalog', requireAuth: true },
                secondaryBtn: { text: 'Heritage Archive', link: 'views/customer/heritage.php' }
            },
            {
                background: 'slide-2',
                title: 'ሽመና እና የሸክላ ሥራ',
                subtitle: 'Preserve Cultural Heritage',
                description: 'Support Ms. Ashreka and local artisans preserving 15+ years of tradition',
                primaryBtn: { text: 'Join as Artisan', link: 'views/auth/pre_register.php', requireAuth: false },
                secondaryBtn: { text: 'Learn More', link: '#about' }
            },
            {
                background: 'slide-3',
                title: 'አሽረቃ እና ጓደኞቿ',
                subtitle: 'Ashreka & Friends Association',
                description: 'From Sebeta Mazoria workshop to your home - authentic Ethiopian crafts',
                primaryBtn: { text: 'Browse Catalog', link: '#catalog' },
                secondaryBtn: { text: 'Contact Us', link: '#contact' }
            },
            {
                background: 'slide-4',
                title: 'ባህላዊ የሸክላ ሥራ',
                subtitle: 'Traditional Pottery Art',
                description: 'Handcrafted with love, preserving ancient Ethiopian techniques',
                primaryBtn: { text: 'Explore Products', link: '#catalog' },
                secondaryBtn: { text: 'View Heritage', link: 'views/customer/heritage.php' }
            },
            {
                background: 'slide-5',
                title: 'ባህላዊ የሽመና ሥራ',
                subtitle: 'Traditional weaving Art',
                description: 'Handcrafted with love, preserving ancient Ethiopian techniques',
                primaryBtn: { text: 'Shop Now', link: '#catalog', requireAuth: true },
                secondaryBtn: { text: 'View Heritage', link: 'views/customer/heritage.php' }
            }
        ];

        // Create slideshow HTML
        const slideshowHTML = `
            <div class="hero-slideshow">
                <div class="slideshow-loading">
                    <div class="loading-spinner"></div>
                    <div>Loading...</div>
                </div>
                <div class="slideshow-container">
                    ${this.slides.map((slide, index) => `
                        <div class="slide ${slide.background} ${index === 0 ? 'active' : ''}" data-slide="${index}">
                            <div class="slide-content">
                                <h1 class="slide-title">${slide.title}</h1>
                                <h2 class="slide-subtitle">${slide.subtitle}</h2>
                                <p class="slide-description">${slide.description}</p>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <!-- Navigation arrows -->
                <div class="slide-nav prev" onclick="slideshow.prevSlide()">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="slide-nav next" onclick="slideshow.nextSlide()">
                    <i class="fas fa-chevron-right"></i>
                </div>
                
                <!-- Slide indicators -->
                <div class="slide-indicators">
                    ${this.slides.map((_, index) => `
                        <div class="indicator ${index === 0 ? 'active' : ''}" onclick="slideshow.goToSlide(${index})"></div>
                    `).join('')}
                </div>
            </div>
        `;

        heroSection.innerHTML = slideshowHTML;
        
        // Hide loading after a short delay
        setTimeout(() => {
            const loading = document.querySelector('.slideshow-loading');
            if (loading) {
                loading.style.opacity = '0';
                setTimeout(() => loading.remove(), 500);
            }
        }, 1000);
    }

    bindEvents() {
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.prevSlide();
            if (e.key === 'ArrowRight') this.nextSlide();
            if (e.key === ' ') {
                e.preventDefault();
                this.toggleAutoPlay();
            }
        });

        // Touch/swipe support for mobile
        let startX = 0;
        let endX = 0;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        document.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            this.handleSwipe();
        });

        const handleSwipe = () => {
            const threshold = 50;
            const diff = startX - endX;
            
            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }
        };

        this.handleSwipe = handleSwipe;

        // Pause on hover
        const slideshow = document.querySelector('.hero-slideshow');
        if (slideshow) {
            slideshow.addEventListener('mouseenter', () => this.pauseAutoPlay());
            slideshow.addEventListener('mouseleave', () => this.resumeAutoPlay());
        }

        // Visibility API to pause when tab is not active
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseAutoPlay();
            } else {
                this.resumeAutoPlay();
            }
        });
    }

    nextSlide() {
        this.currentSlide = (this.currentSlide + 1) % this.slides.length;
        this.updateSlide();
    }

    prevSlide() {
        this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.updateSlide();
    }

    goToSlide(index) {
        this.currentSlide = index;
        this.updateSlide();
    }

    updateSlide() {
        // Update slide visibility
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.indicator');

        slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === this.currentSlide);
        });

        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === this.currentSlide);
        });

        // Animate content
        this.animateSlideContent();
    }

    animateSlideContent() {
        const activeSlide = document.querySelector('.slide.active .slide-content');
        if (!activeSlide) return;

        const title = activeSlide.querySelector('.slide-title');
        const subtitle = activeSlide.querySelector('.slide-subtitle');
        const description = activeSlide.querySelector('.slide-description');

        // Reset animations
        [title, subtitle, description].forEach(el => {
            if (el) {
                el.style.animation = 'none';
                el.offsetHeight; // Trigger reflow
            }
        });

        // Apply animations with delays
        setTimeout(() => {
            if (title) title.style.animation = 'slideInDown 1s ease-out';
        }, 100);

        setTimeout(() => {
            if (subtitle) subtitle.style.animation = 'slideInUp 1s ease-out';
        }, 300);

        setTimeout(() => {
            if (description) description.style.animation = 'fadeIn 1s ease-out';
        }, 600);
    }

    startAutoPlay() {
        if (this.slideInterval) return;
        
        this.slideInterval = setInterval(() => {
            if (this.isPlaying) {
                this.nextSlide();
            }
        }, this.autoPlayDelay);
    }

    pauseAutoPlay() {
        this.isPlaying = false;
    }

    resumeAutoPlay() {
        this.isPlaying = true;
    }

    toggleAutoPlay() {
        this.isPlaying = !this.isPlaying;
    }

    stopAutoPlay() {
        if (this.slideInterval) {
            clearInterval(this.slideInterval);
            this.slideInterval = null;
        }
        this.isPlaying = false;
    }

    preloadImages() {
        const imageUrls = [
            'assets/images/ethiopian-pottery-hero.jpg.webp',
            'assets/images/ethiopian-weaving-hero.jpg.webp',
            'assets/images/logback.webp',
             'assets/images/people.png',
            'assets/images/weav.png'
        ];

        imageUrls.forEach(url => {
            const img = new Image();
            img.src = url;
        });
    }

    // Public methods for external control
    setAutoPlayDelay(delay) {
        this.autoPlayDelay = delay;
        if (this.slideInterval) {
            this.stopAutoPlay();
            this.startAutoPlay();
        }
    }

    getCurrentSlide() {
        return this.currentSlide;
    }

    getTotalSlides() {
        return this.slides.length;
    }
}

// Initialize slideshow when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the home page
    if (document.getElementById('home')) {
        window.slideshow = new HeroSlideshow();
        
        // Add smooth scrolling for anchor links
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
    }
});

// Handle slide button clicks with authentication check
function handleSlideClick(link, requireAuth) {
    // Shop Now and Join as Artisan buttons require authentication
    if (requireAuth && (link.includes('#catalog') || link.includes('catalog') || link.includes('register_artisan'))) {
        // Check if user is logged in (PHP session check)
        fetch('controllers/AuthController.php?action=check_session')
            .then(response => response.json())
            .then(data => {
                if (data.logged_in) {
                    window.location.href = link;
                } else {
                    showLoginModal();
                }
            })
            .catch(() => {
                // Fallback - show login modal
                showLoginModal();
            });
    } else {
        // All other buttons work without authentication
        if (link.startsWith('#')) {
            // Smooth scroll to section
            const target = document.querySelector(link);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        } else {
            // Navigate to page
            window.location.href = link;
        }
    }
}

// Login modal moved to translate-messages.js

function closeAuthModal() {
    const modal = document.querySelector('.auth-modal-backdrop');
    if (modal) {
        modal.remove();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeroSlideshow;
}