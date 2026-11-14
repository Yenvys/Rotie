/**
 * ====================================================================
 * JAVASCRIPT: Animasi Sinematik GSAP, ScrollTrigger & Scene Transitions
 * ROTIÉ — Artisan Bakery
 * UAS Pemrograman Web Dasar - STMIK IKMI Cirebon
 * ====================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    initGSAPAnimations();
    initPromoCountdown();
    initTestimonialSlider();
});

/**
 * Inisialisasi GSAP Timelines & ScrollTrigger
 */
function initGSAPAnimations() {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || typeof gsap === 'undefined') {
        return;
    }

    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    }

    // 1. Animasi Masuk Hero Section (Stagger Reveal)
    const heroTl = gsap.timeline({ defaults: { ease: 'power3.out', duration: 1 } });
    
    heroTl.from('.hero-badge', {
        y: -24,
        opacity: 0,
        duration: 0.8
    })
    .from('.hero-headline span', {
        y: 60,
        opacity: 0,
        stagger: 0.16,
        duration: 1.15
    }, '-=0.4')
    .from('.hero-subheadline', {
        y: 28,
        opacity: 0,
        duration: 0.85
    }, '-=0.55')
    .from('.hero-cta-group', {
        y: 20,
        opacity: 0,
        duration: 0.75
    }, '-=0.45')
    .from('.hero-image-container', {
        scale: 0.9,
        opacity: 0,
        duration: 1.25,
        ease: 'power2.out'
    }, '-=0.9');

    // 2. Continuous Parallax & Morph Scrubbing dari Hero ke Story
    if (typeof ScrollTrigger !== 'undefined') {
        
        // Parallax image hero yang bergerak berkesinambungan saat digulir
        gsap.to('.hero-parallax-img', {
            y: 90,
            scale: 1.04,
            ease: 'none',
            scrollTrigger: {
                trigger: '#hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1.3
            }
        });

        // Floating badges parallax depth berlawanan arah
        gsap.to('.animate-float', {
            y: -50,
            scrollTrigger: {
                trigger: '#hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1.5
            }
        });

        gsap.to('.animate-float-reverse', {
            y: 40,
            scrollTrigger: {
                trigger: '#hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1.5
            }
        });

        // 3. Reveal Brand Story Section dengan Mask Expansion
        gsap.from('.story-image-reveal', {
            scrollTrigger: {
                trigger: '#story',
                start: 'top 75%',
                toggleActions: 'play none none reverse'
            },
            clipPath: 'inset(12% 12% 12% 12%)',
            scale: 0.94,
            opacity: 0.4,
            duration: 1.3,
            ease: 'power3.out'
        });

        gsap.from('.story-text-stagger > *', {
            scrollTrigger: {
                trigger: '#story',
                start: 'top 70%',
                toggleActions: 'play none none reverse'
            },
            y: 40,
            opacity: 0,
            stagger: 0.18,
            duration: 0.9,
            ease: 'power2.out'
        });

        // 4. Stagger Process Cards Reveal
        gsap.from('.process-card', {
            scrollTrigger: {
                trigger: '#process',
                start: 'top 75%',
                toggleActions: 'play none none reverse'
            },
            y: 55,
            opacity: 0,
            stagger: 0.16,
            duration: 0.85,
            ease: 'power3.out'
        });

        // 5. Signature Products Grid Entrance
        gsap.from('.signature-card', {
            scrollTrigger: {
                trigger: '#signature',
                start: 'top 75%',
                toggleActions: 'play none none reverse'
            },
            y: 45,
            opacity: 0,
            stagger: 0.18,
            duration: 0.95,
            ease: 'power2.out'
        });
    }
}

/**
 * Hitung Mundur Promo Spesial (JavaScript Real-time Countdown)
 */
function initPromoCountdown() {
    const hoursEl = document.getElementById('promo-hours');
    const minutesEl = document.getElementById('promo-minutes');
    const secondsEl = document.getElementById('promo-seconds');

    if (!hoursEl || !minutesEl || !secondsEl) return;

    let targetTime = new Date();
    targetTime.setHours(23, 59, 59, 999);

    function updateCountdown() {
        const now = new Date().getTime();
        let diff = targetTime.getTime() - now;

        if (diff <= 0) {
            targetTime = new Date(now + 24 * 60 * 60 * 1000);
            diff = targetTime.getTime() - now;
        }

        const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
        const minutes = Math.floor((diff / (1000 * 60)) % 60);
        const seconds = Math.floor((diff / 1000) % 60);

        hoursEl.textContent = String(hours).padStart(2, '0');
        minutesEl.textContent = String(minutes).padStart(2, '0');
        secondsEl.textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}

/**
 * Slider Testimonial Interaktif
 */
function initTestimonialSlider() {
    const slides = document.querySelectorAll('.testimonial-slide');
    const prevBtn = document.getElementById('testi-prev');
    const nextBtn = document.getElementById('testi-next');
    const dotsContainer = document.getElementById('testi-dots');

    if (!slides.length) return;

    let currentIndex = 0;
    const totalSlides = slides.length;

    if (dotsContainer) {
        dotsContainer.innerHTML = '';
        slides.forEach((_, idx) => {
            const dot = document.createElement('button');
            dot.className = `h-2.5 rounded-full transition-all duration-300 ${idx === 0 ? 'bg-rotie-caramel w-7' : 'bg-rotie-beige w-2.5 hover:bg-rotie-caramel/50'}`;
            dot.setAttribute('aria-label', `Slide ${idx + 1}`);
            dot.addEventListener('click', () => goToSlide(idx));
            dotsContainer.appendChild(dot);
        });
    }

    function updateSlideVisibility() {
        slides.forEach((slide, idx) => {
            if (idx === currentIndex) {
                slide.classList.remove('hidden', 'opacity-0', 'translate-x-6');
                slide.classList.add('opacity-100', 'translate-x-0');
            } else {
                slide.classList.add('hidden', 'opacity-0', 'translate-x-6');
                slide.classList.remove('opacity-100', 'translate-x-0');
            }
        });

        if (dotsContainer) {
            const dots = dotsContainer.children;
            for (let i = 0; i < dots.length; i++) {
                if (i === currentIndex) {
                    dots[i].className = 'w-7 h-2.5 rounded-full bg-rotie-caramel transition-all duration-300';
                } else {
                    dots[i].className = 'w-2.5 h-2.5 rounded-full bg-rotie-beige hover:bg-rotie-caramel/50 transition-all duration-300';
                }
            }
        }
    }

    function goToSlide(idx) {
        currentIndex = (idx + totalSlides) % totalSlides;
        updateSlideVisibility();
    }

    prevBtn?.addEventListener('click', () => goToSlide(currentIndex - 1));
    nextBtn?.addEventListener('click', () => goToSlide(currentIndex + 1));

    let autoInterval = setInterval(() => goToSlide(currentIndex + 1), 7000);

    const sliderWrapper = document.getElementById('testimonial-wrapper');
    sliderWrapper?.addEventListener('mouseenter', () => clearInterval(autoInterval));
    sliderWrapper?.addEventListener('mouseleave', () => {
        clearInterval(autoInterval);
        autoInterval = setInterval(() => goToSlide(currentIndex + 1), 7000);
    });

    updateSlideVisibility();
}
