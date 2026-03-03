<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache\Stores;

use Canvastack\Canvastack\Contracts\CacheStoreInterface;
use Redis;
use RedisException;

/**
 * Redis Cache Store
 * 
 * Implements caching using Redis with tag support.
 */
class RedisStore implements CacheStoreInterface
{
    /**
     * Redis connection.
     */
    protected Redis $redis;

    /**
     * Cache key prefix.
     */
    protected string $prefix;

    /**
     * Tag prefix.
     */
    protected string $tagPrefix = 'tag:';

    /**
     * Create a new Redis store instance.
     * 
     * @throws RedisException
     */
    public function __construct(array $config, string $prefix = 'canvastack')
    {
        // Check if Redis extension is available
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension is not loaded. Please install php-redis extension or use file cache driver.');
        }

        $this->prefix = $prefix;
        $this->redis = new Redis();
        
        $this->redis->connect(
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 6379,
            $config['timeout'] ?? 0.0
        );

        if (isset($config['password']) && $config['password'] !== '') {
            $this->redis->auth($config['password']);
        }

        if (isset($config['database'])) {
            $this->redis->select($config['database']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, array $tags = []): mixed
    {
        $value = $this->redis->get($this->getKey($key));

        if ($value === false) {
            return null;
        }

        return $this->unserialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl, array $tags = []): bool
    {
        $key = $this->getKey($key);
        $value = $this->serialize($value);

        $result = $this->redis->setex($key, $ttl, $value);

        if ($result && !empty($tags)) {
            $this->tagKey($key, $tags);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function forever(string $key, mixed $value, array $tags = []): bool
    {
        $key = $this->getKey($key);
        $value = $this->serialize($value);

        $result = $this->redis->set($key, $value);

        if ($result && !empty($tags)) {
            $this->tagKey($key, $tags);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key, array $tags = []): bool
    {
        return $this->redis->exists($this->getKey($key)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key, array $tags = []): bool
    {
        $key = $this->getKey($key);
        
        if (!empty($tags)) {
            $this->untagKey($key, $tags);
        }

        return $this->redis->del($key) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $tags = []): bool
    {
        if (empty($tags)) {
            return $this->clear();
        }

        foreach ($tags as $tag) {
            $tagKey = $this->getTagKey($tag);
            $keys = $this->redis->sMembers($tagKey);

            if (!empty($keys)) {
                $this->redis->del(...$keys);
                $this->redis->del($tagKey);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return $this->redis->incrBy($this->getKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->redis->decrBy($this->getKey($key), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        try {
            $info = $this->redis->info();
            
            return [
                'driver' => 'redis',
                'connected' => true,
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'keys' => $this->redis->dbSize(),
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
            ];
        } catch (RedisException $e) {
            return [
                'driver' => 'redis',
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $keys = $this->redis->keys($this->prefix . ':*');
        
        if (!empty($keys)) {
            return $this->redis->del(...$keys) > 0;
        }

        return true;
    }

    /**
     * Get the Redis connection.
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * Tag a cache key.
     */
    protected function tagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $this->redis->sAdd($this->getTagKey($tag), $key);
        }
    }

    /**
     * Remove tags from a cache key.
     */
    protected function untagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $this->redis->sRem($this->getTagKey($tag), $key);
        }
    }

    /**
     * Get the full cache key.
     */
    protected function getKey(string $key): string
    {
        return $this->prefix . ':' . $key;
    }

    /**
     * Get the tag key.
     */
    protected function getTagKey(string $tag): string
    {
        return $this->prefix . ':' . $this->tagPrefix . $tag;
    }

    /**
     * Serialize value for storage.
     */
    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * Unserialize value from storage.
     */
    protected function unserialize(string $value): mixed
    {
        return unserialize($value);
    }

    /**
     * Close Redis connection on destruct.
     */
    public function __destruct()
    {
        try {
            $this->redis->close();
        } catch (RedisException $e) {
            // Ignore errors on close
        }
    }
}
