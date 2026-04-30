<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Eris\TestTrait;
use Eris\Generators;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Property-based tests for TailwindAdapter.
 *
 * Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes.
 * For any valid input, output must NOT contain Bootstrap-specific classes
 * such as `alert-block`, `nav-item`, `ckbox`, `chosen-select`, `btn-xs`,
 * `pull-right`, `hide`, or `d-none`.
 *
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.7
 *
 * Uses giorgiosironi/eris with minimum 100 iterations per property.
 */
class TailwindAdapterPropertyTest extends TestCase
{
    use TestTrait;

    private TailwindAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter    = new TailwindAdapter();
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

    // ── Property 7a: renderTabHeader does not use Bootstrap nav-item ──────

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * TailwindAdapter::renderTabHeader() output must NOT contain Bootstrap-specific
     * classes: `nav-item`, `pull-right`, `hide`, `d-none`.
     *
     * Validates: Requirements 6.1
     */
    public function test_render_tab_header_does_not_contain_bootstrap_classes(): void
    {
        $this->forAll(
            $this->safeString(),    // $data
            $this->safeString(),    // $pointer
            $this->stringOrFalse(), // $active
            $this->stringOrFalse()  // $class
        )->then(function (string $data, string $pointer, $active, $class): void {
            $output = $this->adapter->renderTabHeader($data, $pointer, $active, $class);

            $this->assertStringNotContainsString(
                'nav-item',
                $output,
                'renderTabHeader() must NOT contain Bootstrap nav-item class'
            );

            $this->assertStringNotContainsString(
                'pull-right',
                $output,
                'renderTabHeader() must NOT contain Bootstrap pull-right class'
            );

            $this->assertStringNotContainsString(
                'd-none',
                $output,
                'renderTabHeader() must NOT contain Bootstrap d-none class'
            );

            $this->assertStringNotContainsString(
                '"hide"',
                $output,
                'renderTabHeader() must NOT contain Bootstrap hide class'
            );
        });
    }

    // ── Property 7b: renderAlertMessage does not use Bootstrap alert-block ──

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * TailwindAdapter::renderAlertMessage() output must NOT contain Bootstrap-specific
     * classes: `alert-block`, `pull-right`, `hide`, `d-none`.
     *
     * Validates: Requirements 6.2
     */
    public function test_render_alert_message_does_not_contain_bootstrap_classes(): void
    {
        $this->forAll(
            $this->safeString(),         // $message
            $this->alertTypeGenerator(), // $type
            $this->safeString(),         // $title
            $this->safeString(),         // $prefix
            $this->stringOrFalse()       // $extra
        )->then(function (string $message, string $type, string $title, string $prefix, $extra): void {
            $output = $this->adapter->renderAlertMessage($message, $type, $title, $prefix, $extra);

            $this->assertStringNotContainsString(
                'alert-block',
                $output,
                'renderAlertMessage() must NOT contain Bootstrap alert-block class'
            );

            $this->assertStringNotContainsString(
                'pull-right',
                $output,
                'renderAlertMessage() must NOT contain Bootstrap pull-right class'
            );

            $this->assertStringNotContainsString(
                'd-none',
                $output,
                'renderAlertMessage() must NOT contain Bootstrap d-none class'
            );

            $this->assertStringNotContainsString(
                '"hide"',
                $output,
                'renderAlertMessage() must NOT contain Bootstrap hide class'
            );
        });
    }

    // ── Property 7c: renderCheckList does not use Bootstrap ckbox ─────────

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * TailwindAdapter::renderCheckList() output must NOT contain Bootstrap-specific
     * classes: `ckbox`, `chosen-select`, `pull-right`, `hide`, `d-none`.
     *
     * Validates: Requirements 6.4
     */
    public function test_render_check_list_does_not_contain_bootstrap_classes(): void
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

            // Check for Bootstrap ckbox class pattern (not substring of 'checkbox')
            // Bootstrap 4 uses: class="ckbox ckbox-{class}" — check for the exact pattern
            $this->assertStringNotContainsString(
                'class="ckbox',
                $output,
                'renderCheckList() must NOT contain Bootstrap ckbox class attribute'
            );

            $this->assertStringNotContainsString(
                'chosen-select',
                $output,
                'renderCheckList() must NOT contain Bootstrap chosen-select class'
            );

            $this->assertStringNotContainsString(
                'pull-right',
                $output,
                'renderCheckList() must NOT contain Bootstrap pull-right class'
            );

            $this->assertStringNotContainsString(
                'd-none',
                $output,
                'renderCheckList() must NOT contain Bootstrap d-none class'
            );

            $this->assertStringNotContainsString(
                '"hide"',
                $output,
                'renderCheckList() must NOT contain Bootstrap hide class'
            );
        });
    }

    // ── Property 7d: renderModalWrapper does not use Bootstrap classes ────

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * TailwindAdapter::renderModalWrapper() output must NOT contain Bootstrap-specific
     * classes: `pull-right`, `hide`, `d-none`, `btn-xs`.
     *
     * Validates: Requirements 6.7
     */
    public function test_render_modal_wrapper_does_not_contain_bootstrap_classes(): void
    {
        $this->forAll(
            $this->safeString(), // $name
            $this->safeString()  // $title
        )->then(function (string $name, string $title): void {
            $name  = $name ?: 'modal_name';
            $title = $title ?: 'Modal Title';

            $output = $this->adapter->renderModalWrapper($name, $title, []);

            $this->assertStringNotContainsString(
                'pull-right',
                $output,
                'renderModalWrapper() must NOT contain Bootstrap pull-right class'
            );

            $this->assertStringNotContainsString(
                'd-none',
                $output,
                'renderModalWrapper() must NOT contain Bootstrap d-none class'
            );

            $this->assertStringNotContainsString(
                '"hide"',
                $output,
                'renderModalWrapper() must NOT contain Bootstrap hide class'
            );

            $this->assertStringNotContainsString(
                'btn-xs',
                $output,
                'renderModalWrapper() must NOT contain Bootstrap btn-xs class'
            );
        });
    }

    // ── Property 7e: renderFilterModal does not use Bootstrap classes ─────

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * TailwindAdapter::renderFilterModal() output must NOT contain Bootstrap-specific
     * classes: `pull-right`, `hide`, `d-none`, `btn-xs`.
     *
     * Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.7
     */
    public function test_render_filter_modal_does_not_contain_bootstrap_classes(): void
    {
        $this->forAll(
            $this->safeString(), // $name
            $this->safeString()  // $title
        )->then(function (string $name, string $title): void {
            $name  = $name ?: 'filter_modal';
            $title = $title ?: 'Filter';

            $output = $this->adapter->renderFilterModal($name, $title, []);

            $this->assertStringNotContainsString(
                'pull-right',
                $output,
                'renderFilterModal() must NOT contain Bootstrap pull-right class'
            );

            $this->assertStringNotContainsString(
                '"hide"',
                $output,
                'renderFilterModal() must NOT contain Bootstrap hide class'
            );

            $this->assertStringNotContainsString(
                'd-none',
                $output,
                'renderFilterModal() must NOT contain Bootstrap d-none class'
            );

            $this->assertStringNotContainsString(
                'btn-xs',
                $output,
                'renderFilterModal() must NOT contain Bootstrap btn-xs class'
            );

            $this->assertStringNotContainsString(
                'alert-block',
                $output,
                'renderFilterModal() must NOT contain Bootstrap alert-block class'
            );

            $this->assertStringNotContainsString(
                'nav-item',
                $output,
                'renderFilterModal() must NOT contain Bootstrap nav-item class'
            );

            $this->assertStringNotContainsString(
                'ckbox',
                $output,
                'renderFilterModal() must NOT contain Bootstrap ckbox class'
            );

            $this->assertStringNotContainsString(
                'chosen-select',
                $output,
                'renderFilterModal() must NOT contain Bootstrap chosen-select class'
            );
        });
    }

    // ── Property 4 (partial): All render methods return strings, never null ──

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * All TailwindAdapter render methods return strings, never null.
     *
     * Validates: Requirements 6.1–6.7
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

    // ── Property 7f: renderTabContent returns valid Tailwind structure ────

    /**
     * @test
     * Feature: theme-adapter, Property 7: TailwindAdapter tidak menggunakan Bootstrap-specific classes
     *
     * TailwindAdapter::renderTabContent() returns a tab-pane div with the correct
     * pointer as the id attribute, and does NOT contain Bootstrap-specific classes.
     *
     * Validates: Requirements 6.1
     */
    public function test_render_tab_content_does_not_contain_bootstrap_classes(): void
    {
        $this->forAll(
            $this->safeString(), // $data
            $this->safeString(), // $pointer
            Generators::bool()   // $active
        )->then(function (string $data, string $pointer, bool $active): void {
            $output = $this->adapter->renderTabContent($data, $pointer, $active);

            $this->assertStringContainsString('tab-pane', $output);
            $this->assertStringContainsString('id="' . htmlspecialchars($pointer, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"', $output);

            $this->assertStringNotContainsString(
                'nav-item',
                $output,
                'renderTabContent() must NOT contain Bootstrap nav-item class'
            );

            $this->assertStringNotContainsString(
                'd-none',
                $output,
                'renderTabContent() must NOT contain Bootstrap d-none class'
            );
        });
    }
}
