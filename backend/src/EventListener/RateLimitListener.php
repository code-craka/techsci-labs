<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\RateLimitService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event listener for automatic rate limiting on API requests
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 10)]
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onKernelResponse', priority: -10)]
class RateLimitListener
{
    public function __construct(
        private readonly RateLimitService $rateLimitService
    ) {}

    /**
     * Apply rate limiting on request
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Skip rate limiting for internal routes
        if (str_starts_with($path, '/_')) {
            return;
        }

        // Apply specific rate limits based on route patterns
        try {
            if (str_starts_with($path, '/api/auth/')) {
                $this->rateLimitService->checkAuthLimit($request);
            } elseif (str_starts_with($path, '/api/emails/') || str_starts_with($path, '/api/mailboxes/')) {
                $this->rateLimitService->checkEmailLimit($request);
            } elseif (str_starts_with($path, '/api/domains/')) {
                $this->rateLimitService->checkDomainLimit($request);
            } elseif (str_starts_with($path, '/api/attachments/')) {
                $this->rateLimitService->checkAttachmentLimit($request);
            } elseif (str_starts_with($path, '/api/auth/password-reset')) {
                // Extract email from request for password reset limiting
                $data = json_decode($request->getContent(), true);
                if (isset($data['email'])) {
                    $this->rateLimitService->checkPasswordResetLimit($request, $data['email']);
                }
            } elseif (str_starts_with($path, '/api/')) {
                // General API rate limiting
                $this->rateLimitService->checkApiLimit($request);
            }
        } catch (\Exception) {
            // Rate limit exceptions are handled by Symfony's error handling
            throw;
        }
    }

    /**
     * Add rate limit headers to response
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $path = $request->getPathInfo();

        // Skip for non-API routes
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        // Determine rate limit type based on path
        $rateLimitType = 'api'; // default

        if (str_starts_with($path, '/api/auth/')) {
            $rateLimitType = 'auth';
        } elseif (str_starts_with($path, '/api/emails/') || str_starts_with($path, '/api/mailboxes/')) {
            $rateLimitType = 'email';
        } elseif (str_starts_with($path, '/api/domains/')) {
            $rateLimitType = 'domain';
        } elseif (str_starts_with($path, '/api/attachments/')) {
            $rateLimitType = 'attachment';
        }

        // Add rate limit headers
        $this->rateLimitService->addRateLimitHeaders($response, $request, $rateLimitType);
    }
}