<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use RuntimeException;

/**
 * Theme Migrator.
 *
 * Handles theme version migrations and upgrades.
 */
class ThemeMigrator
{
    /**
     * Registered migrations.
     *
     * @var array<string, array<string, callable>>
     */
    protected array $migrations = [];

    /**
     * Migration history.
     *
     * @var array<string, array<string>>
     */
    protected array $history = [];

    /**
     * Register a migration.
     *
     * @param string $fromVersion
     * @param string $toVersion
     * @param callable $migration
     * @return self
     */
    public function register(string $fromVersion, string $toVersion, callable $migration): self
    {
        $key = "{$fromVersion}:{$toVersion}";
        $this->migrations[$key] = [
            'from' => $fromVersion,
            'to' => $toVersion,
            'migration' => $migration,
        ];

        return $this;
    }

    /**
     * Migrate theme to target version.
     *
     * @param array<string, mixed> $themeData
     * @param string $targetVersion
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    public function migrate(array $themeData, string $targetVersion): array
    {
        $currentVersion = $themeData['version'] ?? '1.0.0';

        if (version_compare($currentVersion, $targetVersion, '=')) {
            return $themeData;
        }

        if (version_compare($currentVersion, $targetVersion, '>')) {
            throw new RuntimeException("Cannot downgrade theme from {$currentVersion} to {$targetVersion}");
        }

        // Find migration path
        $path = $this->findMigrationPath($currentVersion, $targetVersion);

        if (empty($path)) {
            throw new RuntimeException("No migration path found from {$currentVersion} to {$targetVersion}");
        }

        // Apply migrations
        foreach ($path as $migration) {
            $themeData = $this->applyMigration($themeData, $migration);
            $this->recordMigration($themeData['name'] ?? 'unknown', $migration['from'], $migration['to']);
        }

        return $themeData;
    }

    /**
     * Find migration path between versions.
     *
     * @param string $from
     * @param string $to
     * @return array<array<string, mixed>>
     */
    protected function findMigrationPath(string $from, string $to): array
    {
        $path = [];
        $current = $from;

        // Simple linear path finding
        // In production, this could use a graph algorithm for complex paths
        while (version_compare($current, $to, '<')) {
            $nextMigration = $this->findNextMigration($current, $to);

            if ($nextMigration === null) {
                break;
            }

            $path[] = $nextMigration;
            $current = $nextMigration['to'];
        }

        return $path;
    }

    /**
     * Find next migration from current version.
     *
     * @param string $from
     * @param string $to
     * @return array<string, mixed>|null
     */
    protected function findNextMigration(string $from, string $to): ?array
    {
        $candidates = [];

        foreach ($this->migrations as $key => $migration) {
            if (version_compare($migration['from'], $from, '=') &&
                version_compare($migration['to'], $to, '<=')) {
                $candidates[] = $migration;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Return the migration with the highest target version
        usort($candidates, fn ($a, $b) => version_compare($b['to'], $a['to']));

        return $candidates[0];
    }

    /**
     * Apply a migration to theme data.
     *
     * @param array<string, mixed> $themeData
     * @param array<string, mixed> $migration
     * @return array<string, mixed>
     */
    protected function applyMigration(array $themeData, array $migration): array
    {
        $migrationFn = $migration['migration'];
        $result = $migrationFn($themeData);

        // Update version
        $result['version'] = $migration['to'];

        // Add migration metadata
        if (!isset($result['migrations'])) {
            $result['migrations'] = [];
        }

        $result['migrations'][] = [
            'from' => $migration['from'],
            'to' => $migration['to'],
            'applied_at' => date('Y-m-d H:i:s'),
        ];

        return $result;
    }

    /**
     * Record migration in history.
     *
     * @param string $themeName
     * @param string $from
     * @param string $to
     * @return void
     */
    protected function recordMigration(string $themeName, string $from, string $to): void
    {
        if (!isset($this->history[$themeName])) {
            $this->history[$themeName] = [];
        }

        $this->history[$themeName][] = "{$from} -> {$to}";
    }

    /**
     * Get migration history for a theme.
     *
     * @param string $themeName
     * @return array<string>
     */
    public function getHistory(string $themeName): array
    {
        return $this->history[$themeName] ?? [];
    }

    /**
     * Check if migration is available.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function hasMigration(string $from, string $to): bool
    {
        $key = "{$from}:{$to}";

        return isset($this->migrations[$key]);
    }

    /**
     * Get all registered migrations.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }

    /**
     * Clear migration history.
     *
     * @return self
     */
    public function clearHistory(): self
    {
        $this->history = [];

        return $this;
    }

    /**
     * Register default migrations.
     *
     * @return self
     */
    public function registerDefaultMigrations(): self
    {
        // Migration from 1.0.0 to 1.1.0
        $this->register('1.0.0', '1.1.0', function (array $data) {
            // Add dark mode support if not present
            if (!isset($data['config']['dark_mode'])) {
                $data['config']['dark_mode'] = [
                    'enabled' => true,
                ];
            }

            return $data;
        });

        // Migration from 1.1.0 to 1.2.0
        $this->register('1.1.0', '1.2.0', function (array $data) {
            // Add radius configuration if not present
            if (!isset($data['config']['radius'])) {
                $data['config']['radius'] = [
                    'card' => '1.5rem',
                    'button' => '1rem',
                    'input' => '1rem',
                    'badge' => '9999px',
                    'modal' => '1.5rem',
                ];
            }

            return $data;
        });

        // Migration from 1.2.0 to 2.0.0
        $this->register('1.2.0', '2.0.0', function (array $data) {
            // Restructure colors to support variants
            if (isset($data['config']['colors']) && !isset($data['config']['light'])) {
                $data['config']['light'] = [
                    'colors' => $data['config']['colors'],
                ];

                // Create dark variant with adjusted colors
                $data['config']['dark'] = [
                    'colors' => $this->adjustColorsForDark($data['config']['colors']),
                ];
            }

            return $data;
        });

        return $this;
    }

    /**
     * Adjust colors for dark mode.
     *
     * @param array<string, mixed> $colors
     * @return array<string, mixed>
     */
    protected function adjustColorsForDark(array $colors): array
    {
        // Simple color adjustment for dark mode
        // In production, this would use proper color manipulation
        return $colors;
    }

    /**
     * Validate theme data before migration.
     *
     * @param array<string, mixed> $themeData
     * @return bool
     */
    public function validate(array $themeData): bool
    {
        $required = ['name', 'version', 'config'];

        foreach ($required as $field) {
            if (!isset($themeData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get migration summary.
     *
     * @param string $from
     * @param string $to
     * @return array<string, mixed>
     */
    public function getSummary(string $from, string $to): array
    {
        $path = $this->findMigrationPath($from, $to);

        return [
            'from' => $from,
            'to' => $to,
            'steps' => count($path),
            'path' => array_map(fn ($m) => "{$m['from']} -> {$m['to']}", $path),
            'available' => !empty($path),
        ];
    }
}
