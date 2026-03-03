<?php

declare(strict_types=1);

namespace Tests\Unit\Chart;

use Canvastack\Canvastack\Components\Chart\ChartBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Test AJAX functionality in ChartBuilder.
 */
class ChartBuilderAjaxTest extends TestCase
{
    public function test_ajax_method_sets_url_and_method(): void
    {
        $chart = new ChartBuilder();
        $chart->ajax('/api/charts/data', 'POST');

        $this->assertTrue($chart->isAjaxMode());
        $this->assertEquals('/api/charts/data', $chart->getAjaxUrl());
        $this->assertEquals('POST', $chart->getMethod());
    }

    public function test_ajax_method_defaults_to_post(): void
    {
        $chart = new ChartBuilder();
        $chart->ajax('/api/charts/data');

        $this->assertEquals('POST', $chart->getMethod());
    }

    public function test_set_method_accepts_valid_http_methods(): void
    {
        $chart = new ChartBuilder();

        $chart->setMethod('GET');
        $this->assertEquals('GET', $chart->getMethod());

        $chart->setMethod('POST');
        $this->assertEquals('POST', $chart->getMethod());

        $chart->setMethod('PUT');
        $this->assertEquals('PUT', $chart->getMethod());

        $chart->setMethod('PATCH');
        $this->assertEquals('PATCH', $chart->getMethod());

        $chart->setMethod('DELETE');
        $this->assertEquals('DELETE', $chart->getMethod());
    }

    public function test_set_method_throws_exception_for_invalid_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $chart = new ChartBuilder();
        $chart->setMethod('INVALID');
    }

    public function test_ajax_params_can_be_set(): void
    {
        $chart = new ChartBuilder();
        $params = ['start_date' => '2024-01-01', 'category' => 'sales'];

        $chart->ajax('/api/charts/data')->ajaxParams($params);

        $this->assertEquals($params, $chart->getAjaxParams());
    }

    public function test_realtime_sets_interval(): void
    {
        $chart = new ChartBuilder();
        $chart->realtime(60);

        $this->assertEquals(60, $chart->getRealtimeInterval());
    }

    public function test_auto_load_can_be_disabled(): void
    {
        $chart = new ChartBuilder();

        $this->assertTrue($chart->isAutoLoad()); // Default is true

        $chart->autoLoad(false);
        $this->assertFalse($chart->isAutoLoad());
    }

    public function test_is_ajax_mode_returns_false_when_no_url_set(): void
    {
        $chart = new ChartBuilder();

        $this->assertFalse($chart->isAjaxMode());
    }

    public function test_complete_ajax_configuration(): void
    {
        $chart = new ChartBuilder();
        $chart->line([], [])
            ->ajax('/api/charts/sales', 'POST')
            ->ajaxParams(['period' => 'monthly'])
            ->realtime(30)
            ->autoLoad(true);

        $this->assertTrue($chart->isAjaxMode());
        $this->assertEquals('/api/charts/sales', $chart->getAjaxUrl());
        $this->assertEquals('POST', $chart->getMethod());
        $this->assertEquals(['period' => 'monthly'], $chart->getAjaxParams());
        $this->assertEquals(30, $chart->getRealtimeInterval());
        $this->assertTrue($chart->isAutoLoad());
    }
}
