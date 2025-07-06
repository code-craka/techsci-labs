<?php

namespace App\EventListener;

use App\Event\EmailSentEvent;
use App\Event\EmailDeliveredEvent;
use App\Event\EmailBouncedEvent;
use App\Service\NightwatchService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class EmailNightwatchListener
{
    public function __construct(
        private readonly NightwatchService $nightwatchService
    ) {}

    #[AsEventListener(event: EmailSentEvent::class)]
    public function onEmailSent(EmailSentEvent $event): void
    {
        $emailData = [
            'id' => $event->getEmailId(),
            'to' => $event->getRecipient(),
            'from' => $event->getSender(),
            'subject' => $event->getSubject(),
            'timestamp' => $event->getTimestamp()->getTimestamp(),
        ];

        $this->nightwatchService->recordEmailSent($emailData);
    }

    #[AsEventListener(event: EmailDeliveredEvent::class)]
    public function onEmailDelivered(EmailDeliveredEvent $event): void
    {
        $emailData = [
            'id' => $event->getEmailId(),
            'recipient' => $event->getRecipient(),
            'delivery_time' => $event->getDeliveryTime()->getTimestamp(),
            'metadata' => $event->getMetadata(),
        ];

        $this->nightwatchService->recordEmailDelivered($emailData);
    }

    #[AsEventListener(event: EmailBouncedEvent::class)]
    public function onEmailBounced(EmailBouncedEvent $event): void
    {
        $emailData = [
            'id' => $event->getEmailId(),
            'recipient' => $event->getRecipient(),
            'bounce_reason' => $event->getBounceReason(),
            'bounce_time' => $event->getBounceTime()->getTimestamp(),
            'metadata' => $event->getMetadata(),
        ];

        $this->nightwatchService->recordEmailBounced($emailData);
    }
}