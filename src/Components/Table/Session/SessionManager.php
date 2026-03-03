<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Session;

use Canvastack\Canvastack\Support\Cache\CacheManager;

/**
 * SessionManager - Manages session persistence for TableBuilder.
 *
 * Handles saving and loading table state (filters, tabs, display limits)
 * to/from session storage with Redis caching for performance.
 */
class SessionManager
{
    /**
     * Session key for storing data.
     */
    protected string $sessionKey;

    /**
     * Session data cache.
     */
    protected array $data = [];

    /**
     * Cache manager instance.
     */
    protected ?CacheManager $cache = null;

    /**
     * Cache TTL in seconds (5 minutes default).
     */
    protected int $cacheTtl = 300;

    /**
     * Cache tags for this session.
     */
    protected array $cacheTags = ['table_sessions'];

    /**
     * Whether Redis caching is enabled.
     */
    protected bool $cacheEnabled = false;

    /**
     * Create a new SessionManager instance.
     *
     * @param string $tableName Table name for session key generation
     * @param string $context Additional context for session key (optional)
     * @param CacheManager|null $cache Cache manager instance (optional)
     */
    public function __construct(string $tableName, string $context = '', ?CacheManager $cache = null)
    {
        $this->sessionKey = $this->generateKey($tableName, $context);
        $this->cache = $cache;
        $this->cacheEnabled = $cache !== null && config('canvastack.cache.enabled', true);
        
        $this->load();
    }

    /**
     * Generate unique session key.
     *
     * Creates a unique session key based on:
     * - Table name
     * - Current request path
     * - User ID (or 'guest' if not authenticated)
     * - Additional context
     *
     * @param string $tableName Table name
     * @param string $context Additional context
     * @return string Generated session key
     */
    protected function generateKey(string $tableName, string $context): string
    {
        $path = request()->path();
        $userId = auth()->id() ?? 'guest';

        return 'table_session_' . md5($tableName . '_' . $path . '_' . $userId . '_' . $context);
    }

    /**
     * Save data to session and cache.
     *
     * Merges new data with existing session data and persists to both
     * session storage and Redis cache for improved performance.
     *
     * @param array $data Data to save
     * @return void
     */
    public function save(array $data): void
    {
        $this->data = array_merge($this->data, $data);
        
        // Save to session storage
        session([$this->sessionKey => $this->data]);
        
        // Save to Redis cache if enabled
        if ($this->cacheEnabled) {
            $this->saveToCache();
        }
    }

    /**
     * Load data from cache or session.
     *
     * Attempts to load from Redis cache first for better performance,
     * falls back to session storage if cache miss.
     *
     * @return array Loaded session data
     */
    public function load(): array
    {
        // Try loading from cache first
        if ($this->cacheEnabled) {
            $cached = $this->loadFromCache();
            
            if ($cached !== null) {
                $this->data = $cached;
                return $this->data;
            }
        }
        
        // Fallback to session storage
        $this->data = session($this->sessionKey, []);
        
        // Warm cache with session data
        if ($this->cacheEnabled && !empty($this->data)) {
            $this->saveToCache();
        }

        return $this->data;
    }

    /**
     * Get specific value from session.
     *
     * @param string $key Data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Value from session or default
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Clear all session data and cache.
     *
     * Removes all data from session storage, internal cache, and Redis cache.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
        session()->forget($this->sessionKey);
        
        // Clear from Redis cache
        if ($this->cacheEnabled) {
            $this->invalidateCache();
        }
    }

    /**
     * Check if key exists in session.
     *
     * @param string $key Data key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get session key.
     *
     * @return string Current session key
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * Get all session data.
     *
     * @return array All session data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Remove specific key from session and cache.
     *
     * @param string $key Data key to remove
     * @return void
     */
    public function forget(string $key): void
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            session([$this->sessionKey => $this->data]);
            
            // Update cache
            if ($this->cacheEnabled) {
                $this->saveToCache();
            }
        }
    }

    /**
     * Set specific value in session and cache.
     *
     * @param string $key Data key
     * @param mixed $value Value to set
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
        session([$this->sessionKey => $this->data]);
        
        // Update cache
        if ($this->cacheEnabled) {
            $this->saveToCache();
        }
    }

    /**
     * Save data to Redis cache.
     *
     * @return void
     */
    protected function saveToCache(): void
    {
        if ($this->cache === null) {
            return;
        }

        try {
            $this->cache
                ->tags($this->cacheTags)
                ->put($this->getCacheKey(), $this->data, $this->cacheTtl);
        } catch (\Exception $e) {
            // Log error but don't fail - session storage is still available
            if (function_exists('logger')) {
                logger()->warning('Failed to save session to cache', [
                    'key' => $this->sessionKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Load data from Redis cache.
     *
     * @return array|null Cached data or null if not found
     */
    protected function loadFromCache(): ?array
    {
        if ($this->cache === null) {
            return null;
        }

        try {
            $data = $this->cache
                ->tags($this->cacheTags)
                ->get($this->getCacheKey());
            
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            // Log error but don't fail - will fallback to session storage
            if (function_exists('logger')) {
                logger()->warning('Failed to load session from cache', [
                    'key' => $this->sessionKey,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return null;
        }
    }

    /**
     * Invalidate cache for this session.
     *
     * @return void
     */
    protected function invalidateCache(): void
    {
        if ($this->cache === null) {
            return;
        }

        try {
            $this->cache
                ->tags($this->cacheTags)
                ->forget($this->getCacheKey());
        } catch (\Exception $e) {
            // Log error but don't fail
            if (function_exists('logger')) {
                logger()->warning('Failed to invalidate session cache', [
                    'key' => $this->sessionKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get cache key for this session.
     *
     * @return string Cache key
     */
    protected function getCacheKey(): string
    {
        return 'session:' . $this->sessionKey;
    }

    /**
     * Set cache TTL.
     *
     * @param int $ttl TTL in seconds
     * @return self
     */
    public function setCacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Get cache TTL.
     *
     * @return int TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool True if caching is enabled
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    /**
     * Warm cache with current data.
     *
     * Useful for pre-populating cache after bulk operations.
     *
     * @return void
     */
    public function warmCache(): void
    {
        if ($this->cacheEnabled && !empty($this->data)) {
            $this->saveToCache();
        }
    }
}
