<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Mailbox;
use App\Document\EmailAccount;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class MailboxRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $classMetadata = $dm->getClassMetadata(Mailbox::class);
        parent::__construct($dm, $dm->getUnitOfWork(), $classMetadata);
    }

    public function findByEmailAccount(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findSelectableByEmailAccount(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('isSelectable')->equals(true)
            ->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDefaultMailbox(EmailAccount $emailAccount): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('isDefault')->equals(true)
            ->getQuery()
            ->getSingleResult();
    }

    public function findInboxMailbox(EmailAccount $emailAccount): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^inbox$', 'i')),
                $this->createQueryBuilder()->field('path')->regex(new \MongoDB\BSON\Regex('^inbox$', 'i'))
            )
            ->getQuery()
            ->getSingleResult();
    }

    public function findSentMailbox(EmailAccount $emailAccount): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^sent$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^sent items$', 'i')),
                $this->createQueryBuilder()->field('path')->regex(new \MongoDB\BSON\Regex('^sent$', 'i'))
            )
            ->getQuery()
            ->getSingleResult();
    }

    public function findDraftsMailbox(EmailAccount $emailAccount): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^drafts$', 'i')),
                $this->createQueryBuilder()->field('path')->regex(new \MongoDB\BSON\Regex('^drafts$', 'i'))
            )
            ->getQuery()
            ->getSingleResult();
    }

    public function findTrashMailbox(EmailAccount $emailAccount): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^trash$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^deleted items$', 'i')),
                $this->createQueryBuilder()->field('path')->regex(new \MongoDB\BSON\Regex('^trash$', 'i'))
            )
            ->getQuery()
            ->getSingleResult();
    }

    public function findSpamMailbox(EmailAccount $emailAccount): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^spam$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^junk$', 'i')),
                $this->createQueryBuilder()->field('path')->regex(new \MongoDB\BSON\Regex('^spam$', 'i'))
            )
            ->getQuery()
            ->getSingleResult();
    }

    public function findByPath(EmailAccount $emailAccount, string $path): ?Mailbox
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('path')->equals($path)
            ->getQuery()
            ->getSingleResult();
    }

    public function findMailboxesWithUnreadMessages(EmailAccount $emailAccount = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('unreadMessages')->gt(0);

        if ($emailAccount) {
            $qb->field('emailAccount.id')->equals($emailAccount->getId());
        }

        return $qb->sort(['unreadMessages' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findLargestMailboxes(int $limit = 10): array
    {
        return $this->createQueryBuilder()
            ->sort(['totalSizeMb' => 'DESC'])
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMailboxesWithMostMessages(int $limit = 10): array
    {
        return $this->createQueryBuilder()
            ->sort(['totalMessages' => 'DESC'])
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findRecentlyAccessedMailboxes(EmailAccount $emailAccount, int $limit = 5): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('lastAccess')->exists(true)
            ->sort(['lastAccess' => 'DESC'])
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findSubscribedMailboxes(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('isSubscribed')->equals(true)
            ->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function searchMailboxes(EmailAccount $emailAccount, string $query): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex($query, 'i')),
                $this->createQueryBuilder()->field('path')->regex(new \MongoDB\BSON\Regex($query, 'i')),
                $this->createQueryBuilder()->field('description')->regex(new \MongoDB\BSON\Regex($query, 'i'))
            )
            ->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function getMailboxStatistics(EmailAccount $emailAccount = null): array
    {
        $matchStage = [];
        if ($emailAccount) {
            $matchStage['emailAccount.$id'] = new \MongoDB\BSON\ObjectId($emailAccount->getId());
        }

        $pipeline = [];
        if (!empty($matchStage)) {
            $pipeline[] = ['$match' => $matchStage];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => null,
                'totalMailboxes' => ['$sum' => 1],
                'totalMessages' => ['$sum' => '$totalMessages'],
                'totalUnreadMessages' => ['$sum' => '$unreadMessages'],
                'totalRecentMessages' => ['$sum' => '$recentMessages'],
                'totalStorage' => ['$sum' => '$totalSizeMb'],
                'averageMessagesPerMailbox' => ['$avg' => '$totalMessages'],
                'maxMessagesInMailbox' => ['$max' => '$totalMessages'],
                'subscribedMailboxes' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isSubscribed', true], 1, 0]
                    ]
                ],
                'selectableMailboxes' => [
                    '$sum' => [
                        '$cond' => ['$eq' => ['$isSelectable', true], 1, 0]
                    ]
                ]
            ]
        ];

        $result = $this->getDocumentManager()
            ->getDocumentCollection(Mailbox::class)
            ->aggregate($pipeline)
            ->toArray();

        if (empty($result)) {
            return [
                'totalMailboxes' => 0,
                'totalMessages' => 0,
                'totalUnreadMessages' => 0,
                'totalRecentMessages' => 0,
                'totalStorage' => 0,
                'averageMessagesPerMailbox' => 0,
                'maxMessagesInMailbox' => 0,
                'subscribedMailboxes' => 0,
                'selectableMailboxes' => 0
            ];
        }

        return $result[0];
    }

    public function updateMailboxStatistics(Mailbox $mailbox, array $stats): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($mailbox->getId())
            ->field('totalMessages')->set($stats['totalMessages'] ?? $mailbox->getTotalMessages())
            ->field('unreadMessages')->set($stats['unreadMessages'] ?? $mailbox->getUnreadMessages())
            ->field('recentMessages')->set($stats['recentMessages'] ?? $mailbox->getRecentMessages())
            ->field('totalSizeMb')->set($stats['totalSizeMb'] ?? $mailbox->getTotalSizeMb())
            ->field('statistics')->set($stats['statistics'] ?? $mailbox->getStatistics())
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function incrementMessageCount(Mailbox $mailbox, bool $isUnread = true): void
    {
        $updateQuery = $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($mailbox->getId())
            ->field('totalMessages')->inc(1)
            ->field('lastMessageAt')->set(new \DateTime())
            ->field('updatedAt')->set(new \DateTime());

        if ($isUnread) {
            $updateQuery->field('unreadMessages')->inc(1);
        }

        $updateQuery->getQuery()->execute();
    }

    public function decrementMessageCount(Mailbox $mailbox, bool $wasUnread = false): void
    {
        $updateQuery = $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($mailbox->getId())
            ->field('totalMessages')->inc(-1)
            ->field('updatedAt')->set(new \DateTime());

        if ($wasUnread) {
            $updateQuery->field('unreadMessages')->inc(-1);
        }

        $updateQuery->getQuery()->execute();
    }

    public function markMessageAsRead(Mailbox $mailbox): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($mailbox->getId())
            ->field('unreadMessages')->inc(-1)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function markMessageAsUnread(Mailbox $mailbox): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($mailbox->getId())
            ->field('unreadMessages')->inc(1)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function updateLastAccess(Mailbox $mailbox): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($mailbox->getId())
            ->field('lastAccess')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function findEmptyMailboxes(EmailAccount $emailAccount = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('totalMessages')->equals(0);

        if ($emailAccount) {
            $qb->field('emailAccount.id')->equals($emailAccount->getId());
        }

        return $qb->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMailboxesByUidValidity(int $uidValidity): array
    {
        return $this->createQueryBuilder()
            ->field('uidValidity')->equals($uidValidity)
            ->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findStandardMailboxes(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->addOr(
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^inbox$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^sent$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^drafts$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^trash$', 'i')),
                $this->createQueryBuilder()->field('name')->regex(new \MongoDB\BSON\Regex('^spam$', 'i'))
            )
            ->sort(['name' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }
}