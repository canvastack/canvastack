<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\HashGenerator;
use Canvastack\Canvastack\Components\Table\Renderers\TanStackRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

/**
 * Test for TanStack script loading check.
 * 
 * Validates: Requirement 8.4 - THE TableBuilder SHALL ensure TanStack Table JavaScript is loaded before initialization
 */
class TanStackScriptLoadingCheckTest extends TestCase
{
    private TanStackRenderer $renderer;
    private TableBuilder $table;
    private ThemeLocaleIntegration $themeLocaleIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeLocaleIntegration = Mockery::mock(ThemeLocaleIntegration::class);
        $this->themeLocaleIntegration->shouldReceive('getLocalizedThemeCss')->andReturn('');
        $this->themeLocaleIntegration->shouldReceive('getHtmlAttributes')->andReturn([
            'lang' => 'en',
            'dir' => 'ltr',
            'class' => '',
        ]);

        $this->renderer = new TanStackRenderer($this->themeLocaleIntegration);

        // Create a mock TableBuilder
        $this->table = Mockery::mock(TableBuilder::class);
        $this->table->shouldReceive('getUniqueId')->andReturn('canvastable_abc123def456');
        $this->table->shouldReceive('getModel')->andReturn(null);
        $this->table->shouldReceive('getConfiguration')->andReturn((object)['searchableColumns' => []]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that renderScripts includes TanStack library check.
     * 
     * Validates: Requirement 8.4
     */
    public function test_render_scripts_includes_tanstack_library_check(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
            ['id' => 'email', 'header' => 'Email', 'accessorKey' => 'email'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Verify TanStack library check is present
        $this->assertStringContainsString(
            'typeof window.TanStackTable',
            $scripts,
            'Scripts should check for TanStackTable global'
        );

        $this->assertStringContainsString(
            'typeof window.tanstackTable',
            $scripts,
            'Scripts should check for tanstackTable global (alternative)'
        );

        // Verify error message for missing library
        $this->assertStringContainsString(
            'TanStack Table library not loaded',
            $scripts,
            'Scripts should include error message for missing library'
        );

        $this->assertStringContainsString(
            'Please ensure TanStack Table JavaScript is included',
            $scripts,
            'Scripts should include helpful error message'
        );
    }

    /**
     * Test that TanStack check happens before Alpine registration.
     * 
     * Validates: Requirement 8.4
     */
    public function test_tanstack_check_happens_before_alpine_registration(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Find position of TanStack check
        $tanstackCheckPos = strpos($scripts, 'typeof window.TanStackTable');
        $registerComponentPos = strpos($scripts, 'registerComponent()');

        $this->assertNotFalse($tanstackCheckPos, 'TanStack check should be present');
        $this->assertNotFalse($registerComponentPos, 'registerComponent call should be present');
        $this->assertLessThan(
            $registerComponentPos,
            $tanstackCheckPos,
            'TanStack check should happen before registerComponent call'
        );
    }

    /**
     * Test that error message is shown in table container when TanStack is missing.
     * 
     * Validates: Requirement 8.4 - Handle missing dependency gracefully
     */
    public function test_error_message_shown_when_tanstack_missing(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Verify error UI is included
        $this->assertStringContainsString(
            'alert alert-error',
            $scripts,
            'Scripts should include error alert UI'
        );

        $this->assertStringContainsString(
            'TanStack Table Error:',
            $scripts,
            'Scripts should include error title'
        );

        $this->assertStringContainsString(
            'tableContainer.innerHTML',
            $scripts,
            'Scripts should set innerHTML to show error'
        );
    }

    /**
     * Test that initialization stops when TanStack is missing.
     * 
     * Validates: Requirement 8.4 - Handle missing dependency gracefully
     */
    public function test_initialization_stops_when_tanstack_missing(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Verify return statement after error
        $this->assertStringContainsString(
            'return; // Stop initialization',
            $scripts,
            'Scripts should stop initialization when TanStack is missing'
        );

        // Verify error is logged
        $this->assertStringContainsString(
            'console.error',
            $scripts,
            'Scripts should log error to console'
        );
    }

    /**
     * Test that TanStack check happens in both Alpine loaded and not loaded paths.
     * 
     * Validates: Requirement 8.4
     */
    public function test_tanstack_check_in_both_alpine_paths(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Count occurrences of TanStack check
        $checkCount = substr_count($scripts, 'typeof window.TanStackTable');

        $this->assertGreaterThanOrEqual(
            2,
            $checkCount,
            'TanStack check should appear in both Alpine loaded and not loaded paths'
        );
    }

    /**
     * Test that success message is logged when TanStack is verified.
     * 
     * Validates: Requirement 8.4
     */
    public function test_success_message_logged_when_tanstack_verified(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Verify success log message
        $this->assertStringContainsString(
            'TanStack Table library verified',
            $scripts,
            'Scripts should log success message when TanStack is verified'
        );
    }

    /**
     * Test that error message includes instance ID for debugging.
     * 
     * Validates: Requirement 8.4
     */
    public function test_error_message_includes_instance_id(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Verify error message includes instance ID
        $this->assertStringContainsString(
            'for instance',
            $scripts,
            'Error message should include instance ID for debugging'
        );

        $this->assertStringContainsString(
            'tableId',
            $scripts,
            'Error message should reference tableId variable'
        );
    }

    /**
     * Test that check supports both TanStackTable and tanstackTable globals.
     * 
     * Validates: Requirement 8.4 - Support different naming conventions
     */
    public function test_check_supports_both_naming_conventions(): void
    {
        $config = [
            'serverSide' => ['enabled' => false],
            'columnPinning' => ['left' => [], 'right' => []],
        ];

        $columns = [
            ['id' => 'name', 'header' => 'Name', 'accessorKey' => 'name'],
        ];

        $alpineData = [
            'data' => [],
            'pageSize' => 10,
            'totalRows' => 0,
        ];

        $scripts = $this->renderer->renderScripts($this->table, $config, $columns, $alpineData);

        // Verify both naming conventions are checked
        $this->assertStringContainsString(
            'window.TanStackTable',
            $scripts,
            'Scripts should check for TanStackTable (PascalCase)'
        );

        $this->assertStringContainsString(
            'window.tanstackTable',
            $scripts,
            'Scripts should check for tanstackTable (camelCase)'
        );

        // Verify they are checked with OR logic
        $this->assertStringContainsString(
            'typeof window.TanStackTable === \'undefined\' && typeof window.tanstackTable === \'undefined\'',
            $scripts,
            'Scripts should use OR logic to check both naming conventions'
        );
    }
}
