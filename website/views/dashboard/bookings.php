<?php
/**
 * Dashboard - Bookings Management
 */
$activePage = 'bookings';
$pageTitle = 'Bookings';

use App\Services\BookingService;
use App\Utils\Database;

$bookingService = new BookingService();

// Get filter parameters
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

// Get bookings
$filters = ['limit' => 100];
if ($status) $filters['status'] = $status;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;

$bookings = $bookingService->getAllBookings($filters);
$upcomingBookings = $bookingService->getUpcomingBookings(5);
$stats = $bookingService->getStats('month');
?>

<!-- Booking Stats -->
<div class="stats-grid mb-xl">
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
            <span class="stat-label">Total This Month</span>
            <span class="stat-value"><?= $stats['total_bookings'] ?? 0 ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--success);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Completed</span>
            <span class="stat-value"><?= $stats['completed'] ?? 0 ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(96, 165, 250, 0.1); color: var(--info);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Upcoming</span>
            <span class="stat-value"><?= $stats['upcoming'] ?? 0 ?></span>
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
            <span class="stat-label">Revenue (Month)</span>
            <span class="stat-value">$<?= number_format($stats['earned_revenue'] ?? 0, 0) ?></span>
        </div>
    </div>
</div>

<!-- Actions Bar -->
<div class="card dashboard-card mb-lg">
    <div class="card-body">
        <div class="flex items-center justify-between gap-lg flex-wrap">
            <!-- Filters -->
            <form class="flex items-center gap-md flex-wrap" method="GET">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                
                <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" class="form-input" placeholder="From">
                <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>" class="form-input" placeholder="To">
                
                <button type="submit" class="btn btn-ghost">Filter</button>
                <?php if ($status || $dateFrom || $dateTo): ?>
                    <a href="/dashboard/bookings" class="btn btn-ghost">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Actions -->
            <div class="flex items-center gap-md">
                <a href="/dashboard/bookings?action=new" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Booking
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Bookings -->
<?php if (!empty($upcomingBookings)): ?>
<div class="card dashboard-card mb-lg">
    <div class="card-header">
        <h3>🔔 Upcoming Bookings</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="upcoming-grid">
            <?php foreach ($upcomingBookings as $booking): ?>
            <a href="/dashboard/bookings/<?= $booking['id'] ?>" class="upcoming-item">
                <div class="upcoming-date">
                    <span class="upcoming-day"><?= date('d', strtotime($booking['date_start'])) ?></span>
                    <span class="upcoming-month"><?= date('M', strtotime($booking['date_start'])) ?></span>
                </div>
                <div class="upcoming-details">
                    <strong><?= htmlspecialchars($booking['client_name'] ?? 'Client') ?></strong>
                    <span><?= ucfirst($booking['service_type']) ?> • <?= date('g:i A', strtotime($booking['date_start'])) ?></span>
                </div>
                <span class="badge badge-<?= $booking['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                    <?= ucfirst($booking['status']) ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- All Bookings Table -->
<div class="card dashboard-card">
    <div class="card-header">
        <h3>All Bookings</h3>
        <span class="text-muted"><?= count($bookings) ?> total</span>
    </div>
    <div class="card-body" style="padding: 0; overflow-x: auto;">
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <p>No bookings found</p>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-sm">
                            <div class="avatar-sm"><?= strtoupper(substr($booking['client_name'] ?? 'C', 0, 1)) ?></div>
                            <div>
                                <strong><?= htmlspecialchars($booking['client_name'] ?? 'Client') ?></strong>
                                <div class="text-muted text-sm"><?= htmlspecialchars($booking['email'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= ucfirst($booking['service_type']) ?></td>
                    <td>
                        <?= date('M j, Y', strtotime($booking['date_start'])) ?><br>
                        <span class="text-muted"><?= date('g:i A', strtotime($booking['date_start'])) ?></span>
                    </td>
                    <td><?= $booking['duration_hours'] ?> hrs</td>
                    <td>
                        <span class="badge badge-<?= match($booking['status']) {
                            'confirmed', 'paid', 'completed' => 'success',
                            'pending' => 'warning',
                            'cancelled', 'refunded' => 'error',
                            default => 'info'
                        } ?>">
                            <?= ucfirst($booking['status']) ?>
                        </span>
                    </td>
                    <td>$<?= number_format($booking['total_price'], 2) ?></td>
                    <td>
                        <div class="flex items-center gap-xs">
                            <a href="/dashboard/bookings/<?= $booking['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                            <button class="btn btn-ghost btn-sm" onclick="openActions(<?= $booking['id'] ?>)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="12" cy="5" r="1"></circle>
                                    <circle cx="12" cy="19" r="1"></circle>
                                </svg>
                            </button>
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
.upcoming-grid {
    display: grid;
    gap: 1px;
    background: var(--bg-hover);
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    padding: var(--space-lg);
    background: var(--bg-surface);
    text-decoration: none;
    color: var(--text-primary);
    transition: background var(--transition-fast);
}

.upcoming-item:hover {
    background: var(--bg-hover);
}

.upcoming-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 50px;
    padding: var(--space-sm);
    background: var(--bg-elevated);
    border-radius: var(--radius-md);
}

.upcoming-day {
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1;
}

.upcoming-month {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
}

.upcoming-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.upcoming-details span {
    font-size: 0.875rem;
    color: var(--text-muted);
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

.avatar-sm {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted);
}

.form-select, .form-input {
    background: var(--bg-elevated);
    border: 1px solid var(--bg-hover);
    color: var(--text-primary);
    padding: var(--space-sm) var(--space-md);
    border-radius: var(--radius-md);
    font-size: 0.875rem;
}

.form-select:focus, .form-input:focus {
    outline: none;
    border-color: var(--violet-500);
}
</style>

<script>
function openActions(bookingId) {
    // Open actions dropdown/modal
    console.log('Actions for booking', bookingId);
}
</script>
