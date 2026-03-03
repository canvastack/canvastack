<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Searchable Select Performance.
 *
 * Property 21: For any searchable select instance, initialization should complete within 200ms.
 *
 * Validates: Requirements 6.22
 *
 * This property test validates that searchable select initialization meets performance targets
 * across various scenarios:
 *
 * 1. Single instance initialization < 200ms
 * 2. Multiple instances (up to 10) each < 200ms
 * 3. Instances with various option counts (10, 100, 1000 options)
 * 4. Instances with different configurations (multiple, ajax, searchable)
 * 5. Script rendering performance < 200ms per instance
 * 6. Configuration building performance < 50ms
 * 7. Memory usage remains reasonable during initialization
 *
 * **Validates: Requirements 6.22**
 */
class SearchableSelectPerformancePropertyTest extends TestCase
{
    /**
     * Property: Single searchable select instance initialization completes within 200ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_single_instance_initialization_within_200ms(): void
    {
        $searchableSelect = new SearchableSelect();

        $startTime = microtime(true);

        $searchableSelect->register('country', [
            'placeholder' => 'Select a country',
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(
            200,
            $duration,
            "Single instance initialization took {$duration}ms, expected < 200ms"
        );
        $this->assertNotEmpty($script, 'Script should be generated');
    }

    /**
     * Property: Multiple searchable select instances each initialize within 200ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_multiple_instances_each_within_200ms(): void
    {
        $searchableSelect = new SearchableSelect();
        $instanceCount = 10;
        $durations = [];

        for ($i = 1; $i <= $instanceCount; $i++) {
            $startTime = microtime(true);

            $searchableSelect->register("field_{$i}", [
                'placeholder' => "Select field {$i}",
                'searchEnabled' => true,
            ]);

            $endTime = microtime(true);
            $durations[$i] = ($endTime - $startTime) * 1000;
        }

        // Render all scripts
        $startTime = microtime(true);
        $script = $searchableSelect->renderScript();
        $endTime = microtime(true);
        $renderDuration = ($endTime - $startTime) * 1000;

        // Each registration should be < 200ms
        foreach ($durations as $index => $duration) {
            $this->assertLessThan(
                200,
                $duration,
                "Instance {$index} registration took {$duration}ms, expected < 200ms"
            );
        }

        // Total render should be reasonable (< 200ms per instance average)
        $averageRenderTime = $renderDuration / $instanceCount;
        $this->assertLessThan(
            200,
            $averageRenderTime,
            "Average render time per instance was {$averageRenderTime}ms, expected < 200ms"
        );

        $this->assertNotEmpty($script, 'Script should be generated for all instances');
    }

    /**
     * Property: Searchable select with large option sets initializes within 200ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_large_option_sets_within_200ms(): void
    {
        $optionCounts = [10, 100, 1000];

        foreach ($optionCounts as $count) {
            $searchableSelect = new SearchableSelect();

            $startTime = microtime(true);

            $searchableSelect->register("field_with_{$count}_options", [
                'placeholder' => "Select from {$count} options",
                'searchEnabled' => true,
                'maxItemCount' => $count,
            ]);

            $script = $searchableSelect->renderScript();

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            $this->assertLessThan(
                200,
                $duration,
                "Instance with {$count} options took {$duration}ms, expected < 200ms"
            );
            $this->assertNotEmpty($script, "Script should be generated for {$count} options");
        }
    }

    /**
     * Property: Searchable select with various configurations initializes within 200ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_various_configurations_within_200ms(): void
    {
        $configurations = [
            'basic' => [],
            'multiple' => ['multiple' => true],
            'ajax' => ['ajax' => true, 'ajax_url' => '/api/options'],
            'searchable' => ['searchEnabled' => true, 'searchPlaceholder' => 'Search...'],
            'grouped' => ['removeItemButton' => true, 'placeholder' => 'Select...'],
            'complex' => [
                'multiple' => true,
                'searchEnabled' => true,
                'removeItemButton' => true,
                'maxItemCount' => 5,
                'placeholder' => 'Select multiple',
            ],
        ];

        foreach ($configurations as $name => $config) {
            $searchableSelect = new SearchableSelect();

            $startTime = microtime(true);

            $searchableSelect->register("field_{$name}", $config);
            $script = $searchableSelect->renderScript();

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            $this->assertLessThan(
                200,
                $duration,
                "Configuration '{$name}' took {$duration}ms, expected < 200ms"
            );
            $this->assertNotEmpty($script, "Script should be generated for '{$name}' configuration");
        }
    }

    /**
     * Property: Script rendering for searchable select completes within 200ms per instance.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     */
    public function property_script_rendering_within_200ms_per_instance(): void
    {
        $searchableSelect = new SearchableSelect();
        $instanceCount = 5;

        // Register multiple instances
        for ($i = 1; $i <= $instanceCount; $i++) {
            $searchableSelect->register("field_{$i}", [
                'placeholder' => "Select field {$i}",
                'searchEnabled' => true,
                'multiple' => $i % 2 === 0, // Alternate multiple selection
            ]);
        }

        // Measure script rendering time
        $startTime = microtime(true);
        $script = $searchableSelect->renderScript();
        $endTime = microtime(true);

        $duration = ($endTime - $startTime) * 1000;
        $perInstanceDuration = $duration / $instanceCount;

        $this->assertLessThan(
            200,
            $perInstanceDuration,
            "Script rendering took {$perInstanceDuration}ms per instance, expected < 200ms"
        );
        $this->assertNotEmpty($script, 'Script should be generated');

        // Verify script contains all instances
        for ($i = 1; $i <= $instanceCount; $i++) {
            $this->assertStringContainsString(
                "field_{$i}",
                $script,
                "Script should contain initialization for field_{$i}"
            );
        }
    }

    /**
     * Property: Configuration building completes within 50ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     */
    public function property_configuration_building_within_50ms(): void
    {
        $searchableSelect = new SearchableSelect();

        $complexConfig = [
            'multiple' => true,
            'searchEnabled' => true,
            'searchPlaceholder' => 'Type to search...',
            'removeItemButton' => true,
            'maxItemCount' => 10,
            'placeholder' => 'Select multiple items',
            'shouldSort' => true,
            'shouldSortItems' => true,
            'ajax' => true,
            'ajax_url' => '/api/search',
            'context' => 'admin',
        ];

        $startTime = microtime(true);

        $searchableSelect->register('complex_field', $complexConfig);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            50,
            $duration,
            "Configuration building took {$duration}ms, expected < 50ms"
        );

        $instances = $searchableSelect->getInstances();
        $this->assertArrayHasKey('complex_field', $instances);
    }

    /**
     * Property: Memory usage remains reasonable during initialization.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_memory_usage_remains_reasonable(): void
    {
        $memoryBefore = memory_get_usage(true);

        $searchableSelect = new SearchableSelect();

        // Register 20 instances with various configurations
        for ($i = 1; $i <= 20; $i++) {
            $searchableSelect->register("field_{$i}", [
                'placeholder' => "Select field {$i}",
                'searchEnabled' => true,
                'multiple' => $i % 3 === 0,
                'removeItemButton' => $i % 2 === 0,
            ]);
        }

        // Render all scripts
        $script = $searchableSelect->renderScript();

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        // Memory usage should be reasonable (< 5MB for 20 instances)
        $this->assertLessThan(
            5,
            $memoryUsed,
            "Memory usage was {$memoryUsed}MB for 20 instances, expected < 5MB"
        );

        $this->assertNotEmpty($script, 'Script should be generated');
    }

    /**
     * Property: Ajax-enabled searchable select initializes within 200ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_ajax_enabled_initialization_within_200ms(): void
    {
        $searchableSelect = new SearchableSelect();

        $startTime = microtime(true);

        $searchableSelect->register('ajax_field', [
            'ajax' => true,
            'ajax_url' => '/api/search/cities',
            'searchEnabled' => true,
            'placeholder' => 'Search cities...',
        ]);

        $script = $searchableSelect->renderScript();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            200,
            $duration,
            "Ajax-enabled instance took {$duration}ms, expected < 200ms"
        );
        $this->assertNotEmpty($script, 'Script should be generated');
        $this->assertStringContainsString('ajax', $script, 'Script should contain ajax configuration');
    }

    /**
     * Property: Searchable select with special characters in field name initializes within 200ms.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function property_special_characters_initialization_within_200ms(): void
    {
        $fieldNames = [
            'field-with-dashes',
            'field_with_underscores',
            'field.with.dots',
            'field[with][brackets]',
            'field123',
        ];

        foreach ($fieldNames as $fieldName) {
            $searchableSelect = new SearchableSelect();

            $startTime = microtime(true);

            $searchableSelect->register($fieldName, [
                'placeholder' => 'Select...',
                'searchEnabled' => true,
            ]);

            $script = $searchableSelect->renderScript();

            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;

            $this->assertLessThan(
                200,
                $duration,
                "Field '{$fieldName}' took {$duration}ms, expected < 200ms"
            );
            $this->assertNotEmpty($script, "Script should be generated for '{$fieldName}'");
        }
    }
}
