<?php
/**
 * Homepage - Hero + Slideshow
 */

// Get config values
$config = require __DIR__ . '/../../config/app.php';
$profileImage = $config['profile']['image'] ?? '/assets/images/profile.jpg';
$showcaseInterval = $config['showcase']['interval'] ?? 6000;
?>

<section class="hero">
    <!-- Profile Section -->
    <div class="hero-profile animate-fade-in-up">
        <img
            src="<?= htmlspecialchars($profileImage) ?>"
            alt="TiredOfDoinTM"
            class="hero-avatar"
        >
        <h1 class="hero-name brand-name">TiredOfDoinTM</h1>
        <p class="subtitle">Photographer</p>
    </div>

    <!-- Action Buttons (ABOVE slideshow per spec) -->
    <div class="hero-buttons animate-fade-in-up" style="animation-delay: 0.1s">
        <a href="/calendar" class="btn btn-secondary">View Schedule</a>
        <a href="/gallery" class="btn btn-primary animate-pulse-glow">View Portfolio</a>
        <a href="/booking" class="btn btn-secondary">Book Now</a>
    </div>

    <!-- Hero Slideshow (GTA V Style) -->
    <div class="slideshow" data-interval="<?= $showcaseInterval ?>">
        <?php if (!empty($pinnedImages)): ?>
            <?php foreach ($pinnedImages as $index => $image): ?>
                <div class="slideshow-slide <?= $index === 0 ? 'active' : '' ?>"
                     data-direction="<?= $image['entry_direction'] ?? 'right' ?>">
                    <img
                        src="<?= htmlspecialchars($image['url']) ?>"
                        alt="<?= htmlspecialchars($image['caption'] ?? 'Photography by TiredOfDoinTM') ?>"
                        loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                    >
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Placeholder slides for demo -->
            <div class="slideshow-slide active">
                <img src="https://picsum.photos/1200/750?random=1" alt="Sample 1">
            </div>
            <div class="slideshow-slide">
                <img src="https://picsum.photos/1200/750?random=2" alt="Sample 2">
            </div>
            <div class="slideshow-slide">
                <img src="https://picsum.photos/1200/750?random=3" alt="Sample 3">
            </div>
        <?php endif; ?>

        <!-- Navigation Arrows -->
        <button class="slideshow-nav prev" aria-label="Previous slide">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        <button class="slideshow-nav next" aria-label="Next slide">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>

        <!-- Dot Indicators -->
        <div class="slideshow-dots">
            <?php
            $slideCount = !empty($pinnedImages) ? count($pinnedImages) : 3;
            for ($i = 0; $i < $slideCount; $i++):
            ?>
                <button class="slideshow-dot <?= $i === 0 ? 'active' : '' ?>"
                        aria-label="Go to slide <?= $i + 1 ?>"></button>
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php if (!empty($testimonials)): ?>
<!-- Testimonials Section -->
<section class="testimonials container mt-xl">
    <h2 class="text-center mb-lg">What Clients Say</h2>
    <div class="testimonials-carousel">
        <?php foreach ($testimonials as $testimonial): ?>
            <div class="card">
                <p class="text-secondary">"<?= htmlspecialchars($testimonial['content']) ?>"</p>
                <p class="text-sm text-muted mt-md">— <?= htmlspecialchars($testimonial['client_name']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
