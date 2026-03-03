<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Library\Components;

/**
 * MetaTags Component.
 *
 * Modern meta tags management with SEO optimization.
 * Backward compatible with Origin\Library\Components\MetaTags API.
 *
 * Features:
 * - Basic meta tags (title, description, keywords, author)
 * - Open Graph meta tags for social media
 * - Twitter Card meta tags
 * - JSON-LD structured data
 * - Theme integration
 * - Database preference support
 */
class MetaTags
{
    /**
     * Meta tag content storage.
     *
     * @var array<string, array<string, string>>
     */
    protected array $content = [];

    /**
     * Base URL for the application.
     *
     * @var string|null
     */
    protected ?string $baseUrl = null;

    /**
     * Language code (e.g., 'en', 'id').
     *
     * @var string|null
     */
    protected ?string $lang = null;

    /**
     * Author name.
     *
     * @var string|null
     */
    protected ?string $author = null;

    /**
     * Application name.
     *
     * @var string|null
     */
    protected ?string $appName = null;

    /**
     * Preference data from database or config.
     *
     * @var array<string, string>
     */
    protected array $preference = [];

    /**
     * Open Graph meta tags.
     *
     * @var array<string, string>
     */
    protected array $openGraph = [];

    /**
     * Twitter Card meta tags.
     *
     * @var array<string, string>
     */
    protected array $twitterCard = [];

    /**
     * JSON-LD structured data.
     *
     * @var array<string, mixed>
     */
    protected array $jsonLd = [];

    /**
     * CSRF token.
     *
     * @var string|null
     */
    public ?string $csrf = null;

    /**
     * Constructor.
     *
     * Initializes meta tags with default values from database or config.
     */
    public function __construct()
    {
        $this->loadPreferences();
        $this->loadMeta();
    }

    /**
     * Load preferences from database or config.
     *
     * Tries to load from Preference model first, falls back to config.
     *
     * @return void
     */
    protected function loadPreferences(): void
    {
        // Try to load from database (if Preference model exists)
        if (class_exists('Canvastack\Origin\Models\Admin\System\Preference')) {
            try {
                $preference = app('Canvastack\Origin\Models\Admin\System\Preference');
                $prefData = $preference->first()?->getAttributes() ?? [];

                $this->preference = [
                    'app_name' => $prefData['title'] ?? config('app.name', 'CanvaStack'),
                    'meta_title' => $prefData['meta_title'] ?? config('app.name', 'CanvaStack'),
                    'meta_keywords' => $prefData['meta_keywords'] ?? '',
                    'meta_description' => $prefData['meta_description'] ?? '',
                    'meta_author' => $prefData['meta_author'] ?? '',
                    'email_person' => $prefData['email_person'] ?? '',
                    'email_address' => $prefData['email_address'] ?? '',
                ];

                return;
            } catch (\Exception $e) {
                // Fall through to config loading
            }
        }

        // Load from config as fallback
        $this->loadFromConfig();
    }

    /**
     * Load from config as fallback.
     *
     * @return void
     */
    protected function loadFromConfig(): void
    {
        $this->preference = [
            'app_name' => config('app.name', 'CanvaStack'),
            'meta_title' => config('app.name', 'CanvaStack'),
            'meta_keywords' => config('canvastack.meta.keywords', ''),
            'meta_description' => config('canvastack.meta.description', ''),
            'meta_author' => config('canvastack.meta.author', ''),
            'email_person' => '',
            'email_address' => '',
        ];
    }

    /**
     * Initialize default meta tags.
     *
     * @return void
     */
    protected function loadMeta(): void
    {
        $this->getMeta();
        $this->getHtml();
    }

    /**
     * Render default meta tags.
     *
     * @param string|null $inject Optional content to inject
     * @return void
     */
    public function getMeta(?string $inject = null): void
    {
        if (empty($this->content)) {
            $this->baseURL();
            $this->title();
            $this->charset();
            $this->httpEquiv();
            $this->appName();
            $this->author();
            $this->keywords();
            $this->description();
            $this->language();
            $this->viewport();
        }
    }

    /**
     * Get HTML representation of meta tags.
     *
     * @return string
     */
    protected function getHtml(): string
    {
        $html = $this->content['html'] ?? [];

        if (is_array($html)) {
            return implode("\n", array_filter($html));
        }

        return (string) $html;
    }

    /**
     * Get text representation of meta tags.
     *
     * @return array<string, string>
     */
    protected function getText(): array
    {
        return $this->content['text'] ?? [];
    }

    /**
     * Get config value.
     *
     * @param string $name Config key
     * @return mixed
     */
    protected function config(string $name): mixed
    {
        // Map old config keys to new structure
        $keyMap = [
            'baseURL' => 'app.base_url',
            'appName' => 'app.name',
            'appDescription' => 'app.description',
        ];

        $configKey = $keyMap[$name] ?? $name;

        return config("canvastack.{$configKey}");
    }

    /**
     * Render string with preference fallback.
     *
     * This method handles three scenarios:
     * 1. User provides value → use it directly (no fallback)
     * 2. No value + usePreference=true → use preference/config
     * 3. No value + usePreference=false → use config only
     *
     * For title, appends preference/config to user value with separator.
     *
     * @param string|null $string User-provided string
     * @param string $settingName Config/preference key
     * @param bool $usePreference Whether to use preference fallback
     * @return string
     */
    protected function renderString(?string $string, string $settingName, bool $usePreference = false): string
    {
        // If user provides a value, use it directly (no fallback)
        if (!empty($string)) {
            // Special case for title: append preference/config
            if ($settingName === 'meta_title' && $usePreference) {
                $suffix = $this->preference[$settingName] ?? $this->config($settingName);

                return $string . ' | ' . $suffix;
            }

            return $string;
        }

        // No user value: use preference or config
        if ($usePreference && !empty($this->preference[$settingName])) {
            return $this->preference[$settingName];
        }

        return $this->config($settingName) ?? '';
    }

    /**
     * Render all meta tags.
     *
     * @param string $as Output format ('html' or 'text')
     * @return string|array<string, string>
     */
    public function tags(string $as = 'html'): string|array
    {
        if ('html' === $as) {
            return $this->getHtml();
        }

        return $this->getText();
    }

    /**
     * Set CSRF token.
     *
     * @param string $inject CSRF token value
     * @return void
     */
    public function csrf(string $inject): void
    {
        $str = $this->renderString($inject, 'csrf');
        $this->csrf = $str;

        $this->content['text']['csrf'] = $inject;
        $this->content['html']['csrf'] = '<meta name="csrf-token" content="' . $inject . '" />';
    }

    /**
     * Get meta tag text value.
     *
     * @param string $metaName Meta tag name
     * @return string
     */
    public function getMetaText(string $metaName): string
    {
        return $this->content['text'][$metaName] ?? '';
    }

    /**
     * Get meta tag HTML.
     *
     * @param string $metaName Meta tag name
     * @return string
     */
    public function getMetaHTML(string $metaName): string
    {
        return $this->content['html'][$metaName] ?? '';
    }

    /**
     * Render Base URL.
     *
     * @param string|null $string Base URL
     * @return self
     */
    public function baseURL(?string $string = null): self
    {
        $this->baseUrl = $string;

        if (empty($string)) {
            $this->baseUrl = $this->config('baseURL');
        }

        $this->content['text']['baseURL'] = $this->baseUrl;
        $this->content['html']['baseURL'] = '<base href="' . $this->baseUrl . '" />';

        return $this;
    }

    /**
     * Render Application Name.
     *
     * @param string|null $string Application name
     * @return self
     */
    public function appName(?string $string = null): self
    {
        $str = $this->renderString($string, 'app_name', true);
        $this->appName = $str;

        $this->content['text']['app_name'] = $this->appName;
        $this->content['html']['app_name'] = '<meta name="application-name" content="' . $this->appName . '" />';

        return $this;
    }

    /**
     * Render Meta Tag for Language.
     *
     * @param string|null $string Language code
     * @return self
     */
    public function language(?string $string = null): self
    {
        $this->lang = $string;
        if (empty($string)) {
            $this->lang = $this->config('lang');
        }

        $this->content['text']['lang'] = $this->lang;
        $this->content['html']['lang'] = '<meta http-equiv="content-language" content="' . $this->lang . '">';

        return $this;
    }

    /**
     * Render Meta Tag for Charset.
     *
     * @param string|null $string Charset
     * @return self
     */
    public function charset(?string $string = null): self
    {
        $str = $string;
        if (empty($string)) {
            $str = $this->config('charset');
        }

        $this->content['html']['charset'] = '<meta charset="' . $str . '" />';

        return $this;
    }

    /**
     * Render Title Tag.
     *
     * @param string|null $string Page title
     * @return self
     */
    public function title(?string $string = null): self
    {
        $str = $this->renderString($string, 'meta_title', true);

        $this->content['text']['title'] = $str;
        $this->content['html']['title'] = '<title>' . $str . '</title>';

        return $this;
    }

    /**
     * Render Meta Tag for Author.
     *
     * @param string|null $string Author name
     * @return self
     */
    public function author(?string $string = null): self
    {
        $this->author = $this->renderString($string, 'meta_author');

        $this->content['text']['author'] = $this->author;
        $this->content['html']['author'] = '<meta name="author" content="' . $this->author . '" />';

        return $this;
    }

    /**
     * Render Meta Tag for Keywords.
     *
     * When a value is provided, it will be used directly without fallback.
     * When no value is provided, falls back to preference/config.
     *
     * @param string|null $string Keywords (comma-separated)
     * @param bool $html Deprecated parameter (kept for backward compatibility)
     * @return self
     */
    public function keywords(?string $string = null, bool $html = true): self
    {
        $str = $this->renderString($string, 'meta_keywords', empty($string));

        $this->content['text']['meta_keywords'] = $str;
        $this->content['html']['meta_keywords'] = '<meta name="keywords" content="' . $str . '" />';

        return $this;
    }

    /**
     * Render Meta Tag for Description.
     *
     * When a value is provided, it will be used directly without fallback.
     * When no value is provided, falls back to preference/config.
     *
     * @param string|null $string Description text
     * @param bool $html Deprecated parameter (kept for backward compatibility)
     * @return self
     */
    public function description(?string $string = null, bool $html = true): self
    {
        $str = $this->renderString($string, 'meta_description', empty($string));

        $this->content['text']['meta_description'] = $str;
        $this->content['html']['meta_description'] = '<meta name="description" content="' . $str . '" />';

        return $this;
    }

    /**
     * Render Meta Tag for Viewport.
     *
     * @param string|null $string Viewport content
     * @param bool $html Whether to render HTML (deprecated parameter)
     * @return self
     */
    public function viewport(?string $string = null, bool $html = true): self
    {
        $str = $string;
        if (empty($string)) {
            $str = $this->config('meta_viewport');
        }

        $this->content['text']['meta_viewport'] = $str;
        $this->content['html']['meta_viewport'] = '<meta name="viewport" content="' . $str . '" />';

        return $this;
    }

    /**
     * Render Meta Tag for HTTP_EQUIV.
     *
     * @param string|null $type HTTP-EQUIV type
     * @param string|null $content Content value
     * @param bool $html Whether to render HTML (deprecated parameter)
     * @return self
     */
    public function httpEquiv(?string $type = null, ?string $content = null, bool $html = true): self
    {
        $str = [];
        $httpEquiv = $this->config('meta_http_equiv');

        if (empty($type)) {
            $str['type'] = $httpEquiv['type'] ?? 'X-UA-Compatible';
        } else {
            $str['type'] = $type;
        }

        if (empty($content)) {
            $str['content'] = $httpEquiv['content'] ?? 'IE=edge';
        } else {
            $str['content'] = $content;
        }

        $this->content['text']['meta_http_equiv'] = $str;
        $this->content['html']['meta_http_equiv'] = '<meta http-equiv="' . $str['type'] . '" content="' . $str['content'] . '" />';

        return $this;
    }

    /**
     * Set Open Graph meta tags.
     *
     * @param array<string, string> $data Open Graph data
     * @return self
     */
    public function openGraph(array $data): self
    {
        $this->openGraph = array_merge($this->openGraph, $data);

        // Generate HTML for Open Graph tags
        foreach ($data as $property => $content) {
            $this->content['html']['og:' . $property] = '<meta property="og:' . $property . '" content="' . htmlspecialchars($content) . '" />';
        }

        return $this;
    }

    /**
     * Set Twitter Card meta tags.
     *
     * @param array<string, string> $data Twitter Card data
     * @return self
     */
    public function twitterCard(array $data): self
    {
        $this->twitterCard = array_merge($this->twitterCard, $data);

        // Generate HTML for Twitter Card tags
        foreach ($data as $name => $content) {
            $this->content['html']['twitter:' . $name] = '<meta name="twitter:' . $name . '" content="' . htmlspecialchars($content) . '" />';
        }

        return $this;
    }

    /**
     * Set JSON-LD structured data.
     *
     * @param array<string, mixed> $data JSON-LD data
     * @return self
     */
    public function jsonLd(array $data): self
    {
        $this->jsonLd = $data;

        // Generate script tag for JSON-LD
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->content['html']['json-ld'] = '<script type="application/ld+json">' . $json . '</script>';

        return $this;
    }

    /**
     * Get all Open Graph tags.
     *
     * @return array<string, string>
     */
    public function getOpenGraph(): array
    {
        return $this->openGraph;
    }

    /**
     * Get all Twitter Card tags.
     *
     * @return array<string, string>
     */
    public function getTwitterCard(): array
    {
        return $this->twitterCard;
    }

    /**
     * Get JSON-LD data.
     *
     * @return array<string, mixed>
     */
    public function getJsonLd(): array
    {
        return $this->jsonLd;
    }
}
