<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MercurePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/webhooks/nightwatch')]
class NightwatchWebhookController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(NIGHTWATCH_WEBHOOK_SECRET)%')] private readonly string $webhookSecret,
        private readonly LoggerInterface $logger,
        private readonly MercurePublisher $mercurePublisher
    ) {
    }

    #[Route('', name: 'nightwatch_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        // Verify webhook signature
        $signature = $request->headers->get('X-Nightwatch-Signature');
        $payload = $request->getContent();
        
        if (!$this->verifySignature($payload, $signature)) {
            $this->logger->warning('Invalid Nightwatch webhook signature', [
                'ip' => $request->getClientIp(),
                'signature' => $signature
            ]);
            return new Response('Unauthorized', 401);
        }

        $data = json_decode($payload, true);
        
        if (!$data) {
            $this->logger->error('Invalid JSON payload in Nightwatch webhook');
            return new Response('Bad Request', 400);
        }

        // Handle different webhook events
        try {
            match ($data['event_type'] ?? null) {
                'email.delivered' => $this->handleEmailDelivered($data),
                'email.bounced' => $this->handleEmailBounced($data),
                'email.failed' => $this->handleEmailFailed($data),
                'monitor.down' => $this->handleMonitorDown($data),
                'monitor.up' => $this->handleMonitorUp($data),
                'alert.triggered' => $this->handleAlertTriggered($data),
                'quota.exceeded' => $this->handleQuotaExceeded($data),
                default => $this->handleUnknownEvent($data)
            };
        } catch (\Exception $e) {
            $this->logger->error('Error processing Nightwatch webhook', [
                'error' => $e->getMessage(),
                'event_type' => $data['event_type'] ?? 'unknown',
                'data' => $data
            ]);
            return new Response('Internal Server Error', 500);
        }

        return new Response('OK');
    }

    #[Route('/test', name: 'nightwatch_webhook_test', methods: ['GET'])]
    public function test(): Response
    {
        return $this->json([
            'status' => 'ok',
            'message' => 'Nightwatch webhook endpoint is active',
            'timestamp' => (new \DateTimeImmutable())->format('c')
        ]);
    }

    private function verifySignature(string $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    private function handleEmailDelivered(array $data): void
    {
        $this->logger->info('Email delivered notification from Nightwatch', [
            'email_id' => $data['email_id'] ?? null,
            'recipient' => $data['recipient'] ?? null,
            'delivery_time' => $data['delivery_time'] ?? null
        ]);

        // Publish real-time notification
        $this->mercurePublisher->publishSystemNotification(
            'email_delivered',
            'Email successfully delivered',
            [
                'email_id' => $data['email_id'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'provider' => 'nightwatch'
            ]
        );
    }

    private function handleEmailBounced(array $data): void
    {
        $this->logger->warning('Email bounced notification from Nightwatch', [
            'email_id' => $data['email_id'] ?? null,
            'recipient' => $data['recipient'] ?? null,
            'bounce_reason' => $data['bounce_reason'] ?? null
        ]);

        // Publish real-time notification
        $this->mercurePublisher->publishSystemNotification(
            'email_bounced',
            'Email bounced: ' . ($data['bounce_reason'] ?? 'Unknown reason'),
            [
                'email_id' => $data['email_id'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'bounce_reason' => $data['bounce_reason'] ?? null,
                'severity' => 'warning'
            ]
        );
    }

    private function handleEmailFailed(array $data): void
    {
        $this->logger->error('Email failed notification from Nightwatch', [
            'email_id' => $data['email_id'] ?? null,
            'recipient' => $data['recipient'] ?? null,
            'failure_reason' => $data['failure_reason'] ?? null
        ]);

        // Publish real-time notification
        $this->mercurePublisher->publishSystemNotification(
            'email_failed',
            'Email delivery failed: ' . ($data['failure_reason'] ?? 'Unknown reason'),
            [
                'email_id' => $data['email_id'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'failure_reason' => $data['failure_reason'] ?? null,
                'severity' => 'error'
            ]
        );
    }

    private function handleMonitorDown(array $data): void
    {
        $this->logger->error('Monitor down alert from Nightwatch', [
            'monitor_id' => $data['monitor_id'] ?? null,
            'monitor_name' => $data['monitor_name'] ?? null,
            'failure_reason' => $data['failure_reason'] ?? null
        ]);

        // Publish real-time alert
        $this->mercurePublisher->publishSystemNotification(
            'service_down',
            sprintf('Service down: %s', $data['monitor_name'] ?? 'Unknown service'),
            [
                'monitor_id' => $data['monitor_id'] ?? null,
                'monitor_name' => $data['monitor_name'] ?? null,
                'failure_reason' => $data['failure_reason'] ?? null,
                'severity' => 'critical'
            ]
        );
    }

    private function handleMonitorUp(array $data): void
    {
        $this->logger->info('Monitor recovered notification from Nightwatch', [
            'monitor_id' => $data['monitor_id'] ?? null,
            'monitor_name' => $data['monitor_name'] ?? null,
            'recovery_time' => $data['recovery_time'] ?? null
        ]);

        // Publish real-time notification
        $this->mercurePublisher->publishSystemNotification(
            'service_recovered',
            sprintf('Service recovered: %s', $data['monitor_name'] ?? 'Unknown service'),
            [
                'monitor_id' => $data['monitor_id'] ?? null,
                'monitor_name' => $data['monitor_name'] ?? null,
                'recovery_time' => $data['recovery_time'] ?? null,
                'severity' => 'info'
            ]
        );
    }

    private function handleAlertTriggered(array $data): void
    {
        $alertType = $data['alert_type'] ?? 'unknown';
        $severity = $data['severity'] ?? 'info';
        
        $this->logger->log(
            $severity === 'critical' ? 'error' : ($severity === 'warning' ? 'warning' : 'info'),
            'Alert triggered from Nightwatch',
            [
                'alert_type' => $alertType,
                'message' => $data['message'] ?? null,
                'severity' => $severity,
                'context' => $data['context'] ?? []
            ]
        );

        // Publish real-time alert
        $this->mercurePublisher->publishSystemNotification(
            'alert_triggered',
            $data['message'] ?? 'Alert triggered',
            [
                'alert_type' => $alertType,
                'severity' => $severity,
                'context' => $data['context'] ?? [],
                'timestamp' => $data['timestamp'] ?? time()
            ]
        );
    }

    private function handleQuotaExceeded(array $data): void
    {
        $this->logger->warning('Quota exceeded notification from Nightwatch', [
            'quota_type' => $data['quota_type'] ?? null,
            'current_usage' => $data['current_usage'] ?? null,
            'limit' => $data['limit'] ?? null
        ]);

        // Publish real-time notification
        $this->mercurePublisher->publishSystemNotification(
            'quota_exceeded',
            sprintf('Quota exceeded: %s', $data['quota_type'] ?? 'Unknown quota'),
            [
                'quota_type' => $data['quota_type'] ?? null,
                'current_usage' => $data['current_usage'] ?? null,
                'limit' => $data['limit'] ?? null,
                'severity' => 'warning'
            ]
        );
    }

    private function handleUnknownEvent(array $data): void
    {
        $this->logger->info('Unknown Nightwatch webhook event received', [
            'event_type' => $data['event_type'] ?? 'unknown',
            'data' => $data
        ]);
    }
}