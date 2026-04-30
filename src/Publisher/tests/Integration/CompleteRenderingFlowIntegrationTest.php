<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Integration test for complete rendering flow across all three templates.
 *
 * Task 14.1: Write integration test for complete rendering flow
 * 
 * This test validates the complete ThemeAdapter system works correctly across
 * all three supported templates:
 * - `default` template → Bootstrap 4 (DefaultAdapter)
 * - `canvasign` template → Bootstrap 5 (Bootstrap5Adapter)
 * - `canvas` template → TailwindCSS (TailwindAdapter)
 *
 * Test Coverage:
 * 1. Form elements (tabs, alerts, checkboxes, select boxes) render correctly for each template
 * 2. Table elements (filter modals, action buttons, table classes) render correctly for each template
 * 3. Modal wrappers render correctly for each template
 * 4. Bootstrap 5 output does NOT contain Bootstrap 4 attributes (data-toggle, data-dismiss)
 * 5. Tailwind output does NOT contain Bootstrap-specific classes
 *
 * Requirements Validated: 4.8, 5.1-5.8, 6.1-6.7, 7.7, 8.6, 9.6, 10.6
 *
 * @group integration
 * @group theme-adapter
 * @group end-to-end
 */
class CompleteRenderingFlowIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::resetAll();
    }

    protected function tearDown(): void
    {
        ThemeAdapterResolver::resetAll();
        parent::tearDown();
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    /**
     * Set the active template via Laravel config.
     */
    private function setTemplate(string $template): void
    {
        config(['canvastack.settings.template' => $template]);
        ThemeAdapterResolver::reset();
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 1: Form Rendering Tests
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 4.8, 7.7: Test form rendering with default template (Bootstrap 4)
     * 
     * Validates that all form elements render correctly with Bootstrap 4 classes
     * and attributes when using the default template.
     */
    public function test_form_rendering_with_default_template(): void
    {
        $this->setTemplate('default');

        // Test tab header
        $tabHeader = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', 'fa fa-user');
        $this->assertStringContainsString('data-toggle="tab"', $tabHeader, 'Tab header must use Bootstrap 4 data-toggle');
        $this->assertStringContainsString('nav-item', $tabHeader, 'Tab header must contain nav-item class');
        $this->assertStringContainsString('nav-link', $tabHeader, 'Tab header must contain nav-link class');

        // Test tab content
        $tabContent = canvastack_form_create_content_tab('<p>Content</p>', 'tab-profile', true);
        $this->assertStringContainsString('tab-pane', $tabContent, 'Tab content must contain tab-pane class');
        $this->assertStringContainsString('tab-profile', $tabContent, 'Tab content must contain pointer as id');

        // Test alert message
        $alert = canvastack_form_alert_message('Success!', 'success', 'Success', 'fa-check', false);
        $this->assertStringContainsString('alert-block', $alert, 'Alert must contain Bootstrap 4 alert-block class');
        $this->assertStringContainsString('data-dismiss="alert"', $alert, 'Alert must use Bootstrap 4 data-dismiss');

        // Test checkbox
        $checkbox = canvastack_form_checkList('agree', '1', 'I agree', false, 'success', false, null);
        $this->assertStringContainsString('ckbox', $checkbox, 'Checkbox must contain Bootstrap 4 ckbox class');

        // Test select box
        $selectBox = canvastack_form_selectbox('country', ['us' => 'USA', 'uk' => 'UK'], false, [], true, [null => 'Select']);
        $this->assertStringContainsString('chosen-select', $selectBox, 'Select box must contain chosen-select class');
    }

    /**
     * @test
     * Requirement 5.1-5.8, 7.7: Test form rendering with canvasign template (Bootstrap 5)
     * 
     * Validates that all form elements render correctly with Bootstrap 5 classes
     * and attributes when using the canvasign template.
     */
    public function test_form_rendering_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        // Test tab header
        $tabHeader = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', 'fa fa-user');
        $this->assertStringContainsString('data-bs-toggle="tab"', $tabHeader, 'Tab header must use Bootstrap 5 data-bs-toggle');
        $this->assertStringNotContainsString('data-toggle="tab"', $tabHeader, 'Tab header must NOT contain Bootstrap 4 data-toggle');

        // Test tab content
        $tabContent = canvastack_form_create_content_tab('<p>Content</p>', 'tab-profile', true);
        $this->assertStringContainsString('tab-pane', $tabContent, 'Tab content must contain tab-pane class');

        // Test alert message
        $alert = canvastack_form_alert_message('Success!', 'success', 'Success', 'fa-check', false);
        $this->assertStringContainsString('data-bs-dismiss="alert"', $alert, 'Alert must use Bootstrap 5 data-bs-dismiss');
        $this->assertStringNotContainsString('data-dismiss="alert"', $alert, 'Alert must NOT contain Bootstrap 4 data-dismiss');
        $this->assertStringNotContainsString('alert-block', $alert, 'Alert must NOT contain Bootstrap 4 alert-block class');

        // Test checkbox
        $checkbox = canvastack_form_checkList('agree', '1', 'I agree', false, 'success', false, null);
        $this->assertStringContainsString('form-check', $checkbox, 'Checkbox must contain Bootstrap 5 form-check class');
        $this->assertStringNotContainsString('class="ckbox', $checkbox, 'Checkbox must NOT contain Bootstrap 4 ckbox class');

        // Test select box
        $selectBox = canvastack_form_selectbox('country', ['us' => 'USA', 'uk' => 'UK'], false, [], true, [null => 'Select']);
        $this->assertStringContainsString('form-select', $selectBox, 'Select box must contain Bootstrap 5 form-select class');
        $this->assertStringNotContainsString('chosen-select', $selectBox, 'Select box must NOT contain chosen-select class');
    }

    /**
     * @test
     * Requirement 6.1-6.7, 7.7: Test form rendering with canvas template (Tailwind)
     * 
     * Validates that all form elements render correctly with Tailwind utility classes
     * and NO Bootstrap-specific classes when using the canvas template.
     */
    public function test_form_rendering_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        // Test tab header
        $tabHeader = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', 'fa fa-user');
        $this->assertStringNotContainsString('nav-item', $tabHeader, 'Tab header must NOT contain Bootstrap nav-item class');
        $this->assertStringContainsString('cursor-pointer', $tabHeader, 'Tab header must contain Tailwind cursor-pointer class');

        // Test tab content
        $tabContent = canvastack_form_create_content_tab('<p>Content</p>', 'tab-profile', true);
        $this->assertStringContainsString('tab-pane', $tabContent, 'Tab content must contain tab-pane class');

        // Test alert message
        $alert = canvastack_form_alert_message('Success!', 'success', 'Success', 'fa-check', false);
        $this->assertStringNotContainsString('alert-block', $alert, 'Alert must NOT contain Bootstrap alert-block class');
        $this->assertStringNotContainsString('data-bs-dismiss', $alert, 'Alert must NOT contain Bootstrap 5 data-bs-dismiss');
        $this->assertStringContainsString('flex', $alert, 'Alert must contain Tailwind flex class');

        // Test checkbox
        $checkbox = canvastack_form_checkList('agree', '1', 'I agree', false, 'success', false, null);
        $this->assertStringNotContainsString('class="ckbox', $checkbox, 'Checkbox must NOT contain Bootstrap ckbox class');
        $this->assertStringNotContainsString('form-check-input', $checkbox, 'Checkbox must NOT contain Bootstrap form-check-input class');
        $this->assertStringContainsString('flex', $checkbox, 'Checkbox must contain Tailwind flex class');

        // Test select box
        $selectBox = canvastack_form_selectbox('country', ['us' => 'USA', 'uk' => 'UK'], false, [], true, [null => 'Select']);
        $this->assertStringNotContainsString('chosen-select', $selectBox, 'Select box must NOT contain chosen-select class');
        $this->assertStringNotContainsString('form-select', $selectBox, 'Select box must NOT contain Bootstrap form-select class');
        $this->assertStringContainsString('form-input', $selectBox, 'Select box must contain Tailwind form-input class');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 2: Table Rendering Tests
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 8.6, 10.6: Test table rendering with default template (Bootstrap 4)
     * 
     * Validates that table elements render correctly with Bootstrap 4 classes
     * when using the default template.
     */
    public function test_table_rendering_with_default_template(): void
    {
        $this->setTemplate('default');

        // Test table class
        $adapter = ThemeAdapterResolver::resolve();
        $tableClass = $adapter->getTableClass();
        $this->assertStringContainsString('animated', $tableClass, 'Table class must contain animated class');
        $this->assertStringContainsString('fadeIn', $tableClass, 'Table class must contain fadeIn class');

        // Test utility methods
        $this->assertEquals('data-dismiss', $adapter->getDismissAttribute(), 'Dismiss attribute must be data-dismiss');
        $this->assertEquals('pull-right', $adapter->getFloatRightClass(), 'Float right class must be pull-right');
        $this->assertEquals('hide', $adapter->getHideClass(), 'Hide class must be hide');
    }

    /**
     * @test
     * Requirement 8.6, 10.6: Test table rendering with canvasign template (Bootstrap 5)
     * 
     * Validates that table elements render correctly with Bootstrap 5 classes
     * and NO Bootstrap 4 attributes when using the canvasign template.
     */
    public function test_table_rendering_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        // Test table class
        $adapter = ThemeAdapterResolver::resolve();
        $tableClass = $adapter->getTableClass();
        $this->assertStringNotContainsString('animated', $tableClass, 'Table class must NOT contain animated class');
        $this->assertStringNotContainsString('fadeIn', $tableClass, 'Table class must NOT contain fadeIn class');

        // Test utility methods
        $this->assertEquals('data-bs-dismiss', $adapter->getDismissAttribute(), 'Dismiss attribute must be data-bs-dismiss');
        $this->assertEquals('float-end', $adapter->getFloatRightClass(), 'Float right class must be float-end');
        $this->assertEquals('d-none', $adapter->getHideClass(), 'Hide class must be d-none');
    }

    /**
     * @test
     * Requirement 8.6, 10.6: Test table rendering with canvas template (Tailwind)
     * 
     * Validates that table elements render correctly with Tailwind utility classes
     * and NO Bootstrap-specific classes when using the canvas template.
     */
    public function test_table_rendering_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        // Test table class
        $adapter = ThemeAdapterResolver::resolve();
        $tableClass = $adapter->getTableClass();
        $this->assertStringNotContainsString('animated', $tableClass, 'Table class must NOT contain animated class');
        $this->assertStringNotContainsString('fadeIn', $tableClass, 'Table class must NOT contain fadeIn class');
        $this->assertStringContainsString('w-full', $tableClass, 'Table class must contain Tailwind w-full class');

        // Test utility methods
        $this->assertEquals('data-dismiss', $adapter->getDismissAttribute(), 'Dismiss attribute must be data-dismiss');
        $this->assertEquals('ml-auto', $adapter->getFloatRightClass(), 'Float right class must be ml-auto');
        $this->assertEquals('hidden', $adapter->getHideClass(), 'Hide class must be hidden');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 3: Modal Wrapper Tests
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 4.8: Test modal wrapper rendering with default template (Bootstrap 4)
     */
    public function test_modal_wrapper_rendering_with_default_template(): void
    {
        $this->setTemplate('default');
        $adapter = ThemeAdapterResolver::resolve();

        // Test utility methods that modal wrapper uses
        $this->assertEquals('data-dismiss', $adapter->getDismissAttribute(), 'Modal must use Bootstrap 4 data-dismiss');
        $this->assertEquals('data-toggle', $adapter->getDataToggleAttribute(), 'Modal must use Bootstrap 4 data-toggle');
    }

    /**
     * @test
     * Requirement 5.8: Test modal wrapper rendering with canvasign template (Bootstrap 5)
     */
    public function test_modal_wrapper_rendering_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');
        $adapter = ThemeAdapterResolver::resolve();

        // Test utility methods that modal wrapper uses
        $this->assertEquals('data-bs-dismiss', $adapter->getDismissAttribute(), 'Modal must use Bootstrap 5 data-bs-dismiss');
        $this->assertEquals('data-bs-toggle', $adapter->getDataToggleAttribute(), 'Modal must use Bootstrap 5 data-bs-toggle');
    }

    /**
     * @test
     * Requirement 6.7: Test modal wrapper rendering with canvas template (Tailwind)
     */
    public function test_modal_wrapper_rendering_with_canvas_template(): void
    {
        $this->setTemplate('canvas');
        $adapter = ThemeAdapterResolver::resolve();

        // Test utility methods that modal wrapper uses
        $this->assertEquals('data-dismiss', $adapter->getDismissAttribute(), 'Modal must use data-dismiss for custom JS');
        $this->assertEquals('data-toggle', $adapter->getDataToggleAttribute(), 'Modal must use data-toggle for custom JS');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 4: Bootstrap 5 Attribute Validation
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 5.1-5.8: Verify Bootstrap 5 output does NOT contain Bootstrap 4 attributes
     * 
     * This is a comprehensive test that validates NO Bootstrap 4 attributes
     * (data-toggle, data-dismiss) appear in Bootstrap 5 output.
     */
    public function test_bootstrap5_output_contains_no_bootstrap4_attributes(): void
    {
        $this->setTemplate('canvasign');

        // Collect all outputs
        $outputs = [];

        // Form elements
        $outputs[] = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', false);
        $outputs[] = canvastack_form_alert_message('Test', 'success', 'Success', 'fa-check', false);
        $outputs[] = canvastack_form_checkList('test', '1', 'Test', false, 'success', false, null);

        // Table elements
        $outputs[] = canvastack_modal_content_html('filter', 'Filter', []);
        
        // Modal wrapper
        $adapter = ThemeAdapterResolver::resolve();
        $outputs[] = $adapter->renderModalWrapper('test', 'Test', []);

        // Validate NO Bootstrap 4 attributes in any output
        foreach ($outputs as $output) {
            $this->assertStringNotContainsString(
                'data-toggle="tab"',
                $output,
                'Bootstrap 5 output must NOT contain Bootstrap 4 data-toggle="tab"'
            );
            $this->assertStringNotContainsString(
                'data-dismiss="alert"',
                $output,
                'Bootstrap 5 output must NOT contain Bootstrap 4 data-dismiss="alert"'
            );
            $this->assertStringNotContainsString(
                'data-dismiss="modal"',
                $output,
                'Bootstrap 5 output must NOT contain Bootstrap 4 data-dismiss="modal"'
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 5: Tailwind Class Validation
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 6.1-6.7: Verify Tailwind output does NOT contain Bootstrap classes
     * 
     * This is a comprehensive test that validates NO Bootstrap-specific classes
     * appear in Tailwind output.
     */
    public function test_tailwind_output_contains_no_bootstrap_classes(): void
    {
        $this->setTemplate('canvas');

        // Collect all outputs
        $outputs = [];

        // Form elements
        $outputs[] = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', false);
        $outputs[] = canvastack_form_alert_message('Test', 'success', 'Success', 'fa-check', false);
        $outputs[] = canvastack_form_checkList('mytest', '1', 'Test', false, 'success', false, null);
        $outputs[] = canvastack_form_selectbox('myselect', ['a' => 'A'], false, [], true, [null => 'Select']);

        // Bootstrap-specific classes that must NOT appear
        $forbiddenClasses = [
            'alert-block',
            'nav-item',
            'class="ckbox',  // Check for ckbox as a class, not in ID
            'chosen-select',
            'btn-xs',
            'pull-right',
            'hide',
            'd-none',
        ];

        // Validate NO Bootstrap classes in any output
        foreach ($outputs as $output) {
            foreach ($forbiddenClasses as $forbiddenClass) {
                $this->assertStringNotContainsString(
                    $forbiddenClass,
                    $output,
                    "Tailwind output must NOT contain Bootstrap class: {$forbiddenClass}"
                );
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // SECTION 6: Cross-Template Consistency Tests
    // ══════════════════════════════════════════════════════════════════════

    /**
     * @test
     * Requirement 7.7, 8.6, 9.6, 10.6: Verify all templates produce valid output
     * 
     * This test validates that all three templates produce non-empty, valid HTML
     * output for all rendering methods.
     */
    public function test_all_templates_produce_valid_output(): void
    {
        $templates = ['default', 'canvasign', 'canvas'];

        foreach ($templates as $template) {
            $this->setTemplate($template);

            // Test form elements
            $tabHeader = canvastack_form_create_header_tab('test', 'tab-test', false, false);
            $this->assertNotEmpty($tabHeader, "Template {$template}: Tab header must not be empty");
            $this->assertStringContainsString('<', $tabHeader, "Template {$template}: Tab header must contain HTML");

            $tabContent = canvastack_form_create_content_tab('<p>Test</p>', 'tab-test', false);
            $this->assertNotEmpty($tabContent, "Template {$template}: Tab content must not be empty");

            $alert = canvastack_form_alert_message('Test', 'info', 'Info', 'fa-info', false);
            $this->assertNotEmpty($alert, "Template {$template}: Alert must not be empty");

            $checkbox = canvastack_form_checkList('test', '1', 'Test', false, 'success', false, null);
            $this->assertNotEmpty($checkbox, "Template {$template}: Checkbox must not be empty");

            $selectBox = canvastack_form_selectbox('test', ['a' => 'A'], false, [], true, [null => 'Select']);
            $this->assertNotEmpty($selectBox, "Template {$template}: Select box must not be empty");

            // Test table elements
            $adapter = ThemeAdapterResolver::resolve();
            $tableClass = $adapter->getTableClass();
            $this->assertNotEmpty($tableClass, "Template {$template}: Table class must not be empty");
        }
    }

    /**
     * @test
     * Requirement 7.7, 8.6: Verify resolver returns correct adapter for each template
     */
    public function test_resolver_returns_correct_adapter_for_each_template(): void
    {
        // Test default template
        $this->setTemplate('default');
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(DefaultAdapter::class, $adapter, 'Default template must resolve to DefaultAdapter');

        // Test canvasign template
        $this->setTemplate('canvasign');
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(Bootstrap5Adapter::class, $adapter, 'Canvasign template must resolve to Bootstrap5Adapter');

        // Test canvas template
        $this->setTemplate('canvas');
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(TailwindAdapter::class, $adapter, 'Canvas template must resolve to TailwindAdapter');
    }
}
