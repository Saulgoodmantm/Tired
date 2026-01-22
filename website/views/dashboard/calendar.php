<?php
/**
 * Dashboard - Calendar View
 */
$activePage = 'calendar';
$pageTitle = 'Calendar';

use App\Services\CalendarService;
use App\Services\BookingService;
use App\Utils\Database;

$calendarService = new CalendarService();
$bookingService = new BookingService();

// Get current month bookings
$month = $_GET['month'] ?? date('Y-m');
$startDate = $month . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

$bookings = Database::query(
    "SELECT b.*, u.username as client_name, u.email
     FROM bookings b
     LEFT JOIN users u ON b.user_id = u.id
     WHERE DATE(b.date_start) BETWEEN ? AND ?
     AND b.status NOT IN ('cancelled', 'refunded')
     ORDER BY b.date_start",
    [$startDate, $endDate]
);

// Group bookings by date
$bookingsByDate = [];
foreach ($bookings as $booking) {
    $date = date('Y-m-d', strtotime($booking['date_start']));
    if (!isset($bookingsByDate[$date])) {
        $bookingsByDate[$date] = [];
    }
    $bookingsByDate[$date][] = $booking;
}

// Calendar navigation
$currentMonth = new DateTime($month . '-01');
$prevMonth = (clone $currentMonth)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $currentMonth)->modify('+1 month')->format('Y-m');

// Check if Google Calendar is connected
$calendarConnected = $calendarService->isConnected();
?>

<!-- Calendar Header -->
<div class="calendar-header mb-lg">
    <div class="flex items-center gap-lg">
        <a href="?month=<?= $prevMonth ?>" class="btn btn-ghost">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <h2 class="calendar-month"><?= $currentMonth->format('F Y') ?></h2>
        <a href="?month=<?= $nextMonth ?>" class="btn btn-ghost">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
    </div>

    <div class="flex items-center gap-md">
        <a href="?month=<?= date('Y-m') ?>" class="btn btn-ghost">Today</a>
        
        <?php if ($calendarConnected): ?>
            <span class="calendar-sync-status synced">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Google Calendar Synced
            </span>
        <?php else: ?>
            <a href="<?= $calendarService->getAuthUrl() ?>" class="btn btn-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                </svg>
                Connect Google Calendar
            </a>
        <?php endif; ?>
        
        <a href="/dashboard/bookings?action=new" class="btn btn-primary">+ New Booking</a>
    </div>
</div>

<!-- Calendar Grid -->
<div class="calendar-container">
    <div class="calendar-grid">
        <!-- Week day headers -->
        <div class="calendar-header-cell">Sun</div>
        <div class="calendar-header-cell">Mon</div>
        <div class="calendar-header-cell">Tue</div>
        <div class="calendar-header-cell">Wed</div>
        <div class="calendar-header-cell">Thu</div>
        <div class="calendar-header-cell">Fri</div>
        <div class="calendar-header-cell">Sat</div>

        <?php
        // Generate calendar days
        $firstDay = (clone $currentMonth)->modify('first day of this month');
        $lastDay = (clone $currentMonth)->modify('last day of this month');
        $startOfWeek = (clone $firstDay)->modify('sunday this week');
        if ($startOfWeek > $firstDay) {
            $startOfWeek->modify('-1 week');
        }
        $endOfWeek = (clone $lastDay)->modify('saturday this week');
        if ($endOfWeek < $lastDay) {
            $endOfWeek->modify('+1 week');
        }

        $day = clone $startOfWeek;
        $today = date('Y-m-d');

        while ($day <= $endOfWeek):
            $dateStr = $day->format('Y-m-d');
            $isCurrentMonth = $day->format('m') === $currentMonth->format('m');
            $isToday = $dateStr === $today;
            $isPast = $dateStr < $today;
            $dayBookings = $bookingsByDate[$dateStr] ?? [];
            ?>
            <div class="calendar-day <?= $isToday ? 'today' : '' ?> <?= !$isCurrentMonth ? 'other-month' : '' ?> <?= $isPast ? 'past' : '' ?>"
                 data-date="<?= $dateStr ?>">
                <div class="day-number"><?= $day->format('j') ?></div>
                
                <?php if (!empty($dayBookings)): ?>
                    <div class="day-events">
                        <?php foreach (array_slice($dayBookings, 0, 3) as $booking): ?>
                            <a href="/dashboard/bookings/<?= $booking['id'] ?>" 
                               class="day-event status-<?= $booking['status'] ?>"
                               title="<?= htmlspecialchars($booking['client_name'] ?? 'Client') ?> - <?= date('g:i A', strtotime($booking['date_start'])) ?>">
                                <span class="event-time"><?= date('g:i', strtotime($booking['date_start'])) ?></span>
                                <span class="event-name"><?= htmlspecialchars($booking['client_name'] ?? 'Client') ?></span>
                            </a>
                        <?php endforeach; ?>
                        
                        <?php if (count($dayBookings) > 3): ?>
                            <span class="more-events">+<?= count($dayBookings) - 3 ?> more</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            $day->modify('+1 day');
        endwhile;
        ?>
    </div>
</div>

<!-- Legend -->
<div class="calendar-legend mt-lg">
    <div class="legend-item">
        <span class="legend-color status-confirmed"></span>
        <span>Confirmed</span>
    </div>
    <div class="legend-item">
        <span class="legend-color status-pending"></span>
        <span>Pending</span>
    </div>
    <div class="legend-item">
        <span class="legend-color status-paid"></span>
        <span>Paid</span>
    </div>
    <div class="legend-item">
        <span class="legend-color status-completed"></span>
        <span>Completed</span>
    </div>
</div>

<style>
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-lg);
}

.calendar-month {
    font-size: 1.5rem;
    font-weight: 600;
    min-width: 200px;
    text-align: center;
}

.calendar-sync-status {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    font-size: 0.875rem;
    color: var(--text-muted);
}

.calendar-sync-status.synced {
    color: var(--success);
}

.calendar-container {
    background: var(--bg-surface);
    border: 1px solid var(--bg-hover);
    border-radius: var(--radius-xl);
    overflow: hidden;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-header-cell {
    padding: var(--space-md);
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-muted);
    background: var(--bg-elevated);
    border-bottom: 1px solid var(--bg-hover);
}

.calendar-day {
    min-height: 120px;
    padding: var(--space-sm);
    border-right: 1px solid var(--bg-hover);
    border-bottom: 1px solid var(--bg-hover);
    transition: background var(--transition-fast);
}

.calendar-day:nth-child(7n) {
    border-right: none;
}

.calendar-day:hover {
    background: var(--bg-hover);
}

.calendar-day.other-month {
    background: var(--bg-base);
}

.calendar-day.other-month .day-number {
    color: var(--text-muted);
}

.calendar-day.today {
    background: rgba(124, 107, 240, 0.1);
}

.calendar-day.today .day-number {
    background: var(--violet-500);
    color: white;
}

.calendar-day.past {
    opacity: 0.7;
}

.day-number {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 50%;
    margin-bottom: var(--space-xs);
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.day-event {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    padding: 2px 6px;
    font-size: 0.6875rem;
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: white;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.day-event.status-confirmed,
.day-event.status-paid {
    background: var(--success);
}

.day-event.status-pending {
    background: var(--warning);
    color: var(--bg-base);
}

.day-event.status-completed {
    background: var(--violet-500);
}

.event-time {
    font-weight: 500;
}

.event-name {
    overflow: hidden;
    text-overflow: ellipsis;
}

.more-events {
    font-size: 0.6875rem;
    color: var(--text-muted);
    padding: 2px 6px;
}

.calendar-legend {
    display: flex;
    justify-content: center;
    gap: var(--space-xl);
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-size: 0.8125rem;
    color: var(--text-muted);
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: var(--radius-sm);
}

.legend-color.status-confirmed,
.legend-color.status-paid {
    background: var(--success);
}

.legend-color.status-pending {
    background: var(--warning);
}

.legend-color.status-completed {
    background: var(--violet-500);
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
    }
    
    .day-event {
        padding: 2px 4px;
    }
    
    .event-name {
        display: none;
    }
}
</style>

<script>
// Make calendar days clickable to create booking
document.querySelectorAll('.calendar-day:not(.past)').forEach(day => {
    day.style.cursor = 'pointer';
    day.addEventListener('dblclick', () => {
        const date = day.dataset.date;
        window.location.href = `/dashboard/bookings?action=new&date=${date}`;
    });
});
</script>
