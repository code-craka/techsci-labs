<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\EmailAccount;
use App\Document\Domain;
use App\Document\Mailbox;
use App\Document\Message;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MercurePublisher
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private HubInterface $hub,
        private SerializerInterface $serializer,
        private NightwatchService $nightwatchService,
        private LoggerInterface $logger,
        #[Autowire('%env(MERCURE_URL)%')] private string $mercureUrl,
        #[Autowire('%env(MERCURE_JWT_SECRET)%')] private string $mercureSecret
    ) {
        $this->httpClient = HttpClient::create();
    }

    /**
     * Publish new email notification
     */
    public function publishNewEmail(Message $message): void
    {
        try {
            $mailbox = $message->getMailbox();
            $account = $mailbox?->getEmailAccount();

            if (!$account) {
                $this->logger->warning('Cannot publish new email: no account found', [
                    'message_id' => $message->getId()
                ]);
                return;
            }

            $data = [
                'type' => 'new_email',
                'message' => [
                    'id' => $message->getId(),
                    'subject' => $message->getSubject(),
                    'from' => $message->getFrom()?->toArray(),
                    'date' => $message->getDate()?->format('c'),
                    'isRead' => $message->isRead(),
                    'hasAttachments' => $message->hasAttachments(),
                    'sizeMb' => $message->getSizeMb()
                ],
                'mailbox' => [
                    'id' => $mailbox->getId(),
                    'name' => $mailbox->getName(),
                    'path' => $mailbox->getPath()
                ],
                'account' => [
                    'id' => $account->getId(),
                    'email' => $account->getEmail()
                ],
                'timestamp' => (new \DateTime())->format('c')
            ];

            // Publish to account-specific topic
            $this->publishUpdate(
                sprintf('/accounts/%s/emails', $account->getId()),
                $data,
                [$account->getId()]
            );

            // Publish to mailbox-specific topic
            $this->publishUpdate(
                sprintf('/mailboxes/%s/emails', $mailbox->getId()),
                $data,
                [$account->getId()]
            );

            $this->logger->info('New email notification published', [
                'message_id' => $message->getId(),
                'account_id' => $account->getId()
            ]);

            // Record in Nightwatch
            $this->nightwatchService->recordEmailEvent('email_received', [
                'message_id' => $message->getId(),
                'account_id' => $account->getId(),
                'subject' => $message->getSubject()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish new email notification', [
                'message_id' => $message->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish email status update (read, flagged, deleted, etc.)
     */
    public function publishEmailUpdate(Message $message, string $action): void
    {
        try {
            $mailbox = $message->getMailbox();
            $account = $mailbox?->getEmailAccount();

            if (!$account) {
                return;
            }

            $data = [
                'type' => 'email_update',
                'action' => $action,
                'message' => [
                    'id' => $message->getId(),
                    'isRead' => $message->isRead(),
                    'isFlagged' => $message->isFlagged(),
                    'isDeleted' => $message->isDeleted(),
                    'isSpam' => $message->isSpam()
                ],
                'timestamp' => (new \DateTime())->format('c')
            ];

            $this->publishUpdate(
                sprintf('/accounts/%s/emails/%s', $account->getId(), $message->getId()),
                $data,
                [$account->getId()]
            );

            $this->logger->debug('Email update notification published', [
                'message_id' => $message->getId(),
                'action' => $action
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish email update notification', [
                'message_id' => $message->getId(),
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish mailbox statistics update
     */
    public function publishMailboxUpdate(Mailbox $mailbox): void
    {
        try {
            $account = $mailbox->getEmailAccount();

            if (!$account) {
                return;
            }

            $data = [
                'type' => 'mailbox_update',
                'mailbox' => [
                    'id' => $mailbox->getId(),
                    'name' => $mailbox->getName(),
                    'totalMessages' => $mailbox->getTotalMessages(),
                    'unreadMessages' => $mailbox->getUnreadMessages(),
                    'recentMessages' => $mailbox->getRecentMessages(),
                    'totalSizeMb' => $mailbox->getTotalSizeMb()
                ],
                'timestamp' => (new \DateTime())->format('c')
            ];

            $this->publishUpdate(
                sprintf('/accounts/%s/mailboxes/%s', $account->getId(), $mailbox->getId()),
                $data,
                [$account->getId()]
            );

            // Also publish to account overview
            $this->publishUpdate(
                sprintf('/accounts/%s/overview', $account->getId()),
                $data,
                [$account->getId()]
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish mailbox update notification', [
                'mailbox_id' => $mailbox->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish account update (login, settings change, etc.)
     */
    public function publishAccountUpdate(EmailAccount $account, string $action): void
    {
        try {
            $data = [
                'type' => 'account_update',
                'action' => $action,
                'account' => [
                    'id' => $account->getId(),
                    'email' => $account->getEmail(),
                    'isActive' => $account->isActive(),
                    'quotaMb' => $account->getQuotaMb(),
                    'usedQuotaMb' => $account->getUsedQuotaMb(),
                    'lastActivityAt' => $account->getLastActivityAt()?->format('c')
                ],
                'timestamp' => (new \DateTime())->format('c')
            ];

            $this->publishUpdate(
                sprintf('/accounts/%s', $account->getId()),
                $data,
                [$account->getId()]
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish account update notification', [
                'account_id' => $account->getId(),
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish domain update
     */
    public function publishDomainUpdate(Domain $domain, string $action): void
    {
        try {
            $data = [
                'type' => 'domain_update',
                'action' => $action,
                'domain' => [
                    'id' => $domain->getId(),
                    'domain' => $domain->getDomain(),
                    'isActive' => $domain->isActive(),
                    'isCatchAll' => $domain->isCatchAll(),
                    'isPlusAliasing' => $domain->isPlusAliasing()
                ],
                'timestamp' => (new \DateTime())->format('c')
            ];

            // Publish to domain-specific topic
            $this->publishUpdate(
                sprintf('/domains/%s', $domain->getId()),
                $data,
                ['admin'] // Only admins get domain updates
            );

            // Publish to global admin topic
            $this->publishUpdate(
                '/admin/domains',
                $data,
                ['admin']
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish domain update notification', [
                'domain_id' => $domain->getId(),
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish system status update
     */
    public function publishSystemStatus(array $status): void
    {
        try {
            $data = [
                'type' => 'system_status',
                'status' => $status,
                'timestamp' => (new \DateTime())->format('c')
            ];

            $this->publishUpdate(
                '/system/status',
                $data,
                ['admin']
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish system status notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish real-time statistics
     */
    public function publishStatistics(array $stats, string $type = 'general'): void
    {
        try {
            $data = [
                'type' => 'statistics',
                'category' => $type,
                'stats' => $stats,
                'timestamp' => (new \DateTime())->format('c')
            ];

            $this->publishUpdate(
                sprintf('/stats/%s', $type),
                $data,
                ['admin']
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish statistics notification', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish error notification
     */
    public function publishError(string $component, string $error, array $context = []): void
    {
        try {
            $data = [
                'type' => 'error',
                'component' => $component,
                'error' => $error,
                'context' => $context,
                'timestamp' => (new \DateTime())->format('c')
            ];

            $this->publishUpdate(
                '/system/errors',
                $data,
                ['admin']
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish error notification', [
                'component' => $component,
                'original_error' => $error,
                'publish_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Core method to publish Mercure update
     */
    private function publishUpdate(string $topic, array $data, array $private = []): void
    {
        try {
            $update = new Update(
                $topic,
                json_encode($data, JSON_THROW_ON_ERROR),
                $private
            );

            $this->hub->publish($update);

            $this->logger->debug('Mercure update published', [
                'topic' => $topic,
                'private' => $private
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish Mercure update', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);

            // Try fallback HTTP publish if hub fails
            $this->publishViaHttp($topic, $data, $private);
        }
    }

    /**
     * Fallback HTTP publish method
     */
    private function publishViaHttp(string $topic, array $data, array $private = []): void
    {
        try {
            $payload = [
                'topic' => $topic,
                'data' => json_encode($data, JSON_THROW_ON_ERROR),
                'private' => $private
            ];

            $this->httpClient->request('POST', $this->mercureUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->generateJwt(),
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $this->logger->debug('HTTP Mercure publish successful', ['topic' => $topic]);

        } catch (\Exception $e) {
            $this->logger->error('HTTP Mercure publish failed', [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate JWT for Mercure authentication
     */
    private function generateJwt(): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'mercure' => [
                'publish' => ['*'],
                'subscribe' => ['*']
            ],
            'iat' => time(),
            'exp' => time() + 3600
        ]);

        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->mercureSecret, true);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    /**
     * Test Mercure connection
     */
    public function testConnection(): bool
    {
        try {
            $this->publishUpdate('/test', ['test' => true, 'timestamp' => time()]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Mercure connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get Mercure hub status
     */
    public function getHubStatus(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->mercureUrl . '/.well-known/mercure');
            $isConnected = $response->getStatusCode() === 200;

            return [
                'connected' => $isConnected,
                'url' => $this->mercureUrl,
                'status_code' => $response->getStatusCode(),
                'timestamp' => (new \DateTime())->format('c')
            ];

        } catch (\Exception $e) {
            return [
                'connected' => false,
                'url' => $this->mercureUrl,
                'error' => $e->getMessage(),
                'timestamp' => (new \DateTime())->format('c')
            ];
        }
    }
}