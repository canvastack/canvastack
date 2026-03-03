<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers\Admin;

use Canvastack\Canvastack\Http\Controllers\BaseController;
use Canvastack\Canvastack\Support\Config\ConfigBackupService;
use Canvastack\Canvastack\Support\Config\ConfigMigrationService;
use Canvastack\Canvastack\Support\Config\ConfigurationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuration Controller.
 *
 * Handles configuration management UI and API endpoints.
 */
class ConfigurationController extends BaseController
{
    /**
     * Configuration manager instance.
     */
    protected ConfigurationManager $configManager;

    /**
     * Backup service instance.
     */
    protected ConfigBackupService $backupService;

    /**
     * Migration service instance.
     */
    protected ConfigMigrationService $migrationService;

    /**
     * Constructor.
     */
    public function __construct(
        ConfigurationManager $configManager,
        ConfigBackupService $backupService,
        ConfigMigrationService $migrationService
    ) {
        $this->configManager = $configManager;
        $this->backupService = $backupService;
        $this->migrationService = $migrationService;
    }

    /**
     * Show configuration management page.
     */
    public function index(): View
    {
        $settings = $this->configManager->getSettingsForUI();
        $backups = $this->backupService->listBackups();
        $cacheStats = $this->configManager->getCacheStats();

        return view('canvastack::admin.configuration.index', [
            'settings' => $settings,
            'backups' => $backups,
            'cacheStats' => $cacheStats,
        ]);
    }

    /**
     * Get all configuration settings (API).
     */
    public function getSettings(): JsonResponse
    {
        $settings = $this->configManager->getAllSettings();

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Get settings for UI rendering (API).
     */
    public function getSettingsForUI(): JsonResponse
    {
        $settings = $this->configManager->getSettingsForUI();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update configuration settings (API).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $group = $request->input('group');
        $settings = $request->input('settings', []);

        // Create auto backup before updating
        $this->backupService->createAutoBackup();

        // Update settings
        $result = $this->configManager->updateSettings($group, $settings);

        return response()->json($result);
    }

    /**
     * Reset configuration to defaults (API).
     */
    public function resetToDefaults(Request $request): JsonResponse
    {
        $group = $request->input('group');

        // Create backup before resetting
        $this->backupService->createAutoBackup();

        // Reset settings
        $result = $this->configManager->resetToDefaults($group);

        return response()->json($result);
    }

    /**
     * Export configuration (API).
     */
    public function exportConfiguration(): JsonResponse
    {
        $config = $this->configManager->exportConfiguration();

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    /**
     * Download configuration as JSON file.
     */
    public function downloadConfiguration()
    {
        $config = $this->configManager->exportConfiguration();
        $filename = 'canvastack-config-' . now()->format('Y-m-d_H-i-s') . '.json';

        return response()->json($config)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Import configuration (API).
     */
    public function importConfiguration(Request $request): JsonResponse
    {
        $config = $request->input('config');

        if (!$config) {
            return response()->json([
                'success' => false,
                'errors' => ['config' => 'Configuration data is required'],
            ], 400);
        }

        // Create backup before importing
        $this->backupService->createAutoBackup();

        // Import configuration
        $result = $this->configManager->importConfiguration($config);

        return response()->json($result);
    }

    /**
     * Clear configuration cache (API).
     */
    public function clearCache(): JsonResponse
    {
        $this->configManager->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Configuration cache cleared successfully',
        ]);
    }

    /**
     * Get cache statistics (API).
     */
    public function getCacheStats(): JsonResponse
    {
        $stats = $this->configManager->getCacheStats();

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * List all backups (API).
     */
    public function listBackups(): JsonResponse
    {
        $backups = $this->backupService->listBackups();

        return response()->json([
            'success' => true,
            'backups' => $backups,
        ]);
    }

    /**
     * Create backup (API).
     */
    public function createBackup(Request $request): JsonResponse
    {
        $name = $request->input('name');
        $result = $this->backupService->createBackup($name);

        return response()->json($result);
    }

    /**
     * Restore backup (API).
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        $filename = $request->input('filename');

        if (!$filename) {
            return response()->json([
                'success' => false,
                'error' => 'Filename is required',
            ], 400);
        }

        $result = $this->backupService->restoreBackup($filename);

        return response()->json($result);
    }

    /**
     * Delete backup (API).
     */
    public function deleteBackup(Request $request): JsonResponse
    {
        $filename = $request->input('filename');

        if (!$filename) {
            return response()->json([
                'success' => false,
                'error' => 'Filename is required',
            ], 400);
        }

        $result = $this->backupService->deleteBackup($filename);

        return response()->json($result);
    }

    /**
     * Download backup file.
     */
    public function downloadBackup(string $filename)
    {
        $result = $this->backupService->exportBackup($filename);

        if (!$result['success']) {
            abort(404, $result['error']);
        }

        return response($result['content'])
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Upload and import backup file.
     */
    public function uploadBackup(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        $file = $request->file('file');
        $content = $file->get();
        $filename = $file->getClientOriginalName();

        $result = $this->backupService->importBackup($content, $filename);

        return response()->json($result);
    }

    /**
     * Clean old backups (API).
     */
    public function cleanOldBackups(Request $request): JsonResponse
    {
        $keep = $request->input('keep', 10);
        $result = $this->backupService->cleanOldBackups((int) $keep);

        return response()->json($result);
    }

    /**
     * Get migration report (API).
     */
    public function getMigrationReport(): JsonResponse
    {
        $report = $this->migrationService->generateReport();

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Run configuration migration (API).
     */
    public function runMigration(): JsonResponse
    {
        // Backup old config first
        $backupResult = $this->migrationService->backupOldConfig();

        if (!$backupResult['success']) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to backup old configuration',
                'details' => $backupResult['errors'],
            ]);
        }

        // Run migration
        $result = $this->migrationService->migrate();

        return response()->json($result);
    }

    /**
     * Validate migration (API).
     */
    public function validateMigration(): JsonResponse
    {
        $validation = $this->migrationService->validateMigration();

        return response()->json([
            'success' => true,
            'validation' => $validation,
        ]);
    }

    /**
     * Get migration instructions (API).
     */
    public function getMigrationInstructions(): JsonResponse
    {
        $instructions = $this->migrationService->getInstructions();

        return response()->json([
            'success' => true,
            'instructions' => $instructions,
        ]);
    }
}
