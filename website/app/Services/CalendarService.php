<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Google Calendar Integration Service
 * =============================================================================
 * Handle Google Calendar OAuth, event creation, and availability checking
 * =============================================================================
 */

namespace App\Services;

use App\Utils\Database;

class CalendarService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private array $appConfig;

    private const CALENDAR_API_BASE = 'https://www.googleapis.com/calendar/v3';
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const OAUTH_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    public function __construct()
    {
        $this->appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $googleConfig = $this->appConfig['google'] ?? [];

        $this->clientId = $googleConfig['client_id'] ?? '';
        $this->clientSecret = $googleConfig['client_secret'] ?? '';
        $this->redirectUri = ($this->appConfig['app']['url'] ?? '') . '/auth/google/calendar/callback';

        // Load stored tokens
        $this->loadTokens();
    }

    /**
     * Get OAuth authorization URL for calendar access
     */
    public function getAuthUrl(string $state = ''): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.events',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        return self::OAUTH_AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens
     */
    public function handleCallback(string $code): bool
    {
        $response = $this->request('POST', self::OAUTH_TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ], false);

        if ($response && isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->refreshToken = $response['refresh_token'] ?? $this->refreshToken;
            $this->saveTokens($response);
            return true;
        }

        return false;
    }

    /**
     * Check if calendar is connected
     */
    public function isConnected(): bool
    {
        return !empty($this->accessToken) || !empty($this->refreshToken);
    }

    /**
     * Refresh the access token
     */
    public function refreshAccessToken(): bool
    {
        if (empty($this->refreshToken)) {
            return false;
        }

        $response = $this->request('POST', self::OAUTH_TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ], false);

        if ($response && isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->saveTokens($response);
            return true;
        }

        return false;
    }

    /**
     * Create a calendar event for a booking
     */
    public function createBookingEvent(array $booking): ?array
    {
        if (!$this->ensureValidToken()) {
            return null;
        }

        $startTime = new \DateTime($booking['date_start']);
        $endTime = clone $startTime;
        $endTime->add(new \DateInterval('PT' . ($booking['duration_hours'] ?? 2) . 'H'));

        $event = [
            'summary' => 'Photo Session - ' . ($booking['client_name'] ?? 'Client'),
            'description' => $this->buildEventDescription($booking),
            'start' => [
                'dateTime' => $startTime->format('c'),
                'timeZone' => $this->appConfig['app']['timezone'] ?? 'America/New_York',
            ],
            'end' => [
                'dateTime' => $endTime->format('c'),
                'timeZone' => $this->appConfig['app']['timezone'] ?? 'America/New_York',
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60], // 1 day before
                    ['method' => 'popup', 'minutes' => 60], // 1 hour before
                ],
            ],
            'colorId' => '9', // Blue
        ];

        // Add location if available
        if (!empty($booking['location'])) {
            $event['location'] = $booking['location'];
        }

        $response = $this->request(
            'POST',
            self::CALENDAR_API_BASE . '/calendars/primary/events',
            $event,
            true,
            true
        );

        if ($response && isset($response['id'])) {
            // Store the event ID in the booking
            if (!empty($booking['id'])) {
                Database::update('bookings', [
                    'google_event_id' => $response['id'],
                ], 'id = ?', [$booking['id']]);
            }
            return $response;
        }

        return null;
    }

    /**
     * Update a calendar event
     */
    public function updateBookingEvent(string $eventId, array $booking): ?array
    {
        if (!$this->ensureValidToken()) {
            return null;
        }

        $startTime = new \DateTime($booking['date_start']);
        $endTime = clone $startTime;
        $endTime->add(new \DateInterval('PT' . ($booking['duration_hours'] ?? 2) . 'H'));

        $event = [
            'summary' => 'Photo Session - ' . ($booking['client_name'] ?? 'Client'),
            'description' => $this->buildEventDescription($booking),
            'start' => [
                'dateTime' => $startTime->format('c'),
                'timeZone' => $this->appConfig['app']['timezone'] ?? 'America/New_York',
            ],
            'end' => [
                'dateTime' => $endTime->format('c'),
                'timeZone' => $this->appConfig['app']['timezone'] ?? 'America/New_York',
            ],
        ];

        if (!empty($booking['location'])) {
            $event['location'] = $booking['location'];
        }

        return $this->request(
            'PUT',
            self::CALENDAR_API_BASE . '/calendars/primary/events/' . $eventId,
            $event,
            true,
            true
        );
    }

    /**
     * Delete a calendar event
     */
    public function deleteEvent(string $eventId): bool
    {
        if (!$this->ensureValidToken()) {
            return false;
        }

        $response = $this->request(
            'DELETE',
            self::CALENDAR_API_BASE . '/calendars/primary/events/' . $eventId
        );

        return $response !== null;
    }

    /**
     * Get events for a date range
     */
    public function getEvents(string $startDate, string $endDate): array
    {
        if (!$this->ensureValidToken()) {
            return [];
        }

        $params = [
            'timeMin' => (new \DateTime($startDate))->format('c'),
            'timeMax' => (new \DateTime($endDate))->format('c'),
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'maxResults' => 250,
        ];

        $response = $this->request(
            'GET',
            self::CALENDAR_API_BASE . '/calendars/primary/events?' . http_build_query($params)
        );

        return $response['items'] ?? [];
    }

    /**
     * Get busy times for availability checking
     */
    public function getBusyTimes(string $startDate, string $endDate): array
    {
        if (!$this->ensureValidToken()) {
            return [];
        }

        $data = [
            'timeMin' => (new \DateTime($startDate))->format('c'),
            'timeMax' => (new \DateTime($endDate))->format('c'),
            'items' => [
                ['id' => 'primary'],
            ],
        ];

        $response = $this->request(
            'POST',
            'https://www.googleapis.com/calendar/v3/freeBusy',
            $data,
            true,
            true
        );

        return $response['calendars']['primary']['busy'] ?? [];
    }

    /**
     * Check if a specific time slot is available
     */
    public function isTimeSlotAvailable(string $startTime, int $durationHours): bool
    {
        $start = new \DateTime($startTime);
        $end = clone $start;
        $end->add(new \DateInterval('PT' . $durationHours . 'H'));

        $busyTimes = $this->getBusyTimes(
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        foreach ($busyTimes as $busy) {
            $busyStart = new \DateTime($busy['start']);
            $busyEnd = new \DateTime($busy['end']);

            // Check for overlap
            if ($start < $busyEnd && $end > $busyStart) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available time slots for a date
     */
    public function getAvailableSlots(string $date, int $durationHours = 2): array
    {
        $busyTimes = $this->getBusyTimes($date, $date);
        
        // Define working hours (9 AM to 6 PM)
        $workStart = 9;
        $workEnd = 18;
        
        $slots = [];
        $dateObj = new \DateTime($date);
        
        for ($hour = $workStart; $hour <= $workEnd - $durationHours; $hour++) {
            $slotStart = clone $dateObj;
            $slotStart->setTime($hour, 0);
            
            $slotEnd = clone $slotStart;
            $slotEnd->add(new \DateInterval('PT' . $durationHours . 'H'));
            
            $available = true;
            foreach ($busyTimes as $busy) {
                $busyStart = new \DateTime($busy['start']);
                $busyEnd = new \DateTime($busy['end']);
                
                if ($slotStart < $busyEnd && $slotEnd > $busyStart) {
                    $available = false;
                    break;
                }
            }
            
            if ($available) {
                $slots[] = [
                    'start' => $slotStart->format('c'),
                    'end' => $slotEnd->format('c'),
                    'display' => $slotStart->format('g:i A') . ' - ' . $slotEnd->format('g:i A'),
                ];
            }
        }
        
        return $slots;
    }

    /**
     * Build event description from booking data
     */
    private function buildEventDescription(array $booking): string
    {
        $lines = [];
        $lines[] = "📸 Photography Session";
        $lines[] = "";
        
        if (!empty($booking['service_type'])) {
            $lines[] = "Type: " . ucfirst($booking['service_type']);
        }
        if (!empty($booking['client_email'])) {
            $lines[] = "Client: " . $booking['client_email'];
        }
        if (!empty($booking['notes'])) {
            $lines[] = "";
            $lines[] = "Notes: " . $booking['notes'];
        }
        
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "Managed by TiredOfDoinTM";
        
        return implode("\n", $lines);
    }

    /**
     * Ensure we have a valid access token
     */
    private function ensureValidToken(): bool
    {
        if (!empty($this->accessToken)) {
            return true;
        }

        if (!empty($this->refreshToken)) {
            return $this->refreshAccessToken();
        }

        return false;
    }

    /**
     * Load tokens from database
     */
    private function loadTokens(): void
    {
        try {
            $tokens = Database::queryOne(
                "SELECT * FROM settings WHERE key = 'google_calendar_tokens'"
            );

            if ($tokens && !empty($tokens['value'])) {
                $data = json_decode($tokens['value'], true);
                $this->accessToken = $data['access_token'] ?? null;
                $this->refreshToken = $data['refresh_token'] ?? null;

                // Check if token is expired
                if (!empty($data['expires_at']) && time() > $data['expires_at']) {
                    $this->accessToken = null;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }
    }

    /**
     * Save tokens to database
     */
    private function saveTokens(array $tokenResponse): void
    {
        $data = [
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'] ?? $this->refreshToken,
            'expires_at' => time() + ($tokenResponse['expires_in'] ?? 3600),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $existing = Database::queryOne(
                "SELECT * FROM settings WHERE key = 'google_calendar_tokens'"
            );

            if ($existing) {
                Database::update('settings', [
                    'value' => json_encode($data),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], "key = ?", ['google_calendar_tokens']);
            } else {
                Database::insert('settings', [
                    'key' => 'google_calendar_tokens',
                    'value' => json_encode($data),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            error_log("Failed to save calendar tokens: " . $e->getMessage());
        }
    }

    /**
     * Make HTTP request
     */
    private function request(
        string $method,
        string $url,
        array $data = [],
        bool $authenticated = true,
        bool $jsonBody = false
    ): ?array {
        $ch = curl_init();

        $headers = [];
        if ($authenticated && $this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            
            if ($jsonBody) {
                $headers[] = 'Content-Type: application/json';
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            } else {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $options[CURLOPT_POSTFIELDS] = http_build_query($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        if (!empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Calendar API curl error: {$error}");
            return null;
        }

        // DELETE returns 204 No Content
        if ($method === 'DELETE' && $httpCode === 204) {
            return [];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown error';
            error_log("Calendar API error ({$httpCode}): {$errorMessage}");
            
            // Token expired, try to refresh
            if ($httpCode === 401 && $authenticated) {
                if ($this->refreshAccessToken()) {
                    // Retry the request
                    return $this->request($method, $url, $data, $authenticated, $jsonBody);
                }
            }
            
            return null;
        }

        return $decoded ?? [];
    }

    /**
     * Disconnect calendar (revoke tokens)
     */
    public function disconnect(): bool
    {
        try {
            Database::query("DELETE FROM settings WHERE key = 'google_calendar_tokens'");
            $this->accessToken = null;
            $this->refreshToken = null;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
