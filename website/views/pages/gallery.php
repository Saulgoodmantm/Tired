<?php
/**
 * Gallery Page - Shows images by category
 */

$category = $category ?? 'all';
$categories = ['all', 'personal', 'product', 'group', 'event', 'misc'];
?>

<section class="container" style="padding-top: 100px;">
    <!-- Category Tabs -->
    <div class="gallery-tabs flex gap-md mb-xl" style="flex-wrap: wrap;">
        <?php foreach ($categories as $cat): ?>
            <a href="/gallery/<?= $cat === 'all' ? '' : $cat ?>"
               class="btn <?= $category === $cat ? 'btn-primary' : 'btn-ghost' ?>">
                <?= ucfirst($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Gallery Header -->
    <div class="flex justify-between items-center mb-lg">
        <h1 class="text-2xl font-display">
            <?= $category === 'all' ? 'All Work' : ucfirst($category) . ' Photography' ?>
        </h1>
        <?php if (!empty($galleries)): ?>
            <span class="text-muted"><?= count($images ?? []) ?> photos</span>
        <?php endif; ?>
    </div>

    <!-- Gallery Grid -->
    <?php if (!empty($images)): ?>
        <div class="gallery-grid">
            <?php foreach ($images as $image): ?>
                <div class="gallery-item">
                    <img
                        src="<?= htmlspecialchars($image['thumb_url'] ?? $image['url']) ?>"
                        data-full="<?= htmlspecialchars($image['full_url'] ?? $image['url']) ?>"
                        alt="<?= htmlspecialchars($image['caption'] ?? 'Photo') ?>"
                        loading="lazy"
                    >
                    <div class="gallery-item-overlay">
                        <span class="text-sm"><?= htmlspecialchars($image['caption'] ?? '') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More -->
        <?php if ($hasMore ?? false): ?>
            <div class="text-center mt-xl">
                <button class="btn btn-secondary" id="load-more" data-page="<?= ($currentPage ?? 1) + 1 ?>">
                    Load More
                </button>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center" style="padding: 4rem 0;">
            <p class="text-secondary text-lg mb-md">No photos yet in this category.</p>
            <a href="/booking" class="btn btn-primary">Book a Shoot</a>
        </div>
    <?php endif; ?>
</section>

<script>
// Load more functionality
document.getElementById('load-more')?.addEventListener('click', async function() {
    const btn = this;
    const page = parseInt(btn.dataset.page);
    const category = '<?= htmlspecialchars($category) ?>';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Loading...';

    try {
        const response = await fetch(`/api/gallery?category=${category}&page=${page}`);
        const data = await response.json();

        if (data.images && data.images.length > 0) {
            const grid = document.querySelector('.gallery-grid');

            data.images.forEach(image => {
                const item = document.createElement('div');
                item.className = 'gallery-item animate-fade-in';
                item.innerHTML = `
                    <img src="${image.thumb_url || image.url}"
                         data-full="${image.full_url || image.url}"
                         alt="${image.caption || 'Photo'}"
                         loading="lazy">
                    <div class="gallery-item-overlay">
                        <span class="text-sm">${image.caption || ''}</span>
                    </div>
                `;
                grid.appendChild(item);
            });

            // Update page number
            btn.dataset.page = page + 1;
            btn.disabled = false;
            btn.innerHTML = 'Load More';

            // Hide if no more
            if (!data.hasMore) {
                btn.style.display = 'none';
            }

            // Re-init gallery clicks
            TODT.Gallery.init();
        }
    } catch (error) {
        console.error('Failed to load more:', error);
        btn.disabled = false;
        btn.innerHTML = 'Load More';
    }
});
</script>
