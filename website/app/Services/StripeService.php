<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Stripe Payment Service
 * =============================================================================
 * Handle Stripe payments, webhooks, and refunds
 * =============================================================================
 */

namespace App\Services;

class StripeService
{
    private string $secretKey;
    private string $publishableKey;
    private string $webhookSecret;
    private string $apiVersion = '2023-10-16';

    public function __construct(?string $secretKey = null)
    {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $stripeConfig = $appConfig['stripe'];

        $this->secretKey = $secretKey ?? $stripeConfig['secret_key'] ?? '';
        $this->publishableKey = $stripeConfig['publishable_key'] ?? '';
        $this->webhookSecret = $stripeConfig['webhook_secret'] ?? '';
    }

    /**
     * Create a Payment Intent for a deposit or full payment
     */
    public function createPaymentIntent(int $amountCents, string $currency = 'usd', array $metadata = []): ?array
    {
        $data = [
            'amount' => $amountCents,
            'currency' => $currency,
            'payment_method_types' => ['card'],
            'metadata' => $metadata,
        ];

        $response = $this->request('POST', '/v1/payment_intents', $data);

        if ($response && isset($response['id'])) {
            return [
                'id' => $response['id'],
                'client_secret' => $response['client_secret'],
                'amount' => $response['amount'],
                'currency' => $response['currency'],
                'status' => $response['status'],
            ];
        }

        return null;
    }

    /**
     * Retrieve a Payment Intent by ID
     */
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        return $this->request('GET', "/v1/payment_intents/{$paymentIntentId}");
    }

    /**
     * Confirm a Payment Intent
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId): ?array
    {
        return $this->request('POST', "/v1/payment_intents/{$paymentIntentId}/confirm", [
            'payment_method' => $paymentMethodId,
        ]);
    }

    /**
     * Cancel a Payment Intent
     */
    public function cancelPaymentIntent(string $paymentIntentId): ?array
    {
        return $this->request('POST', "/v1/payment_intents/{$paymentIntentId}/cancel");
    }

    /**
     * Create a refund for a payment
     */
    public function refund(string $paymentIntentId, ?int $amountCents = null, string $reason = 'requested_by_customer'): ?array
    {
        $data = [
            'payment_intent' => $paymentIntentId,
            'reason' => $reason,
        ];

        if ($amountCents !== null) {
            $data['amount'] = $amountCents;
        }

        return $this->request('POST', '/v1/refunds', $data);
    }

    /**
     * Create or retrieve a customer
     */
    public function createCustomer(string $email, ?string $name = null, array $metadata = []): ?array
    {
        $data = [
            'email' => $email,
            'metadata' => $metadata,
        ];

        if ($name) {
            $data['name'] = $name;
        }

        return $this->request('POST', '/v1/customers', $data);
    }

    /**
     * Retrieve a customer by ID
     */
    public function getCustomer(string $customerId): ?array
    {
        return $this->request('GET', "/v1/customers/{$customerId}");
    }

    /**
     * Search for customer by email
     */
    public function findCustomerByEmail(string $email): ?array
    {
        $response = $this->request('GET', '/v1/customers', [
            'email' => $email,
            'limit' => 1,
        ]);

        if ($response && !empty($response['data'])) {
            return $response['data'][0];
        }

        return null;
    }

    /**
     * Create a Checkout Session for payment
     */
    public function createCheckoutSession(array $lineItems, string $successUrl, string $cancelUrl, array $options = []): ?array
    {
        $data = array_merge([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ], $options);

        return $this->request('POST', '/v1/checkout/sessions', $data);
    }

    /**
     * Verify and parse a webhook event
     */
    public function handleWebhook(string $payload, string $sigHeader): ?array
    {
        if (empty($this->webhookSecret)) {
            error_log("Stripe webhook secret not configured");
            return null;
        }

        // Verify signature
        $elements = explode(',', $sigHeader);
        $timestamp = null;
        $signature = null;

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if (count($parts) === 2) {
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signature = $parts[1];
                }
            }
        }

        if (!$timestamp || !$signature) {
            error_log("Invalid Stripe webhook signature format");
            return null;
        }

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - (int)$timestamp) > 300) {
            error_log("Stripe webhook timestamp too old");
            return null;
        }

        // Verify signature
        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            error_log("Invalid Stripe webhook signature");
            return null;
        }

        return json_decode($payload, true);
    }

    /**
     * Process webhook event and take appropriate action
     */
    public function processWebhookEvent(array $event): array
    {
        $type = $event['type'] ?? '';
        $data = $event['data']['object'] ?? [];

        switch ($type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSucceeded($data);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($data);

            case 'checkout.session.completed':
                return $this->handleCheckoutCompleted($data);

            case 'charge.refunded':
                return $this->handleRefund($data);

            default:
                return ['handled' => false, 'message' => "Unhandled event type: {$type}"];
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded(array $paymentIntent): array
    {
        $bookingId = $paymentIntent['metadata']['booking_id'] ?? null;
        $amount = $paymentIntent['amount'] / 100; // Convert cents to dollars

        if ($bookingId) {
            try {
                $db = \App\Utils\Database::class;

                // Update booking status
                $db::update('bookings', [
                    'status' => 'paid',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$bookingId]);

                // Record payment
                $db::insert('payments', [
                    'booking_id' => $bookingId,
                    'method' => 'card',
                    'provider' => 'stripe',
                    'provider_payment_id' => $paymentIntent['id'],
                    'amount' => $amount,
                    'currency' => strtoupper($paymentIntent['currency']),
                    'status' => 'succeeded',
                    'is_deposit' => ($paymentIntent['metadata']['type'] ?? '') === 'deposit',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Send confirmation email
                $booking = $db::queryOne("SELECT b.*, u.email FROM bookings b LEFT JOIN users u ON b.user_id = u.id WHERE b.id = ?", [$bookingId]);
                if ($booking && $booking['email']) {
                    $emailService = new EmailService();
                    $emailService->sendBookingConfirmation($booking['email'], $booking);
                }

                return ['handled' => true, 'message' => "Payment succeeded for booking {$bookingId}"];
            } catch (\Exception $e) {
                error_log("Error handling payment success: " . $e->getMessage());
                return ['handled' => false, 'message' => $e->getMessage()];
            }
        }

        return ['handled' => true, 'message' => 'Payment succeeded (no booking ID)'];
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(array $paymentIntent): array
    {
        $bookingId = $paymentIntent['metadata']['booking_id'] ?? null;

        if ($bookingId) {
            try {
                $db = \App\Utils\Database::class;

                $db::update('bookings', [
                    'status' => 'payment_failed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$bookingId]);

                // Record failed payment attempt
                $db::insert('payments', [
                    'booking_id' => $bookingId,
                    'method' => 'card',
                    'provider' => 'stripe',
                    'provider_payment_id' => $paymentIntent['id'],
                    'amount' => $paymentIntent['amount'] / 100,
                    'currency' => strtoupper($paymentIntent['currency']),
                    'status' => 'failed',
                    'metadata' => json_encode(['error' => $paymentIntent['last_payment_error'] ?? null]),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                return ['handled' => true, 'message' => "Payment failed for booking {$bookingId}"];
            } catch (\Exception $e) {
                error_log("Error handling payment failure: " . $e->getMessage());
                return ['handled' => false, 'message' => $e->getMessage()];
            }
        }

        return ['handled' => true, 'message' => 'Payment failed (no booking ID)'];
    }

    /**
     * Handle checkout session completed
     */
    private function handleCheckoutCompleted(array $session): array
    {
        // Similar to payment succeeded, but for Checkout Sessions
        return ['handled' => true, 'message' => 'Checkout completed'];
    }

    /**
     * Handle refund
     */
    private function handleRefund(array $charge): array
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;

        if ($paymentIntentId) {
            try {
                $db = \App\Utils\Database::class;

                $payment = $db::queryOne("SELECT * FROM payments WHERE provider_payment_id = ?", [$paymentIntentId]);

                if ($payment) {
                    $refundedAmount = ($charge['amount_refunded'] ?? 0) / 100;

                    $db::update('payments', [
                        'status' => $charge['refunded'] ? 'refunded' : 'partial_refund',
                        'refunded_amount' => $refundedAmount,
                    ], 'id = ?', [$payment['id']]);

                    if ($payment['booking_id']) {
                        $db::update('bookings', [
                            'status' => 'refunded',
                            'updated_at' => date('Y-m-d H:i:s'),
                        ], 'id = ?', [$payment['booking_id']]);
                    }
                }

                return ['handled' => true, 'message' => 'Refund processed'];
            } catch (\Exception $e) {
                error_log("Error handling refund: " . $e->getMessage());
                return ['handled' => false, 'message' => $e->getMessage()];
            }
        }

        return ['handled' => true, 'message' => 'Refund processed (no payment intent)'];
    }

    /**
     * Get the publishable key for frontend use
     */
    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * Make a request to the Stripe API
     */
    private function request(string $method, string $endpoint, array $data = []): ?array
    {
        if (empty($this->secretKey)) {
            error_log("Stripe secret key not configured");
            return null;
        }

        $url = "https://api.stripe.com{$endpoint}";

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Stripe-Version: ' . $this->apiVersion,
        ];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($this->flattenArray($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $options[CURLOPT_URL] = $url . '?' . http_build_query($data);
        } elseif ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            if (!empty($data)) {
                $options[CURLOPT_POSTFIELDS] = http_build_query($this->flattenArray($data));
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Stripe API curl error: {$error}");
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $decoded['error']['message'] ?? 'Unknown error';
            error_log("Stripe API error ({$httpCode}): {$errorMessage}");
            return null;
        }

        return $decoded;
    }

    /**
     * Flatten nested arrays for form encoding (Stripe format)
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
