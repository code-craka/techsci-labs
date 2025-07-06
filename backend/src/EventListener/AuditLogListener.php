<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\AuditLogService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Event listener for automatic audit logging
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 5)]
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onKernelResponse', priority: -5)]
#[AsEventListener(event: LoginSuccessEvent::class, method: 'onLoginSuccess')]
#[AsEventListener(event: LoginFailureEvent::class, method: 'onLoginFailure')]
#[AsEventListener(event: LogoutEvent::class, method: 'onLogout')]
class AuditLogListener
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly TokenStorageInterface $tokenStorage
    ) {}

    /**
     * Track request start for response time calculation
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        
        // Store request start time for performance metrics
        $request->attributes->set('_audit_start_time', microtime(true));
        
        // Skip non-API requests
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }
        
        // Log API access for sensitive endpoints
        $this->logApiRequest($request);
    }

    /**
     * Log API response and performance metrics
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        
        // Skip non-API requests
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }
        
        $user = $this->getAuthenticatedUser();
        
        $this->auditLogService->logApiAccess(
            $request->getPathInfo(),
            $request->getMethod(),
            $response->getStatusCode(),
            $request,
            $user
        );
    }

    /**
     * Log successful authentication
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $event->getRequest();
        
        $email = method_exists($user, 'getEmail') ? $user->getEmail() : 
                (method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : 'unknown');
        
        $this->auditLogService->logAuthAttempt($email, true, $request);
    }

    /**
     * Log failed authentication
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getException();
        
        // Extract email from request
        $email = 'unknown';
        $requestData = json_decode($request->getContent(), true);
        if (isset($requestData['email'])) {
            $email = $requestData['email'];
        } elseif ($request->request->has('email')) {
            $email = $request->request->get('email');
        }
        
        $reason = $exception?->getMessage() ?? 'Authentication failed';
        
        $this->auditLogService->logAuthAttempt($email, false, $request, $reason);
    }

    /**
     * Log logout events
     */
    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        $request = $event->getRequest();
        
        if ($user) {
            $email = method_exists($user, 'getEmail') ? $user->getEmail() : 
                    (method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : 'unknown');
            
            $this->auditLogService->logOperation(
                'auth.logout',
                'authentication',
                ['email' => $email],
                $request,
                $user
            );
        }
    }

    /**
     * Log API request for sensitive endpoints
     */
    private function logApiRequest($request): void
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();
        
        // Define sensitive endpoints that require audit logging
        $sensitivePatterns = [
            '/api/auth/' => 'authentication',
            '/api/emails/' => 'email_access',
            '/api/domains/' => 'domain_operation',
            '/api/accounts/' => 'account_operation',
            '/api/admin/' => 'admin_operation',
            '/api/attachments/' => 'attachment_access'
        ];
        
        foreach ($sensitivePatterns as $pattern => $type) {
            if (str_starts_with($path, $pattern)) {
                $this->logSensitiveApiRequest($request, $type, $path, $method);
                break;
            }
        }
    }

    /**
     * Log sensitive API request with details
     */
    private function logSensitiveApiRequest($request, string $type, string $path, string $method): void
    {
        $user = $this->getAuthenticatedUser();
        
        // Extract relevant details based on endpoint type
        $details = [
            'endpoint' => $path,
            'method' => $method,
            'query_params' => $request->query->all(),
        ];
        
        // Add specific details based on operation type
        switch ($type) {
            case 'email_access':
                $this->addEmailAccessDetails($request, $details);
                break;
            case 'domain_operation':
                $this->addDomainOperationDetails($request, $details);
                break;
            case 'account_operation':
                $this->addAccountOperationDetails($request, $details);
                break;
            case 'attachment_access':
                $this->addAttachmentAccessDetails($request, $details);
                break;
        }
        
        $this->auditLogService->logOperation(
            'api.' . $type,
            $type,
            $details,
            $request,
            $user
        );
    }

    /**
     * Add email access specific details
     */
    private function addEmailAccessDetails($request, array &$details): void
    {
        // Extract email ID from URL or request body
        if (preg_match('/\/emails\/([^\/]+)/', $request->getPathInfo(), $matches)) {
            $details['email_id'] = $matches[1];
        }
        
        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
            $requestData = json_decode($request->getContent(), true);
            if ($requestData) {
                $details['action'] = $requestData['action'] ?? 'unknown';
                $details['recipients'] = $requestData['recipients'] ?? null;
            }
        }
    }

    /**
     * Add domain operation specific details
     */
    private function addDomainOperationDetails($request, array &$details): void
    {
        // Extract domain from URL
        if (preg_match('/\/domains\/([^\/]+)/', $request->getPathInfo(), $matches)) {
            $details['domain'] = $matches[1];
        }
        
        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
            $requestData = json_decode($request->getContent(), true);
            if ($requestData) {
                $details['domain_name'] = $requestData['name'] ?? null;
                $details['settings'] = isset($requestData['settings']) ? 'modified' : null;
            }
        }
    }

    /**
     * Add account operation specific details
     */
    private function addAccountOperationDetails($request, array &$details): void
    {
        // Extract account ID from URL
        if (preg_match('/\/accounts\/([^\/]+)/', $request->getPathInfo(), $matches)) {
            $details['account_id'] = $matches[1];
        }
        
        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
            $requestData = json_decode($request->getContent(), true);
            if ($requestData) {
                $details['email'] = $requestData['email'] ?? null;
                $details['role_changes'] = isset($requestData['roles']) ? 'modified' : null;
            }
        }
    }

    /**
     * Add attachment access specific details
     */
    private function addAttachmentAccessDetails($request, array &$details): void
    {
        // Extract attachment ID from URL
        if (preg_match('/\/attachments\/([^\/]+)/', $request->getPathInfo(), $matches)) {
            $details['attachment_id'] = $matches[1];
        }
        
        // Check if this is a download request
        if (str_contains($request->getPathInfo(), '/download')) {
            $details['action'] = 'download';
        }
    }

    /**
     * Get currently authenticated user
     */
    private function getAuthenticatedUser()
    {
        $token = $this->tokenStorage->getToken();
        
        if ($token && $token->getUser()) {
            return $token->getUser();
        }
        
        return null;
    }
}