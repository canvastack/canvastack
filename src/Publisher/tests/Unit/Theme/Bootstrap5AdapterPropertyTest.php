<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Eris\TestTrait;
use Eris\Generators;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;

/**
 * Property-based tests for Bootstrap5Adapter.
 *
 * Property 6: Bootstrap5Adapter does not use Bootstrap 4 attributes.
 * For any valid input, output must contain Bootstrap 5 attributes
 * (data-bs-toggle / data-bs-dismiss) and must NOT contain Bootstrap 4
 * equivalents (data-toggle="tab" / data-dismiss="alert").
 *
 * Requirements: 5.1, 5.2, 5.3, 5.7, 5.8
 *
 * Uses giorgiosironi/eris with minimum 100 iterations per property.
 */
class Bootstrap5AdapterPropertyTest extends TestCase
{
    use TestTrait;

    private Bootstrap5Adapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter    = new Bootstrap5Adapter();
        $this->iterations = 25;
    }

    // ── Generators ────────────────────────────────────────────────────────

    /**
     * Generator for safe alphanumeric strings (avoids HTML-breaking chars).
     */
    private function safeString(): \Eris\Generator
    {
        return Generators::map(
            function (string $s): string {
                $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', $s);
                return $clean ?: 'default';
            },
            Generators::string()
        );
    }

    /**
     * Generator for alert type strings.
     */
    private function alertTypeGenerator(): \Eris\Generator
    {
        return Generators::elements('success', 'danger', 'warning', 'info');
    }

    /**
     * Generator for string|false values.
     */
    private function stringOrFalse(): \Eris\Generator
    {
        return Generators::oneOf(
            Generators::constant(false),
            $this->safeString()
        );
    }

    // ── Property 6a: renderTabHeader uses data-bs-toggle, not data-toggle ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderTabHeader() output contains 'data-bs-toggle'
     *             and does NOT contain Bootstrap 4 'data-toggle="tab"'.
     *
     * Validates: Requirements 5.1
     */
    public function test_render_tab_header_uses_bootstrap5_toggle_attribute(): void
    {
        $this->forAll(
            $this->safeString(),    // $data
            $this->safeString(),    // $pointer
            $this->stringOrFalse(), // $active
            $this->stringOrFalse()  // $class
        )->then(function (string $data, string $pointer, $active, $class): void {
            $output = $this->adapter->renderTabHeader($data, $pointer, $active, $class);

            $this->assertStringContainsString(
                'data-bs-toggle',
                $output,
                'renderTabHeader() must contain Bootstrap 5 data-bs-toggle attribute'
            );

            $this->assertStringNotContainsString(
                'data-toggle="tab"',
                $output,
                'renderTabHeader() must NOT contain Bootstrap 4 data-toggle="tab"'
            );
        });
    }

    // ── Property 6b: renderAlertMessage uses data-bs-dismiss, not data-dismiss ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderAlertMessage() output contains 'data-bs-dismiss'
     *             and does NOT contain Bootstrap 4 'data-dismiss="alert"'.
     *
     * Validates: Requirements 5.2, 5.3
     */
    public function test_render_alert_message_uses_bootstrap5_dismiss_attribute(): void
    {
        $this->forAll(
            $this->safeString(),         // $message
            $this->alertTypeGenerator(), // $type
            $this->safeString(),         // $title
            $this->safeString(),         // $prefix
            $this->stringOrFalse()       // $extra
        )->then(function (string $message, string $type, string $title, string $prefix, $extra): void {
            $output = $this->adapter->renderAlertMessage($message, $type, $title, $prefix, $extra);

            $this->assertStringContainsString(
                'data-bs-dismiss',
                $output,
                'renderAlertMessage() must contain Bootstrap 5 data-bs-dismiss attribute'
            );

            $this->assertStringNotContainsString(
                'data-dismiss="alert"',
                $output,
                'renderAlertMessage() must NOT contain Bootstrap 4 data-dismiss="alert"'
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderAlertMessage() output does NOT contain 'alert-block'
     *             (Bootstrap 5 dropped this class).
     *
     * Validates: Requirements 5.3
     */
    public function test_render_alert_message_does_not_contain_alert_block(): void
    {
        $this->forAll(
            $this->safeString(),
            $this->alertTypeGenerator(),
            $this->safeString(),
            $this->safeString(),
            $this->stringOrFalse()
        )->then(function (string $message, string $type, string $title, string $prefix, $extra): void {
            $output = $this->adapter->renderAlertMessage($message, $type, $title, $prefix, $extra);

            $this->assertStringNotContainsString(
                'alert-block',
                $output,
                'renderAlertMessage() must NOT contain Bootstrap 4 alert-block class'
            );
        });
    }

    // ── Property 6c: renderCheckList uses Bootstrap 5 form-check structure ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderCheckList() output contains Bootstrap 5
     *             form-check classes and does NOT contain Bootstrap 4 ckbox wrapper.
     *
     * Validates: Requirements 5.7
     */
    public function test_render_check_list_uses_bootstrap5_form_check_structure(): void
    {
        $this->forAll(
            $this->safeString(),    // $name
            $this->stringOrFalse(), // $value
            $this->stringOrFalse(), // $label
            Generators::bool(),     // $checked
            Generators::elements('success', 'danger', 'warning', 'info', 'primary'), // $class
            $this->stringOrFalse()  // $id
        )->then(function (string $name, $value, $label, bool $checked, string $class, $id): void {
            $name   = $name ?: 'checkbox_name';
            $output = $this->adapter->renderCheckList($name, $value, $label, $checked, $class, $id, null);

            $this->assertStringContainsString(
                'form-check',
                $output,
                'renderCheckList() must contain Bootstrap 5 form-check class'
            );

            $this->assertStringContainsString(
                'form-check-input',
                $output,
                'renderCheckList() must contain Bootstrap 5 form-check-input class'
            );

            $this->assertStringContainsString(
                'form-check-label',
                $output,
                'renderCheckList() must contain Bootstrap 5 form-check-label class'
            );

            $this->assertStringNotContainsString(
                'class="ckbox',
                $output,
                'renderCheckList() must NOT contain Bootstrap 4 ckbox wrapper class'
            );
        });
    }

    // ── Property 6d: renderModalWrapper uses data-bs-dismiss ─────────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderModalWrapper() output contains 'data-bs-dismiss'
     *             and does NOT contain Bootstrap 4 'data-dismiss="modal"'.
     *
     * Validates: Requirements 5.8
     */
    public function test_render_modal_wrapper_uses_bootstrap5_dismiss_attribute(): void
    {
        $this->forAll(
            $this->safeString(), // $name
            $this->safeString()  // $title
        )->then(function (string $name, string $title): void {
            $name  = $name ?: 'modal_name';
            $title = $title ?: 'Modal Title';

            $output = $this->adapter->renderModalWrapper($name, $title, []);

            $this->assertStringContainsString(
                'data-bs-dismiss',
                $output,
                'renderModalWrapper() must contain Bootstrap 5 data-bs-dismiss attribute'
            );

            $this->assertStringNotContainsString(
                'data-dismiss="modal"',
                $output,
                'renderModalWrapper() must NOT contain Bootstrap 4 data-dismiss="modal"'
            );
        });
    }

    // ── Property 6e: renderFilterModal uses Bootstrap 5 classes ──────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderFilterModal() output contains Bootstrap 5
     *             attributes and classes, not Bootstrap 4 equivalents.
     *
     * Validates: Requirements 8.3
     */
    public function test_render_filter_modal_uses_bootstrap5_attributes(): void
    {
        $this->forAll(
            $this->safeString(), // $name
            $this->safeString()  // $title
        )->then(function (string $name, string $title): void {
            $name  = $name ?: 'filter_modal';
            $title = $title ?: 'Filter';

            $output = $this->adapter->renderFilterModal($name, $title, []);

            // Must use Bootstrap 5 dismiss
            $this->assertStringContainsString(
                'data-bs-dismiss',
                $output,
                'renderFilterModal() must contain Bootstrap 5 data-bs-dismiss'
            );

            // Must use Bootstrap 5 float-end
            $this->assertStringContainsString(
                'float-end',
                $output,
                'renderFilterModal() must contain Bootstrap 5 float-end class'
            );

            // Must use Bootstrap 5 d-none
            $this->assertStringContainsString(
                'd-none',
                $output,
                'renderFilterModal() must contain Bootstrap 5 d-none class'
            );

            // Must NOT use Bootstrap 4 equivalents
            $this->assertStringNotContainsString(
                'data-dismiss="modal"',
                $output,
                'renderFilterModal() must NOT contain Bootstrap 4 data-dismiss="modal"'
            );

            $this->assertStringNotContainsString(
                'pull-right',
                $output,
                'renderFilterModal() must NOT contain Bootstrap 4 pull-right class'
            );

            $this->assertStringNotContainsString(
                '"hide"',
                $output,
                'renderFilterModal() must NOT contain Bootstrap 4 hide class'
            );
        });
    }

    // ── Property 4 (partial): All render methods return strings, never null ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 4 (partial): All Bootstrap5Adapter render methods return strings, never null.
     *
     * Validates: Requirements 5.1–5.8
     */
    public function test_all_render_methods_return_strings_never_null(): void
    {
        $this->forAll(
            $this->safeString(),
            $this->safeString()
        )->then(function (string $a, string $b): void {
            $a = $a ?: 'a';
            $b = $b ?: 'b';

            $this->assertIsString($this->adapter->renderTabHeader($a, $b, false, false));
            $this->assertIsString($this->adapter->renderTabContent($a, $b, false));
            $this->assertIsString($this->adapter->renderAlertMessage($a, 'success', $b, 'fa-check', false));
            $this->assertIsString($this->adapter->renderCheckList($a, false, false, false, 'success', false, null));
            $this->assertIsString($this->adapter->renderFilterModal($a, $b, []));
            $this->assertIsString($this->adapter->renderModalWrapper($a, $b, []));
        });
    }

    // ── Property 6f: renderTabContent returns valid HTML structure ────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 6: Bootstrap5Adapter::renderTabContent() returns a tab-pane div
     *             with the correct pointer as the id attribute.
     *
     * Validates: Requirements 5.1 (structural consistency)
     */
    public function test_render_tab_content_returns_tab_pane_structure(): void
    {
        $this->forAll(
            $this->safeString(), // $data
            $this->safeString(), // $pointer
            Generators::bool()   // $active
        )->then(function (string $data, string $pointer, bool $active): void {
            $output = $this->adapter->renderTabContent($data, $pointer, $active);

            $this->assertStringContainsString('tab-pane', $output);
            $this->assertStringContainsString('id="' . htmlspecialchars($pointer, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"', $output);
        });
    }
}
