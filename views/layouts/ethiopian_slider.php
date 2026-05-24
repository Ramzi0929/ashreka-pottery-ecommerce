<!-- Ethiopian Cultural Slider Component -->
<div id="ethiopianSlider" class="ethiopian-slider">
    <div class="slider-container">
        <div class="slide active" data-bg="slide1">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h2 class="slide-title">የኢትዮጵያ ባህላዊ ሥራዎች</h2>
                <p class="slide-subtitle">Traditional Ethiopian Craftsmanship</p>
            </div>
        </div>
        
        <div class="slide" data-bg="slide2">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h2 class="slide-title">ሽመና እና የሸክላ ሥራ</h2>
                <p class="slide-subtitle">Pottery & Weaving Heritage</p>
            </div>
        </div>
        
        <div class="slide" data-bg="slide3">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h2 class="slide-title">አሽረቃ እና ጓደኞቿ</h2>
                <p class="slide-subtitle">Ashreka & Friends Association</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Dots -->
    <div class="slider-dots">
        <span class="dot active" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
        <span class="dot" onclick="currentSlide(3)"></span>
    </div>
    
    <!-- Navigation Arrows -->
    <button class="slider-arrow prev" onclick="changeSlide(-1)">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button class="slider-arrow next" onclick="changeSlide(1)">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>

<style>
.ethiopian-slider {
    position: relative;
    width: 100%;
    height: 400px;
    overflow: hidden;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(139, 69, 19, 0.3);
    margin: 20px 0;
}

.ethiopian-slider::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    height: 100px;
    background: linear-gradient(to bottom, 
                rgba(255, 255, 255, 0.1) 0%, 
                rgba(255, 255, 255, 0.05) 30%, 
                transparent 100%);
    transform: scaleY(-1);
    opacity: 0.6;
    pointer-events: none;
    border-radius: 0 0 15px 15px;
}

.slider-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.slide::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    height: 100px;
    background: inherit;
    background-size: cover;
    background-position: center bottom;
    transform: scaleY(-1);
    opacity: 0.3;
    mask: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, transparent 100%);
    -webkit-mask: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, transparent 100%);
    pointer-events: none;
}

.slide.active {
    opacity: 1;
}

/* Ethiopian Cultural Backgrounds */
.slide[data-bg="slide1"] {
    background-image: linear-gradient(135deg, rgba(139, 69, 19, 0.4), rgba(210, 105, 30, 0.4)), 
                      url('../../assets/images/ethiopian-pottery-hero.jpg.webp');
}

.slide[data-bg="slide2"] {
    background-image: linear-gradient(135deg, rgba(34, 139, 34, 0.4), rgba(255, 215, 0, 0.4)), 
                      url('../../assets/images/ethiopian-weaving-hero.jpg.webp');
}

.slide[data-bg="slide3"] {
    background-image: linear-gradient(135deg, rgba(220, 20, 60, 0.4), rgba(255, 140, 0, 0.4)), 
                      url('../../assets/images/sebeta-workshop.jpg.webp');
}

.slide-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(0, 0, 0, 0.3), rgba(139, 69, 19, 0.2));
}

.slide-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: white;
    z-index: 2;
}

.slide-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
    animation: slideInUp 1s ease-out;
}

.slide-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
    animation: slideInUp 1s ease-out 0.3s both;
}

.slider-dots {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 3;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
}

.dot.active,
.dot:hover {
    background: #ffc107;
    transform: scale(1.2);
}

.slider-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(139, 69, 19, 0.8);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s ease;
    z-index: 3;
}

.slider-arrow:hover {
    background: rgba(139, 69, 19, 1);
    transform: translateY(-50%) scale(1.1);
}

.slider-arrow.prev {
    left: 20px;
}

.slider-arrow.next {
    right: 20px;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .ethiopian-slider {
        height: 300px;
    }
    
    .slide-title {
        font-size: 1.8rem;
    }
    
    .slide-subtitle {
        font-size: 1rem;
    }
    
    .slider-arrow {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }
}
</style>

<script>
let currentSlideIndex = 0;
const slides = document.querySelectorAll('#ethiopianSlider .slide');
const dots = document.querySelectorAll('#ethiopianSlider .dot');
const totalSlides = slides.length;

function showSlide(index) {
    // Remove active class from all slides and dots
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Add active class to current slide and dot
    slides[index].classList.add('active');
    dots[index].classList.add('active');
}

function changeSlide(direction) {
    currentSlideIndex += direction;
    
    if (currentSlideIndex >= totalSlides) {
        currentSlideIndex = 0;
    } else if (currentSlideIndex < 0) {
        currentSlideIndex = totalSlides - 1;
    }
    
    showSlide(currentSlideIndex);
}

function currentSlide(index) {
    currentSlideIndex = index - 1;
    showSlide(currentSlideIndex);
}

// Auto-slide every 5 seconds
setInterval(() => {
    changeSlide(1);
}, 5000);
</script>