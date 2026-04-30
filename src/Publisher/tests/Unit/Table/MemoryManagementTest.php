<?php

namespace Tests\Unit\Table;

use Tests\TestCase;
use ReflectionClass;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Library\Components\Table\Craft\Export;

/**
 * Memory Management Tests
 *
 * Verifies the memory management features implemented in task 2.3:
 * - Chunking threshold detection (2.3.1)
 * - Streaming export validation (2.3.2)
 * - Memory limit parsing (2.3.6)
 * - Out-of-memory error detection (2.3.7)
 *
 * @performance Memory Management (Requirement 6) - test coverage
 */
class MemoryManagementTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Call a private/protected method via reflection.
     */
    private function callPrivate(object $object, string $method, array $args = [])
    {
        $ref = new ReflectionClass($object);
        $m   = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($object, $args);
    }

    // =========================================================================
    // 1. Chunking threshold (Datatables::shouldUseChunking)
    // =========================================================================

    /** @test */
    public function shouldUseChunking_returns_false_below_threshold(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'shouldUseChunking', [999]);
        $this->assertFalse($result);
    }

    /** @test */
    public function shouldUseChunking_returns_false_at_threshold(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'shouldUseChunking', [1000]);
        $this->assertFalse($result);
    }

    /** @test */
    public function shouldUseChunking_returns_true_above_threshold(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'shouldUseChunking', [1001]);
        $this->assertTrue($result);
    }

    // =========================================================================
    // 2. Memory limit parsing (Datatables::parseMemoryLimit)
    // =========================================================================

    /** @test */
    public function parseMemoryLimit_parses_megabytes(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'parseMemoryLimit', ['128M']);
        $this->assertSame(134217728, $result); // 128 * 1024 * 1024
    }

    /** @test */
    public function parseMemoryLimit_parses_gigabytes(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'parseMemoryLimit', ['1G']);
        $this->assertSame(1073741824, $result); // 1 * 1024 * 1024 * 1024
    }

    /** @test */
    public function parseMemoryLimit_parses_kilobytes(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'parseMemoryLimit', ['512K']);
        $this->assertSame(524288, $result); // 512 * 1024
    }

    /** @test */
    public function parseMemoryLimit_returns_zero_for_unlimited(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'parseMemoryLimit', ['-1']);
        $this->assertSame(0, $result);
    }

    /** @test */
    public function parseMemoryLimit_returns_raw_bytes_for_plain_integer(): void
    {
        $dt     = new Datatables();
        $result = $this->callPrivate($dt, 'parseMemoryLimit', ['256']);
        $this->assertSame(256, $result);
    }

    // =========================================================================
    // 3. OOM error detection (Datatables::isOutOfMemoryError)
    // =========================================================================

    /** @test */
    public function isOutOfMemoryError_detects_allowed_memory_size_message(): void
    {
        $dt     = new Datatables();
        $error  = new \Error('Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes)');
        $result = $this->callPrivate($dt, 'isOutOfMemoryError', [$error]);
        $this->assertTrue($result);
    }

    /** @test */
    public function isOutOfMemoryError_detects_out_of_memory_message(): void
    {
        $dt     = new Datatables();
        $error  = new \Error('Out of memory (allocated 134217728) (tried to allocate 20480 bytes)');
        $result = $this->callPrivate($dt, 'isOutOfMemoryError', [$error]);
        $this->assertTrue($result);
    }

    /** @test */
    public function isOutOfMemoryError_returns_false_for_unrelated_error(): void
    {
        $dt     = new Datatables();
        $error  = new \Error('Division by zero');
        $result = $this->callPrivate($dt, 'isOutOfMemoryError', [$error]);
        $this->assertFalse($result);
    }

    // =========================================================================
    // 4. Export streaming validation (Export::validateExportRequest)
    // =========================================================================

    /** @test */
    public function validateExportRequest_accepts_valid_csv_request(): void
    {
        $export = new Export();
        $result = $this->callPrivate($export, 'validateExportRequest', [[
            'format' => 'csv',
            'data'   => [['id' => 1, 'name' => 'Test']],
        ]]);

        $this->assertSame('csv', $result['format']);
        $this->assertIsArray($result['data']);
    }

    /** @test */
    public function validateExportRequest_throws_for_unsupported_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $export = new Export();
        $this->callPrivate($export, 'validateExportRequest', [[
            'format' => 'xml',
            'data'   => [['id' => 1]],
        ]]);
    }

    /** @test */
    public function validateExportRequest_throws_for_empty_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $export = new Export();
        $this->callPrivate($export, 'validateExportRequest', [[
            'format' => 'csv',
            'data'   => [],
        ]]);
    }

    /** @test */
    public function validateExportRequest_throws_when_format_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $export = new Export();
        $this->callPrivate($export, 'validateExportRequest', [[
            'data' => [['id' => 1]],
        ]]);
    }

    // =========================================================================
    // 5. OOM helper function (Table.php global function)
    // =========================================================================

    /** @test */
    public function canvastack_is_out_of_memory_error_returns_true_for_oom(): void
    {
        $this->assertTrue(
            canvastack_is_out_of_memory_error(new \Error('Allowed memory size exhausted'))
        );
    }

    /** @test */
    public function canvastack_is_out_of_memory_error_returns_false_for_other_errors(): void
    {
        $this->assertFalse(
            canvastack_is_out_of_memory_error(new \Error('Syntax error'))
        );
    }

    // =========================================================================
    // 6. Memory limit parsing (Table.php global function)
    // =========================================================================

    /** @test */
    public function canvastack_table_parse_memory_limit_parses_megabytes(): void
    {
        $this->assertSame(134217728, canvastack_table_parse_memory_limit('128M'));
    }

    /** @test */
    public function canvastack_table_parse_memory_limit_returns_zero_for_unlimited(): void
    {
        $this->assertSame(0, canvastack_table_parse_memory_limit('-1'));
    }

    /** @test */
    public function canvastack_table_parse_memory_limit_parses_gigabytes(): void
    {
        $this->assertSame(1073741824, canvastack_table_parse_memory_limit('1G'));
    }
}
