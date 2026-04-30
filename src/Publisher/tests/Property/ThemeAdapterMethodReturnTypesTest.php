<?php

namespace Tests\Property;

use Tests\TestCase;
use Eris\TestTrait;
use Eris\Generators;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Property-based tests for adapter method return types.
 *
 * Property 4: Semua adapter method tidak pernah return null
 *
 * For any adapter implementing ThemeAdapterInterface and for any valid input
 * to each method, every method SHALL return a string (never null, never throw
 * exception for valid input).
 *
 * Requirements: 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
 *
 * Uses giorgiosironi/eris with minimum 100 iterations per property.
 */
class ThemeAdapterMethodReturnTypesTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Set minimum 100 iterations as per design document
        $this->iterations = 100;
    }

    // ── Generators ────────────────────────────────────────────────────────

    /**
     * Generator for safe alphanumeric strings.
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
     * Generator for string|false values.
     */
    private function stringOrFalse(): \Eris\Generator
    {
        return Generators::oneOf(
            Generators::constant(false),
            $this->safeString()
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
     * Generator for CSS class strings.
     */
    private function cssClassGenerator(): \Eris\Generator
    {
        return Generators::elements('success', 'danger', 'warning', 'info', 'primary');
    }

    /**
     * Generator for column numbers (1-12 for grid systems).
     */
    private function columnNumberGenerator(): \Eris\Generator
    {
        return Generators::choose(1, 12);
    }

    // ── Property 4: All adapter methods never return null ────────────────

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test all utility methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_all_utility_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),
                $this->safeString()
            )->then(function ($name, $title) use ($adapter, $adapterName): void {
                // Test all utility methods that don't require parameters
                $this->assertIsString(
                    $adapter->getSelectBoxClass(),
                    "{$adapterName}::getSelectBoxClass() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getDataToggleAttribute(),
                    "{$adapterName}::getDataToggleAttribute() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getDismissAttribute(),
                    "{$adapterName}::getDismissAttribute() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getHideClass(),
                    "{$adapterName}::getHideClass() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getFloatRightClass(),
                    "{$adapterName}::getFloatRightClass() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getFloatLeftClass(),
                    "{$adapterName}::getFloatLeftClass() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getTableClass(),
                    "{$adapterName}::getTableClass() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getContainerClass(),
                    "{$adapterName}::getContainerClass() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->getRowClass(),
                    "{$adapterName}::getRowClass() must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test grid column methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_grid_column_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->columnNumberGenerator()
            )->then(function (int $columns) use ($adapter, $adapterName): void {
                $this->assertIsString(
                    $adapter->getColumnClass($columns),
                    "{$adapterName}::getColumnClass({$columns}) must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test all form rendering methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_all_form_rendering_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),    // $data
                $this->safeString(),    // $pointer
                $this->stringOrFalse(), // $active
                $this->stringOrFalse(), // $class
                Generators::bool()      // $checked
            )->then(function ($data, $pointer, $active, $class, $checked) use ($adapter, $adapterName): void {
                // Ensure non-empty strings for required parameters
                $data = $data ?: 'data';
                $pointer = $pointer ?: 'pointer';
                
                // Test renderTabHeader
                $this->assertIsString(
                    $adapter->renderTabHeader($data, $pointer, $active, $class),
                    "{$adapterName}::renderTabHeader() must return string, not null"
                );
                
                // Test renderTabContent
                $this->assertIsString(
                    $adapter->renderTabContent($data, $pointer, $checked),
                    "{$adapterName}::renderTabContent() must return string, not null"
                );
                
                // Test renderCheckList
                $this->assertIsString(
                    $adapter->renderCheckList($data, $active, $class, $checked, 'success', false, null),
                    "{$adapterName}::renderCheckList() must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test alert and modal rendering methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_alert_and_modal_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),         // $message
                $this->alertTypeGenerator(), // $type
                $this->safeString(),         // $title
                $this->safeString(),         // $prefix
                $this->stringOrFalse()       // $extra
            )->then(function ($message, $type, $title, $prefix, $extra) use ($adapter, $adapterName): void {
                // Ensure non-empty strings
                $message = $message ?: 'message';
                $title = $title ?: 'title';
                $prefix = $prefix ?: 'fa-check';
                
                // Test renderAlertMessage
                $this->assertIsString(
                    $adapter->renderAlertMessage($message, $type, $title, $prefix, $extra),
                    "{$adapterName}::renderAlertMessage() must return string, not null"
                );
                
                // Test renderModalWrapper
                $this->assertIsString(
                    $adapter->renderModalWrapper($message, $title, []),
                    "{$adapterName}::renderModalWrapper() must return string, not null"
                );
                
                // Test renderFilterModal
                $this->assertIsString(
                    $adapter->renderFilterModal($message, $title, []),
                    "{$adapterName}::renderFilterModal() must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test selectbox rendering methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_selectbox_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),
                Generators::elements(
                    ['option1' => 'Option 1', 'option2' => 'Option 2'],
                    ['a' => 'Alpha', 'b' => 'Beta'],
                    ['1' => 'One', '2' => 'Two']
                ),
                $this->stringOrFalse()
            )->then(function ($name, $values, $selected) use ($adapter, $adapterName): void {
                $name = $name ?: 'select_name';
                
                $this->assertIsString(
                    $adapter->renderSelectBox($name, $values, $selected, [], true, [null => 'Select']),
                    "{$adapterName}::renderSelectBox() must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test wrapper rendering methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_wrapper_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),
                $this->safeString(),
                $this->safeString()
            )->then(function ($html1, $html2, $type) use ($adapter, $adapterName): void {
                $html1 = $html1 ?: '<div>content1</div>';
                $html2 = $html2 ?: '<div>content2</div>';
                $type = $type ?: 'default';
                
                // Test renderCheckboxWrapper
                $this->assertIsString(
                    $adapter->renderCheckboxWrapper($type, $html1, $html2),
                    "{$adapterName}::renderCheckboxWrapper() must return string, not null"
                );
                
                // Test renderTabWrapper
                $this->assertIsString(
                    $adapter->renderTabWrapper($html1, $html2),
                    "{$adapterName}::renderTabWrapper() must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test breadcrumb and sidebar rendering methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_breadcrumb_and_sidebar_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),
                $this->stringOrFalse(),
                $this->stringOrFalse(),
                Generators::bool()
            )->then(function ($title, $icon, $heading, $type) use ($adapter, $adapterName): void {
                $title = $title ?: 'Title';
                
                // Test renderBreadcrumb
                // $links should be associative array: ['link_title' => 'link_url']
                $links = ['home' => '/home', 'dashboard' => '/dashboard'];
                // $iconLinks should be array|false (array of icon names)
                $iconLinks = $icon !== false ? ['home', 'dashboard'] : false;
                
                $this->assertIsString(
                    $adapter->renderBreadcrumb($title, $links, $icon, $iconLinks, false),
                    "{$adapterName}::renderBreadcrumb() must return string, not null"
                );
                
                // Test renderSidebarContent
                $this->assertIsString(
                    $adapter->renderSidebarContent($title, $heading, $icon, $type),
                    "{$adapterName}::renderSidebarContent() must return string, not null"
                );
            });
        }
    }

    /**
     * @test
     * Feature: theme-adapter, Property 4: Semua adapter method tidak pernah return null
     *
     * Test action button rendering methods on all adapters return string (never null).
     *
     * Validates: Requirements 1.1–1.8, 2.1–2.6, 4.8, 5.1–5.8, 6.1–6.7
     */
    public function test_action_button_methods_never_return_null(): void
    {
        $adapters = [
            'DefaultAdapter' => new DefaultAdapter(),
            'Bootstrap5Adapter' => new Bootstrap5Adapter(),
            'TailwindAdapter' => new TailwindAdapter(),
        ];

        foreach ($adapters as $adapterName => $adapter) {
            $this->forAll(
                $this->safeString(),
                $this->safeString()
            )->then(function ($field, $url) use ($adapter, $adapterName): void {
                $field = $field ?: 'id';
                $url = $url ?: '/test';
                
                // Create a simple row data object
                $rowData = (object) [$field => 1, 'name' => 'Test'];
                
                // Test renderActionButtons with various action configurations
                $this->assertIsString(
                    $adapter->renderActionButtons($rowData, $field, $url, 'edit', null),
                    "{$adapterName}::renderActionButtons() must return string, not null"
                );
                
                $this->assertIsString(
                    $adapter->renderActionButtons($rowData, $field, $url, ['edit', 'delete'], null),
                    "{$adapterName}::renderActionButtons() with array action must return string, not null"
                );
            });
        }
    }
}
