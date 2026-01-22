<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Professional photography services by TiredOfDoinTM') ?>">

    <title><?= htmlspecialchars($pageTitle ?? 'TiredOfDoinTM - Photography') ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@300;400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <?php if (!empty($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
</head>
<body>

    <!-- Navigation Header -->
    <header class="nav-header">
        <a href="/" class="nav-logo brand-name">TiredOfDoinTM</a>

        <button class="nav-menu-btn" aria-label="Toggle menu">
            <?php if (!empty($user)): ?>
                <span class="nav-username text-sm"><?= htmlspecialchars($user['username']) ?></span>
                <img
                    src="<?= htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png') ?>"
                    alt="Profile"
                    class="nav-avatar"
                >
            <?php endif; ?>
            <div class="nav-hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </header>

    <!-- Menu Overlay -->
    <div class="menu-overlay">
        <nav class="menu-nav">
            <!-- Gallery with submenu -->
            <a href="#" class="menu-item has-submenu">Gallery</a>
            <div class="menu-submenu">
                <a href="/gallery/personal">Personal</a>
                <a href="/gallery/product">Product</a>
                <a href="/gallery/group">Group</a>
                <a href="/gallery/event">Event</a>
                <a href="/gallery/misc">Misc</a>
            </div>

            <!-- Socials with submenu -->
            <a href="#" class="menu-item has-submenu">Socials</a>
            <div class="menu-submenu">
                <a href="https://www.instagram.com/tiredofdointm/" target="_blank" rel="noopener">Instagram</a>
                <a href="https://www.instagram.com/tiredflics/" target="_blank" rel="noopener">Instagram 2</a>
                <a href="https://www.tiktok.com/@tiredofdointm" target="_blank" rel="noopener">TikTok</a>
            </div>

            <a href="/rates" class="menu-item">Rates</a>
            <a href="/booking" class="menu-item">Book Now</a>
            <a href="/contact" class="menu-item">Contact</a>

            <?php if (empty($user)): ?>
                <a href="/login" class="menu-item">Sign In</a>
            <?php else: ?>
                <a href="/profile" class="menu-item">My Profile</a>
                <?php if (($user['role_level'] ?? 0) >= 50): ?>
                    <a href="/dashboard" class="menu-item">Dashboard</a>
                <?php endif; ?>
                <a href="/logout" class="menu-item text-muted">Sign Out</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <main>
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <span class="brand-name" style="font-size: 1rem;">TiredOfDoinTM</span>
                    <p class="text-muted text-sm mt-sm">Professional Photography</p>
                </div>
                <div class="footer-links">
                    <a href="/privacy">Privacy Policy</a>
                    <a href="/terms">Terms of Service</a>
                    <a href="/contact">Contact</a>
                </div>
                <div class="footer-social">
                    <a href="https://www.instagram.com/tiredofdointm/" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="https://www.tiktok.com/@tiredofdointm" target="_blank" rel="noopener" aria-label="TikTok">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                        </svg>
                    </a>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="text-muted text-sm">
                    © <?= date('Y') ?> TiredOfDoinTM. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <style>
    .site-footer {
        background: var(--bg-surface);
        border-top: 1px solid var(--bg-hover);
        padding: 48px 0 24px;
        margin-top: 80px;
    }
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 24px;
        margin-bottom: 32px;
    }
    .footer-links {
        display: flex;
        gap: 24px;
    }
    .footer-links a {
        color: var(--text-muted);
        font-size: 0.875rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    .footer-links a:hover {
        color: var(--text-primary);
    }
    .footer-social {
        display: flex;
        gap: 16px;
    }
    .footer-social a {
        color: var(--text-muted);
        transition: color 0.2s;
    }
    .footer-social a:hover {
        color: var(--violet-400);
    }
    .footer-bottom {
        text-align: center;
        padding-top: 24px;
        border-top: 1px solid var(--bg-hover);
    }
    @media (max-width: 640px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
        }
        .footer-links {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
    </style>

    <!-- Lightbox (for galleries) -->
    <div class="lightbox">
        <button class="lightbox-close" aria-label="Close">&times;</button>
        <div class="lightbox-content">
            <img src="" alt="Full size image">
        </div>
    </div>

    <?php if (!($gatePassed ?? \App\Utils\Gate::hasPassed())): ?>
    <!-- Gate Overlay -->
    <div class="gate-overlay">
        <div class="gate-panel">
            <div class="gate-content">
                <input 
                    type="password" 
                    class="gate-input" 
                    placeholder="•••"
                    maxlength="10"
                    autocomplete="off"
                    inputmode="numeric"
                >
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="/assets/js/app.js"></script>
    <?php if (!($gatePassed ?? \App\Utils\Gate::hasPassed())): ?>
        <script src="/assets/js/gate.js"></script>
    <?php endif; ?>
    <?php if (!empty($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?= htmlspecialchars($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
