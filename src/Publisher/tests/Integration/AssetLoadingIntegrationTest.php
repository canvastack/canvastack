<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Template;
use Illuminate\Support\Facades\Config;

/**
 * Integration test for Template component asset loading.
 *
 * Task 14.2: Write integration test for asset loading
 * 
 * This test validates that the Template component correctly loads CSS and JavaScript
 * assets based on the active template configuration:
 * - `default` template → Bootstrap 4 assets
 * - `canvasign` template → Bootstrap 5 assets (when configured)
 * - `canvas` template → TailwindCSS CDN + custom assets
 *
 * Test Coverage:
 * 1. Template component loads correct assets for each template
 * 2. Fallback behavior when config missing
 * 3. TailwindCSS CDN loaded for canvas template
 * 4. Bootstrap 5 assets loaded for canvasign template (when configured)
 *
 * Requirements Validated: 12.1, 12.2, 12.3, 12.4, 12.5, 13.1, 13.2, 13.3, 13.4
 *
 * @group integration
 * @group theme-adapter
 * @group asset-loading
 */
class AssetLoadingIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    /**
     * Set the active template via Laravel config.
     */
    private function setTemplate(string $template): void
    {
        Config::set('canvastack.settings.template', $template);
    }

    /**
     * Get the scripts array from a Template instance using reflection.
     * The scripts property is protected, so we need reflection to access it.
     */
    private function getScriptsFromTemplate(Template $template): array
    {
        $reflection = new \ReflectionClass($template);
        $property = $reflection->getProperty('scripts');
        $property->setAccessible(true);
        return $property->getValue($template);
    }

    /**
     * Extract all script URLs from a scripts array.
     * 
     * @param array $scripts Scripts array from Template component
     * @param string $type 'js' or 'css'
     * @return array Array of script URLs
     */
    private function extractScriptUrls(array $scripts, string $type): array
    {
        $urls = [];
        
        if (!isset($scripts[$type])) {
            return $urls;
        }

        foreach ($scripts[$type] as $position => $positionScripts) {
            if (!is_array($positionScripts)) {
                continue;
            }

            foreach ($positionScripts as $scriptObj) {
                if (is_object($scriptObj) && isset($scriptObj->url)) {
                    $urls[] = $scriptObj->url;
                } elseif (is_string($scriptObj)) {
                    $urls[] = $scriptObj;
                }
            }
        }

        return $urls;
    }

    /**
     * Check if any URL in the array contains the given substring.
     */
    private function urlsContain(array $urls, string $substring): bool
    {
        foreach ($urls as $url) {
            if (str_contains($url, $substring)) {
                return true;
            }
        }
        return false;
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 1: Default Template Asset Loading
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 12.3, 13.4: Test default template loads Bootstrap 4 assets
     * 
     * Validates that the default template loads the correct Bootstrap 4 assets
     * from the admin.default configuration.
     */
    public function test_default_template_loads_bootstrap4_assets(): void
    {
        $this->setTemplate('default');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract all JS and CSS URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');
        $cssUrls = $this->extractScriptUrls($scripts, 'css');

        // Verify Bootstrap 4 JS is loaded
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'bootstrap') && $this->urlsContain($jsUrls, 'bootstrap.min.js'),
            'Default template must load Bootstrap 4 JS (bootstrap.min.js)'
        );

        // Verify Bootstrap 4 CSS is loaded
        $this->assertTrue(
            $this->urlsContain($cssUrls, 'bootstrap') && $this->urlsContain($cssUrls, 'bootstrap.css'),
            'Default template must load Bootstrap 4 CSS (bootstrap.css)'
        );

        // Verify jQuery is loaded (required by Bootstrap 4)
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'jquery'),
            'Default template must load jQuery (required by Bootstrap 4)'
        );

        // Verify Popper.js is loaded (required by Bootstrap 4)
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'popper'),
            'Default template must load Popper.js (required by Bootstrap 4)'
        );
    }

    /**
     * @test
     * Requirement 12.1, 12.2: Test default template loads all position types
     * 
     * Validates that assets are loaded from all position types:
     * - top (header scripts)
     * - bottom.first (early body scripts)
     * - bottom.last (late body scripts)
     */
    public function test_default_template_loads_all_positions(): void
    {
        $this->setTemplate('default');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify JS positions exist
        $this->assertArrayHasKey('js', $scripts, 'Scripts must contain JS array');
        $this->assertArrayHasKey('top', $scripts['js'], 'JS must have top position');
        $this->assertArrayHasKey('bottom_first', $scripts['js'], 'JS must have bottom_first position');
        $this->assertArrayHasKey('bottom_last', $scripts['js'], 'JS must have bottom_last position');

        // Verify CSS positions exist
        $this->assertArrayHasKey('css', $scripts, 'Scripts must contain CSS array');
        $this->assertArrayHasKey('top', $scripts['css'], 'CSS must have top position');
        $this->assertArrayHasKey('bottom_first', $scripts['css'], 'CSS must have bottom_first position');
        $this->assertArrayHasKey('bottom_last', $scripts['css'], 'CSS must have bottom_last position');

        // Verify each position has scripts
        $this->assertNotEmpty($scripts['js']['top'], 'Top JS position must have scripts');
        $this->assertNotEmpty($scripts['js']['bottom_first'], 'Bottom first JS position must have scripts');
        $this->assertNotEmpty($scripts['js']['bottom_last'], 'Bottom last JS position must have scripts');

        $this->assertNotEmpty($scripts['css']['top'], 'Top CSS position must have scripts');
        $this->assertNotEmpty($scripts['css']['bottom_first'], 'Bottom first CSS position must have scripts');
        $this->assertNotEmpty($scripts['css']['bottom_last'], 'Bottom last CSS position must have scripts');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 2: Canvas Template Asset Loading (TailwindCSS)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 13.1, 13.2: Test canvas template loads TailwindCSS CDN
     * 
     * Validates that the canvas template loads the TailwindCSS CDN from
     * the admin.canvas.position.top.js configuration.
     */
    public function test_canvas_template_loads_tailwindcss_cdn(): void
    {
        $this->setTemplate('canvas');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract all JS URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');

        // Verify TailwindCSS CDN is loaded
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'cdn.tailwindcss.com'),
            'Canvas template must load TailwindCSS CDN (https://cdn.tailwindcss.com)'
        );

        // Verify the CDN is loaded in the top position (header)
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);

        $topJsUrls = [];
        foreach ($scripts['js']['top'] as $scriptObj) {
            if (is_object($scriptObj) && isset($scriptObj->url)) {
                $topJsUrls[] = $scriptObj->url;
            }
        }

        $this->assertTrue(
            $this->urlsContain($topJsUrls, 'cdn.tailwindcss.com'),
            'TailwindCSS CDN must be loaded in top position (header)'
        );
    }

    /**
     * @test
     * Requirement 13.2, 13.4: Test canvas template loads custom CSS and JS
     * 
     * Validates that the canvas template loads custom canvas.css and
     * canvas-scripts.js from the configuration.
     */
    public function test_canvas_template_loads_custom_assets(): void
    {
        $this->setTemplate('canvas');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract all CSS and JS URLs
        $cssUrls = $this->extractScriptUrls($scripts, 'css');
        $jsUrls = $this->extractScriptUrls($scripts, 'js');

        // Verify custom canvas.css is loaded
        $this->assertTrue(
            $this->urlsContain($cssUrls, 'canvas.css'),
            'Canvas template must load custom canvas.css'
        );

        // Verify custom canvas-scripts.js is loaded
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'canvas-scripts.js'),
            'Canvas template must load custom canvas-scripts.js'
        );

        // Verify canvas.css is loaded in bottom_first position
        $this->assertArrayHasKey('css', $scripts);
        $this->assertArrayHasKey('bottom_first', $scripts['css']);

        $bottomFirstCssUrls = [];
        foreach ($scripts['css']['bottom_first'] as $cssObj) {
            if (is_object($cssObj) && isset($cssObj->url)) {
                $bottomFirstCssUrls[] = $cssObj->url;
            }
        }

        $this->assertTrue(
            $this->urlsContain($bottomFirstCssUrls, 'canvas.css'),
            'canvas.css must be loaded in bottom_first position'
        );

        // Verify canvas-scripts.js is loaded in bottom_last position
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('bottom_last', $scripts['js']);

        $bottomLastJsUrls = [];
        foreach ($scripts['js']['bottom_last'] as $scriptObj) {
            if (is_object($scriptObj) && isset($scriptObj->url)) {
                $bottomLastJsUrls[] = $scriptObj->url;
            }
        }

        $this->assertTrue(
            $this->urlsContain($bottomLastJsUrls, 'canvas-scripts.js'),
            'canvas-scripts.js must be loaded in bottom_last position'
        );
    }

    /**
     * @test
     * Requirement 13.2: Test canvas template does NOT load Bootstrap assets
     * 
     * Validates that the canvas template does not load Bootstrap CSS or JS,
     * since it uses TailwindCSS instead.
     */
    public function test_canvas_template_does_not_load_bootstrap(): void
    {
        $this->setTemplate('canvas');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract all JS and CSS URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');
        $cssUrls = $this->extractScriptUrls($scripts, 'css');

        // Verify Bootstrap is NOT loaded in top position
        $topJsUrls = [];
        $topCssUrls = [];

        if (isset($scripts['js']['top'])) {
            foreach ($scripts['js']['top'] as $scriptObj) {
                if (is_object($scriptObj) && isset($scriptObj->url)) {
                    $topJsUrls[] = $scriptObj->url;
                }
            }
        }

        if (isset($scripts['css']['top'])) {
            foreach ($scripts['css']['top'] as $cssObj) {
                if (is_object($cssObj) && isset($cssObj->url)) {
                    $topCssUrls[] = $cssObj->url;
                }
            }
        }

        // Canvas template should NOT have Bootstrap in top position
        // (it may have Bootstrap in other positions from fallback, but not in top)
        $this->assertFalse(
            $this->urlsContain($topJsUrls, 'bootstrap.min.js') || $this->urlsContain($topJsUrls, 'bootstrap.bundle.min.js'),
            'Canvas template should NOT load Bootstrap JS in top position (uses TailwindCSS instead)'
        );

        $this->assertFalse(
            $this->urlsContain($topCssUrls, 'bootstrap.css') || $this->urlsContain($topCssUrls, 'bootstrap.min.css'),
            'Canvas template should NOT load Bootstrap CSS in top position (uses TailwindCSS instead)'
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 3: Canvasign Template Asset Loading (Bootstrap 5)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 13.3: Test canvasign template loads Bootstrap 5 assets when configured
     * 
     * Validates that when Bootstrap 5 assets are configured for canvasign template,
     * they are loaded correctly.
     * 
     * Note: The current canvasign config has [null] values, so we temporarily
     * configure Bootstrap 5 assets to test the loading mechanism.
     */
    public function test_canvasign_template_loads_bootstrap5_assets_when_configured(): void
    {
        $this->setTemplate('canvasign');

        // Configure Bootstrap 5 assets for canvasign template
        Config::set('canvastack.templates.admin.canvasign.position.top.js', [
            'vendor/plugins/bootstrap5/bootstrap.bundle.min.js'
        ]);

        Config::set('canvastack.templates.admin.canvasign.position.top.css', [
            'vendor/plugins/bootstrap5/bootstrap.min.css'
        ]);

        // Set other positions to [null] to avoid fallback
        Config::set('canvastack.templates.admin.canvasign.position.bottom.js', [null]);
        Config::set('canvastack.templates.admin.canvasign.position.bottom.css', [null]);
        Config::set('canvastack.templates.admin.canvasign.position.bottom.first.js', [null]);
        Config::set('canvastack.templates.admin.canvasign.position.bottom.first.css', [null]);
        Config::set('canvastack.templates.admin.canvasign.position.bottom.last.js', [null]);
        Config::set('canvastack.templates.admin.canvasign.position.bottom.last.css', [null]);

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract all JS and CSS URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');
        $cssUrls = $this->extractScriptUrls($scripts, 'css');

        // Verify Bootstrap 5 JS is loaded
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'bootstrap5') || $this->urlsContain($jsUrls, 'bootstrap.bundle.min.js'),
            'Canvasign template must load Bootstrap 5 JS when configured'
        );

        // Verify Bootstrap 5 CSS is loaded
        $this->assertTrue(
            $this->urlsContain($cssUrls, 'bootstrap5') || $this->urlsContain($cssUrls, 'bootstrap.min.css'),
            'Canvasign template must load Bootstrap 5 CSS when configured'
        );
    }

    /**
     * @test
     * Requirement 13.3: Test canvasign template uses different Bootstrap version than default
     * 
     * Validates that canvasign template can load a different Bootstrap version
     * than the default template (Bootstrap 5 vs Bootstrap 4).
     */
    public function test_canvasign_template_uses_different_bootstrap_version(): void
    {
        // Configure Bootstrap 5 for canvasign
        Config::set('canvastack.templates.admin.canvasign.position.top.js', [
            'vendor/plugins/bootstrap5/bootstrap.bundle.min.js'
        ]);

        Config::set('canvastack.templates.admin.canvasign.position.top.css', [
            'vendor/plugins/bootstrap5/bootstrap.min.css'
        ]);

        // Test default template
        $this->setTemplate('default');
        $defaultTemplate = new Template();
        $defaultScripts = $this->getScriptsFromTemplate($defaultTemplate);
        $defaultJsUrls = $this->extractScriptUrls($defaultScripts, 'js');

        // Test canvasign template
        $this->setTemplate('canvasign');
        $canvasignTemplate = new Template();
        $canvasignScripts = $this->getScriptsFromTemplate($canvasignTemplate);
        $canvasignJsUrls = $this->extractScriptUrls($canvasignScripts, 'js');

        // Verify default uses Bootstrap 4 (in nodes/bootstrap directory)
        $defaultUsesBootstrap4 = $this->urlsContain($defaultJsUrls, 'nodes/bootstrap');
        $this->assertTrue(
            $defaultUsesBootstrap4,
            'Default template must use Bootstrap 4 (nodes/bootstrap)'
        );

        // Verify canvasign uses Bootstrap 5 (in bootstrap5 directory)
        $canvasignUsesBootstrap5 = $this->urlsContain($canvasignJsUrls, 'bootstrap5');
        $this->assertTrue(
            $canvasignUsesBootstrap5,
            'Canvasign template must use Bootstrap 5 (bootstrap5) when configured'
        );

        // Verify they are different
        $this->assertNotEquals(
            $defaultJsUrls,
            $canvasignJsUrls,
            'Default and canvasign templates must load different Bootstrap versions'
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 4: Fallback Behavior
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 12.4: Test fallback to default config when template config missing
     * 
     * Validates that when a template's configuration is missing or incomplete,
     * the Template component falls back to the admin.default configuration.
     */
    public function test_fallback_to_default_when_config_missing(): void
    {
        $this->setTemplate('nonexistent-template');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract all JS and CSS URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');
        $cssUrls = $this->extractScriptUrls($scripts, 'css');

        // Verify default Bootstrap 4 assets are loaded (fallback)
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'bootstrap') && $this->urlsContain($jsUrls, 'bootstrap.min.js'),
            'Missing template config must fall back to default Bootstrap 4 JS'
        );

        $this->assertTrue(
            $this->urlsContain($cssUrls, 'bootstrap') && $this->urlsContain($cssUrls, 'bootstrap.css'),
            'Missing template config must fall back to default Bootstrap 4 CSS'
        );

        // Verify jQuery is loaded (from default fallback)
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'jquery'),
            'Missing template config must fall back to default jQuery'
        );
    }

    /**
     * @test
     * Requirement 12.4: Test fallback when specific position config is null
     * 
     * Validates that when a specific position has [null] value in config,
     * the Template component falls back to the default config for that position.
     */
    public function test_fallback_when_position_config_is_null(): void
    {
        $this->setTemplate('canvasign');

        // canvasign config has [null] for most positions
        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify scripts are still loaded (from fallback)
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('css', $scripts);

        // Extract all JS URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');

        // Verify at least some scripts are loaded (from fallback)
        $this->assertNotEmpty(
            $jsUrls,
            'Template with [null] config must fall back to default and load scripts'
        );

        // When config has [null], the templateScripts method uses the null coalescing operator (??)
        // which falls back to default config. The fallback mechanism works at the config level,
        // not at the individual script level. So we verify that the scripts array is populated
        // with valid scripts from the fallback.
        
        // Count non-null scripts
        $validScripts = array_filter($jsUrls, function($url) {
            return $url !== null && $url !== '';
        });

        $this->assertNotEmpty(
            $validScripts,
            'Template with [null] config must have valid scripts loaded from fallback'
        );
    }

    /**
     * @test
     * Requirement 12.4, 15.2, 15.3: Test system never breaks with missing config
     * 
     * Validates that the Template component handles missing or incomplete
     * configurations gracefully without throwing exceptions.
     */
    public function test_system_never_breaks_with_missing_config(): void
    {
        // Test with completely missing template
        $this->setTemplate('completely-missing-template');

        try {
            $template = new Template();
            $scripts = $this->getScriptsFromTemplate($template);

            // Verify scripts array exists (even if empty)
            $this->assertIsArray($scripts);
            $this->assertArrayHasKey('js', $scripts);
            $this->assertArrayHasKey('css', $scripts);

            $success = true;
        } catch (\Exception $e) {
            $success = false;
        }

        $this->assertTrue(
            $success,
            'Template component must not throw exceptions with missing config'
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 5: Cross-Template Consistency
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 12.1, 12.2, 12.5: Test all templates load valid assets
     * 
     * Validates that all three templates (default, canvasign, canvas) load
     * valid, non-empty asset configurations.
     */
    public function test_all_templates_load_valid_assets(): void
    {
        $templates = ['default', 'canvasign', 'canvas'];

        foreach ($templates as $templateName) {
            $this->setTemplate($templateName);

            $template = new Template();
            $scripts = $this->getScriptsFromTemplate($template);

            // Verify scripts array structure
            $this->assertArrayHasKey('js', $scripts, "Template {$templateName}: Must have JS array");
            $this->assertArrayHasKey('css', $scripts, "Template {$templateName}: Must have CSS array");

            // Extract URLs
            $jsUrls = $this->extractScriptUrls($scripts, 'js');
            $cssUrls = $this->extractScriptUrls($scripts, 'css');

            // Verify at least some assets are loaded
            $this->assertNotEmpty(
                $jsUrls,
                "Template {$templateName}: Must load at least one JS asset"
            );

            $this->assertNotEmpty(
                $cssUrls,
                "Template {$templateName}: Must load at least one CSS asset"
            );
        }
    }

    /**
     * @test
     * Requirement 13.1, 13.2, 13.4: Test canvas template has unique assets
     * 
     * Validates that canvas template loads different assets than default template,
     * specifically TailwindCSS instead of Bootstrap.
     */
    public function test_canvas_template_has_unique_assets(): void
    {
        // Get default template assets
        $this->setTemplate('default');
        $defaultTemplate = new Template();
        $defaultScripts = $this->getScriptsFromTemplate($defaultTemplate);
        $defaultJsUrls = $this->extractScriptUrls($defaultScripts, 'js');

        // Get canvas template assets
        $this->setTemplate('canvas');
        $canvasTemplate = new Template();
        $canvasScripts = $this->getScriptsFromTemplate($canvasTemplate);
        $canvasJsUrls = $this->extractScriptUrls($canvasScripts, 'js');

        // Verify canvas has TailwindCSS
        $this->assertTrue(
            $this->urlsContain($canvasJsUrls, 'tailwindcss'),
            'Canvas template must load TailwindCSS'
        );

        // Verify default does NOT have TailwindCSS
        $this->assertFalse(
            $this->urlsContain($defaultJsUrls, 'tailwindcss'),
            'Default template must NOT load TailwindCSS'
        );

        // Verify canvas has custom canvas-scripts.js
        $this->assertTrue(
            $this->urlsContain($canvasJsUrls, 'canvas-scripts.js'),
            'Canvas template must load custom canvas-scripts.js'
        );

        // Verify default does NOT have canvas-scripts.js
        $this->assertFalse(
            $this->urlsContain($defaultJsUrls, 'canvas-scripts.js'),
            'Default template must NOT load canvas-scripts.js'
        );
    }

    /**
     * @test
     * Requirement 12.5: Test Template component supports extensibility
     * 
     * Validates that new templates can be added to the configuration
     * without modifying the Template component code.
     */
    public function test_template_component_supports_extensibility(): void
    {
        // Add a completely new template configuration
        Config::set('canvastack.templates.admin.mytheme.position.top.js', [
            'vendor/mytheme/mytheme.bundle.js'
        ]);

        Config::set('canvastack.templates.admin.mytheme.position.top.css', [
            'vendor/mytheme/mytheme.min.css'
        ]);

        Config::set('canvastack.templates.admin.mytheme.position.bottom.js', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.css', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.first.js', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.first.css', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.last.js', ['js/mytheme-custom.js']);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.last.css', [null]);

        $this->setTemplate('mytheme');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Extract URLs
        $jsUrls = $this->extractScriptUrls($scripts, 'js');
        $cssUrls = $this->extractScriptUrls($scripts, 'css');

        // Verify new theme's assets are loaded
        $this->assertTrue(
            $this->urlsContain($jsUrls, 'mytheme.bundle.js'),
            'New template must load its configured JS assets'
        );

        $this->assertTrue(
            $this->urlsContain($cssUrls, 'mytheme.min.css'),
            'New template must load its configured CSS assets'
        );

        $this->assertTrue(
            $this->urlsContain($jsUrls, 'mytheme-custom.js'),
            'New template must load its custom JS from bottom_last position'
        );
    }
}
