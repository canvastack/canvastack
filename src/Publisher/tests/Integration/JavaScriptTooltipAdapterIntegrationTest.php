<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Integration tests for JavaScript Tooltip Adapter (canvastack-tooltip-adapter.js).
 *
 * These tests verify that the CanvaStackTooltip JavaScript adapter provides
 * a unified API for tooltip and popover initialization across Bootstrap 4,
 * Bootstrap 5, and TailwindCSS.
 *
 * Since this is a Laravel backend project without browser-based E2E testing (Dusk),
 * these tests verify:
 *   1. The JavaScript file exists and is loadable
 *   2. The JavaScript code structure is correct
 *   3. Template detection logic is present
 *   4. Tooltip/popover initialization methods are defined
 *   5. Integration with scripts.js is correct
 *
 * Requirements: 15.4
 *
 * @group integration
 * @group theme-adapter
 * @group javascript-tooltip-adapter
 */
class JavaScriptTooltipAdapterIntegrationTest extends TestCase
{
    private string $tooltipAdapterPath;
    private string $scriptsJsPath;
    private string $configPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tooltipAdapterPath = public_path('assets/templates/default/js/canvastack-tooltip-adapter.js');
        $this->scriptsJsPath = public_path('assets/templates/default/js/scripts.js');
        $this->configPath = config_path('canvastack.templates.php');
    }

    // ── 1. File Existence Tests ──────────────────────────────────────────

    /**
     * @test
     * Requirement 15.4: canvastack-tooltip-adapter.js file exists.
     */
    public function test_tooltip_adapter_file_exists(): void
    {
        $this->assertFileExists(
            $this->tooltipAdapterPath,
            'canvastack-tooltip-adapter.js must exist in public/assets/templates/default/js/'
        );
    }

    /**
     * @test
     * Requirement 15.4: scripts.js file exists.
     */
    public function test_scripts_js_file_exists(): void
    {
        $this->assertFileExists(
            $this->scriptsJsPath,
            'scripts.js must exist in public/assets/templates/default/js/'
        );
    }

    /**
     * @test
     * Requirement 15.4: config/canvastack.templates.php file exists.
     */
    public function test_config_file_exists(): void
    {
        $this->assertFileExists(
            $this->configPath,
            'config/canvastack.templates.php must exist'
        );
    }

    // ── 2. Tooltip Adapter Structure Tests ───────────────────────────────

    /**
     * @test
     * Requirement 15.4: Tooltip adapter defines CanvaStackTooltip global object.
     */
    public function test_tooltip_adapter_defines_canvastack_tooltip_object(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            'var CanvaStackTooltip',
            $content,
            'Tooltip adapter must define CanvaStackTooltip global variable'
        );

        $this->assertStringContainsString(
            'CanvaStackTooltip = (function()',
            $content,
            'Tooltip adapter must use IIFE pattern for CanvaStackTooltip'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter defines init() method.
     */
    public function test_tooltip_adapter_defines_init_method(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            'function init()',
            $content,
            'Tooltip adapter must define init() function'
        );

        $this->assertStringContainsString(
            'init: init',
            $content,
            'Tooltip adapter must export init method in public API'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter defines getTemplate() method for template detection.
     */
    public function test_tooltip_adapter_defines_get_template_method(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            'function getTemplate()',
            $content,
            'Tooltip adapter must define getTemplate() function'
        );

        $this->assertStringContainsString(
            'window.canvastackTemplate',
            $content,
            'Tooltip adapter must check window.canvastackTemplate for template detection'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter exports destroy() helper method.
     */
    public function test_tooltip_adapter_exports_destroy_method(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            'function destroy()',
            $content,
            'Tooltip adapter must define destroy() function'
        );

        $this->assertStringContainsString(
            'destroy: destroy',
            $content,
            'Tooltip adapter must export destroy method in public API'
        );
    }

    // ── 3. Template-Specific Logic Tests ─────────────────────────────────

    /**
     * @test
     * Requirement 15.4: Tooltip adapter handles 'default' template (Bootstrap 4).
     */
    public function test_tooltip_adapter_handles_default_template(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Check for Bootstrap 4 tooltip/popover initialization
        $this->assertStringContainsString(
            "case 'default':",
            $content,
            'Tooltip adapter must have case for default template'
        );

        $this->assertStringContainsString(
            'initBootstrap4Tooltips',
            $content,
            'Tooltip adapter must have initBootstrap4Tooltips function'
        );

        $this->assertStringContainsString(
            '[data-toggle="tooltip"]',
            $content,
            'Tooltip adapter must initialize Bootstrap 4 tooltips with data-toggle="tooltip"'
        );

        $this->assertStringContainsString(
            '[data-toggle="popover"]',
            $content,
            'Tooltip adapter must initialize Bootstrap 4 popovers with data-toggle="popover"'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter handles 'canvasign' template (Bootstrap 5).
     */
    public function test_tooltip_adapter_handles_canvasign_template(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Check for Bootstrap 5 tooltip/popover initialization
        $this->assertStringContainsString(
            "case 'canvasign':",
            $content,
            'Tooltip adapter must have case for canvasign template'
        );

        $this->assertStringContainsString(
            'initBootstrap5Tooltips',
            $content,
            'Tooltip adapter must have initBootstrap5Tooltips function'
        );

        $this->assertStringContainsString(
            '[data-bs-toggle="tooltip"]',
            $content,
            'Tooltip adapter must initialize Bootstrap 5 tooltips with data-bs-toggle="tooltip"'
        );

        $this->assertStringContainsString(
            '[data-bs-toggle="popover"]',
            $content,
            'Tooltip adapter must initialize Bootstrap 5 popovers with data-bs-toggle="popover"'
        );

        $this->assertStringContainsString(
            'bootstrap.Tooltip',
            $content,
            'Tooltip adapter must use Bootstrap 5 bootstrap.Tooltip API'
        );

        $this->assertStringContainsString(
            'bootstrap.Popover',
            $content,
            'Tooltip adapter must use Bootstrap 5 bootstrap.Popover API'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter handles 'canvas' template (Tailwind).
     */
    public function test_tooltip_adapter_handles_canvas_template(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Check for Tailwind custom tooltip logic
        $this->assertStringContainsString(
            "case 'canvas':",
            $content,
            'Tooltip adapter must have case for canvas template'
        );

        $this->assertStringContainsString(
            'initTailwindTooltips',
            $content,
            'Tooltip adapter must have initTailwindTooltips function'
        );

        // Should check for Tippy.js availability
        $this->assertStringContainsString(
            'typeof tippy',
            $content,
            'Tooltip adapter must check for Tippy.js availability'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter has fallback to default template.
     */
    public function test_tooltip_adapter_has_default_fallback(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            "|| 'default'",
            $content,
            'Tooltip adapter must fallback to "default" template if window.canvastackTemplate is not set'
        );
    }

    // ── 4. Scripts.js Integration Tests ──────────────────────────────────

    /**
     * @test
     * Requirement 15.4: scripts.js uses CanvaStackTooltip.init() instead of direct popover initialization.
     */
    public function test_scripts_js_uses_canvastack_tooltip_init(): void
    {
        $content = file_get_contents($this->scriptsJsPath);

        // Must use CanvaStackTooltip.init()
        $this->assertStringContainsString(
            'CanvaStackTooltip.init()',
            $content,
            'scripts.js must use CanvaStackTooltip.init() for tooltip/popover initialization'
        );
    }

    /**
     * @test
     * Requirement 15.4: scripts.js does NOT use direct Bootstrap popover API.
     */
    public function test_scripts_js_does_not_use_direct_bootstrap_popover_api(): void
    {
        $content = file_get_contents($this->scriptsJsPath);

        // Remove CanvaStackTooltip.init() to avoid false positives
        $contentWithoutAdapter = str_replace('CanvaStackTooltip.init()', '', $content);

        // Should NOT contain direct .popover() calls
        $this->assertStringNotContainsString(
            ".popover()",
            $contentWithoutAdapter,
            'scripts.js must NOT use direct Bootstrap .popover() API'
        );

        $this->assertStringNotContainsString(
            '$(\'[data-toggle="popover"]\').popover()',
            $contentWithoutAdapter,
            'scripts.js must NOT use direct Bootstrap popover initialization'
        );
    }

    /**
     * @test
     * Requirement 15.4: scripts.js checks if CanvaStackTooltip is defined before calling init().
     */
    public function test_scripts_js_checks_tooltip_adapter_existence(): void
    {
        $content = file_get_contents($this->scriptsJsPath);

        // Should check if CanvaStackTooltip is defined
        $this->assertStringContainsString(
            'typeof CanvaStackTooltip',
            $content,
            'scripts.js must check if CanvaStackTooltip is defined before calling init()'
        );

        $this->assertStringContainsString(
            "!== 'undefined'",
            $content,
            'scripts.js must verify CanvaStackTooltip is not undefined'
        );
    }

    // ── 5. Asset Loading Pipeline Tests ──────────────────────────────────

    /**
     * @test
     * Requirement 15.4: Tooltip adapter is included in asset loading pipeline.
     */
    public function test_tooltip_adapter_included_in_asset_pipeline(): void
    {
        $config = require $this->configPath;

        // Check if tooltip adapter is in the bottom.last.js array for default template
        $this->assertArrayHasKey('admin', $config);
        $this->assertArrayHasKey('default', $config['admin']);
        $this->assertArrayHasKey('position', $config['admin']['default']);
        $this->assertArrayHasKey('bottom', $config['admin']['default']['position']);
        $this->assertArrayHasKey('last', $config['admin']['default']['position']['bottom']);
        $this->assertArrayHasKey('js', $config['admin']['default']['position']['bottom']['last']);

        $jsAssets = $config['admin']['default']['position']['bottom']['last']['js'];

        $this->assertContains(
            'js/canvastack-tooltip-adapter.js',
            $jsAssets,
            'canvastack-tooltip-adapter.js must be included in asset loading pipeline'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter is loaded BEFORE scripts.js.
     */
    public function test_tooltip_adapter_loaded_before_scripts_js(): void
    {
        $config = require $this->configPath;

        $jsAssets = $config['admin']['default']['position']['bottom']['last']['js'];

        $tooltipAdapterIndex = array_search('js/canvastack-tooltip-adapter.js', $jsAssets);
        $scriptsJsIndex = array_search('js/scripts.js', $jsAssets);

        $this->assertNotFalse(
            $tooltipAdapterIndex,
            'canvastack-tooltip-adapter.js must be in asset pipeline'
        );

        $this->assertNotFalse(
            $scriptsJsIndex,
            'scripts.js must be in asset pipeline'
        );

        $this->assertLessThan(
            $scriptsJsIndex,
            $tooltipAdapterIndex,
            'canvastack-tooltip-adapter.js must be loaded BEFORE scripts.js'
        );
    }

    // ── 6. Code Quality Tests ────────────────────────────────────────────

    /**
     * @test
     * Tooltip adapter has proper JSDoc comments.
     */
    public function test_tooltip_adapter_has_jsdoc_comments(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            '/**',
            $content,
            'Tooltip adapter must have JSDoc comments'
        );

        $this->assertStringContainsString(
            '* CanvaStack Tooltip Adapter',
            $content,
            'Tooltip adapter must have descriptive header comment'
        );

        $this->assertStringContainsString(
            '* Usage:',
            $content,
            'Tooltip adapter must have usage examples in comments'
        );
    }

    /**
     * @test
     * Tooltip adapter has proper error handling.
     */
    public function test_tooltip_adapter_has_error_handling(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Should have console.warn or console.error for debugging
        $this->assertMatchesRegularExpression(
            '/console\.(warn|error|info)/',
            $content,
            'Tooltip adapter must have console warnings/errors for debugging'
        );
    }

    /**
     * @test
     * Tooltip adapter exports for CommonJS and AMD environments.
     */
    public function test_tooltip_adapter_exports_for_module_systems(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // CommonJS export
        $this->assertStringContainsString(
            'module.exports',
            $content,
            'Tooltip adapter must export for CommonJS (Node.js)'
        );

        // AMD export
        $this->assertStringContainsString(
            'define.amd',
            $content,
            'Tooltip adapter must export for AMD (RequireJS)'
        );
    }

    // ── 7. Template Detection Tests ──────────────────────────────────────

    /**
     * @test
     * Requirement 15.4: Tooltip adapter detects template from window.canvastackTemplate.
     */
    public function test_tooltip_adapter_detects_template_from_window_variable(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            'window.canvastackTemplate',
            $content,
            'Tooltip adapter must read template from window.canvastackTemplate'
        );

        $this->assertStringContainsString(
            'getTemplate()',
            $content,
            'Tooltip adapter must call getTemplate() to detect active template'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter uses switch statement for template-specific logic.
     */
    public function test_tooltip_adapter_uses_switch_for_template_logic(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        $this->assertStringContainsString(
            'switch (template)',
            $content,
            'Tooltip adapter must use switch statement for template-specific logic'
        );

        // Must have all three template cases
        $this->assertStringContainsString(
            "case 'default':",
            $content,
            'Tooltip adapter must have case for default template'
        );

        $this->assertStringContainsString(
            "case 'canvasign':",
            $content,
            'Tooltip adapter must have case for canvasign template'
        );

        $this->assertStringContainsString(
            "case 'canvas':",
            $content,
            'Tooltip adapter must have case for canvas template'
        );
    }

    // ── 8. Backward Compatibility Tests ──────────────────────────────────

    /**
     * @test
     * Requirement 15.4: Tooltip adapter maintains backward compatibility with Bootstrap 4.
     *
     * The default template must use Bootstrap 4 tooltip/popover API which is the existing behavior.
     */
    public function test_tooltip_adapter_maintains_bootstrap4_compatibility(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Default template must use jQuery .tooltip() and .popover() API
        $this->assertMatchesRegularExpression(
            "/function initBootstrap4Tooltips.*\\.tooltip\\(\\)/s",
            $content,
            'Tooltip adapter default template must use Bootstrap 4 .tooltip() API'
        );

        $this->assertMatchesRegularExpression(
            "/function initBootstrap4Tooltips.*\\.popover\\(\\)/s",
            $content,
            'Tooltip adapter default template must use Bootstrap 4 .popover() API'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter supports Bootstrap 5 tooltip/popover API.
     */
    public function test_tooltip_adapter_supports_bootstrap5_api(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Canvasign template must use Bootstrap 5 Tooltip/Popover API
        $this->assertMatchesRegularExpression(
            "/function initBootstrap5Tooltips.*bootstrap\\.Tooltip/s",
            $content,
            'Tooltip adapter canvasign template must use Bootstrap 5 bootstrap.Tooltip API'
        );

        $this->assertMatchesRegularExpression(
            "/function initBootstrap5Tooltips.*bootstrap\\.Popover/s",
            $content,
            'Tooltip adapter canvasign template must use Bootstrap 5 bootstrap.Popover API'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter supports Tailwind custom tooltip logic.
     */
    public function test_tooltip_adapter_supports_tailwind_custom_logic(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Canvas template must check for Tippy.js
        $this->assertMatchesRegularExpression(
            "/function initTailwindTooltips.*typeof tippy/s",
            $content,
            'Tooltip adapter canvas template must check for Tippy.js availability'
        );

        // Must have fallback to native title tooltips
        $this->assertStringContainsString(
            'native title tooltips',
            $content,
            'Tooltip adapter canvas template must have fallback to native title tooltips'
        );
    }

    // ── 9. Initialization Tests ──────────────────────────────────────────

    /**
     * @test
     * Requirement 15.4: Tooltip adapter initializes both tooltips and popovers.
     */
    public function test_tooltip_adapter_initializes_tooltips_and_popovers(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Must initialize tooltips
        $this->assertStringContainsString(
            'tooltip',
            strtolower($content),
            'Tooltip adapter must initialize tooltips'
        );

        // Must initialize popovers
        $this->assertStringContainsString(
            'popover',
            strtolower($content),
            'Tooltip adapter must initialize popovers'
        );
    }

    /**
     * @test
     * Requirement 15.4: Tooltip adapter destroy method cleans up all instances.
     */
    public function test_tooltip_adapter_destroy_cleans_up_instances(): void
    {
        $content = file_get_contents($this->tooltipAdapterPath);

        // Destroy method must handle all three templates
        $this->assertMatchesRegularExpression(
            "/function destroy\\(\\).*switch.*template/s",
            $content,
            'Tooltip adapter destroy() must use switch statement for template-specific cleanup'
        );

        // Must dispose Bootstrap instances
        $this->assertStringContainsString(
            'dispose',
            $content,
            'Tooltip adapter destroy() must dispose Bootstrap tooltip/popover instances'
        );
    }
}
