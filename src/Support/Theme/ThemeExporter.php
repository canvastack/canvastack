<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use ZipArchive;

/**
 * Theme Exporter.
 *
 * Handles exporting themes to various formats including JSON, PHP, and ZIP packages.
 */
class ThemeExporter
{
    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * Create a new theme exporter instance.
     *
     * @param Filesystem|null $files
     */
    public function __construct(?Filesystem $files = null)
    {
        $this->files = $files ?? new Filesystem();
    }

    /**
     * Export theme to JSON.
     *
     * @param ThemeInterface $theme
     * @param bool $pretty
     * @return string
     */
    public function toJson(ThemeInterface $theme, bool $pretty = true): string
    {
        $data = $this->prepareExportData($theme);

        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $options);
    }

    /**
     * Export theme to PHP array.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    public function toPhp(ThemeInterface $theme): string
    {
        $data = $this->prepareExportData($theme);

        return "<?php\n\nreturn " . var_export($data, true) . ";\n";
    }

    /**
     * Export theme to file.
     *
     * @param ThemeInterface $theme
     * @param string $path
     * @param string $format
     * @return bool
     */
    public function toFile(ThemeInterface $theme, string $path, string $format = 'json'): bool
    {
        $content = match ($format) {
            'json' => $this->toJson($theme),
            'php' => $this->toPhp($theme),
            default => throw new InvalidArgumentException("Unsupported export format: {$format}"),
        };

        return $this->files->put($path, $content) !== false;
    }

    /**
     * Export theme as ZIP package.
     *
     * @param ThemeInterface $theme
     * @param string $outputPath
     * @param array<string, mixed> $options
     * @return bool
     */
    public function toZip(ThemeInterface $theme, string $outputPath, array $options = []): bool
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required for ZIP export');
        }

        $zip = new ZipArchive();

        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // Add theme.json
        $zip->addFromString('theme.json', $this->toJson($theme));

        // Add README.md
        $readme = $this->generateReadme($theme);
        $zip->addFromString('README.md', $readme);

        // Add LICENSE if specified
        if (!empty($options['license'])) {
            $zip->addFromString('LICENSE', $options['license']);
        }

        // Add preview image if specified
        if (!empty($options['preview']) && $this->files->exists($options['preview'])) {
            $zip->addFile($options['preview'], 'preview.png');
        }

        // Add custom assets if specified
        if (!empty($options['assets']) && is_array($options['assets'])) {
            foreach ($options['assets'] as $assetPath => $zipPath) {
                if ($this->files->exists($assetPath)) {
                    $zip->addFile($assetPath, $zipPath);
                }
            }
        }

        $zip->close();

        return true;
    }

    /**
     * Prepare theme data for export.
     *
     * @param ThemeInterface $theme
     * @return array<string, mixed>
     */
    protected function prepareExportData(ThemeInterface $theme): array
    {
        $data = [
            'name' => $theme->getName(),
            'display_name' => $theme->getDisplayName(),
            'version' => $theme->getVersion(),
            'author' => $theme->getAuthor(),
            'description' => $theme->getDescription(),
            'config' => $theme->getConfig(),
        ];

        // Add parent if exists
        if ($theme->hasParent()) {
            $data['parent'] = $theme->getParent();
        }

        // Add metadata
        $metadata = $theme->getMetadata();
        if (!empty($metadata['homepage'])) {
            $data['homepage'] = $metadata['homepage'];
        }
        if (!empty($metadata['license'])) {
            $data['license'] = $metadata['license'];
        }
        if (!empty($metadata['tags'])) {
            $data['tags'] = $metadata['tags'];
        }

        // Add export timestamp
        $data['exported_at'] = date('Y-m-d H:i:s');

        return $data;
    }

    /**
     * Generate README content for theme.
     *
     * @param ThemeInterface $theme
     * @return string
     */
    protected function generateReadme(ThemeInterface $theme): string
    {
        $name = $theme->getDisplayName();
        $description = $theme->getDescription();
        $author = $theme->getAuthor();
        $version = $theme->getVersion();

        $readme = "# {$name}\n\n";
        $readme .= "{$description}\n\n";
        $readme .= "## Information\n\n";
        $readme .= "- **Version**: {$version}\n";
        $readme .= "- **Author**: {$author}\n";

        if ($theme->hasParent()) {
            $readme .= "- **Parent Theme**: {$theme->getParent()}\n";
        }

        $readme .= "\n## Installation\n\n";
        $readme .= "1. Extract this theme to `resources/themes/{$theme->getName()}/`\n";
        $readme .= "2. Set the theme in your `.env` file:\n";
        $readme .= "   ```\n";
        $readme .= "   CANVASTACK_THEME={$theme->getName()}\n";
        $readme .= "   ```\n";
        $readme .= "3. Clear the cache:\n";
        $readme .= "   ```bash\n";
        $readme .= "   php artisan cache:clear\n";
        $readme .= "   ```\n\n";

        $readme .= "## Features\n\n";

        if ($theme->supportsDarkMode()) {
            $readme .= "- ✅ Dark mode support\n";
        }

        $readme .= "\n## License\n\n";
        $readme .= $theme->getMetadata()['license'] ?? 'MIT';

        return $readme;
    }

    /**
     * Export multiple themes.
     *
     * @param array<ThemeInterface> $themes
     * @param string $outputDir
     * @param string $format
     * @return int Number of themes exported
     */
    public function exportMultiple(array $themes, string $outputDir, string $format = 'json'): int
    {
        if (!$this->files->isDirectory($outputDir)) {
            $this->files->makeDirectory($outputDir, 0755, true);
        }

        $count = 0;

        foreach ($themes as $theme) {
            $filename = $theme->getName() . '.' . $format;
            $path = $outputDir . '/' . $filename;

            if ($this->toFile($theme, $path, $format)) {
                $count++;
            }
        }

        return $count;
    }
}
