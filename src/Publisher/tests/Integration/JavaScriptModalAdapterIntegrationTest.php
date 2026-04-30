<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Integration tests for JavaScript Modal Adapter (canvastack-modal-adapter.js).
 *
 * These tests verify that the CanvaStackModal JavaScript adapter provides
 * a unified API for modal operations across Bootstrap 4, Bootstrap 5, and TailwindCSS.
 *
 * Since this is a Laravel backend project without browser-based E2E testing (Dusk),
 * these tests verify:
 *   1. The JavaScript file exists and is loadable
 *   2. The JavaScript code structure is correct
 *   3. Template detection logic is present
 *   4. Modal show/hide methods are defined
 *   5. Integration points with delete-handler.js and filter.js are correct
 *
 * For true E2E testing with browser automation, Laravel Dusk would be required.
 *
 * Requirements: 8.6
 *
 * @group integration
 * @group theme-adapter
 * @group javascript-modal-adapter
 */
class JavaScriptModalAdapterIntegrationTest extends TestCase
{
    private string $modalAdapterPath;
    private string $deleteHandlerPath;
    private string $filterJsPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->modalAdapterPath = public_path('assets/templates/default/js/canvastack-modal-adapter.js');
        $this->deleteHandlerPath = public_path('assets/templates/default/js/delete-handler.js');
        $this->filterJsPath = public_path('assets/templates/default/js/datatables/filter.js');
    }

    // ── 1. File Existence Tests ──────────────────────────────────────────

    /**
     * @test
     * Requirement 8.3: canvastack-modal-adapter.js file exists.
     */
    public function test_modal_adapter_file_exists(): void
    {
        $this->assertFileExists(
            $this->modalAdapterPath,
            'canvastack-modal-adapter.js must exist in public/assets/templates/default/js/'
        );
    }

    /**
     * @test
     * Requirement 8.4: delete-handler.js file exists.
     */
    public function test_delete_handler_file_exists(): void
    {
        $this->assertFileExists(
            $this->deleteHandlerPath,
            'delete-handler.js must exist in public/assets/templates/default/js/'
        );
    }

    /**
     * @test
     * Requirement 8.5: filter.js file exists.
     */
    public function test_filter_js_file_exists(): void
    {
        $this->assertFileExists(
            $this->filterJsPath,
            'filter.js must exist in public/assets/templates/default/js/datatables/'
        );
    }

    // ── 2. Modal Adapter Structure Tests ─────────────────────────────────

    /**
     * @test
     * Requirement 8.3: Modal adapter defines CanvaStackModal global object.
     */
    public function test_modal_adapter_defines_canvastack_modal_object(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'var CanvaStackModal',
            $content,
            'Modal adapter must define CanvaStackModal global variable'
        );

        $this->assertStringContainsString(
            'CanvaStackModal = (function()',
            $content,
            'Modal adapter must use IIFE pattern for CanvaStackModal'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter defines show() method.
     */
    public function test_modal_adapter_defines_show_method(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'function show(modalId)',
            $content,
            'Modal adapter must define show(modalId) function'
        );

        $this->assertStringContainsString(
            'show: show',
            $content,
            'Modal adapter must export show method in public API'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter defines hide() method.
     */
    public function test_modal_adapter_defines_hide_method(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'function hide(modalId)',
            $content,
            'Modal adapter must define hide(modalId) function'
        );

        $this->assertStringContainsString(
            'hide: hide',
            $content,
            'Modal adapter must export hide method in public API'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter defines getTemplate() method for template detection.
     */
    public function test_modal_adapter_defines_get_template_method(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'function getTemplate()',
            $content,
            'Modal adapter must define getTemplate() function'
        );

        $this->assertStringContainsString(
            'window.canvastackTemplate',
            $content,
            'Modal adapter must check window.canvastackTemplate for template detection'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter exports isVisible() and toggle() helper methods.
     */
    public function test_modal_adapter_exports_helper_methods(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'function isVisible(modalId)',
            $content,
            'Modal adapter must define isVisible(modalId) function'
        );

        $this->assertStringContainsString(
            'function toggle(modalId)',
            $content,
            'Modal adapter must define toggle(modalId) function'
        );

        $this->assertStringContainsString(
            'isVisible: isVisible',
            $content,
            'Modal adapter must export isVisible method in public API'
        );

        $this->assertStringContainsString(
            'toggle: toggle',
            $content,
            'Modal adapter must export toggle method in public API'
        );
    }

    // ── 3. Template-Specific Logic Tests ─────────────────────────────────

    /**
     * @test
     * Requirement 8.1: Modal adapter handles 'default' template (Bootstrap 4).
     */
    public function test_modal_adapter_handles_default_template(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Check for Bootstrap 4 modal API usage
        $this->assertStringContainsString(
            "case 'default':",
            $content,
            'Modal adapter must have case for default template'
        );

        $this->assertStringContainsString(
            ".modal('show')",
            $content,
            'Modal adapter must use Bootstrap 4 .modal("show") API'
        );

        $this->assertStringContainsString(
            ".modal('hide')",
            $content,
            'Modal adapter must use Bootstrap 4 .modal("hide") API'
        );
    }

    /**
     * @test
     * Requirement 8.2: Modal adapter handles 'canvasign' template (Bootstrap 5).
     */
    public function test_modal_adapter_handles_canvasign_template(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Check for Bootstrap 5 modal API usage
        $this->assertStringContainsString(
            "case 'canvasign':",
            $content,
            'Modal adapter must have case for canvasign template'
        );

        $this->assertStringContainsString(
            'bootstrap.Modal',
            $content,
            'Modal adapter must use Bootstrap 5 bootstrap.Modal API'
        );

        $this->assertStringContainsString(
            'getOrCreateInstance',
            $content,
            'Modal adapter must use Bootstrap 5 getOrCreateInstance method'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter handles 'canvas' template (Tailwind).
     */
    public function test_modal_adapter_handles_canvas_template(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Check for Tailwind custom modal logic
        $this->assertStringContainsString(
            "case 'canvas':",
            $content,
            'Modal adapter must have case for canvas template'
        );

        $this->assertStringContainsString(
            "removeClass('hidden')",
            $content,
            'Modal adapter must remove "hidden" class for Tailwind show'
        );

        $this->assertStringContainsString(
            "addClass('hidden')",
            $content,
            'Modal adapter must add "hidden" class for Tailwind hide'
        );

        $this->assertStringContainsString(
            'modal-backdrop',
            $content,
            'Modal adapter must manage modal-backdrop for Tailwind'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter has fallback to default template.
     */
    public function test_modal_adapter_has_default_fallback(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            "|| 'default'",
            $content,
            'Modal adapter must fallback to "default" template if window.canvastackTemplate is not set'
        );
    }

    // ── 4. Delete Handler Integration Tests ──────────────────────────────

    /**
     * @test
     * Requirement 8.4: delete-handler.js uses CanvaStackModal.hide() instead of .modal('hide').
     */
    public function test_delete_handler_uses_canvastack_modal_hide(): void
    {
        $content = file_get_contents($this->deleteHandlerPath);

        // Must use CanvaStackModal.hide()
        $this->assertStringContainsString(
            'CanvaStackModal.hide',
            $content,
            'delete-handler.js must use CanvaStackModal.hide() for modal hiding'
        );

        // Count occurrences - should have at least 2 (success and error handlers)
        $hideCount = substr_count($content, 'CanvaStackModal.hide');
        $this->assertGreaterThanOrEqual(
            2,
            $hideCount,
            'delete-handler.js must use CanvaStackModal.hide() at least 2 times (success and error handlers)'
        );
    }

    /**
     * @test
     * Requirement 8.4: delete-handler.js does NOT use direct Bootstrap modal API.
     */
    public function test_delete_handler_does_not_use_direct_bootstrap_modal_api(): void
    {
        $content = file_get_contents($this->deleteHandlerPath);

        // Remove CanvaStackModal.hide calls to avoid false positives
        $contentWithoutAdapter = str_replace('CanvaStackModal.hide', '', $content);

        // Should NOT contain direct .modal('hide') calls
        $this->assertStringNotContainsString(
            ".modal('hide')",
            $contentWithoutAdapter,
            'delete-handler.js must NOT use direct Bootstrap .modal("hide") API'
        );

        $this->assertStringNotContainsString(
            '.modal("hide")',
            $contentWithoutAdapter,
            'delete-handler.js must NOT use direct Bootstrap .modal("hide") API'
        );
    }

    /**
     * @test
     * Requirement 8.4: delete-handler.js properly extracts modalId for CanvaStackModal.hide().
     */
    public function test_delete_handler_extracts_modal_id_correctly(): void
    {
        $content = file_get_contents($this->deleteHandlerPath);

        // Should extract modalId from modal element
        $this->assertStringContainsString(
            "modal.attr('id')",
            $content,
            'delete-handler.js must extract modalId using modal.attr("id")'
        );

        // Should pass modalId to CanvaStackModal.hide()
        $this->assertMatchesRegularExpression(
            '/var\s+modalId\s*=\s*modal\.attr\([\'"]id[\'"]\)/',
            $content,
            'delete-handler.js must store modalId in a variable before passing to CanvaStackModal.hide()'
        );
    }

    // ── 5. Filter.js Integration Tests ───────────────────────────────────

    /**
     * @test
     * Requirement 8.5: filter.js uses CanvaStackModal.hide() instead of .modal('hide').
     */
    public function test_filter_js_uses_canvastack_modal_hide(): void
    {
        $content = file_get_contents($this->filterJsPath);

        // Must use CanvaStackModal.hide()
        $this->assertStringContainsString(
            'CanvaStackModal.hide',
            $content,
            'filter.js must use CanvaStackModal.hide() for modal hiding'
        );

        // Count occurrences - should have at least 3 (exportFromModal, canvastackDataTableFilters, clearDataTableFilters)
        $hideCount = substr_count($content, 'CanvaStackModal.hide');
        $this->assertGreaterThanOrEqual(
            3,
            $hideCount,
            'filter.js must use CanvaStackModal.hide() at least 3 times'
        );
    }

    /**
     * @test
     * Requirement 8.5: filter.js does NOT use direct Bootstrap modal API.
     */
    public function test_filter_js_does_not_use_direct_bootstrap_modal_api(): void
    {
        $content = file_get_contents($this->filterJsPath);

        // Remove CanvaStackModal.hide calls to avoid false positives
        $contentWithoutAdapter = str_replace('CanvaStackModal.hide', '', $content);

        // Should NOT contain direct .modal('hide') calls
        $this->assertStringNotContainsString(
            ".modal('hide')",
            $contentWithoutAdapter,
            'filter.js must NOT use direct Bootstrap .modal("hide") API'
        );

        $this->assertStringNotContainsString(
            '.modal("hide")',
            $contentWithoutAdapter,
            'filter.js must NOT use direct Bootstrap .modal("hide") API'
        );
    }

    /**
     * @test
     * Requirement 8.5: filter.js passes correct modalId to CanvaStackModal.hide().
     */
    public function test_filter_js_passes_correct_modal_id(): void
    {
        $content = file_get_contents($this->filterJsPath);

        // Should pass filterID to CanvaStackModal.hide() in exportFromModal
        $this->assertStringContainsString(
            'CanvaStackModal.hide(filterID)',
            $content,
            'filter.js exportFromModal must pass filterID to CanvaStackModal.hide()'
        );

        // Should pass constructed modalId to CanvaStackModal.hide() in canvastackDataTableFilters
        $this->assertMatchesRegularExpression(
            '/CanvaStackModal\.hide\(id\s*\+\s*[\'"]_CanvaStackFILTER[\'"]\)/',
            $content,
            'filter.js canvastackDataTableFilters must pass id + "_CanvaStackFILTER" to CanvaStackModal.hide()'
        );
    }

    // ── 6. Code Quality Tests ────────────────────────────────────────────

    /**
     * @test
     * Modal adapter has proper JSDoc comments.
     */
    public function test_modal_adapter_has_jsdoc_comments(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            '/**',
            $content,
            'Modal adapter must have JSDoc comments'
        );

        $this->assertStringContainsString(
            '* CanvaStack Modal Adapter',
            $content,
            'Modal adapter must have descriptive header comment'
        );

        $this->assertStringContainsString(
            '* Usage:',
            $content,
            'Modal adapter must have usage examples in comments'
        );
    }

    /**
     * @test
     * Modal adapter has proper error handling.
     */
    public function test_modal_adapter_has_error_handling(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Should check if modal element exists
        $this->assertStringContainsString(
            '.length === 0',
            $content,
            'Modal adapter must check if modal element exists'
        );

        // Should have console.warn or console.error for debugging
        $this->assertMatchesRegularExpression(
            '/console\.(warn|error)/',
            $content,
            'Modal adapter must have console warnings/errors for debugging'
        );
    }

    /**
     * @test
     * Modal adapter exports for CommonJS and AMD environments.
     */
    public function test_modal_adapter_exports_for_module_systems(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // CommonJS export
        $this->assertStringContainsString(
            'module.exports',
            $content,
            'Modal adapter must export for CommonJS (Node.js)'
        );

        // AMD export
        $this->assertStringContainsString(
            'define.amd',
            $content,
            'Modal adapter must export for AMD (RequireJS)'
        );
    }

    // ── 7. Integration Workflow Tests ────────────────────────────────────

    /**
     * @test
     * Requirement 8.6: Delete confirmation modal workflow is properly integrated.
     *
     * Verifies the complete workflow:
     * 1. Delete button triggers modal show
     * 2. Confirm button triggers AJAX delete
     * 3. Success/error handlers hide modal using CanvaStackModal.hide()
     */
    public function test_delete_confirmation_modal_workflow_integration(): void
    {
        $content = file_get_contents($this->deleteHandlerPath);

        // Step 1: Modal show event handler exists
        $this->assertStringContainsString(
            "$(document).on('show.bs.modal'",
            $content,
            'delete-handler.js must have modal show event handler'
        );

        // Step 2: Confirm button click handler exists
        $this->assertStringContainsString(
            "$(document).on('click'",
            $content,
            'delete-handler.js must have confirm button click handler'
        );

        // Step 3: AJAX success handler hides modal
        $this->assertMatchesRegularExpression(
            '/success\s*:\s*function.*CanvaStackModal\.hide/s',
            $content,
            'delete-handler.js AJAX success handler must hide modal using CanvaStackModal.hide()'
        );

        // Step 4: AJAX error handler hides modal
        $this->assertMatchesRegularExpression(
            '/error\s*:\s*function.*CanvaStackModal\.hide/s',
            $content,
            'delete-handler.js AJAX error handler must hide modal using CanvaStackModal.hide()'
        );
    }

    /**
     * @test
     * Requirement 8.6: Filter modal workflow is properly integrated.
     *
     * Verifies the complete workflow:
     * 1. Filter form submit triggers AJAX request
     * 2. Success handler hides modal using CanvaStackModal.hide()
     * 3. Clear filter button hides modal using CanvaStackModal.hide()
     */
    public function test_filter_modal_workflow_integration(): void
    {
        $content = file_get_contents($this->filterJsPath);

        // Step 1: Filter form submit handler exists
        $this->assertStringContainsString(
            "on('submit'",
            $content,
            'filter.js must have filter form submit handler'
        );

        // Step 2: DataTable reload callback hides modal
        $this->assertMatchesRegularExpression(
            '/ajax\.reload\(function.*CanvaStackModal\.hide/s',
            $content,
            'filter.js DataTable reload callback must hide modal using CanvaStackModal.hide()'
        );

        // Step 3: Export modal complete handler hides modal
        $this->assertMatchesRegularExpression(
            '/complete\s*:\s*function.*CanvaStackModal\.hide/s',
            $content,
            'filter.js export complete handler must hide modal using CanvaStackModal.hide()'
        );
    }

    // ── 8. Template Detection Tests ──────────────────────────────────────

    /**
     * @test
     * Requirement 8.3: Modal adapter detects template from window.canvastackTemplate.
     */
    public function test_modal_adapter_detects_template_from_window_variable(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'window.canvastackTemplate',
            $content,
            'Modal adapter must read template from window.canvastackTemplate'
        );

        $this->assertStringContainsString(
            'getTemplate()',
            $content,
            'Modal adapter must call getTemplate() to detect active template'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter uses switch statement for template-specific logic.
     */
    public function test_modal_adapter_uses_switch_for_template_logic(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        $this->assertStringContainsString(
            'switch (template)',
            $content,
            'Modal adapter must use switch statement for template-specific logic'
        );

        // Must have all three template cases
        $this->assertStringContainsString(
            "case 'default':",
            $content,
            'Modal adapter must have case for default template'
        );

        $this->assertStringContainsString(
            "case 'canvasign':",
            $content,
            'Modal adapter must have case for canvasign template'
        );

        $this->assertStringContainsString(
            "case 'canvas':",
            $content,
            'Modal adapter must have case for canvas template'
        );
    }

    // ── 9. Backward Compatibility Tests ──────────────────────────────────

    /**
     * @test
     * Requirement 8.1: Modal adapter maintains backward compatibility with Bootstrap 4.
     *
     * The default template must use Bootstrap 4 modal API which is the existing behavior.
     */
    public function test_modal_adapter_maintains_bootstrap4_compatibility(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Default template must use jQuery .modal() API
        $this->assertMatchesRegularExpression(
            "/case 'default':.*\\.modal\\('show'\\)/s",
            $content,
            'Modal adapter default template must use Bootstrap 4 .modal("show") API'
        );

        $this->assertMatchesRegularExpression(
            "/case 'default':.*\\.modal\\('hide'\\)/s",
            $content,
            'Modal adapter default template must use Bootstrap 4 .modal("hide") API'
        );
    }

    /**
     * @test
     * Requirement 8.2: Modal adapter supports Bootstrap 5 modal API.
     */
    public function test_modal_adapter_supports_bootstrap5_api(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Canvasign template must use Bootstrap 5 Modal API
        $this->assertMatchesRegularExpression(
            "/case 'canvasign':.*bootstrap\\.Modal/s",
            $content,
            'Modal adapter canvasign template must use Bootstrap 5 bootstrap.Modal API'
        );

        // Must handle both getInstance and getOrCreateInstance
        $this->assertStringContainsString(
            'getInstance',
            $content,
            'Modal adapter must use Bootstrap 5 getInstance method'
        );
    }

    /**
     * @test
     * Requirement 8.3: Modal adapter supports Tailwind custom modal logic.
     */
    public function test_modal_adapter_supports_tailwind_custom_logic(): void
    {
        $content = file_get_contents($this->modalAdapterPath);

        // Canvas template must use custom Tailwind logic
        $this->assertMatchesRegularExpression(
            "/case 'canvas':.*removeClass\\('hidden'\\)/s",
            $content,
            'Modal adapter canvas template must use removeClass("hidden") for show'
        );

        $this->assertMatchesRegularExpression(
            "/case 'canvas':.*addClass\\('hidden'\\)/s",
            $content,
            'Modal adapter canvas template must use addClass("hidden") for hide'
        );

        // Must manage body scroll
        $this->assertStringContainsString(
            'overflow-hidden',
            $content,
            'Modal adapter canvas template must manage body scroll with overflow-hidden'
        );
    }
}
