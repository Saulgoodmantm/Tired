<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Main Entry Point
 * =============================================================================
 * All requests are routed through this file
 * =============================================================================
 */

// Error reporting based on environment
define('BASE_PATH', dirname(__DIR__));

// Load configuration first to check environment
$config = require BASE_PATH . '/config/app.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize core services
use App\Utils\Database;
use App\Utils\Auth;
use App\Utils\Router;
use App\Utils\View;
use App\Utils\Gate;

// Initialize database
Database::init($config['database']);

// Initialize auth
Auth::init();

// Initialize gate system
Gate::init($config['gate']);

// Initialize view system
View::setPath(BASE_PATH . '/views');
View::share('config', $config);
View::share('user', Auth::user());
View::share('gatePassed', Gate::hasPassed());

// =============================================================================
// MIDDLEWARE
// =============================================================================

// Auth middleware - require login
Router::middleware('auth', function() {
    if (!Auth::check()) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            Router::json(['error' => 'Unauthorized'], 401);
            return false;
        }
        Router::redirect('/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        return false;
    }
    return true;
});

// Admin middleware - require staff role or higher
Router::middleware('admin', function() {
    if (!Auth::isStaff()) {
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            Router::json(['error' => 'Forbidden'], 403);
            return false;
        }
        Router::redirect('/');
        return false;
    }
    return true;
});

// CSRF middleware
Router::middleware('csrf', function() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                Router::json(['error' => 'Invalid CSRF token'], 403);
                return false;
            }
            http_response_code(403);
            echo 'Invalid CSRF token';
            return false;
        }
    }
    return true;
});

// =============================================================================
// PUBLIC ROUTES
// =============================================================================

// Homepage
Router::get('/', function() use ($config) {
    // Get pinned images for slideshow
    $pinnedImages = [];
    try {
        $pinnedImages = Database::query(
            "SELECT i.*, iv.r2_url as url, iv.r2_url as thumb_url
             FROM images i
             LEFT JOIN image_versions iv ON i.id = iv.image_id AND iv.type = 'web'
             WHERE i.is_pinned = true
             ORDER BY i.pin_order ASC
             LIMIT 10"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    // Get testimonials
    $testimonials = [];
    try {
        $testimonials = Database::query(
            "SELECT * FROM testimonials WHERE is_visible = true ORDER BY display_order ASC LIMIT 5"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('pages/home', [
        'pageTitle' => 'TiredOfDoinTM - Photography',
        'pinnedImages' => $pinnedImages,
        'testimonials' => $testimonials,
    ]);
});

// Gallery
Router::get('/gallery', function() {
    $images = [];
    try {
        $images = Database::query(
            "SELECT i.*, iv.r2_url as url, iv.r2_url as thumb_url, g.category
             FROM images i
             LEFT JOIN image_versions iv ON i.id = iv.image_id AND iv.type = 'thumb'
             LEFT JOIN galleries g ON i.gallery_id = g.id
             WHERE g.type = 'public'
             ORDER BY i.created_at DESC
             LIMIT 40"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('pages/gallery', [
        'pageTitle' => 'Gallery - TiredOfDoinTM',
        'category' => 'all',
        'images' => $images,
    ]);
});

Router::get('/gallery/{category}', function($params) {
    $category = $params['category'] ?? 'all';
    $validCategories = ['personal', 'product', 'group', 'event', 'misc'];

    if (!in_array($category, $validCategories)) {
        Router::redirect('/gallery');
        return;
    }

    $images = [];
    try {
        $images = Database::query(
            "SELECT i.*, iv.r2_url as url, iv.r2_url as thumb_url
             FROM images i
             LEFT JOIN image_versions iv ON i.id = iv.image_id AND iv.type = 'thumb'
             LEFT JOIN galleries g ON i.gallery_id = g.id
             WHERE g.type = 'public' AND g.category = ?
             ORDER BY i.created_at DESC
             LIMIT 40",
            [$category]
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('pages/gallery', [
        'pageTitle' => ucfirst($category) . ' Gallery - TiredOfDoinTM',
        'category' => $category,
        'images' => $images,
    ]);
});

// Booking
Router::get('/booking', function() {
    $step = (int) ($_GET['step'] ?? 1);
    $step = max(1, min(7, $step));

    // Get available dates for calendar
    $bookedDates = [];
    try {
        $bookedDates = Database::query(
            "SELECT DATE(date_start) as date FROM bookings
             WHERE status IN ('confirmed', 'paid')
             AND date_start > NOW()
             ORDER BY date_start"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('pages/booking', [
        'pageTitle' => 'Book a Session - TiredOfDoinTM',
        'step' => $step,
        'bookedDates' => array_column($bookedDates, 'date'),
    ]);
});

// Rates
Router::get('/rates', function() {
    View::display('pages/rates', [
        'pageTitle' => 'Rates - TiredOfDoinTM',
    ]);
});

// Contact
Router::get('/contact', function() {
    View::display('pages/contact', [
        'pageTitle' => 'Contact - TiredOfDoinTM',
    ]);
});

// Privacy Policy (required for Google OAuth)
Router::get('/privacy', function() {
    View::display('pages/privacy', [
        'pageTitle' => 'Privacy Policy - TiredOfDoinTM',
    ]);
});

// Terms of Service (required for Google OAuth)
Router::get('/terms', function() {
    View::display('pages/terms', [
        'pageTitle' => 'Terms of Service - TiredOfDoinTM',
    ]);
});

// Calendar (public availability)
Router::get('/calendar', function() {
    // Get booked dates
    $bookedDates = [];
    $unavailableDates = [];

    try {
        $bookedDates = Database::query(
            "SELECT DATE(date_start) as date FROM bookings
             WHERE status IN ('confirmed', 'paid')
             AND date_start > NOW()
             AND date_start < NOW() + INTERVAL '90 days'"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('pages/calendar', [
        'pageTitle' => 'Schedule - TiredOfDoinTM',
        'bookedDates' => array_column($bookedDates, 'date'),
        'unavailableDates' => $unavailableDates,
    ]);
});

// =============================================================================
// AUTH ROUTES
// =============================================================================

// Login page
Router::get('/login', function() {
    if (Auth::check()) {
        Router::redirect('/profile');
        return;
    }

    View::display('pages/login', [
        'pageTitle' => 'Sign In - TiredOfDoinTM',
        'step' => 'email',
    ]);
});

// Request OTP
Router::post('/auth/request-otp', function() {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        View::display('pages/login', [
            'pageTitle' => 'Sign In - TiredOfDoinTM',
            'step' => 'email',
            'error' => 'Please enter a valid email address',
        ]);
        return;
    }

    // Generate and store OTP
    $otp = Auth::generateOTP();
    Auth::storeOTP($email, $otp);

    // Send email with OTP
    $emailService = new \App\Services\EmailService();
    $sent = $emailService->sendOTP($email, $otp);

    if (!$sent) {
        // Log the OTP for development fallback
        error_log("OTP for {$email}: {$otp}");
    }

    // Store email in session for verification step
    $_SESSION['otp_email'] = $email;

    View::display('pages/login', [
        'pageTitle' => 'Sign In - TiredOfDoinTM',
        'step' => 'otp',
        'email' => $email,
    ]);
}, ['csrf']);

// Verify OTP
Router::post('/auth/verify-otp', function() {
    $email = $_POST['email'] ?? $_SESSION['otp_email'] ?? '';
    $otp = strtoupper(trim($_POST['otp'] ?? ''));

    $result = Auth::verifyOTP($email, $otp);

    if ($result === 'valid') {
        // Find or create user
        $userData = Auth::findOrCreateUser($email);
        $user = $userData['user'];
        $isNew = $userData['is_new'];

        if ($isNew) {
            // Prompt for username
            $_SESSION['new_user_email'] = $email;
            View::display('pages/login', [
                'pageTitle' => 'Sign In - TiredOfDoinTM',
                'step' => 'username',
                'email' => $email,
            ]);
            return;
        }

        // Log in existing user
        Auth::login($user['id']);

        $redirect = $_GET['redirect'] ?? '/profile';
        Router::redirect($redirect);
    } else {
        $errors = [
            'expired' => 'Code expired. Please request a new one.',
            'max_attempts' => 'Too many attempts. Please request a new code.',
            'invalid' => 'Invalid code. Please try again.',
        ];

        View::display('pages/login', [
            'pageTitle' => 'Sign In - TiredOfDoinTM',
            'step' => 'otp',
            'email' => $email,
            'error' => $errors[$result] ?? 'Invalid code',
        ]);
    }
}, ['csrf']);

// Set username (new users)
Router::post('/auth/set-username', function() {
    $email = $_POST['email'] ?? $_SESSION['new_user_email'] ?? '';
    $username = trim($_POST['username'] ?? '');

    // Validate username
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        View::display('pages/login', [
            'pageTitle' => 'Sign In - TiredOfDoinTM',
            'step' => 'username',
            'email' => $email,
            'error' => 'Username must be 3-20 characters, letters/numbers/underscores only',
        ]);
        return;
    }

    // Check if username taken
    $existing = Database::queryValue("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        View::display('pages/login', [
            'pageTitle' => 'Sign In - TiredOfDoinTM',
            'step' => 'username',
            'email' => $email,
            'error' => 'Username already taken',
        ]);
        return;
    }

    // Create user with username
    $userData = Auth::findOrCreateUser($email, $username);
    Auth::login($userData['user']['id']);

    unset($_SESSION['new_user_email']);
    unset($_SESSION['otp_email']);

    Router::redirect('/profile');
}, ['csrf']);

// Google OAuth
Router::get('/auth/google', function() use ($config) {
    $params = http_build_query([
        'client_id' => $config['google']['client_id'],
        'redirect_uri' => $config['google']['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
    ]);

    Router::redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
});

Router::get('/auth/google/callback', function() use ($config) {
    $code = $_GET['code'] ?? '';

    if (!$code) {
        Router::redirect('/login?error=google_failed');
        return;
    }

    // Exchange code for tokens
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $code,
            'client_id' => $config['google']['client_id'],
            'client_secret' => $config['google']['client_secret'],
            'redirect_uri' => $config['google']['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $tokenResponse = curl_exec($ch);
    curl_close($ch);

    $tokens = json_decode($tokenResponse, true);

    if (!isset($tokens['id_token'])) {
        Router::redirect('/login?error=google_failed');
        return;
    }

    // Decode ID token payload
    $parts = explode('.', $tokens['id_token']);
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

    $email = $payload['email'] ?? '';
    $googleId = $payload['sub'] ?? '';

    if (!$email) {
        Router::redirect('/login?error=google_failed');
        return;
    }

    // Find or create user
    $userData = Auth::findOrCreateUser($email, null, $googleId);
    $user = $userData['user'];

    // Update avatar if available
    if (!empty($payload['picture']) && empty($user['avatar_url'])) {
        Database::update('users', ['avatar_url' => $payload['picture']], 'id = ?', [$user['id']]);
    }

    Auth::login($user['id']);

    $redirect = $_GET['state'] ?? '/profile';
    Router::redirect($redirect);
});

// Logout
Router::get('/logout', function() {
    Auth::logout();
    Router::redirect('/');
});

// =============================================================================
// API ROUTES
// =============================================================================

// Gallery API
Router::get('/api/gallery', function() {
    $category = $_GET['category'] ?? 'all';
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $images = [];
    $total = 0;

    try {
        $whereClause = "WHERE g.type = 'public'";
        $params = [];

        if ($category !== 'all') {
            $whereClause .= " AND g.category = ?";
            $params[] = $category;
        }

        $total = Database::queryValue(
            "SELECT COUNT(*) FROM images i
             LEFT JOIN galleries g ON i.gallery_id = g.id
             {$whereClause}",
            $params
        );

        $params[] = $perPage;
        $params[] = $offset;

        $images = Database::query(
            "SELECT i.id, i.caption, iv.r2_url as url,
                    (SELECT r2_url FROM image_versions WHERE image_id = i.id AND type = 'full' LIMIT 1) as full_url
             FROM images i
             LEFT JOIN image_versions iv ON i.id = iv.image_id AND iv.type = 'thumb'
             LEFT JOIN galleries g ON i.gallery_id = g.id
             {$whereClause}
             ORDER BY i.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    Router::json([
        'images' => $images,
        'hasMore' => ($offset + count($images)) < $total,
        'page' => $page,
        'total' => $total,
    ]);
});

// Contact form API
Router::post('/api/contact', function() {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        Router::json(['success' => false, 'error' => 'All fields are required'], 400);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Router::json(['success' => false, 'error' => 'Invalid email address'], 400);
        return;
    }

    // Store message
    try {
        // Find or create user for this email
        $user = Database::queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        $userId = $user ? $user['id'] : null;

        // Create thread
        $threadId = Database::insert('message_threads', [
            'user_id' => $userId,
            'subject' => $subject,
            'status' => 'open',
            'last_message_at' => date('Y-m-d H:i:s'),
            'unread_count' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Create message
        Database::insert('messages', [
            'thread_id' => $threadId,
            'sender_id' => $userId,
            'sender_email' => $email,
            'content' => "Name: {$name}\n\n{$message}",
            'source' => 'website',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Send notification email to admin
        $emailService = new \App\Services\EmailService();
        $emailService->sendContactNotification($name, $email, $subject, $message);

        Router::json(['success' => true, 'message' => 'Message sent successfully']);
    } catch (Exception $e) {
        Router::json(['success' => false, 'error' => 'Failed to send message'], 500);
    }
}, ['csrf']);

// Booking API - Create booking
Router::post('/api/booking', function() {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['name', 'email', 'date', 'duration', 'type'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            Router::json(['success' => false, 'error' => "Missing required field: {$field}"], 400);
            return;
        }
    }

    // Calculate pricing
    $pricing = [
        'personal' => ['2' => 200, '4' => 375, '6' => 525],
        'product' => ['2' => 250, '4' => 450, '6' => 625],
        'group' => ['2' => 300, '4' => 525, '6' => 700],
        'event' => ['2' => 350, '4' => 600, '6' => 800],
        'misc' => ['2' => 200, '4' => 375, '6' => 525],
    ];

    $type = $data['type'];
    $duration = $data['duration'];
    $basePrice = $pricing[$type][$duration] ?? 200;

    // Add-ons
    $addOnsTotal = 0;
    $addOns = [];
    if (!empty($data['addon_model'])) {
        $addOns['model'] = 75;
        $addOnsTotal += 75;
    }
    if (!empty($data['addon_rush'])) {
        $addOns['rush'] = $basePrice * 0.5;
        $addOnsTotal += $addOns['rush'];
    }
    if (!empty($data['addon_locations']) && $data['addon_locations'] > 0) {
        $addOns['extra_locations'] = $data['addon_locations'] * 50;
        $addOnsTotal += $addOns['extra_locations'];
    }

    $totalPrice = $basePrice + $addOnsTotal;
    $depositAmount = $totalPrice * 0.5;

    // Generate unique link
    $uniqueLink = bin2hex(random_bytes(16));

    try {
        // Find or create user
        $userData = Auth::findOrCreateUser($data['email'], $data['name']);
        $userId = $userData['user']['id'];

        // Parse date and time
        $dateStart = new DateTime($data['date'] . ' ' . ($data['time'] ?? '10:00'));
        $dateEnd = clone $dateStart;
        $dateEnd->modify("+{$duration} hours");

        // Create booking
        $bookingId = Database::insert('bookings', [
            'unique_link' => $uniqueLink,
            'user_id' => $userId,
            'service_type' => $type,
            'date_start' => $dateStart->format('Y-m-d H:i:s'),
            'date_end' => $dateEnd->format('Y-m-d H:i:s'),
            'duration_hours' => $duration,
            'status' => 'requested',
            'questionnaire_answers' => json_encode($data),
            'add_ons' => json_encode($addOns),
            'total_price' => $totalPrice,
            'deposit_amount' => $depositAmount,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Router::json([
            'success' => true,
            'booking_id' => $bookingId,
            'unique_link' => $uniqueLink,
            'total' => $totalPrice,
            'deposit' => $depositAmount,
        ]);
    } catch (Exception $e) {
        Router::json(['success' => false, 'error' => 'Failed to create booking: ' . $e->getMessage()], 500);
    }
}, ['csrf']);

// Stripe Payment Intent
Router::post('/api/payment/create-intent', function() use ($config) {
    $data = json_decode(file_get_contents('php://input'), true);
    $bookingId = $data['booking_id'] ?? null;

    if (!$bookingId) {
        Router::json(['error' => 'Missing booking ID'], 400);
        return;
    }

    $booking = Database::queryOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);

    if (!$booking) {
        Router::json(['error' => 'Booking not found'], 404);
        return;
    }

    // Create Stripe Payment Intent
    $stripe = new \App\Services\StripeService($config['stripe']['secret_key']);
    $intent = $stripe->createPaymentIntent(
        (int)($booking['deposit_amount'] * 100), // Amount in cents
        'usd',
        [
            'booking_id' => $bookingId,
            'type' => 'deposit',
        ]
    );

    if ($intent) {
        Router::json([
            'clientSecret' => $intent['client_secret'],
            'amount' => $booking['deposit_amount'],
        ]);
    } else {
        Router::json(['error' => 'Failed to create payment intent'], 500);
    }
}, ['csrf']);

// =============================================================================
// PROTECTED ROUTES
// =============================================================================

// Profile
Router::get('/profile', function() {
    $user = Auth::user();

    // Get user's bookings
    $bookings = Database::query(
        "SELECT * FROM bookings WHERE user_id = ? ORDER BY date_start DESC LIMIT 10",
        [$user['id']]
    );

    View::display('pages/profile', [
        'pageTitle' => 'My Profile - TiredOfDoinTM',
        'bookings' => $bookings,
    ]);
}, ['auth']);

// =============================================================================
// ADMIN/DASHBOARD ROUTES
// =============================================================================

Router::get('/dashboard', function() {
    // Get dashboard stats
    $stats = [
        'bookings_today' => 0,
        'bookings_week' => 0,
        'bookings_month' => 0,
        'revenue_year' => 0,
        'planned_earnings' => 0,
        'available_days' => 18,
    ];

    try {
        $stats['bookings_today'] = Database::queryValue(
            "SELECT COUNT(*) FROM bookings WHERE DATE(date_start) = CURRENT_DATE AND status IN ('confirmed', 'paid')"
        ) ?? 0;

        $stats['bookings_week'] = Database::queryValue(
            "SELECT COUNT(*) FROM bookings WHERE date_start >= NOW() - INTERVAL '7 days' AND status IN ('confirmed', 'paid')"
        ) ?? 0;

        $stats['bookings_month'] = Database::queryValue(
            "SELECT COUNT(*) FROM bookings WHERE date_start >= NOW() - INTERVAL '30 days' AND status IN ('confirmed', 'paid')"
        ) ?? 0;

        $stats['revenue_year'] = Database::queryValue(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'succeeded' AND created_at >= DATE_TRUNC('year', NOW())"
        ) ?? 0;

        $stats['planned_earnings'] = Database::queryValue(
            "SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status IN ('confirmed', 'paid') AND date_start > NOW()"
        ) ?? 0;
    } catch (Exception $e) {
        // Tables might not exist yet
    }

    // Get recent bookings
    $recentBookings = [];
    try {
        $recentBookings = Database::query(
            "SELECT b.*, u.username, u.email
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             ORDER BY b.created_at DESC
             LIMIT 5"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/index', [
        'pageTitle' => 'Dashboard',
        'activePage' => 'overview',
        'stats' => $stats,
        'recentBookings' => $recentBookings,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Bookings
Router::get('/dashboard/bookings', function() {
    $bookings = [];
    try {
        $bookings = Database::query(
            "SELECT b.*, u.username, u.email
             FROM bookings b
             LEFT JOIN users u ON b.user_id = u.id
             ORDER BY b.date_start DESC"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/bookings', [
        'pageTitle' => 'Bookings',
        'activePage' => 'bookings',
        'bookings' => $bookings,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Galleries
Router::get('/dashboard/galleries', function() {
    $galleries = [];
    try {
        $galleries = Database::query(
            "SELECT g.*, COUNT(i.id) as image_count
             FROM galleries g
             LEFT JOIN images i ON g.id = i.gallery_id
             GROUP BY g.id
             ORDER BY g.created_at DESC"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/galleries', [
        'pageTitle' => 'Galleries',
        'activePage' => 'galleries',
        'galleries' => $galleries,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Clients
Router::get('/dashboard/clients', function() {
    $clients = [];
    try {
        $clients = Database::query(
            "SELECT u.*, r.name as role_name,
                    (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as booking_count,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE user_id = u.id AND status = 'succeeded') as total_spent
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             ORDER BY u.created_at DESC"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/clients', [
        'pageTitle' => 'Clients',
        'activePage' => 'clients',
        'clients' => $clients,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Messages
Router::get('/dashboard/messages', function() {
    $threads = [];
    try {
        $threads = Database::query(
            "SELECT mt.*, u.username, u.email,
                    (SELECT content FROM messages WHERE thread_id = mt.id ORDER BY created_at DESC LIMIT 1) as last_message
             FROM message_threads mt
             LEFT JOIN users u ON mt.user_id = u.id
             ORDER BY mt.last_message_at DESC"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/messages', [
        'pageTitle' => 'Messages',
        'activePage' => 'messages',
        'threads' => $threads,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Calendar
Router::get('/dashboard/calendar', function() {
    View::display('dashboard/calendar', [
        'pageTitle' => 'Calendar',
        'activePage' => 'calendar',
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Contracts
Router::get('/dashboard/contracts', function() {
    View::display('dashboard/contracts', [
        'pageTitle' => 'Contracts',
        'activePage' => 'contracts',
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Billing
Router::get('/dashboard/billing', function() {
    $payments = [];
    $stats = ['total_revenue' => 0, 'pending' => 0, 'refunded' => 0];
    try {
        $payments = Database::query(
            "SELECT p.*, b.service_type, u.email as client_email
             FROM payments p
             LEFT JOIN bookings b ON p.booking_id = b.id
             LEFT JOIN users u ON b.user_id = u.id
             ORDER BY p.created_at DESC
             LIMIT 100"
        );
        $stats = Database::queryOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN status = 'succeeded' THEN amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = 'refunded' THEN refunded_amount ELSE 0 END), 0) as refunded
             FROM payments"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/billing', [
        'pageTitle' => 'Billing',
        'activePage' => 'billing',
        'payments' => $payments,
        'stats' => $stats,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Analytics
Router::get('/dashboard/analytics', function() {
    $stats = [];
    try {
        // Get various analytics
        $stats['bookings_by_month'] = Database::query(
            "SELECT DATE_TRUNC('month', date_start) as month, COUNT(*) as count
             FROM bookings WHERE date_start >= NOW() - INTERVAL '12 months'
             GROUP BY month ORDER BY month"
        );
        $stats['revenue_by_month'] = Database::query(
            "SELECT DATE_TRUNC('month', created_at) as month, SUM(amount) as total
             FROM payments WHERE status = 'succeeded' AND created_at >= NOW() - INTERVAL '12 months'
             GROUP BY month ORDER BY month"
        );
        $stats['top_services'] = Database::query(
            "SELECT service_type, COUNT(*) as count, SUM(total_price) as revenue
             FROM bookings WHERE status IN ('completed', 'paid')
             GROUP BY service_type ORDER BY count DESC"
        );
    } catch (Exception $e) {
        // Tables might not exist yet
    }

    View::display('dashboard/analytics', [
        'pageTitle' => 'Analytics',
        'activePage' => 'analytics',
        'stats' => $stats,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Locations
Router::get('/dashboard/locations', function() {
    $locations = [];
    try {
        $locations = Database::query("SELECT * FROM locations ORDER BY name");
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/locations', [
        'pageTitle' => 'Locations',
        'activePage' => 'locations',
        'locations' => $locations,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Models
Router::get('/dashboard/models', function() {
    $models = [];
    try {
        $models = Database::query(
            "SELECT m.*, u.email 
             FROM models m 
             LEFT JOIN users u ON m.user_id = u.id 
             ORDER BY m.name"
        );
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/models', [
        'pageTitle' => 'Models',
        'activePage' => 'models',
        'models' => $models,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Pricing
Router::get('/dashboard/pricing', function() {
    $packages = [];
    $addons = [];
    try {
        $packages = Database::query("SELECT * FROM pricing_packages ORDER BY sort_order");
        $addons = Database::query("SELECT * FROM pricing_addons ORDER BY name");
    } catch (Exception $e) {
        // Tables might not exist yet
    }

    View::display('dashboard/pricing', [
        'pageTitle' => 'Pricing',
        'activePage' => 'pricing',
        'packages' => $packages,
        'addons' => $addons,
    ], 'dashboard');
}, ['auth', 'admin']);

// Dashboard - Settings (Admin only)
Router::get('/dashboard/settings', function() {
    if (!Auth::isAdmin()) {
        Router::redirect('/dashboard');
        return;
    }

    $settings = [];
    try {
        $rows = Database::query("SELECT * FROM settings");
        foreach ($rows as $row) {
            $settings[$row['key']] = json_decode($row['value'], true);
        }
    } catch (Exception $e) {
        // Table might not exist yet
    }

    View::display('dashboard/settings', [
        'pageTitle' => 'Settings',
        'activePage' => 'settings',
        'settings' => $settings,
    ], 'dashboard');
}, ['auth', 'admin']);

// =============================================================================
// STRIPE WEBHOOK
// =============================================================================

Router::post('/webhook/stripe', function() use ($config) {
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($sigHeader)) {
        http_response_code(400);
        echo 'Missing signature';
        return;
    }

    $stripe = new \App\Services\StripeService();
    $event = $stripe->handleWebhook($payload, $sigHeader);

    if (!$event) {
        http_response_code(400);
        echo 'Invalid webhook';
        return;
    }

    $result = $stripe->processWebhookEvent($event);

    if ($result['handled']) {
        http_response_code(200);
        echo json_encode(['received' => true]);
    } else {
        http_response_code(200); // Still return 200 to prevent retries
        echo json_encode(['received' => true, 'message' => $result['message']]);
    }
});

// =============================================================================
// R2 IMAGE UPLOAD API (Admin only)
// =============================================================================

Router::post('/api/upload/image', function() {
    if (!Auth::isStaff()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        Router::json(['error' => 'No image uploaded'], 400);
        return;
    }

    $file = $_FILES['image'];
    $galleryId = (int) ($_POST['gallery_id'] ?? 0);

    if (!$galleryId) {
        Router::json(['error' => 'Gallery ID required'], 400);
        return;
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        Router::json(['error' => 'Invalid file type'], 400);
        return;
    }

    // Max 50MB
    if ($file['size'] > 50 * 1024 * 1024) {
        Router::json(['error' => 'File too large (max 50MB)'], 400);
        return;
    }

    try {
        // Create image record
        $imageId = Database::insert('images', [
            'gallery_id' => $galleryId,
            'uploader_id' => Auth::user()['id'],
            'filename' => $file['name'],
            'original_filename' => $file['name'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Upload to R2
        $r2 = new \App\Services\R2Service();
        $versions = $r2->uploadImage($file['tmp_name'], $galleryId, $imageId);

        // Store version URLs in database
        foreach ($versions as $type => $data) {
            Database::insert('image_versions', [
                'image_id' => $imageId,
                'type' => $type,
                'r2_path' => $data['path'],
                'r2_url' => $data['url'],
                'file_size' => $data['size'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        Router::json([
            'success' => true,
            'image_id' => $imageId,
            'versions' => $versions,
        ]);
    } catch (Exception $e) {
        Router::json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
    }
}, ['auth', 'admin']);

// =============================================================================
// GITHUB WEBHOOK (Auto-deploy from private repo)
// =============================================================================

Router::post('/webhook/github', function() use ($config) {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

    if (empty($signature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing signature']);
        return;
    }

    // Use encryption key as webhook secret
    $secret = substr(hash('sha256', $config['security']['encryption_key'] ?? 'default'), 0, 32);

    $server = new \App\Services\ServerService();
    $result = $server->handleGitHubWebhook($payload, $signature, $secret);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
});

// =============================================================================
// SERVER MANAGEMENT API (Admin only)
// =============================================================================

// Dashboard - Server Management
Router::get('/dashboard/server', function() {
    $server = new \App\Services\ServerService();

    View::display('dashboard/server', [
        'pageTitle' => 'Server Management',
        'activePage' => 'server',
        'currentBranch' => $server->getCurrentBranch(),
        'lastDeploy' => $server->getLastDeploy()['timestamp'] ?? 'Never',
        'branches' => $server->getBranches(),
        'domains' => $server->getDomains(),
    ], 'dashboard');
}, ['auth', 'admin']);

// Server Status API
Router::get('/api/server/status', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $server = new \App\Services\ServerService();
    Router::json($server->getServerStatus());
}, ['auth']);

// Deploy API
Router::post('/api/server/deploy', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $branch = $data['branch'] ?? 'main';

    $server = new \App\Services\ServerService();
    $result = $server->deploy($branch);

    Router::json($result);
}, ['auth', 'csrf']);

// Domains API
Router::get('/api/server/domains', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $server = new \App\Services\ServerService();
    Router::json(['domains' => $server->getDomains()]);
}, ['auth']);

Router::post('/api/server/domains', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $domain = $data['domain'] ?? '';

    $server = new \App\Services\ServerService();
    $result = $server->addDomain($domain);

    Router::json($result);
}, ['auth', 'csrf']);

Router::delete('/api/server/domains', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $domain = $data['domain'] ?? '';

    $server = new \App\Services\ServerService();
    $result = $server->removeDomain($domain);

    Router::json($result);
}, ['auth', 'csrf']);

// SSL API
Router::post('/api/server/ssl', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $domain = $data['domain'] ?? '';

    $server = new \App\Services\ServerService();
    $result = $server->enableSSL($domain);

    Router::json($result);
}, ['auth', 'csrf']);

// Logs API
Router::get('/api/server/logs', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }

    $type = $_GET['type'] ?? 'deploy';
    $lines = (int) ($_GET['lines'] ?? 100);

    $server = new \App\Services\ServerService();
    Router::json(['content' => $server->getLogs($type, $lines)]);
}, ['auth']);

// =============================================================================
// LOCATIONS API
// =============================================================================

Router::get('/api/locations', function() {
    if (!Auth::isStaff()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $locations = Database::query("SELECT * FROM locations ORDER BY name");
    Router::json($locations);
}, ['auth']);

Router::get('/api/locations/{id}', function($id) {
    if (!Auth::isStaff()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $location = Database::queryRow("SELECT * FROM locations WHERE id = ?", [$id]);
    if (!$location) {
        Router::json(['error' => 'Location not found'], 404);
        return;
    }
    Router::json($location);
}, ['auth']);

Router::post('/api/locations', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = Database::insert('locations', [
        'name' => $data['name'],
        'address' => $data['address'] ?? null,
        'latitude' => $data['latitude'] ?? null,
        'longitude' => $data['longitude'] ?? null,
        'description' => $data['description'] ?? null,
        'notes' => $data['notes'] ?? null,
        'is_active' => $data['is_active'] ?? true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    
    Router::json(['success' => true, 'id' => $id]);
}, ['auth', 'csrf']);

Router::put('/api/locations/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    Database::update('locations', [
        'name' => $data['name'],
        'address' => $data['address'] ?? null,
        'latitude' => $data['latitude'] ?? null,
        'longitude' => $data['longitude'] ?? null,
        'description' => $data['description'] ?? null,
        'notes' => $data['notes'] ?? null,
        'is_active' => $data['is_active'] ?? true,
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => $id]);
    
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

Router::delete('/api/locations/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    Database::delete('locations', ['id' => $id]);
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

// =============================================================================
// MODELS API
// =============================================================================

Router::post('/api/models/promote', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = (int) ($data['user_id'] ?? 0);
    
    if (!$userId) {
        Router::json(['error' => 'User ID required'], 400);
        return;
    }
    
    // Get model role ID
    $modelRole = Database::queryRow("SELECT id FROM roles WHERE name = 'model'");
    if (!$modelRole) {
        Router::json(['error' => 'Model role not found'], 500);
        return;
    }
    
    Database::update('users', ['role_id' => $modelRole['id']], ['id' => $userId]);
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

Router::post('/api/models/invite', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $name = $data['name'] ?? '';
    
    if (!$email) {
        Router::json(['error' => 'Email required'], 400);
        return;
    }
    
    // Check if user already exists
    $existing = Database::queryRow("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        Router::json(['error' => 'User with this email already exists'], 400);
        return;
    }
    
    // Get model role ID
    $modelRole = Database::queryRow("SELECT id FROM roles WHERE name = 'model'");
    
    // Create user with model role
    $userId = Database::insert('users', [
        'email' => $email,
        'username' => $name ?: null,
        'role_id' => $modelRole['id'],
        'is_verified' => false,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    // Send invitation email
    try {
        $emailService = new \App\Services\EmailService();
        $emailService->send(
            $email,
            'You\'ve been invited to join TiredProduction',
            'model_invitation',
            ['name' => $name, 'message' => $data['message'] ?? '']
        );
    } catch (Exception $e) {
        // Log but don't fail
    }
    
    Router::json(['success' => true, 'user_id' => $userId]);
}, ['auth', 'csrf']);

Router::delete('/api/models/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    // Get registered role ID
    $registeredRole = Database::queryRow("SELECT id FROM roles WHERE name = 'registered'");
    if (!$registeredRole) {
        Router::json(['error' => 'Registered role not found'], 500);
        return;
    }
    
    // Demote to registered user
    Database::update('users', ['role_id' => $registeredRole['id']], ['id' => $id]);
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

// =============================================================================
// PRICING API
// =============================================================================

Router::get('/api/pricing/packages', function() {
    $packages = Database::query("SELECT * FROM pricing_packages ORDER BY sort_order");
    Router::json($packages);
});

Router::get('/api/pricing/packages/{id}', function($id) {
    $package = Database::queryRow("SELECT * FROM pricing_packages WHERE id = ?", [$id]);
    if (!$package) {
        Router::json(['error' => 'Package not found'], 404);
        return;
    }
    Router::json($package);
});

Router::post('/api/pricing/packages', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = Database::insert('pricing_packages', [
        'name' => $data['name'],
        'price' => $data['price'],
        'base_price' => $data['price'],
        'description' => $data['description'] ?? null,
        'features' => $data['features'] ?? '[]',
        'includes' => $data['features'] ?? '[]',
        'duration_hours' => $data['duration_hours'] ?? 2,
        'is_popular' => $data['is_popular'] ?? false,
        'is_active' => $data['is_active'] ?? true,
        'sort_order' => $data['sort_order'] ?? 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    
    Router::json(['success' => true, 'id' => $id]);
}, ['auth', 'csrf']);

Router::put('/api/pricing/packages/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    Database::update('pricing_packages', [
        'name' => $data['name'],
        'price' => $data['price'],
        'base_price' => $data['price'],
        'description' => $data['description'] ?? null,
        'features' => $data['features'] ?? '[]',
        'includes' => $data['features'] ?? '[]',
        'is_popular' => $data['is_popular'] ?? false,
        'is_active' => $data['is_active'] ?? true,
        'sort_order' => $data['sort_order'] ?? 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => $id]);
    
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

Router::delete('/api/pricing/packages/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    Database::delete('pricing_packages', ['id' => $id]);
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

// Add-ons API
Router::get('/api/pricing/addons', function() {
    $addons = Database::query("SELECT * FROM pricing_addons ORDER BY sort_order");
    Router::json($addons);
});

Router::get('/api/pricing/addons/{id}', function($id) {
    $addon = Database::queryRow("SELECT * FROM pricing_addons WHERE id = ?", [$id]);
    if (!$addon) {
        Router::json(['error' => 'Add-on not found'], 404);
        return;
    }
    Router::json($addon);
});

Router::post('/api/pricing/addons', function() {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = Database::insert('pricing_addons', [
        'name' => $data['name'],
        'price' => $data['price'],
        'description' => $data['description'] ?? null,
        'is_active' => $data['is_active'] ?? true,
        'sort_order' => $data['sort_order'] ?? 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    Router::json(['success' => true, 'id' => $id]);
}, ['auth', 'csrf']);

Router::put('/api/pricing/addons/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    Database::update('pricing_addons', [
        'name' => $data['name'],
        'price' => $data['price'],
        'description' => $data['description'] ?? null,
        'is_active' => $data['is_active'] ?? true,
        'sort_order' => $data['sort_order'] ?? 0,
    ], ['id' => $id]);
    
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

Router::delete('/api/pricing/addons/{id}', function($id) {
    if (!Auth::isAdmin()) {
        Router::json(['error' => 'Unauthorized'], 403);
        return;
    }
    
    Database::delete('pricing_addons', ['id' => $id]);
    Router::json(['success' => true]);
}, ['auth', 'csrf']);

// =============================================================================
// GATE API (No auth required)
// =============================================================================

Router::post('/api/gate/verify', function() {
    $data = json_decode(file_get_contents('php://input'), true);
    $password = $data['password'] ?? '';
    
    if (Gate::verify($password)) {
        Router::json(['success' => true]);
    } else {
        Router::json(['success' => false, 'error' => 'Invalid password'], 401);
    }
});

// =============================================================================
// DISPATCH
// =============================================================================

Router::dispatch();
