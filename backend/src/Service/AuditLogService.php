<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Audit logging service for security-sensitive operations
 * 
 * Logs all critical operations including:
 * - Authentication attempts (success/failure)
 * - Email access and reading
 * - Domain operations
 * - Account management
 * - Configuration changes
 * - Data exports
 */
class AuditLogService
{
    private const HIGH_RISK_OPERATIONS = [
        'auth.login.success',
        'auth.login.failure',
        'auth.logout',
        'auth.password_reset',
        'auth.password_change',
        'email.read',
        'email.send',
        'email.delete',
        'email.export',
        'domain.create',
        'domain.delete',
        'domain.modify',
        'account.create',
        'account.delete',
        'account.modify',
        'attachment.download',
        'config.change',
        'api.access',
        'data.export'
    ];

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly LoggerInterface $auditLogger,
        private readonly TokenStorageInterface $tokenStorage
    ) {}

    /**
     * Log a security-sensitive operation
     */
    public function logOperation(
        string $operation,
        string $resource = null,
        array $details = [],
        Request $request = null,
        UserInterface $user = null
    ): void {
        $logEntry = [
            'timestamp' => new \DateTimeImmutable(),
            'operation' => $operation,
            'resource' => $resource,
            'user_id' => $this->getUserId($user),
            'user_email' => $this->getUserEmail($user),
            'ip_address' => $this->getClientIp($request),
            'user_agent' => $this->getUserAgent($request),
            'session_id' => $this->getSessionId($request),
            'details' => $details,
            'risk_level' => $this->getRiskLevel($operation),
            'trace_id' => $this->generateTraceId()
        ];

        // Log to structured audit log
        $this->auditLogger->info('AUDIT: ' . $operation, $logEntry);

        // Store in MongoDB for long-term retention
        $this->storeAuditLog($logEntry);

        // Check for suspicious patterns
        $this->analyzeSuspiciousActivity($logEntry);
    }

    /**
     * Log authentication attempt
     */
    public function logAuthAttempt(
        string $email,
        bool $success,
        Request $request,
        string $reason = null
    ): void {
        $operation = $success ? 'auth.login.success' : 'auth.login.failure';
        
        $details = [
            'email' => $email,
            'success' => $success,
            'failure_reason' => $reason,
            'auth_method' => 'password', // Could be expanded for MFA
        ];

        $this->logOperation($operation, 'authentication', $details, $request);
    }

    /**
     * Log email access
     */
    public function logEmailAccess(
        string $messageId,
        string $action,
        Request $request,
        UserInterface $user = null
    ): void {
        $operation = 'email.' . $action;
        
        $details = [
            'message_id' => $messageId,
            'action' => $action,
        ];

        $this->logOperation($operation, 'email', $details, $request, $user);
    }

    /**
     * Log domain operation
     */
    public function logDomainOperation(
        string $domain,
        string $action,
        Request $request,
        UserInterface $user = null,
        array $additionalDetails = []
    ): void {
        $operation = 'domain.' . $action;
        
        $details = array_merge([
            'domain' => $domain,
            'action' => $action,
        ], $additionalDetails);

        $this->logOperation($operation, 'domain', $details, $request, $user);
    }

    /**
     * Log account operation
     */
    public function logAccountOperation(
        string $accountId,
        string $action,
        Request $request,
        UserInterface $user = null,
        array $additionalDetails = []
    ): void {
        $operation = 'account.' . $action;
        
        $details = array_merge([
            'account_id' => $accountId,
            'action' => $action,
        ], $additionalDetails);

        $this->logOperation($operation, 'account', $details, $request, $user);
    }

    /**
     * Log data export operation
     */
    public function logDataExport(
        string $exportType,
        string $format,
        Request $request,
        UserInterface $user = null,
        array $additionalDetails = []
    ): void {
        $details = array_merge([
            'export_type' => $exportType,
            'format' => $format,
            'exported_at' => new \DateTimeImmutable(),
        ], $additionalDetails);

        $this->logOperation('data.export', 'data', $details, $request, $user);
    }

    /**
     * Log API access
     */
    public function logApiAccess(
        string $endpoint,
        string $method,
        int $statusCode,
        Request $request,
        UserInterface $user = null
    ): void {
        // Only log sensitive API endpoints
        $sensitiveEndpoints = [
            '/api/auth',
            '/api/emails',
            '/api/domains',
            '/api/accounts',
            '/api/admin'
        ];

        $isSensitive = false;
        foreach ($sensitiveEndpoints as $sensitiveEndpoint) {
            if (str_starts_with($endpoint, $sensitiveEndpoint)) {
                $isSensitive = true;
                break;
            }
        }

        if (!$isSensitive) {
            return;
        }

        $details = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'response_time' => $request?->server->get('REQUEST_TIME_FLOAT') 
                ? round((microtime(true) - $request->server->get('REQUEST_TIME_FLOAT')) * 1000)
                : null,
        ];

        $this->logOperation('api.access', 'api', $details, $request, $user);
    }

    /**
     * Get audit logs for analysis
     */
    public function getAuditLogs(
        array $criteria = [],
        int $limit = 100,
        int $offset = 0
    ): array {
        $repository = $this->getAuditRepository();
        
        $queryBuilder = $repository->createQueryBuilder();
        
        // Apply criteria
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $queryBuilder->field($field)->in($value);
            } else {
                $queryBuilder->field($field)->equals($value);
            }
        }
        
        $query = $queryBuilder
            ->sort('timestamp', 'desc')
            ->limit($limit)
            ->skip($offset)
            ->getQuery();
            
        return $query->execute()->toArray();
    }

    /**
     * Get suspicious activity patterns
     */
    public function getSuspiciousActivity(
        \DateTimeInterface $since = null,
        int $threshold = 5
    ): array {
        $since = $since ?? new \DateTimeImmutable('-1 hour');
        
        $repository = $this->getAuditRepository();
        
        // Find failed login attempts
        $failedLogins = $repository->createQueryBuilder()
            ->field('operation')->equals('auth.login.failure')
            ->field('timestamp')->gte($since)
            ->getQuery()
            ->execute();
            
        // Group by IP address
        $suspiciousIps = [];
        foreach ($failedLogins as $log) {
            $ip = $log['ip_address'];
            if (!isset($suspiciousIps[$ip])) {
                $suspiciousIps[$ip] = 0;
            }
            $suspiciousIps[$ip]++;
        }
        
        // Filter IPs with attempts above threshold
        return array_filter($suspiciousIps, fn($count) => $count >= $threshold);
    }

    /**
     * Store audit log in MongoDB
     */
    private function storeAuditLog(array $logEntry): void
    {
        try {
            $collection = $this->documentManager
                ->getDocumentCollection('AuditLog');
                
            $collection->insertOne($logEntry);
        } catch (\Exception $e) {
            // Log storage error but don't fail the operation
            $this->auditLogger->error('Failed to store audit log', [
                'error' => $e->getMessage(),
                'log_entry' => $logEntry
            ]);
        }
    }

    /**
     * Analyze for suspicious activity patterns
     */
    private function analyzeSuspiciousActivity(array $logEntry): void
    {
        $operation = $logEntry['operation'];
        $ipAddress = $logEntry['ip_address'];
        
        // Check for brute force attempts
        if ($operation === 'auth.login.failure') {
            $recentFailures = $this->countRecentFailedLogins($ipAddress);
            
            if ($recentFailures >= 5) {
                $this->auditLogger->warning('SECURITY ALERT: Potential brute force attack', [
                    'ip_address' => $ipAddress,
                    'failed_attempts' => $recentFailures,
                    'timeframe' => '15 minutes'
                ]);
            }
        }
        
        // Check for unusual access patterns
        if (str_starts_with($operation, 'email.')) {
            $this->checkUnusualEmailAccess($logEntry);
        }
    }

    /**
     * Count recent failed login attempts from IP
     */
    private function countRecentFailedLogins(string $ipAddress): int
    {
        $since = new \DateTimeImmutable('-15 minutes');
        
        $repository = $this->getAuditRepository();
        
        return $repository->createQueryBuilder()
            ->field('operation')->equals('auth.login.failure')
            ->field('ip_address')->equals($ipAddress)
            ->field('timestamp')->gte($since)
            ->getQuery()
            ->execute()
            ->count();
    }

    /**
     * Check for unusual email access patterns
     */
    private function checkUnusualEmailAccess(array $logEntry): void
    {
        $userId = $logEntry['user_id'];
        $operation = $logEntry['operation'];
        
        if (!$userId || $operation !== 'email.read') {
            return;
        }
        
        // Check for rapid email reading (potential scraping)
        $since = new \DateTimeImmutable('-5 minutes');
        $repository = $this->getAuditRepository();
        
        $recentReads = $repository->createQueryBuilder()
            ->field('operation')->equals('email.read')
            ->field('user_id')->equals($userId)
            ->field('timestamp')->gte($since)
            ->getQuery()
            ->execute()
            ->count();
            
        if ($recentReads > 50) {
            $this->auditLogger->warning('SECURITY ALERT: Rapid email access detected', [
                'user_id' => $userId,
                'email_reads' => $recentReads,
                'timeframe' => '5 minutes'
            ]);
        }
    }

    /**
     * Get audit log repository
     */
    private function getAuditRepository(): DocumentRepository
    {
        return $this->documentManager->getRepository('AuditLog');
    }

    /**
     * Get risk level for operation
     */
    private function getRiskLevel(string $operation): string
    {
        if (in_array($operation, self::HIGH_RISK_OPERATIONS)) {
            return 'high';
        }
        
        if (str_starts_with($operation, 'auth.') || 
            str_starts_with($operation, 'admin.') ||
            str_contains($operation, 'delete')) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * Get user ID from user or token
     */
    private function getUserId(UserInterface $user = null): ?string
    {
        if ($user) {
            return method_exists($user, 'getId') ? $user->getId() : null;
        }
        
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof UserInterface) {
            $tokenUser = $token->getUser();
            return method_exists($tokenUser, 'getId') ? $tokenUser->getId() : null;
        }
        
        return null;
    }

    /**
     * Get user email from user or token
     */
    private function getUserEmail(UserInterface $user = null): ?string
    {
        if ($user) {
            return method_exists($user, 'getEmail') ? $user->getEmail() : 
                   (method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null);
        }
        
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof UserInterface) {
            $tokenUser = $token->getUser();
            return method_exists($tokenUser, 'getEmail') ? $tokenUser->getEmail() : 
                   (method_exists($tokenUser, 'getUserIdentifier') ? $tokenUser->getUserIdentifier() : null);
        }
        
        return null;
    }

    /**
     * Get client IP address from request
     */
    private function getClientIp(Request $request = null): ?string
    {
        if (!$request) {
            return null;
        }
        
        return $request->getClientIp();
    }

    /**
     * Get user agent from request
     */
    private function getUserAgent(Request $request = null): ?string
    {
        if (!$request) {
            return null;
        }
        
        return $request->headers->get('User-Agent');
    }

    /**
     * Get session ID from request
     */
    private function getSessionId(Request $request = null): ?string
    {
        if (!$request || !$request->hasSession()) {
            return null;
        }
        
        return $request->getSession()->getId();
    }

    /**
     * Generate unique trace ID for request correlation
     */
    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }
}