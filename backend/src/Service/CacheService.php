<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Centralized cache service for database queries and application data
 * 
 * Provides intelligent caching with:
 * - Automatic cache key generation
 * - Tag-based invalidation
 * - Performance monitoring
 * - Cache warming strategies
 */
class CacheService
{
    private const CACHE_PREFIXES = [
        'db' => 'database:',
        'email' => 'email:',
        'api' => 'api:',
        'search' => 'search:',
        'user' => 'user:',
        'domain' => 'domain:',
        'attachment' => 'attachment:'
    ];

    private const DEFAULT_TTL = 3600; // 1 hour
    private const CACHE_TAGS = [
        'emails' => ['email', 'mailbox', 'message'],
        'domains' => ['domain', 'account'],
        'users' => ['user', 'account', 'token'],
        'attachments' => ['attachment', 'file']
    ];

    public function __construct(
        private readonly CacheInterface $databaseCache,
        private readonly CacheInterface $emailCache,
        private readonly CacheInterface $apiCache,
        private readonly CacheInterface $searchCache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Cache database query result
     */
    public function cacheDbQuery(
        string $key,
        callable $callback,
        int $ttl = self::DEFAULT_TTL,
        array $tags = []
    ): mixed {
        $cacheKey = $this->generateCacheKey('db', $key);
        
        return $this->databaseCache->get($cacheKey, function (ItemInterface $item) use ($callback, $ttl, $tags) {
            $item->expiresAfter($ttl);
            
            if (!empty($tags)) {
                $this->setItemTags($item, $tags);
            }
            
            $startTime = microtime(true);
            $result = $callback();
            $duration = (microtime(true) - $startTime) * 1000;
            
            $this->logger->info('Database query cached', [
                'cache_key' => $item->getKey(),
                'query_time' => $duration,
                'ttl' => $ttl,
                'tags' => $tags
            ]);
            
            return $result;
        });
    }

    /**
     * Cache email data
     */
    public function cacheEmailData(
        string $key,
        callable $callback,
        int $ttl = 1800,
        array $tags = ['emails']
    ): mixed {
        $cacheKey = $this->generateCacheKey('email', $key);
        
        return $this->emailCache->get($cacheKey, function (ItemInterface $item) use ($callback, $ttl, $tags) {
            $item->expiresAfter($ttl);
            $this->setItemTags($item, $tags);
            
            return $callback();
        });
    }

    /**
     * Cache API response
     */
    public function cacheApiResponse(
        string $key,
        callable $callback,
        int $ttl = 300,
        array $tags = ['api']
    ): mixed {
        $cacheKey = $this->generateCacheKey('api', $key);
        
        return $this->apiCache->get($cacheKey, function (ItemInterface $item) use ($callback, $ttl, $tags) {
            $item->expiresAfter($ttl);
            $this->setItemTags($item, $tags);
            
            return $callback();
        });
    }

    /**
     * Cache search results
     */
    public function cacheSearchResults(
        string $query,
        callable $callback,
        int $ttl = 600,
        array $filters = []
    ): mixed {
        $cacheKey = $this->generateSearchCacheKey($query, $filters);
        
        return $this->searchCache->get($cacheKey, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            $this->setItemTags($item, ['search', 'emails']);
            
            return $callback();
        });
    }

    /**
     * Cache user mailbox list
     */
    public function cacheUserMailboxes(string $userId, callable $callback, int $ttl = 1800): mixed
    {
        return $this->cacheDbQuery(
            "user_mailboxes:{$userId}",
            $callback,
            $ttl,
            ['users', 'mailboxes']
        );
    }

    /**
     * Cache message list for mailbox
     */
    public function cacheMailboxMessages(
        string $mailboxId,
        array $criteria,
        callable $callback,
        int $ttl = 900
    ): mixed {
        $criteriaHash = md5(serialize($criteria));
        
        return $this->cacheDbQuery(
            "mailbox_messages:{$mailboxId}:{$criteriaHash}",
            $callback,
            $ttl,
            ['emails', 'messages']
        );
    }

    /**
     * Cache domain statistics
     */
    public function cacheDomainStats(string $domainId, callable $callback, int $ttl = 3600): mixed
    {
        return $this->cacheDbQuery(
            "domain_stats:{$domainId}",
            $callback,
            $ttl,
            ['domains', 'stats']
        );
    }

    /**
     * Cache attachment metadata
     */
    public function cacheAttachmentMeta(string $attachmentId, callable $callback, int $ttl = 7200): mixed
    {
        return $this->cacheDbQuery(
            "attachment_meta:{$attachmentId}",
            $callback,
            $ttl,
            ['attachments']
        );
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags): bool
    {
        try {
            $adapters = [
                $this->databaseCache,
                $this->emailCache,
                $this->apiCache,
                $this->searchCache
            ];
            
            foreach ($adapters as $adapter) {
                if ($adapter instanceof TagAwareAdapterInterface) {
                    $adapter->invalidateTags($tags);
                }
            }
            
            $this->logger->info('Cache invalidated by tags', ['tags' => $tags]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Cache invalidation failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate email-related caches
     */
    public function invalidateEmailCaches(string $mailboxId = null): void
    {
        $tags = ['emails', 'messages'];
        
        if ($mailboxId) {
            // Also clear specific keys
            $this->databaseCache->delete($this->generateCacheKey('db', "mailbox_messages:{$mailboxId}"));
        }
        
        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate user-related caches
     */
    public function invalidateUserCaches(string $userId): void
    {
        $tags = ['users'];
        
        // Clear specific user caches
        $this->databaseCache->delete($this->generateCacheKey('db', "user_mailboxes:{$userId}"));
        
        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate domain-related caches
     */
    public function invalidateDomainCaches(string $domainId): void
    {
        $tags = ['domains'];
        
        // Clear specific domain caches
        $this->databaseCache->delete($this->generateCacheKey('db', "domain_stats:{$domainId}"));
        
        $this->invalidateByTags($tags);
    }

    /**
     * Warm up frequently accessed caches
     */
    public function warmUpCache(array $targets = []): array
    {
        $warmedUp = [];
        
        if (empty($targets) || in_array('popular_queries', $targets)) {
            $warmedUp['popular_queries'] = $this->warmUpPopularQueries();
        }
        
        if (empty($targets) || in_array('domain_stats', $targets)) {
            $warmedUp['domain_stats'] = $this->warmUpDomainStats();
        }
        
        if (empty($targets) || in_array('user_mailboxes', $targets)) {
            $warmedUp['user_mailboxes'] = $this->warmUpUserMailboxes();
        }
        
        $this->logger->info('Cache warmed up', ['targets' => array_keys($warmedUp)]);
        
        return $warmedUp;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would need to be implemented based on cache adapter capabilities
        return [
            'database' => $this->getCachePoolStats($this->databaseCache),
            'email' => $this->getCachePoolStats($this->emailCache),
            'api' => $this->getCachePoolStats($this->apiCache),
            'search' => $this->getCachePoolStats($this->searchCache),
        ];
    }

    /**
     * Clear all caches
     */
    public function clearAllCaches(): bool
    {
        try {
            $this->databaseCache->clear();
            $this->emailCache->clear();
            $this->apiCache->clear();
            $this->searchCache->clear();
            
            $this->logger->info('All caches cleared');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear all caches', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate cache key with prefix
     */
    private function generateCacheKey(string $type, string $key): string
    {
        $prefix = self::CACHE_PREFIXES[$type] ?? 'app:';
        return $prefix . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $key);
    }

    /**
     * Generate search cache key
     */
    private function generateSearchCacheKey(string $query, array $filters): string
    {
        $filtersHash = empty($filters) ? '' : ':' . md5(serialize($filters));
        return $this->generateCacheKey('search', "query:" . md5($query) . $filtersHash);
    }

    /**
     * Set tags on cache item if supported
     */
    private function setItemTags(ItemInterface $item, array $tags): void
    {
        if (method_exists($item, 'tag')) {
            $item->tag($tags);
        }
    }

    /**
     * Warm up popular database queries
     */
    private function warmUpPopularQueries(): int
    {
        $warmed = 0;
        
        // Popular queries that should be cached
        $popularQueries = [
            'recent_messages' => fn() => $this->getRecentMessages(),
            'top_domains' => fn() => $this->getTopDomains(),
            'active_users' => fn() => $this->getActiveUsers(),
        ];
        
        foreach ($popularQueries as $key => $callback) {
            try {
                $this->cacheDbQuery($key, $callback, 3600);
                $warmed++;
            } catch (\Exception $e) {
                $this->logger->warning("Failed to warm up query: {$key}", ['error' => $e->getMessage()]);
            }
        }
        
        return $warmed;
    }

    /**
     * Warm up domain statistics
     */
    private function warmUpDomainStats(): int
    {
        // Implementation would fetch active domains and cache their stats
        return 0;
    }

    /**
     * Warm up user mailbox data
     */
    private function warmUpUserMailboxes(): int
    {
        // Implementation would fetch active users and cache their mailbox data
        return 0;
    }

    /**
     * Get cache pool statistics
     */
    private function getCachePoolStats(CacheInterface $cache): array
    {
        // Basic stats - would need to be extended based on cache implementation
        return [
            'type' => get_class($cache),
            'available' => true
        ];
    }

    /**
     * Placeholder methods for warm-up queries
     */
    private function getRecentMessages(): array
    {
        // Implementation would fetch recent messages
        return [];
    }

    private function getTopDomains(): array
    {
        // Implementation would fetch top domains
        return [];
    }

    private function getActiveUsers(): array
    {
        // Implementation would fetch active users
        return [];
    }
}