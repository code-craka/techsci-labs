<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Email queue service for async email processing
 * 
 * Manages Redis-based message queues for:
 * - Email processing
 * - Email sending
 * - Attachment processing
 * - Notification delivery
 */
class EmailQueueService
{
    private const QUEUE_EMAIL_PROCESSING = 'email:processing';
    private const QUEUE_EMAIL_SENDING = 'email:sending';
    private const QUEUE_ATTACHMENT_PROCESSING = 'email:attachments';
    private const QUEUE_NOTIFICATIONS = 'email:notifications';
    private const QUEUE_CLEANUP = 'email:cleanup';
    
    private const PRIORITY_HIGH = 'high';
    private const PRIORITY_NORMAL = 'normal';
    private const PRIORITY_LOW = 'low';

    public function __construct(
        private readonly RedisAdapter $redisAdapter,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Queue email for processing
     */
    public function queueEmailProcessing(
        string $messageId,
        array $emailData,
        string $priority = self::PRIORITY_NORMAL
    ): bool {
        $job = [
            'id' => $this->generateJobId(),
            'type' => 'email_processing',
            'message_id' => $messageId,
            'data' => $emailData,
            'priority' => $priority,
            'queued_at' => time(),
            'attempts' => 0,
            'max_attempts' => 3
        ];

        return $this->enqueue(self::QUEUE_EMAIL_PROCESSING, $job, $priority);
    }

    /**
     * Queue email for sending
     */
    public function queueEmailSending(
        string $to,
        string $subject,
        string $body,
        array $attachments = [],
        string $priority = self::PRIORITY_NORMAL
    ): bool {
        $job = [
            'id' => $this->generateJobId(),
            'type' => 'email_sending',
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachments,
            'priority' => $priority,
            'queued_at' => time(),
            'attempts' => 0,
            'max_attempts' => 3
        ];

        return $this->enqueue(self::QUEUE_EMAIL_SENDING, $job, $priority);
    }

    /**
     * Queue attachment processing
     */
    public function queueAttachmentProcessing(
        string $attachmentId,
        string $messageId,
        array $attachmentData,
        string $priority = self::PRIORITY_NORMAL
    ): bool {
        $job = [
            'id' => $this->generateJobId(),
            'type' => 'attachment_processing',
            'attachment_id' => $attachmentId,
            'message_id' => $messageId,
            'data' => $attachmentData,
            'priority' => $priority,
            'queued_at' => time(),
            'attempts' => 0,
            'max_attempts' => 3
        ];

        return $this->enqueue(self::QUEUE_ATTACHMENT_PROCESSING, $job, $priority);
    }

    /**
     * Queue notification delivery
     */
    public function queueNotification(
        string $userId,
        string $type,
        array $notificationData,
        string $priority = self::PRIORITY_NORMAL
    ): bool {
        $job = [
            'id' => $this->generateJobId(),
            'type' => 'notification',
            'user_id' => $userId,
            'notification_type' => $type,
            'data' => $notificationData,
            'priority' => $priority,
            'queued_at' => time(),
            'attempts' => 0,
            'max_attempts' => 3
        ];

        return $this->enqueue(self::QUEUE_NOTIFICATIONS, $job, $priority);
    }

    /**
     * Queue cleanup task
     */
    public function queueCleanup(
        string $cleanupType,
        array $cleanupData,
        string $priority = self::PRIORITY_LOW
    ): bool {
        $job = [
            'id' => $this->generateJobId(),
            'type' => 'cleanup',
            'cleanup_type' => $cleanupType,
            'data' => $cleanupData,
            'priority' => $priority,
            'queued_at' => time(),
            'attempts' => 0,
            'max_attempts' => 1
        ];

        return $this->enqueue(self::QUEUE_CLEANUP, $job, $priority);
    }

    /**
     * Dequeue next job from specified queue
     */
    public function dequeue(string $queueName): ?array
    {
        try {
            $redis = $this->redisAdapter->getConnection();
            
            // Try priority queues first
            $priorities = [self::PRIORITY_HIGH, self::PRIORITY_NORMAL, self::PRIORITY_LOW];
            
            foreach ($priorities as $priority) {
                $queueKey = $this->getQueueKey($queueName, $priority);
                $jobData = $redis->lpop($queueKey);
                
                if ($jobData) {
                    $job = json_decode($jobData, true);
                    $this->logger->info('Dequeued job', [
                        'queue' => $queueName,
                        'job_id' => $job['id'],
                        'priority' => $priority
                    ]);
                    return $job;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to dequeue job', [
                'queue' => $queueName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Mark job as completed
     */
    public function markCompleted(array $job): void
    {
        try {
            $redis = $this->redisAdapter->getConnection();
            $completedKey = 'queue:completed:' . $job['id'];
            
            $completedJob = array_merge($job, [
                'completed_at' => time(),
                'status' => 'completed'
            ]);
            
            $redis->setex($completedKey, 86400, json_encode($completedJob)); // Keep for 24 hours
            
            $this->logger->info('Job marked as completed', ['job_id' => $job['id']]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark job as completed', [
                'job_id' => $job['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark job as failed and retry if attempts remaining
     */
    public function markFailed(array $job, string $error): bool
    {
        try {
            $job['attempts']++;
            $job['last_error'] = $error;
            $job['failed_at'] = time();
            
            if ($job['attempts'] < $job['max_attempts']) {
                // Retry with exponential backoff
                $delay = min(pow(2, $job['attempts']) * 60, 3600); // Max 1 hour delay
                $retryAt = time() + $delay;
                
                $this->scheduleRetry($job, $retryAt);
                
                $this->logger->warning('Job failed, scheduled for retry', [
                    'job_id' => $job['id'],
                    'attempt' => $job['attempts'],
                    'max_attempts' => $job['max_attempts'],
                    'retry_at' => $retryAt,
                    'error' => $error
                ]);
                
                return true;
            } else {
                // Max attempts reached, move to failed queue
                $this->moveToFailedQueue($job);
                
                $this->logger->error('Job permanently failed', [
                    'job_id' => $job['id'],
                    'attempts' => $job['attempts'],
                    'error' => $error
                ]);
                
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to handle job failure', [
                'job_id' => $job['id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        try {
            $redis = $this->redisAdapter->getConnection();
            $stats = [];
            
            $queues = [
                self::QUEUE_EMAIL_PROCESSING,
                self::QUEUE_EMAIL_SENDING,
                self::QUEUE_ATTACHMENT_PROCESSING,
                self::QUEUE_NOTIFICATIONS,
                self::QUEUE_CLEANUP
            ];
            
            foreach ($queues as $queue) {
                $stats[$queue] = [
                    'high' => $redis->llen($this->getQueueKey($queue, self::PRIORITY_HIGH)),
                    'normal' => $redis->llen($this->getQueueKey($queue, self::PRIORITY_NORMAL)),
                    'low' => $redis->llen($this->getQueueKey($queue, self::PRIORITY_LOW)),
                ];
                $stats[$queue]['total'] = array_sum($stats[$queue]);
            }
            
            // Get retry queue stats
            $stats['retry'] = $redis->zcard('queue:retry');
            $stats['failed'] = $redis->llen('queue:failed');
            
            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get queue stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Process retry queue
     */
    public function processRetryQueue(): int
    {
        try {
            $redis = $this->redisAdapter->getConnection();
            $now = time();
            $processed = 0;
            
            // Get jobs ready for retry
            $retryJobs = $redis->zrangebyscore('queue:retry', 0, $now, ['LIMIT' => [0, 100]]);
            
            foreach ($retryJobs as $jobData) {
                $job = json_decode($jobData, true);
                
                // Re-queue the job
                $queueName = $this->getQueueNameForJobType($job['type']);
                if ($this->enqueue($queueName, $job, $job['priority'])) {
                    // Remove from retry queue
                    $redis->zrem('queue:retry', $jobData);
                    $processed++;
                }
            }
            
            return $processed;
        } catch (\Exception $e) {
            $this->logger->error('Failed to process retry queue', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Clean up old completed and failed jobs
     */
    public function cleanup(int $maxAge = 86400): int
    {
        try {
            $redis = $this->redisAdapter->getConnection();
            $cleaned = 0;
            
            // Clean completed jobs older than maxAge
            $pattern = 'queue:completed:*';
            $keys = $redis->keys($pattern);
            
            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                if ($ttl <= 0) {
                    $redis->del($key);
                    $cleaned++;
                }
            }
            
            // Clean old failed jobs (keep last 1000)
            $failedCount = $redis->llen('queue:failed');
            if ($failedCount > 1000) {
                $toRemove = $failedCount - 1000;
                $redis->ltrim('queue:failed', 0, 999);
                $cleaned += $toRemove;
            }
            
            return $cleaned;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cleanup queues', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Enqueue job to specified queue with priority
     */
    private function enqueue(string $queueName, array $job, string $priority): bool
    {
        try {
            $redis = $this->redisAdapter->getConnection();
            $queueKey = $this->getQueueKey($queueName, $priority);
            $jobData = json_encode($job);
            
            $redis->rpush($queueKey, $jobData);
            
            $this->logger->info('Job enqueued', [
                'queue' => $queueName,
                'job_id' => $job['id'],
                'priority' => $priority
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to enqueue job', [
                'queue' => $queueName,
                'job_id' => $job['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Schedule job for retry
     */
    private function scheduleRetry(array $job, int $retryAt): void
    {
        $redis = $this->redisAdapter->getConnection();
        $jobData = json_encode($job);
        $redis->zadd('queue:retry', $retryAt, $jobData);
    }

    /**
     * Move job to failed queue
     */
    private function moveToFailedQueue(array $job): void
    {
        $redis = $this->redisAdapter->getConnection();
        $failedJob = array_merge($job, ['status' => 'failed']);
        $redis->rpush('queue:failed', json_encode($failedJob));
    }

    /**
     * Get queue key with priority
     */
    private function getQueueKey(string $queueName, string $priority): string
    {
        return sprintf('queue:%s:%s', $queueName, $priority);
    }

    /**
     * Get queue name for job type
     */
    private function getQueueNameForJobType(string $jobType): string
    {
        return match ($jobType) {
            'email_processing' => self::QUEUE_EMAIL_PROCESSING,
            'email_sending' => self::QUEUE_EMAIL_SENDING,
            'attachment_processing' => self::QUEUE_ATTACHMENT_PROCESSING,
            'notification' => self::QUEUE_NOTIFICATIONS,
            'cleanup' => self::QUEUE_CLEANUP,
            default => self::QUEUE_EMAIL_PROCESSING
        };
    }

    /**
     * Generate unique job ID
     */
    private function generateJobId(): string
    {
        return uniqid('job_', true);
    }
}