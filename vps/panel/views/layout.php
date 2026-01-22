<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'VPS Panel') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
</head>
<body>
    <?php if (isset($user) && $user): ?>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">⚡</span>
                    <span class="logo-text">VPS Panel</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/dashboard" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="/sites" class="nav-item <?= str_starts_with($currentPage ?? '', 'sites') ? 'active' : '' ?>">
                    <span class="nav-icon">🌐</span>
                    <span>Sites</span>
                </a>
                <a href="/commands" class="nav-item <?= ($currentPage ?? '') === 'commands' ? 'active' : '' ?>">
                    <span class="nav-icon">⌨️</span>
                    <span>Commands</span>
                </a>
                <a href="/settings" class="nav-item <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <?php if (!empty($user['picture'])): ?>
                    <img src="<?= htmlspecialchars($user['picture']) ?>" alt="User" class="user-avatar">
                    <?php else: ?>
                    <div class="user-avatar user-avatar-placeholder">
                        <?= strtoupper(substr($user['email'] ?? 'U', 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
                        <a href="/logout" class="user-logout">Logout</a>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>
            
            <?php require $content; ?>
        </main>
    </div>
    <?php else: ?>
    <div class="auth-container">
        <?php require $content; ?>
    </div>
    <?php endif; ?>
    
    <script src="/assets/js/app.js"></script>
</body>
</html>
