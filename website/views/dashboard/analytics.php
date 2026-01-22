<?php
/**
 * Dashboard - Analytics
 */
$activePage = 'analytics';
$pageTitle = 'Analytics';

$stats = $stats ?? [];
?>

<!-- Overview Cards -->
<div class="stats-grid mb-xl">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(124, 107, 240, 0.1); color: var(--violet-400);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Bookings (YTD)</span>
            <span class="stat-value" id="total-bookings">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Revenue (YTD)</span>
            <span class="stat-value" id="total-revenue">$0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(96, 165, 250, 0.1); color: var(--info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Page Views (30d)</span>
            <span class="stat-value" id="page-views">0</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Conversion Rate</span>
            <span class="stat-value" id="conversion-rate">0%</span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="dashboard-row mb-xl">
    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Bookings Over Time</h3>
            <select class="form-select form-select-sm" id="bookings-period">
                <option value="12">Last 12 months</option>
                <option value="6">Last 6 months</option>
                <option value="3">Last 3 months</option>
            </select>
        </div>
        <div class="card-body">
            <div class="chart-container" id="bookings-chart">
                <!-- Chart rendered by JS -->
                <div class="chart-placeholder">
                    <svg viewBox="0 0 400 150" preserveAspectRatio="none">
                        <polyline fill="none" stroke="var(--violet-500)" stroke-width="2"
                                  points="0,120 40,100 80,110 120,80 160,90 200,60 240,70 280,40 320,50 360,30 400,35"/>
                        <polyline fill="url(#chartGradient)" stroke="none"
                                  points="0,120 40,100 80,110 120,80 160,90 200,60 240,70 280,40 320,50 360,30 400,35 400,150 0,150"/>
                        <defs>
                            <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:var(--violet-500);stop-opacity:0.3"/>
                                <stop offset="100%" style="stop-color:var(--violet-500);stop-opacity:0"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-card">
        <div class="card-header">
            <h3>Revenue by Service</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($stats['top_services'])): ?>
            <div class="service-breakdown">
                <?php 
                $totalRevenue = array_sum(array_column($stats['top_services'], 'revenue'));
                foreach ($stats['top_services'] as $service): 
                    $percent = $totalRevenue > 0 ? ($service['revenue'] / $totalRevenue) * 100 : 0;
                ?>
                <div class="service-row">
                    <div class="service-info">
                        <span class="service-name"><?= ucfirst($service['service_type']) ?></span>
                        <span class="service-stats"><?= $service['count'] ?> bookings</span>
                    </div>
                    <div class="service-bar-container">
                        <div class="service-bar" style="width: <?= $percent ?>%"></div>
                    </div>
                    <span class="service-amount">$<?= number_format($service['revenue'], 0) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted text-center">No data available yet</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Traffic Sources -->
<div class="card dashboard-card">
    <div class="card-header">
        <h3>Recent Activity</h3>
    </div>
    <div class="card-body">
        <div class="activity-feed" id="activity-feed">
            <div class="activity-item">
                <div class="activity-dot" style="background: var(--success);"></div>
                <div class="activity-content">
                    <p>New booking confirmed</p>
                    <span class="text-muted text-sm">2 hours ago</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-dot" style="background: var(--violet-500);"></div>
                <div class="activity-content">
                    <p>Payment received - $375.00</p>
                    <span class="text-muted text-sm">5 hours ago</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-dot" style="background: var(--info);"></div>
                <div class="activity-content">
                    <p>Contract signed by client</p>
                    <span class="text-muted text-sm">Yesterday</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-dot" style="background: var(--warning);"></div>
                <div class="activity-content">
                    <p>New contact form submission</p>
                    <span class="text-muted text-sm">Yesterday</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chart-container {
    height: 200px;
}

.chart-placeholder {
    height: 100%;
}

.chart-placeholder svg {
    width: 100%;
    height: 100%;
}

.form-select-sm {
    font-size: 0.8125rem;
    padding: var(--space-xs) var(--space-sm);
}

.service-breakdown {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.service-row {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.service-info {
    flex: 0 0 120px;
}

.service-name {
    display: block;
    font-weight: 500;
}

.service-stats {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.service-bar-container {
    flex: 1;
    height: 8px;
    background: var(--bg-hover);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.service-bar {
    height: 100%;
    background: var(--violet-500);
    border-radius: var(--radius-full);
    transition: width 0.5s ease;
}

.service-amount {
    flex: 0 0 80px;
    text-align: right;
    font-weight: 500;
}

.activity-feed {
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-md);
}

.activity-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}

.activity-content p {
    margin: 0;
}
</style>

<script>
// Load and animate stats
document.addEventListener('DOMContentLoaded', () => {
    // Animate counters
    const counters = {
        'total-bookings': <?= array_sum(array_column($stats['bookings_by_month'] ?? [], 'count')) ?>,
        'total-revenue': <?= array_sum(array_column($stats['revenue_by_month'] ?? [], 'total')) ?>,
        'page-views': 0,
        'conversion-rate': 0
    };

    Object.keys(counters).forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        
        const target = counters[id];
        const isCurrency = id === 'total-revenue';
        const isPercent = id === 'conversion-rate';
        const duration = 1200;
        const start = performance.now();

        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(target * easeOut);

            if (isCurrency) {
                el.textContent = '$' + current.toLocaleString();
            } else if (isPercent) {
                el.textContent = current + '%';
            } else {
                el.textContent = current.toLocaleString();
            }

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }

        requestAnimationFrame(animate);
    });
});
</script>
