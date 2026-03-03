<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

/**
 * Theme Importer.
 *
 * Handles importing themes from various formats including JSON, PHP, and ZIP packages.
 */
class ThemeImporter
{
    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Theme loader instance.
     *
     * @var ThemeLoader
     */
    protected ThemeLoader $loader;

    /**
     * Theme validator instance.
     *
     * @var ThemeValidator
     */
    protected ThemeValidator $validator;

    /**
     * Create a new theme importer instance.
     *
     * @param ThemeLoader $loader
     * @param Filesystem|null $files
     * @param ThemeValidator|null $validator
     */
    public function __construct(ThemeLoader $loader, ?Filesystem $files = null, ?ThemeValidator $validator = null)
    {
        $this->loader = $loader;
        $this->files = $files ?? new Filesystem();
        $this->validator = $validator ?? new ThemeValidator();
    }

    /**
     * Import theme from JSON string.
     *
     * @param string $json
     * @return Theme
     * @throws InvalidArgumentException
     */
    public function fromJson(string $json): Theme
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return $this->fromArray($data);
    }

    /**
     * Import theme from PHP array.
     *
     * @param array<string, mixed> $data
     * @return Theme
     * @throws InvalidArgumentException
     */
    public function fromArray(array $data): Theme
    {
        // Validate theme data
        $this->validator->validateOrFail($data);

        // Remove export metadata
        unset($data['exported_at']);

        return $this->loader->loadFromArray($data);
    }

    /**
     * Import theme from file.
     *
     * @param string $path
     * @return Theme
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function fromFile(string $path): Theme
    {
        if (!$this->files->exists($path)) {
            throw new InvalidArgumentException("Theme file not found: {$path}");
        }

        $extension = $this->files->extension($path);

        return match ($extension) {
            'json' => $this->fromJson($this->files->get($path)),
            'php' => $this->fromArray(require $path),
            'zip' => $this->fromZip($path),
            default => throw new InvalidArgumentException("Unsupported import format: {$extension}"),
        };
    }

    /**
     * Import theme from ZIP package.
     *
     * @param string $zipPath
     * @param string|null $extractTo
     * @return Theme
     * @throws RuntimeException
     */
    public function fromZip(string $zipPath, ?string $extractTo = null): Theme
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required for ZIP import');
        }

        if (!$this->files->exists($zipPath)) {
            throw new InvalidArgumentException("ZIP file not found: {$zipPath}");
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Failed to open ZIP file: {$zipPath}");
        }

        // Extract to temporary directory if not specified
        if ($extractTo === null) {
            $extractTo = sys_get_temp_dir() . '/canvastack-theme-' . uniqid();
        }

        // Extract ZIP
        $zip->extractTo($extractTo);
        $zip->close();

        // Load theme from extracted files
        $themeFile = $extractTo . '/theme.json';

        if (!$this->files->exists($themeFile)) {
            throw new RuntimeException('theme.json not found in ZIP package');
        }

        $theme = $this->fromFile($themeFile);

        // Clean up temporary directory if we created it
        if (str_starts_with($extractTo, sys_get_temp_dir())) {
            $this->files->deleteDirectory($extractTo);
        }

        return $theme;
    }

    /**
     * Import and install theme from ZIP package.
     *
     * @param string $zipPath
     * @param string $themesPath
     * @param bool $overwrite
     * @return Theme
     * @throws RuntimeException
     */
    public function installFromZip(string $zipPath, string $themesPath, bool $overwrite = false): Theme
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required for ZIP import');
        }

        if (!$this->files->exists($zipPath)) {
            throw new InvalidArgumentException("ZIP file not found: {$zipPath}");
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Failed to open ZIP file: {$zipPath}");
        }

        // Read theme.json to get theme name
        $themeJson = $zip->getFromName('theme.json');

        if ($themeJson === false) {
            $zip->close();

            throw new RuntimeException('theme.json not found in ZIP package');
        }

        $themeData = json_decode($themeJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $zip->close();

            throw new RuntimeException('Invalid theme.json: ' . json_last_error_msg());
        }

        $themeName = $themeData['name'] ?? null;

        if (empty($themeName)) {
            $zip->close();

            throw new RuntimeException('Theme name not found in theme.json');
        }

        // Check if theme already exists
        $themeDir = $themesPath . '/' . $themeName;

        if ($this->files->exists($themeDir) && !$overwrite) {
            $zip->close();

            throw new RuntimeException("Theme '{$themeName}' already exists. Use overwrite option to replace.");
        }

        // Create theme directory
        if (!$this->files->isDirectory($themesPath)) {
            $this->files->makeDirectory($themesPath, 0755, true);
        }

        // Remove existing theme if overwriting
        if ($this->files->exists($themeDir)) {
            $this->files->deleteDirectory($themeDir);
        }

        // Extract ZIP to theme directory
        $zip->extractTo($themeDir);
        $zip->close();

        // Load and return theme
        return $this->loader->load($themeName);
    }

    /**
     * Import multiple themes from directory.
     *
     * @param string $directory
     * @param array<string> $extensions
     * @return array<Theme>
     */
    public function importMultiple(string $directory, array $extensions = ['json', 'php', 'zip']): array
    {
        if (!$this->files->isDirectory($directory)) {
            throw new InvalidArgumentException("Directory not found: {$directory}");
        }

        $themes = [];
        $files = $this->files->files($directory);

        foreach ($files as $file) {
            $extension = $this->files->extension($file);

            if (!in_array($extension, $extensions)) {
                continue;
            }

            try {
                $themes[] = $this->fromFile($file);
            } catch (\Exception $e) {
                // Log error but continue with other files
                if (function_exists('logger')) {
                    logger()->error("Failed to import theme from {$file}: " . $e->getMessage());
                }
            }
        }

        return $themes;
    }

    /**
     * Validate theme package before import.
     *
     * @param string $path
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    public function validate(string $path): array
    {
        if (!$this->files->exists($path)) {
            throw new InvalidArgumentException("File not found: {$path}");
        }

        $extension = $this->files->extension($path);

        try {
            $theme = match ($extension) {
                'json' => $this->fromJson($this->files->get($path)),
                'php' => $this->fromArray(require $path),
                'zip' => $this->fromZip($path),
                default => throw new InvalidArgumentException("Unsupported format: {$extension}"),
            };

            return [
                'valid' => true,
                'theme' => $theme->toArray(),
                'errors' => [],
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'theme' => null,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
