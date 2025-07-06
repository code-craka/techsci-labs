<?php

declare(strict_types=1);

namespace App\Repository;

use App\Document\Token;
use App\Document\EmailAccount;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class TokenRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $classMetadata = $dm->getClassMetadata(Token::class);
        parent::__construct($dm, $dm->getUnitOfWork(), $classMetadata);
    }

    public function findValidToken(string $token): ?Token
    {
        return $this->createQueryBuilder()
            ->field('token')->equals($token)
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false)
            ->field('revokedAt')->exists(false)
            ->field('expiresAt')->gte(new \DateTime())
            ->getQuery()
            ->getSingleResult();
    }

    public function findByEmailAccount(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findByType(string $type): array
    {
        return $this->createQueryBuilder()
            ->field('type')->equals($type)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findActiveTokensByAccount(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false)
            ->field('revokedAt')->exists(false)
            ->field('expiresAt')->gte(new \DateTime())
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findExpiredTokens(): array
    {
        return $this->createQueryBuilder()
            ->addOr(
                $this->createQueryBuilder()
                    ->field('expiresAt')->lt(new \DateTime())
                    ->field('isExpired')->equals(false)
            )
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findApiTokensByAccount(EmailAccount $emailAccount): array
    {
        return $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('type')->equals(Token::TYPE_API_ACCESS)
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false)
            ->field('revokedAt')->exists(false)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findTokensNeedingCleanup(?\DateTimeInterface $before = null): array
    {
        if (!$before) {
            $before = new \DateTime('-30 days'); // Default: older than 30 days
        }

        return $this->createQueryBuilder()
            ->addOr(
                $this->createQueryBuilder()
                    ->field('isUsed')->equals(true)
                    ->field('usedAt')->lt($before),
                $this->createQueryBuilder()
                    ->field('expiresAt')->lt($before),
                $this->createQueryBuilder()
                    ->field('revokedAt')->lt($before)
            )
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findUnusedTokensOlderThan(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder()
            ->field('isUsed')->equals(false)
            ->field('createdAt')->lt($date)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findTokensByScope(string $scope): array
    {
        return $this->createQueryBuilder()
            ->field('scopes')->in([$scope])
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false)
            ->field('revokedAt')->exists(false)
            ->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findEmailVerificationTokens(EmailAccount $emailAccount = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('type')->equals(Token::TYPE_EMAIL_VERIFICATION)
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false);

        if ($emailAccount) {
            $qb->field('emailAccount.id')->equals($emailAccount->getId());
        }

        return $qb->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findPasswordResetTokens(EmailAccount $emailAccount = null): array
    {
        $qb = $this->createQueryBuilder()
            ->field('type')->equals(Token::TYPE_PASSWORD_RESET)
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false);

        if ($emailAccount) {
            $qb->field('emailAccount.id')->equals($emailAccount->getId());
        }

        return $qb->sort(['createdAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function revokeAllTokensForAccount(EmailAccount $emailAccount, string $reason = 'Account security'): int
    {
        $tokens = $this->findActiveTokensByAccount($emailAccount);
        $count = 0;

        foreach ($tokens as $token) {
            $token->revoke($reason, 'system');
            $this->getDocumentManager()->persist($token);
            $count++;
        }

        if ($count > 0) {
            $this->getDocumentManager()->flush();
        }

        return $count;
    }

    public function revokeTokensByType(EmailAccount $emailAccount, string $type, string $reason = 'Token cleanup'): int
    {
        $tokens = $this->createQueryBuilder()
            ->field('emailAccount.id')->equals($emailAccount->getId())
            ->field('type')->equals($type)
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false)
            ->field('revokedAt')->exists(false)
            ->getQuery()
            ->execute();

        $count = 0;
        foreach ($tokens as $token) {
            $token->revoke($reason, 'system');
            $this->getDocumentManager()->persist($token);
            $count++;
        }

        if ($count > 0) {
            $this->getDocumentManager()->flush();
        }

        return $count;
    }

    public function markExpiredTokens(): int
    {
        $expiredTokens = $this->createQueryBuilder()
            ->field('expiresAt')->lt(new \DateTime())
            ->field('isExpired')->equals(false)
            ->getQuery()
            ->execute();

        $count = 0;
        foreach ($expiredTokens as $token) {
            $token->setIsExpired(true);
            $this->getDocumentManager()->persist($token);
            $count++;
        }

        if ($count > 0) {
            $this->getDocumentManager()->flush();
        }

        return $count;
    }

    public function cleanupOldTokens(?\DateTimeInterface $before = null): int
    {
        if (!$before) {
            $before = new \DateTime('-90 days'); // Default: older than 90 days
        }

        $tokensToDelete = $this->findTokensNeedingCleanup($before);
        $count = 0;

        foreach ($tokensToDelete as $token) {
            $this->getDocumentManager()->remove($token);
            $count++;
        }

        if ($count > 0) {
            $this->getDocumentManager()->flush();
        }

        return $count;
    }

    public function getTokenStatistics(): array
    {
        $pipeline = [
            [
                '$group' => [
                    '_id' => '$type',
                    'total' => ['$sum' => 1],
                    'active' => [
                        '$sum' => [
                            '$cond' => [
                                '$and' => [
                                    ['$eq' => ['$isUsed', false]],
                                    ['$eq' => ['$isExpired', false]],
                                    ['$eq' => ['$revokedAt', null]]
                                ],
                                1,
                                0
                            ]
                        ]
                    ],
                    'used' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isUsed', true], 1, 0]
                        ]
                    ],
                    'expired' => [
                        '$sum' => [
                            '$cond' => ['$eq' => ['$isExpired', true], 1, 0]
                        ]
                    ],
                    'revoked' => [
                        '$sum' => [
                            '$cond' => ['$ne' => ['$revokedAt', null], 1, 0]
                        ]
                    ]
                ]
            ],
            [
                '$sort' => ['total' => -1]
            ]
        ];

        return $this->getDocumentManager()
            ->getDocumentCollection(Token::class)
            ->aggregate($pipeline)
            ->toArray();
    }

    public function findTokensExpiringIn(\DateInterval $interval): array
    {
        $futureDate = (new \DateTime())->add($interval);

        return $this->createQueryBuilder()
            ->field('expiresAt')->lte($futureDate)
            ->field('expiresAt')->gte(new \DateTime())
            ->field('isUsed')->equals(false)
            ->field('isExpired')->equals(false)
            ->field('revokedAt')->exists(false)
            ->sort(['expiresAt' => 'ASC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findMostUsedTokens(int $limit = 10): array
    {
        return $this->createQueryBuilder()
            ->field('usageCount')->gt(0)
            ->sort(['usageCount' => 'DESC'])
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function findTokensByIpAddress(string $ipAddress): array
    {
        return $this->createQueryBuilder()
            ->field('ipAddress')->equals($ipAddress)
            ->sort(['lastUsedAt' => 'DESC'])
            ->getQuery()
            ->execute()
            ->toArray();
    }
}