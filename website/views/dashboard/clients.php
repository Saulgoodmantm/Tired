<?php
/**
 * Dashboard - Clients Management
 */
$activePage = 'clients';
$pageTitle = 'Clients';

use App\Utils\Database;

// Get clients with their booking stats
$clients = Database::query(
    "SELECT u.*, 
            COUNT(DISTINCT b.id) as total_bookings,
            SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
            COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.total_price ELSE 0 END), 0) as total_spent,
            MAX(b.date_start) as last_booking
     FROM users u
     LEFT JOIN bookings b ON u.id = b.user_id
     WHERE u.role_id >= 1
     GROUP BY u.id
     ORDER BY total_bookings DESC, u.created_at DESC
     LIMIT 100"
);

$totalClients = count($clients);
$activeClients = count(array_filter($clients, fn($c) => $c['total_bookings'] > 0));
$totalRevenue = array_sum(array_column($clients, 'total_spent'));
?>

<!-- Stats -->
<div class="stats-grid mb-xl">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(124, 107, 240, 0.1); color: var(--violet-400);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Clients</span>
            <span class="stat-value"><?= $totalClients ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Active Clients</span>
            <span class="stat-value"><?= $activeClients ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-value">$<?= number_format($totalRevenue, 0) ?></span>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card dashboard-card mb-lg">
    <div class="card-body">
        <div class="flex items-center justify-between gap-lg">
            <div class="flex items-center gap-md">
                <div class="search-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="client-search" placeholder="Search clients..." class="form-input">
                </div>
            </div>
            
            <button class="btn btn-ghost" id="export-btn">
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

<!-- Clients Table -->
<div class="card dashboard-card">
    <div class="card-body" style="padding: 0; overflow-x: auto;">
        <table class="data-table" id="clients-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Bookings</th>
                    <th>Total Spent</th>
                    <th>Last Booking</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <tr data-name="<?= strtolower($client['username'] ?? '') ?>" data-email="<?= strtolower($client['email'] ?? '') ?>">
                    <td>
                        <div class="flex items-center gap-sm">
                            <div class="avatar-sm avatar-initials">
                                <?= strtoupper(substr($client['username'] ?? $client['email'] ?? 'C', 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($client['username'] ?? 'Unknown') ?></strong>
                                <?php if ($client['total_bookings'] >= 5): ?>
                                    <span class="badge badge-success ml-xs">VIP</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($client['email'] ?? '') ?></td>
                    <td>
                        <span><?= $client['total_bookings'] ?></span>
                        <span class="text-muted text-sm">(<?= $client['completed_bookings'] ?> completed)</span>
                    </td>
                    <td>$<?= number_format($client['total_spent'], 2) ?></td>
                    <td>
                        <?php if ($client['last_booking']): ?>
                            <?= date('M j, Y', strtotime($client['last_booking'])) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($client['created_at'])) ?></td>
                    <td>
                        <div class="flex items-center gap-xs">
                            <a href="/dashboard/clients/<?= $client['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                            <a href="mailto:<?= $client['email'] ?>" class="btn btn-ghost btn-sm">Email</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box svg {
    position: absolute;
    left: 12px;
    color: var(--text-muted);
}

.search-box .form-input {
    padding-left: 36px;
    min-width: 250px;
}

.avatar-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--bg-hover);
    color: var(--text-muted);
}

.ml-xs {
    margin-left: var(--space-xs);
}
</style>

<script>
// Client search
document.getElementById('client-search').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#clients-table tbody tr').forEach(row => {
        const name = row.dataset.name;
        const email = row.dataset.email;
        if (name.includes(query) || email.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Export (placeholder)
document.getElementById('export-btn').addEventListener('click', () => {
    alert('Export functionality coming soon!');
});
</script>
