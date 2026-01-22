<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Contract & Document Service
 * =============================================================================
 * Handle contract generation, e-signatures, and PDF creation
 * Built in-house - NO DocuSign
 * =============================================================================
 */

namespace App\Services;

use App\Utils\Database;

class ContractService
{
    private string $storagePath;
    private array $appConfig;

    public function __construct()
    {
        $this->appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $this->storagePath = dirname(__DIR__, 2) . '/storage/contracts';
        
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Get all contract templates
     */
    public function getTemplates(bool $activeOnly = true): array
    {
        $where = $activeOnly ? "WHERE is_active = true" : "";
        return Database::query("SELECT * FROM contract_templates {$where} ORDER BY name");
    }

    /**
     * Get a single template by ID
     */
    public function getTemplate(int $id): ?array
    {
        return Database::queryOne("SELECT * FROM contract_templates WHERE id = ?", [$id]);
    }

    /**
     * Create a new contract template
     */
    public function createTemplate(string $name, string $type, string $content, array $clauses = []): int
    {
        return Database::insert('contract_templates', [
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'clauses' => json_encode($clauses),
            'is_active' => 'true',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a template
     */
    public function updateTemplate(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Database::update('contract_templates', $data, 'id = ?', [$id]) > 0;
    }

    /**
     * Generate a contract from a template with variable substitution
     */
    public function generateContract(int $templateId, int $bookingId, int $userId, array $variables = []): ?int
    {
        $template = $this->getTemplate($templateId);
        if (!$template) {
            return null;
        }

        // Get booking and user data for auto-fill
        $booking = Database::queryOne(
            "SELECT b.*, u.email, u.username 
             FROM bookings b 
             LEFT JOIN users u ON b.user_id = u.id 
             WHERE b.id = ?",
            [$bookingId]
        );

        $user = Database::queryOne("SELECT * FROM users WHERE id = ?", [$userId]);

        // Build default variables
        $defaultVars = [
            'client_name' => $user['username'] ?? '',
            'client_email' => $user['email'] ?? '',
            'date' => date('F j, Y'),
            'booking_date' => $booking ? date('F j, Y', strtotime($booking['date_start'])) : '',
            'booking_time' => $booking ? date('g:i A', strtotime($booking['date_start'])) : '',
            'shoot_type' => $booking['service_type'] ?? '',
            'duration' => ($booking['duration_hours'] ?? 0) . ' hours',
            'location' => '', // Will be filled from location_id if exists
            'price' => '$' . number_format($booking['total_price'] ?? 0, 2),
            'deposit' => '$' . number_format($booking['deposit_amount'] ?? 0, 2),
            'photographer_name' => 'TiredOfDoinTM',
            'company_name' => 'TiredOfDoinTM',
            'year' => date('Y'),
        ];

        // Merge with provided variables
        $variables = array_merge($defaultVars, $variables);

        // Substitute variables in content
        $content = $template['content'];
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        // Create the contract
        $contractId = Database::insert('contracts', [
            'template_id' => $templateId,
            'booking_id' => $bookingId,
            'user_id' => $userId,
            'title' => $template['name'] . ' - ' . ($user['username'] ?? 'Client'),
            'content' => $content,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $contractId;
    }

    /**
     * Get a contract by ID
     */
    public function getContract(int $id): ?array
    {
        return Database::queryOne(
            "SELECT c.*, t.name as template_name, t.type as template_type,
                    u.username, u.email
             FROM contracts c
             LEFT JOIN contract_templates t ON c.template_id = t.id
             LEFT JOIN users u ON c.user_id = u.id
             WHERE c.id = ?",
            [$id]
        );
    }

    /**
     * Get contracts for a user
     */
    public function getContractsForUser(int $userId): array
    {
        return Database::query(
            "SELECT c.*, t.name as template_name, t.type as template_type
             FROM contracts c
             LEFT JOIN contract_templates t ON c.template_id = t.id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get contracts for a booking
     */
    public function getContractsForBooking(int $bookingId): array
    {
        return Database::query(
            "SELECT c.*, t.name as template_name
             FROM contracts c
             LEFT JOIN contract_templates t ON c.template_id = t.id
             WHERE c.booking_id = ?
             ORDER BY c.created_at DESC",
            [$bookingId]
        );
    }

    /**
     * Get all contracts (for admin)
     */
    public function getAllContracts(string $status = null, int $limit = 50): array
    {
        $where = $status ? "WHERE c.status = ?" : "";
        $params = $status ? [$status] : [];
        
        return Database::query(
            "SELECT c.*, t.name as template_name, t.type as template_type,
                    u.username, u.email
             FROM contracts c
             LEFT JOIN contract_templates t ON c.template_id = t.id
             LEFT JOIN users u ON c.user_id = u.id
             {$where}
             ORDER BY c.created_at DESC
             LIMIT {$limit}",
            $params
        );
    }

    /**
     * Mark contract as sent
     */
    public function markAsSent(int $contractId): bool
    {
        return Database::update('contracts', [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$contractId]) > 0;
    }

    /**
     * Mark contract as viewed
     */
    public function markAsViewed(int $contractId): bool
    {
        $contract = $this->getContract($contractId);
        if ($contract && empty($contract['viewed_at'])) {
            return Database::update('contracts', [
                'status' => 'viewed',
                'viewed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$contractId]) > 0;
        }
        return true;
    }

    /**
     * Sign a contract with e-signature
     */
    public function signContract(int $contractId, array $signatureData): bool
    {
        $contract = $this->getContract($contractId);
        if (!$contract) {
            return false;
        }

        // Validate signature data
        if (empty($signatureData['signature']) && empty($signatureData['typed_name'])) {
            return false;
        }

        // Build signature record
        $signatureRecord = [
            'type' => !empty($signatureData['signature']) ? 'drawn' : 'typed',
            'signature' => $signatureData['signature'] ?? null,
            'typed_name' => $signatureData['typed_name'] ?? null,
            'font' => $signatureData['font'] ?? 'default',
            'timestamp' => date('Y-m-d H:i:s'),
            'timestamp_utc' => gmdate('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'document_hash' => hash('sha256', $contract['content']),
            'agreed_terms' => $signatureData['agreed_terms'] ?? false,
        ];

        // Generate PDF with signature
        $pdfPath = $this->generateSignedPDF($contract, $signatureRecord);

        // Update contract
        $updated = Database::update('contracts', [
            'signature_data' => json_encode($signatureRecord),
            'status' => 'signed',
            'signed_at' => date('Y-m-d H:i:s'),
            'pdf_path' => $pdfPath,
        ], 'id = ?', [$contractId]);

        if ($updated) {
            // Log the signature event
            $this->logSignatureEvent($contractId, $signatureRecord);
            
            // Send confirmation email
            $this->sendSignatureConfirmation($contract);
        }

        return $updated > 0;
    }

    /**
     * Generate a PDF of the signed contract
     */
    private function generateSignedPDF(array $contract, array $signature): string
    {
        // Generate unique filename
        $filename = 'contract_' . $contract['id'] . '_' . date('Ymd_His') . '.pdf';
        $filepath = $this->storagePath . '/' . $filename;

        // Build PDF content (simple HTML to PDF conversion)
        $html = $this->buildPDFContent($contract, $signature);
        
        // Save as HTML (for now - can integrate proper PDF library later)
        $htmlPath = str_replace('.pdf', '.html', $filepath);
        file_put_contents($htmlPath, $html);

        // Try to use wkhtmltopdf if available
        if ($this->hasWkhtmltopdf()) {
            exec("wkhtmltopdf --quiet {$htmlPath} {$filepath}");
            if (file_exists($filepath)) {
                unlink($htmlPath);
                return $filename;
            }
        }

        // Fallback: keep HTML version
        return str_replace('.pdf', '.html', $filename);
    }

    /**
     * Check if wkhtmltopdf is available
     */
    private function hasWkhtmltopdf(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('which wkhtmltopdf 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Build PDF content HTML
     */
    private function buildPDFContent(array $contract, array $signature): string
    {
        $signatureHtml = '';
        if ($signature['type'] === 'drawn' && !empty($signature['signature'])) {
            $signatureHtml = "<img src='{$signature['signature']}' style='max-width: 300px; height: auto;' alt='Signature'>";
        } else {
            $fontStyle = $this->getSignatureFont($signature['font']);
            $signatureHtml = "<div style='{$fontStyle}'>{$signature['typed_name']}</div>";
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>{$contract['title']}</title>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.6; padding: 40px; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .content { white-space: pre-wrap; }
        .signature-block { margin-top: 60px; padding-top: 20px; border-top: 1px solid #ccc; }
        .signature-line { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-item { width: 45%; }
        .signature-item .line { border-bottom: 1px solid #000; height: 60px; display: flex; align-items: flex-end; padding-bottom: 5px; }
        .signature-item .label { font-size: 12px; color: #666; margin-top: 5px; }
        .metadata { margin-top: 40px; font-size: 10px; color: #888; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>TiredOfDoinTM</h1>
        <h2>{$contract['title']}</h2>
    </div>
    
    <div class='content'>
" . nl2br(htmlspecialchars($contract['content'])) . "
    </div>
    
    <div class='signature-block'>
        <h3>Signatures</h3>
        <div class='signature-line'>
            <div class='signature-item'>
                <div class='line'>{$signatureHtml}</div>
                <div class='label'>Client Signature - {$signature['typed_name'] ?? ''}</div>
                <div class='label'>Date: {$signature['timestamp']}</div>
            </div>
            <div class='signature-item'>
                <div class='line'></div>
                <div class='label'>TiredOfDoinTM</div>
                <div class='label'>Date: {$signature['timestamp']}</div>
            </div>
        </div>
    </div>
    
    <div class='metadata'>
        <p>Document ID: {$contract['id']}</p>
        <p>Document Hash: {$signature['document_hash']}</p>
        <p>Signed at: {$signature['timestamp_utc']} UTC</p>
        <p>IP Address: {$signature['ip_address']}</p>
        <p>This document is electronically signed in accordance with the E-Sign Act.</p>
    </div>
</body>
</html>
        ";
    }

    /**
     * Get CSS for signature fonts
     */
    private function getSignatureFont(string $font): string
    {
        $fonts = [
            'default' => "font-family: 'Brush Script MT', cursive; font-size: 28px;",
            'formal' => "font-family: 'Great Vibes', cursive; font-size: 32px;",
            'casual' => "font-family: 'Dancing Script', cursive; font-size: 28px;",
            'print' => "font-family: 'Times New Roman', serif; font-size: 24px; font-style: italic;",
        ];

        return $fonts[$font] ?? $fonts['default'];
    }

    /**
     * Log signature event for audit trail
     */
    private function logSignatureEvent(int $contractId, array $signature): void
    {
        try {
            Database::insert('analytics_events', [
                'event_type' => 'contract_signed',
                'user_id' => Database::queryValue("SELECT user_id FROM contracts WHERE id = ?", [$contractId]),
                'resource_type' => 'contract',
                'resource_id' => $contractId,
                'ip_address' => $signature['ip_address'],
                'user_agent' => $signature['user_agent'],
                'metadata' => json_encode([
                    'signature_type' => $signature['type'],
                    'document_hash' => $signature['document_hash'],
                ]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log signature event: " . $e->getMessage());
        }
    }

    /**
     * Send signature confirmation email
     */
    private function sendSignatureConfirmation(array $contract): void
    {
        try {
            $emailService = new EmailService();
            $appUrl = $this->appConfig['app']['url'] ?? 'https://tiredofdointm.com';
            
            $subject = "Contract Signed - {$contract['title']}";
            $html = "
                <html><body style='font-family: sans-serif; background: #0a0a0a; color: #fff; padding: 20px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #111; padding: 40px; border-radius: 12px;'>
                    <h1 style='text-align: center;'>TiredOfDoinTM</h1>
                    <h2>Contract Signed Successfully ✅</h2>
                    <p>The following contract has been signed:</p>
                    <p><strong>{$contract['title']}</strong></p>
                    <p>Signed on: " . date('F j, Y \a\t g:i A') . "</p>
                    <p>A copy of the signed document is available in your account.</p>
                    <p style='text-align: center; margin-top: 30px;'>
                        <a href='{$appUrl}/contracts/{$contract['id']}' style='background: #fff; color: #000; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 600;'>View Contract</a>
                    </p>
                </div>
                </body></html>
            ";
            
            $emailService->send($contract['email'], $subject, $html);
        } catch (\Exception $e) {
            error_log("Failed to send signature confirmation: " . $e->getMessage());
        }
    }

    /**
     * Send contract to client for signing
     */
    public function sendContractToClient(int $contractId): bool
    {
        $contract = $this->getContract($contractId);
        if (!$contract || empty($contract['email'])) {
            return false;
        }

        $appUrl = $this->appConfig['app']['url'] ?? 'https://tiredofdointm.com';
        $signUrl = "{$appUrl}/contract/sign/{$contractId}";

        $emailService = new EmailService();
        $subject = "Contract Ready for Signature - {$contract['title']}";
        $html = "
            <html><body style='font-family: sans-serif; background: #0a0a0a; color: #fff; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #111; padding: 40px; border-radius: 12px;'>
                <h1 style='text-align: center;'>TiredOfDoinTM</h1>
                <h2>Contract Ready for Your Signature</h2>
                <p>A contract is waiting for your signature:</p>
                <p><strong>{$contract['title']}</strong></p>
                <p>Please review and sign the contract to confirm your booking.</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$signUrl}' style='background: #fff; color: #000; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 600;'>Review & Sign Contract</a>
                </p>
                <p style='font-size: 12px; color: #888;'>This contract must be signed before your session can be confirmed.</p>
            </div>
            </body></html>
        ";

        $sent = $emailService->send($contract['email'], $subject, $html);

        if ($sent) {
            $this->markAsSent($contractId);
        }

        return $sent;
    }

    /**
     * Get default contract templates
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'Photography Client Agreement',
                'type' => 'client',
                'content' => self::getClientAgreementTemplate(),
            ],
            [
                'name' => 'Model Release',
                'type' => 'model_release',
                'content' => self::getModelReleaseTemplate(),
            ],
            [
                'name' => 'Copyright License',
                'type' => 'copyright',
                'content' => self::getCopyrightLicenseTemplate(),
            ],
        ];
    }

    /**
     * Client Agreement Template
     */
    private static function getClientAgreementTemplate(): string
    {
        return <<<EOT
PHOTOGRAPHY CLIENT AGREEMENT

This Photography Services Agreement ("Agreement") is entered into on {{date}} between:

PHOTOGRAPHER: TiredOfDoinTM ("Photographer")
CLIENT: {{client_name}} ("Client")
Email: {{client_email}}

1. SERVICES
The Photographer agrees to provide photography services as described:
- Session Type: {{shoot_type}}
- Date: {{booking_date}}
- Time: {{booking_time}}
- Duration: {{duration}}
- Location: {{location}}

2. COMPENSATION
Total Fee: {{price}}
Deposit Required: {{deposit}} (50% non-refundable)
Balance Due: Upon delivery of final images

3. CANCELLATION POLICY
- Cancellations more than 48 hours before session: Deposit forfeited, no additional charges
- Cancellations within 48 hours: Full payment required
- Rescheduling: One reschedule permitted with 48+ hours notice

4. DELIVERABLES
The Photographer will deliver edited digital images within 2-4 weeks of the session date.

5. IMAGE USAGE
Client grants Photographer permission to use images for portfolio, social media, and promotional purposes unless otherwise specified in writing.

6. LIMITATION OF LIABILITY
Photographer's liability is limited to the total amount paid for services.

7. AGREEMENT
By signing below, both parties agree to the terms of this Agreement.

Client Name: {{client_name}}
Date: {{date}}
EOT;
    }

    /**
     * Model Release Template
     */
    private static function getModelReleaseTemplate(): string
    {
        return <<<EOT
MODEL RELEASE AGREEMENT

Date: {{date}}

I, {{client_name}} ("Model"), hereby grant {{company_name}} ("Photographer"), its representatives, successors, and assigns, the irrevocable and unrestricted right to use and publish photographs of me, or in which I may be included, for editorial, trade, advertising, and any other purpose, in any manner and medium, and to alter the same without restriction.

I hereby release and agree to hold harmless the Photographer from any liability by virtue of any blurring, distortion, alteration, optical illusion, or use in composite form, whether intentional or otherwise, that may occur or be produced in the taking of said pictures or in any subsequent processing thereof.

I affirm that I am over the age of 18 (or have parental/guardian consent if under 18) and that I have the right to contract in my own name.

I have read this release before signing below and I fully understand the contents, meaning, and impact of this release.

Model Name: {{client_name}}
Model Email: {{client_email}}
Date: {{date}}

Photographer: {{company_name}}
EOT;
    }

    /**
     * Copyright License Template
     */
    private static function getCopyrightLicenseTemplate(): string
    {
        return <<<EOT
COPYRIGHT LICENSE AGREEMENT

This Copyright License Agreement is entered into on {{date}} between:

LICENSOR: {{company_name}} ("Photographer")
LICENSEE: {{client_name}} ("Client")

1. GRANT OF LICENSE
The Photographer grants to Client a non-exclusive license to use the photographs from the session dated {{booking_date}} for personal, non-commercial purposes.

2. RESTRICTIONS
Client may NOT:
- Sell, license, or transfer the images to third parties
- Use images for commercial purposes without written permission
- Edit, alter, or manipulate images without permission
- Remove watermarks or metadata

3. COPYRIGHT OWNERSHIP
All photographs remain the exclusive property of the Photographer. This license grants usage rights only, not ownership.

4. CREDIT
When images are shared publicly (social media, websites), Client agrees to credit: @tiredofdointm

5. DURATION
This license is valid for personal use in perpetuity, unless revoked due to breach of terms.

6. COMMERCIAL LICENSING
For commercial use rights, Client must obtain a separate commercial license and pay applicable fees.

Client Name: {{client_name}}
Date: {{date}}

Photographer: {{company_name}}
© {{year}} TiredOfDoinTM. All rights reserved.
EOT;
    }

    /**
     * Seed default templates
     */
    public function seedDefaultTemplates(): void
    {
        $templates = self::getDefaultTemplates();
        
        foreach ($templates as $template) {
            $existing = Database::queryOne(
                "SELECT id FROM contract_templates WHERE name = ?",
                [$template['name']]
            );
            
            if (!$existing) {
                $this->createTemplate(
                    $template['name'],
                    $template['type'],
                    $template['content']
                );
            }
        }
    }
}
