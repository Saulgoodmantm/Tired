<?php
/**
 * Dashboard - Gallery Management
 */
$activePage = 'galleries';
$pageTitle = 'Galleries';

use App\Utils\Database;
use App\Services\R2Service;

// Get galleries
$galleries = Database::query(
    "SELECT g.*, 
            (SELECT COUNT(*) FROM images WHERE gallery_id = g.id) as image_count,
            (SELECT url FROM images WHERE gallery_id = g.id AND is_cover = true LIMIT 1) as cover_image
     FROM galleries g 
     ORDER BY g.created_at DESC 
     LIMIT 100"
);

// Stats
$totalGalleries = count($galleries);
$totalImages = Database::queryValue("SELECT COUNT(*) FROM images");
$totalViews = Database::queryValue("SELECT COALESCE(SUM(view_count), 0) FROM galleries");
?>

<!-- Gallery Stats -->
<div class="stats-grid mb-xl">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(124, 107, 240, 0.1); color: var(--violet-400);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Galleries</span>
            <span class="stat-value"><?= $totalGalleries ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Images</span>
            <span class="stat-value"><?= $totalImages ?? 0 ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(96, 165, 250, 0.1); color: var(--info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Views</span>
            <span class="stat-value"><?= number_format($totalViews ?? 0) ?></span>
        </div>
    </div>
</div>

<!-- Actions Bar -->
<div class="card dashboard-card mb-lg">
    <div class="card-body">
        <div class="flex items-center justify-between gap-lg">
            <div class="flex items-center gap-md">
                <select id="visibility-filter" class="form-select">
                    <option value="">All Visibility</option>
                    <option value="public">Public</option>
                    <option value="unlisted">Unlisted</option>
                    <option value="private">Private</option>
                </select>
            </div>

            <a href="/dashboard/galleries/new" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                New Gallery
            </a>
        </div>
    </div>
</div>

<!-- Galleries Grid -->
<div class="galleries-grid">
    <?php if (empty($galleries)): ?>
        <div class="empty-state" style="grid-column: 1 / -1;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
            </svg>
            <h3>No galleries yet</h3>
            <p>Create your first gallery to showcase your work</p>
            <a href="/dashboard/galleries/new" class="btn btn-primary">Create Gallery</a>
        </div>
    <?php else: ?>
        <?php foreach ($galleries as $gallery): ?>
        <div class="gallery-card" data-id="<?= $gallery['id'] ?>" data-visibility="<?= $gallery['visibility'] ?>">
            <div class="gallery-cover">
                <?php if ($gallery['cover_image']): ?>
                    <img src="<?= htmlspecialchars($gallery['cover_image']) ?>" alt="<?= htmlspecialchars($gallery['title']) ?>">
                <?php else: ?>
                    <div class="gallery-cover-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <div class="gallery-overlay">
                    <a href="/dashboard/galleries/<?= $gallery['id'] ?>/edit" class="btn btn-sm">Edit</a>
                    <a href="/gallery/<?= $gallery['slug'] ?>" target="_blank" class="btn btn-sm btn-ghost">View</a>
                </div>
            </div>
            
            <div class="gallery-info">
                <h4><?= htmlspecialchars($gallery['title']) ?></h4>
                <div class="gallery-meta">
                    <span><?= $gallery['image_count'] ?> images</span>
                    <span>•</span>
                    <span><?= $gallery['view_count'] ?? 0 ?> views</span>
                </div>
                <div class="gallery-badges">
                    <span class="badge badge-<?= match($gallery['visibility']) {
                        'public' => 'success',
                        'unlisted' => 'warning',
                        'private' => 'error',
                        default => 'info'
                    } ?>">
                        <?= ucfirst($gallery['visibility']) ?>
                    </span>
                    <?php if ($gallery['is_featured']): ?>
                        <span class="badge badge-info">Featured</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.galleries-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--space-lg);
}

.gallery-card {
    background: var(--bg-surface);
    border: 1px solid var(--bg-hover);
    border-radius: var(--radius-xl);
    overflow: hidden;
    transition: all var(--transition-base);
}

.gallery-card:hover {
    border-color: var(--violet-500);
    box-shadow: var(--glow-violet-subtle);
}

.gallery-cover {
    position: relative;
    aspect-ratio: 16/10;
    background: var(--bg-elevated);
    overflow: hidden;
}

.gallery-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-base);
}

.gallery-card:hover .gallery-cover img {
    transform: scale(1.05);
}

.gallery-cover-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.gallery-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.gallery-card:hover .gallery-overlay {
    opacity: 1;
}

.gallery-info {
    padding: var(--space-lg);
}

.gallery-info h4 {
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: var(--space-xs);
}

.gallery-meta {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin-bottom: var(--space-sm);
}

.gallery-badges {
    display: flex;
    gap: var(--space-xs);
}

.empty-state {
    text-align: center;
    padding: var(--space-xxl);
    color: var(--text-muted);
}

.empty-state svg {
    margin-bottom: var(--space-lg);
    opacity: 0.5;
}

.empty-state h3 {
    margin-bottom: var(--space-sm);
    color: var(--text-primary);
}

.empty-state p {
    margin-bottom: var(--space-lg);
}
</style>

<script>
// Filter galleries by visibility
document.getElementById('visibility-filter').addEventListener('change', function() {
    const value = this.value;
    document.querySelectorAll('.gallery-card').forEach(card => {
        if (!value || card.dataset.visibility === value) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>
