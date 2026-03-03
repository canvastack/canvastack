<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Theme;

use Illuminate\Support\Collection;

/**
 * Theme Marketplace.
 *
 * Manages theme marketplace operations including browsing, searching,
 * and installing themes from remote sources.
 */
class ThemeMarketplace
{
    /**
     * Theme manager instance.
     *
     * @var ThemeManager
     */
    protected ThemeManager $manager;

    /**
     * Marketplace sources.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $sources = [];

    /**
     * Cache of available themes.
     *
     * @var Collection|null
     */
    protected ?Collection $availableThemes = null;

    /**
     * Create a new theme marketplace instance.
     *
     * @param ThemeManager $manager
     * @param array<string, array<string, mixed>> $sources
     */
    public function __construct(ThemeManager $manager, array $sources = [])
    {
        $this->manager = $manager;
        $this->sources = $sources;
    }

    /**
     * Add a marketplace source.
     *
     * @param string $name
     * @param string $url
     * @param array<string, mixed> $options
     * @return self
     */
    public function addSource(string $name, string $url, array $options = []): self
    {
        $this->sources[$name] = array_merge([
            'url' => $url,
            'enabled' => true,
            'priority' => 0,
        ], $options);

        // Clear cache
        $this->availableThemes = null;

        return $this;
    }

    /**
     * Remove a marketplace source.
     *
     * @param string $name
     * @return self
     */
    public function removeSource(string $name): self
    {
        unset($this->sources[$name]);

        // Clear cache
        $this->availableThemes = null;

        return $this;
    }

    /**
     * Get all marketplace sources.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Get enabled marketplace sources.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEnabledSources(): array
    {
        return array_filter($this->sources, fn ($source) => $source['enabled'] ?? true);
    }

    /**
     * Browse available themes from all sources.
     *
     * @param bool $refresh
     * @return Collection
     */
    public function browse(bool $refresh = false): Collection
    {
        if ($this->availableThemes !== null && !$refresh) {
            return $this->availableThemes;
        }

        $themes = new Collection();

        foreach ($this->getEnabledSources() as $sourceName => $source) {
            try {
                $sourceThemes = $this->fetchFromSource($sourceName, $source);
                $themes = $themes->merge($sourceThemes);
            } catch (\Exception $e) {
                // Log error but continue with other sources
                if (function_exists('logger')) {
                    logger()->error("Failed to fetch themes from source {$sourceName}: " . $e->getMessage());
                }
            }
        }

        $this->availableThemes = $themes;

        return $themes;
    }

    /**
     * Search for themes.
     *
     * @param string $query
     * @param array<string, mixed> $filters
     * @return Collection
     */
    public function search(string $query, array $filters = []): Collection
    {
        $themes = $this->browse();

        // Filter by search query
        if (!empty($query)) {
            $themes = $themes->filter(function ($theme) use ($query) {
                $searchable = strtolower(implode(' ', [
                    $theme['name'] ?? '',
                    $theme['display_name'] ?? '',
                    $theme['description'] ?? '',
                    implode(' ', $theme['tags'] ?? []),
                ]));

                return str_contains($searchable, strtolower($query));
            });
        }

        // Apply filters
        if (!empty($filters['author'])) {
            $themes = $themes->filter(fn ($theme) => ($theme['author'] ?? '') === $filters['author']);
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $themes = $themes->filter(function ($theme) use ($tags) {
                $themeTags = $theme['tags'] ?? [];

                return !empty(array_intersect($tags, $themeTags));
            });
        }

        if (!empty($filters['min_version'])) {
            $themes = $themes->filter(fn ($theme) => version_compare($theme['version'] ?? '0.0.0', $filters['min_version'], '>='));
        }

        return $themes;
    }

    /**
     * Get theme details from marketplace.
     *
     * @param string $name
     * @return array<string, mixed>|null
     */
    public function getThemeDetails(string $name): ?array
    {
        $themes = $this->browse();

        return $themes->firstWhere('name', $name);
    }

    /**
     * Check if theme is installed.
     *
     * @param string $name
     * @return bool
     */
    public function isInstalled(string $name): bool
    {
        return $this->manager->has($name);
    }

    /**
     * Get installed version of a theme.
     *
     * @param string $name
     * @return string|null
     */
    public function getInstalledVersion(string $name): ?string
    {
        if (!$this->isInstalled($name)) {
            return null;
        }

        return $this->manager->get($name)->getVersion();
    }

    /**
     * Check if theme has updates available.
     *
     * @param string $name
     * @return bool
     */
    public function hasUpdate(string $name): bool
    {
        if (!$this->isInstalled($name)) {
            return false;
        }

        $details = $this->getThemeDetails($name);

        if ($details === null) {
            return false;
        }

        $installedVersion = $this->getInstalledVersion($name);
        $availableVersion = $details['version'] ?? '0.0.0';

        return version_compare($availableVersion, $installedVersion, '>');
    }

    /**
     * Get themes with available updates.
     *
     * @return Collection
     */
    public function getUpdatableThemes(): Collection
    {
        $installed = collect($this->manager->names());

        return $installed->filter(fn ($name) => $this->hasUpdate($name))
            ->map(fn ($name) => [
                'name' => $name,
                'installed_version' => $this->getInstalledVersion($name),
                'available_version' => $this->getThemeDetails($name)['version'] ?? null,
            ]);
    }

    /**
     * Fetch themes from a source.
     *
     * @param string $sourceName
     * @param array<string, mixed> $source
     * @return Collection
     */
    protected function fetchFromSource(string $sourceName, array $source): Collection
    {
        $url = $source['url'];

        // For now, return empty collection
        // In production, this would make HTTP request to fetch themes
        // Example: return Http::get($url)->json();

        return new Collection();
    }

    /**
     * Get marketplace statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $themes = $this->browse();
        $installed = collect($this->manager->names());

        return [
            'total_available' => $themes->count(),
            'total_installed' => $installed->count(),
            'total_sources' => count($this->getEnabledSources()),
            'updates_available' => $this->getUpdatableThemes()->count(),
            'popular_tags' => $this->getPopularTags(),
            'top_authors' => $this->getTopAuthors(),
        ];
    }

    /**
     * Get popular tags.
     *
     * @param int $limit
     * @return array<string, int>
     */
    protected function getPopularTags(int $limit = 10): array
    {
        $themes = $this->browse();
        $tags = [];

        foreach ($themes as $theme) {
            foreach ($theme['tags'] ?? [] as $tag) {
                $tags[$tag] = ($tags[$tag] ?? 0) + 1;
            }
        }

        arsort($tags);

        return array_slice($tags, 0, $limit, true);
    }

    /**
     * Get top authors.
     *
     * @param int $limit
     * @return array<string, int>
     */
    protected function getTopAuthors(int $limit = 10): array
    {
        $themes = $this->browse();
        $authors = [];

        foreach ($themes as $theme) {
            $author = $theme['author'] ?? 'Unknown';
            $authors[$author] = ($authors[$author] ?? 0) + 1;
        }

        arsort($authors);

        return array_slice($authors, 0, $limit, true);
    }

    /**
     * Clear marketplace cache.
     *
     * @return self
     */
    public function clearCache(): self
    {
        $this->availableThemes = null;

        return $this;
    }
}
