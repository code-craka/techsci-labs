<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Domain;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class DomainRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $classMetadata = $dm->getClassMetadata(Domain::class);
        parent::__construct($dm, $dm->getUnitOfWork(), $classMetadata);
    }

    public function findActiveDomains(): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findByDomainName(string $domain): ?Domain
    {
        return $this->createQueryBuilder()
            ->field('domain')->equals(strtolower(trim($domain)))
            ->getQuery()
            ->getSingleResult();
    }

    public function findValidatedDomains(): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->field('dnsRecords')->elemMatch(
                $this->createQueryBuilder()
                    ->field('type')->equals('MX')
                    ->field('verified')->equals(true)
            )
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDomainsWithCatchAll(): array
    {
        return $this->createQueryBuilder()
            ->field('isCatchAll')->equals(true)
            ->field('isActive')->equals(true)
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDomainsWithPlusAliasing(): array
    {
        return $this->createQueryBuilder()
            ->field('isPlusAliasing')->equals(true)
            ->field('isActive')->equals(true)
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDomainsNearAccountLimit(float $threshold = 0.9): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->where('function() {
                return (this.statistics.totalAccounts / this.maxAccounts) >= ' . $threshold . ';
            }')
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDomainsNearStorageLimit(float $threshold = 0.9): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->where('function() {
                return (this.statistics.totalStorageMb / this.maxStorageMb) >= ' . $threshold . ';
            }')
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function searchDomains(string $query): array
    {
        return $this->createQueryBuilder()
            ->field('domain')->regex(new \MongoDB\BSON\Regex($query, 'i'))
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function getDomainStatistics(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => null,
                    'totalDomains' => ['$sum' => 1],
                    'activeDomains' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isActive', true], 1, 0]
                        ]
                    ],
                    'validatedDomains' => [
                        '$sum' => [
                            '$cond' => [
                                '$gt' => [
                                    ['$size' => [
                                        '$filter' => [
                                            'input' => '$dnsRecords',
                                            'cond' => [
                                                '$and' => [
                                                    ['$eq' => ['$$this.type', 'MX']],
                                                    ['$eq' => ['$$this.verified', true]]
                                                ]
                                            ]
                                        ]
                                    ]],
                                    0
                                ],
                                1,
                                0
                            ]
                        ]
                    ],
                    'catchAllDomains' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isCatchAll', true], 1, 0]
                        ]
                    ],
                    'plusAliasingDomains' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isPlusAliasing', true], 1, 0]
                        ]
                    ],
                    'totalAccounts' => ['$sum' => '$statistics.totalAccounts'],
                    'totalStorage' => ['$sum' => '$statistics.totalStorageMb'],
                    'averageAccountsPerDomain' => ['$avg' => '$statistics.totalAccounts']
                ]
            ]
        ];

        $result = $this->getDocumentManager()
            ->getDocumentCollection(Domain::class)
            ->aggregate($pipeline)
            ->toArray();

        if (empty($result)) {
            return [
                'totalDomains' => 0,
                'activeDomains' => 0,
                'validatedDomains' => 0,
                'catchAllDomains' => 0,
                'plusAliasingDomains' => 0,
                'totalAccounts' => 0,
                'totalStorage' => 0,
                'averageAccountsPerDomain' => 0
            ];
        }

        return $result[0];
    }

    public function findRecentlyCreatedDomains(int $days = 7): array
    {
        $since = new \DateTime('-' . $days . ' days');
        
        return $this->createQueryBuilder()
            ->field('createdAt')->gte($since)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findInactiveDomains(): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(false)
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDomainsWithoutMxRecords(): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->addOr(
                $this->createQueryBuilder()->field('dnsRecords')->size(0),
                $this->createQueryBuilder()->field('dnsRecords')->not(
                    $this->createQueryBuilder()->elemMatch(
                        $this->createQueryBuilder()->field('type')->equals('MX')
                    )
                )
            )
            ->sort(['domain' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function updateDomainStatistics(Domain $domain, array $stats): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($domain->getId())
            ->field('statistics')->set($stats)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function findDomainsByStorageUsage(string $order = 'DESC'): array
    {
        $sortOrder = $order === 'ASC' ? 'ASC' : 'DESC';
        
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->sort(['statistics.totalStorageMb' => $sortOrder])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findDomainsByAccountCount(string $order = 'DESC'): array
    {
        $sortOrder = $order === 'ASC' ? 'ASC' : 'DESC';
        
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->sort(['statistics.totalAccounts' => $sortOrder])
            ->getQuery()
            ->execute()
            ->toArray();
    }
}