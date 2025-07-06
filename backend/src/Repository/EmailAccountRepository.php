<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\EmailAccount;
use App\Document\Domain;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class EmailAccountRepository extends DocumentRepository implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(DocumentManager $dm)
    {
        $classMetadata = $dm->getClassMetadata(EmailAccount::class);
        parent::__construct($dm, $dm->getUnitOfWork(), $classMetadata);
    }

    // UserProviderInterface implementation
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->findOneBy(['email' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof EmailAccount) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return EmailAccount::class === $class || is_subclass_of($class, EmailAccount::class);
    }

    // PasswordUpgraderInterface implementation
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof EmailAccount) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
    }

    // Custom repository methods
    public function findByEmail(string $email): ?EmailAccount
    {
        return $this->createQueryBuilder()
            ->field('email')->equals(strtolower(trim($email)))
            ->getQuery()
            ->getSingleResult();
    }

    public function findActiveAccounts(): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findByDomain(Domain $domain): array
    {
        return $this->createQueryBuilder()
            ->field('domain.id')->equals($domain->getId())
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findActiveBeDomain(Domain $domain): array
    {
        return $this->createQueryBuilder()
            ->field('domain.id')->equals($domain->getId())
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAccountsNearQuotaLimit(float $threshold = 0.9): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->where('function() {
                return this.quotaMb > 0 && (this.usedQuotaMb / this.quotaMb) >= ' . $threshold . ';
            }')
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAccountsExceedingQuota(): array
    {
        return $this->createQueryBuilder()
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->where('function() {
                return this.quotaMb > 0 && this.usedQuotaMb >= this.quotaMb;
            }')
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findExpiredAccounts(): array
    {
        return $this->createQueryBuilder()
            ->field('expiresAt')->lt(new \DateTime())
            ->field('isDeleted')->equals(false)
            ->sort(['expiresAt' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAccountsExpiringIn(\DateInterval $interval): array
    {
        $futureDate = (new \DateTime())->add($interval);

        return $this->createQueryBuilder()
            ->field('expiresAt')->lte($futureDate)
            ->field('expiresAt')->gte(new \DateTime())
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->sort(['expiresAt' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findInactiveAccounts(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder()
            ->addOr(
                $this->createQueryBuilder()->field('lastActivityAt')->lt($since),
                $this->createQueryBuilder()->field('lastActivityAt')->exists(false)
            )
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->sort(['lastActivityAt' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function searchAccountsByEmail(string $query): array
    {
        return $this->createQueryBuilder()
            ->field('email')->regex(new \MongoDB\BSON\Regex($query, 'i'))
            ->field('isDeleted')->equals(false)
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAccountsByTag(string $tag): array
    {
        return $this->createQueryBuilder()
            ->field('tags')->in([$tag])
            ->field('isDeleted')->equals(false)
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAccountsWithAliases(): array
    {
        return $this->createQueryBuilder()
            ->field('aliases')->exists(true)
            ->field('aliases.0')->exists(true) // Has at least one alias
            ->field('isDeleted')->equals(false)
            ->sort(['email' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findAccountByAlias(string $alias): ?EmailAccount
    {
        return $this->createQueryBuilder()
            ->field('aliases')->in([strtolower(trim($alias))])
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->getQuery()
            ->getSingleResult();
    }

    public function getAccountStatistics(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => null,
                    'totalAccounts' => ['$sum' => 1],
                    'activeAccounts' => [
                        '$sum' => [
                            '$cond' => [
                                '$and' => [
                                    ['$eq' => ['$isActive', true]],
                                    ['$eq' => ['$isDeleted', false]]
                                ],
                                1,
                                0
                            ]
                        ]
                    ],
                    'deletedAccounts' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isDeleted', true], 1, 0]
                        ]
                    ],
                    'expiredAccounts' => [
                        '$sum' => [
                            '$cond' => [
                                '$and' => [
                                    ['$ne' => ['$expiresAt', null]],
                                    ['$lt' => ['$expiresAt', new \DateTime()]]
                                ],
                                1,
                                0
                            ]
                        ]
                    ],
                    'totalQuotaUsed' => ['$sum' => '$usedQuotaMb'],
                    'totalQuotaAllocated' => ['$sum' => '$quotaMb'],
                    'averageQuotaUsage' => ['$avg' => '$usedQuotaMb'],
                    'accountsWithAliases' => [
                        '$sum' => [
                            '$cond' => [
                                '$gt' => [['$size' => '$aliases'], 0],
                                1,
                                0
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->getDocumentManager()
            ->getDocumentCollection(EmailAccount::class)
            ->aggregate($pipeline)
            ->toArray();

        if (empty($result)) {
            return [
                'totalAccounts' => 0,
                'activeAccounts' => 0,
                'deletedAccounts' => 0,
                'expiredAccounts' => 0,
                'totalQuotaUsed' => 0,
                'totalQuotaAllocated' => 0,
                'averageQuotaUsage' => 0,
                'accountsWithAliases' => 0
            ];
        }

        return $result[0];
    }

    public function findRecentlyCreatedAccounts(int $days = 7): array
    {
        $since = new \DateTime('-' . $days . ' days');
        
        return $this->createQueryBuilder()
            ->field('createdAt')->gte($since)
            ->field('isDeleted')->equals(false)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMostActiveAccounts(int $limit = 10): array
    {
        return $this->createQueryBuilder()
            ->field('lastActivityAt')->exists(true)
            ->field('isActive')->equals(true)
            ->field('isDeleted')->equals(false)
            ->sort(['lastActivityAt' => 'DESC'])
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function updateAccountQuotaUsage(EmailAccount $account, int $usedQuotaMb): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($account->getId())
            ->field('usedQuotaMb')->set($usedQuotaMb)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function updateAccountStatistics(EmailAccount $account, array $stats): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($account->getId())
            ->field('statistics')->set($stats)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function markAccountAsActive(EmailAccount $account): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($account->getId())
            ->field('lastActivityAt')->set(new \DateTime())
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function softDeleteAccount(EmailAccount $account): void
    {
        $this->createQueryBuilder()
            ->updateOne()
            ->field('id')->equals($account->getId())
            ->field('isDeleted')->set(true)
            ->field('isActive')->set(false)
            ->field('updatedAt')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function findAccountsByQuotaRange(int $minQuota, int $maxQuota): array
    {
        return $this->createQueryBuilder()
            ->field('quotaMb')->gte($minQuota)
            ->field('quotaMb')->lte($maxQuota)
            ->field('isDeleted')->equals(false)
            ->sort(['quotaMb' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }
}