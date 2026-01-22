<?php
/**
 * Rates/Pricing Page
 */

$pricing = [
    'personal' => [
        'title' => 'Personal Photography',
        'description' => 'Portraits, headshots, lifestyle photos',
        'packages' => [
            ['hours' => 2, 'price' => 200, 'photos' => '50+', 'locations' => 'Single', 'features' => ['Retouching', 'Online Gallery']],
            ['hours' => 4, 'price' => 375, 'photos' => '125+', 'locations' => '1-2', 'features' => ['Full Retouching', 'Online Gallery']],
            ['hours' => 6, 'price' => 525, 'photos' => '275+', 'locations' => 'Unlimited', 'features' => ['Full Retouching', 'Consultation', 'Online Gallery']],
        ],
    ],
    'product' => [
        'title' => 'Product Photography',
        'description' => 'E-commerce, social media, marketing',
        'packages' => [
            ['hours' => 2, 'price' => 250, 'photos' => '30+', 'locations' => 'Studio', 'features' => ['White Background', 'Basic Editing']],
            ['hours' => 4, 'price' => 450, 'photos' => '75+', 'locations' => 'Studio', 'features' => ['Multiple Angles', 'Full Editing', 'Lifestyle Shots']],
            ['hours' => 6, 'price' => 625, 'photos' => '150+', 'locations' => 'Studio + Location', 'features' => ['Full Production', 'Lifestyle', 'Flat Lays']],
        ],
    ],
];
?>

<section class="container" style="padding-top: 100px; padding-bottom: 4rem;">
    <div class="text-center mb-xl">
        <h1 class="text-2xl mb-md">Pricing</h1>
        <p class="text-secondary">Transparent pricing for all photography services</p>
    </div>

    <?php foreach ($pricing as $key => $category): ?>
    <div class="pricing-category mb-xl">
        <h2 class="text-xl mb-sm"><?= htmlspecialchars($category['title']) ?></h2>
        <p class="text-muted mb-lg"><?= htmlspecialchars($category['description']) ?></p>

        <div class="pricing-grid">
            <?php foreach ($category['packages'] as $index => $pkg): ?>
            <div class="card pricing-card <?= $index === 1 ? 'featured' : '' ?>">
                <?php if ($index === 1): ?>
                    <div class="pricing-badge">Most Popular</div>
                <?php endif; ?>

                <div class="pricing-header">
                    <span class="pricing-hours"><?= $pkg['hours'] ?> Hours</span>
                    <span class="pricing-amount">$<?= $pkg['price'] ?></span>
                    <span class="pricing-rate">$<?= number_format($pkg['price'] / $pkg['hours'], 2) ?>/hr</span>
                </div>

                <ul class="pricing-features">
                    <li><?= $pkg['photos'] ?> Edited Photos</li>
                    <li><?= $pkg['locations'] ?> Location<?= $pkg['locations'] !== 'Single' && $pkg['locations'] !== 'Studio' ? 's' : '' ?></li>
                    <?php foreach ($pkg['features'] as $feature): ?>
                        <li><?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                </ul>

                <a href="/booking?type=<?= $key ?>&hours=<?= $pkg['hours'] ?>" class="btn <?= $index === 1 ? 'btn-primary' : 'btn-secondary' ?>" style="width: 100%;">
                    Book Now
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Add-ons Section -->
    <div class="card mt-xl">
        <h3 class="mb-lg">Add-ons</h3>
        <div class="addons-list">
            <div class="addon-row">
                <span>Model Hiring</span>
                <span class="text-violet">$25 - $150</span>
            </div>
            <div class="addon-row">
                <span>Rush Editing (48hr delivery)</span>
                <span class="text-violet">+50%</span>
            </div>
            <div class="addon-row">
                <span>Extra Location</span>
                <span class="text-violet">$50/each</span>
            </div>
            <div class="addon-row">
                <span>Travel Fee (outside 30 miles)</span>
                <span class="text-violet">$0.50/mile</span>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div class="mt-xl">
        <h3 class="text-center mb-lg">Frequently Asked Questions</h3>
        <div class="faq-list">
            <details class="faq-item">
                <summary>What's included in the deposit?</summary>
                <p>A 50% deposit is required to secure your booking date. The remaining balance is due before or on the day of your session.</p>
            </details>
            <details class="faq-item">
                <summary>How long until I receive my photos?</summary>
                <p>Standard delivery is 2-3 weeks. Rush editing (48 hours) is available for an additional 50% fee.</p>
            </details>
            <details class="faq-item">
                <summary>Can I reschedule my session?</summary>
                <p>Yes! You can reschedule up to 48 hours before your session at no additional cost.</p>
            </details>
            <details class="faq-item">
                <summary>Do you travel for shoots?</summary>
                <p>Absolutely! Travel within 30 miles is included. Beyond that, a travel fee of $0.50/mile applies.</p>
            </details>
        </div>
    </div>
</section>

<style>
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-lg);
}

.pricing-card {
    position: relative;
    text-align: center;
}

.pricing-card.featured {
    border-color: var(--violet-500);
    box-shadow: var(--glow-violet-medium);
}

.pricing-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--violet-500);
    color: white;
    padding: 4px 16px;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 500;
}

.pricing-header {
    padding-bottom: var(--space-lg);
    border-bottom: 1px solid var(--bg-hover);
    margin-bottom: var(--space-lg);
}

.pricing-hours {
    display: block;
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: var(--space-sm);
}

.pricing-amount {
    display: block;
    font-size: 2.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.pricing-rate {
    display: block;
    font-size: 0.75rem;
    color: var(--text-tertiary);
    margin-top: var(--space-xs);
}

.pricing-features {
    list-style: none;
    text-align: left;
    margin-bottom: var(--space-xl);
}

.pricing-features li {
    padding: var(--space-sm) 0;
    color: var(--text-secondary);
    position: relative;
    padding-left: var(--space-xl);
}

.pricing-features li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--success);
}

.addons-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.addon-row {
    display: flex;
    justify-content: space-between;
    padding: var(--space-md) 0;
    border-bottom: 1px solid var(--bg-hover);
}

.addon-row:last-child {
    border-bottom: none;
}

.faq-list {
    max-width: 700px;
    margin: 0 auto;
}

.faq-item {
    background: var(--bg-surface);
    border: 1px solid var(--bg-hover);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-md);
    overflow: hidden;
}

.faq-item summary {
    padding: var(--space-lg);
    cursor: pointer;
    font-weight: 500;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.faq-item summary::after {
    content: '+';
    font-size: 1.25rem;
    color: var(--violet-400);
}

.faq-item[open] summary::after {
    content: '−';
}

.faq-item p {
    padding: 0 var(--space-lg) var(--space-lg);
    color: var(--text-secondary);
}
</style>
