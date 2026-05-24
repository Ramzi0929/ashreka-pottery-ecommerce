<?php
// Page-specific slideshow configurations
$page_slideshows = [
    'catalog' => [
        'slides' => [
            ['bg' => 'slide-1', 'title' => 'የሸክላ ሥራዎች', 'subtitle' => 'Pottery Collection', 'desc' => 'Handcrafted pottery from skilled artisans'],
            ['bg' => 'slide-2', 'title' => 'ባህላዊ ሽመና', 'subtitle' => 'Traditional Weaving', 'desc' => 'Authentic Ethiopian weaving patterns'],
            ['bg' => 'slide-3', 'title' => 'የእጅ ሥራ', 'subtitle' => 'Handmade Crafts', 'desc' => 'Unique handcrafted items for your home']
        ]
    ],
    'heritage' => [
        'slides' => [
            ['bg' => 'slide-2', 'title' => 'የባህል ቅርስ', 'subtitle' => 'Cultural Heritage', 'desc' => 'Preserving Ethiopian traditions'],
            ['bg' => 'slide-4', 'title' => 'ታሪካዊ ሥራዎች', 'subtitle' => 'Historical Crafts', 'desc' => 'Stories behind our ancient techniques'],
            ['bg' => 'slide-1', 'title' => 'የአርቲስት ታሪክ', 'subtitle' => 'Artisan Stories', 'desc' => 'Meet the masters of traditional crafts']
        ]
    ],
    'about' => [
        'slides' => [
            ['bg' => 'slide-3', 'title' => 'አሽረቃ እና ጓደኞቿ', 'subtitle' => 'Ashreka & Friends', 'desc' => 'Our mission and vision'],
            ['bg' => 'slide-1', 'title' => 'የሰበታ ማዞሪያ', 'subtitle' => 'Sebeta Mazoria', 'desc' => 'Our workshop and community'],
            ['bg' => 'slide-2', 'title' => 'የባህል ጥበቃ', 'subtitle' => 'Cultural Preservation', 'desc' => 'Keeping traditions alive for future generations']
        ]
    ]
];

// Get current page from URL
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$slides = $page_slideshows[$current_page] ?? $page_slideshows['catalog'];
?>

<!-- Page Header Slideshow -->
<section class="page-slideshow mb-4">
    <div class="slideshow-container-mini">
        <?php foreach ($slides['slides'] as $index => $slide): ?>
        <div class="slide-mini <?= $slide['bg'] ?> <?= $index === 0 ? 'active' : '' ?>">
            <div class="slide-content-mini">
                <h2><?= $slide['title'] ?></h2>
                <h3><?= $slide['subtitle'] ?></h3>
                <p><?= $slide['desc'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="slide-indicators-mini">
        <?php foreach ($slides['slides'] as $index => $slide): ?>
        <div class="indicator-mini <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlideMini(<?= $index ?>)"></div>
        <?php endforeach; ?>
    </div>
</section>

<style>
.page-slideshow {
    position: relative;
    height: 300px;
    overflow: hidden;
    border-radius: 15px;
    margin-top: 20px;
}

.slideshow-container-mini {
    position: relative;
    width: 100%;
    height: 100%;
}

.slide-mini {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1.5s ease-in-out;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slide-mini.active {
    opacity: 1;
}

.slide-content-mini {
    text-align: center;
    color: white;
    background: rgba(0,0,0,0.6);
    padding: 2rem;
    border-radius: 15px;
    backdrop-filter: blur(10px);
}

.slide-content-mini h2 {
    font-size: 2rem;
    color: #FFCD00;
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
}

.slide-content-mini h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}

.slide-content-mini p {
    font-size: 1.1rem;
    color: #FFF8DC;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}

.slide-indicators-mini {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
}

.indicator-mini {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: all 0.3s ease;
}

.indicator-mini.active {
    background: #FFCD00;
    transform: scale(1.2);
}

@media (max-width: 768px) {
    .page-slideshow {
        height: 200px;
    }
    
    .slide-content-mini {
        padding: 1rem;
    }
    
    .slide-content-mini h2 {
        font-size: 1.5rem;
    }
    
    .slide-content-mini h3 {
        font-size: 1.2rem;
    }
    
    .slide-content-mini p {
        font-size: 1rem;
    }
}
</style>

<script>
let currentSlideMini = 0;
const slidesMini = document.querySelectorAll('.slide-mini');
const indicatorsMini = document.querySelectorAll('.indicator-mini');

function goToSlideMini(index) {
    slidesMini[currentSlideMini].classList.remove('active');
    indicatorsMini[currentSlideMini].classList.remove('active');
    
    currentSlideMini = index;
    
    slidesMini[currentSlideMini].classList.add('active');
    indicatorsMini[currentSlideMini].classList.add('active');
}

// Auto-advance slides
setInterval(() => {
    const nextSlide = (currentSlideMini + 1) % slidesMini.length;
    goToSlideMini(nextSlide);
}, 4000);
</script>