<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Config;

use Illuminate\Support\Facades\File;

/**
 * Configuration Backup Service.
 *
 * Handles backup and restore of configuration files.
 */
class ConfigBackupService
{
    /**
     * Backup directory path.
     */
    protected string $backupPath;

    /**
     * Configuration manager instance.
     */
    protected ConfigurationManager $configManager;

    /**
     * Constructor.
     */
    public function __construct(ConfigurationManager $configManager)
    {
        $this->configManager = $configManager;
        $this->backupPath = storage_path('app/config-backups');

        // Ensure backup directory exists
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a backup of current configuration.
     */
    public function createBackup(?string $name = null): array
    {
        try {
            $name = $name ?? 'backup_' . now()->format('Y-m-d_H-i-s');
            $filename = $this->sanitizeFilename($name) . '.json';
            $filepath = $this->backupPath . '/' . $filename;

            // Export configuration
            $config = $this->configManager->exportConfiguration();

            // Add backup metadata
            $backup = [
                'name' => $name,
                'created_at' => now()->toIso8601String(),
                'version' => $config['version'],
                'config' => $config,
            ];

            // Save to file
            File::put($filepath, json_encode($backup, JSON_PRETTY_PRINT));

            return [
                'success' => true,
                'backup' => [
                    'name' => $name,
                    'filename' => $filename,
                    'path' => $filepath,
                    'size' => File::size($filepath),
                    'created_at' => now()->toIso8601String(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore configuration from backup.
     */
    public function restoreBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return [
                    'success' => false,
                    'error' => "Backup file not found: {$filename}",
                ];
            }

            // Load backup
            $backup = json_decode(File::get($filepath), true);

            if (!isset($backup['config'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid backup format',
                ];
            }

            // Import configuration
            $result = $this->configManager->importConfiguration($backup['config']);

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => "Configuration restored from backup: {$backup['name']}",
                    'restored_at' => now()->toIso8601String(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to restore configuration',
                'details' => $result['errors'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List all backups.
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = File::files($this->backupPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            try {
                $content = json_decode(File::get($file->getPathname()), true);

                $backups[] = [
                    'filename' => $file->getFilename(),
                    'name' => $content['name'] ?? $file->getFilename(),
                    'created_at' => $content['created_at'] ?? null,
                    'version' => $content['version'] ?? 'unknown',
                    'size' => $file->getSize(),
                    'size_human' => $this->formatBytes($file->getSize()),
                ];
            } catch (\Exception $e) {
                // Skip invalid backup files
                continue;
            }
        }

        // Sort by created_at descending
        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        return $backups;
    }

    /**
     * Delete a backup.
     */
    public function deleteBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return [
                    'success' => false,
                    'error' => "Backup file not found: {$filename}",
                ];
            }

            File::delete($filepath);

            return [
                'success' => true,
                'message' => "Backup deleted: {$filename}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get backup details.
     */
    public function getBackupDetails(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return [
                    'success' => false,
                    'error' => "Backup file not found: {$filename}",
                ];
            }

            $backup = json_decode(File::get($filepath), true);

            return [
                'success' => true,
                'backup' => [
                    'filename' => $filename,
                    'name' => $backup['name'] ?? $filename,
                    'created_at' => $backup['created_at'] ?? null,
                    'version' => $backup['version'] ?? 'unknown',
                    'size' => File::size($filepath),
                    'size_human' => $this->formatBytes(File::size($filepath)),
                    'groups' => array_keys($backup['config']['settings'] ?? []),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create automatic backup before configuration changes.
     */
    public function createAutoBackup(): array
    {
        return $this->createBackup('auto_' . now()->format('Y-m-d_H-i-s'));
    }

    /**
     * Clean old backups (keep last N backups).
     */
    public function cleanOldBackups(int $keep = 10): array
    {
        $backups = $this->listBackups();
        $deleted = [];

        if (count($backups) <= $keep) {
            return [
                'success' => true,
                'message' => 'No backups to clean',
                'deleted' => [],
            ];
        }

        // Delete oldest backups
        $toDelete = array_slice($backups, $keep);

        foreach ($toDelete as $backup) {
            $result = $this->deleteBackup($backup['filename']);

            if ($result['success']) {
                $deleted[] = $backup['filename'];
            }
        }

        return [
            'success' => true,
            'message' => count($deleted) . ' backup(s) deleted',
            'deleted' => $deleted,
        ];
    }

    /**
     * Export backup to downloadable file.
     */
    public function exportBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return [
                    'success' => false,
                    'error' => "Backup file not found: {$filename}",
                ];
            }

            return [
                'success' => true,
                'path' => $filepath,
                'filename' => $filename,
                'content' => File::get($filepath),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Import backup from uploaded file.
     */
    public function importBackup(string $content, string $filename): array
    {
        try {
            // Validate JSON
            $backup = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON format',
                ];
            }

            // Validate backup structure
            if (!isset($backup['config'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid backup structure',
                ];
            }

            // Save backup file
            $filename = $this->sanitizeFilename($filename);
            $filepath = $this->backupPath . '/' . $filename;

            File::put($filepath, $content);

            return [
                'success' => true,
                'message' => "Backup imported: {$filename}",
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sanitize filename.
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove extension if present
        $filename = preg_replace('/\.json$/', '', $filename);

        // Replace invalid characters
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);

        return $filename;
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

    /**
     * Get backup path.
     */
    public function getBackupPath(): string
    {
        return $this->backupPath;
    }
}
