<?php
/**
 * Dashboard - Contracts Management
 */
$activePage = 'contracts';
$pageTitle = 'Contracts';

use App\Services\ContractService;

$contractService = new ContractService();

// Get contracts
$statusFilter = $_GET['status'] ?? '';
$contracts = $contractService->getAllContracts($statusFilter ?: null);

// Get templates
$templates = $contractService->getTemplates();

// Stats
$stats = [
    'pending' => 0,
    'sent' => 0,
    'signed' => 0,
    'total' => count($contracts)
];

foreach ($contracts as $c) {
    if (isset($stats[$c['status']])) {
        $stats[$c['status']]++;
    }
}
?>

<!-- Contract Stats -->
<div class="stats-grid mb-xl">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Pending</span>
            <span class="stat-value"><?= $stats['pending'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(96, 165, 250, 0.1); color: var(--info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Sent</span>
            <span class="stat-value"><?= $stats['sent'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Signed</span>
            <span class="stat-value"><?= $stats['signed'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(124, 107, 240, 0.1); color: var(--violet-400);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total</span>
            <span class="stat-value"><?= $stats['total'] ?></span>
        </div>
    </div>
</div>

<!-- Actions Bar -->
<div class="card dashboard-card mb-lg">
    <div class="card-body">
        <div class="flex items-center justify-between gap-lg flex-wrap">
            <form class="flex items-center gap-md" method="GET">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : '' ?>>Sent</option>
                    <option value="viewed" <?= $statusFilter === 'viewed' ? 'selected' : '' ?>>Viewed</option>
                    <option value="signed" <?= $statusFilter === 'signed' ? 'selected' : '' ?>>Signed</option>
                </select>
            </form>

            <div class="flex items-center gap-md">
                <a href="/dashboard/contracts/templates" class="btn btn-ghost">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Templates
                </a>
                <a href="/dashboard/contracts/new" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Contract
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Contracts Table -->
<div class="card dashboard-card">
    <div class="card-body" style="padding: 0; overflow-x: auto;">
        <?php if (empty($contracts)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <p>No contracts found</p>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Created</th>
                    <th>Status</th>
                    <th>Signed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $contract): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($contract['title']) ?></strong>
                    </td>
                    <td>
                        <div>
                            <span><?= htmlspecialchars($contract['username'] ?? 'Unknown') ?></span>
                            <div class="text-muted text-sm"><?= htmlspecialchars($contract['email'] ?? '') ?></div>
                        </div>
                    </td>
                    <td>
                        <span class="text-sm"><?= htmlspecialchars($contract['template_name'] ?? $contract['template_type'] ?? 'Custom') ?></span>
                    </td>
                    <td>
                        <?= date('M j, Y', strtotime($contract['created_at'])) ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= match($contract['status']) {
                            'signed' => 'success',
                            'viewed' => 'info',
                            'sent' => 'info',
                            'pending' => 'warning',
                            default => 'warning'
                        } ?>">
                            <?= ucfirst($contract['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($contract['signed_at']): ?>
                            <span class="text-success">✓ <?= date('M j', strtotime($contract['signed_at'])) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex items-center gap-xs">
                            <a href="/dashboard/contracts/<?= $contract['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                            <?php if ($contract['status'] === 'pending'): ?>
                                <button class="btn btn-ghost btn-sm" onclick="sendContract(<?= $contract['id'] ?>)">Send</button>
                            <?php endif; ?>
                            <?php if ($contract['pdf_path']): ?>
                                <a href="/contracts/download/<?= $contract['id'] ?>" class="btn btn-ghost btn-sm" download>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Templates Section -->
<div class="card dashboard-card mt-xl">
    <div class="card-header">
        <h3>Contract Templates</h3>
        <a href="/dashboard/contracts/templates/new" class="btn btn-ghost btn-sm">+ New Template</a>
    </div>
    <div class="card-body">
        <?php if (empty($templates)): ?>
            <p class="text-muted text-center">No templates yet. Create one to get started.</p>
        <?php else: ?>
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
            <div class="template-card">
                <div class="template-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="template-info">
                    <h4><?= htmlspecialchars($template['name']) ?></h4>
                    <span class="text-muted text-sm"><?= ucfirst($template['type']) ?></span>
                </div>
                <a href="/dashboard/contracts/templates/<?= $template['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--space-md);
}

.template-card {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-lg);
    background: var(--bg-elevated);
    border: 1px solid var(--bg-hover);
    border-radius: var(--radius-lg);
    transition: all var(--transition-fast);
}

.template-card:hover {
    border-color: var(--violet-500);
}

.template-icon {
    color: var(--violet-400);
}

.template-info {
    flex: 1;
}

.template-info h4 {
    font-size: 0.9375rem;
    font-weight: 500;
    margin-bottom: 2px;
}

.empty-state {
    padding: var(--space-xxl);
    text-align: center;
    color: var(--text-muted);
}

.empty-state svg {
    margin-bottom: var(--space-md);
    opacity: 0.5;
}
</style>

<script>
async function sendContract(contractId) {
    if (!confirm('Send this contract to the client for signature?')) return;
    
    try {
        const response = await fetch(`/api/contracts/${contractId}/send`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to send contract');
        }
    } catch (error) {
        console.error(error);
        alert('Error sending contract');
    }
}
</script>
