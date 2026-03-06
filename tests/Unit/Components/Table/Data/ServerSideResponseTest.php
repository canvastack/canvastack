<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Data;

use Canvastack\Canvastack\Components\Table\Data\ServerSideResponse;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for ServerSideResponse.
 *
 * Tests the normalization of server-side response format across
 * DataTables.js and TanStack Table engines.
 *
 * @covers \Canvastack\Canvastack\Components\Table\Data\ServerSideResponse
 */
class ServerSideResponseTest extends TestCase
{
    /**
     * Test that ServerSideResponse can be instantiated with basic data.
     *
     * @return void
     */
    public function test_can_be_instantiated_with_basic_data(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $response = new ServerSideResponse(
            data: $data,
            total: 100,
            filtered: 50,
            page: 1,
            pageSize: 10
        );

        $this->assertEquals($data, $response->data);
        $this->assertEquals(100, $response->total);
        $this->assertEquals(50, $response->filtered);
        $this->assertEquals(1, $response->page);
        $this->assertEquals(10, $response->pageSize);
        $this->assertEquals(5, $response->totalPages); // 50 / 10 = 5
        $this->assertIsArray($response->meta);
        $this->assertEmpty($response->meta);
    }

    /**
     * Test that ServerSideResponse calculates total pages correctly.
     *
     * @return void
     */
    public function test_calculates_total_pages_correctly(): void
    {
        // Exact division
        $response = new ServerSideResponse([], 100, 100, 1, 10);
        $this->assertEquals(10, $response->totalPages);

        // With remainder
        $response = new ServerSideResponse([], 100, 95, 1, 10);
        $this->assertEquals(10, $response->totalPages); // ceil(95/10) = 10

        // Single page
        $response = new ServerSideResponse([], 5, 5, 1, 10);
        $this->assertEquals(1, $response->totalPages);

        // Zero records
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $this->assertEquals(0, $response->totalPages);
    }

    /**
     * Test that ServerSideResponse handles zero page size.
     *
     * @return void
     */
    public function test_handles_zero_page_size(): void
    {
        $response = new ServerSideResponse([], 100, 50, 1, 0);

        $this->assertEquals(0, $response->totalPages);
    }

    /**
     * Test that ServerSideResponse can include metadata.
     *
     * @return void
     */
    public function test_can_include_metadata(): void
    {
        $meta = [
            'query_time' => 0.05,
            'cache_hit' => true,
        ];

        $response = new ServerSideResponse([], 100, 50, 1, 10, $meta);

        $this->assertEquals($meta, $response->meta);
        $this->assertEquals(0.05, $response->meta['query_time']);
        $this->assertTrue($response->meta['cache_hit']);
    }

    /**
     * Test toDataTables() returns correct format.
     *
     * @return void
     */
    public function test_to_datatables_returns_correct_format(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $response = new ServerSideResponse($data, 100, 50, 2, 10);
        $result = $response->toDataTables(5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('draw', $result);
        $this->assertEquals(5, $result['draw']);
        $this->assertArrayHasKey('recordsTotal', $result);
        $this->assertEquals(100, $result['recordsTotal']);
        $this->assertArrayHasKey('recordsFiltered', $result);
        $this->assertEquals(50, $result['recordsFiltered']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($data, $result['data']);
    }

    /**
     * Test toDataTables() with default draw value.
     *
     * @return void
     */
    public function test_to_datatables_with_default_draw_value(): void
    {
        $response = new ServerSideResponse([], 100, 50, 1, 10);
        $result = $response->toDataTables();

        $this->assertEquals(1, $result['draw']);
    }

    /**
     * Test toDataTables() with empty data.
     *
     * @return void
     */
    public function test_to_datatables_with_empty_data(): void
    {
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $result = $response->toDataTables();

        $this->assertEquals(0, $result['recordsTotal']);
        $this->assertEquals(0, $result['recordsFiltered']);
        $this->assertEmpty($result['data']);
    }

    /**
     * Test toTanStack() returns correct format.
     *
     * @return void
     */
    public function test_to_tanstack_returns_correct_format(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $meta = ['query_time' => 0.05];

        $response = new ServerSideResponse($data, 100, 50, 2, 10, $meta);
        $result = $response->toTanStack();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals($data, $result['data']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertIsArray($result['pagination']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertEquals($meta, $result['meta']);
    }

    /**
     * Test toTanStack() pagination structure.
     *
     * @return void
     */
    public function test_to_tanstack_pagination_structure(): void
    {
        $response = new ServerSideResponse([], 100, 50, 3, 10);
        $result = $response->toTanStack();

        $pagination = $result['pagination'];

        $this->assertArrayHasKey('page', $pagination);
        $this->assertEquals(3, $pagination['page']);
        $this->assertArrayHasKey('pageSize', $pagination);
        $this->assertEquals(10, $pagination['pageSize']);
        $this->assertArrayHasKey('totalPages', $pagination);
        $this->assertEquals(5, $pagination['totalPages']);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertEquals(50, $pagination['total']);
        $this->assertArrayHasKey('totalRecords', $pagination);
        $this->assertEquals(100, $pagination['totalRecords']);
    }

    /**
     * Test toTanStack() with empty data.
     *
     * @return void
     */
    public function test_to_tanstack_with_empty_data(): void
    {
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $result = $response->toTanStack();

        $this->assertEmpty($result['data']);
        $this->assertEquals(0, $result['pagination']['total']);
        $this->assertEquals(0, $result['pagination']['totalRecords']);
        $this->assertEquals(0, $result['pagination']['totalPages']);
    }

    /**
     * Test response format normalization between engines.
     *
     * @return void
     */
    public function test_response_format_normalization(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $response = new ServerSideResponse($data, 100, 50, 2, 10);

        // DataTables format
        $dataTablesResult = $response->toDataTables(1);
        $this->assertEquals($data, $dataTablesResult['data']);
        $this->assertEquals(100, $dataTablesResult['recordsTotal']);
        $this->assertEquals(50, $dataTablesResult['recordsFiltered']);

        // TanStack format
        $tanStackResult = $response->toTanStack();
        $this->assertEquals($data, $tanStackResult['data']);
        $this->assertEquals(100, $tanStackResult['pagination']['totalRecords']);
        $this->assertEquals(50, $tanStackResult['pagination']['total']);

        // Both formats should have same underlying data
        $this->assertEquals(
            $dataTablesResult['data'],
            $tanStackResult['data']
        );
    }

    /**
     * Test toArray() returns complete array representation.
     *
     * @return void
     */
    public function test_to_array_returns_complete_representation(): void
    {
        $data = [['id' => 1, 'name' => 'John']];
        $meta = ['query_time' => 0.05];

        $response = new ServerSideResponse($data, 100, 50, 3, 10, $meta);
        $array = $response->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertEquals($data, $array['data']);
        $this->assertArrayHasKey('total', $array);
        $this->assertEquals(100, $array['total']);
        $this->assertArrayHasKey('filtered', $array);
        $this->assertEquals(50, $array['filtered']);
        $this->assertArrayHasKey('page', $array);
        $this->assertEquals(3, $array['page']);
        $this->assertArrayHasKey('pageSize', $array);
        $this->assertEquals(10, $array['pageSize']);
        $this->assertArrayHasKey('totalPages', $array);
        $this->assertEquals(5, $array['totalPages']);
        $this->assertArrayHasKey('meta', $array);
        $this->assertEquals($meta, $array['meta']);
    }

    /**
     * Test fromArray() creates instance from array.
     *
     * @return void
     */
    public function test_from_array_creates_instance(): void
    {
        $data = [
            'data' => [['id' => 1, 'name' => 'John']],
            'total' => 100,
            'filtered' => 50,
            'page' => 2,
            'pageSize' => 10,
            'meta' => ['query_time' => 0.05],
        ];

        $response = ServerSideResponse::fromArray($data);

        $this->assertEquals($data['data'], $response->data);
        $this->assertEquals(100, $response->total);
        $this->assertEquals(50, $response->filtered);
        $this->assertEquals(2, $response->page);
        $this->assertEquals(10, $response->pageSize);
        $this->assertEquals(5, $response->totalPages);
        $this->assertEquals($data['meta'], $response->meta);
    }

    /**
     * Test fromArray() with missing optional fields.
     *
     * @return void
     */
    public function test_from_array_with_missing_optional_fields(): void
    {
        $data = [];

        $response = ServerSideResponse::fromArray($data);

        $this->assertEmpty($response->data);
        $this->assertEquals(0, $response->total);
        $this->assertEquals(0, $response->filtered);
        $this->assertEquals(1, $response->page);
        $this->assertEquals(10, $response->pageSize);
        $this->assertEmpty($response->meta);
    }

    /**
     * Test getStartRecord() returns correct start record number.
     *
     * @return void
     */
    public function test_get_start_record_returns_correct_number(): void
    {
        // First page
        $response = new ServerSideResponse([], 100, 50, 1, 10);
        $this->assertEquals(1, $response->getStartRecord());

        // Second page
        $response = new ServerSideResponse([], 100, 50, 2, 10);
        $this->assertEquals(11, $response->getStartRecord());

        // Third page
        $response = new ServerSideResponse([], 100, 50, 3, 10);
        $this->assertEquals(21, $response->getStartRecord());

        // Empty data
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $this->assertEquals(0, $response->getStartRecord());
    }

    /**
     * Test getEndRecord() returns correct end record number.
     *
     * @return void
     */
    public function test_get_end_record_returns_correct_number(): void
    {
        // Full page
        $response = new ServerSideResponse([], 100, 50, 1, 10);
        $this->assertEquals(10, $response->getEndRecord());

        // Last page with partial records
        $response = new ServerSideResponse([], 100, 45, 5, 10);
        $this->assertEquals(45, $response->getEndRecord()); // min(50, 45)

        // Single record
        $response = new ServerSideResponse([], 1, 1, 1, 10);
        $this->assertEquals(1, $response->getEndRecord());
    }

    /**
     * Test hasNextPage() returns correct boolean.
     *
     * @return void
     */
    public function test_has_next_page_returns_correct_boolean(): void
    {
        // Has next page
        $response = new ServerSideResponse([], 100, 50, 1, 10);
        $this->assertTrue($response->hasNextPage());

        // Last page
        $response = new ServerSideResponse([], 100, 50, 5, 10);
        $this->assertFalse($response->hasNextPage());

        // Single page
        $response = new ServerSideResponse([], 5, 5, 1, 10);
        $this->assertFalse($response->hasNextPage());
    }

    /**
     * Test hasPreviousPage() returns correct boolean.
     *
     * @return void
     */
    public function test_has_previous_page_returns_correct_boolean(): void
    {
        // First page
        $response = new ServerSideResponse([], 100, 50, 1, 10);
        $this->assertFalse($response->hasPreviousPage());

        // Second page
        $response = new ServerSideResponse([], 100, 50, 2, 10);
        $this->assertTrue($response->hasPreviousPage());

        // Last page
        $response = new ServerSideResponse([], 100, 50, 5, 10);
        $this->assertTrue($response->hasPreviousPage());
    }

    /**
     * Test getPaginationText() returns correct text.
     *
     * @return void
     */
    public function test_get_pagination_text_returns_correct_text(): void
    {
        // Normal case
        $response = new ServerSideResponse([], 100, 50, 1, 10);
        $this->assertEquals(
            'Showing 1 to 10 of 50 entries (filtered from 100 total entries)',
            $response->getPaginationText()
        );

        // No filtering
        $response = new ServerSideResponse([], 50, 50, 1, 10);
        $this->assertEquals(
            'Showing 1 to 10 of 50 entries',
            $response->getPaginationText()
        );

        // Last page
        $response = new ServerSideResponse([], 100, 45, 5, 10);
        $this->assertEquals(
            'Showing 41 to 45 of 45 entries (filtered from 100 total entries)',
            $response->getPaginationText()
        );

        // Empty data
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $this->assertEquals(
            'Showing 0 entries',
            $response->getPaginationText()
        );
    }

    /**
     * Test addMeta() adds metadata.
     *
     * @return void
     */
    public function test_add_meta_adds_metadata(): void
    {
        $response = new ServerSideResponse([], 100, 50, 1, 10);

        $result = $response->addMeta('query_time', 0.05);

        $this->assertSame($response, $result); // Fluent interface
        $this->assertEquals(0.05, $response->meta['query_time']);
    }

    /**
     * Test addMeta() can chain multiple calls.
     *
     * @return void
     */
    public function test_add_meta_can_chain_multiple_calls(): void
    {
        $response = new ServerSideResponse([], 100, 50, 1, 10);

        $response->addMeta('query_time', 0.05)
            ->addMeta('cache_hit', true)
            ->addMeta('server', 'web-01');

        $this->assertEquals(0.05, $response->meta['query_time']);
        $this->assertTrue($response->meta['cache_hit']);
        $this->assertEquals('web-01', $response->meta['server']);
    }

    /**
     * Test getMeta() returns metadata value.
     *
     * @return void
     */
    public function test_get_meta_returns_metadata_value(): void
    {
        $meta = ['query_time' => 0.05, 'cache_hit' => true];
        $response = new ServerSideResponse([], 100, 50, 1, 10, $meta);

        $this->assertEquals(0.05, $response->getMeta('query_time'));
        $this->assertTrue($response->getMeta('cache_hit'));
    }

    /**
     * Test getMeta() returns default for missing key.
     *
     * @return void
     */
    public function test_get_meta_returns_default_for_missing_key(): void
    {
        $response = new ServerSideResponse([], 100, 50, 1, 10);

        $this->assertNull($response->getMeta('missing_key'));
        $this->assertEquals('default', $response->getMeta('missing_key', 'default'));
    }

    /**
     * Test hasData() returns correct boolean.
     *
     * @return void
     */
    public function test_has_data_returns_correct_boolean(): void
    {
        // With data
        $response = new ServerSideResponse([['id' => 1]], 100, 50, 1, 10);
        $this->assertTrue($response->hasData());

        // Empty data
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $this->assertFalse($response->hasData());
    }

    /**
     * Test getRecordCount() returns correct count.
     *
     * @return void
     */
    public function test_get_record_count_returns_correct_count(): void
    {
        // Multiple records
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'Bob'],
        ];
        $response = new ServerSideResponse($data, 100, 50, 1, 10);
        $this->assertEquals(3, $response->getRecordCount());

        // Single record
        $response = new ServerSideResponse([['id' => 1]], 100, 50, 1, 10);
        $this->assertEquals(1, $response->getRecordCount());

        // Empty
        $response = new ServerSideResponse([], 0, 0, 1, 10);
        $this->assertEquals(0, $response->getRecordCount());
    }

    /**
     * Test response with large dataset pagination.
     *
     * @return void
     */
    public function test_response_with_large_dataset_pagination(): void
    {
        // 10,000 total records, 5,000 filtered, page 50 of 500
        $response = new ServerSideResponse([], 10000, 5000, 50, 10);

        $this->assertEquals(500, $response->totalPages);
        $this->assertEquals(491, $response->getStartRecord());
        $this->assertEquals(500, $response->getEndRecord());
        $this->assertTrue($response->hasNextPage());
        $this->assertTrue($response->hasPreviousPage());
    }

    /**
     * Test response with custom page sizes.
     *
     * @return void
     */
    public function test_response_with_custom_page_sizes(): void
    {
        // Page size 25
        $response = new ServerSideResponse([], 100, 100, 1, 25);
        $this->assertEquals(4, $response->totalPages);
        $this->assertEquals(1, $response->getStartRecord());
        $this->assertEquals(25, $response->getEndRecord());

        // Page size 50
        $response = new ServerSideResponse([], 100, 100, 1, 50);
        $this->assertEquals(2, $response->totalPages);
        $this->assertEquals(1, $response->getStartRecord());
        $this->assertEquals(50, $response->getEndRecord());

        // Page size 100
        $response = new ServerSideResponse([], 100, 100, 1, 100);
        $this->assertEquals(1, $response->totalPages);
        $this->assertEquals(1, $response->getStartRecord());
        $this->assertEquals(100, $response->getEndRecord());
    }

    /**
     * Test response maintains data integrity across conversions.
     *
     * @return void
     */
    public function test_response_maintains_data_integrity_across_conversions(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ];

        $response = new ServerSideResponse($data, 100, 50, 2, 10);

        // Convert to DataTables
        $dataTablesResult = $response->toDataTables(5);
        $this->assertEquals($data, $dataTablesResult['data']);

        // Convert to TanStack
        $tanStackResult = $response->toTanStack();
        $this->assertEquals($data, $tanStackResult['data']);

        // Convert to array
        $arrayResult = $response->toArray();
        $this->assertEquals($data, $arrayResult['data']);

        // All conversions should have identical data
        $this->assertEquals(
            $dataTablesResult['data'],
            $tanStackResult['data']
        );
        $this->assertEquals(
            $tanStackResult['data'],
            $arrayResult['data']
        );
    }

    /**
     * Test response with edge case: single record on last page.
     *
     * @return void
     */
    public function test_response_with_single_record_on_last_page(): void
    {
        // 91 total records, page size 10, page 10 (last page with 1 record)
        $response = new ServerSideResponse([['id' => 91]], 100, 91, 10, 10);

        $this->assertEquals(10, $response->totalPages);
        $this->assertEquals(91, $response->getStartRecord());
        $this->assertEquals(91, $response->getEndRecord());
        $this->assertFalse($response->hasNextPage());
        $this->assertTrue($response->hasPreviousPage());
        $this->assertEquals(1, $response->getRecordCount());
    }

    /**
     * Test response format consistency for both engines.
     *
     * @return void
     */
    public function test_response_format_consistency_for_both_engines(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $response = new ServerSideResponse($data, 100, 50, 3, 10);

        // DataTables format
        $dt = $response->toDataTables(1);
        $this->assertCount(4, $dt); // draw, recordsTotal, recordsFiltered, data
        $this->assertArrayHasKey('draw', $dt);
        $this->assertArrayHasKey('recordsTotal', $dt);
        $this->assertArrayHasKey('recordsFiltered', $dt);
        $this->assertArrayHasKey('data', $dt);

        // TanStack format
        $ts = $response->toTanStack();
        $this->assertCount(3, $ts); // data, pagination, meta
        $this->assertArrayHasKey('data', $ts);
        $this->assertArrayHasKey('pagination', $ts);
        $this->assertArrayHasKey('meta', $ts);
        $this->assertCount(5, $ts['pagination']); // page, pageSize, totalPages, total, totalRecords

        // Both should represent same underlying data
        $this->assertEquals($dt['data'], $ts['data']);
        $this->assertEquals($dt['recordsTotal'], $ts['pagination']['totalRecords']);
        $this->assertEquals($dt['recordsFiltered'], $ts['pagination']['total']);
    }
}
