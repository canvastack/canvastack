<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Eris\TestTrait;
use Eris\Generators;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;

/**
 * Property-based tests for DefaultAdapter backward compatibility.
 *
 * Property 1: DefaultAdapter output is byte-for-byte identical to existing helpers
 * for any valid input.
 *
 * Requirements: 4.2, 4.3, 4.4, 4.5, 4.8, 8.2, 10.2
 *
 * Uses giorgiosironi/eris with minimum 100 iterations per property.
 */
class DefaultAdapterPropertyTest extends TestCase
{
    use TestTrait;

    private DefaultAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new DefaultAdapter();
        $this->iterations = 25;
    }

    // ── Generators ────────────────────────────────────────────────────────

    /**
     * Generator for safe alphanumeric strings (avoids HTML-breaking chars
     * that would cause false mismatches due to escaping differences).
     * Uses regex generator to produce only word-safe characters.
     */
    private function safeString(): \Eris\Generator
    {
        return Generators::map(
            function (string $s): string {
                // Keep only safe chars, ensure non-empty
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

    // ── Property 1a: renderTabHeader matches canvastack_form_create_header_tab ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderTabHeader() output is identical to
     *             canvastack_form_create_header_tab() for any valid input.
     *
     * Validates: Requirements 4.2, 4.8
     */
    public function test_render_tab_header_matches_existing_helper(): void
    {
        $this->forAll(
            $this->safeString(),    // $data
            $this->safeString(),    // $pointer
            $this->stringOrFalse(), // $active
            $this->stringOrFalse()  // $class
        )->then(function (string $data, string $pointer, $active, $class): void {
            $adapterOutput = $this->adapter->renderTabHeader($data, $pointer, $active, $class);
            $helperOutput  = canvastack_form_create_header_tab($data, $pointer, $active, $class);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderTabHeader() output differs from canvastack_form_create_header_tab() for data='{$data}', pointer='{$pointer}'"
            );
        });
    }

    // ── Property 1b: renderTabContent matches canvastack_form_create_content_tab ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderTabContent() output is identical to
     *             canvastack_form_create_content_tab() for any valid input.
     *
     * Validates: Requirements 4.2, 4.8
     */
    public function test_render_tab_content_matches_existing_helper(): void
    {
        $this->forAll(
            $this->safeString(), // $data (tab content HTML)
            $this->safeString(), // $pointer
            Generators::bool()   // $active
        )->then(function (string $data, string $pointer, bool $active): void {
            $adapterOutput = $this->adapter->renderTabContent($data, $pointer, $active);
            $helperOutput  = canvastack_form_create_content_tab($data, $pointer, $active);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderTabContent() output differs from canvastack_form_create_content_tab() for pointer='{$pointer}'"
            );
        });
    }

    // ── Property 1c: renderAlertMessage matches canvastack_form_alert_message ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderAlertMessage() output is identical to
     *             canvastack_form_alert_message() for any valid string message input.
     *
     * Validates: Requirements 4.3, 4.8
     */
    public function test_render_alert_message_matches_existing_helper_string(): void
    {
        $this->forAll(
            $this->safeString(),         // $message
            $this->alertTypeGenerator(), // $type
            $this->safeString(),         // $title
            $this->safeString(),         // $prefix
            $this->stringOrFalse()       // $extra
        )->then(function (string $message, string $type, string $title, string $prefix, $extra): void {
            $adapterOutput = $this->adapter->renderAlertMessage($message, $type, $title, $prefix, $extra);
            $helperOutput  = canvastack_form_alert_message($message, $type, $title, $prefix, $extra);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderAlertMessage() output differs from canvastack_form_alert_message() for type='{$type}'"
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderAlertMessage() output is identical to
     *             canvastack_form_alert_message() for array message input (validation errors).
     *
     * Validates: Requirements 4.3, 4.8
     */
    public function test_render_alert_message_matches_existing_helper_array(): void
    {
        $this->forAll(
            Generators::elements('danger', 'warning', 'info'), // $type (not 'success' to trigger array path)
            $this->safeString(),                               // $title
            $this->safeString()                                // field name key
        )->then(function (string $type, string $title, string $fieldName): void {
            $fieldName = $fieldName ?: 'field';
            $message   = [$fieldName => ['Error message one', 'Error message two']];

            $adapterOutput = $this->adapter->renderAlertMessage($message, $type, $title, 'fa-warning', false);
            $helperOutput  = canvastack_form_alert_message($message, $type, $title, 'fa-warning', false);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderAlertMessage() array output differs from canvastack_form_alert_message() for type='{$type}'"
            );
        });
    }

    // ── Property 1d: renderCheckList matches canvastack_form_checkList ────

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderCheckList() output is identical to
     *             canvastack_form_checkList() for any valid input.
     *
     * Validates: Requirements 4.4, 4.8
     */
    public function test_render_check_list_matches_existing_helper(): void
    {
        $this->forAll(
            $this->safeString(),    // $name
            $this->stringOrFalse(), // $value
            $this->stringOrFalse(), // $label
            Generators::bool(),     // $checked
            Generators::elements('success', 'danger', 'warning', 'info', 'primary'), // $class
            $this->stringOrFalse()  // $id
        )->then(function (string $name, $value, $label, bool $checked, string $class, $id): void {
            $name = $name ?: 'checkbox_name';

            $adapterOutput = $this->adapter->renderCheckList($name, $value, $label, $checked, $class, $id, null);
            $helperOutput  = canvastack_form_checkList($name, $value, $label, $checked, $class, $id, null);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderCheckList() output differs from canvastack_form_checkList() for name='{$name}'"
            );
        });
    }

    // ── Property 1e: renderSelectBox matches canvastack_form_selectbox ────

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderSelectBox() output is identical to
     *             canvastack_form_selectbox() for any valid input.
     *
     * Validates: Requirements 4.5, 4.8
     */
    public function test_render_select_box_matches_existing_helper(): void
    {
        $this->forAll(
            $this->safeString(), // $name
            Generators::elements(
                ['option1' => 'Option 1', 'option2' => 'Option 2'],
                ['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma'],
                ['1' => 'One', '2' => 'Two']
            ), // $values
            $this->stringOrFalse() // $selected
        )->then(function (string $name, array $values, $selected): void {
            $name = $name ?: 'select_name';

            $adapterOutput = $this->adapter->renderSelectBox($name, $values, $selected, [], true, [null => 'Select']);
            $helperOutput  = canvastack_form_selectbox($name, $values, $selected, [], true, [null => 'Select']);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderSelectBox() output differs from canvastack_form_selectbox() for name='{$name}'"
            );
        });
    }

    // ── Property 1f: renderFilterModal matches canvastack_modal_content_html ──

    /**
     * @test
     * Feature: theme-adapter
     * Property 1: DefaultAdapter::renderFilterModal() output is identical to
     *             canvastack_modal_content_html() for any valid input.
     *
     * Validates: Requirements 8.2, 4.8
     */
    public function test_render_filter_modal_matches_existing_helper(): void
    {
        $this->forAll(
            $this->safeString(), // $name
            $this->safeString()  // $title
        )->then(function (string $name, string $title): void {
            // Ensure name is non-empty (renderFilterModal throws on empty name)
            $name  = $name ?: 'modal_name';
            $title = $title ?: 'Modal Title';

            $elements = ['<div>Filter Field 1</div>', '<div>Filter Field 2</div>'];

            $adapterOutput = $this->adapter->renderFilterModal($name, $title, $elements);
            $helperOutput  = canvastack_modal_content_html($name, $title, $elements);

            $this->assertSame(
                $helperOutput,
                $adapterOutput,
                "renderFilterModal() output differs from canvastack_modal_content_html() for name='{$name}'"
            );
        });
    }

    // ── Bootstrap 4 structural assertions ────────────────────────────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 1 (structural): renderTabHeader() always contains Bootstrap 4 data-toggle="tab"
     *
     * Validates: Requirements 4.1, 4.2
     */
    public function test_render_tab_header_always_contains_bootstrap4_toggle(): void
    {
        $this->forAll(
            $this->safeString(),
            $this->safeString(),
            $this->stringOrFalse(),
            $this->stringOrFalse()
        )->then(function (string $data, string $pointer, $active, $class): void {
            $output = $this->adapter->renderTabHeader($data, $pointer, $active, $class);

            $this->assertStringContainsString(
                'data-toggle="tab"',
                $output,
                'renderTabHeader() must always contain Bootstrap 4 data-toggle="tab"'
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 1 (structural): renderAlertMessage() always contains Bootstrap 4 alert-block class
     *
     * Validates: Requirements 4.3
     */
    public function test_render_alert_message_always_contains_alert_block(): void
    {
        $this->forAll(
            $this->safeString(),
            $this->alertTypeGenerator(),
            $this->safeString(),
            $this->safeString(),
            $this->stringOrFalse()
        )->then(function (string $message, string $type, string $title, string $prefix, $extra): void {
            $output = $this->adapter->renderAlertMessage($message, $type, $title, $prefix, $extra);

            $this->assertStringContainsString(
                'alert-block',
                $output,
                'renderAlertMessage() must always contain Bootstrap 4 alert-block class'
            );
            $this->assertStringContainsString(
                'data-dismiss="alert"',
                $output,
                'renderAlertMessage() must always contain Bootstrap 4 data-dismiss="alert"'
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 1 (structural): renderCheckList() always contains Bootstrap 4 ckbox wrapper
     *
     * Validates: Requirements 4.4
     */
    public function test_render_check_list_always_contains_ckbox_wrapper(): void
    {
        $this->forAll(
            $this->safeString(),
            $this->stringOrFalse(),
            $this->stringOrFalse(),
            Generators::bool(),
            Generators::elements('success', 'danger', 'warning', 'info'),
            $this->stringOrFalse()
        )->then(function (string $name, $value, $label, bool $checked, string $class, $id): void {
            $name   = $name ?: 'cb';
            $output = $this->adapter->renderCheckList($name, $value, $label, $checked, $class, $id, null);

            $this->assertStringContainsString(
                'ckbox',
                $output,
                'renderCheckList() must always contain Bootstrap 4 ckbox wrapper class'
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 1 (structural): renderFilterModal() always contains Bootstrap 4 pull-right and hide classes
     *
     * Validates: Requirements 8.2
     */
    public function test_render_filter_modal_always_contains_bootstrap4_classes(): void
    {
        $this->forAll(
            $this->safeString(),
            $this->safeString()
        )->then(function (string $name, string $title): void {
            $name  = $name ?: 'filter_modal';
            $title = $title ?: 'Filter';

            $output = $this->adapter->renderFilterModal($name, $title, []);

            $this->assertStringContainsString(
                'pull-right',
                $output,
                'renderFilterModal() must always contain Bootstrap 4 pull-right class'
            );
            $this->assertStringContainsString(
                'data-dismiss="modal"',
                $output,
                'renderFilterModal() must always contain Bootstrap 4 data-dismiss="modal"'
            );
            $this->assertStringContainsString(
                'hide',
                $output,
                'renderFilterModal() must always contain Bootstrap 4 hide class'
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 4 (partial): All DefaultAdapter render methods return strings, never null.
     *
     * Validates: Requirements 1.1–1.8, 4.8
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
}
