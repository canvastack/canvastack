<?php

declare(strict_types=1);

namespace Tests\Unit\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test 2: Tab Rendering Performance.
 *
 * Validates: Requirements 1.12, 13.1
 *
 * Property: For all forms with N tabs (where 1 ≤ N ≤ 10),
 * the tab rendering time SHALL be less than 100ms.
 *
 * This property ensures that the tab system meets performance targets
 * regardless of the number of tabs or fields within tabs.
 */
class TabRenderingPerformancePropertyTest extends TestCase
{
    private AdminRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new AdminRenderer();
    }

    /**
     * @test
     * Property: Tab rendering completes within 100ms for up to 10 tabs
     */
    public function it_renders_tabs_within_performance_target(): void
    {
        // Test with varying number of tabs (1 to 10)
        for ($tabCount = 1; $tabCount <= 10; $tabCount++) {
            $tabs = $this->generateTabs($tabCount);

            $startTime = microtime(true);
            $html = $this->renderer->renderTabs($tabs);
            $endTime = microtime(true);

            $renderTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $this->assertLessThan(
                100,
                $renderTime,
                "Tab rendering took {$renderTime}ms for {$tabCount} tabs, exceeding 100ms target"
            );

            // Verify HTML was actually generated
            $this->assertNotEmpty($html);
            $this->assertStringContainsString('tabs-container', $html);
        }
    }

    /**
     * @test
     * Property: Tab rendering performance scales linearly with tab count
     */
    public function it_scales_linearly_with_tab_count(): void
    {
        $measurements = [];

        // Measure rendering time for different tab counts
        // Run multiple times and take average to reduce variance
        foreach ([1, 3, 5, 7, 10] as $tabCount) {
            $times = [];
            for ($i = 0; $i < 5; $i++) {
                $tabs = $this->generateTabs($tabCount);

                $startTime = microtime(true);
                $this->renderer->renderTabs($tabs);
                $endTime = microtime(true);

                $times[] = ($endTime - $startTime) * 1000;
            }

            // Use average to reduce variance
            $measurements[$tabCount] = array_sum($times) / count($times);
        }

        // Verify that all measurements are under 100ms
        foreach ($measurements as $tabCount => $time) {
            $this->assertLessThan(
                100,
                $time,
                "Rendering {$tabCount} tabs took {$time}ms, exceeding 100ms target"
            );
        }

        // Verify reasonable scaling: 10 tabs shouldn't take more than 10x the time of 3 tabs
        // (using 3 tabs as baseline instead of 1 to avoid microsecond variance issues)
        // Note: In development environment, this ratio can be higher due to overhead
        if ($measurements[3] > 0) {
            $ratio = $measurements[10] / $measurements[3];
            $this->assertLessThan(
                10,
                $ratio,
                "Rendering 10 tabs took {$ratio}x longer than 3 tabs, indicating poor scaling"
            );
        }
    }

    /**
     * @test
     * Property: Tab rendering with fields maintains performance target
     */
    public function it_maintains_performance_with_fields_in_tabs(): void
    {
        $tabs = [];

        // Create 10 tabs, each with 5 fields
        for ($i = 1; $i <= 10; $i++) {
            $tab = new Tab("Tab {$i}");

            // Add 5 fields to each tab
            for ($j = 1; $j <= 5; $j++) {
                $field = new TextField("field_{$i}_{$j}", "Field {$i}.{$j}");
                $tab->addField($field);
            }

            $tabs[] = $tab;
        }

        $startTime = microtime(true);
        $html = $this->renderer->renderTabs($tabs);
        $endTime = microtime(true);

        $renderTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            100,
            $renderTime,
            "Tab rendering with fields took {$renderTime}ms, exceeding 100ms target"
        );

        // Verify all fields were rendered
        for ($i = 1; $i <= 10; $i++) {
            for ($j = 1; $j <= 5; $j++) {
                $this->assertStringContainsString("field_{$i}_{$j}", $html);
            }
        }
    }

    /**
     * @test
     * Property: Tab rendering with validation errors maintains performance
     */
    public function it_maintains_performance_with_validation_errors(): void
    {
        $tabs = $this->generateTabs(10);

        // Simulate validation errors for fields in various tabs
        $errors = [
            'field_1' => ['Field 1 is required'],
            'field_5' => ['Field 5 is invalid'],
            'field_10' => ['Field 10 must be unique'],
        ];

        $startTime = microtime(true);
        $html = $this->renderer->renderTabs($tabs, $errors);
        $endTime = microtime(true);

        $renderTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            100,
            $renderTime,
            "Tab rendering with errors took {$renderTime}ms, exceeding 100ms target"
        );

        // Verify error indicators are present
        $this->assertStringContainsString('text-red-600', $html);
    }

    /**
     * @test
     * Property: Tab rendering is consistent across multiple invocations
     */
    public function it_has_consistent_performance_across_invocations(): void
    {
        $tabs = $this->generateTabs(10);
        $measurements = [];

        // Warm up - run once to initialize any caches
        $this->renderer->renderTabs($tabs);

        // Measure 10 consecutive renderings
        for ($i = 0; $i < 10; $i++) {
            $startTime = microtime(true);
            $this->renderer->renderTabs($tabs);
            $endTime = microtime(true);

            $measurements[] = ($endTime - $startTime) * 1000;
        }

        // All measurements should be under 100ms
        foreach ($measurements as $index => $time) {
            $this->assertLessThan(
                100,
                $time,
                "Invocation {$index} took {$time}ms, exceeding 100ms target"
            );
        }

        // Calculate coefficient of variation (CV) for consistency check
        // CV = (stddev / mean) * 100
        $mean = array_sum($measurements) / count($measurements);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $measurements)) / count($measurements);
        $stdDev = sqrt($variance);
        $cv = ($stdDev / $mean) * 100;

        // Coefficient of variation should be less than 100% (relaxed for CI/varied environments)
        // This allows for reasonable variation while still catching major inconsistencies
        // CV < 100% means stddev is less than the mean, which is acceptable
        $this->assertLessThan(
            100,
            $cv,
            "Performance is highly inconsistent (CV: {$cv}%, stddev: {$stdDev}ms, mean: {$mean}ms)"
        );

        // Also check that mean performance is reasonable
        $this->assertLessThan(
            50,
            $mean,
            "Average rendering time {$mean}ms exceeds 50ms target"
        );
    }

    /**
     * @test
     * Property: Empty tabs render quickly
     */
    public function it_renders_empty_tabs_quickly(): void
    {
        $tabs = [];

        // Create 10 empty tabs (no fields, no content)
        for ($i = 1; $i <= 10; $i++) {
            $tabs[] = new Tab("Tab {$i}");
        }

        $startTime = microtime(true);
        $html = $this->renderer->renderTabs($tabs);
        $endTime = microtime(true);

        $renderTime = ($endTime - $startTime) * 1000;

        // Empty tabs should render even faster (< 50ms)
        $this->assertLessThan(
            50,
            $renderTime,
            "Empty tab rendering took {$renderTime}ms, exceeding 50ms target"
        );

        $this->assertNotEmpty($html);
    }

    /**
     * @test
     * Property: Tab rendering with custom content maintains performance
     */
    public function it_maintains_performance_with_custom_content(): void
    {
        $tabs = [];

        // Create 10 tabs with custom HTML content
        for ($i = 1; $i <= 10; $i++) {
            $tab = new Tab("Tab {$i}");

            // Add custom HTML content
            $customHtml = str_repeat('<div class="custom-content">Content ' . $i . '</div>', 10);
            $tab->addContent($customHtml);

            $tabs[] = $tab;
        }

        $startTime = microtime(true);
        $html = $this->renderer->renderTabs($tabs);
        $endTime = microtime(true);

        $renderTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            100,
            $renderTime,
            "Tab rendering with custom content took {$renderTime}ms, exceeding 100ms target"
        );

        // Verify custom content was included
        $this->assertStringContainsString('custom-content', $html);
    }

    /**
     * Generate tabs for testing.
     *
     * @param int $count Number of tabs to generate
     * @return array<Tab>
     */
    private function generateTabs(int $count): array
    {
        $tabs = [];

        for ($i = 1; $i <= $count; $i++) {
            $tab = new Tab("Tab {$i}", $i === 1 ? 'active' : false);

            // Add a field to each tab
            $field = new TextField("field_{$i}", "Field {$i}");
            $tab->addField($field);

            $tabs[] = $tab;
        }

        return $tabs;
    }
}
