/**
 * =============================================================================
 * TIREDOFDOINTM - Main Application JavaScript
 * =============================================================================
 */

// App namespace
window.TODT = window.TODT || {};

/**
 * Initialize the application
 */
document.addEventListener('DOMContentLoaded', () => {
    TODT.init();
});

/**
 * Main initialization
 */
TODT.init = function() {
    console.log('TiredOfDoinTM initialized');

    // Initialize modules
    TODT.Menu.init();
    TODT.Slideshow.init();
    TODT.Gallery.init();
};

// =============================================================================
// MENU MODULE
// =============================================================================
TODT.Menu = {
    isOpen: false,
    menuBtn: null,
    menuOverlay: null,

    init() {
        this.menuBtn = document.querySelector('.nav-menu-btn');
        this.menuOverlay = document.querySelector('.menu-overlay');

        if (this.menuBtn) {
            this.menuBtn.addEventListener('click', () => this.toggle());
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Handle submenu toggles
        document.querySelectorAll('.menu-item.has-submenu').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                item.classList.toggle('open');
            });
        });
    },

    toggle() {
        this.isOpen ? this.close() : this.open();
    },

    open() {
        this.isOpen = true;
        this.menuBtn?.classList.add('open');
        this.menuOverlay?.classList.add('open');
        document.body.style.overflow = 'hidden';
    },

    close() {
        this.isOpen = false;
        this.menuBtn?.classList.remove('open');
        this.menuOverlay?.classList.remove('open');
        document.body.style.overflow = '';
    }
};

// =============================================================================
// SLIDESHOW MODULE (GTA V Style)
// =============================================================================
TODT.Slideshow = {
    container: null,
    slides: [],
    dots: [],
    currentIndex: 0,
    interval: null,
    intervalTime: 6000,
    isAnimating: false,

    init() {
        this.container = document.querySelector('.slideshow');
        if (!this.container) return;

        this.slides = Array.from(this.container.querySelectorAll('.slideshow-slide'));
        this.dots = Array.from(this.container.querySelectorAll('.slideshow-dot'));

        if (this.slides.length === 0) return;

        // Get config from data attributes
        this.intervalTime = parseInt(this.container.dataset.interval) || 6000;

        // Set up navigation
        const prevBtn = this.container.querySelector('.slideshow-nav.prev');
        const nextBtn = this.container.querySelector('.slideshow-nav.next');

        prevBtn?.addEventListener('click', () => this.prev());
        nextBtn?.addEventListener('click', () => this.next());

        // Set up dots
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goTo(index));
        });

        // Start autoplay
        this.startAutoplay();

        // Pause on hover
        this.container.addEventListener('mouseenter', () => this.stopAutoplay());
        this.container.addEventListener('mouseleave', () => this.startAutoplay());

        // Set initial state
        this.showSlide(0);
    },

    showSlide(index, direction = 'right') {
        if (this.isAnimating || index === this.currentIndex) return;
        this.isAnimating = true;

        const currentSlide = this.slides[this.currentIndex];
        const nextSlide = this.slides[index];

        // Remove all classes
        this.slides.forEach(slide => {
            slide.classList.remove('active', 'entering-left', 'entering-right');
        });

        // Add entering animation class
        nextSlide.classList.add(`entering-${direction}`, 'active');

        // Update dots
        this.dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });

        this.currentIndex = index;

        // Reset animation lock after transition
        setTimeout(() => {
            this.isAnimating = false;
        }, 400);
    },

    next() {
        const nextIndex = (this.currentIndex + 1) % this.slides.length;
        this.showSlide(nextIndex, 'right');
    },

    prev() {
        const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
        this.showSlide(prevIndex, 'left');
    },

    goTo(index) {
        const direction = index > this.currentIndex ? 'right' : 'left';
        this.showSlide(index, direction);
    },

    startAutoplay() {
        this.stopAutoplay();
        this.interval = setInterval(() => this.next(), this.intervalTime);
    },

    stopAutoplay() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
};

// =============================================================================
// GALLERY MODULE
// =============================================================================
TODT.Gallery = {
    lightbox: null,
    lightboxImg: null,
    currentImages: [],
    currentIndex: 0,

    init() {
        // Set up lightbox
        this.lightbox = document.querySelector('.lightbox');
        this.lightboxImg = this.lightbox?.querySelector('img');

        if (this.lightbox) {
            // Close on click outside
            this.lightbox.addEventListener('click', (e) => {
                if (e.target === this.lightbox) {
                    this.closeLightbox();
                }
            });

            // Close button
            this.lightbox.querySelector('.lightbox-close')?.addEventListener('click', () => {
                this.closeLightbox();
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!this.lightbox.classList.contains('open')) return;

                if (e.key === 'Escape') this.closeLightbox();
                if (e.key === 'ArrowRight') this.nextImage();
                if (e.key === 'ArrowLeft') this.prevImage();
            });
        }

        // Set up gallery items
        document.querySelectorAll('.gallery-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.openLightbox(item, index);
            });
        });
    },

    openLightbox(item, index) {
        if (!this.lightbox) return;

        // Collect all images in the same gallery
        const gallery = item.closest('.gallery-grid');
        this.currentImages = Array.from(gallery?.querySelectorAll('.gallery-item img') || []);
        this.currentIndex = index;

        const img = item.querySelector('img');
        if (img && this.lightboxImg) {
            this.lightboxImg.src = img.dataset.full || img.src;
        }

        this.lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    },

    closeLightbox() {
        this.lightbox?.classList.remove('open');
        document.body.style.overflow = '';
    },

    nextImage() {
        if (this.currentImages.length === 0) return;
        this.currentIndex = (this.currentIndex + 1) % this.currentImages.length;
        this.updateLightboxImage();
    },

    prevImage() {
        if (this.currentImages.length === 0) return;
        this.currentIndex = (this.currentIndex - 1 + this.currentImages.length) % this.currentImages.length;
        this.updateLightboxImage();
    },

    updateLightboxImage() {
        const img = this.currentImages[this.currentIndex];
        if (img && this.lightboxImg) {
            this.lightboxImg.src = img.dataset.full || img.src;
        }
    }
};

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================
TODT.utils = {
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Fetch with JSON
     */
    async fetchJSON(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            ...options,
        });
        return response.json();
    },

    /**
     * POST request
     */
    async post(url, data) {
        return this.fetchJSON(url, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    },

    /**
     * Show notification
     */
    notify(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;

        // Add to DOM
        document.body.appendChild(notification);

        // Animate in
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
};
