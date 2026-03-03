<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Unit Tests for SearchableSelect Component.
 *
 * Validates Requirements: 6.1, 6.2, 6.3
 *
 * Tests cover:
 * - Instance registration
 * - Script generation
 * - Configuration building
 * - Choices.js initialization
 * - Multiple instance handling
 * - Special character handling in field names
 */
class SearchableSelectTest extends TestCase
{
    /**
     * Test registering a single instance.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::getInstances
     */
    public function test_register_single_instance(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('country');

        $instances = $searchableSelect->getInstances();
        $this->assertArrayHasKey('country', $instances);
        $this->assertIsArray($instances['country']);
    }

    /**
     * Test registering multiple instances.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::getInstances
     */
    public function test_register_multiple_instances(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('country');
        $searchableSelect->register('province');
        $searchableSelect->register('city');

        $instances = $searchableSelect->getInstances();
        $this->assertCount(3, $instances);
        $this->assertArrayHasKey('country', $instances);
        $this->assertArrayHasKey('province', $instances);
        $this->assertArrayHasKey('city', $instances);
    }

    /**
     * Test registering instance with options.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::getInstances
     */
    public function test_register_instance_with_options(): void
    {
        $searchableSelect = new SearchableSelect();
        $options = [
            'multiple' => true,
            'placeholder_text' => 'Select countries',
        ];

        $searchableSelect->register('countries', $options);

        $instances = $searchableSelect->getInstances();
        $this->assertEquals($options, $instances['countries']);
    }

    /**
     * Test hasInstances returns false when no instances registered.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::hasInstances
     */
    public function test_has_instances_returns_false_when_empty(): void
    {
        $searchableSelect = new SearchableSelect();

        $this->assertFalse($searchableSelect->hasInstances());
    }

    /**
     * Test hasInstances returns true when instances registered.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::hasInstances
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     */
    public function test_has_instances_returns_true_when_registered(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('country');

        $this->assertTrue($searchableSelect->hasInstances());
    }

    /**
     * Test renderScript returns empty string when no instances.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::hasInstances
     */
    public function test_render_script_returns_empty_when_no_instances(): void
    {
        $searchableSelect = new SearchableSelect();

        $script = $searchableSelect->renderScript();

        $this->assertEmpty($script);
    }

    /**
     * Test renderScript generates valid JavaScript.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_render_script_generates_valid_javascript(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('country');

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('<script>', $script);
        $this->assertStringContainsString('</script>', $script);
        $this->assertStringContainsString('DOMContentLoaded', $script);
        $this->assertStringContainsString('addEventListener', $script);
    }

    /**
     * Test renderScript contains querySelector for field.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     */
    public function test_render_script_contains_query_selector(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('country');

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('querySelector', $script);
        $this->assertStringContainsString('select[name="country"]', $script);
    }

    /**
     * Test renderScript contains Choices constructor.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     */
    public function test_render_script_contains_choices_constructor(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('country');

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('new Choices', $script);
    }

    /**
     * Test renderScript contains null check.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     */
    public function test_render_script_contains_null_check(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('country');

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('if (select_country)', $script);
    }

    /**
     * Test renderScript handles special characters in field names.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     */
    public function test_render_script_handles_special_characters(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('user[country]');

        $script = $searchableSelect->renderScript();

        // Variable name should be sanitized
        $this->assertStringContainsString('select_user_country', $script);

        // Original field name should be in querySelector
        $this->assertStringContainsString('select[name="user[country]"]', $script);
    }

    /**
     * Test buildConfig includes searchEnabled.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_search_enabled(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $config = $method->invoke($searchableSelect, []);

        $this->assertArrayHasKey('searchEnabled', $config);
        $this->assertTrue($config['searchEnabled']);
    }

    /**
     * Test buildConfig includes default search placeholder.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_default_search_placeholder(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $config = $method->invoke($searchableSelect, []);

        $this->assertArrayHasKey('searchPlaceholderValue', $config);
        $this->assertEquals('Search...', $config['searchPlaceholderValue']);
    }

    /**
     * Test buildConfig includes custom search placeholder.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_custom_search_placeholder(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $options = ['search_placeholder' => 'Type to search...'];
        $config = $method->invoke($searchableSelect, $options);

        $this->assertEquals('Type to search...', $config['searchPlaceholderValue']);
    }

    /**
     * Test buildConfig includes removeItemButton for multiple.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_remove_item_button(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $options = ['multiple' => true];
        $config = $method->invoke($searchableSelect, $options);

        $this->assertArrayHasKey('removeItemButton', $config);
        $this->assertTrue($config['removeItemButton']);
    }

    /**
     * Test buildConfig includes shouldSort.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_should_sort(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $config = $method->invoke($searchableSelect, []);

        $this->assertArrayHasKey('shouldSort', $config);
        $this->assertTrue($config['shouldSort']);
    }

    /**
     * Test buildConfig includes custom sort option.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_custom_sort_option(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $options = ['sort' => false];
        $config = $method->invoke($searchableSelect, $options);

        $this->assertFalse($config['shouldSort']);
    }

    /**
     * Test buildConfig includes placeholder.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_placeholder(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $config = $method->invoke($searchableSelect, []);

        $this->assertArrayHasKey('placeholder', $config);
        $this->assertTrue($config['placeholder']);
        $this->assertArrayHasKey('placeholderValue', $config);
        $this->assertEquals('Select an option', $config['placeholderValue']);
    }

    /**
     * Test buildConfig includes custom placeholder text.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_custom_placeholder_text(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $options = ['placeholder_text' => 'Choose one'];
        $config = $method->invoke($searchableSelect, $options);

        $this->assertEquals('Choose one', $config['placeholderValue']);
    }

    /**
     * Test buildConfig includes allowHTML set to false for security.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_allow_html_false(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $config = $method->invoke($searchableSelect, []);

        $this->assertArrayHasKey('allowHTML', $config);
        $this->assertFalse($config['allowHTML']);
    }

    /**
     * Test buildConfig includes classNames for styling.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_includes_class_names(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $config = $method->invoke($searchableSelect, []);

        $this->assertArrayHasKey('classNames', $config);
        $this->assertIsArray($config['classNames']);
        $this->assertArrayHasKey('containerOuter', $config['classNames']);
        $this->assertArrayHasKey('input', $config['classNames']);
        $this->assertArrayHasKey('list', $config['classNames']);
    }

    /**
     * Test renderScript generates script for multiple instances.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     */
    public function test_render_script_generates_for_multiple_instances(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('country');
        $searchableSelect->register('province');
        $searchableSelect->register('city');

        $script = $searchableSelect->renderScript();

        // Should contain selectors for all fields
        $this->assertStringContainsString('select[name="country"]', $script);
        $this->assertStringContainsString('select[name="province"]', $script);
        $this->assertStringContainsString('select[name="city"]', $script);

        // Should contain variable names for all fields
        $this->assertStringContainsString('select_country', $script);
        $this->assertStringContainsString('select_province', $script);
        $this->assertStringContainsString('select_city', $script);

        // Should have 3 Choices constructor calls
        $this->assertEquals(3, substr_count($script, 'new Choices'));
    }

    /**
     * Test renderInstanceScript generates valid JavaScript for single instance.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_render_instance_script_generates_valid_javascript(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('renderInstanceScript');
        $method->setAccessible(true);

        $script = $method->invoke($searchableSelect, 'country', []);

        $this->assertStringContainsString('const select_country', $script);
        $this->assertStringContainsString('querySelector', $script);
        $this->assertStringContainsString('select[name="country"]', $script);
        $this->assertStringContainsString('if (select_country)', $script);
        $this->assertStringContainsString('new Choices(select_country', $script);
    }

    /**
     * Test renderScript contains valid JSON configuration.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderInstanceScript
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_render_script_contains_valid_json_configuration(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('country');

        $script = $searchableSelect->renderScript();

        // Extract JSON from script
        preg_match('/new Choices\([^,]+,\s*(\{.+?\})\)/', $script, $matches);
        $this->assertNotEmpty($matches);

        // Validate JSON
        $json = $matches[1];
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('searchEnabled', $decoded);
    }

    /**
     * Test buildConfig with all custom options.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::buildConfig
     */
    public function test_build_config_with_all_custom_options(): void
    {
        $searchableSelect = new SearchableSelect();
        $reflection = new \ReflectionClass($searchableSelect);
        $method = $reflection->getMethod('buildConfig');
        $method->setAccessible(true);

        $options = [
            'search_placeholder' => 'Type to search...',
            'placeholder_text' => 'Choose one',
            'multiple' => true,
            'sort' => false,
        ];

        $config = $method->invoke($searchableSelect, $options);

        $this->assertEquals('Type to search...', $config['searchPlaceholderValue']);
        $this->assertEquals('Choose one', $config['placeholderValue']);
        $this->assertTrue($config['removeItemButton']);
        $this->assertFalse($config['shouldSort']);
    }

    /**
     * Test getInstances returns empty array initially.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::getInstances
     */
    public function test_get_instances_returns_empty_array_initially(): void
    {
        $searchableSelect = new SearchableSelect();

        $instances = $searchableSelect->getInstances();

        $this->assertIsArray($instances);
        $this->assertEmpty($instances);
    }

    /**
     * Test register overwrites existing instance with same name.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::getInstances
     */
    public function test_register_overwrites_existing_instance(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('country', ['multiple' => false]);
        $searchableSelect->register('country', ['multiple' => true]);

        $instances = $searchableSelect->getInstances();
        $this->assertCount(1, $instances);
        $this->assertTrue($instances['country']['multiple']);
    }
}
