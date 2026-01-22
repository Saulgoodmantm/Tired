<div class="page-header">
    <h1>Dashboard</h1>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="refreshStats()">
            🔄 Refresh
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">⏱️</div>
        <div class="stat-content">
            <div class="stat-value"><?= htmlspecialchars($stats['uptime']['formatted'] ?? 'N/A') ?></div>
            <div class="stat-label">Uptime</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['load']['1min'] ?? 0 ?></div>
            <div class="stat-label">Load Average (1m)</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💾</div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['memory']['percent'] ?? 0 ?>%</div>
            <div class="stat-label">Memory (<?= $stats['memory']['used_formatted'] ?? 'N/A' ?> / <?= $stats['memory']['total_formatted'] ?? 'N/A' ?>)</div>
        </div>
        <div class="stat-bar">
            <div class="stat-bar-fill" style="width: <?= $stats['memory']['percent'] ?? 0 ?>%"></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💿</div>
        <div class="stat-content">
            <div class="stat-value"><?= $stats['disk']['percent'] ?? 0 ?>%</div>
            <div class="stat-label">Disk (<?= $stats['disk']['used_formatted'] ?? 'N/A' ?> / <?= $stats['disk']['total_formatted'] ?? 'N/A' ?>)</div>
        </div>
        <div class="stat-bar">
            <div class="stat-bar-fill" style="width: <?= $stats['disk']['percent'] ?? 0 ?>%"></div>
        </div>
    </div>
</div>

<!-- Services & Sites Row -->
<div class="dashboard-row">
    <!-- Services -->
    <div class="card">
        <div class="card-header">
            <h2>Services</h2>
        </div>
        <div class="card-body">
            <div class="services-list" id="services-list">
                <?php foreach ($services ?? [] as $service): ?>
                <div class="service-item">
                    <div class="service-info">
                        <span class="service-status <?= $service['running'] ? 'status-running' : 'status-stopped' ?>">
                            <?= $service['running'] ? '●' : '○' ?>
                        </span>
                        <span class="service-name"><?= htmlspecialchars($service['name']) ?></span>
                    </div>
                    <div class="service-actions">
                        <?php if (in_array($service['name'], ['nginx', 'php8.2-fpm'])): ?>
                        <button class="btn btn-sm btn-secondary" onclick="restartService('<?= $service['name'] ?>')">
                            Restart
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sites -->
    <div class="card">
        <div class="card-header">
            <h2>Sites</h2>
            <a href="/sites/add" class="btn btn-sm btn-primary">+ Add</a>
        </div>
        <div class="card-body">
            <?php if (empty($sites)): ?>
            <p class="text-muted">No sites configured. <a href="/sites/add">Add your first site</a>.</p>
            <?php else: ?>
            <div class="sites-list">
                <?php foreach ($sites as $site): ?>
                <div class="site-item">
                    <div class="site-info">
                        <span class="site-status status-running">●</span>
                        <div>
                            <div class="site-name"><?= htmlspecialchars($site['name']) ?></div>
                            <div class="site-domain"><?= htmlspecialchars($site['domain']) ?></div>
                        </div>
                    </div>
                    <div class="site-actions">
                        <a href="https://<?= htmlspecialchars($site['domain']) ?>" target="_blank" class="btn btn-sm btn-ghost">
                            🔗
                        </a>
                        <form action="/sites/deploy" method="POST" class="inline-form">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($site['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-secondary">Deploy</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <button class="btn btn-action" onclick="runQuickCommand('restart-nginx')">
                🔄 Restart Nginx
            </button>
            <button class="btn btn-action" onclick="runQuickCommand('restart-php')">
                🔄 Restart PHP-FPM
            </button>
            <button class="btn btn-action" onclick="runQuickCommand('disk-usage')">
                💿 Check Disk
            </button>
            <button class="btn btn-action" onclick="runQuickCommand('ssl-status')">
                🔒 SSL Status
            </button>
            <a href="/commands" class="btn btn-action">
                ⌨️ All Commands
            </a>
        </div>
    </div>
</div>

<!-- Command Output Modal -->
<div id="output-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Command Output</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <pre id="command-output"></pre>
        </div>
    </div>
</div>

<script>
function refreshStats() {
    location.reload();
}

async function restartService(service) {
    if (!confirm(`Restart ${service}?`)) return;
    
    try {
        const response = await fetch('/api/service/restart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `service=${encodeURIComponent(service)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Service restarted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.error || 'Failed to restart service', 'error');
        }
    } catch (err) {
        showNotification('Request failed', 'error');
    }
}

async function runQuickCommand(commandId) {
    try {
        const response = await fetch('/commands/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `command_id=${encodeURIComponent(commandId)}`
        });
        
        const data = await response.json();
        
        document.getElementById('command-output').textContent = data.output || 'No output';
        document.getElementById('output-modal').style.display = 'flex';
    } catch (err) {
        showNotification('Command failed', 'error');
    }
}

function closeModal() {
    document.getElementById('output-modal').style.display = 'none';
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
});
</script>
