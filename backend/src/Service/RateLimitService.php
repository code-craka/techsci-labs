<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

/**
 * Rate limiting service for API endpoints and user actions
 * 
 * Provides comprehensive rate limiting for:
 * - Authentication attempts
 * - API requests
 * - Email operations
 * - File uploads
 * - Domain operations
 */
class RateLimitService
{
    public function __construct(
        private readonly RateLimiterFactory $authLimiterFactory,
        private readonly RateLimiterFactory $apiLimiterFactory,
        private readonly RateLimiterFactory $emailLimiterFactory,
        private readonly RateLimiterFactory $passwordResetLimiterFactory,
        private readonly RateLimiterFactory $emailSendLimiterFactory,
        private readonly RateLimiterFactory $attachmentLimiterFactory,
        private readonly RateLimiterFactory $domainLimiterFactory
    ) {}

    /**
     * Check and consume auth rate limit
     */
    public function checkAuthLimit(Request $request): void
    {
        $identifier = $this->getIdentifier($request, 'auth');
        $limiter = $this->authLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                900, // 15 minutes
                'Too many authentication attempts. Please try again later.'
            );
        }
    }

    /**
     * Check and consume API rate limit
     */
    public function checkApiLimit(Request $request): void
    {
        $identifier = $this->getIdentifier($request, 'api');
        $limiter = $this->apiLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                3600, // 1 hour
                'API rate limit exceeded. Please reduce your request frequency.'
            );
        }
    }

    /**
     * Check and consume email operation rate limit
     */
    public function checkEmailLimit(Request $request): void
    {
        $identifier = $this->getIdentifier($request, 'email');
        $limiter = $this->emailLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                600, // 10 minutes
                'Email operation rate limit exceeded. Please wait before performing more email operations.'
            );
        }
    }

    /**
     * Check and consume password reset rate limit
     */
    public function checkPasswordResetLimit(Request $request, string $email): void
    {
        $identifier = sprintf('password_reset:%s', hash('sha256', $email));
        $limiter = $this->passwordResetLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                3600, // 1 hour
                'Too many password reset attempts. Please try again later.'
            );
        }
    }

    /**
     * Check and consume email sending rate limit
     */
    public function checkEmailSendLimit(Request $request): void
    {
        $identifier = $this->getIdentifier($request, 'email_send');
        $limiter = $this->emailSendLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                3600, // 1 hour
                'Email sending rate limit exceeded. Please wait before sending more emails.'
            );
        }
    }

    /**
     * Check and consume attachment upload rate limit
     */
    public function checkAttachmentLimit(Request $request): void
    {
        $identifier = $this->getIdentifier($request, 'attachment');
        $limiter = $this->attachmentLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                300, // 5 minutes
                'Attachment upload rate limit exceeded. Please wait before uploading more files.'
            );
        }
    }

    /**
     * Check and consume domain operation rate limit
     */
    public function checkDomainLimit(Request $request): void
    {
        $identifier = $this->getIdentifier($request, 'domain');
        $limiter = $this->domainLimiterFactory->create($identifier);
        
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(
                600, // 10 minutes
                'Domain operation rate limit exceeded. Please wait before performing more domain operations.'
            );
        }
    }

    /**
     * Get rate limit headers for response
     */
    public function getRateLimitHeaders(Request $request, string $type = 'api'): array
    {
        $identifier = $this->getIdentifier($request, $type);
        
        $limiter = match ($type) {
            'auth' => $this->authLimiterFactory->create($identifier),
            'email' => $this->emailLimiterFactory->create($identifier),
            'password_reset' => $this->passwordResetLimiterFactory->create($identifier),
            'email_send' => $this->emailSendLimiterFactory->create($identifier),
            'attachment' => $this->attachmentLimiterFactory->create($identifier),
            'domain' => $this->domainLimiterFactory->create($identifier),
            default => $this->apiLimiterFactory->create($identifier)
        };

        $consumption = $limiter->consume(0); // Peek without consuming

        return [
            'X-RateLimit-Limit' => (string) $limiter->getLimit(),
            'X-RateLimit-Remaining' => (string) $consumption->getRemainingTokens(),
            'X-RateLimit-Reset' => (string) $consumption->getRetryAfter()?->getTimestamp(),
        ];
    }

    /**
     * Reset rate limit for a specific identifier
     */
    public function resetRateLimit(Request $request, string $type): void
    {
        $identifier = $this->getIdentifier($request, $type);
        
        $limiter = match ($type) {
            'auth' => $this->authLimiterFactory->create($identifier),
            'email' => $this->emailLimiterFactory->create($identifier),
            'password_reset' => $this->passwordResetLimiterFactory->create($identifier),
            'email_send' => $this->emailSendLimiterFactory->create($identifier),
            'attachment' => $this->attachmentLimiterFactory->create($identifier),
            'domain' => $this->domainLimiterFactory->create($identifier),
            default => $this->apiLimiterFactory->create($identifier)
        };

        $limiter->reset();
    }

    /**
     * Check if rate limit would be exceeded without consuming
     */
    public function wouldExceedLimit(Request $request, string $type = 'api'): bool
    {
        $identifier = $this->getIdentifier($request, $type);
        
        $limiter = match ($type) {
            'auth' => $this->authLimiterFactory->create($identifier),
            'email' => $this->emailLimiterFactory->create($identifier),
            'password_reset' => $this->passwordResetLimiterFactory->create($identifier),
            'email_send' => $this->emailSendLimiterFactory->create($identifier),
            'attachment' => $this->attachmentLimiterFactory->create($identifier),
            'domain' => $this->domainLimiterFactory->create($identifier),
            default => $this->apiLimiterFactory->create($identifier)
        };

        return !$limiter->consume(0)->isAccepted();
    }

    /**
     * Get unique identifier for rate limiting based on request
     */
    private function getIdentifier(Request $request, string $type): string
    {
        // Use user ID if authenticated, otherwise IP address
        $userId = $request->headers->get('X-User-ID');
        $apiKey = $request->headers->get('X-API-KEY');
        $ip = $request->getClientIp();
        
        if ($userId) {
            return sprintf('%s:user:%s', $type, $userId);
        }
        
        if ($apiKey) {
            return sprintf('%s:api_key:%s', $type, hash('sha256', $apiKey));
        }
        
        return sprintf('%s:ip:%s', $type, hash('sha256', $ip ?? 'unknown'));
    }

    /**
     * Add rate limit headers to response
     */
    public function addRateLimitHeaders(Response $response, Request $request, string $type = 'api'): Response
    {
        $headers = $this->getRateLimitHeaders($request, $type);
        
        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }
        
        return $response;
    }

    /**
     * Get current rate limit status
     */
    public function getRateLimitStatus(Request $request, string $type = 'api'): array
    {
        $identifier = $this->getIdentifier($request, $type);
        
        $limiter = match ($type) {
            'auth' => $this->authLimiterFactory->create($identifier),
            'email' => $this->emailLimiterFactory->create($identifier),
            'password_reset' => $this->passwordResetLimiterFactory->create($identifier),
            'email_send' => $this->emailSendLimiterFactory->create($identifier),
            'attachment' => $this->attachmentLimiterFactory->create($identifier),
            'domain' => $this->domainLimiterFactory->create($identifier),
            default => $this->apiLimiterFactory->create($identifier)
        };

        $consumption = $limiter->consume(0); // Peek without consuming

        return [
            'type' => $type,
            'identifier' => hash('sha256', $identifier), // Hash for privacy
            'limit' => $limiter->getLimit(),
            'remaining' => $consumption->getRemainingTokens(),
            'reset_at' => $consumption->getRetryAfter()?->getTimestamp(),
            'is_accepted' => $consumption->isAccepted(),
        ];
    }
}