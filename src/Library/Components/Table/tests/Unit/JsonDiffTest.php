<?php

namespace Canvastack\Table\Tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\JsonDiff;
use PHPUnit\Framework\TestCase;

class JsonDiffTest extends TestCase
{
    public function test_compare_identical_payloads_returns_no_diff_with_summary()
    {
        $legacy = ['draw' => 1, 'recordsTotal' => 10, 'recordsFiltered' => 10, 'data' => [['id' => 1], ['id' => 2]]];
        $pipe = ['draw' => 1, 'recordsTotal' => 10, 'recordsFiltered' => 10, 'data' => [['id' => 1], ['id' => 2]]];
        $diff = JsonDiff::compare($legacy, $pipe);
        $this->assertSame('no_diff', $diff['note'] ?? null);
        $this->assertArrayHasKey('summary', $diff);
        $this->assertSame(10, $diff['summary']['recordsTotal']['legacy']);
        $this->assertSame(10, $diff['summary']['recordsTotal']['pipeline']);
        $this->assertSame(2, $diff['summary']['data_length']['legacy']);
        $this->assertSame(2, $diff['summary']['data_length']['pipeline']);
    }

    public function test_compare_different_counts_reports_diffs()
    {
        $legacy = ['recordsTotal' => 5, 'recordsFiltered' => 5, 'data' => [['id' => 1]]];
        $pipe = ['recordsTotal' => 6, 'recordsFiltered' => 5, 'data' => [['id' => 1], ['id' => 2]]];
        $diff = JsonDiff::compare($legacy, $pipe);
        $this->assertArrayHasKey('recordsTotal', $diff);
        $this->assertSame(['legacy' => 5, 'pipeline' => 6], $diff['recordsTotal']);
        $this->assertArrayHasKey('data_length', $diff);
        $this->assertSame(['legacy' => 1, 'pipeline' => 2], $diff['data_length']);
        $this->assertArrayHasKey('summary', $diff);
    }

    public function test_pipeline_unavailable_note()
    {
        $diff = JsonDiff::compare(['a' => 1], null);
        $this->assertSame('pipeline_output_unavailable', $diff['note'] ?? null);
    }
}
