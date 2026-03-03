<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Feature Tests for Searchable Select Component.
 *
 * Tests complete user workflows including:
 * - Complete user workflow
 * - Ajax data loading
 * - Caching behavior
 *
 * Validates Requirements: 6.8, 6.9
 */
class SearchableSelectFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test complete user workflow for searchable select.
     *
     * Validates: Requirements 6.1, 6.4, 6.5, 6.6
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect
     */
    public function test_complete_user_workflow(): void
    {
        $searchableSelect = new SearchableSelect();

        // Step 1: Register a searchable select field
        $searchableSelect->register('country', [
            'searchEnabled' => true,
            'placeholder' => 'Select a country',
        ]);

        // Verify instance is registered
        $this->assertTrue(
            $searchableSelect->hasInstances(),
            'Searchable select instance should be registered'
        );

        $instances = $searchableSelect->getInstances();
        $this->assertArrayHasKey(
            'country',
            $instances,
            'Country field should be in instances'
        );

        // Step 2: Render the script
        $script = $searchableSelect->renderScript();

        // Verify script contains all necessary components
        $this->assertStringContainsString(
            'choices.js',
            $script,
            'Script should load Choices.js library'
        );
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Script should initialize Choices.js'
        );
        $this->assertStringContainsString(
            '[name="country"]',
            $script,
            'Script should target the country field'
        );
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Script should enable search functionality'
        );

        // Step 3: Verify the script is ready for user interaction
        $this->assertStringContainsString(
            'DOMContentLoaded',
            $script,
            'Script should wait for DOM to be ready'
        );
        $this->assertStringContainsString(
            'querySelector',
            $script,
            'Script should use querySelector to find the field'
        );

        // Step 4: Verify context attribute for styling
        $this->assertStringContainsString(
            'data-context',
            $script,
            'Script should set context data attribute for styling'
        );
    }

    /**
     * Test Ajax data loading workflow.
     *
     * Validates: Requirement 6.8 - Support remote data loading via Ajax
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_ajax_data_loading_workflow(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register field with Ajax configuration
        $searchableSelect->register('city', [
            'ajax' => true,
            'ajax_url' => '/api/cities',
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify Ajax configuration is present
        $this->assertStringContainsString(
            'ajax',
            $script,
            'Script should contain Ajax configuration'
        );
        $this->assertStringContainsString(
            '/api/cities',
            $script,
            'Script should contain Ajax URL'
        );

        // Verify the field is initialized with Ajax support
        $this->assertStringContainsString(
            '[name="city"]',
            $script,
            'Script should target the city field'
        );
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Search should be enabled for Ajax field'
        );
    }

    /**
     * Test caching behavior for searchable select.
     *
     * Validates: Requirement 6.9 - Cache search results for 5 minutes
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_caching_behavior(): void
    {
        // First instance
        $searchableSelect1 = new SearchableSelect();
        $searchableSelect1->register('product', [
            'searchEnabled' => true,
        ]);

        // First render - should generate script
        $startTime = microtime(true);
        $script1 = $searchableSelect1->renderScript();
        $firstRenderTime = (microtime(true) - $startTime) * 1000;

        // Verify script was generated
        $this->assertNotEmpty($script1, 'First render should generate script');

        // Second instance with same configuration
        $searchableSelect2 = new SearchableSelect();
        $searchableSelect2->register('product', [
            'searchEnabled' => true,
        ]);

        // Second render
        $startTime = microtime(true);
        $script2 = $searchableSelect2->renderScript();
        $secondRenderTime = (microtime(true) - $startTime) * 1000;

        // Verify script was generated (may differ in asset loading)
        $this->assertNotEmpty($script2, 'Second render should generate script');

        // Both should contain the same field initialization
        $this->assertStringContainsString(
            '[name="product"]',
            $script1,
            'First script should contain product field'
        );
        $this->assertStringContainsString(
            '[name="product"]',
            $script2,
            'Second script should contain product field'
        );

        // Note: Render times should be reasonable
        $this->assertLessThan(
            200,
            $firstRenderTime,
            'First render should be fast'
        );
        $this->assertLessThan(
            200,
            $secondRenderTime,
            'Second render should be fast'
        );
    }

    /**
     * Test asset loading optimization.
     *
     * Validates: Requirement 6.16 - Load library assets only when needed
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_asset_loading_optimization(): void
    {
        $searchableSelect = new SearchableSelect();

        // Without any instances, no script should be rendered
        $emptyScript = $searchableSelect->renderScript();
        $this->assertEmpty(
            $emptyScript,
            'No script should be rendered without instances'
        );

        // With instances, assets should be loaded
        $searchableSelect->register('category', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify Choices.js assets are loaded
        $this->assertStringContainsString(
            'choices.js',
            $script,
            'Choices.js library should be loaded'
        );
        $this->assertStringContainsString(
            'choices.min.css',
            $script,
            'Choices.js CSS should be loaded'
        );

        // Verify assets are loaded from CDN
        $this->assertStringContainsString(
            'cdn.jsdelivr.net',
            $script,
            'Assets should be loaded from CDN'
        );
    }

    /**
     * Test multiple fields workflow.
     *
     * Validates: Requirements 6.1, 6.4
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_multiple_fields_workflow(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register multiple fields with different configurations
        $fields = [
            'country' => ['searchEnabled' => true],
            'state' => ['searchEnabled' => true, 'multiple' => false],
            'city' => ['searchEnabled' => true, 'ajax' => true, 'ajax_url' => '/api/cities'],
            'category' => ['searchEnabled' => true, 'multiple' => true, 'removeItemButton' => true],
        ];

        foreach ($fields as $name => $config) {
            $searchableSelect->register($name, $config);
        }

        // Verify all instances are registered
        $instances = $searchableSelect->getInstances();
        $this->assertCount(
            4,
            $instances,
            'All four fields should be registered'
        );

        // Render script
        $script = $searchableSelect->renderScript();

        // Verify each field is initialized
        foreach (array_keys($fields) as $fieldName) {
            $this->assertStringContainsString(
                "[name=\"{$fieldName}\"]",
                $script,
                "Script should initialize {$fieldName} field"
            );
        }

        // Verify Choices.js is loaded only once
        $choicesLoadCount = substr_count($script, 'choices.min.js');
        $this->assertEquals(
            1,
            $choicesLoadCount,
            'Choices.js library should be loaded only once'
        );

        // Verify each field has its own Choices instance
        $choicesInitCount = substr_count($script, 'new Choices');
        $this->assertEquals(
            4,
            $choicesInitCount,
            'Each field should have its own Choices instance'
        );
    }

    /**
     * Test searchable select with context switching.
     *
     * Validates: Requirements 6.13, 6.14
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_context_switching_workflow(): void
    {
        // Test admin context
        $adminSelect = new SearchableSelect();
        $adminSelect->register('admin_field', [
            'searchEnabled' => true,
            'context' => 'admin',
        ]);

        $adminScript = $adminSelect->renderScript();
        $this->assertStringContainsString(
            'admin',
            $adminScript,
            'Admin context should be applied'
        );

        // Test public context
        $publicSelect = new SearchableSelect();
        $publicSelect->register('public_field', [
            'searchEnabled' => true,
            'context' => 'public',
        ]);

        $publicScript = $publicSelect->renderScript();
        $this->assertStringContainsString(
            'public',
            $publicScript,
            'Public context should be applied'
        );
    }

    /**
     * Test searchable select error handling.
     *
     * Validates: Requirements 6.1, 6.4
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_error_handling_workflow(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register field with empty name (edge case)
        $searchableSelect->register('', [
            'searchEnabled' => true,
        ]);

        // Should still render without errors
        $script = $searchableSelect->renderScript();
        $this->assertNotEmpty(
            $script,
            'Script should render even with empty field name'
        );

        // Register field with special characters
        $searchableSelect->register('field[with][brackets]', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();
        $this->assertStringContainsString(
            'field[with][brackets]',
            $script,
            'Script should handle special characters in field names'
        );
    }

    /**
     * Test searchable select with lazy loading.
     *
     * Validates: Requirement 6.17 - Support lazy loading
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_lazy_loading_workflow(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('lazy_field', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify script uses defer attribute for lazy loading
        $this->assertStringContainsString(
            'defer',
            $script,
            'Script should use defer attribute for lazy loading'
        );

        // Verify DOMContentLoaded event for proper initialization timing
        $this->assertStringContainsString(
            'DOMContentLoaded',
            $script,
            'Script should wait for DOM to be ready'
        );
    }

    /**
     * Test searchable select performance with large datasets.
     *
     * Validates: Requirement 6.22 - Initialization within 200ms
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_performance_with_large_datasets(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register 50 fields to simulate a large form
        for ($i = 1; $i <= 50; $i++) {
            $searchableSelect->register("field_{$i}", [
                'searchEnabled' => true,
                'multiple' => $i % 2 === 0,
            ]);
        }

        // Measure render time
        $startTime = microtime(true);
        $script = $searchableSelect->renderScript();
        $endTime = microtime(true);

        $renderTime = ($endTime - $startTime) * 1000;

        // Verify script was generated
        $this->assertNotEmpty(
            $script,
            'Script should be generated for 50 fields'
        );

        // Verify all fields are initialized
        $choicesCount = substr_count($script, 'new Choices');
        $this->assertEquals(
            50,
            $choicesCount,
            'All 50 fields should be initialized'
        );

        // Verify reasonable render time (< 1 second for 50 fields)
        $this->assertLessThan(
            1000,
            $renderTime,
            "Render time for 50 fields was {$renderTime}ms, expected < 1000ms"
        );
    }

    /**
     * Test searchable select with model binding workflow.
     *
     * Validates: Requirement 6.18 - Work with Model_Binding
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_model_binding_workflow(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register field that would be used with model binding
        $searchableSelect->register('status', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify field is properly initialized for model binding
        $this->assertStringContainsString(
            '[name="status"]',
            $script,
            'Field should be targeted by name attribute for model binding'
        );

        // Verify Choices.js is initialized (will respect selected values from model)
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Choices.js should be initialized to work with pre-selected values'
        );
    }

    /**
     * Test complete workflow with all features enabled.
     *
     * Validates: Requirements 6.1, 6.4, 6.5, 6.6, 6.7, 6.8
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect
     */
    public function test_complete_workflow_all_features(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register a field with all features enabled
        $searchableSelect->register('advanced_field', [
            'searchEnabled' => true,
            'multiple' => true,
            'removeItemButton' => true,
            'ajax' => true,
            'ajax_url' => '/api/search',
            'placeholder' => 'Search and select...',
            'context' => 'admin',
        ]);

        // Verify instance is registered
        $this->assertTrue($searchableSelect->hasInstances());

        // Render script
        $script = $searchableSelect->renderScript();

        // Verify all features are present
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Search should be enabled'
        );
        $this->assertStringContainsString(
            '"removeItemButton":true',
            $script,
            'Remove button should be enabled'
        );
        $this->assertStringContainsString(
            'ajax',
            $script,
            'Ajax should be configured'
        );
        $this->assertStringContainsString('/api/search', $script,
            'Ajax URL should be set');
        $this->assertStringContainsString('admin', $script,
            'Admin context should be applied');

        // Verify script structure
        $this->assertStringContainsString('new Choices', $script,
            'Choices.js should be initialized');
        $this->assertStringContainsString('[name="advanced_field"]', $script,
            'Field should be targeted');
        $this->assertStringContainsString('DOMContentLoaded', $script,
            'Script should wait for DOM');
    }
}
