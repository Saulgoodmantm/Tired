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
