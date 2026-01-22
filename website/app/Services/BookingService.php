<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Booking Service
 * =============================================================================
 * Handle booking creation, management, and scheduling
 * =============================================================================
 */

namespace App\Services;

use App\Utils\Database;

class BookingService
{
    private CalendarService $calendar;
    private StripeService $stripe;
    private EmailService $email;
    private ContractService $contracts;
    private array $appConfig;

    // Service types and their base prices
    private const SERVICE_TYPES = [
        'personal' => ['name' => 'Personal Session', 'base_price' => 150, 'min_hours' => 1],
        'portrait' => ['name' => 'Portrait Session', 'base_price' => 200, 'min_hours' => 1],
        'event' => ['name' => 'Event Coverage', 'base_price' => 300, 'min_hours' => 2],
        'wedding' => ['name' => 'Wedding Photography', 'base_price' => 500, 'min_hours' => 4],
        'commercial' => ['name' => 'Commercial Shoot', 'base_price' => 400, 'min_hours' => 2],
        'fashion' => ['name' => 'Fashion/Editorial', 'base_price' => 350, 'min_hours' => 2],
    ];

    // Hourly rate for additional hours
    private const HOURLY_RATE = 100;

    // Deposit percentage
    private const DEPOSIT_PERCENT = 50;

    public function __construct()
    {
        $this->appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $this->calendar = new CalendarService();
        $this->stripe = new StripeService();
        $this->email = new EmailService();
        $this->contracts = new ContractService();
    }

    /**
     * Get all service types
     */
    public function getServiceTypes(): array
    {
        return self::SERVICE_TYPES;
    }

    /**
     * Calculate price for a booking
     */
    public function calculatePrice(string $serviceType, int $durationHours, array $addons = []): array
    {
        $service = self::SERVICE_TYPES[$serviceType] ?? self::SERVICE_TYPES['personal'];
        
        $basePrice = $service['base_price'];
        $minHours = $service['min_hours'];
        
        // Additional hours beyond minimum
        $extraHours = max(0, $durationHours - $minHours);
        $extraCost = $extraHours * self::HOURLY_RATE;
        
        // Calculate addon costs
        $addonCost = 0;
        foreach ($addons as $addon) {
            $addonCost += $this->getAddonPrice($addon);
        }
        
        $subtotal = $basePrice + $extraCost + $addonCost;
        $deposit = $subtotal * (self::DEPOSIT_PERCENT / 100);
        
        return [
            'base_price' => $basePrice,
            'extra_hours' => $extraHours,
            'extra_cost' => $extraCost,
            'addon_cost' => $addonCost,
            'subtotal' => $subtotal,
            'deposit' => $deposit,
            'balance' => $subtotal - $deposit,
        ];
    }

    /**
     * Get addon price
     */
    private function getAddonPrice(string $addon): int
    {
        $addons = [
            'prints' => 50,
            'album' => 150,
            'rush_delivery' => 75,
            'extra_location' => 50,
            'makeup_artist' => 100,
            'styling' => 75,
        ];
        
        return $addons[$addon] ?? 0;
    }

    /**
     * Get available addons
     */
    public function getAddons(): array
    {
        return [
            'prints' => ['name' => '5 Professional Prints', 'price' => 50],
            'album' => ['name' => 'Photo Album', 'price' => 150],
            'rush_delivery' => ['name' => 'Rush Delivery (48hrs)', 'price' => 75],
            'extra_location' => ['name' => 'Additional Location', 'price' => 50],
            'makeup_artist' => ['name' => 'Makeup Artist', 'price' => 100],
            'styling' => ['name' => 'Styling Consultation', 'price' => 75],
        ];
    }

    /**
     * Check slot availability
     */
    public function checkAvailability(string $date, int $durationHours = 2): array
    {
        // Get booked slots from database
        $bookedSlots = Database::query(
            "SELECT date_start, date_end FROM bookings 
             WHERE DATE(date_start) = ? 
             AND status NOT IN ('cancelled', 'refunded')
             ORDER BY date_start",
            [$date]
        );

        // Get calendar busy times if connected
        $calendarSlots = [];
        if ($this->calendar->isConnected()) {
            $calendarSlots = $this->calendar->getAvailableSlots($date, $durationHours);
        } else {
            // Generate default slots if calendar not connected
            $calendarSlots = $this->generateDefaultSlots($date, $durationHours);
        }

        // Filter out already booked slots
        $availableSlots = [];
        foreach ($calendarSlots as $slot) {
            $slotStart = new \DateTime($slot['start']);
            $slotEnd = new \DateTime($slot['end']);
            
            $available = true;
            foreach ($bookedSlots as $booked) {
                $bookedStart = new \DateTime($booked['date_start']);
                $bookedEnd = new \DateTime($booked['date_end']);
                
                if ($slotStart < $bookedEnd && $slotEnd > $bookedStart) {
                    $available = false;
                    break;
                }
            }
            
            if ($available) {
                $availableSlots[] = $slot;
            }
        }

        return $availableSlots;
    }

    /**
     * Generate default time slots
     */
    private function generateDefaultSlots(string $date, int $durationHours): array
    {
        $slots = [];
        $dateObj = new \DateTime($date);
        
        // Working hours: 9 AM to 7 PM
        for ($hour = 9; $hour <= 19 - $durationHours; $hour++) {
            $start = clone $dateObj;
            $start->setTime($hour, 0);
            
            $end = clone $start;
            $end->add(new \DateInterval('PT' . $durationHours . 'H'));
            
            $slots[] = [
                'start' => $start->format('c'),
                'end' => $end->format('c'),
                'display' => $start->format('g:i A') . ' - ' . $end->format('g:i A'),
            ];
        }
        
        return $slots;
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data): ?array
    {
        // Validate required fields
        $required = ['user_id', 'service_type', 'date_start', 'duration_hours'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Calculate pricing
        $pricing = $this->calculatePrice(
            $data['service_type'],
            $data['duration_hours'],
            $data['addons'] ?? []
        );

        // Generate unique link
        $uniqueLink = $this->generateUniqueLink();

        // Calculate end time
        $startTime = new \DateTime($data['date_start']);
        $endTime = clone $startTime;
        $endTime->add(new \DateInterval('PT' . $data['duration_hours'] . 'H'));

        // Create booking record
        $bookingId = Database::insert('bookings', [
            'user_id' => $data['user_id'],
            'service_type' => $data['service_type'],
            'date_start' => $startTime->format('Y-m-d H:i:s'),
            'date_end' => $endTime->format('Y-m-d H:i:s'),
            'duration_hours' => $data['duration_hours'],
            'total_price' => $pricing['subtotal'],
            'deposit_amount' => $pricing['deposit'],
            'status' => 'pending',
            'unique_link' => $uniqueLink,
            'notes' => $data['notes'] ?? null,
            'addons' => json_encode($data['addons'] ?? []),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$bookingId) {
            return null;
        }

        // Get the full booking with user info
        $booking = $this->getBooking($bookingId);

        return $booking;
    }

    /**
     * Get a booking by ID
     */
    public function getBooking(int $id): ?array
    {
        return Database::queryOne(
            "SELECT b.*, u.email, u.username as client_name
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             WHERE b.id = ?",
            [$id]
        );
    }

    /**
     * Get booking by unique link
     */
    public function getBookingByLink(string $link): ?array
    {
        return Database::queryOne(
            "SELECT b.*, u.email, u.username as client_name
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             WHERE b.unique_link = ?",
            [$link]
        );
    }

    /**
     * Get bookings for a user
     */
    public function getUserBookings(int $userId): array
    {
        return Database::query(
            "SELECT * FROM bookings WHERE user_id = ? ORDER BY date_start DESC",
            [$userId]
        );
    }

    /**
     * Get all bookings (for admin)
     */
    public function getAllBookings(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'b.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'b.date_start >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'b.date_start <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['service_type'])) {
            $where[] = 'b.service_type = ?';
            $params[] = $filters['service_type'];
        }

        $whereClause = implode(' AND ', $where);
        $limit = $filters['limit'] ?? 100;

        return Database::query(
            "SELECT b.*, u.email, u.username as client_name
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             WHERE {$whereClause}
             ORDER BY b.date_start DESC
             LIMIT {$limit}",
            $params
        );
    }

    /**
     * Get upcoming bookings
     */
    public function getUpcomingBookings(int $limit = 10): array
    {
        return Database::query(
            "SELECT b.*, u.email, u.username as client_name
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             WHERE b.date_start > NOW()
             AND b.status NOT IN ('cancelled', 'refunded')
             ORDER BY b.date_start ASC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Update booking status
     */
    public function updateStatus(int $bookingId, string $status): bool
    {
        $validStatuses = ['pending', 'confirmed', 'paid', 'completed', 'cancelled', 'refunded', 'no_show'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return Database::update('bookings', [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$bookingId]) > 0;
    }

    /**
     * Process deposit payment
     */
    public function processDeposit(int $bookingId): ?array
    {
        $booking = $this->getBooking($bookingId);
        if (!$booking) {
            return null;
        }

        $depositCents = (int)($booking['deposit_amount'] * 100);

        $paymentIntent = $this->stripe->createPaymentIntent($depositCents, 'usd', [
            'booking_id' => $bookingId,
            'type' => 'deposit',
        ]);

        return $paymentIntent;
    }

    /**
     * Process remaining balance payment
     */
    public function processBalance(int $bookingId): ?array
    {
        $booking = $this->getBooking($bookingId);
        if (!$booking) {
            return null;
        }

        $balanceCents = (int)(($booking['total_price'] - $booking['deposit_amount']) * 100);

        $paymentIntent = $this->stripe->createPaymentIntent($balanceCents, 'usd', [
            'booking_id' => $bookingId,
            'type' => 'balance',
        ]);

        return $paymentIntent;
    }

    /**
     * Confirm booking after payment
     */
    public function confirmBooking(int $bookingId): bool
    {
        $booking = $this->getBooking($bookingId);
        if (!$booking) {
            return false;
        }

        // Update status
        $this->updateStatus($bookingId, 'confirmed');

        // Create calendar event
        if ($this->calendar->isConnected()) {
            $this->calendar->createBookingEvent($booking);
        }

        // Send confirmation email
        if (!empty($booking['email'])) {
            $this->email->sendBookingConfirmation($booking['email'], $booking);
        }

        return true;
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(int $bookingId, string $reason = ''): bool
    {
        $booking = $this->getBooking($bookingId);
        if (!$booking) {
            return false;
        }

        // Update status
        Database::update('bookings', [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$bookingId]);

        // Delete calendar event if exists
        if (!empty($booking['google_event_id']) && $this->calendar->isConnected()) {
            $this->calendar->deleteEvent($booking['google_event_id']);
        }

        return true;
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $bookingId, string $newDateTime): bool
    {
        $booking = $this->getBooking($bookingId);
        if (!$booking) {
            return false;
        }

        $startTime = new \DateTime($newDateTime);
        $endTime = clone $startTime;
        $endTime->add(new \DateInterval('PT' . $booking['duration_hours'] . 'H'));

        // Update booking
        Database::update('bookings', [
            'date_start' => $startTime->format('Y-m-d H:i:s'),
            'date_end' => $endTime->format('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$bookingId]);

        // Update calendar event if exists
        if (!empty($booking['google_event_id']) && $this->calendar->isConnected()) {
            $booking['date_start'] = $startTime->format('Y-m-d H:i:s');
            $this->calendar->updateBookingEvent($booking['google_event_id'], $booking);
        }

        return true;
    }

    /**
     * Generate unique booking link
     */
    private function generateUniqueLink(): string
    {
        $chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $link = '';
        for ($i = 0; $i < 12; $i++) {
            $link .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $link;
    }

    /**
     * Get booking statistics
     */
    public function getStats(string $period = 'month'): array
    {
        $dateFilter = match ($period) {
            'week' => "date_start >= DATE_SUB(NOW(), INTERVAL 1 WEEK)",
            'month' => "date_start >= DATE_SUB(NOW(), INTERVAL 1 MONTH)",
            'year' => "date_start >= DATE_SUB(NOW(), INTERVAL 1 YEAR)",
            default => "1=1",
        };

        $stats = Database::queryOne(
            "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status IN ('confirmed', 'paid') THEN 1 ELSE 0 END) as upcoming,
                SUM(total_price) as total_revenue,
                SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as earned_revenue
             FROM bookings
             WHERE {$dateFilter}"
        );

        return $stats ?: [];
    }

    /**
     * Send reminder emails for upcoming bookings
     */
    public function sendReminders(): int
    {
        // Get bookings in next 24-48 hours that haven't been reminded
        $bookings = Database::query(
            "SELECT b.*, u.email
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             WHERE b.date_start BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
             AND b.status IN ('confirmed', 'paid')
             AND b.reminder_sent = false"
        );

        $sent = 0;
        foreach ($bookings as $booking) {
            if (!empty($booking['email'])) {
                $hoursUntil = round((strtotime($booking['date_start']) - time()) / 3600);
                
                if ($this->email->sendBookingReminder($booking['email'], $booking, $hoursUntil)) {
                    Database::update('bookings', [
                        'reminder_sent' => true,
                        'reminder_sent_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$booking['id']]);
                    $sent++;
                }
            }
        }

        return $sent;
    }
}
