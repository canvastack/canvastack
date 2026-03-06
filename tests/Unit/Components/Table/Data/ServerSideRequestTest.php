<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Data;

use Canvastack\Canvastack\Components\Table\Data\ServerSideRequest;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for ServerSideRequest.
 *
 * Tests the normalization of server-side request parameters across
 * DataTables.js and TanStack Table engines.
 *
 * @covers \Canvastack\Canvastack\Components\Table\Data\ServerSideRequest
 */
class ServerSideRequestTest extends TestCase
{
    /**
     * Test that ServerSideRequest can be instantiated with default values.
     *
     * @return void
     */
    public function test_can_be_instantiated_with_defaults(): void
    {
        $request = new ServerSideRequest();

        $this->assertEquals(1, $request->page);
        $this->assertEquals(10, $request->pageSize);
        $this->assertEquals(0, $request->start);
        $this->assertEquals(10, $request->length);
        $this->assertNull($request->sortColumn);
        $this->assertNull($request->sortDirection);
        $this->assertIsArray($request->sortColumns);
        $this->assertEmpty($request->sortColumns);
        $this->assertNull($request->searchValue);
        $this->assertIsArray($request->columnSearches);
        $this->assertEmpty($request->columnSearches);
        $this->assertIsArray($request->filters);
        $this->assertEmpty($request->filters);
        $this->assertIsArray($request->extra);
        $this->assertEmpty($request->extra);
    }

    /**
     * Test fromDataTables() with basic pagination.
     *
     * @return void
     */
    public function test_from_datatables_basic_pagination(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertEquals(1, $request->page);
        $this->assertEquals(10, $request->pageSize);
        $this->assertEquals(0, $request->start);
        $this->assertEquals(10, $request->length);
    }

    /**
     * Test fromDataTables() with second page pagination.
     *
     * @return void
     */
    public function test_from_datatables_second_page_pagination(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 10,
            'length' => 10,
        ]);

        $this->assertEquals(2, $request->page);
        $this->assertEquals(10, $request->pageSize);
        $this->assertEquals(10, $request->start);
        $this->assertEquals(10, $request->length);
    }

    /**
     * Test fromDataTables() with custom page size.
     *
     * @return void
     */
    public function test_from_datatables_custom_page_size(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 25,
            'length' => 25,
        ]);

        $this->assertEquals(2, $request->page);
        $this->assertEquals(25, $request->pageSize);
        $this->assertEquals(25, $request->start);
        $this->assertEquals(25, $request->length);
    }

    /**
     * Test fromDataTables() with global search.
     *
     * @return void
     */
    public function test_from_datatables_global_search(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'search' => [
                'value' => 'test search',
            ],
        ]);

        $this->assertEquals('test search', $request->searchValue);
        $this->assertTrue($request->hasGlobalSearch());
    }

    /**
     * Test fromDataTables() with empty global search.
     *
     * @return void
     */
    public function test_from_datatables_empty_global_search(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'search' => [
                'value' => '',
            ],
        ]);

        $this->assertNull($request->searchValue);
        $this->assertFalse($request->hasGlobalSearch());
    }

    /**
     * Test fromDataTables() with single column sorting.
     *
     * @return void
     */
    public function test_from_datatables_single_column_sorting(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'order' => [
                ['column' => 0, 'dir' => 'asc'],
            ],
            'columns' => [
                ['data' => 'name'],
                ['data' => 'email'],
            ],
        ]);

        $this->assertEquals('name', $request->sortColumn);
        $this->assertEquals('asc', $request->sortDirection);
        $this->assertTrue($request->hasSorting());
        $this->assertCount(1, $request->sortColumns);
        $this->assertEquals('name', $request->sortColumns[0]['column']);
        $this->assertEquals('asc', $request->sortColumns[0]['direction']);
    }

    /**
     * Test fromDataTables() with descending sort.
     *
     * @return void
     */
    public function test_from_datatables_descending_sort(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'order' => [
                ['column' => 1, 'dir' => 'desc'],
            ],
            'columns' => [
                ['data' => 'name'],
                ['data' => 'created_at'],
            ],
        ]);

        $this->assertEquals('created_at', $request->sortColumn);
        $this->assertEquals('desc', $request->sortDirection);
    }

    /**
     * Test fromDataTables() with multi-column sorting.
     *
     * @return void
     */
    public function test_from_datatables_multi_column_sorting(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'order' => [
                ['column' => 0, 'dir' => 'asc'],
                ['column' => 1, 'dir' => 'desc'],
            ],
            'columns' => [
                ['data' => 'name'],
                ['data' => 'created_at'],
            ],
        ]);

        $this->assertEquals('name', $request->sortColumn);
        $this->assertEquals('asc', $request->sortDirection);
        $this->assertCount(2, $request->sortColumns);
        $this->assertEquals('name', $request->sortColumns[0]['column']);
        $this->assertEquals('asc', $request->sortColumns[0]['direction']);
        $this->assertEquals('created_at', $request->sortColumns[1]['column']);
        $this->assertEquals('desc', $request->sortColumns[1]['direction']);
    }

    /**
     * Test fromDataTables() with column-specific search.
     *
     * @return void
     */
    public function test_from_datatables_column_specific_search(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'columns' => [
                [
                    'data' => 'name',
                    'search' => ['value' => 'John'],
                ],
                [
                    'data' => 'email',
                    'search' => ['value' => 'example.com'],
                ],
            ],
        ]);

        $this->assertTrue($request->hasColumnSearches());
        $this->assertCount(2, $request->columnSearches);
        $this->assertEquals('John', $request->columnSearches['name']);
        $this->assertEquals('example.com', $request->columnSearches['email']);
    }

    /**
     * Test fromDataTables() ignores empty column searches.
     *
     * @return void
     */
    public function test_from_datatables_ignores_empty_column_searches(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'columns' => [
                [
                    'data' => 'name',
                    'search' => ['value' => ''],
                ],
                [
                    'data' => 'email',
                    'search' => ['value' => 'test'],
                ],
            ],
        ]);

        $this->assertCount(1, $request->columnSearches);
        $this->assertArrayNotHasKey('name', $request->columnSearches);
        $this->assertEquals('test', $request->columnSearches['email']);
    }

    /**
     * Test fromDataTables() stores extra parameters.
     *
     * @return void
     */
    public function test_from_datatables_stores_extra_parameters(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'draw' => 1,
            'custom_filter' => 'value',
        ]);

        $this->assertArrayHasKey('draw', $request->extra);
        $this->assertEquals(1, $request->extra['draw']);
        $this->assertArrayHasKey('custom_filter', $request->extra);
        $this->assertEquals('value', $request->extra['custom_filter']);
    }

    /**
     * Test fromTanStack() with basic pagination.
     *
     * @return void
     */
    public function test_from_tanstack_basic_pagination(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
        ]);

        $this->assertEquals(1, $request->page);
        $this->assertEquals(10, $request->pageSize);
        $this->assertEquals(0, $request->start);
        $this->assertEquals(10, $request->length);
    }

    /**
     * Test fromTanStack() with second page pagination.
     *
     * @return void
     */
    public function test_from_tanstack_second_page_pagination(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 1,
                'pageSize' => 10,
            ],
        ]);

        $this->assertEquals(2, $request->page);
        $this->assertEquals(10, $request->pageSize);
        $this->assertEquals(10, $request->start);
        $this->assertEquals(10, $request->length);
    }

    /**
     * Test fromTanStack() with custom page size.
     *
     * @return void
     */
    public function test_from_tanstack_custom_page_size(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 2,
                'pageSize' => 25,
            ],
        ]);

        $this->assertEquals(3, $request->page);
        $this->assertEquals(25, $request->pageSize);
        $this->assertEquals(50, $request->start);
        $this->assertEquals(25, $request->length);
    }

    /**
     * Test fromTanStack() with global filter.
     *
     * @return void
     */
    public function test_from_tanstack_global_filter(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'globalFilter' => 'test search',
        ]);

        $this->assertEquals('test search', $request->searchValue);
        $this->assertTrue($request->hasGlobalSearch());
    }

    /**
     * Test fromTanStack() with empty global filter.
     *
     * @return void
     */
    public function test_from_tanstack_empty_global_filter(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'globalFilter' => '',
        ]);

        $this->assertNull($request->searchValue);
        $this->assertFalse($request->hasGlobalSearch());
    }

    /**
     * Test fromTanStack() with single column sorting ascending.
     *
     * @return void
     */
    public function test_from_tanstack_single_column_sorting_ascending(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'sorting' => [
                ['id' => 'name', 'desc' => false],
            ],
        ]);

        $this->assertEquals('name', $request->sortColumn);
        $this->assertEquals('asc', $request->sortDirection);
        $this->assertTrue($request->hasSorting());
        $this->assertCount(1, $request->sortColumns);
        $this->assertEquals('name', $request->sortColumns[0]['column']);
        $this->assertEquals('asc', $request->sortColumns[0]['direction']);
    }

    /**
     * Test fromTanStack() with single column sorting descending.
     *
     * @return void
     */
    public function test_from_tanstack_single_column_sorting_descending(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'sorting' => [
                ['id' => 'created_at', 'desc' => true],
            ],
        ]);

        $this->assertEquals('created_at', $request->sortColumn);
        $this->assertEquals('desc', $request->sortDirection);
    }

    /**
     * Test fromTanStack() with multi-column sorting.
     *
     * @return void
     */
    public function test_from_tanstack_multi_column_sorting(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'sorting' => [
                ['id' => 'name', 'desc' => false],
                ['id' => 'created_at', 'desc' => true],
            ],
        ]);

        $this->assertEquals('name', $request->sortColumn);
        $this->assertEquals('asc', $request->sortDirection);
        $this->assertCount(2, $request->sortColumns);
        $this->assertEquals('name', $request->sortColumns[0]['column']);
        $this->assertEquals('asc', $request->sortColumns[0]['direction']);
        $this->assertEquals('created_at', $request->sortColumns[1]['column']);
        $this->assertEquals('desc', $request->sortColumns[1]['direction']);
    }

    /**
     * Test fromTanStack() with column filters.
     *
     * @return void
     */
    public function test_from_tanstack_column_filters(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'columnFilters' => [
                ['id' => 'name', 'value' => 'John'],
                ['id' => 'email', 'value' => 'example.com'],
            ],
        ]);

        $this->assertTrue($request->hasColumnSearches());
        $this->assertCount(2, $request->columnSearches);
        $this->assertEquals('John', $request->columnSearches['name']);
        $this->assertEquals('example.com', $request->columnSearches['email']);
    }

    /**
     * Test fromTanStack() ignores empty column filters.
     *
     * @return void
     */
    public function test_from_tanstack_ignores_empty_column_filters(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'columnFilters' => [
                ['id' => 'name', 'value' => ''],
                ['id' => 'email', 'value' => 'test'],
            ],
        ]);

        $this->assertCount(1, $request->columnSearches);
        $this->assertArrayNotHasKey('name', $request->columnSearches);
        $this->assertEquals('test', $request->columnSearches['email']);
    }

    /**
     * Test fromTanStack() with advanced filters.
     *
     * @return void
     */
    public function test_from_tanstack_advanced_filters(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'filters' => [
                'status' => 'active',
                'role' => 'admin',
            ],
        ]);

        $this->assertTrue($request->hasFilters());
        $this->assertCount(2, $request->filters);
        $this->assertEquals('active', $request->filters['status']);
        $this->assertEquals('admin', $request->filters['role']);
    }

    /**
     * Test fromTanStack() stores extra parameters.
     *
     * @return void
     */
    public function test_from_tanstack_stores_extra_parameters(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'custom_param' => 'value',
        ]);

        $this->assertArrayHasKey('custom_param', $request->extra);
        $this->assertEquals('value', $request->extra['custom_param']);
    }

    /**
     * Test getOffset() returns correct offset.
     *
     * @return void
     */
    public function test_get_offset_returns_correct_offset(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 20,
            'length' => 10,
        ]);

        $this->assertEquals(20, $request->getOffset());
    }

    /**
     * Test getLimit() returns correct limit.
     *
     * @return void
     */
    public function test_get_limit_returns_correct_limit(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 25,
        ]);

        $this->assertEquals(25, $request->getLimit());
    }

    /**
     * Test hasSorting() returns false when no sorting.
     *
     * @return void
     */
    public function test_has_sorting_returns_false_when_no_sorting(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertFalse($request->hasSorting());
    }

    /**
     * Test hasGlobalSearch() returns false when no search.
     *
     * @return void
     */
    public function test_has_global_search_returns_false_when_no_search(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertFalse($request->hasGlobalSearch());
    }

    /**
     * Test hasColumnSearches() returns false when no column searches.
     *
     * @return void
     */
    public function test_has_column_searches_returns_false_when_no_searches(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertFalse($request->hasColumnSearches());
    }

    /**
     * Test hasFilters() returns false when no filters.
     *
     * @return void
     */
    public function test_has_filters_returns_false_when_no_filters(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertFalse($request->hasFilters());
    }

    /**
     * Test getSortColumns() returns all sort columns.
     *
     * @return void
     */
    public function test_get_sort_columns_returns_all_sort_columns(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'sorting' => [
                ['id' => 'name', 'desc' => false],
                ['id' => 'created_at', 'desc' => true],
            ],
        ]);

        $sortColumns = $request->getSortColumns();

        $this->assertCount(2, $sortColumns);
        $this->assertEquals('name', $sortColumns[0]['column']);
        $this->assertEquals('asc', $sortColumns[0]['direction']);
        $this->assertEquals('created_at', $sortColumns[1]['column']);
        $this->assertEquals('desc', $sortColumns[1]['direction']);
    }

    /**
     * Test getPrimarySortColumn() returns primary sort column.
     *
     * @return void
     */
    public function test_get_primary_sort_column_returns_primary_column(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'sorting' => [
                ['id' => 'name', 'desc' => false],
                ['id' => 'created_at', 'desc' => true],
            ],
        ]);

        $this->assertEquals('name', $request->getPrimarySortColumn());
    }

    /**
     * Test getPrimarySortDirection() returns primary sort direction.
     *
     * @return void
     */
    public function test_get_primary_sort_direction_returns_primary_direction(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
            'sorting' => [
                ['id' => 'name', 'desc' => false],
                ['id' => 'created_at', 'desc' => true],
            ],
        ]);

        $this->assertEquals('asc', $request->getPrimarySortDirection());
    }

    /**
     * Test toArray() returns complete array representation.
     *
     * @return void
     */
    public function test_to_array_returns_complete_representation(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 1,
                'pageSize' => 25,
            ],
            'sorting' => [
                ['id' => 'name', 'desc' => false],
            ],
            'globalFilter' => 'test',
            'columnFilters' => [
                ['id' => 'email', 'value' => 'example.com'],
            ],
            'filters' => [
                'status' => 'active',
            ],
        ]);

        $array = $request->toArray();

        $this->assertArrayHasKey('page', $array);
        $this->assertEquals(2, $array['page']);
        $this->assertArrayHasKey('pageSize', $array);
        $this->assertEquals(25, $array['pageSize']);
        $this->assertArrayHasKey('start', $array);
        $this->assertEquals(25, $array['start']);
        $this->assertArrayHasKey('length', $array);
        $this->assertEquals(25, $array['length']);
        $this->assertArrayHasKey('sortColumn', $array);
        $this->assertEquals('name', $array['sortColumn']);
        $this->assertArrayHasKey('sortDirection', $array);
        $this->assertEquals('asc', $array['sortDirection']);
        $this->assertArrayHasKey('sortColumns', $array);
        $this->assertCount(1, $array['sortColumns']);
        $this->assertArrayHasKey('searchValue', $array);
        $this->assertEquals('test', $array['searchValue']);
        $this->assertArrayHasKey('columnSearches', $array);
        $this->assertCount(1, $array['columnSearches']);
        $this->assertArrayHasKey('filters', $array);
        $this->assertCount(1, $array['filters']);
        $this->assertArrayHasKey('extra', $array);
    }

    /**
     * Test property mapping between DataTables and TanStack formats.
     *
     * @return void
     */
    public function test_property_mapping_consistency_between_formats(): void
    {
        // DataTables request
        $dataTablesRequest = ServerSideRequest::fromDataTables([
            'start' => 20,
            'length' => 10,
            'search' => ['value' => 'test'],
            'order' => [
                ['column' => 0, 'dir' => 'desc'],
            ],
            'columns' => [
                ['data' => 'name'],
            ],
        ]);

        // TanStack request with equivalent data
        $tanStackRequest = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 2,
                'pageSize' => 10,
            ],
            'globalFilter' => 'test',
            'sorting' => [
                ['id' => 'name', 'desc' => true],
            ],
        ]);

        // Both should have same normalized values
        $this->assertEquals($dataTablesRequest->page, $tanStackRequest->page);
        $this->assertEquals($dataTablesRequest->pageSize, $tanStackRequest->pageSize);
        $this->assertEquals($dataTablesRequest->start, $tanStackRequest->start);
        $this->assertEquals($dataTablesRequest->length, $tanStackRequest->length);
        $this->assertEquals($dataTablesRequest->searchValue, $tanStackRequest->searchValue);
        $this->assertEquals($dataTablesRequest->sortColumn, $tanStackRequest->sortColumn);
        $this->assertEquals($dataTablesRequest->sortDirection, $tanStackRequest->sortDirection);
    }

    /**
     * Test fromDataTables() handles missing order gracefully.
     *
     * @return void
     */
    public function test_from_datatables_handles_missing_order_gracefully(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertNull($request->sortColumn);
        $this->assertNull($request->sortDirection);
        $this->assertEmpty($request->sortColumns);
        $this->assertFalse($request->hasSorting());
    }

    /**
     * Test fromTanStack() handles missing sorting gracefully.
     *
     * @return void
     */
    public function test_from_tanstack_handles_missing_sorting_gracefully(): void
    {
        $request = ServerSideRequest::fromTanStack([
            'pagination' => [
                'pageIndex' => 0,
                'pageSize' => 10,
            ],
        ]);

        $this->assertNull($request->sortColumn);
        $this->assertNull($request->sortDirection);
        $this->assertEmpty($request->sortColumns);
        $this->assertFalse($request->hasSorting());
    }

    /**
     * Test fromDataTables() handles zero length pagination.
     *
     * @return void
     */
    public function test_from_datatables_handles_zero_length_pagination(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 0,
        ]);

        $this->assertEquals(1, $request->page);
        $this->assertEquals(0, $request->pageSize);
        $this->assertEquals(0, $request->start);
        $this->assertEquals(0, $request->length);
    }

    /**
     * Test fromDataTables() normalizes sort direction to lowercase.
     *
     * @return void
     */
    public function test_from_datatables_normalizes_sort_direction_to_lowercase(): void
    {
        $request = ServerSideRequest::fromDataTables([
            'start' => 0,
            'length' => 10,
            'order' => [
                ['column' => 0, 'dir' => 'DESC'],
            ],
            'columns' => [
                ['data' => 'name'],
            ],
        ]);

        $this->assertEquals('desc', $request->sortDirection);
    }
}
