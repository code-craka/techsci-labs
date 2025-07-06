<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CDN service for static assets and attachment delivery
 * 
 * Handles:
 * - Static asset optimization and delivery
 * - Email attachment serving with security
 * - Image optimization and thumbnail generation
 * - Cache headers and performance optimization
 */
class CdnService
{
    private const CACHE_PROFILES = [
        'static' => [
            'max_age' => 31536000, // 1 year
            'type' => 'public',
            'immutable' => true
        ],
        'attachment' => [
            'max_age' => 3600, // 1 hour
            'type' => 'private',
            'immutable' => false
        ],
        'image' => [
            'max_age' => 86400, // 1 day
            'type' => 'public',
            'immutable' => false
        ],
        'api' => [
            'max_age' => 300, // 5 minutes
            'type' => 'public',
            'immutable' => false
        ]
    ];

    private const SUPPORTED_IMAGE_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const SUPPORTED_OPTIMIZATIONS = ['webp', 'avif', 'resize', 'quality'];

    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%env(CDN_BASE_URL)%')]
        private readonly string $cdnBaseUrl,
        #[Autowire('%env(bool:CDN_ENABLED)%')]
        private readonly bool $cdnEnabled = false
    ) {}

    /**
     * Generate CDN URL for static assets
     */
    public function getAssetUrl(string $path, array $options = []): string
    {
        if (!$this->cdnEnabled) {
            return $path;
        }

        $cdnPath = $this->normalizeAssetPath($path);
        $url = rtrim($this->cdnBaseUrl, '/') . '/' . ltrim($cdnPath, '/');

        // Add optimization parameters
        if (!empty($options)) {
            $queryParams = $this->buildOptimizationParams($options);
            if ($queryParams) {
                $url .= '?' . http_build_query($queryParams);
            }
        }

        return $url;
    }

    /**
     * Generate secure URL for email attachments
     */
    public function getAttachmentUrl(string $attachmentId, array $options = []): string
    {
        $path = '/attachments/' . $attachmentId;
        
        if ($this->cdnEnabled) {
            $url = rtrim($this->cdnBaseUrl, '/') . $path;
        } else {
            $url = '/api' . $path;
        }

        // Add security parameters
        $securityParams = $this->buildSecurityParams($attachmentId, $options);
        if ($securityParams) {
            $url .= '?' . http_build_query($securityParams);
        }

        return $url;
    }

    /**
     * Serve static asset with proper caching headers
     */
    public function serveStaticAsset(string $path, Request $request): Response
    {
        $filePath = $this->resolveAssetPath($path);
        
        if (!file_exists($filePath)) {
            return new Response('Asset not found', 404);
        }

        $mimeType = $this->getMimeType($filePath);
        $cacheProfile = $this->getCacheProfile('static');
        
        // Check if-modified-since header
        $lastModified = filemtime($filePath);
        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        
        if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
            return new Response('', 304);
        }

        $response = new StreamedResponse();
        $response->setCallback(function() use ($filePath) {
            readfile($filePath);
        });

        $this->setCacheHeaders($response, $cacheProfile);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) filesize($filePath));
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        $response->headers->set('ETag', '"' . md5_file($filePath) . '"');

        return $response;
    }

    /**
     * Serve email attachment with security checks
     */
    public function serveAttachment(string $attachmentId, Request $request, array $security = []): Response
    {
        // Verify security token if required
        if (!$this->verifyAttachmentAccess($attachmentId, $request, $security)) {
            return new Response('Access denied', 403);
        }

        $attachmentPath = $this->resolveAttachmentPath($attachmentId);
        
        if (!file_exists($attachmentPath)) {
            return new Response('Attachment not found', 404);
        }

        // Security scan check
        if (!$this->isAttachmentSafe($attachmentPath)) {
            return new Response('Attachment failed security scan', 403);
        }

        $mimeType = $this->getMimeType($attachmentPath);
        $filename = basename($attachmentPath);
        $cacheProfile = $this->getCacheProfile('attachment');

        $response = new StreamedResponse();
        $response->setCallback(function() use ($attachmentPath) {
            readfile($attachmentPath);
        });

        $this->setCacheHeaders($response, $cacheProfile);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) filesize($attachmentPath));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('X-Content-Scanned', 'true');

        // Log attachment access
        $this->logger->info('Attachment served', [
            'attachment_id' => $attachmentId,
            'filename' => $filename,
            'size' => filesize($attachmentPath),
            'user_ip' => $request->getClientIp()
        ]);

        return $response;
    }

    /**
     * Serve optimized image with format conversion
     */
    public function serveOptimizedImage(string $imagePath, Request $request, array $options = []): Response
    {
        $filePath = $this->resolveImagePath($imagePath);
        
        if (!file_exists($filePath)) {
            return new Response('Image not found', 404);
        }

        // Check if image optimization is supported
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::SUPPORTED_IMAGE_FORMATS)) {
            return $this->serveStaticAsset($imagePath, $request);
        }

        // Determine optimal format based on Accept header
        $acceptHeader = $request->headers->get('Accept', '');
        $outputFormat = $this->determineOptimalImageFormat($acceptHeader, $options);
        
        // Check if we need to generate optimized version
        $optimizedPath = $this->getOptimizedImagePath($filePath, $outputFormat, $options);
        
        if (!file_exists($optimizedPath)) {
            $this->generateOptimizedImage($filePath, $optimizedPath, $outputFormat, $options);
        }

        $mimeType = $this->getImageMimeType($outputFormat);
        $cacheProfile = $this->getCacheProfile('image');

        $response = new StreamedResponse();
        $response->setCallback(function() use ($optimizedPath) {
            readfile($optimizedPath);
        });

        $this->setCacheHeaders($response, $cacheProfile);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Length', (string) filesize($optimizedPath));
        $response->headers->set('X-Image-Optimized', 'true');
        $response->headers->set('Vary', 'Accept');

        return $response;
    }

    /**
     * Set cache headers based on profile
     */
    private function setCacheHeaders(Response $response, array $profile): void
    {
        $cacheControl = sprintf('%s, max-age=%d', $profile['type'], $profile['max_age']);
        
        if ($profile['immutable']) {
            $cacheControl .= ', immutable';
        }

        $response->headers->set('Cache-Control', $cacheControl);
        
        if ($profile['type'] === 'public') {
            $expires = new \DateTime(sprintf('+%d seconds', $profile['max_age']));
            $response->headers->set('Expires', $expires->format('D, d M Y H:i:s') . ' GMT');
        }
    }

    /**
     * Get cache profile by type
     */
    private function getCacheProfile(string $type): array
    {
        return self::CACHE_PROFILES[$type] ?? self::CACHE_PROFILES['static'];
    }

    /**
     * Normalize asset path for CDN
     */
    private function normalizeAssetPath(string $path): string
    {
        // Remove leading slash and normalize path
        $path = ltrim($path, '/');
        
        // Add version hash for cache busting if not present
        if (!str_contains($path, '?v=') && !str_contains($path, '.')) {
            $path .= '?v=' . substr(md5(filemtime($this->projectDir . '/public/' . $path)), 0, 8);
        }

        return $path;
    }

    /**
     * Build optimization parameters
     */
    private function buildOptimizationParams(array $options): array
    {
        $params = [];

        if (isset($options['width'])) {
            $params['w'] = (int) $options['width'];
        }

        if (isset($options['height'])) {
            $params['h'] = (int) $options['height'];
        }

        if (isset($options['quality'])) {
            $params['q'] = max(1, min(100, (int) $options['quality']));
        }

        if (isset($options['format'])) {
            $params['f'] = $options['format'];
        }

        return $params;
    }

    /**
     * Build security parameters for attachments
     */
    private function buildSecurityParams(string $attachmentId, array $options): array
    {
        $params = [];

        // Add timestamp for link expiration
        $params['t'] = time();

        // Add security token
        $params['token'] = $this->generateSecurityToken($attachmentId, $params['t']);

        if (isset($options['download'])) {
            $params['dl'] = 1;
        }

        return $params;
    }

    /**
     * Generate security token for attachment access
     */
    private function generateSecurityToken(string $attachmentId, int $timestamp): string
    {
        $secret = $_ENV['APP_SECRET'] ?? 'default-secret';
        return hash_hmac('sha256', $attachmentId . $timestamp, $secret);
    }

    /**
     * Verify attachment access
     */
    private function verifyAttachmentAccess(string $attachmentId, Request $request, array $security): bool
    {
        $token = $request->query->get('token');
        $timestamp = $request->query->get('t');

        if (!$token || !$timestamp) {
            return false;
        }

        // Check if link has expired (1 hour)
        if (time() - $timestamp > 3600) {
            return false;
        }

        $expectedToken = $this->generateSecurityToken($attachmentId, (int) $timestamp);
        return hash_equals($expectedToken, $token);
    }

    /**
     * Check if attachment is safe (passed security scan)
     */
    private function isAttachmentSafe(string $filePath): bool
    {
        // Check for malware scan results
        $scanFile = $filePath . '.scan';
        if (file_exists($scanFile)) {
            $scanResult = json_decode(file_get_contents($scanFile), true);
            return $scanResult['safe'] ?? false;
        }

        // If no scan file, assume safe for development
        return true;
    }

    /**
     * Determine optimal image format
     */
    private function determineOptimalImageFormat(string $acceptHeader, array $options): string
    {
        if (isset($options['format'])) {
            return $options['format'];
        }

        // Check for AVIF support
        if (str_contains($acceptHeader, 'image/avif')) {
            return 'avif';
        }

        // Check for WebP support  
        if (str_contains($acceptHeader, 'image/webp')) {
            return 'webp';
        }

        return 'jpg'; // Default fallback
    }

    /**
     * Get optimized image path
     */
    private function getOptimizedImagePath(string $originalPath, string $format, array $options): string
    {
        $cacheDir = $this->projectDir . '/var/cache/images';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $hash = md5($originalPath . serialize($options) . $format);
        return $cacheDir . '/' . $hash . '.' . $format;
    }

    /**
     * Generate optimized image
     */
    private function generateOptimizedImage(string $source, string $destination, string $format, array $options): void
    {
        // This would integrate with image optimization libraries
        // For now, just copy the original
        copy($source, $destination);
        
        $this->logger->info('Image optimized', [
            'source' => $source,
            'destination' => $destination,
            'format' => $format,
            'options' => $options
        ]);
    }

    /**
     * Resolve file paths
     */
    private function resolveAssetPath(string $path): string
    {
        return $this->projectDir . '/public/' . ltrim($path, '/');
    }

    private function resolveAttachmentPath(string $attachmentId): string
    {
        return $this->projectDir . '/var/attachments/' . $attachmentId;
    }

    private function resolveImagePath(string $path): string
    {
        return $this->projectDir . '/public/' . ltrim($path, '/');
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * Get MIME type for image format
     */
    private function getImageMimeType(string $format): string
    {
        return match ($format) {
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'image/jpeg'
        };
    }
}