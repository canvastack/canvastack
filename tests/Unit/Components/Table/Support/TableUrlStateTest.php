<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\TableUrlState;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Test for TableUrlState.
 *
 * Tests URL state management functionality including:
 * - toUrl() method
 * - fromUrl() method
 * - URL parameter parsing
 * - Sort state encoding/decoding
 * - Filter state encoding/decoding
 * - Page state encoding/decoding
 * - Search state encoding/decoding
 * - Hidden columns encoding/decoding
 * - Active tab encoding/decoding
 * - Complex value encoding/decoding
 * - URL generation
 * - URL parameter removal
 * - State merging
 *
 * @covers \Canvastack\Canvastack\Components\Table\Support\TableUrlState
 */
class TableUrlStateTest extends TestCase
{
    /**
     * Table URL state instance.
     *
     * @var TableUrlState
     */
    protected TableUrlState $urlState;

    /**
     * Test table ID.
     *
     * @var string
     */
    protected string $tableId = 'test_table';

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create URL state instance
        $this->urlState = new TableUrlState($this->tableId);
    }

    /**
     * Test that URL state can be instantiated.
     *
     * @return void
     */
    public function test_url_state_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TableUrlState::class, $this->urlState);
        $this->assertEquals($this->tableId, $this->urlState->getTableId());
    }

    /**
     * Test that getPrefix() returns correct prefix.
     *
     * @return void
     */
    public function test_get_prefix_returns_correct_prefix(): void
    {
        $prefix = $this->urlState->getPrefix();
        
        $this->assertIsString($prefix);
        $this->assertEquals('table_' . $this->tableId . '_', $prefix);
    }

    /**
     * Test that toUrl() converts empty state to empty array.
     *
     * @return void
     */
    public function test_to_url_converts_empty_state_to_empty_array(): void
    {
        // Arrange
        $state = [];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertIsArray($params);
        $this->assertEmpty($params);
    }

    /**
     * Test that toUrl() converts sort state to URL parameters.
     *
     * @return void
     */
    public function test_to_url_converts_sort_state_to_url_parameters(): void
    {
        // Arrange
        $state = [
            'sort' => [
                'column' => 'name',
                'direction' => 'asc',
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_sort', $params);
        $this->assertArrayHasKey('table_test_table_order', $params);
        $this->assertEquals('name', $params['table_test_table_sort']);
        $this->assertEquals('asc', $params['table_test_table_order']);
    }

    /**
     * Test that toUrl() defaults sort direction to asc.
     *
     * @return void
     */
    public function test_to_url_defaults_sort_direction_to_asc(): void
    {
        // Arrange
        $state = [
            'sort' => [
                'column' => 'name',
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertEquals('asc', $params['table_test_table_order']);
    }

    /**
     * Test that toUrl() converts filter state to URL parameters.
     *
     * @return void
     */
    public function test_to_url_converts_filter_state_to_url_parameters(): void
    {
        // Arrange
        $state = [
            'filters' => [
                'status' => 'active',
                'role' => 'admin',
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_filter_status', $params);
        $this->assertArrayHasKey('table_test_table_filter_role', $params);
        $this->assertEquals('active', $params['table_test_table_filter_status']);
        $this->assertEquals('admin', $params['table_test_table_filter_role']);
    }

    /**
     * Test that toUrl() skips empty filter values.
     *
     * @return void
     */
    public function test_to_url_skips_empty_filter_values(): void
    {
        // Arrange
        $state = [
            'filters' => [
                'status' => 'active',
                'role' => '',
                'name' => null,
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_filter_status', $params);
        $this->assertArrayNotHasKey('table_test_table_filter_role', $params);
        $this->assertArrayNotHasKey('table_test_table_filter_name', $params);
    }

    /**
     * Test that toUrl() encodes array filter values.
     *
     * @return void
     */
    public function test_to_url_encodes_array_filter_values(): void
    {
        // Arrange
        $state = [
            'filters' => [
                'tags' => ['php', 'laravel', 'testing'],
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_filter_tags', $params);
        $encoded = $params['table_test_table_filter_tags'];
        
        // Verify it's base64 encoded JSON
        $decoded = base64_decode($encoded, true);
        $this->assertNotFalse($decoded);
        
        $json = json_decode($decoded, true);
        $this->assertEquals(['php', 'laravel', 'testing'], $json);
    }

    /**
     * Test that toUrl() converts page number to URL parameter.
     *
     * @return void
     */
    public function test_to_url_converts_page_number_to_url_parameter(): void
    {
        // Arrange
        $state = [
            'current_page' => 5,
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_page', $params);
        $this->assertEquals('5', $params['table_test_table_page']);
    }

    /**
     * Test that toUrl() skips page 1 (default page).
     *
     * @return void
     */
    public function test_to_url_skips_page_one(): void
    {
        // Arrange
        $state = [
            'current_page' => 1,
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayNotHasKey('table_test_table_page', $params);
    }

    /**
     * Test that toUrl() converts page size to URL parameter.
     *
     * @return void
     */
    public function test_to_url_converts_page_size_to_url_parameter(): void
    {
        // Arrange
        $state = [
            'page_size' => 50,
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_per_page', $params);
        $this->assertEquals('50', $params['table_test_table_per_page']);
    }

    /**
     * Test that toUrl() converts search value to URL parameter.
     *
     * @return void
     */
    public function test_to_url_converts_search_value_to_url_parameter(): void
    {
        // Arrange
        $state = [
            'search' => 'test search',
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_search', $params);
        $this->assertEquals('test search', $params['table_test_table_search']);
    }

    /**
     * Test that toUrl() skips empty search value.
     *
     * @return void
     */
    public function test_to_url_skips_empty_search_value(): void
    {
        // Arrange
        $state = [
            'search' => '',
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayNotHasKey('table_test_table_search', $params);
    }

    /**
     * Test that toUrl() converts hidden columns to URL parameter.
     *
     * @return void
     */
    public function test_to_url_converts_hidden_columns_to_url_parameter(): void
    {
        // Arrange
        $state = [
            'hidden_columns' => ['id', 'password', 'created_at'],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_hidden', $params);
        $this->assertEquals('id,password,created_at', $params['table_test_table_hidden']);
    }

    /**
     * Test that toUrl() converts active tab to URL parameter.
     *
     * @return void
     */
    public function test_to_url_converts_active_tab_to_url_parameter(): void
    {
        // Arrange
        $state = [
            'active_tab' => 'settings',
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_tab', $params);
        $this->assertEquals('settings', $params['table_test_table_tab']);
    }

    /**
     * Test that toUrl() skips empty active tab.
     *
     * @return void
     */
    public function test_to_url_skips_empty_active_tab(): void
    {
        // Arrange
        $state = [
            'active_tab' => '',
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayNotHasKey('table_test_table_tab', $params);
    }

    /**
     * Test that toUrl() converts complex state with all properties.
     *
     * @return void
     */
    public function test_to_url_converts_complex_state_with_all_properties(): void
    {
        // Arrange
        $state = [
            'sort' => ['column' => 'name', 'direction' => 'desc'],
            'filters' => ['status' => 'active', 'role' => 'admin'],
            'current_page' => 3,
            'page_size' => 50,
            'search' => 'test',
            'hidden_columns' => ['id', 'password'],
            'active_tab' => 'users',
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertCount(9, $params); // 2 sort + 2 filters + page + per_page + search + hidden + tab
        $this->assertEquals('name', $params['table_test_table_sort']);
        $this->assertEquals('desc', $params['table_test_table_order']);
        $this->assertEquals('active', $params['table_test_table_filter_status']);
        $this->assertEquals('admin', $params['table_test_table_filter_role']);
        $this->assertEquals('3', $params['table_test_table_page']);
        $this->assertEquals('50', $params['table_test_table_per_page']);
        $this->assertEquals('test', $params['table_test_table_search']);
        $this->assertEquals('id,password', $params['table_test_table_hidden']);
        $this->assertEquals('users', $params['table_test_table_tab']);
    }

    /**
     * Test that fromUrl() parses empty parameters to empty state.
     *
     * @return void
     */
    public function test_from_url_parses_empty_parameters_to_empty_state(): void
    {
        // Arrange
        $params = [];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertIsArray($state);
        $this->assertEmpty($state);
    }

    /**
     * Test that fromUrl() parses sort parameters to state.
     *
     * @return void
     */
    public function test_from_url_parses_sort_parameters_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_sort' => 'name',
            'table_test_table_order' => 'desc',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('sort', $state);
        $this->assertEquals('name', $state['sort']['column']);
        $this->assertEquals('desc', $state['sort']['direction']);
    }

    /**
     * Test that fromUrl() defaults sort direction to asc.
     *
     * @return void
     */
    public function test_from_url_defaults_sort_direction_to_asc(): void
    {
        // Arrange
        $params = [
            'table_test_table_sort' => 'name',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertEquals('asc', $state['sort']['direction']);
    }

    /**
     * Test that fromUrl() parses filter parameters to state.
     *
     * @return void
     */
    public function test_from_url_parses_filter_parameters_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_filter_status' => 'active',
            'table_test_table_filter_role' => 'admin',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('filters', $state);
        $this->assertEquals('active', $state['filters']['status']);
        $this->assertEquals('admin', $state['filters']['role']);
    }

    /**
     * Test that fromUrl() decodes array filter values.
     *
     * @return void
     */
    public function test_from_url_decodes_array_filter_values(): void
    {
        // Arrange
        $arrayValue = ['php', 'laravel', 'testing'];
        $encoded = base64_encode(json_encode($arrayValue));
        
        $params = [
            'table_test_table_filter_tags' => $encoded,
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('filters', $state);
        $this->assertEquals($arrayValue, $state['filters']['tags']);
    }

    /**
     * Test that fromUrl() parses page parameter to state.
     *
     * @return void
     */
    public function test_from_url_parses_page_parameter_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_page' => '5',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('current_page', $state);
        $this->assertEquals(5, $state['current_page']);
    }

    /**
     * Test that fromUrl() parses page size parameter to state.
     *
     * @return void
     */
    public function test_from_url_parses_page_size_parameter_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_per_page' => '50',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('page_size', $state);
        $this->assertEquals(50, $state['page_size']);
    }

    /**
     * Test that fromUrl() parses search parameter to state.
     *
     * @return void
     */
    public function test_from_url_parses_search_parameter_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_search' => 'test search',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('search', $state);
        $this->assertEquals('test search', $state['search']);
    }

    /**
     * Test that fromUrl() parses hidden columns parameter to state.
     *
     * @return void
     */
    public function test_from_url_parses_hidden_columns_parameter_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_hidden' => 'id,password,created_at',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('hidden_columns', $state);
        $this->assertEquals(['id', 'password', 'created_at'], $state['hidden_columns']);
    }

    /**
     * Test that fromUrl() parses active tab parameter to state.
     *
     * @return void
     */
    public function test_from_url_parses_active_tab_parameter_to_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_tab' => 'settings',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertArrayHasKey('active_tab', $state);
        $this->assertEquals('settings', $state['active_tab']);
    }

    /**
     * Test that fromUrl() parses complex parameters with all properties.
     *
     * @return void
     */
    public function test_from_url_parses_complex_parameters_with_all_properties(): void
    {
        // Arrange
        $params = [
            'table_test_table_sort' => 'name',
            'table_test_table_order' => 'desc',
            'table_test_table_filter_status' => 'active',
            'table_test_table_filter_role' => 'admin',
            'table_test_table_page' => '3',
            'table_test_table_per_page' => '50',
            'table_test_table_search' => 'test',
            'table_test_table_hidden' => 'id,password',
            'table_test_table_tab' => 'users',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertEquals('name', $state['sort']['column']);
        $this->assertEquals('desc', $state['sort']['direction']);
        $this->assertEquals('active', $state['filters']['status']);
        $this->assertEquals('admin', $state['filters']['role']);
        $this->assertEquals(3, $state['current_page']);
        $this->assertEquals(50, $state['page_size']);
        $this->assertEquals('test', $state['search']);
        $this->assertEquals(['id', 'password'], $state['hidden_columns']);
        $this->assertEquals('users', $state['active_tab']);
    }

    /**
     * Test that fromUrl() works with Request object.
     *
     * @return void
     */
    public function test_from_url_works_with_request_object(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET', [
            'table_test_table_sort' => 'name',
            'table_test_table_order' => 'asc',
            'table_test_table_page' => '2',
        ]);

        // Act
        $state = $this->urlState->fromUrl($request);

        // Assert
        $this->assertEquals('name', $state['sort']['column']);
        $this->assertEquals('asc', $state['sort']['direction']);
        $this->assertEquals(2, $state['current_page']);
    }

    /**
     * Test that fromUrl() ignores parameters from other tables.
     *
     * @return void
     */
    public function test_from_url_ignores_parameters_from_other_tables(): void
    {
        // Arrange
        $params = [
            'table_test_table_sort' => 'name',
            'table_other_table_sort' => 'email',
            'table_test_table_page' => '2',
            'table_other_table_page' => '5',
        ];

        // Act
        $state = $this->urlState->fromUrl($params);

        // Assert
        $this->assertEquals('name', $state['sort']['column']);
        $this->assertEquals(2, $state['current_page']);
        $this->assertArrayNotHasKey('email', $state);
    }

    /**
     * Test that generateUrl() creates URL with state parameters.
     *
     * @return void
     */
    public function test_generate_url_creates_url_with_state_parameters(): void
    {
        // Arrange
        $baseUrl = 'https://example.com/users';
        $state = [
            'sort' => ['column' => 'name', 'direction' => 'asc'],
            'current_page' => 2,
        ];

        // Act
        $url = $this->urlState->generateUrl($baseUrl, $state);

        // Assert
        $this->assertStringStartsWith($baseUrl, $url);
        $this->assertStringContainsString('table_test_table_sort=name', $url);
        $this->assertStringContainsString('table_test_table_order=asc', $url);
        $this->assertStringContainsString('table_test_table_page=2', $url);
    }

    /**
     * Test that generateUrl() returns base URL when state is empty.
     *
     * @return void
     */
    public function test_generate_url_returns_base_url_when_state_is_empty(): void
    {
        // Arrange
        $baseUrl = 'https://example.com/users';
        $state = [];

        // Act
        $url = $this->urlState->generateUrl($baseUrl, $state);

        // Assert
        $this->assertEquals($baseUrl, $url);
    }

    /**
     * Test that generateUrl() uses correct separator for existing query string.
     *
     * @return void
     */
    public function test_generate_url_uses_correct_separator_for_existing_query_string(): void
    {
        // Arrange
        $baseUrl = 'https://example.com/users?existing=param';
        $state = [
            'sort' => ['column' => 'name', 'direction' => 'asc'],
        ];

        // Act
        $url = $this->urlState->generateUrl($baseUrl, $state);

        // Assert
        $this->assertStringContainsString('existing=param', $url);
        $this->assertStringContainsString('&table_test_table_sort=name', $url);
    }

    /**
     * Test that parseRequest() parses Request object to state.
     *
     * @return void
     */
    public function test_parse_request_parses_request_object_to_state(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET', [
            'table_test_table_sort' => 'email',
            'table_test_table_order' => 'desc',
            'table_test_table_filter_status' => 'active',
        ]);

        // Act
        $state = $this->urlState->parseRequest($request);

        // Assert
        $this->assertEquals('email', $state['sort']['column']);
        $this->assertEquals('desc', $state['sort']['direction']);
        $this->assertEquals('active', $state['filters']['status']);
    }

    /**
     * Test that getCleanParams() removes empty values.
     *
     * @return void
     */
    public function test_get_clean_params_removes_empty_values(): void
    {
        // Arrange
        $state = [
            'sort' => ['column' => 'name', 'direction' => 'asc'],
            'search' => '',
            'current_page' => 1, // Should be excluded (default page)
            'page_size' => 50,
        ];

        // Act
        $params = $this->urlState->getCleanParams($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_sort', $params);
        $this->assertArrayHasKey('table_test_table_order', $params);
        $this->assertArrayHasKey('table_test_table_per_page', $params);
        $this->assertArrayNotHasKey('table_test_table_search', $params);
        $this->assertArrayNotHasKey('table_test_table_page', $params);
    }

    /**
     * Test that mergeWithUrl() merges URL state with existing state.
     *
     * @return void
     */
    public function test_merge_with_url_merges_url_state_with_existing_state(): void
    {
        // Arrange
        $existingState = [
            'sort' => ['column' => 'name', 'direction' => 'asc'],
            'page_size' => 25,
        ];

        $params = [
            'table_test_table_sort' => 'email',
            'table_test_table_order' => 'desc',
            'table_test_table_filter_status' => 'active',
        ];

        // Act
        $mergedState = $this->urlState->mergeWithUrl($existingState, $params);

        // Assert
        $this->assertEquals('email', $mergedState['sort']['column']);
        $this->assertEquals('desc', $mergedState['sort']['direction']);
        $this->assertEquals(25, $mergedState['page_size']);
        $this->assertEquals('active', $mergedState['filters']['status']);
    }

    /**
     * Test that mergeWithUrl() works with Request object.
     *
     * @return void
     */
    public function test_merge_with_url_works_with_request_object(): void
    {
        // Arrange
        $existingState = [
            'page_size' => 25,
        ];

        $request = Request::create('/test', 'GET', [
            'table_test_table_sort' => 'name',
            'table_test_table_order' => 'asc',
        ]);

        // Act
        $mergedState = $this->urlState->mergeWithUrl($existingState, $request);

        // Assert
        $this->assertEquals('name', $mergedState['sort']['column']);
        $this->assertEquals(25, $mergedState['page_size']);
    }

    /**
     * Test that hasUrlState() returns true when URL has table state.
     *
     * @return void
     */
    public function test_has_url_state_returns_true_when_url_has_table_state(): void
    {
        // Arrange
        $params = [
            'table_test_table_sort' => 'name',
            'other_param' => 'value',
        ];

        // Act
        $hasState = $this->urlState->hasUrlState($params);

        // Assert
        $this->assertTrue($hasState);
    }

    /**
     * Test that hasUrlState() returns false when URL has no table state.
     *
     * @return void
     */
    public function test_has_url_state_returns_false_when_url_has_no_table_state(): void
    {
        // Arrange
        $params = [
            'other_param' => 'value',
            'another_param' => 'value2',
        ];

        // Act
        $hasState = $this->urlState->hasUrlState($params);

        // Assert
        $this->assertFalse($hasState);
    }

    /**
     * Test that hasUrlState() works with Request object.
     *
     * @return void
     */
    public function test_has_url_state_works_with_request_object(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET', [
            'table_test_table_page' => '2',
        ]);

        // Act
        $hasState = $this->urlState->hasUrlState($request);

        // Assert
        $this->assertTrue($hasState);
    }

    /**
     * Test that hasUrlState() returns false for other table parameters.
     *
     * @return void
     */
    public function test_has_url_state_returns_false_for_other_table_parameters(): void
    {
        // Arrange
        $params = [
            'table_other_table_sort' => 'name',
        ];

        // Act
        $hasState = $this->urlState->hasUrlState($params);

        // Assert
        $this->assertFalse($hasState);
    }

    /**
     * Test that removeFromUrl() removes table state parameters from URL.
     *
     * @return void
     */
    public function test_remove_from_url_removes_table_state_parameters_from_url(): void
    {
        // Arrange
        $url = 'https://example.com/users?table_test_table_sort=name&table_test_table_order=asc&other=param';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertStringNotContainsString('table_test_table_sort', $cleanUrl);
        $this->assertStringNotContainsString('table_test_table_order', $cleanUrl);
        $this->assertStringContainsString('other=param', $cleanUrl);
    }

    /**
     * Test that removeFromUrl() preserves other query parameters.
     *
     * @return void
     */
    public function test_remove_from_url_preserves_other_query_parameters(): void
    {
        // Arrange
        $url = 'https://example.com/users?table_test_table_page=2&filter=active&search=test';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertStringContainsString('filter=active', $cleanUrl);
        $this->assertStringContainsString('search=test', $cleanUrl);
        $this->assertStringNotContainsString('table_test_table_page', $cleanUrl);
    }

    /**
     * Test that removeFromUrl() handles URL without query string.
     *
     * @return void
     */
    public function test_remove_from_url_handles_url_without_query_string(): void
    {
        // Arrange
        $url = 'https://example.com/users';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertEquals($url, $cleanUrl);
    }

    /**
     * Test that removeFromUrl() handles URL with only table state parameters.
     *
     * @return void
     */
    public function test_remove_from_url_handles_url_with_only_table_state_parameters(): void
    {
        // Arrange
        $url = 'https://example.com/users?table_test_table_sort=name&table_test_table_order=asc';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertEquals('https://example.com/users', $cleanUrl);
        $this->assertStringNotContainsString('?', $cleanUrl);
    }

    /**
     * Test that removeFromUrl() preserves URL fragment.
     *
     * @return void
     */
    public function test_remove_from_url_preserves_url_fragment(): void
    {
        // Arrange
        $url = 'https://example.com/users?table_test_table_page=2&other=param#section';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertStringContainsString('#section', $cleanUrl);
        $this->assertStringNotContainsString('table_test_table_page', $cleanUrl);
    }

    /**
     * Test that removeFromUrl() preserves URL port.
     *
     * @return void
     */
    public function test_remove_from_url_preserves_url_port(): void
    {
        // Arrange
        $url = 'https://example.com:8080/users?table_test_table_page=2';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertStringContainsString(':8080', $cleanUrl);
        $this->assertStringNotContainsString('table_test_table_page', $cleanUrl);
    }

    /**
     * Test that removeFromUrl() preserves parameters from other tables.
     *
     * @return void
     */
    public function test_remove_from_url_preserves_parameters_from_other_tables(): void
    {
        // Arrange
        $url = 'https://example.com/users?table_test_table_page=2&table_other_table_page=5';

        // Act
        $cleanUrl = $this->urlState->removeFromUrl($url);

        // Assert
        $this->assertStringNotContainsString('table_test_table_page', $cleanUrl);
        $this->assertStringContainsString('table_other_table_page=5', $cleanUrl);
    }

    /**
     * Test that round-trip conversion preserves state.
     *
     * @return void
     */
    public function test_round_trip_conversion_preserves_state(): void
    {
        // Arrange
        $originalState = [
            'sort' => ['column' => 'name', 'direction' => 'desc'],
            'filters' => ['status' => 'active', 'role' => 'admin'],
            'current_page' => 3,
            'page_size' => 50,
            'search' => 'test search',
            'hidden_columns' => ['id', 'password'],
            'active_tab' => 'settings',
        ];

        // Act
        $params = $this->urlState->toUrl($originalState);
        $restoredState = $this->urlState->fromUrl($params);

        // Assert
        $this->assertEquals($originalState['sort'], $restoredState['sort']);
        $this->assertEquals($originalState['filters'], $restoredState['filters']);
        $this->assertEquals($originalState['current_page'], $restoredState['current_page']);
        $this->assertEquals($originalState['page_size'], $restoredState['page_size']);
        $this->assertEquals($originalState['search'], $restoredState['search']);
        $this->assertEquals($originalState['hidden_columns'], $restoredState['hidden_columns']);
        $this->assertEquals($originalState['active_tab'], $restoredState['active_tab']);
    }

    /**
     * Test that round-trip conversion with array filters preserves state.
     *
     * @return void
     */
    public function test_round_trip_conversion_with_array_filters_preserves_state(): void
    {
        // Arrange
        $originalState = [
            'filters' => [
                'tags' => ['php', 'laravel', 'testing'],
                'categories' => ['backend', 'frontend'],
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($originalState);
        $restoredState = $this->urlState->fromUrl($params);

        // Assert
        $this->assertEquals($originalState['filters']['tags'], $restoredState['filters']['tags']);
        $this->assertEquals($originalState['filters']['categories'], $restoredState['filters']['categories']);
    }

    /**
     * Test that different table instances have separate URL parameters.
     *
     * @return void
     */
    public function test_different_table_instances_have_separate_url_parameters(): void
    {
        // Arrange
        $urlState1 = new TableUrlState('table1');
        $urlState2 = new TableUrlState('table2');

        $state1 = ['sort' => ['column' => 'name', 'direction' => 'asc']];
        $state2 = ['sort' => ['column' => 'email', 'direction' => 'desc']];

        // Act
        $params1 = $urlState1->toUrl($state1);
        $params2 = $urlState2->toUrl($state2);

        // Assert
        $this->assertArrayHasKey('table_table1_sort', $params1);
        $this->assertArrayHasKey('table_table2_sort', $params2);
        $this->assertEquals('name', $params1['table_table1_sort']);
        $this->assertEquals('email', $params2['table_table2_sort']);
    }

    /**
     * Test that URL parameters are properly URL-encoded.
     *
     * @return void
     */
    public function test_url_parameters_are_properly_url_encoded(): void
    {
        // Arrange
        $state = [
            'search' => 'test & special chars',
            'filters' => [
                'name' => 'John Doe',
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);
        $url = $this->urlState->generateUrl('https://example.com/users', $state);

        // Assert
        $this->assertStringContainsString('test+%26+special+chars', $url);
        $this->assertStringContainsString('John+Doe', $url);
    }

    /**
     * Test that empty hidden columns array is not added to URL.
     *
     * @return void
     */
    public function test_empty_hidden_columns_array_is_not_added_to_url(): void
    {
        // Arrange
        $state = [
            'hidden_columns' => [],
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayNotHasKey('table_test_table_hidden', $params);
    }

    /**
     * Test that zero page size is included in URL.
     *
     * @return void
     */
    public function test_zero_page_size_is_included_in_url(): void
    {
        // Arrange
        $state = [
            'page_size' => 0,
        ];

        // Act
        $params = $this->urlState->toUrl($state);

        // Assert
        $this->assertArrayHasKey('table_test_table_per_page', $params);
        $this->assertEquals('0', $params['table_test_table_per_page']);
    }

    /**
     * Test that special characters in filter values are handled correctly.
     *
     * @return void
     */
    public function test_special_characters_in_filter_values_are_handled_correctly(): void
    {
        // Arrange
        $state = [
            'filters' => [
                'description' => 'Test & <script>alert("xss")</script>',
            ],
        ];

        // Act
        $params = $this->urlState->toUrl($state);
        $restoredState = $this->urlState->fromUrl($params);

        // Assert
        $this->assertEquals(
            'Test & <script>alert("xss")</script>',
            $restoredState['filters']['description']
        );
    }
}
