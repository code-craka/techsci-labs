<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Message;
use App\Entity\Account;
use App\Entity\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class NightwatchService
{
    private Client $httpClient;
    
    public function __construct(
        #[Autowire('%env(NIGHTWATCH_API_KEY)%')] private readonly string $apiKey,
        #[Autowire('%env(NIGHTWATCH_PROJECT_ID)%')] private readonly string $projectId,
        #[Autowire('%env(NIGHTWATCH_API_URL)%')] private readonly string $apiUrl,
        #[Autowire('%env(bool:NIGHTWATCH_ENABLED)%')] private readonly bool $enabled,
        private readonly LoggerInterface $logger
    ) {
        $this->httpClient = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Record email sent event from our Message entity
     */
    public function recordEmailFromMessage(Message $message, string $status = 'sent'): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $emailData = [
                'project_id' => $this->projectId,
                'email_id' => $message->getId(),
                'message_id' => $message->getMsgid(),
                'to' => $this->formatEmailAddresses($message->getTo()->toArray()),
                'from' => $message->getFrom() ? [
                    'address' => $message->getFrom()->getAddress(),
                    'name' => $message->getFrom()->getName()
                ] : null,
                'subject' => $message->getSubject(),
                'status' => $status,
                'timestamp' => $message->getCreatedAt()->getTimestamp(),
                'size' => $message->getSize(),
                'has_attachments' => $message->hasAttachments(),
                'mailbox' => $message->getMailbox()->getPath(),
                'account' => $message->getMailbox()->getAccount()->getAddress(),
                'metadata' => [
                    'server' => 'haraka',
                    'platform' => 'techsci-labs',
                    'environment' => $_ENV['APP_ENV'] ?? 'production',
                    'is_catch_all' => $message->getMailbox()->getAccount()->isCatchAll(),
                    'plus_aliasing' => $this->detectPlusAliasing($message),
                    'verifications' => $message->getVerifications()
                ]
            ];

            $response = $this->httpClient->post('/emails/sent', [
                'json' => $emailData
            ]);

            $this->logger->info('Email recorded in Nightwatch', [
                'message_id' => $message->getId(),
                'status_code' => $response->getStatusCode()
            ]);

            return $response->getStatusCode() < 300;

        } catch (RequestException $e) {
            $this->logger->error('Failed to record email in Nightwatch', [
                'error' => $e->getMessage(),
                'message_id' => $message->getId()
            ]);
            
            return false;
        }
    }

    /**
     * Record API token usage
     */
    public function recordTokenUsage(Token $token, array $requestData): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $usageData = [
                'project_id' => $this->projectId,
                'token_id' => $token->getId(),
                'token_name' => $token->getName(),
                'user_email' => $token->getUser()?->getAddress(),
                'endpoint' => $requestData['endpoint'] ?? 'unknown',
                'method' => $requestData['method'] ?? 'unknown',
                'ip_address' => $requestData['ip'] ?? null,
                'user_agent' => $requestData['user_agent'] ?? null,
                'response_time' => $requestData['response_time'] ?? null,
                'status_code' => $requestData['status_code'] ?? null,
                'timestamp' => time(),
                'scopes' => $token->getScopes(),
                'usage_count' => $token->getUsageCount(),
                'metadata' => [
                    'platform' => 'techsci-labs-api',
                    'token_type' => 'api_key'
                ]
            ];

            $response = $this->httpClient->post('/api/usage', [
                'json' => $usageData
            ]);

            return $response->getStatusCode() < 300;

        } catch (RequestException $e) {
            $this->logger->error('Failed to record token usage in Nightwatch', [
                'error' => $e->getMessage(),
                'token_id' => $token->getId()
            ]);
            
            return false;
        }
    }

    /**
     * Record account activity
     */
    public function recordAccountActivity(Account $account, string $activity, array $metadata = []): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $activityData = [
                'project_id' => $this->projectId,
                'account_id' => $account->getId(),
                'email' => $account->getAddress(),
                'activity' => $activity,
                'timestamp' => time(),
                'metadata' => array_merge([
                    'platform' => 'techsci-labs',
                    'is_catch_all' => $account->isCatchAll(),
                    'is_active' => $account->isActive(),
                    'quota' => $account->getQuota(),
                    'used_space' => $account->getUsed()
                ], $metadata)
            ];

            $response = $this->httpClient->post('/accounts/activity', [
                'json' => $activityData
            ]);

            return $response->getStatusCode() < 300;

        } catch (RequestException $e) {
            $this->logger->error('Failed to record account activity in Nightwatch', [
                'error' => $e->getMessage(),
                'account_id' => $account->getId()
            ]);
            
            return false;
        }
    }

    /**
     * Create infrastructure monitor
     */
    public function createInfrastructureMonitor(array $monitorConfig): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $defaultConfig = [
                'project_id' => $this->projectId,
                'name' => $monitorConfig['name'],
                'type' => $monitorConfig['type'] ?? 'http',
                'url' => $monitorConfig['url'],
                'check_interval' => $monitorConfig['check_interval'] ?? 300,
                'timeout' => $monitorConfig['timeout'] ?? 30,
                'alerts_enabled' => $monitorConfig['alerts_enabled'] ?? true,
                'metadata' => array_merge([
                    'platform' => 'techsci-labs',
                    'service_type' => $monitorConfig['service_type'] ?? 'api'
                ], $monitorConfig['metadata'] ?? [])
            ];

            $response = $this->httpClient->post('/monitors', [
                'json' => $defaultConfig
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Infrastructure monitor created in Nightwatch', [
                'monitor_name' => $monitorConfig['name'],
                'monitor_id' => $data['monitor_id'] ?? null
            ]);

            return $data['monitor_id'] ?? null;

        } catch (RequestException $e) {
            $this->logger->error('Failed to create Nightwatch monitor', [
                'error' => $e->getMessage(),
                'config' => $monitorConfig
            ]);
            
            return null;
        }
    }

    /**
     * Get project statistics
     */
    public function getProjectStats(): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = $this->httpClient->get("/projects/{$this->projectId}/stats");
            $stats = json_decode($response->getBody()->getContents(), true);

            $this->logger->debug('Retrieved Nightwatch project stats', [
                'total_emails' => $stats['total_emails'] ?? 0
            ]);

            return $stats;

        } catch (RequestException $e) {
            $this->logger->error('Failed to fetch Nightwatch stats', [
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Record system alert
     */
    public function recordAlert(string $alertType, string $message, array $context = []): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $alertData = [
                'project_id' => $this->projectId,
                'alert_type' => $alertType,
                'message' => $message,
                'severity' => $context['severity'] ?? 'info',
                'timestamp' => time(),
                'context' => array_merge([
                    'platform' => 'techsci-labs',
                    'environment' => $_ENV['APP_ENV'] ?? 'production'
                ], $context)
            ];

            $response = $this->httpClient->post('/alerts', [
                'json' => $alertData
            ]);

            return $response->getStatusCode() < 300;

        } catch (RequestException $e) {
            $this->logger->error('Failed to record alert in Nightwatch', [
                'error' => $e->getMessage(),
                'alert_type' => $alertType
            ]);
            
            return false;
        }
    }

    /**
     * Test Nightwatch connection
     */
    public function testConnection(): bool
    {
        if (!$this->enabled) {
            $this->logger->info('Nightwatch is disabled');
            return false;
        }

        try {
            $response = $this->httpClient->get('/health');
            $healthy = $response->getStatusCode() === 200;

            $this->logger->info('Nightwatch connection test', [
                'success' => $healthy,
                'status_code' => $response->getStatusCode()
            ]);

            return $healthy;

        } catch (RequestException $e) {
            $this->logger->error('Nightwatch connection test failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Format email addresses for Nightwatch
     */
    private function formatEmailAddresses(array $emailAddresses): array
    {
        return array_map(function($emailAddress) {
            return [
                'address' => $emailAddress->getAddress(),
                'name' => $emailAddress->getName()
            ];
        }, $emailAddresses);
    }

    /**
     * Detect plus-sign aliasing in message
     */
    private function detectPlusAliasing(Message $message): bool
    {
        foreach ($message->getTo() as $recipient) {
            if (str_contains($recipient->getAddress(), '+')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Setup default monitors for TechSci Labs infrastructure
     */
    public function setupDefaultMonitors(): array
    {
        $monitors = [];

        $defaultMonitors = [
            [
                'name' => 'TechSci API Health',
                'type' => 'http',
                'url' => $_ENV['API_BASE_URL'] . '/api/health',
                'service_type' => 'api'
            ],
            [
                'name' => 'Frontend Application',
                'type' => 'http', 
                'url' => $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000',
                'service_type' => 'frontend'
            ],
            [
                'name' => 'Mercure Hub',
                'type' => 'http',
                'url' => $_ENV['MERCURE_PUBLIC_URL'] . '/.well-known/mercure',
                'service_type' => 'mercure'
            ],
            [
                'name' => 'Email Server (SMTP)',
                'type' => 'tcp',
                'url' => $_ENV['SMTP_HOST'] . ':' . ($_ENV['SMTP_PORT'] ?? '587'),
                'service_type' => 'smtp'
            ]
        ];

        foreach ($defaultMonitors as $config) {
            $monitorId = $this->createInfrastructureMonitor($config);
            if ($monitorId) {
                $monitors[] = [
                    'name' => $config['name'],
                    'id' => $monitorId,
                    'type' => $config['type']
                ];
            }
        }

        return $monitors;
    }
}