<div class="page-header">
    <div class="page-title">
        <a href="/sites/edit?id=<?= htmlspecialchars($site['id']) ?>" class="back-link">← <?= htmlspecialchars($site['name']) ?></a>
        <h1>Logs: <?= htmlspecialchars($site['domain']) ?></h1>
    </div>
    <div class="page-actions">
        <div class="btn-group">
            <a href="/sites/logs?id=<?= htmlspecialchars($site['id']) ?>&type=access" 
               class="btn <?= $type === 'access' ? 'btn-primary' : 'btn-secondary' ?>">
                Access Log
            </a>
            <a href="/sites/logs?id=<?= htmlspecialchars($site['id']) ?>&type=error" 
               class="btn <?= $type === 'error' ? 'btn-primary' : 'btn-secondary' ?>">
                Error Log
            </a>
        </div>
        <button class="btn btn-secondary" onclick="refreshLogs()">🔄 Refresh</button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><?= $type === 'error' ? 'Error' : 'Access' ?> Log</h2>
        <span class="text-muted">Last <?= $lines ?> lines</span>
    </div>
    <div class="card-body logs-container">
        <pre class="log-content" id="log-content"><?= htmlspecialchars($logs ?: 'No logs available') ?></pre>
    </div>
</div>

<script>
function refreshLogs() {
    location.reload();
}

// Auto-refresh every 30 seconds
setInterval(async () => {
    try {
        const response = await fetch('/api/logs?id=<?= htmlspecialchars($site['id']) ?>&type=<?= $type ?>&lines=<?= $lines ?>');
        const data = await response.json();
        if (data.success) {
            document.getElementById('log-content').textContent = data.logs || 'No logs available';
        }
    } catch (err) {
        console.error('Failed to refresh logs');
    }
}, 30000);
</script>
