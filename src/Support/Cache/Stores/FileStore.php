<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Cache\Stores;

use Canvastack\Canvastack\Contracts\CacheStoreInterface;

/**
 * File Cache Store
 * 
 * Implements caching using the filesystem with tag support.
 * Used as fallback when Redis is not available.
 */
class FileStore implements CacheStoreInterface
{
    /**
     * Cache directory path.
     */
    protected string $path;

    /**
     * Cache key prefix.
     */
    protected string $prefix;

    /**
     * Tag directory name.
     */
    protected string $tagDir = 'tags';

    /**
     * Create a new file store instance.
     */
    public function __construct(string $path, string $prefix = 'canvastack')
    {
        $this->path = rtrim($path, '/');
        $this->prefix = $prefix;

        $this->ensureDirectoryExists($this->path);
        $this->ensureDirectoryExists($this->getTagPath());
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, array $tags = []): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $contents = file_get_contents($file);
        
        if ($contents === false) {
            return null;
        }

        $data = unserialize($contents);

        // Check if expired
        if ($data['expires_at'] !== null && time() >= $data['expires_at']) {
            $this->forget($key, $tags);
            return null;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $key, mixed $value, int $ttl, array $tags = []): bool
    {
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
        ];

        $result = file_put_contents($file, serialize($data), LOCK_EX) !== false;

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
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expires_at' => null,
        ];

        $result = file_put_contents($file, serialize($data), LOCK_EX) !== false;

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
        return $this->get($key, $tags) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function forget(string $key, array $tags = []): bool
    {
        $file = $this->getFilePath($key);

        if (!empty($tags)) {
            $this->untagKey($key, $tags);
        }

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
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
            $keys = $this->getTaggedKeys($tag);
            
            foreach ($keys as $key) {
                $this->forget($key);
            }

            $this->deleteTagFile($tag);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        $current = $this->get($key);

        if ($current === null) {
            $current = 0;
        }

        if (!is_numeric($current)) {
            return false;
        }

        $new = (int)$current + $value;
        
        $this->forever($key, $new);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return $this->increment($key, -$value);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        $files = glob($this->path . '/*');
        $totalSize = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }

        return [
            'driver' => 'file',
            'path' => $this->path,
            'keys' => count($files),
            'size' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $files = glob($this->path . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Clear tag directory
        $tagFiles = glob($this->getTagPath() . '/*');
        
        foreach ($tagFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Tag a cache key.
     */
    protected function tagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagFile = $this->getTagFilePath($tag);
            $keys = $this->getTaggedKeys($tag);
            
            if (!in_array($key, $keys)) {
                $keys[] = $key;
                file_put_contents($tagFile, serialize($keys), LOCK_EX);
            }
        }
    }

    /**
     * Remove tags from a cache key.
     */
    protected function untagKey(string $key, array $tags): void
    {
        foreach ($tags as $tag) {
            $keys = $this->getTaggedKeys($tag);
            $keys = array_filter($keys, fn($k) => $k !== $key);
            
            $tagFile = $this->getTagFilePath($tag);
            file_put_contents($tagFile, serialize($keys), LOCK_EX);
        }
    }

    /**
     * Get keys tagged with the given tag.
     */
    protected function getTaggedKeys(string $tag): array
    {
        $tagFile = $this->getTagFilePath($tag);

        if (!file_exists($tagFile)) {
            return [];
        }

        $contents = file_get_contents($tagFile);
        
        if ($contents === false) {
            return [];
        }

        return unserialize($contents);
    }

    /**
     * Delete a tag file.
     */
    protected function deleteTagFile(string $tag): bool
    {
        $tagFile = $this->getTagFilePath($tag);

        if (file_exists($tagFile)) {
            return unlink($tagFile);
        }

        return true;
    }

    /**
     * Get the file path for a cache key.
     */
    protected function getFilePath(string $key): string
    {
        return $this->path . '/' . $this->prefix . '_' . md5($key);
    }

    /**
     * Get the tag directory path.
     */
    protected function getTagPath(): string
    {
        return $this->path . '/' . $this->tagDir;
    }

    /**
     * Get the file path for a tag.
     */
    protected function getTagFilePath(string $tag): string
    {
        return $this->getTagPath() . '/' . $this->prefix . '_tag_' . md5($tag);
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
