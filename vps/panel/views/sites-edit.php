<div class="page-header">
    <div class="page-title">
        <a href="/sites" class="back-link">← Sites</a>
        <h1>Edit: <?= htmlspecialchars($site['name']) ?></h1>
    </div>
    <div class="page-actions">
        <a href="https://<?= htmlspecialchars($site['domain']) ?>" target="_blank" class="btn btn-secondary">
            🔗 Visit Site
        </a>
        <form action="/sites/deploy" method="POST" class="inline-form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($site['id']) ?>">
            <button type="submit" class="btn btn-primary">🚀 Deploy</button>
        </form>
    </div>
</div>

<div class="edit-grid">
    <!-- Main Form -->
    <div class="card">
        <div class="card-header">
            <h2>Site Configuration</h2>
        </div>
        <div class="card-body">
            <form action="/sites/edit" method="POST" class="form">
                <input type="hidden" name="id" value="<?= htmlspecialchars($site['id']) ?>">
                
                <div class="form-group">
                    <label for="name">Site Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($site['name']) ?>" required class="form-input">
                </div>
                
                <div class="form-group">
                    <label>Domain</label>
                    <input type="text" value="<?= htmlspecialchars($site['domain']) ?>" class="form-input" disabled>
                    <span class="form-hint">Domain cannot be changed after creation</span>
                </div>
                
                <div class="form-group">
                    <label for="repo">Repository URL</label>
                    <input type="text" id="repo" name="repo" value="<?= htmlspecialchars($site['repo']) ?>" class="form-input">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="branch">Branch</label>
                        <input type="text" id="branch" name="branch" value="<?= htmlspecialchars($site['branch'] ?? 'main') ?>" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="php_version">PHP Version</label>
                        <select id="php_version" name="php_version" class="form-input">
                            <option value="8.2" <?= ($site['php_version'] ?? '8.2') === '8.2' ? 'selected' : '' ?>>PHP 8.2</option>
                            <option value="8.1" <?= ($site['php_version'] ?? '') === '8.1' ? 'selected' : '' ?>>PHP 8.1</option>
                            <option value="8.0" <?= ($site['php_version'] ?? '') === '8.0' ? 'selected' : '' ?>>PHP 8.0</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Status Panel -->
    <div class="side-panel">
        <div class="card">
            <div class="card-header">
                <h2>Status</h2>
            </div>
            <div class="card-body">
                <div class="status-list">
                    <div class="status-item">
                        <span class="status-label">Deployment</span>
                        <span class="status-value <?= ($status['status'] ?? '') === 'active' ? 'text-success' : 'text-warning' ?>">
                            <?= ($status['status'] ?? 'unknown') === 'active' ? '● Active' : '○ Not Deployed' ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Current Commit</span>
                        <span class="status-value">
                            <code><?= htmlspecialchars($status['current_commit'] ?? 'N/A') ?></code>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Nginx Config</span>
                        <span class="status-value <?= ($status['nginx_valid'] ?? false) ? 'text-success' : 'text-muted' ?>">
                            <?= ($status['nginx_valid'] ?? false) ? '✓ Valid' : '— Unknown' ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Path</span>
                        <span class="status-value">
                            <code class="small"><?= htmlspecialchars($site['path']) ?></code>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="card-body">
                <div class="quick-actions-vertical">
                    <a href="/sites/logs?id=<?= htmlspecialchars($site['id']) ?>&type=access" class="btn btn-secondary btn-block">
                        📄 View Access Logs
                    </a>
                    <a href="/sites/logs?id=<?= htmlspecialchars($site['id']) ?>&type=error" class="btn btn-secondary btn-block">
                        ⚠️ View Error Logs
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card card-danger">
            <div class="card-header">
                <h2>Danger Zone</h2>
            </div>
            <div class="card-body">
                <p class="text-muted">Remove this site from the panel. Files on the server will not be deleted.</p>
                <form action="/sites/delete" method="POST" onsubmit="return confirm('Remove this site from the panel?')">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($site['id']) ?>">
                    <button type="submit" class="btn btn-danger btn-block">Remove Site</button>
                </form>
            </div>
        </div>
    </div>
</div>
