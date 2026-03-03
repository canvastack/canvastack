<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Table\Renderers\PublicRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * JavaScript Generation Tests.
 *
 * Tests for Task 21.16: Write unit tests for JavaScript generation
 *
 * Test Coverage:
 * - renderDataTablesScript() generates valid JavaScript
 * - Server-side configuration includes ajax settings
 * - Client-side configuration excludes ajax settings
 * - POST method includes CSRF token header
 * - GET method excludes CSRF token header
 * - Columns configuration is correct
 * - Script is wrapped in DOMContentLoaded
 */
class JavaScriptGenerationTest extends TestCase
{
    /**
     * Test renderDataTablesScript() generates valid JavaScript for server-side.
     *
     * @test
     */
    public function test_render_datatables_script_generates_valid_javascript_for_server_side(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/api/users/data',
            'columns' => ['id', 'name', 'email'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert script contains basic structure
        $this->assertStringContainsString('<script>', $script);
        $this->assertStringContainsString('</script>', $script);
        $this->assertStringContainsString('DOMContentLoaded', $script);
        $this->assertStringContainsString('DataTable', $script);
    }

    /**
     * Test server-side configuration includes ajax settings.
     *
     * @test
     */
    public function test_server_side_configuration_includes_ajax_settings(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'products',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name', 'price'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert AJAX configuration is present
        $this->assertStringContainsString('ajax:', $script);
        $this->assertStringContainsString('url:', $script);
        $this->assertStringContainsString('/datatable/data', $script);
        $this->assertStringContainsString('type:', $script);
        $this->assertStringContainsString('serverSide: true', $script);
    }

    /**
     * Test client-side configuration excludes ajax settings.
     *
     * @test
     */
    public function test_client_side_configuration_excludes_ajax_settings(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'categories',
            'serverSide' => false,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert AJAX configuration is NOT present
        $this->assertStringNotContainsString('ajax:', $script);
        $this->assertStringContainsString('serverSide: false', $script);
    }

    /**
     * Test POST method includes CSRF token header.
     *
     * @test
     */
    public function test_post_method_includes_csrf_token_header(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'orders',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/api/orders',
            'columns' => ['id', 'customer', 'total'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert CSRF token is included
        $this->assertStringContainsString('X-CSRF-TOKEN', $script);
        $this->assertStringContainsString('headers:', $script);
        $this->assertStringContainsString('type: \'POST\'', $script);
    }

    /**
     * Test GET method excludes CSRF token header.
     *
     * @test
     */
    public function test_get_method_excludes_csrf_token_header(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'items',
            'serverSide' => true,
            'httpMethod' => 'GET',
            'ajaxUrl' => '/api/items',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert CSRF token is NOT included for GET
        $this->assertStringNotContainsString('X-CSRF-TOKEN', $script);
        $this->assertStringContainsString('type: \'GET\'', $script);
    }

    /**
     * Test columns configuration is correct.
     *
     * @test
     */
    public function test_columns_configuration_is_correct(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name', 'email', 'created_at'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert columns are configured
        $this->assertStringContainsString('columns:', $script);
        $this->assertStringContainsString('"data":"id"', $script);
        $this->assertStringContainsString('"data":"name"', $script);
        $this->assertStringContainsString('"data":"email"', $script);
        $this->assertStringContainsString('"data":"created_at"', $script);
    }

    /**
     * Test script is wrapped in DOMContentLoaded.
     *
     * @test
     */
    public function test_script_is_wrapped_in_dom_content_loaded(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'posts',
            'serverSide' => false,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'title'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert DOMContentLoaded wrapper
        $this->assertStringContainsString('document.addEventListener(\'DOMContentLoaded\'', $script);
        $this->assertStringContainsString('function()', $script);
    }

    /**
     * Test DataTables configuration includes responsive option.
     *
     * @test
     */
    public function test_datatables_configuration_includes_responsive_option(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert responsive configuration
        $this->assertStringContainsString('responsive: true', $script);
    }

    /**
     * Test DataTables configuration includes processing option for server-side.
     *
     * @test
     */
    public function test_datatables_configuration_includes_processing_for_server_side(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert processing is enabled for server-side
        $this->assertStringContainsString('processing: true', $script);
    }

    /**
     * Test DataTables configuration excludes processing for client-side.
     *
     * @test
     */
    public function test_datatables_configuration_excludes_processing_for_client_side(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => false,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert processing is disabled for client-side
        $this->assertStringContainsString('processing: false', $script);
    }

    /**
     * Test PublicRenderer generates valid JavaScript (different from AdminRenderer by design).
     *
     * AdminRenderer and PublicRenderer generate different JavaScript:
     * - AdminRenderer: Uses tableId, has action buttons, complex filter handling
     * - PublicRenderer: Uses tableName, has export buttons, simpler configuration
     *
     * @test
     */
    public function test_public_renderer_generates_valid_javascript(): void
    {
        $adminRenderer = new AdminRenderer();
        $publicRenderer = new PublicRenderer();

        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name', 'email'],
        ];

        $adminScript = $this->callProtectedMethod($adminRenderer, 'renderDataTablesScript', [$config]);
        $publicScript = $this->callProtectedMethod($publicRenderer, 'renderDataTablesScript', [$config]);

        // Both should generate valid JavaScript with common elements
        $this->assertStringContainsString('<script>', $adminScript);
        $this->assertStringContainsString('DataTable', $adminScript);
        $this->assertStringContainsString('serverSide: true', $adminScript);
        
        $this->assertStringContainsString('<script>', $publicScript);
        $this->assertStringContainsString('DataTable', $publicScript);
        $this->assertStringContainsString('serverSide: true', $publicScript);
        
        // But they should have different features
        // AdminRenderer has action buttons and rowCallback
        $this->assertStringContainsString('rowCallback', $adminScript);
        $this->assertStringContainsString('data-lucide="edit"', $adminScript);
        
        // PublicRenderer has export buttons
        $this->assertStringContainsString('buttons:', $publicScript);
        $this->assertStringContainsString('extend: \'csv\'', $publicScript);
    }

    /**
     * Test script includes error handling for AJAX failures.
     *
     * @test
     */
    public function test_script_includes_error_handling_for_ajax_failures(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert error handling is present
        $this->assertStringContainsString('error:', $script);
        $this->assertStringContainsString('function(xhr, error, code)', $script);
    }

    /**
     * Test script includes custom filter data callback.
     *
     * @test
     */
    public function test_script_includes_custom_filter_data_callback(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => true,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert data callback for custom filters
        $this->assertStringContainsString('data: function(d)', $script);
        $this->assertStringContainsString('d.filters', $script);
        $this->assertStringContainsString('window.tableFilters', $script);
    }

    /**
     * Test script stores table instance globally.
     *
     * @test
     */
    public function test_script_stores_table_instance_globally(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => false,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert global table instance
        $this->assertStringContainsString('window.dataTable', $script);
    }

    /**
     * Test script initializes Lucide icons.
     *
     * @test
     */
    public function test_script_initializes_lucide_icons(): void
    {
        $renderer = new AdminRenderer();
        $config = [
            'tableName' => 'users',
            'serverSide' => false,
            'httpMethod' => 'POST',
            'ajaxUrl' => '/datatable/data',
            'columns' => ['id', 'name'],
        ];

        $script = $this->callProtectedMethod($renderer, 'renderDataTablesScript', [$config]);

        // Assert Lucide icons initialization
        $this->assertStringContainsString('lucide', $script);
        $this->assertStringContainsString('createIcons', $script);
    }

    /**
     * Helper method to call protected methods.
     */
    protected function callProtectedMethod($object, string $method, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
