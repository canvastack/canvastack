<?php

namespace Canvastack\Canvastack\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Core\Services\ComponentFactoryService;
use Canvastack\Canvastack\Core\Contracts\ComponentFactoryInterface;

/**
 * Simple Component Factory Service Test
 * 
 * Unit tests sederhana untuk ComponentFactoryService
 * Tanpa dependencies Laravel untuk validasi basic functionality
 * 
 * @author CanvaStack Dev Team
 * @created 2024-12-19
 * @version 1.0
 */
class ComponentFactoryServiceSimpleTest extends TestCase
{
    protected ComponentFactoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComponentFactoryService();
    }

    /** @test */
    public function it_implements_component_factory_interface()
    {
        $this->assertInstanceOf(ComponentFactoryInterface::class, $this->service);
    }

    /** @test */
    public function it_creates_chart_component()
    {
        $chart = $this->service->createChart();
        
        $this->assertNotNull($chart);
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\Charts\Objects::class, $chart);
    }

    /** @test */
    public function it_creates_email_component()
    {
        $email = $this->service->createEmail();
        
        $this->assertNotNull($email);
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\Messages\Email\Objects::class, $email);
    }

    /** @test */
    public function it_caches_chart_component_instances()
    {
        $chart1 = $this->service->createChart();
        $chart2 = $this->service->createChart();
        
        $this->assertSame($chart1, $chart2, 'Chart instances should be cached');
    }

    /** @test */
    public function it_caches_email_component_instances()
    {
        $email1 = $this->service->createEmail();
        $email2 = $this->service->createEmail();
        
        $this->assertSame($email1, $email2, 'Email instances should be cached');
    }

    /** @test */
    public function it_throws_exception_for_unknown_component()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown component: unknown');
        
        $this->service->getComponent('unknown');
    }

    /** @test */
    public function it_clears_cache()
    {
        // Create and cache components
        $chart1 = $this->service->createChart();
        $this->assertTrue($this->service->isCached('chart'));
        
        // Clear cache
        $this->service->clearCache();
        $this->assertFalse($this->service->isCached('chart'));
        
        // Create new instance after cache clear
        $chart2 = $this->service->createChart();
        $this->assertNotSame($chart1, $chart2, 'New instance should be created after cache clear');
    }

    /** @test */
    public function it_checks_if_component_is_cached()
    {
        $this->assertFalse($this->service->isCached('chart'));
        
        $this->service->createChart();
        $this->assertTrue($this->service->isCached('chart'));
        
        $this->assertFalse($this->service->isCached('email'));
        
        $this->service->createEmail();
        $this->assertTrue($this->service->isCached('email'));
    }

    /** @test */
    public function it_handles_case_insensitive_component_names()
    {
        $chart1 = $this->service->getComponent('CHART');
        $chart2 = $this->service->getComponent('chart');
        $chart3 = $this->service->getComponent('Chart');
        
        $this->assertSame($chart1, $chart2);
        $this->assertSame($chart2, $chart3);
        
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\Charts\Objects::class, $chart1);
    }

    /** @test */
    public function it_gets_chart_component_by_name()
    {
        $chart = $this->service->getComponent('chart');
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\Charts\Objects::class, $chart);
    }

    /** @test */
    public function it_gets_email_component_by_name()
    {
        $email = $this->service->getComponent('email');
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\Messages\Email\Objects::class, $email);
    }

    /** @test */
    public function service_has_all_required_methods()
    {
        $methods = get_class_methods($this->service);
        
        $requiredMethods = [
            'createChart',
            'createEmail',
            'createForm',
            'createMetaTags',
            'createTable',
            'createTemplate',
            'createAllComponents',
            'getComponent',
            'clearCache',
            'isCached'
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(in_array($method, $methods), "Method {$method} should exist");
        }
    }
}