<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\EmailQueueService;
use App\Service\EmailProcessor;
use App\Service\MercurePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'email:queue:worker',
    description: 'Process email queue jobs',
)]
class EmailQueueWorkerCommand extends Command
{
    private bool $shouldStop = false;

    public function __construct(
        private readonly EmailQueueService $queueService,
        private readonly EmailProcessor $emailProcessor,
        private readonly MercurePublisher $mercurePublisher,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('queue', InputArgument::OPTIONAL, 'Queue name to process', 'all')
            ->addOption('max-jobs', 'm', InputOption::VALUE_OPTIONAL, 'Maximum jobs to process', 0)
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Worker timeout in seconds', 0)
            ->addOption('sleep', 's', InputOption::VALUE_OPTIONAL, 'Sleep time between checks (seconds)', 1)
            ->setHelp('Process jobs from email queues')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $queueName = $input->getArgument('queue');
        $maxJobs = (int) $input->getOption('max-jobs');
        $timeout = (int) $input->getOption('timeout');
        $sleepTime = (int) $input->getOption('sleep');
        
        $io->title('Email Queue Worker');
        $io->info(sprintf('Processing queue: %s', $queueName));
        
        if ($maxJobs > 0) {
            $io->info(sprintf('Max jobs: %d', $maxJobs));
        }
        
        if ($timeout > 0) {
            $io->info(sprintf('Timeout: %d seconds', $timeout));
        }
        
        // Setup signal handlers for graceful shutdown
        $this->setupSignalHandlers();
        
        $startTime = time();
        $processedJobs = 0;
        
        try {
            while (!$this->shouldStop) {
                // Check timeout
                if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                    $io->info('Timeout reached, stopping worker');
                    break;
                }
                
                // Check max jobs
                if ($maxJobs > 0 && $processedJobs >= $maxJobs) {
                    $io->info('Max jobs reached, stopping worker');
                    break;
                }
                
                // Process retry queue first
                $retryProcessed = $this->queueService->processRetryQueue();
                if ($retryProcessed > 0) {
                    $io->info(sprintf('Processed %d retry jobs', $retryProcessed));
                }
                
                // Process jobs from queues
                $job = $this->getNextJob($queueName);
                
                if ($job) {
                    $success = $this->processJob($job, $io);
                    $processedJobs++;
                    
                    if ($success) {
                        $this->queueService->markCompleted($job);
                        $io->success(sprintf('Job %s completed', $job['id']));
                    }
                } else {
                    // No jobs available, sleep
                    sleep($sleepTime);
                }
                
                // Periodic cleanup
                if ($processedJobs % 100 === 0) {
                    $cleaned = $this->queueService->cleanup();
                    if ($cleaned > 0) {
                        $io->info(sprintf('Cleaned up %d old jobs', $cleaned));
                    }
                }
            }
        } catch (\Exception $e) {
            $io->error(sprintf('Worker error: %s', $e->getMessage()));
            $this->logger->error('Queue worker error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
        
        $io->success(sprintf('Worker stopped. Processed %d jobs in %d seconds', 
            $processedJobs, time() - $startTime));
        
        return Command::SUCCESS;
    }

    /**
     * Get next job from queue(s)
     */
    private function getNextJob(string $queueName): ?array
    {
        if ($queueName === 'all') {
            // Try all queues in priority order
            $queues = [
                'email:processing',
                'email:sending', 
                'email:attachments',
                'email:notifications',
                'email:cleanup'
            ];
            
            foreach ($queues as $queue) {
                $job = $this->queueService->dequeue($queue);
                if ($job) {
                    return $job;
                }
            }
            
            return null;
        } else {
            return $this->queueService->dequeue($queueName);
        }
    }

    /**
     * Process individual job
     */
    private function processJob(array $job, SymfonyStyle $io): bool
    {
        try {
            $io->info(sprintf('Processing job %s (type: %s)', $job['id'], $job['type']));
            
            $success = match ($job['type']) {
                'email_processing' => $this->processEmailProcessingJob($job),
                'email_sending' => $this->processEmailSendingJob($job),
                'attachment_processing' => $this->processAttachmentJob($job),
                'notification' => $this->processNotificationJob($job),
                'cleanup' => $this->processCleanupJob($job),
                default => throw new \InvalidArgumentException('Unknown job type: ' . $job['type'])
            };
            
            return $success;
        } catch (\Exception $e) {
            $error = sprintf('Job processing failed: %s', $e->getMessage());
            $io->error($error);
            $this->queueService->markFailed($job, $error);
            return false;
        }
    }

    /**
     * Process email processing job
     */
    private function processEmailProcessingJob(array $job): bool
    {
        $messageId = $job['message_id'];
        $emailData = $job['data'];
        
        $this->logger->info('Processing email', ['message_id' => $messageId]);
        
        // Process email with EmailProcessor service
        $result = $this->emailProcessor->processEmail($emailData);
        
        if ($result['success']) {
            // Publish real-time notification
            $this->mercurePublisher->publishEmailReceived($messageId, $result['processed_email']);
            return true;
        }
        
        return false;
    }

    /**
     * Process email sending job
     */
    private function processEmailSendingJob(array $job): bool
    {
        $to = $job['to'];
        $subject = $job['subject'];
        $body = $job['body'];
        $attachments = $job['attachments'] ?? [];
        
        $this->logger->info('Sending email', ['to' => $to, 'subject' => $subject]);
        
        try {
            $email = (new Email())
                ->to($to)
                ->subject($subject)
                ->html($body);
            
            // Add attachments if any
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $email->attachFromPath($attachment['path'], $attachment['name'] ?? null);
                }
            }
            
            $this->mailer->send($email);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process attachment processing job
     */
    private function processAttachmentJob(array $job): bool
    {
        $attachmentId = $job['attachment_id'];
        $messageId = $job['message_id'];
        $attachmentData = $job['data'];
        
        $this->logger->info('Processing attachment', [
            'attachment_id' => $attachmentId,
            'message_id' => $messageId
        ]);
        
        // Process attachment (virus scan, metadata extraction, etc.)
        $result = $this->emailProcessor->processAttachment($attachmentData);
        
        return $result['success'] ?? false;
    }

    /**
     * Process notification job
     */
    private function processNotificationJob(array $job): bool
    {
        $userId = $job['user_id'];
        $notificationType = $job['notification_type'];
        $notificationData = $job['data'];
        
        $this->logger->info('Sending notification', [
            'user_id' => $userId,
            'type' => $notificationType
        ]);
        
        // Send notification via Mercure
        $this->mercurePublisher->publishNotification($userId, $notificationType, $notificationData);
        
        return true;
    }

    /**
     * Process cleanup job
     */
    private function processCleanupJob(array $job): bool
    {
        $cleanupType = $job['cleanup_type'];
        $cleanupData = $job['data'];
        
        $this->logger->info('Running cleanup', ['type' => $cleanupType]);
        
        // Perform cleanup based on type
        $success = match ($cleanupType) {
            'expired_tokens' => $this->cleanupExpiredTokens($cleanupData),
            'old_messages' => $this->cleanupOldMessages($cleanupData),
            'temp_files' => $this->cleanupTempFiles($cleanupData),
            default => false
        };
        
        return $success;
    }

    /**
     * Cleanup expired tokens
     */
    private function cleanupExpiredTokens(array $data): bool
    {
        // Implementation would go here
        $this->logger->info('Cleaned up expired tokens');
        return true;
    }

    /**
     * Cleanup old messages
     */
    private function cleanupOldMessages(array $data): bool
    {
        // Implementation would go here
        $this->logger->info('Cleaned up old messages');
        return true;
    }

    /**
     * Cleanup temporary files
     */
    private function cleanupTempFiles(array $data): bool
    {
        // Implementation would go here
        $this->logger->info('Cleaned up temporary files');
        return true;
    }

    /**
     * Setup signal handlers for graceful shutdown
     */
    private function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        }
    }

    /**
     * Handle shutdown signals
     */
    public function handleSignal(int $signal): void
    {
        $this->logger->info('Received shutdown signal', ['signal' => $signal]);
        $this->shouldStop = true;
    }
}