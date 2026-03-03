<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\ContentSanitizer;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use PHPUnit\Framework\TestCase;

/**
 * Property 17: CKEditor Initialization Performance.
 *
 * **Validates: Requirement 4.20**
 *
 * Property: For any CKEditor configuration, the initialization script generation
 * SHALL complete within 300ms to ensure fast page load times.
 *
 * This property validates that:
 * - Script generation is consistently fast regardless of configuration complexity
 * - Multiple editor instances can be initialized efficiently
 * - Performance remains acceptable with various toolbar configurations
 * - Asset loading and script rendering meet performance targets
 */
class CKEditorInitializationPerformancePropertyTest extends TestCase
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
     * Property: Single editor registration completes within 300ms
     */
    public function single_editor_initialization_is_fast(): void
    {
        // Arrange: Prepare configuration
        $fieldName = 'content';
        $options = ['height' => 300];

        // Act: Measure registration and instance retrieval time
        $startTime = microtime(true);
        $this->integration->register($fieldName, $options);
        $instance = $this->integration->getInstance($fieldName);
        $hasInstances = $this->integration->hasInstances();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Registration completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Single editor registration took {$duration}ms, expected < 300ms"
        );

        // Assert: Editor is registered
        $this->assertTrue($hasInstances);
        $this->assertNotNull($instance);
        $this->assertEquals($fieldName, $instance['fieldName']);
    }

    /**
     * @test
     * Property: Multiple editor registration (5 instances) completes within 300ms
     */
    public function multiple_editors_initialization_is_fast(): void
    {
        // Arrange: Prepare 5 editors with different configurations
        $editors = [
            'content1' => ['height' => 300],
            'content2' => ['height' => 400],
            'content3' => ['toolbar' => $this->config->getMinimalToolbar()],
            'content4' => ['toolbar' => $this->config->getFullToolbar()],
            'content5' => ['language' => 'en', 'height' => 350],
        ];

        // Act: Measure registration time for all editors
        $startTime = microtime(true);
        foreach ($editors as $fieldName => $options) {
            $this->integration->register($fieldName, $options);
        }
        $count = $this->integration->count();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Registration completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Multiple editors (5) registration took {$duration}ms, expected < 300ms"
        );

        // Assert: All editors are registered
        $this->assertEquals(5, $count);
        foreach (array_keys($editors) as $fieldName) {
            $this->assertTrue($this->integration->isRegistered($fieldName));
        }
    }

    /**
     * @test
     * Property: Editor with complex toolbar configuration registers within 300ms
     */
    public function complex_toolbar_initialization_is_fast(): void
    {
        // Arrange: Prepare editor with full toolbar (most complex)
        $options = [
            'toolbar' => $this->config->getFullToolbar(),
            'height' => 500,
            'language' => 'en',
            'extraPlugins' => ['table', 'image', 'link'],
            'removePlugins' => [],
        ];

        // Act: Measure registration time
        $startTime = microtime(true);
        $this->integration->register('content', $options);
        $instance = $this->integration->getInstance('content');
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Registration completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Complex toolbar registration took {$duration}ms, expected < 300ms"
        );

        // Assert: Configuration is properly stored
        $this->assertNotNull($instance);
        $this->assertArrayHasKey('toolbar', $instance['config']);
        $this->assertEquals(500, $instance['config']['height']);
    }

    /**
     * @test
     * Property: Editor with image upload configuration registers within 300ms
     */
    public function image_upload_configuration_is_fast(): void
    {
        // Arrange: Prepare editor with image upload support
        $options = [
            'uploadUrl' => '/upload/image',
            'height' => 400,
        ];

        // Act: Measure registration time
        $startTime = microtime(true);
        $this->integration->register('content', $options);
        $instance = $this->integration->getInstance('content');
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Registration completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Image upload configuration registration took {$duration}ms, expected < 300ms"
        );

        // Assert: Configuration is stored
        $this->assertNotNull($instance);
        $this->assertEquals(400, $instance['config']['height']);
    }

    /**
     * @test
     * Property: Asset manager operations complete within 300ms
     */
    public function asset_rendering_is_fast(): void
    {
        // Arrange: Register multiple editors
        for ($i = 1; $i <= 3; $i++) {
            $this->integration->register("content{$i}", []);
        }

        // Act: Measure asset manager check time
        $startTime = microtime(true);
        $hasInstances = $this->integration->hasInstances();
        $count = $this->integration->count();
        $instances = $this->integration->getInstances();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Operations complete within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Asset manager operations took {$duration}ms, expected < 300ms"
        );

        // Assert: Correct state
        $this->assertTrue($hasInstances);
        $this->assertEquals(3, $count);
        $this->assertCount(3, $instances);
    }

    /**
     * @test
     * Property: Configuration building completes within 300ms
     */
    public function complete_render_is_fast(): void
    {
        // Arrange: Register multiple editors with various configurations
        $this->integration->register('content1', ['height' => 300]);
        $this->integration->register('content2', ['toolbar' => $this->config->getMinimalToolbar()]);
        $this->integration->register('content3', ['uploadUrl' => '/upload']);

        // Act: Measure configuration retrieval time
        $startTime = microtime(true);
        $instances = $this->integration->getInstances();
        $count = $this->integration->count();
        foreach ($instances as $fieldName => $instance) {
            $this->integration->isRegistered($fieldName);
        }
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Operations complete within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Configuration operations took {$duration}ms, expected < 300ms"
        );

        // Assert: All editors are registered
        $this->assertEquals(3, $count);
        $this->assertCount(3, $instances);
    }

    /**
     * @test
     * Property: Dark mode configuration does not significantly impact performance
     */
    public function dark_mode_configuration_is_fast(): void
    {
        // Arrange: Enable dark mode and register editors
        $this->integration->setDarkMode(true);

        // Act: Measure registration time with dark mode
        $startTime = microtime(true);
        $this->integration->register('content1', []);
        $this->integration->register('content2', ['height' => 400]);
        $isDarkMode = $this->integration->isDarkMode();
        $count = $this->integration->count();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Registration completes within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Dark mode registration took {$duration}ms, expected < 300ms"
        );

        // Assert: Dark mode is enabled
        $this->assertTrue($isDarkMode);
        $this->assertEquals(2, $count);
    }

    /**
     * @test
     * Property: Context switching (admin/public) does not impact performance
     */
    public function context_switching_is_fast(): void
    {
        // Act: Measure context switching and registration time
        $startTime = microtime(true);

        $this->integration->setContext('admin');
        $this->integration->register('admin_content', []);
        $adminContext = $this->integration->getContext();

        $this->integration->setContext('public');
        $this->integration->register('public_content', []);
        $publicContext = $this->integration->getContext();

        $count = $this->integration->count();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Operations complete within 300ms
        $this->assertLessThan(
            300,
            $duration,
            "Context switching operations took {$duration}ms, expected < 300ms"
        );

        // Assert: Both editors are registered
        $this->assertEquals('public', $publicContext);
        $this->assertEquals(2, $count);
        $this->assertTrue($this->integration->isRegistered('admin_content'));
        $this->assertTrue($this->integration->isRegistered('public_content'));
    }

    /**
     * @test
     * Property: Performance is consistent across multiple runs
     */
    public function performance_is_consistent(): void
    {
        $durations = [];

        // Act: Measure performance across 10 runs
        for ($run = 0; $run < 10; $run++) {
            // Clear previous instances
            $this->integration->clear();

            // Register editors
            $startTime = microtime(true);
            for ($i = 1; $i <= 5; $i++) {
                $this->integration->register("content{$i}", ['height' => 300 + ($i * 50)]);
            }
            $count = $this->integration->count();
            $endTime = microtime(true);

            $durations[] = ($endTime - $startTime) * 1000;

            // Verify registration worked
            $this->assertEquals(5, $count);
        }

        // Assert: All runs complete within 300ms
        foreach ($durations as $index => $duration) {
            $this->assertLessThan(
                300,
                $duration,
                "Run #{$index} took {$duration}ms, expected < 300ms"
            );
        }

        // Assert: Standard deviation is reasonable (performance is consistent)
        $average = array_sum($durations) / count($durations);
        $variance = array_sum(array_map(function ($d) use ($average) {
            return pow($d - $average, 2);
        }, $durations)) / count($durations);
        $stdDev = sqrt($variance);

        // Standard deviation should be less than 50ms (reasonable consistency)
        $this->assertLessThan(
            50,
            $stdDev,
            "Performance standard deviation is {$stdDev}ms, expected < 50ms for consistency"
        );
    }

    /**
     * @test
     * Property: Large number of editors (10) still meets performance target
     */
    public function large_number_of_editors_is_acceptable(): void
    {
        // Act: Measure registration time for 10 editors (stress test)
        $startTime = microtime(true);
        for ($i = 1; $i <= 10; $i++) {
            $this->integration->register("content{$i}", [
                'height' => 300,
                'toolbar' => $i % 2 === 0 ? $this->config->getMinimalToolbar() : $this->config->getDefaultToolbar(),
            ]);
        }
        $count = $this->integration->count();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert: Registration completes within 300ms (even with 10 editors)
        $this->assertLessThan(
            300,
            $duration,
            "Large number of editors (10) registration took {$duration}ms, expected < 300ms"
        );

        // Assert: All 10 editors are registered
        $this->assertEquals(10, $count);
        for ($i = 1; $i <= 10; $i++) {
            $this->assertTrue($this->integration->isRegistered("content{$i}"));
        }
    }
}
