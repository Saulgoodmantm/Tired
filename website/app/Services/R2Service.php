<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Cloudflare R2 Storage Service
 * =============================================================================
 * S3-compatible storage operations for Cloudflare R2
 * =============================================================================
 */

namespace App\Services;

class R2Service
{
    private string $accountId;
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $endpoint;
    private string $publicUrl;
    private string $region = 'auto';

    public function __construct(array $config = [])
    {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $r2Config = $config ?: $appConfig['r2'];

        $this->accountId = $r2Config['account_id'] ?? '';
        $this->accessKey = $r2Config['access_key'] ?? '';
        $this->secretKey = $r2Config['secret_key'] ?? '';
        $this->bucket = $r2Config['bucket'] ?? 'tiredproduction';
        $this->endpoint = $r2Config['endpoint'] ?? "https://{$this->accountId}.r2.cloudflarestorage.com";
        $this->publicUrl = $r2Config['public_url'] ?? '';
    }

    /**
     * Upload a file to R2
     */
    public function upload(string $localPath, string $remotePath, ?string $contentType = null): ?array
    {
        if (!file_exists($localPath)) {
            throw new \Exception("File not found: {$localPath}");
        }

        $content = file_get_contents($localPath);
        return $this->uploadContent($content, $remotePath, $contentType);
    }

    /**
     * Upload raw content to R2
     */
    public function uploadContent(string $content, string $remotePath, ?string $contentType = null): ?array
    {
        $remotePath = ltrim($remotePath, '/');
        $contentType = $contentType ?? $this->guessContentType($remotePath);
        $contentHash = hash('sha256', $content);

        $url = "{$this->endpoint}/{$this->bucket}/{$remotePath}";
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'Content-Type' => $contentType,
            'Content-Length' => strlen($content),
            'x-amz-content-sha256' => $contentHash,
            'x-amz-date' => $date,
        ];

        $authorization = $this->signRequest('PUT', $remotePath, $headers, $content, $date, $dateShort);
        $headers['Authorization'] = $authorization;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'path' => $remotePath,
                'url' => $this->getPublicUrl($remotePath),
                'size' => strlen($content),
                'content_type' => $contentType,
            ];
        }

        error_log("R2 Upload Error: HTTP {$httpCode} - {$error} - {$response}");
        return null;
    }

    /**
     * Download a file from R2
     */
    public function download(string $remotePath): ?string
    {
        $remotePath = ltrim($remotePath, '/');
        $url = "{$this->endpoint}/{$this->bucket}/{$remotePath}";
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-amz-date' => $date,
        ];

        $authorization = $this->signRequest('GET', $remotePath, $headers, '', $date, $dateShort);
        $headers['Authorization'] = $authorization;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return $response;
        }

        return null;
    }

    /**
     * Delete a file from R2
     */
    public function delete(string $remotePath): bool
    {
        $remotePath = ltrim($remotePath, '/');
        $url = "{$this->endpoint}/{$this->bucket}/{$remotePath}";
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-amz-date' => $date,
        ];

        $authorization = $this->signRequest('DELETE', $remotePath, $headers, '', $date, $dateShort);
        $headers['Authorization'] = $authorization;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * List files in a directory
     */
    public function listFiles(string $prefix = '', int $maxKeys = 1000): array
    {
        $url = "{$this->endpoint}/{$this->bucket}?list-type=2&prefix=" . urlencode($prefix) . "&max-keys={$maxKeys}";
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-amz-date' => $date,
        ];

        $queryString = "list-type=2&max-keys={$maxKeys}&prefix=" . urlencode($prefix);
        $authorization = $this->signRequest('GET', '', $headers, '', $date, $dateShort, $queryString);
        $headers['Authorization'] = $authorization;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [];
        }

        // Parse XML response
        $xml = simplexml_load_string($response);
        $files = [];

        if ($xml && isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $files[] = [
                    'key' => (string) $content->Key,
                    'size' => (int) $content->Size,
                    'last_modified' => (string) $content->LastModified,
                    'url' => $this->getPublicUrl((string) $content->Key),
                ];
            }
        }

        return $files;
    }

    /**
     * Generate a pre-signed URL for temporary access
     */
    public function getSignedUrl(string $remotePath, int $expiresIn = 3600): string
    {
        $remotePath = ltrim($remotePath, '/');
        $expires = time() + $expiresIn;
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');

        $credential = "{$this->accessKey}/{$dateShort}/{$this->region}/s3/aws4_request";

        $queryParams = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $credential,
            'X-Amz-Date' => $date,
            'X-Amz-Expires' => $expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];

        ksort($queryParams);
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $canonicalRequest = "GET\n/{$this->bucket}/{$remotePath}\n{$queryString}\nhost:" . parse_url($this->endpoint, PHP_URL_HOST) . "\n\nhost\nUNSIGNED-PAYLOAD";
        $stringToSign = "AWS4-HMAC-SHA256\n{$date}\n{$dateShort}/{$this->region}/s3/aws4_request\n" . hash('sha256', $canonicalRequest);

        $signingKey = $this->getSigningKey($dateShort);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return "{$this->endpoint}/{$this->bucket}/{$remotePath}?{$queryString}&X-Amz-Signature={$signature}";
    }

    /**
     * Get public URL for a file
     */
    public function getPublicUrl(string $remotePath): string
    {
        $remotePath = ltrim($remotePath, '/');

        if ($this->publicUrl) {
            return rtrim($this->publicUrl, '/') . '/' . $remotePath;
        }

        return "{$this->endpoint}/{$this->bucket}/{$remotePath}";
    }

    /**
     * Check if a file exists
     */
    public function exists(string $remotePath): bool
    {
        $remotePath = ltrim($remotePath, '/');
        $url = "{$this->endpoint}/{$this->bucket}/{$remotePath}";
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');

        $headers = [
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
            'x-amz-date' => $date,
        ];

        $authorization = $this->signRequest('HEAD', $remotePath, $headers, '', $date, $dateShort);
        $headers['Authorization'] = $authorization;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Upload image and generate multiple sizes
     */
    public function uploadImage(string $localPath, int $galleryId, int $imageId): array
    {
        $versions = [];

        // Get original image info
        $imageInfo = getimagesize($localPath);
        $mimeType = $imageInfo['mime'] ?? 'image/jpeg';

        // Original (full size)
        $fullPath = "edited/{$galleryId}/{$imageId}/full.jpg";
        $result = $this->upload($localPath, $fullPath, $mimeType);
        if ($result) {
            $versions['full'] = $result;
        }

        // Generate resized versions if GD is available
        if (extension_loaded('gd')) {
            $sizes = [
                'web' => 1920,
                'mobile' => 1080,
                'thumb' => 400,
            ];

            $originalImage = $this->loadImage($localPath, $mimeType);

            if ($originalImage) {
                $origWidth = imagesx($originalImage);
                $origHeight = imagesy($originalImage);

                foreach ($sizes as $name => $maxWidth) {
                    if ($origWidth > $maxWidth) {
                        $ratio = $maxWidth / $origWidth;
                        $newWidth = $maxWidth;
                        $newHeight = (int) ($origHeight * $ratio);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resized, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

                        // Save to temp file
                        $tempFile = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
                        imagejpeg($resized, $tempFile, 85);
                        imagedestroy($resized);

                        // Upload
                        $path = "edited/{$galleryId}/{$imageId}/{$name}.jpg";
                        $result = $this->upload($tempFile, $path, 'image/jpeg');
                        if ($result) {
                            $versions[$name] = $result;
                        }

                        unlink($tempFile);
                    } else {
                        // Image smaller than target, use original
                        $path = "edited/{$galleryId}/{$imageId}/{$name}.jpg";
                        $result = $this->upload($localPath, $path, $mimeType);
                        if ($result) {
                            $versions[$name] = $result;
                        }
                    }
                }

                imagedestroy($originalImage);
            }
        }

        return $versions;
    }

    /**
     * Load image resource based on mime type
     */
    private function loadImage(string $path, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return null;
        }
    }

    /**
     * AWS Signature V4 signing
     */
    private function signRequest(string $method, string $path, array $headers, string $payload, string $date, string $dateShort, string $queryString = ''): string
    {
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $payloadHash = $payload === '' ? 'UNSIGNED-PAYLOAD' : hash('sha256', $payload);

        // Create signed headers list
        $signedHeaders = ['host', 'x-amz-content-sha256', 'x-amz-date'];
        if (isset($headers['Content-Type'])) {
            $signedHeaders[] = 'content-type';
        }
        sort($signedHeaders);
        $signedHeadersStr = implode(';', $signedHeaders);

        // Create canonical headers
        $canonicalHeaders = "host:{$host}\n";
        if (isset($headers['Content-Type'])) {
            $canonicalHeaders .= "content-type:{$headers['Content-Type']}\n";
        }
        $canonicalHeaders .= "x-amz-content-sha256:{$payloadHash}\n";
        $canonicalHeaders .= "x-amz-date:{$date}\n";

        // Create canonical request
        $canonicalUri = '/' . $this->bucket . ($path ? '/' . $path : '');
        $canonicalRequest = "{$method}\n{$canonicalUri}\n{$queryString}\n{$canonicalHeaders}\n{$signedHeadersStr}\n{$payloadHash}";

        // Create string to sign
        $scope = "{$dateShort}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$date}\n{$scope}\n" . hash('sha256', $canonicalRequest);

        // Calculate signature
        $signingKey = $this->getSigningKey($dateShort);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return "AWS4-HMAC-SHA256 Credential={$this->accessKey}/{$scope}, SignedHeaders={$signedHeadersStr}, Signature={$signature}";
    }

    /**
     * Get signing key for AWS Signature V4
     */
    private function getSigningKey(string $dateShort): string
    {
        $kDate = hash_hmac('sha256', $dateShort, "AWS4{$this->secretKey}", true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    /**
     * Format headers array for cURL
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "{$key}: {$value}";
        }
        return $formatted;
    }

    /**
     * Guess content type from file extension
     */
    private function guessContentType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
        ];

        return $types[$ext] ?? 'application/octet-stream';
    }
}
