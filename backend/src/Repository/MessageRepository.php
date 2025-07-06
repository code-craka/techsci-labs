<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Message;
use App\Document\Mailbox;
use App\Document\EmailAccount;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class MessageRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $classMetadata = $dm->getClassMetadata(Message::class);
        parent::__construct($dm, $dm->getUnitOfWork(), $classMetadata);
    }

    public function findByMailbox(Mailbox $mailbox, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder()
            ->field('mailbox.id')->equals($mailbox->getId())
            ->sort(['date' => 'DESC'])
            ->skip($offset)
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findUnreadByMailbox(Mailbox $mailbox): array
    {
        return $this->createQueryBuilder()
            ->field('mailbox.id')->equals($mailbox->getId())
            ->field('isRead')->equals(false)
            ->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findByMessageId(string $messageId): ?Message
    {
        return $this->createQueryBuilder()
            ->field('messageId')->equals($messageId)
            ->getQuery()
            ->getSingleResult();
    }

    public function findByUid(Mailbox $mailbox, int $uid): ?Message
    {
        return $this->createQueryBuilder()
            ->field('mailbox.id')->equals($mailbox->getId())
            ->field('uid')->equals($uid)
            ->getQuery()
            ->getSingleResult();
    }

    public function findFlaggedMessages(?Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('isFlagged')->equals(true);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDeletedMessages(?Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('isDeleted')->equals(true);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findSpamMessages(?Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('isSpam')->equals(true);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDraftMessages(?Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('isDraft')->equals(true);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMessagesWithAttachments(?Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('hasAttachments')->equals(true);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findLargeMessages(float $sizeMbThreshold = 10.0, ?Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('sizeMb')->gte($sizeMbThreshold);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['sizeMb' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findRecentMessages(Mailbox $mailbox = null, int $days = 7): array
    {
        $since = new \DateTime('-' . $days . ' days');
        
        $qb = $this->createQueryBuilder()
            ->field('date')->gte($since);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function searchMessages(string $query, Mailbox $mailbox = null, array $fields = ['subject', 'textBody']): array
    {
        $qb = $this->createQueryBuilder();

        $orConditions = [];
        foreach ($fields as $field) {
            $orConditions[] = $qb->expr()->field($field)->regex(new \MongoDB\BSON\Regex($query, 'i'));
        }

        $qb->addOr(...$orConditions);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMessagesBySubject(string $subject, Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('subject')->regex(new \MongoDB\BSON\Regex($subject, 'i'));

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMessagesByFrom(string $fromEmail, Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('from.email')->equals(strtolower(trim($fromEmail)));

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMessagesByTo(string $toEmail, Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('to.email')->equals(strtolower(trim($toEmail)));

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMessagesByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate, Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('date')->gte($startDate)
            ->field('date')->lte($endDate);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findConversationMessages(string $messageId): array
    {
        $message = $this->findByMessageId($messageId);
        if (!$message) {
            return [];
        }

        // Find all messages in the same conversation (same subject or in-reply-to chain)
        $qb = $this->createQueryBuilder();
        
        $orConditions = [
            $qb->expr()->field('messageId')->equals($messageId),
            $qb->expr()->field('inReplyTo')->equals($messageId)
        ];

        if ($message->getInReplyTo()) {
            $orConditions[] = $qb->expr()->field('messageId')->equals($message->getInReplyTo());
            $orConditions[] = $qb->expr()->field('inReplyTo')->equals($message->getInReplyTo());
        }

        if ($message->getReferences()) {
            foreach ($message->getReferences() as $reference) {
                $orConditions[] = $qb->expr()->field('messageId')->equals($reference);
                $orConditions[] = $qb->expr()->field('inReplyTo')->equals($reference);
            }
        }

        $qb->addOr(...$orConditions);

        return $qb->sort(['date' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function getMessageStatistics(?Mailbox $mailbox = null): array
    {
        $matchStage = [];
        if ($mailbox) {
            $matchStage['mailbox.$id'] = new \MongoDB\BSON\ObjectId($mailbox->getId());
        }

        $pipeline = [];
        if (!empty($matchStage)) {
            $pipeline[] = ['$match' => $matchStage];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'totalMessages' => ['$sum' => 1],
                'unreadMessages' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isRead', false], 1, 0]
                    ]
                ],
                'flaggedMessages' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isFlagged', true], 1, 0]
                    ]
                ],
                'deletedMessages' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isDeleted', true], 1, 0]
                    ]
                ],
                'spamMessages' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isSpam', true], 1, 0]
                    ]
                ],
                'draftMessages' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isDraft', true], 1, 0]
                    ]
                ],
                'messagesWithAttachments' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$hasAttachments', true], 1, 0]
                    ]
                ],
                'totalSize' => ['$sum' => '$sizeMb'],
                'averageSize' => ['$avg' => '$sizeMb'],
                'maxSize' => ['$max' => '$sizeMb'],
                'totalAttachments' => ['$sum' => '$attachmentCount']
            ]
        ];

        $result = $this->getDocumentManager()
            ->getDocumentCollection(Message::class)
            ->aggregate($pipeline)
            ->toArray();

        if (empty($result)) {
            return [
                'totalMessages' => 0,
                'unreadMessages' => 0,
                'flaggedMessages' => 0,
                'deletedMessages' => 0,
                'spamMessages' => 0,
                'draftMessages' => 0,
                'messagesWithAttachments' => 0,
                'totalSize' => 0,
                'averageSize' => 0,
                'maxSize' => 0,
                'totalAttachments' => 0
            ];
        }

        return $result[0];
    }

    public function markAsRead(Message $message): void
    {
        if (!$message->isRead()) {
            $this->createQueryBuilder()
                ->updateOne()
                ->field('id')->equals($message->getId())
                ->field('isRead')->set(true)
                ->field('flags')->addToSet('\\Seen')
                ->field('updatedAt')->set(new \DateTime())
                ->getQuery()
                ->execute();
        }
    }

    public function markAsUnread(Message $message): void
    {
        if ($message->isRead()) {
            $this->createQueryBuilder()
                ->updateOne()
                ->field('id')->equals($message->getId())
                ->field('isRead')->set(false)
                ->field('flags')->pull('\\Seen')
                ->field('updatedAt')->set(new \DateTime())
                ->getQuery()
                ->execute();
        }
    }

    public function markAsFlagged(Message $message): void
    {
        if (!$message->isFlagged()) {
            $this->createQueryBuilder()
                ->updateOne()
                ->field('id')->equals($message->getId())
                ->field('isFlagged')->set(true)
                ->field('flags')->addToSet('\\Flagged')
                ->field('updatedAt')->set(new \DateTime())
                ->getQuery()
                ->execute();
        }
    }

    public function markAsUnflagged(Message $message): void
    {
        if ($message->isFlagged()) {
            $this->createQueryBuilder()
                ->updateOne()
                ->field('id')->equals($message->getId())
                ->field('isFlagged')->set(false)
                ->field('flags')->pull('\\Flagged')
                ->field('updatedAt')->set(new \DateTime())
                ->getQuery()
                ->execute();
        }
    }

    public function markAsDeleted(Message $message): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($message->getId())
            ->field('isDeleted')->set(true)
            ->field('flags')->addToSet('\\Deleted')
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function markAsSpam(Message $message): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($message->getId())
            ->field('isSpam')->set(true)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function moveToMailbox(Message $message, Mailbox $targetMailbox): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($message->getId())
            ->field('mailbox')->references($targetMailbox)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function updateSecurityInfo(Message $message, array $securityInfo): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($message->getId())
            ->field('securityInfo')->set($securityInfo)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function findOldMessages(\DateTimeInterface $before, Mailbox $mailbox = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('date')->lt($before);

        if ($mailbox) {
            $qb->field('mailbox.id')->equals($mailbox->getId());
        }

        return $qb->sort(['date' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function deleteOldMessages(\DateTimeInterface $before, Mailbox $mailbox = null): int
    {
        $messages = $this->findOldMessages($before, $mailbox);
        $count = 0;

        foreach ($messages as $message) {
            $this->getDocumentManager()->remove($message);
            $count++;
        }

        if ($count > 0) {
            $this->getDocumentManager()->flush();
        }

        return $count;
    }

    public function findDuplicateMessages(Mailbox $mailbox = null): array
    {
        $matchStage = [];
        if ($mailbox) {
            $matchStage['mailbox.$id'] = new \MongoDB\BSON\ObjectId($mailbox->getId());
        }

        $pipeline = [];
        if (!empty($matchStage)) {
            $pipeline[] = ['$match' => $matchStage];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => '$messageId',
                'count' => ['$sum' => 1],
                'messages' => ['$push' => '$$ROOT']
            ]
        ];
        $pipeline[] = [
            '$match' => ['count' => ['$gt' => 1]]
        ];

        return $this->getDocumentManager()
            ->getDocumentCollection(Message::class)
            ->aggregate($pipeline)
            ->toArray();
    }

    public function getMessageCountByDate(int $days = 30, Mailbox $mailbox = null): array
    {
        $since = new \DateTime('-' . $days . ' days');
        
        $matchStage = ['date' => ['$gte' => $since]];
        if ($mailbox) {
            $matchStage['mailbox.$id'] = new \MongoDB\BSON\ObjectId($mailbox->getId());
        }

        $pipeline = [
            ['$match' => $matchStage],
            [
                '$group' => [
                    '_id' => [
                        'year' => ['$year' => '$date'],
                        'month' => ['$month' => '$date'],
                        'day' => ['$dayOfMonth' => '$date']
                    ],
                    'count' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['_id' => 1]]
        ];

        return $this->getDocumentManager()
            ->getDocumentCollection(Message::class)
            ->aggregate($pipeline)
            ->toArray();
    }
}