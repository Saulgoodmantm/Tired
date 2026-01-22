<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Server Management Panel
 * =============================================================================
 * Manage deployments, domains, and server status
 * =============================================================================
 */
?>

<div class="panel-header">
    <h1>Server Management</h1>
    <p class="subtitle">Deploy, manage domains, and monitor your server</p>
</div>

<!-- Quick Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🚀</div>
        <div class="stat-content">
            <span class="stat-value"><?= $currentBranch ?? 'main' ?></span>
            <span class="stat-label">Current Branch</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <span class="stat-value"><?= $lastDeploy ?? 'Never' ?></span>
            <span class="stat-label">Last Deploy</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🌐</div>
        <div class="stat-content">
            <span class="stat-value"><?= count($domains ?? []) ?></span>
            <span class="stat-label">Domains</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💚</div>
        <div class="stat-content">
            <span class="stat-value" id="server-status">Checking...</span>
            <span class="stat-label">Server Status</span>
        </div>
    </div>
</div>

<!-- Deployment Section -->
<div class="panel-section">
    <h2>🚀 Deploy</h2>
    <div class="deploy-controls">
        <div class="form-group">
            <label for="branch-select">Branch / Tag</label>
            <select id="branch-select" class="form-control">
                <option value="main">main (default)</option>
                <?php foreach ($branches ?? [] as $branch): ?>
                    <option value="<?= htmlspecialchars($branch) ?>"><?= htmlspecialchars($branch) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button id="deploy-btn" class="btn btn-primary btn-lg">
            <span class="btn-icon">🚀</span>
            Deploy Now
        </button>
    </div>
    <div id="deploy-output" class="terminal-output" style="display: none;">
        <pre id="deploy-log"></pre>
    </div>
</div>

<!-- Domains Section -->
<div class="panel-section">
    <h2>🌐 Domains</h2>
    <div class="domains-list">
        <?php if (empty($domains)): ?>
            <div class="empty-state">
                <p>No domains configured yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($domains as $domain): ?>
                <div class="domain-card" data-domain="<?= htmlspecialchars($domain['name']) ?>">
                    <div class="domain-info">
                        <span class="domain-name"><?= htmlspecialchars($domain['name']) ?></span>
                        <span class="domain-ssl <?= $domain['ssl'] ? 'ssl-active' : 'ssl-inactive' ?>">
                            <?= $domain['ssl'] ? '🔒 SSL Active' : '⚠️ No SSL' ?>
                        </span>
                    </div>
                    <div class="domain-actions">
                        <?php if (!$domain['ssl']): ?>
                            <button class="btn btn-sm btn-secondary" onclick="enableSSL('<?= htmlspecialchars($domain['name']) ?>')">
                                Enable SSL
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-danger" onclick="removeDomain('<?= htmlspecialchars($domain['name']) ?>')">
                            Remove
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="add-domain-form">
        <h3>Add New Domain</h3>
        <div class="form-row">
            <input type="text" id="new-domain" class="form-control" placeholder="example.com">
            <button id="add-domain-btn" class="btn btn-primary">Add Domain</button>
        </div>
        <p class="help-text">Make sure DNS is pointed to this server (<?= $_SERVER['SERVER_ADDR'] ?? 'your VPS IP' ?>) before adding.</p>
    </div>
</div>

<!-- Server Status Section -->
<div class="panel-section">
    <h2>📊 Server Status</h2>
    <div class="server-stats" id="server-stats">
        <div class="server-stat">
            <span class="stat-name">PHP Version</span>
            <span class="stat-value"><?= PHP_VERSION ?></span>
        </div>
        <div class="server-stat">
            <span class="stat-name">Memory Usage</span>
            <span class="stat-value" id="memory-usage">Loading...</span>
        </div>
        <div class="server-stat">
            <span class="stat-name">Disk Usage</span>
            <span class="stat-value" id="disk-usage">Loading...</span>
        </div>
        <div class="server-stat">
            <span class="stat-name">Uptime</span>
            <span class="stat-value" id="uptime">Loading...</span>
        </div>
    </div>
    
    <div class="service-status">
        <h3>Services</h3>
        <div class="services-grid" id="services-grid">
            <div class="service-item" data-service="nginx">
                <span class="service-name">Nginx</span>
                <span class="service-status loading">•</span>
            </div>
            <div class="service-item" data-service="php-fpm">
                <span class="service-name">PHP-FPM</span>
                <span class="service-status loading">•</span>
            </div>
            <div class="service-item" data-service="postgresql">
                <span class="service-name">PostgreSQL</span>
                <span class="service-status loading">•</span>
            </div>
        </div>
    </div>
</div>

<!-- Logs Section -->
<div class="panel-section">
    <h2>📜 Recent Logs</h2>
    <div class="log-tabs">
        <button class="log-tab active" data-log="deploy">Deploy Logs</button>
        <button class="log-tab" data-log="error">Error Logs</button>
        <button class="log-tab" data-log="access">Access Logs</button>
    </div>
    <div class="terminal-output">
        <pre id="log-content">Select a log type to view...</pre>
    </div>
    <button id="refresh-logs" class="btn btn-secondary">Refresh Logs</button>
</div>

<!-- GitHub Webhook Info -->
<div class="panel-section">
    <h2>🔗 GitHub Webhook</h2>
    <div class="webhook-info">
        <p>Configure this webhook in your GitHub repository settings for auto-deploy:</p>
        <div class="code-block">
            <code id="webhook-url"><?= ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') ?>/webhook/github</code>
            <button class="copy-btn" onclick="copyWebhook()">Copy</button>
        </div>
        <div class="webhook-secret">
            <label>Webhook Secret:</label>
            <code><?= substr(hash('sha256', ($config['security']['encryption_key'] ?? 'default')), 0, 32) ?></code>
        </div>
        <p class="help-text">
            In GitHub: Settings → Webhooks → Add webhook<br>
            Content type: application/json<br>
            Events: Just the push event
        </p>
    </div>
</div>

<style>
.panel-header {
    margin-bottom: 30px;
}
.panel-header h1 {
    font-size: 28px;
    margin-bottom: 8px;
}
.subtitle {
    color: #888;
    font-size: 14px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.stat-icon {
    font-size: 32px;
}
.stat-content {
    display: flex;
    flex-direction: column;
}
.stat-value {
    font-size: 20px;
    font-weight: 600;
}
.stat-label {
    font-size: 12px;
    color: #888;
}

.panel-section {
    background: #1a1a1a;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}
.panel-section h2 {
    font-size: 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.deploy-controls {
    display: flex;
    gap: 16px;
    align-items: flex-end;
}
.form-group {
    flex: 1;
    max-width: 300px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #888;
}
.form-control {
    width: 100%;
    padding: 12px;
    background: #0a0a0a;
    border: 1px solid #333;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
}
.form-control:focus {
    outline: none;
    border-color: #fff;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-primary {
    background: #fff;
    color: #000;
}
.btn-primary:hover {
    background: #e0e0e0;
}
.btn-secondary {
    background: #333;
    color: #fff;
}
.btn-danger {
    background: #ff4444;
    color: #fff;
}
.btn-lg {
    padding: 14px 28px;
    font-size: 16px;
}
.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

.terminal-output {
    background: #0a0a0a;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
    max-height: 400px;
    overflow-y: auto;
}
.terminal-output pre {
    margin: 0;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 12px;
    line-height: 1.5;
    color: #0f0;
    white-space: pre-wrap;
}

.domains-list {
    margin-bottom: 24px;
}
.domain-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #0a0a0a;
    border-radius: 8px;
    margin-bottom: 8px;
}
.domain-name {
    font-weight: 600;
    font-size: 16px;
}
.domain-ssl {
    font-size: 12px;
    margin-left: 12px;
}
.ssl-active { color: #0f0; }
.ssl-inactive { color: #ff0; }
.domain-actions {
    display: flex;
    gap: 8px;
}

.form-row {
    display: flex;
    gap: 12px;
}
.form-row .form-control {
    flex: 1;
}
.help-text {
    font-size: 12px;
    color: #666;
    margin-top: 8px;
}

.server-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.server-stat {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.server-stat .stat-name {
    font-size: 12px;
    color: #888;
}
.server-stat .stat-value {
    font-size: 16px;
    font-weight: 600;
}

.services-grid {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.service-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #0a0a0a;
    border-radius: 8px;
}
.service-status {
    font-size: 24px;
}
.service-status.running { color: #0f0; }
.service-status.stopped { color: #f00; }
.service-status.loading { color: #888; }

.log-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
}
.log-tab {
    padding: 8px 16px;
    background: #0a0a0a;
    border: 1px solid #333;
    border-radius: 8px;
    color: #888;
    cursor: pointer;
}
.log-tab.active {
    background: #333;
    color: #fff;
    border-color: #fff;
}

.webhook-info {
    background: #0a0a0a;
    border-radius: 8px;
    padding: 20px;
}
.code-block {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #1a1a1a;
    padding: 12px;
    border-radius: 8px;
    margin: 12px 0;
}
.code-block code {
    flex: 1;
    font-family: monospace;
    color: #0f0;
    word-break: break-all;
}
.copy-btn {
    padding: 8px 16px;
    background: #333;
    border: none;
    border-radius: 4px;
    color: #fff;
    cursor: pointer;
}
.webhook-secret {
    margin-top: 16px;
}
.webhook-secret label {
    color: #888;
    margin-right: 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load server status
    loadServerStatus();
    
    // Deploy button
    document.getElementById('deploy-btn').addEventListener('click', triggerDeploy);
    
    // Add domain button
    document.getElementById('add-domain-btn').addEventListener('click', addDomain);
    
    // Log tabs
    document.querySelectorAll('.log-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.log-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            loadLogs(this.dataset.log);
        });
    });
    
    // Refresh logs
    document.getElementById('refresh-logs').addEventListener('click', function() {
        const activeTab = document.querySelector('.log-tab.active');
        if (activeTab) loadLogs(activeTab.dataset.log);
    });
});

function loadServerStatus() {
    fetch('/api/server/status')
        .then(r => r.json())
        .then(data => {
            document.getElementById('server-status').textContent = data.status || 'Online';
            document.getElementById('memory-usage').textContent = data.memory || 'N/A';
            document.getElementById('disk-usage').textContent = data.disk || 'N/A';
            document.getElementById('uptime').textContent = data.uptime || 'N/A';
            
            // Services
            if (data.services) {
                Object.keys(data.services).forEach(service => {
                    const el = document.querySelector(`[data-service="${service}"] .service-status`);
                    if (el) {
                        el.classList.remove('loading');
                        el.classList.add(data.services[service] ? 'running' : 'stopped');
                    }
                });
            }
        })
        .catch(() => {
            document.getElementById('server-status').textContent = 'Error';
        });
}

function triggerDeploy() {
    const branch = document.getElementById('branch-select').value;
    const btn = document.getElementById('deploy-btn');
    const output = document.getElementById('deploy-output');
    const log = document.getElementById('deploy-log');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-icon">⏳</span> Deploying...';
    output.style.display = 'block';
    log.textContent = 'Starting deployment...\n';
    
    fetch('/api/server/deploy', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ branch: branch })
    })
    .then(r => r.json())
    .then(data => {
        log.textContent += data.output || data.message || 'Deployment complete';
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-icon">🚀</span> Deploy Now';
        
        if (data.success) {
            log.textContent += '\n\n✅ Deployment successful!';
        } else {
            log.textContent += '\n\n❌ Deployment failed: ' + (data.error || 'Unknown error');
        }
    })
    .catch(err => {
        log.textContent += '\n\n❌ Error: ' + err.message;
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-icon">🚀</span> Deploy Now';
    });
}

function addDomain() {
    const domain = document.getElementById('new-domain').value.trim();
    if (!domain) return alert('Please enter a domain');
    
    fetch('/api/server/domains', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ domain: domain })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to add domain');
        }
    });
}

function removeDomain(domain) {
    if (!confirm(`Remove domain ${domain}?`)) return;
    
    fetch('/api/server/domains', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ domain: domain })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`[data-domain="${domain}"]`)?.remove();
        } else {
            alert(data.error || 'Failed to remove domain');
        }
    });
}

function enableSSL(domain) {
    fetch('/api/server/ssl', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ domain: domain })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to enable SSL');
        }
    });
}

function loadLogs(type) {
    const log = document.getElementById('log-content');
    log.textContent = 'Loading...';
    
    fetch(`/api/server/logs?type=${type}`)
        .then(r => r.json())
        .then(data => {
            log.textContent = data.content || 'No logs available';
        });
}

function copyWebhook() {
    const url = document.getElementById('webhook-url').textContent;
    navigator.clipboard.writeText(url);
    alert('Webhook URL copied!');
}
</script>
