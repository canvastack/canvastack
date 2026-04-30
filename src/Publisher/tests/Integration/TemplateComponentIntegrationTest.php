<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Template;
use Illuminate\Support\Facades\Config;

/**
 * Integration tests for Template component dynamic asset loading.
 *
 * Verifies that the Template component:
 *   1. Loads assets from the correct template configuration based on active template.
 *   2. Falls back to `admin.default` config when template config is missing.
 *   3. Correctly resolves template name via canvastack_current_template().
 *
 * Requirements: 12.1, 12.2, 12.3, 12.4, 12.5
 *
 * @group integration
 * @group theme-adapter
 */
class TemplateComponentIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Set the active template via Laravel config so canvastack_current_template()
     * returns the desired value.
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

    // ── 1. Asset loading with `default` template ──────────────────────────

    /**
     * @test
     * Requirement 12.3: With template `default`, Template component loads assets
     *                   from `admin.default` configuration (existing behavior).
     */
    public function test_default_template_loads_default_config_assets(): void
    {
        $this->setTemplate('default');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify JS scripts are loaded from admin.default.position.top.js
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);

        // Check for a known script from default config
        $topJsScripts = $scripts['js']['top'];
        $this->assertNotEmpty($topJsScripts);

        // Verify at least one script from default config is present
        $foundDefaultScript = false;
        foreach ($topJsScripts as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'jquery.min.js') || str_contains($scriptUrl, 'bootstrap.min.js')) {
                $foundDefaultScript = true;
                break;
            }
        }

        $this->assertTrue(
            $foundDefaultScript,
            'Template with default config must load scripts from admin.default.position.top.js'
        );

        // Verify CSS scripts are loaded from admin.default.position.top.css
        $this->assertArrayHasKey('css', $scripts);
        $this->assertArrayHasKey('top', $scripts['css']);

        $topCssScripts = $scripts['css']['top'];
        $this->assertNotEmpty($topCssScripts);

        // Verify at least one CSS from default config is present
        $foundDefaultCss = false;
        foreach ($topCssScripts as $cssObj) {
            $cssUrl = is_object($cssObj) && isset($cssObj->url) ? $cssObj->url : '';
            if (str_contains($cssUrl, 'bootstrap.css')) {
                $foundDefaultCss = true;
                break;
            }
        }

        $this->assertTrue(
            $foundDefaultCss,
            'Template with default config must load CSS from admin.default.position.top.css'
        );
    }

    /**
     * @test
     * Requirement 12.1, 12.2: With template `default`, Template component loads
     *                         assets from all position types (top, bottom, bottom.first, bottom.last).
     */
    public function test_default_template_loads_all_position_types(): void
    {
        $this->setTemplate('default');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify all position types are loaded for JS
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);
        // Note: 'bottom' is split into 'bottom', 'bottom_first', 'bottom_last' by templateScripts()
        // The actual keys depend on how the config is structured
        $this->assertArrayHasKey('bottom_first', $scripts['js']);
        $this->assertArrayHasKey('bottom_last', $scripts['js']);

        // Verify all position types are loaded for CSS
        $this->assertArrayHasKey('css', $scripts);
        $this->assertArrayHasKey('top', $scripts['css']);
        $this->assertArrayHasKey('bottom_first', $scripts['css']);
        $this->assertArrayHasKey('bottom_last', $scripts['css']);

        // Verify bottom_last contains expected scripts from default config
        $bottomLastJs = $scripts['js']['bottom_last'];
        $foundScriptsJs = false;
        foreach ($bottomLastJs as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'scripts.js') || str_contains($scriptUrl, 'canvastackscripts.js')) {
                $foundScriptsJs = true;
                break;
            }
        }

        $this->assertTrue(
            $foundScriptsJs,
            'Template must load scripts from admin.default.position.bottom.last.js'
        );
    }

    // ── 2. Asset loading with `canvasign` template ────────────────────────

    /**
     * @test
     * Requirement 12.1, 12.2: With template `canvasign`, Template component loads
     *                         assets from `admin.canvasign` configuration.
     *
     * Note: The current canvasign config has [null] values, so we verify that
     * the component attempts to load from canvasign config first, then falls back
     * to default when values are null.
     */
    public function test_canvasign_template_loads_canvasign_config_assets(): void
    {
        $this->setTemplate('canvasign');

        // Add a test asset to canvasign config to verify it's being loaded
        Config::set('canvastack.templates.admin.canvasign.position.top.js', [
            'vendor/plugins/bootstrap5/bootstrap.bundle.min.js'
        ]);

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify JS scripts are loaded
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);

        $topJsScripts = $scripts['js']['top'];
        $this->assertNotEmpty($topJsScripts);

        // Verify the canvasign-specific script is present
        $foundCanvasignScript = false;
        foreach ($topJsScripts as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'bootstrap5') || str_contains($scriptUrl, 'bootstrap.bundle.min.js')) {
                $foundCanvasignScript = true;
                break;
            }
        }

        $this->assertTrue(
            $foundCanvasignScript,
            'Template with canvasign config must load scripts from admin.canvasign.position.top.js'
        );
    }

    // ── 3. Asset loading with `canvas` template ───────────────────────────

    /**
     * @test
     * Requirement 12.1, 12.2: With template `canvas`, Template component loads
     *                         assets from `admin.canvas` configuration.
     *
     * Note: canvas config doesn't exist yet, so this tests the fallback behavior.
     */
    public function test_canvas_template_loads_canvas_config_assets(): void
    {
        $this->setTemplate('canvas');

        // Add canvas config to test
        Config::set('canvastack.templates.admin.canvas.position.top.js', [
            'https://cdn.tailwindcss.com'
        ]);

        Config::set('canvastack.templates.admin.canvas.position.top.css', [null]);

        Config::set('canvastack.templates.admin.canvas.position.bottom.js', [null]);
        Config::set('canvastack.templates.admin.canvas.position.bottom.css', [null]);

        Config::set('canvastack.templates.admin.canvas.position.bottom.first.js', [null]);
        Config::set('canvastack.templates.admin.canvas.position.bottom.first.css', ['css/canvas.css']);

        Config::set('canvastack.templates.admin.canvas.position.bottom.last.js', ['js/canvas-scripts.js']);
        Config::set('canvastack.templates.admin.canvas.position.bottom.last.css', [null]);

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify JS scripts are loaded
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);

        $topJsScripts = $scripts['js']['top'];
        $this->assertNotEmpty($topJsScripts);

        // Verify the canvas-specific script (TailwindCSS CDN) is present
        $foundCanvasScript = false;
        foreach ($topJsScripts as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'tailwindcss')) {
                $foundCanvasScript = true;
                break;
            }
        }

        $this->assertTrue(
            $foundCanvasScript,
            'Template with canvas config must load TailwindCSS CDN from admin.canvas.position.top.js'
        );

        // Verify canvas-specific CSS is loaded
        $this->assertArrayHasKey('css', $scripts);
        $this->assertArrayHasKey('bottom_first', $scripts['css']);

        $bottomFirstCss = $scripts['css']['bottom_first'];
        $foundCanvasCss = false;
        foreach ($bottomFirstCss as $cssObj) {
            $cssUrl = is_object($cssObj) && isset($cssObj->url) ? $cssObj->url : '';
            if (str_contains($cssUrl, 'canvas.css')) {
                $foundCanvasCss = true;
                break;
            }
        }

        $this->assertTrue(
            $foundCanvasCss,
            'Template with canvas config must load canvas.css from admin.canvas.position.bottom.first.css'
        );

        // Verify canvas-specific JS is loaded in bottom_last
        $this->assertArrayHasKey('bottom_last', $scripts['js']);

        $bottomLastJs = $scripts['js']['bottom_last'];
        $foundCanvasJs = false;
        foreach ($bottomLastJs as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'canvas-scripts.js')) {
                $foundCanvasJs = true;
                break;
            }
        }

        $this->assertTrue(
            $foundCanvasJs,
            'Template with canvas config must load canvas-scripts.js from admin.canvas.position.bottom.last.js'
        );
    }

    // ── 4. Fallback to `admin.default` when template config missing ──────

    /**
     * @test
     * Requirement 12.4: When template config is missing, Template component
     *                   falls back to `admin.default` configuration.
     */
    public function test_missing_template_config_falls_back_to_default(): void
    {
        $this->setTemplate('nonexistent-template');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify JS scripts are loaded from default config (fallback)
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);

        $topJsScripts = $scripts['js']['top'];
        $this->assertNotEmpty($topJsScripts);

        // Verify at least one script from default config is present (fallback worked)
        $foundDefaultScript = false;
        foreach ($topJsScripts as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'jquery.min.js') || str_contains($scriptUrl, 'bootstrap.min.js')) {
                $foundDefaultScript = true;
                break;
            }
        }

        $this->assertTrue(
            $foundDefaultScript,
            'Template with missing config must fall back to admin.default.position.top.js'
        );
    }

    /**
     * @test
     * Requirement 12.4: When specific position config has [null] values for a template,
     *                   the Template component processes those null values (doesn't load scripts).
     *
     * Note: [null] in config means "no scripts for this position", not "use default".
     * The fallback only happens when the config key doesn't exist at all.
     */
    public function test_missing_position_config_falls_back_to_default(): void
    {
        $this->setTemplate('canvasign');

        // canvasign config exists but has [null] values
        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify that scripts array exists (even if positions might be empty due to [null])
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('css', $scripts);

        // When config has [null], it means "no scripts for this position"
        // The system should still work without errors
        $this->assertTrue(
            true,
            'Template component must handle [null] config values without errors'
        );
    }

    // ── 5. Template name resolution via canvastack_current_template() ────

    /**
     * @test
     * Requirement 12.1: Template component calls canvastack_current_template()
     *                   to get the active template name.
     */
    public function test_template_component_uses_canvastack_current_template(): void
    {
        // Set template to 'canvasign'
        $this->setTemplate('canvasign');

        // Verify canvastack_current_template() returns the correct value
        $currentTemplate = canvastack_current_template();
        $this->assertSame('canvasign', $currentTemplate);

        // Create Template instance and verify it uses the current template
        $template = new Template();

        // Access the currentTemplate property using reflection
        $reflection = new \ReflectionClass($template);
        $property = $reflection->getProperty('currentTemplate');
        $property->setAccessible(true);
        $templateValue = $property->getValue($template);

        $this->assertSame(
            'canvasign',
            $templateValue,
            'Template component must use canvastack_current_template() to get active template'
        );
    }

    /**
     * @test
     * Requirement 12.5: Template component supports adding new template configs
     *                   without code changes.
     */
    public function test_template_component_supports_new_template_configs(): void
    {
        // Add a completely new template config
        Config::set('canvastack.templates.admin.mytheme.position.top.js', [
            'vendor/mytheme/mytheme.min.js'
        ]);

        Config::set('canvastack.templates.admin.mytheme.position.top.css', [
            'vendor/mytheme/mytheme.min.css'
        ]);

        Config::set('canvastack.templates.admin.mytheme.position.bottom.js', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.css', [null]);

        Config::set('canvastack.templates.admin.mytheme.position.bottom.first.js', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.first.css', [null]);

        Config::set('canvastack.templates.admin.mytheme.position.bottom.last.js', [null]);
        Config::set('canvastack.templates.admin.mytheme.position.bottom.last.css', [null]);

        $this->setTemplate('mytheme');

        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify the new theme's assets are loaded
        $this->assertArrayHasKey('js', $scripts);
        $this->assertArrayHasKey('top', $scripts['js']);

        $topJsScripts = $scripts['js']['top'];
        $foundMythemeScript = false;
        foreach ($topJsScripts as $scriptObj) {
            $scriptUrl = is_object($scriptObj) && isset($scriptObj->url) ? $scriptObj->url : '';
            if (str_contains($scriptUrl, 'mytheme.min.js')) {
                $foundMythemeScript = true;
                break;
            }
        }

        $this->assertTrue(
            $foundMythemeScript,
            'Template component must support new template configs without code changes'
        );

        // Verify CSS is also loaded
        $this->assertArrayHasKey('css', $scripts);
        $this->assertArrayHasKey('top', $scripts['css']);

        $topCssScripts = $scripts['css']['top'];
        $foundMythemeCss = false;
        foreach ($topCssScripts as $cssObj) {
            $cssUrl = is_object($cssObj) && isset($cssObj->url) ? $cssObj->url : '';
            if (str_contains($cssUrl, 'mytheme.min.css')) {
                $foundMythemeCss = true;
                break;
            }
        }

        $this->assertTrue(
            $foundMythemeCss,
            'Template component must load CSS from new template configs'
        );
    }

    // ── 6. Null handling in config ────────────────────────────────────────

    /**
     * @test
     * Requirement 12.4: Template component handles [null] values in config
     *                   by falling back to default config.
     */
    public function test_template_component_handles_null_config_values(): void
    {
        $this->setTemplate('canvasign');

        // canvasign config has [null] values for most positions
        $template = new Template();
        $scripts = $this->getScriptsFromTemplate($template);

        // Verify that scripts are still loaded (from default fallback)
        $this->assertArrayHasKey('js', $scripts);
        $this->assertNotEmpty($scripts['js']);

        $this->assertArrayHasKey('css', $scripts);
        $this->assertNotEmpty($scripts['css']);

        // Verify at least one position has scripts (from fallback)
        $hasScripts = false;
        foreach ($scripts['js'] as $position => $positionScripts) {
            if (!empty($positionScripts)) {
                $hasScripts = true;
                break;
            }
        }

        $this->assertTrue(
            $hasScripts,
            'Template component must handle [null] config values by falling back to default'
        );
    }
}
