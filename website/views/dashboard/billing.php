<?php
/**
 * Dashboard - Billing & Payments
 */
$activePage = 'billing';
$pageTitle = 'Billing';

use App\Utils\Database;

// Stats from controller
$stats = $stats ?? ['total_revenue' => 0, 'pending' => 0, 'refunded' => 0];
$payments = $payments ?? [];
?>

<!-- Stats -->
<div class="stats-grid mb-xl">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-value">$<?= number_format($stats['total_revenue'] ?? 0, 2) ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Pending</span>
            <span class="stat-value">$<?= number_format($stats['pending'] ?? 0, 2) ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(248, 113, 113, 0.1); color: var(--error);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Refunded</span>
            <span class="stat-value">$<?= number_format($stats['refunded'] ?? 0, 2) ?></span>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card dashboard-card mb-lg">
    <div class="card-body">
        <div class="flex items-center justify-between gap-lg">
            <div class="flex items-center gap-md">
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="succeeded">Succeeded</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                <input type="date" class="form-input" id="date-from" placeholder="From">
                <input type="date" class="form-input" id="date-to" placeholder="To">
            </div>
            
            <div class="flex items-center gap-md">
                <button class="btn btn-ghost" id="export-csv">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="card dashboard-card">
    <div class="card-header">
        <h3>Recent Payments</h3>
    </div>
    <div class="card-body" style="padding: 0; overflow-x: auto;">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <p>No payments yet</p>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr data-status="<?= $payment['status'] ?>">
                    <td>
                        <code class="text-sm"><?= substr($payment['provider_payment_id'] ?? $payment['id'], 0, 12) ?>...</code>
                    </td>
                    <td><?= htmlspecialchars($payment['client_email'] ?? 'Unknown') ?></td>
                    <td><?= ucfirst($payment['service_type'] ?? 'N/A') ?></td>
                    <td>
                        <strong>$<?= number_format($payment['amount'], 2) ?></strong>
                        <span class="text-muted text-sm"><?= $payment['currency'] ?? 'USD' ?></span>
                    </td>
                    <td>
                        <span class="badge badge-<?= match($payment['status']) {
                            'succeeded' => 'success',
                            'pending' => 'warning',
                            'failed' => 'error',
                            'refunded' => 'info',
                            default => 'warning'
                        } ?>">
                            <?= ucfirst($payment['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y g:i A', strtotime($payment['created_at'])) ?></td>
                    <td>
                        <div class="flex items-center gap-xs">
                            <?php if ($payment['status'] === 'succeeded'): ?>
                                <button class="btn btn-ghost btn-sm" 
                                        onclick="refundPayment('<?= $payment['id'] ?>')">
                                    Refund
                                </button>
                            <?php endif; ?>
                            <a href="https://dashboard.stripe.com/payments/<?= $payment['provider_payment_id'] ?>" 
                               target="_blank" class="btn btn-ghost btn-sm">
                                View in Stripe
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style>
.empty-state {
    padding: var(--space-xxl);
    text-align: center;
    color: var(--text-muted);
}

.empty-state svg {
    margin-bottom: var(--space-md);
    opacity: 0.5;
}

code {
    font-family: 'SF Mono', Monaco, monospace;
    background: var(--bg-elevated);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
}
</style>

<script>
// Filter by status
document.getElementById('status-filter').addEventListener('change', function() {
    const status = this.value;
    document.querySelectorAll('.data-table tbody tr').forEach(row => {
        if (!status || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Refund payment
async function refundPayment(paymentId) {
    if (!confirm('Are you sure you want to refund this payment?')) return;
    
    try {
        const response = await fetch(`/api/payments/${paymentId}/refund`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        if (response.ok) {
            location.reload();
        } else {
            const data = await response.json();
            alert(data.error || 'Failed to refund payment');
        }
    } catch (error) {
        console.error(error);
        alert('Error processing refund');
    }
}

// Export CSV
document.getElementById('export-csv').addEventListener('click', () => {
    window.location.href = '/api/billing/export?format=csv';
});
</script>
