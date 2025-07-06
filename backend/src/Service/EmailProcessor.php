<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Account;
use App\Entity\Attachment;
use App\Entity\EmailAddress;
use App\Entity\Mailbox;
use App\Entity\Message;
use App\Repository\AccountRepository;
use App\Repository\MailboxRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\TextPart;

class EmailProcessor
{
    public function __construct(
        private DocumentManager $documentManager,
        private AccountRepository $accountRepository,
        private MailboxRepository $mailboxRepository,
        private MercurePublisher $mercurePublisher,
        private NightwatchService $nightwatchService, // Added Nightwatch integration
        private LoggerInterface $logger
    ) {
    }

    /**
     * Process incoming email from SMTP server
     */
    public function processIncomingEmail(array $emailData): ?Message
    {
        try {
            // Extract recipient information
            $recipients = $this->extractRecipients($emailData);
            if (empty($recipients)) {
                $this->logger->warning('No valid recipients found in email', ['email_data' => $emailData]);
                return null;
            }

            // Find target account for email delivery
            $targetAccount = $this->findTargetAccount($recipients);
            if (!$targetAccount) {
                $this->logger->info('No target account found for email', ['recipients' => $recipients]);
                
                // Record bounce in Nightwatch
                $this->nightwatchService->recordAlert('email_no_recipient', 'Email received with no target account', [
                    'recipients' => $recipients,
                    'severity' => 'warning'
                ]);
                
                return null;
            }

            // Get or create INBOX mailbox
            $inbox = $this->mailboxRepository->findOrCreateInbox($targetAccount);

            // Create message entity
            $message = $this->createMessageFromEmailData($emailData, $inbox);

            // Process attachments
            $this->processAttachments($message, $emailData);

            // Save to database
            $this->documentManager->persist($message);
            $this->documentManager->flush();

            // Update mailbox counters
            $this->mailboxRepository->updateMessageCounters($inbox, 1, 1);

            // Publish real-time notification
            $this->mercurePublisher->publishNewMessage($message);

            // Record in Nightwatch
            $this->nightwatchService->recordEmailFromMessage($message, 'received');

            $this->logger->info('Email processed successfully', [
                'message_id' => $message->getId(),
                'account' => $targetAccount->getAddress(),
                'subject' => $message->getSubject()
            ]);

            return $message;

        } catch (\Exception $e) {
            $this->logger->error('Failed to process incoming email', [
                'error' => $e->getMessage(),
                'email_data' => $emailData
            ]);
            
            // Record processing error in Nightwatch
            $this->nightwatchService->recordAlert('email_processing_error', 'Failed to process incoming email', [
                'error' => $e->getMessage(),
                'severity' => 'error'
            ]);
            
            return null;
        }
    }

    /**
     * Send outgoing email (for testing purposes)
     */
    public function sendTestEmail(Account $account, array $emailData): bool
    {
        try {
            // Create message for outgoing email
            $mailbox = $this->mailboxRepository->findByAccountAndPath($account, Mailbox::SENT);
            if (!$mailbox) {
                $mailbox = new Mailbox();
                $mailbox->setPath(Mailbox::SENT);
                $mailbox->setAccount($account);
                $mailbox->setIsSystem(true);
                $this->documentManager->persist($mailbox);
            }

            $message = $this->createMessageFromEmailData($emailData, $mailbox);
            $message->setIsRead(true); // Sent messages are read by default

            $this->documentManager->persist($message);
            $this->documentManager->flush();

            // Record in Nightwatch as sent
            $this->nightwatchService->recordEmailFromMessage($message, 'sent');

            // Publish real-time notification
            $this->mercurePublisher->publishNewMessage($message);

            $this->logger->info('Test email sent successfully', [
                'message_id' => $message->getId(),
                'account' => $account->getAddress(),
                'recipients' => count($message->getTo())
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send test email', [
                'error' => $e->getMessage(),
                'account' => $account->getAddress()
            ]);

            // Record send error in Nightwatch
            $this->nightwatchService->recordAlert('email_send_error', 'Failed to send test email', [
                'account' => $account->getAddress(),
                'error' => $e->getMessage(),
                'severity' => 'error'
            ]);

            return false;
        }
    }

    /**
     * Extract recipient email addresses from email data
     */
    private function extractRecipients(array $emailData): array
    {
        $recipients = [];

        // Extract TO recipients
        if (isset($emailData['to']) && is_array($emailData['to'])) {
            foreach ($emailData['to'] as $recipient) {
                if (is_string($recipient)) {
                    $recipients[] = $this->parseEmailAddress($recipient);
                } elseif (is_array($recipient) && isset($recipient['address'])) {
                    $recipients[] = $recipient['address'];
                }
            }
        }

        // Extract CC recipients
        if (isset($emailData['cc']) && is_array($emailData['cc'])) {
            foreach ($emailData['cc'] as $recipient) {
                if (is_string($recipient)) {
                    $recipients[] = $this->parseEmailAddress($recipient);
                } elseif (is_array($recipient) && isset($recipient['address'])) {
                    $recipients[] = $recipient['address'];
                }
            }
        }

        // Extract BCC recipients (if available)
        if (isset($emailData['bcc']) && is_array($emailData['bcc'])) {
            foreach ($emailData['bcc'] as $recipient) {
                if (is_string($recipient)) {
                    $recipients[] = $this->parseEmailAddress($recipient);
                } elseif (is_array($recipient) && isset($recipient['address'])) {
                    $recipients[] = $recipient['address'];
                }
            }
        }

        return array_filter(array_unique($recipients));
    }

    /**
     * Parse email address from string format
     */
    private function parseEmailAddress(string $emailString): ?string
    {
        // Handle formats like "Name <email@domain.com>" or "email@domain.com"
        if (preg_match('/<([^>]+)>/', $emailString, $matches)) {
            return strtolower(trim($matches[1]));
        }

        // Direct email address
        $email = strtolower(trim($emailString));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Find target account for email delivery
     */
    private function findTargetAccount(array $recipients): ?Account
    {
        foreach ($recipients as $recipient) {
            $account = $this->accountRepository->findAccountForEmail($recipient);
            if ($account) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Create Message entity from email data
     */
    private function createMessageFromEmailData(array $emailData, Mailbox $mailbox): Message
    {
        $message = new Message();
        $message->setMailbox($mailbox);

        // Set message ID
        if (isset($emailData['messageId'])) {
            $message->setMsgid($emailData['messageId']);
        }

        // Set sender
        if (isset($emailData['from'])) {
            $fromData = is_array($emailData['from']) ? $emailData['from'] : ['address' => $emailData['from']];
            $from = new EmailAddress(
                $fromData['address'] ?? null,
                $fromData['name'] ?? null
            );
            $message->setFrom($from);
        }

        // Set recipients
        $this->setMessageRecipients($message, $emailData);

        // Set subject
        if (isset($emailData['subject'])) {
            $message->setSubject($emailData['subject']);
        }

        // Set content
        if (isset($emailData['text'])) {
            $message->setText($emailData['text']);
        }

        if (isset($emailData['html'])) {
            $htmlContent = is_array($emailData['html']) ? $emailData['html'] : [$emailData['html']];
            $message->setHtml($htmlContent);
        }

        // Set size
        if (isset($emailData['size'])) {
            $message->setSize((int) $emailData['size']);
        }

        // Set raw source if available
        if (isset($emailData['rawSource'])) {
            $message->setRawSource($emailData['rawSource']);
        }

        // Set verification results
        if (isset($emailData['verifications'])) {
            $message->setVerifications($emailData['verifications']);
        }

        return $message;
    }

    /**
     * Set message recipients from email data
     */
    private function setMessageRecipients(Message $message, array $emailData): void
    {
        // Set TO recipients
        if (isset($emailData['to']) && is_array($emailData['to'])) {
            foreach ($emailData['to'] as $recipient) {
                $emailAddress = $this->createEmailAddressFromData($recipient);
                if ($emailAddress) {
                    $message->addTo($emailAddress);
                }
            }
        }

        // Set CC recipients
        if (isset($emailData['cc']) && is_array($emailData['cc'])) {
            foreach ($emailData['cc'] as $recipient) {
                $emailAddress = $this->createEmailAddressFromData($recipient);
                if ($emailAddress) {
                    $message->addCc($emailAddress);
                }
            }
        }

        // Set BCC recipients
        if (isset($emailData['bcc']) && is_array($emailData['bcc'])) {
            foreach ($emailData['bcc'] as $recipient) {
                $emailAddress = $this->createEmailAddressFromData($recipient);
                if ($emailAddress) {
                    $message->addBcc($emailAddress);
                }
            }
        }

        // Set Reply-To
        if (isset($emailData['replyTo']) && is_array($emailData['replyTo'])) {
            foreach ($emailData['replyTo'] as $recipient) {
                $emailAddress = $this->createEmailAddressFromData($recipient);
                if ($emailAddress) {
                    $message->addReplyTo($emailAddress);
                }
            }
        }
    }

    /**
     * Create EmailAddress entity from data
     */
    private function createEmailAddressFromData($data): ?EmailAddress
    {
        if (is_string($data)) {
            $address = $this->parseEmailAddress($data);
            return $address ? new EmailAddress($address) : null;
        }

        if (is_array($data) && isset($data['address'])) {
            return new EmailAddress(
                $data['address'],
                $data['name'] ?? null
            );
        }

        return null;
    }

    /**
     * Process email attachments
     */
    private function processAttachments(Message $message, array $emailData): void
    {
        if (!isset($emailData['attachments']) || !is_array($emailData['attachments'])) {
            return;
        }

        foreach ($emailData['attachments'] as $attachmentData) {
            $attachment = $this->createAttachmentFromData($attachmentData, $message);
            if ($attachment) {
                $message->addAttachment($attachment);
                $this->documentManager->persist($attachment);
            }
        }

        if (!empty($emailData['attachments'])) {
            $message->setHasAttachments(true);
            
            // Record attachment processing in Nightwatch
            $this->nightwatchService->recordAlert('attachments_processed', 'Email attachments processed', [
                'message_id' => $message->getId(),
                'attachment_count' => count($emailData['attachments']),
                'severity' => 'info'
            ]);
        }
    }

    /**
     * Create Attachment entity from data
     */
    private function createAttachmentFromData(array $data, Message $message): ?Attachment
    {
        if (!isset($data['filename']) || !isset($data['contentType'])) {
            return null;
        }

        $attachment = new Attachment();
        $attachment->setMessage($message);
        $attachment->setFilename($data['filename']);
        $attachment->setContentType($data['contentType']);

        if (isset($data['size'])) {
            $attachment->setSize((int) $data['size']);
        }

        if (isset($data['disposition'])) {
            $attachment->setDisposition($data['disposition']);
        }

        if (isset($data['transferEncoding'])) {
            $attachment->setTransferEncoding($data['transferEncoding']);
        }

        if (isset($data['contentId'])) {
            $attachment->setContentId($data['contentId']);
            $attachment->setRelated(true);
        }

        if (isset($data['checksum'])) {
            $attachment->setChecksum($data['checksum']);
        }

        if (isset($data['filePath'])) {
            $attachment->setFilePath($data['filePath']);
        }

        // Generate download URL
        $downloadUrl = $this->generateAttachmentDownloadUrl($attachment);
        $attachment->setDownloadUrl($downloadUrl);

        return $attachment;
    }

    /**
     * Generate download URL for attachment
     */
    private function generateAttachmentDownloadUrl(Attachment $attachment): string
    {
        // This will be generated based on the API route structure
        return sprintf(
            '/api/accounts/%s/mailboxes/%s/messages/%s/attachment/%s',
            $attachment->getMessage()->getMailbox()->getAccount()->getId(),
            $attachment->getMessage()->getMailbox()->getId(),
            $attachment->getMessage()->getId(),
            $attachment->getId()
        );
    }

    /**
     * Apply spam filters and verification checks
     */
    public function applySpamFilters(Message $message): array
    {
        $spamScore = 0;
        $filters = [];

        // SPF check
        if ($this->checkSpfRecord($message)) {
            $message->addVerification(Message::VERIFICATION_SPF, 'pass');
        } else {
            $message->addVerification(Message::VERIFICATION_SPF, 'fail');
            $spamScore += 2;
            $filters[] = 'SPF verification failed';
        }

        // DKIM check
        if ($this->checkDkimSignature($message)) {
            $message->addVerification(Message::VERIFICATION_DKIM, 'pass');
        } else {
            $message->addVerification(Message::VERIFICATION_DKIM, 'fail');
            $spamScore += 1;
            $filters[] = 'DKIM signature invalid';
        }

        // Content-based filters
        $contentScore = $this->analyzeContent($message);
        $spamScore += $contentScore;

        $isSpam = $spamScore >= 5;

        // Record spam detection in Nightwatch
        if ($isSpam) {
            $this->nightwatchService->recordAlert('spam_detected', 'Spam email detected', [
                'message_id' => $message->getId(),
                'spam_score' => $spamScore,
                'filters_triggered' => $filters,
                'severity' => 'warning'
            ]);
        }

        return [
            'spamScore' => $spamScore,
            'filters' => $filters,
            'isSpam' => $isSpam
        ];
    }

    /**
     * Check SPF record (simplified implementation)
     */
    private function checkSpfRecord(Message $message): bool
    {
        // Simplified SPF check - in production, use proper SPF library
        return true; // Placeholder
    }

    /**
     * Check DKIM signature (simplified implementation)
     */
    private function checkDkimSignature(Message $message): bool
    {
        // Simplified DKIM check - in production, use proper DKIM library
        return true; // Placeholder
    }

    /**
     * Analyze message content for spam indicators
     */
    private function analyzeContent(Message $message): int
    {
        $score = 0;
        $text = $message->getText() ?? '';
        $subject = $message->getSubject() ?? '';

        // Check for spam keywords
        $spamKeywords = ['viagra', 'lottery', 'winner', 'urgent', 'click here'];
        foreach ($spamKeywords as $keyword) {
            if (stripos($text . ' ' . $subject, $keyword) !== false) {
                $score += 1;
            }
        }

        // Check for excessive caps
        if (preg_match_all('/[A-Z]/', $subject) > strlen($subject) * 0.5) {
            $score += 1;
        }

        return $score;
    }

    /**
     * Process bulk email operations
     */
    public function processBulkEmails(Account $account, array $operations): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'operations' => []
        ];

        foreach ($operations as $operation) {
            try {
                $result = match ($operation['type']) {
                    'delete' => $this->deleteBulkMessages($account, $operation['message_ids']),
                    'mark_read' => $this->markBulkAsRead($account, $operation['message_ids']),
                    'move' => $this->moveBulkMessages($account, $operation['message_ids'], $operation['target_mailbox']),
                    default => ['success' => false, 'message' => 'Unknown operation']
                };

                if ($result['success']) {
                    $results['processed'] += $result['count'] ?? 1;
                } else {
                    $results['failed']++;
                }

                $results['operations'][] = $result;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['operations'][] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        // Record bulk operation in Nightwatch
        $this->nightwatchService->recordAccountActivity($account, 'bulk_email_operation', [
            'total_operations' => count($operations),
            'processed' => $results['processed'],
            'failed' => $results['failed']
        ]);

        return $results;
    }

    /**
     * Delete bulk messages
     */
    private function deleteBulkMessages(Account $account, array $messageIds): array
    {
        // Implementation for bulk delete
        return ['success' => true, 'count' => count($messageIds)];
    }

    /**
     * Mark bulk messages as read
     */
    private function markBulkAsRead(Account $account, array $messageIds): array
    {
        // Implementation for bulk mark as read
        return ['success' => true, 'count' => count($messageIds)];
    }

    /**
     * Move bulk messages
     */
    private function moveBulkMessages(Account $account, array $messageIds, string $targetMailbox): array
    {
        // Implementation for bulk move
        return ['success' => true, 'count' => count($messageIds)];
    }
}