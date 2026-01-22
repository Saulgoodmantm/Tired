<?php
/**
 * Homepage - Modern design with Instagram embed
 */

$config = require __DIR__ . '/../../config/app.php';
?>

<section class="hero-modern">
    <!-- Background gradient effect -->
    <div class="hero-gradient"></div>
    
    <!-- Main content -->
    <div class="hero-content">
        <!-- Logo/Brand -->
        <div class="hero-brand animate-fade-in-up">
            <h1 class="brand-name-large">TIRED<span class="brand-accent">OF</span>DOINTM</h1>
            <p class="brand-tagline">Photography & Visual Storytelling</p>
        </div>

        <!-- Instagram Embed Section -->
        <div class="instagram-banner animate-fade-in-up" style="animation-delay: 0.2s;">
            <blockquote class="instagram-media" 
                data-instgrm-permalink="https://www.instagram.com/tiredofdointm/?utm_source=ig_embed&amp;utm_campaign=loading" 
                data-instgrm-version="14" 
                style="background:transparent; border:0; border-radius:16px; margin: 0 auto; max-width:540px; min-width:326px; padding:0; width:100%;">
                <div style="padding:16px;">
                    <a href="https://www.instagram.com/tiredofdointm/" style="background:#12121a; line-height:0; padding:0; text-align:center; text-decoration:none; width:100%; display:block; border-radius:12px;" target="_blank" rel="noopener">
                        <div style="display:flex; flex-direction:row; align-items:center; padding:16px;">
                            <div style="background:linear-gradient(135deg, var(--violet-500), var(--violet-700)); border-radius:50%; height:48px; width:48px; margin-right:14px; display:flex; align-items:center; justify-content:center;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </div>
                            <div style="display:flex; flex-direction:column; flex-grow:1; justify-content:center; text-align:left;">
                                <div style="color:#fff; font-family:var(--font-display); font-size:1.125rem; font-weight:500;">@tiredofdointm</div>
                                <div style="color:var(--text-muted); font-size:0.875rem;">Follow on Instagram</div>
                            </div>
                            <div style="color:var(--violet-400);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15 3 21 3 21 9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>
            </blockquote>
        </div>

        <!-- Action Buttons -->
        <div class="hero-actions animate-fade-in-up" style="animation-delay: 0.3s;">
            <a href="/booking" class="btn btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Book a Session
            </a>
            <a href="/gallery" class="btn btn-secondary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                View Portfolio
            </a>
        </div>

        <!-- Stats -->
        <div class="hero-stats animate-fade-in-up" style="animation-delay: 0.4s;">
            <div class="stat-item">
                <span class="stat-number">500+</span>
                <span class="stat-label">Sessions</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">Satisfaction</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
                <span class="stat-number">24hr</span>
                <span class="stat-label">Turnaround</span>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="scroll-indicator animate-fade-in" style="animation-delay: 1s;">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section">
    <div class="container">
        <h2 class="section-title animate-fade-in-up">Services</h2>
        <div class="services-grid">
            <a href="/gallery/personal" class="service-card animate-fade-in-up" style="animation-delay: 0.1s;">
                <div class="service-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h3>Personal</h3>
                <p>Portraits, headshots, lifestyle</p>
            </a>
            <a href="/gallery/product" class="service-card animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="service-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    </svg>
                </div>
                <h3>Product</h3>
                <p>E-commerce, social media</p>
            </a>
            <a href="/gallery/group" class="service-card animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="service-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3>Group</h3>
                <p>Couples, families, friends</p>
            </a>
            <a href="/gallery/event" class="service-card animate-fade-in-up" style="animation-delay: 0.4s;">
                <div class="service-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <h3>Events</h3>
                <p>Parties, celebrations</p>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content animate-fade-in-up">
            <h2>Ready to create something amazing?</h2>
            <p>Let's capture your vision together.</p>
            <a href="/booking" class="btn btn-primary btn-lg animate-pulse-glow">Book Now</a>
        </div>
    </div>
</section>

<script async src="//www.instagram.com/embed.js"></script>

<style>
/* Hero Modern */
.hero-modern {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
    padding: 120px 24px 80px;
    overflow: hidden;
}

.hero-gradient {
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(ellipse 80% 50% at 50% -20%, rgba(124, 107, 240, 0.15), transparent),
        radial-gradient(ellipse 60% 40% at 80% 100%, rgba(124, 107, 240, 0.1), transparent);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
    max-width: 600px;
    width: 100%;
    text-align: center;
}

.hero-brand {
    margin-bottom: 48px;
}

.brand-name-large {
    font-family: var(--font-display);
    font-size: clamp(2rem, 8vw, 3.5rem);
    font-weight: 300;
    letter-spacing: -0.02em;
    color: #fff;
    margin-bottom: 12px;
}

.brand-accent {
    color: var(--violet-400);
}

.brand-tagline {
    font-size: 1rem;
    color: var(--text-tertiary);
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.instagram-banner {
    margin-bottom: 48px;
}

.instagram-media {
    background: var(--bg-surface) !important;
    border: 1px solid var(--bg-hover) !important;
    border-radius: 16px !important;
    transition: all 0.3s ease;
}

.instagram-media:hover {
    border-color: var(--violet-500) !important;
    box-shadow: var(--glow-violet-subtle);
}

.hero-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 64px;
}

.hero-stats {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 32px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-family: var(--font-display);
    font-size: 1.75rem;
    font-weight: 500;
    color: #fff;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: var(--bg-hover);
}

.scroll-indicator {
    position: absolute;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.scroll-line {
    width: 1px;
    height: 40px;
    background: linear-gradient(to bottom, var(--violet-500), transparent);
    animation: scrollPulse 2s ease-in-out infinite;
}

@keyframes scrollPulse {
    0%, 100% { opacity: 0.3; transform: scaleY(0.5); }
    50% { opacity: 1; transform: scaleY(1); }
}

/* Services Section */
.services-section {
    padding: 120px 0;
    background: var(--bg-surface);
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 64px;
    color: #fff;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
}

.service-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 48px 32px;
    background: var(--bg-base);
    border: 1px solid var(--bg-hover);
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.service-card:hover {
    border-color: var(--violet-500);
    transform: translateY(-4px);
    box-shadow: var(--glow-violet-subtle);
}

.service-icon {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--violet-600), var(--violet-500));
    border-radius: 16px;
    margin-bottom: 24px;
    color: #fff;
}

.service-card h3 {
    font-size: 1.25rem;
    color: #fff;
    margin-bottom: 8px;
}

.service-card p {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

/* CTA Section */
.cta-section {
    padding: 120px 0;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    color: #fff;
    margin-bottom: 16px;
}

.cta-content p {
    font-size: 1.125rem;
    color: var(--text-secondary);
    margin-bottom: 32px;
}

/* Responsive */
@media (max-width: 640px) {
    .hero-modern {
        padding: 100px 16px 60px;
    }
    
    .hero-stats {
        gap: 20px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .hero-actions {
        flex-direction: column;
    }
    
    .hero-actions .btn {
        width: 100%;
    }
}
</style>
