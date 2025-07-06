<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Attachment;
use App\Document\Message;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class AttachmentRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $classMetadata = $dm->getClassMetadata(Attachment::class);
        parent::__construct($dm, $dm->getUnitOfWork(), $classMetadata);
    }

    public function findByMessage(Message $message): array
    {
        return $this->createQueryBuilder()
            ->field('message.id')->equals($message->getId())
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findByContentType(string $contentType): array
    {
        return $this->createQueryBuilder()
            ->field('contentType')->equals($contentType)
            ->sort(['filename' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findImageAttachments(): array
    {
        return $this->createQueryBuilder()
            ->field('isImage')->equals(true)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDocumentAttachments(): array
    {
        return $this->createQueryBuilder()
            ->field('isDocument')->equals(true)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findLargeAttachments(float $sizeMbThreshold = 10.0): array
    {
        return $this->createQueryBuilder()
            ->field('sizeMb')->gte($sizeMbThreshold)
            ->sort(['sizeMb' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findQuarantinedAttachments(): array
    {
        return $this->createQueryBuilder()
            ->field('securityInfo.quarantined')->equals(true)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAttachmentsByFilename(string $filename): array
    {
        return $this->createQueryBuilder()
            ->field('filename')->regex(new \MongoDB\BSON\Regex($filename, 'i'))
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAttachmentsByExtension(string $extension): array
    {
        $pattern = '.*\.' . preg_quote($extension, '/') . '$';
        return $this->createQueryBuilder()
            ->field('filename')->regex(new \MongoDB\BSON\Regex($pattern, 'i'))
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAttachmentsNeedingVirusScan(): array
    {
        return $this->createQueryBuilder()
            ->field('securityInfo.virusScanStatus')->exists(false)
            ->field('securityInfo.quarantined')->equals(false)
            ->sort(['createdAt' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function getTotalStorageUsed(): float
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => null,
                    'totalSize' => ['$sum' => '$sizeMb']
                ]
            ]
        ];

        $result = $this->getDocumentManager()
            ->getDocumentCollection(Attachment::class)
            ->aggregate($pipeline)
            ->toArray();

        return $result[0]['totalSize'] ?? 0.0;
    }

    public function getAttachmentStatistics(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => null,
                    'totalCount' => ['$sum' => 1],
                    'totalSize' => ['$sum' => '$sizeMb'],
                    'avgSize' => ['$avg' => '$sizeMb'],
                    'maxSize' => ['$max' => '$sizeMb'],
                    'imageCount' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isImage', true], 1, 0]
                        ]
                    ],
                    'documentCount' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isDocument', true], 1, 0]
                        ]
                    ],
                    'archiveCount' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isArchive', true], 1, 0]
                        ]
                    ],
                    'quarantinedCount' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$securityInfo.quarantined', true], 1, 0]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->getDocumentManager()
            ->getDocumentCollection(Attachment::class)
            ->aggregate($pipeline)
            ->toArray();

        if (empty($result)) {
            return [
                'totalCount' => 0,
                'totalSize' => 0.0,
                'avgSize' => 0.0,
                'maxSize' => 0.0,
                'imageCount' => 0,
                'documentCount' => 0,
                'archiveCount' => 0,
                'quarantinedCount' => 0
            ];
        }

        return $result[0];
    }

    public function findAttachmentsByContentTypeStats(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => '$contentType',
                    'count' => ['$sum' => 1],
                    'totalSize' => ['$sum' => '$sizeMb'],
                    'avgSize' => ['$avg' => '$sizeMb']
                ]
            ],
            [
                '$sort' => ['count' => -1]
            ]
        ];

        return $this->getDocumentManager()
            ->getDocumentCollection(Attachment::class)
            ->aggregate($pipeline)
            ->toArray();
    }

    public function deleteOrphanedAttachments(): int
    {
        // Find attachments where message no longer exists
        $orphanedAttachments = $this->createQueryBuilder()
            ->field('message')->exists(false)
            ->getQuery()
            ->execute();

        $count = 0;
        foreach ($orphanedAttachments as $attachment) {
            $this->getDocumentManager()->remove($attachment);
            $count++;
        }

        if ($count > 0) {
            $this->getDocumentManager()->flush();
        }

        return $count;
    }

    public function findRecentAttachments(int $limit = 50): array
    {
        return $this->createQueryBuilder()
            ->sort(['createdAt' => 'DESC'])
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAttachmentsByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder()
            ->field('createdAt')->gte($startDate)
            ->field('createdAt')->lte($endDate)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }
}