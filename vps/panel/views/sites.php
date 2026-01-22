<div class="page-header">
    <h1>Sites</h1>
    <div class="page-actions">
        <a href="/sites/add" class="btn btn-primary">+ Add Site</a>
    </div>
</div>

<?php if (empty($sites)): ?>
<div class="empty-state">
    <div class="empty-icon">🌐</div>
    <h2>No sites configured</h2>
    <p>Add your first site to start managing it from the panel.</p>
    <a href="/sites/add" class="btn btn-primary">+ Add Site</a>
</div>
<?php else: ?>
<div class="sites-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Name</th>
                <th>Domain</th>
                <th>Branch</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sites as $site): ?>
            <tr>
                <td>
                    <span class="status-badge <?= ($site['status']['status'] ?? '') === 'active' ? 'status-active' : 'status-inactive' ?>">
                        <?= ($site['status']['status'] ?? 'unknown') === 'active' ? '● Active' : '○ Not Deployed' ?>
                    </span>
                </td>
                <td>
                    <strong><?= htmlspecialchars($site['name']) ?></strong>
                </td>
                <td>
                    <a href="https://<?= htmlspecialchars($site['domain']) ?>" target="_blank" class="domain-link">
                        <?= htmlspecialchars($site['domain']) ?>
                        <span class="external-link">↗</span>
                    </a>
                </td>
                <td>
                    <code><?= htmlspecialchars($site['branch'] ?? 'main') ?></code>
                    <?php if (!empty($site['status']['current_commit'])): ?>
                    <span class="commit-hash"><?= htmlspecialchars($site['status']['current_commit']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($site['updated_at'])): ?>
                    <span class="date-relative" title="<?= htmlspecialchars($site['updated_at']) ?>">
                        <?= date('M j, Y H:i', strtotime($site['updated_at'])) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">Never</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <form action="/sites/deploy" method="POST" class="inline-form">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($site['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-primary" title="Deploy">
                                🚀 Deploy
                            </button>
                        </form>
                        <a href="/sites/logs?id=<?= htmlspecialchars($site['id']) ?>" class="btn btn-sm btn-secondary" title="View Logs">
                            📄 Logs
                        </a>
                        <a href="/sites/edit?id=<?= htmlspecialchars($site['id']) ?>" class="btn btn-sm btn-ghost" title="Edit">
                            ✏️
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
