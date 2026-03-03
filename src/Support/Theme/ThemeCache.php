<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Theme Cache Manager.
 *
 * Handles caching of theme configurations and compiled CSS
 * to improve performance and reduce filesystem operations.
 */
class ThemeCache
{
    /**
     * Cache repository.
     *
     * @var CacheRepository
     */
    protected CacheRepository $cache;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected string $prefix = 'canvastack.theme';

    /**
     * Default cache TTL in seconds.
     *
     * @var int
     */
    protected int $ttl = 3600;

    /**
     * Cache tags.
     *
     * @var array<string>
     */
    protected array $tags = ['canvastack', 'themes'];

    /**
     * Create a new theme cache instance.
     *
     * @param CacheRepository|null $cache
     * @param int|null $ttl
     */
    public function __construct(?CacheRepository $cache = null, ?int $ttl = null)
    {
        if ($cache === null) {
            // Create a simple array cache if no cache provided
            $store = new \Illuminate\Cache\ArrayStore();
            $this->cache = new \Illuminate\Cache\Repository($store);
        } else {
            $this->cache = $cache;
        }

        $this->ttl = $ttl ?? 3600;
    }

    /**
     * Get a theme from cache.
     *
     * @param string $name
     * @return ThemeInterface|null
     */
    public function get(string $name): ?ThemeInterface
    {
        $key = $this->makeKey('theme', $name);

        return $this->cache->get($key);
    }

    /**
     * Store a theme in cache.
     *
     * @param ThemeInterface $theme
     * @param int|null $ttl
     * @return bool
     */
    public function put(ThemeInterface $theme, ?int $ttl = null): bool
    {
        $key = $this->makeKey('theme', $theme->getName());
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->put($key, $theme, $ttl);
    }

    /**
     * Get all themes from cache.
     *
     * @return array<ThemeInterface>|null
     */
    public function getAll(): ?array
    {
        $key = $this->makeKey('all');

        return $this->cache->get($key);
    }

    /**
     * Store all themes in cache.
     *
     * @param array<ThemeInterface> $themes
     * @param int|null $ttl
     * @return bool
     */
    public function putAll(array $themes, ?int $ttl = null): bool
    {
        $key = $this->makeKey('all');
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->put($key, $themes, $ttl);
    }

    /**
     * Get compiled CSS from cache.
     *
     * @param string $themeName
     * @return string|null
     */
    public function getCompiledCss(string $themeName): ?string
    {
        $key = $this->makeKey('css', $themeName);

        return $this->cache->get($key);
    }

    /**
     * Store compiled CSS in cache.
     *
     * @param string $themeName
     * @param string $css
     * @param int|null $ttl
     * @return bool
     */
    public function putCompiledCss(string $themeName, string $css, ?int $ttl = null): bool
    {
        $key = $this->makeKey('css', $themeName);
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->put($key, $css, $ttl);
    }

    /**
     * Get CSS variables from cache.
     *
     * @param string $themeName
     * @return array<string, string>|null
     */
    public function getCssVariables(string $themeName): ?array
    {
        $key = $this->makeKey('variables', $themeName);

        return $this->cache->get($key);
    }

    /**
     * Store CSS variables in cache.
     *
     * @param string $themeName
     * @param array<string, string> $variables
     * @param int|null $ttl
     * @return bool
     */
    public function putCssVariables(string $themeName, array $variables, ?int $ttl = null): bool
    {
        $key = $this->makeKey('variables', $themeName);
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->put($key, $variables, $ttl);
    }

    /**
     * Check if a theme exists in cache.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        $key = $this->makeKey('theme', $name);

        return $this->cache->has($key);
    }

    /**
     * Remove a theme from cache.
     *
     * @param string $name
     * @return bool
     */
    public function forget(string $name): bool
    {
        $themeKey = $this->makeKey('theme', $name);
        $cssKey = $this->makeKey('css', $name);
        $variablesKey = $this->makeKey('variables', $name);

        $this->cache->forget($themeKey);
        $this->cache->forget($cssKey);
        $this->cache->forget($variablesKey);

        return true;
    }

    /**
     * Clear all theme caches.
     *
     * @return bool
     */
    public function flush(): bool
    {
        // If cache driver supports tags, use them
        if (method_exists($this->cache, 'tags')) {
            return $this->cache->tags($this->tags)->flush();
        }

        // For ArrayStore and other stores, use the flush method if available
        $store = $this->cache->getStore();
        if (method_exists($store, 'flush')) {
            $store->flush();

            return true;
        }

        // Fallback: forget known keys (won't work with wildcards)
        $this->cache->forget($this->makeKey('all'));

        return true;
    }

    /**
     * Remember a value in cache.
     *
     * @param string $key
     * @param int|null $ttl
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->remember($key, $ttl, $callback);
    }

    /**
     * Remember a theme in cache.
     *
     * @param string $name
     * @param callable $callback
     * @param int|null $ttl
     * @return ThemeInterface
     */
    public function rememberTheme(string $name, callable $callback, ?int $ttl = null): ThemeInterface
    {
        $key = $this->makeKey('theme', $name);
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->remember($key, $ttl, $callback);
    }

    /**
     * Remember compiled CSS in cache.
     *
     * @param string $themeName
     * @param callable $callback
     * @param int|null $ttl
     * @return string
     */
    public function rememberCompiledCss(string $themeName, callable $callback, ?int $ttl = null): string
    {
        $key = $this->makeKey('css', $themeName);
        $ttl = $ttl ?? $this->ttl;

        return $this->cache->remember($key, $ttl, $callback);
    }

    /**
     * Make a cache key.
     *
     * @param string ...$parts
     * @return string
     */
    protected function makeKey(string ...$parts): string
    {
        return $this->prefix . '.' . implode('.', $parts);
    }

    /**
     * Set the cache TTL.
     *
     * @param int $seconds
     * @return self
     */
    public function setTtl(int $seconds): self
    {
        $this->ttl = $seconds;

        return $this;
    }

    /**
     * Get the cache TTL.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set the cache key prefix.
     *
     * @param string $prefix
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set cache tags.
     *
     * @param array<string> $tags
     * @return self
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get cache tags.
     *
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Get the cache repository.
     *
     * @return CacheRepository
     */
    public function getCache(): CacheRepository
    {
        return $this->cache;
    }
}
