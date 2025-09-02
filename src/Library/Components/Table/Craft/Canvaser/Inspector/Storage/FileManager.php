<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Storage;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\InspectorConfig;

/**
 * File Storage Management
 *
 * Handles all file operations for the Inspector module including
 * storage, organization, cleanup, and file management.
 *
 * @version 1.0.0
 */
class FileManager
{
    /**
     * Store diagnostic data to file.
     *
     * @param  array  $data Diagnostic data to store
     * @return string|null File path if successful, null if failed
     */
    public static function store(array $data): ?string
    {
        try {
            $directory = self::ensureDirectory();
            if (! $directory) {
                return null;
            }

            $filename = self::generateFilename($data);
            $filepath = $directory.DIRECTORY_SEPARATOR.$filename;

            // Format data for storage
            $formattedData = JsonFormatter::format($data);

            // Check file size limit
            if (strlen($formattedData) > InspectorConfig::getMaxFileSize()) {
                if (InspectorConfig::isDebugMode()) {
                    error_log('Inspector: Data too large for storage ('.strlen($formattedData).' bytes)');
                }

                return null;
            }

            // Write file
            $result = @file_put_contents($filepath, $formattedData, LOCK_EX);

            if ($result === false) {
                if (InspectorConfig::isDebugMode()) {
                    error_log("Inspector: Failed to write file: {$filepath}");
                }

                return null;
            }

            // Trigger cleanup if needed
            self::triggerCleanupIfNeeded();

            return $filepath;

        } catch (\Throwable $e) {
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector storage error: '.$e->getMessage());
            }

            return null;
        }
    }

    /**
     * Quick dump for immediate debugging.
     *
     * @param  array  $data Data to dump
     * @return string|null File path if successful
     */
    public static function quickDump(array $data): ?string
    {
        try {
            $directory = self::ensureDirectory('quick-dumps');
            if (! $directory) {
                return null;
            }

            $filename = 'dump_'.date('Ymd_His').'_'.uniqid().'.json';
            $filepath = $directory.DIRECTORY_SEPARATOR.$filename;

            $formattedData = JsonFormatter::format($data);
            $result = @file_put_contents($filepath, $formattedData, LOCK_EX);

            return $result !== false ? $filepath : null;

        } catch (\Throwable $e) {
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector quick dump error: '.$e->getMessage());
            }

            return null;
        }
    }

    /**
     * Clean up old files.
     *
     * @param  int|null  $daysOld Files older than this many days will be removed
     * @return int Number of files cleaned up
     */
    public static function cleanup(?int $daysOld = null): int
    {
        $daysOld = $daysOld ?? InspectorConfig::getCleanupDays();
        $directory = InspectorConfig::getFullStoragePath();

        if (! is_dir($directory)) {
            return 0;
        }

        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $cleanedCount = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                    if (@unlink($file->getPathname())) {
                        $cleanedCount++;
                    }
                }
            }

            // Also enforce max files limit
            $cleanedCount += self::enforceMaxFiles();

        } catch (\Throwable $e) {
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector cleanup error: '.$e->getMessage());
            }
        }

        return $cleanedCount;
    }

    /**
     * Get list of stored files.
     *
     * @param  string|null  $pattern File pattern to match
     * @param  int  $limit Maximum number of files to return
     */
    public static function getFiles(?string $pattern = null, int $limit = 100): array
    {
        $directory = InspectorConfig::getFullStoragePath();

        if (! is_dir($directory)) {
            return [];
        }

        $pattern = $pattern ?? '*.json';
        $files = glob($directory.DIRECTORY_SEPARATOR.$pattern);

        if (! $files) {
            return [];
        }

        // Sort by modification time (newest first)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Apply limit
        if ($limit > 0) {
            $files = array_slice($files, 0, $limit);
        }

        // Return file information
        return array_map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'modified_human' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }, $files);
    }

    /**
     * Read diagnostic data from file.
     *
     * @param  string  $filepath Path to the file
     * @return array|null Diagnostic data or null if failed
     */
    public static function read(string $filepath): ?array
    {
        try {
            if (! file_exists($filepath)) {
                return null;
            }

            $content = @file_get_contents($filepath);
            if ($content === false) {
                return null;
            }

            $data = json_decode($content, true);

            return is_array($data) ? $data : null;

        } catch (\Throwable $e) {
            if (InspectorConfig::isDebugMode()) {
                error_log('Inspector read error: '.$e->getMessage());
            }

            return null;
        }
    }

    /**
     * Get storage statistics.
     */
    public static function getStats(): array
    {
        $directory = InspectorConfig::getFullStoragePath();

        if (! is_dir($directory)) {
            return [
                'directory_exists' => false,
                'total_files' => 0,
                'total_size' => 0,
                'oldest_file' => null,
                'newest_file' => null,
            ];
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.json');
        $totalSize = 0;
        $oldestTime = null;
        $newestTime = null;

        foreach ($files as $file) {
            $size = filesize($file);
            $mtime = filemtime($file);

            $totalSize += $size;

            if ($oldestTime === null || $mtime < $oldestTime) {
                $oldestTime = $mtime;
            }

            if ($newestTime === null || $mtime > $newestTime) {
                $newestTime = $mtime;
            }
        }

        return [
            'directory_exists' => true,
            'directory_path' => $directory,
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_size_human' => self::formatBytes($totalSize),
            'oldest_file' => $oldestTime ? date('Y-m-d H:i:s', $oldestTime) : null,
            'newest_file' => $newestTime ? date('Y-m-d H:i:s', $newestTime) : null,
        ];
    }

    /**
     * Ensure storage directory exists.
     *
     * @param  string|null  $subdirectory Optional subdirectory
     * @return string|null Directory path if successful
     */
    private static function ensureDirectory(?string $subdirectory = null): ?string
    {
        $basePath = InspectorConfig::getFullStoragePath();
        $directory = $subdirectory ? $basePath.DIRECTORY_SEPARATOR.$subdirectory : $basePath;

        if (! is_dir($directory)) {
            if (! @mkdir($directory, 0775, true)) {
                if (InspectorConfig::isDebugMode()) {
                    error_log("Inspector: Failed to create directory: {$directory}");
                }

                return null;
            }
        }

        if (! is_writable($directory)) {
            if (InspectorConfig::isDebugMode()) {
                error_log("Inspector: Directory not writable: {$directory}");
            }

            return null;
        }

        return $directory;
    }

    /**
     * Generate filename for diagnostic data.
     *
     * @param  array  $data Diagnostic data
     */
    private static function generateFilename(array $data): string
    {
        $tableName = $data['datatable']['table_name'] ?? 'unknown';
        $routeName = $data['request']['route']['name'] ?? null;
        $routePath = $data['request']['path'] ?? null;

        $filenameBase = $tableName;

        if (! empty($routeName)) {
            $filenameBase .= '_'.str_replace([':', '/', '\\', '.'], '-', $routeName);
        } elseif (! empty($routePath)) {
            $filenameBase .= '_'.str_replace([':', '/', '\\', '.'], '-', $routePath);
        }

        $timestamp = date('Ymd_His');
        $unique = substr(uniqid(), -6);

        return "{$filenameBase}_{$timestamp}_{$unique}.json";
    }

    /**
     * Trigger cleanup if needed based on file count.
     */
    private static function triggerCleanupIfNeeded(): void
    {
        $maxFiles = InspectorConfig::getMaxFiles();
        $files = glob(InspectorConfig::getFullStoragePath().DIRECTORY_SEPARATOR.'*.json');

        if (count($files) > $maxFiles) {
            self::cleanup();
        }
    }

    /**
     * Enforce maximum files limit.
     *
     * @return int Number of files removed
     */
    private static function enforceMaxFiles(): int
    {
        $maxFiles = InspectorConfig::getMaxFiles();
        $files = glob(InspectorConfig::getFullStoragePath().DIRECTORY_SEPARATOR.'*.json');

        if (count($files) <= $maxFiles) {
            return 0;
        }

        // Sort by modification time (oldest first)
        usort($files, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        $filesToRemove = array_slice($files, 0, count($files) - $maxFiles);
        $removedCount = 0;

        foreach ($filesToRemove as $file) {
            if (@unlink($file)) {
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * Format bytes to human readable format.
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }
}
