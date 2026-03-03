<?php

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\ContentSanitizer;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use PHPUnit\Framework\TestCase;

/**
 * Performance Tests for CKEditor Integration.
 *
 * Tests performance metrics including:
 * - Initialization time
 * - Memory usage with multiple editors
 *
 * **Validates: Requirement 4.20**
 *
 * Performance Targets:
 * - Initialization: < 300ms
 * - Memory usage: Reasonable for multiple editors
 */
class CKEditorPerformanceTest extends TestCase
{
    protected CKEditorIntegration $integration;

    protected EditorConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new EditorConfig();
        $this->integration = new CKEditorIntegration(
            $this->config,
            new ContentSanitizer(),
            new AssetManager()
        );
    }

    /**
     * @test
     * Test single editor initialization time
     *
     * Target: < 300ms
     * Requirements: 4.20
     */
    public function single_editor_initialization_time_is_acceptable(): void
    {
        // Measure initialization time
        $startTime = microtime(true);

        $this->integration->register('content', [
            'height' => 400,
            'toolbar' => $this->config->getDefaultToolbar(),
        ]);

        $instance = $this->integration->getInstance('content');

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Initialization completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Single editor initialization took {$duration}ms, expected < 300ms"
        );

        // Verify editor is properly initialized
        $this->assertNotNull($instance);
        $this->assertTrue($this->integration->hasInstances());

        // Output performance metric
        echo "\nSingle Editor Initialization: {$duration}ms";
    }

    /**
     * @test
     * Test multiple editors initialization time
     *
     * Target: < 300ms for 5 editors
     * Requirements: 4.20, 4.22
     */
    public function multiple_editors_initialization_time_is_acceptable(): void
    {
        // Measure initialization time for 5 editors
        $startTime = microtime(true);

        for ($i = 1; $i <= 5; $i++) {
            $this->integration->register("content{$i}", [
                'height' => 300 + ($i * 50),
                'toolbar' => $i % 2 === 0
                    ? $this->config->getMinimalToolbar()
                    : $this->config->getDefaultToolbar(),
            ]);
        }

        $count = $this->integration->count();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Initialization completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Multiple editors (5) initialization took {$duration}ms, expected < 300ms"
        );

        // Verify all editors are initialized
        $this->assertEquals(5, $count);

        // Output performance metric
        echo "\nMultiple Editors (5) Initialization: {$duration}ms";
    }

    /**
     * @test
     * Test memory usage with single editor
     *
     * Requirements: 4.20
     */
    public function single_editor_memory_usage_is_reasonable(): void
    {
        // Measure memory before
        $memoryBefore = memory_get_usage(true);

        // Initialize editor
        $this->integration->register('content', [
            'height' => 400,
            'toolbar' => $this->config->getDefaultToolbar(),
        ]);

        $instance = $this->integration->getInstance('content');

        // Measure memory after
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // Convert to KB

        // Assert: Memory usage is reasonable (< 1MB for single editor)
        $this->assertLessThan(
            1024,
            $memoryUsed,
            "Single editor used {$memoryUsed}KB, expected < 1MB"
        );

        // Verify editor is initialized
        $this->assertNotNull($instance);

        // Output performance metric
        echo "\nSingle Editor Memory Usage: " . number_format($memoryUsed, 2) . 'KB';
    }

    /**
     * @test
     * Test memory usage with multiple editors
     *
     * Requirements: 4.20, 4.22
     */
    public function multiple_editors_memory_usage_is_reasonable(): void
    {
        // Measure memory before
        $memoryBefore = memory_get_usage(true);

        // Initialize 10 editors
        for ($i = 1; $i <= 10; $i++) {
            $this->integration->register("content{$i}", [
                'height' => 300,
                'toolbar' => $this->config->getDefaultToolbar(),
            ]);
        }

        $count = $this->integration->count();

        // Measure memory after
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // Convert to KB

        // Assert: Memory usage is reasonable (< 5MB for 10 editors)
        $this->assertLessThan(
            5120,
            $memoryUsed,
            "10 editors used {$memoryUsed}KB, expected < 5MB"
        );

        // Verify all editors are initialized
        $this->assertEquals(10, $count);

        // Output performance metric
        echo "\nMultiple Editors (10) Memory Usage: " . number_format($memoryUsed, 2) . 'KB';
        echo "\nAverage per Editor: " . number_format($memoryUsed / 10, 2) . 'KB';
    }

    /**
     * @test
     * Test configuration building performance
     *
     * Requirements: 4.20
     */
    public function configuration_building_is_fast(): void
    {
        // Register editor
        $this->integration->register('content', [
            'height' => 400,
            'toolbar' => $this->config->getFullToolbar(),
            'language' => 'en',
            'extraPlugins' => ['table', 'image', 'link'],
        ]);

        // Measure configuration retrieval time
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $instance = $this->integration->getInstance('content');
            $config = $instance['config'];
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $averageDuration = $duration / 100;

        // Assert: Average retrieval time is very fast (< 1ms)
        $this->assertLessThan(
            1,
            $averageDuration,
            "Configuration retrieval took {$averageDuration}ms on average, expected < 1ms"
        );

        // Output performance metric
        echo "\nConfiguration Retrieval (100 iterations): {$duration}ms";
        echo "\nAverage per Retrieval: " . number_format($averageDuration, 4) . 'ms';
    }

    /**
     * @test
     * Test content sanitization performance
     *
     * Requirements: 4.15, 4.20
     */
    public function content_sanitization_is_fast(): void
    {
        // Prepare test content (realistic article size)
        $content = str_repeat('<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text. ', 50);
        $content .= '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>';
        $content .= '<h2>Heading</h2>';
        $content .= str_repeat('<p>More content here. ', 50);

        // Measure sanitization time
        $startTime = microtime(true);

        $sanitized = $this->integration->sanitize($content);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Sanitization completes quickly (< 200ms for ~5KB content)
        $this->assertLessThan(
            200,
            $duration,
            "Content sanitization took {$duration}ms, expected < 200ms"
        );

        // Verify content is sanitized
        $this->assertNotEmpty($sanitized);
        $this->assertStringContainsString('paragraph', $sanitized);

        // Output performance metric
        $contentSize = strlen($content) / 1024;
        echo "\nContent Sanitization: {$duration}ms for " . number_format($contentSize, 2) . 'KB';
    }

    /**
     * @test
     * Test bulk sanitization performance
     *
     * Requirements: 4.15, 4.20
     */
    public function bulk_sanitization_is_fast(): void
    {
        // Prepare multiple content pieces
        $contents = [];
        for ($i = 0; $i < 10; $i++) {
            $contents[] = '<p>Content piece ' . $i . ' with <strong>formatting</strong>.</p>';
        }

        // Measure bulk sanitization time
        $startTime = microtime(true);

        $sanitized = [];
        foreach ($contents as $content) {
            $sanitized[] = $this->integration->sanitize($content);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $averageDuration = $duration / 10;

        // Assert: Average sanitization time is fast (< 10ms per piece)
        $this->assertLessThan(
            10,
            $averageDuration,
            "Average sanitization took {$averageDuration}ms, expected < 10ms"
        );

        // Verify all content is sanitized
        $this->assertCount(10, $sanitized);

        // Output performance metric
        echo "\nBulk Sanitization (10 pieces): {$duration}ms";
        echo "\nAverage per Piece: " . number_format($averageDuration, 2) . 'ms';
    }

    /**
     * @test
     * Test context switching performance
     *
     * Requirements: 4.11, 4.12, 4.20
     */
    public function context_switching_is_fast(): void
    {
        // Measure context switching time
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->integration->setContext('admin');
            $this->integration->setContext('public');
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $averageDuration = $duration / 200; // 200 switches (100 iterations × 2)

        // Assert: Context switching is very fast (< 0.1ms per switch)
        $this->assertLessThan(
            0.1,
            $averageDuration,
            "Context switching took {$averageDuration}ms on average, expected < 0.1ms"
        );

        // Output performance metric
        echo "\nContext Switching (200 switches): {$duration}ms";
        echo "\nAverage per Switch: " . number_format($averageDuration, 4) . 'ms';
    }

    /**
     * @test
     * Test dark mode toggle performance
     *
     * Requirements: 4.13, 4.20
     */
    public function dark_mode_toggle_is_fast(): void
    {
        // Measure dark mode toggle time
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->integration->setDarkMode(true);
            $this->integration->setDarkMode(false);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $averageDuration = $duration / 200; // 200 toggles

        // Assert: Dark mode toggle is very fast (< 0.1ms per toggle)
        $this->assertLessThan(
            0.1,
            $averageDuration,
            "Dark mode toggle took {$averageDuration}ms on average, expected < 0.1ms"
        );

        // Output performance metric
        echo "\nDark Mode Toggle (200 toggles): {$duration}ms";
        echo "\nAverage per Toggle: " . number_format($averageDuration, 4) . 'ms';
    }

    /**
     * @test
     * Test instance management performance
     *
     * Requirements: 4.22, 4.20
     */
    public function instance_management_is_fast(): void
    {
        // Register 10 editors
        for ($i = 1; $i <= 10; $i++) {
            $this->integration->register("content{$i}", []);
        }

        // Measure instance management operations
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->integration->hasInstances();
            $this->integration->count();
            $this->integration->isRegistered('content5');
            $this->integration->getInstance('content5');
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $averageDuration = $duration / 400; // 400 operations

        // Assert: Instance management is very fast (< 0.1ms per operation)
        $this->assertLessThan(
            0.1,
            $averageDuration,
            "Instance management took {$averageDuration}ms on average, expected < 0.1ms"
        );

        // Output performance metric
        echo "\nInstance Management (400 operations): {$duration}ms";
        echo "\nAverage per Operation: " . number_format($averageDuration, 4) . 'ms';
    }

    /**
     * @test
     * Test performance under stress (many editors)
     *
     * Requirements: 4.20, 4.22
     */
    public function performance_under_stress_is_acceptable(): void
    {
        // Measure initialization time for 20 editors (stress test)
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);

        for ($i = 1; $i <= 20; $i++) {
            $this->integration->register("content{$i}", [
                'height' => 300,
                'toolbar' => $this->config->getDefaultToolbar(),
            ]);
        }

        $count = $this->integration->count();

        $memoryAfter = memory_get_usage(true);
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // Convert to KB

        // Assert: Even under stress, performance is acceptable
        // Allow more time for stress test (< 500ms for 20 editors)
        $this->assertLessThan(
            500,
            $duration,
            "Stress test (20 editors) took {$duration}ms, expected < 500ms"
        );

        // Assert: Memory usage is reasonable (< 10MB for 20 editors)
        $this->assertLessThan(
            10240,
            $memoryUsed,
            "Stress test used {$memoryUsed}KB, expected < 10MB"
        );

        // Verify all editors are initialized
        $this->assertEquals(20, $count);

        // Output performance metrics
        echo "\nStress Test (20 editors):";
        echo "\n  Initialization Time: {$duration}ms";
        echo "\n  Memory Usage: " . number_format($memoryUsed, 2) . 'KB';
        echo "\n  Average per Editor: " . number_format($duration / 20, 2) . 'ms, ' . number_format($memoryUsed / 20, 2) . 'KB';
    }
}
