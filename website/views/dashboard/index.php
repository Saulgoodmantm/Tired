<?php
/**
 * Dashboard Overview
 */
$activePage = 'overview';

// Sample stats (would come from database)
$stats = [
    'bookings_today' => 2,
    'bookings_week' => 8,
    'bookings_month' => 24,
    'revenue_year' => 45680,
    'revenue_last_year' => 38500,
    'planned_earnings' => 12500,
    'available_days' => 18,
];
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Bookings Today</span>
            <span class="stat-value" data-count="<?= $stats['bookings_today'] ?>">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(124, 107, 240, 0.1); color: var(--violet-400);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">This Week</span>
            <span class="stat-value" data-count="<?= $stats['bookings_week'] ?>">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(96, 165, 250, 0.1); color: var(--info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">This Month</span>
            <span class="stat-value" data-count="<?= $stats['bookings_month'] ?>">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Available Days (30d)</span>
            <span class="stat-value" data-count="<?= $stats['available_days'] ?>">0</span>
        </div>
    </div>
</div>

<!-- Revenue Section -->
<div class="dashboard-row">
    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Revenue This Year</h3>
            <span class="text-success text-sm">
                +<?= round((($stats['revenue_year'] - $stats['revenue_last_year']) / $stats['revenue_last_year']) * 100) ?>% vs last year
            </span>
        </div>
        <div class="card-body">
            <span class="revenue-amount" data-count="<?= $stats['revenue_year'] ?>">$0</span>
            <div class="revenue-chart" id="revenue-chart">
                <!-- Chart placeholder - would use Chart.js -->
                <div class="chart-placeholder">
                    <svg viewBox="0 0 400 100" preserveAspectRatio="none">
                        <polyline
                            fill="none"
                            stroke="var(--violet-500)"
                            stroke-width="2"
                            points="0,80 50,70 100,75 150,50 200,55 250,30 300,35 350,20 400,25"
                        />
                        <polyline
                            fill="url(#gradient)"
                            stroke="none"
                            points="0,80 50,70 100,75 150,50 200,55 250,30 300,35 350,20 400,25 400,100 0,100"
                        />
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--violet-500);stop-opacity:0.3" />
                                <stop offset="100%" style="stop-color:var(--violet-500);stop-opacity:0" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Planned Earnings</h3>
            <span class="text-muted text-sm">From confirmed bookings</span>
        </div>
        <div class="card-body">
            <span class="revenue-amount text-violet" data-count="<?= $stats['planned_earnings'] ?>">$0</span>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card dashboard-card mt-lg">
    <div class="card-header">
        <h3>Recent Bookings</h3>
        <a href="/dashboard/bookings" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <!-- Sample data -->
                <tr>
                    <td>
                        <div class="flex items-center gap-sm">
                            <div class="avatar-sm"></div>
                            <span>John Doe</span>
                        </div>
                    </td>
                    <td>Personal</td>
                    <td>Jan 25, 2026</td>
                    <td><span class="badge badge-success">Confirmed</span></td>
                    <td>$375</td>
                    <td>
                        <button class="btn btn-ghost btn-sm">View</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="flex items-center gap-sm">
                            <div class="avatar-sm"></div>
                            <span>Jane Smith</span>
                        </div>
                    </td>
                    <td>Product</td>
                    <td>Jan 28, 2026</td>
                    <td><span class="badge badge-warning">Pending</span></td>
                    <td>$250</td>
                    <td>
                        <button class="btn btn-ghost btn-sm">View</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="flex items-center gap-sm">
                            <div class="avatar-sm"></div>
                            <span>Alex Johnson</span>
                        </div>
                    </td>
                    <td>Event</td>
                    <td>Feb 2, 2026</td>
                    <td><span class="badge badge-info">Requested</span></td>
                    <td>$525</td>
                    <td>
                        <button class="btn btn-ghost btn-sm">View</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Actions -->
<div class="dashboard-row mt-lg">
    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="/dashboard/galleries?action=new" class="quick-action-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="12" y1="8" x2="12" y2="16"></line>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>New Gallery</span>
                </a>
                <a href="/dashboard/bookings?action=new" class="quick-action-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <line x1="12" y1="14" x2="12" y2="18"></line>
                        <line x1="10" y1="16" x2="14" y2="16"></line>
                    </svg>
                    <span>New Booking</span>
                </a>
                <a href="/dashboard/contracts?action=new" class="quick-action-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <span>New Contract</span>
                </a>
                <a href="/dashboard/billing?action=invoice" class="quick-action-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>Create Invoice</span>
                </a>
            </div>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Pending Actions</h3>
        </div>
        <div class="card-body">
            <ul class="pending-list">
                <li class="pending-item">
                    <span class="pending-dot" style="background: var(--warning);"></span>
                    <span>3 contracts awaiting signature</span>
                </li>
                <li class="pending-item">
                    <span class="pending-dot" style="background: var(--info);"></span>
                    <span>5 unread messages</span>
                </li>
                <li class="pending-item">
                    <span class="pending-dot" style="background: var(--violet-500);"></span>
                    <span>2 booking requests to review</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
// Animate stat counters on load
document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    const isCurrency = el.classList.contains('revenue-amount');
    const duration = 1200;
    const start = performance.now();

    function animate(currentTime) {
        const elapsed = currentTime - start;
        const progress = Math.min(elapsed / duration, 1);

        // Ease out
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(target * easeOut);

        el.textContent = isCurrency ? '$' + current.toLocaleString() : current;

        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    }

    requestAnimationFrame(animate);
});
</script>
