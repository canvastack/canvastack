<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Unit Tests for SwitchCheckbox Component.
 *
 * Validates Requirements: 5.1, 5.2, 5.3, 5.5, 5.6, 5.11
 *
 * Tests cover:
 * - Rendering with different sizes (sm, md, lg)
 * - Rendering with different colors (primary, secondary, accent, success, warning, error)
 * - Checked/unchecked states
 * - Disabled state
 * - Multiple options rendering
 * - Model binding support
 */
class SwitchCheckboxTest extends TestCase
{
    /**
     * Test rendering with small size.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getSizeClass
     */
    public function test_renders_with_small_size(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['size' => 'sm'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-sm', $html);
        $this->assertStringNotContainsString('toggle-lg', $html);
    }

    /**
     * Test rendering with medium size (default).
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getSizeClass
     */
    public function test_renders_with_medium_size_default(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['size' => 'md'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        // Medium is default, no size class added
        $this->assertStringNotContainsString('toggle-sm', $html);
        $this->assertStringNotContainsString('toggle-lg', $html);
        $this->assertStringContainsString('class="toggle', $html);
    }

    /**
     * Test rendering with large size.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getSizeClass
     */
    public function test_renders_with_large_size(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['size' => 'lg'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-lg', $html);
        $this->assertStringNotContainsString('toggle-sm', $html);
    }

    /**
     * Test rendering with primary color.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_primary_color(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['color' => 'primary'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-primary', $html);
    }

    /**
     * Test rendering with secondary color.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_secondary_color(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['color' => 'secondary'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-secondary', $html);
    }

    /**
     * Test rendering with accent color.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_accent_color(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['color' => 'accent'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-accent', $html);
    }

    /**
     * Test rendering with success color.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_success_color(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['color' => 'success'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-success', $html);
    }

    /**
     * Test rendering with warning color.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_warning_color(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['color' => 'warning'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-warning', $html);
    }

    /**
     * Test rendering with error color.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_error_color(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
        $attributes = ['color' => 'error'];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        $this->assertStringContainsString('toggle-error', $html);
    }

    /**
     * Test rendering with checked state (single value).
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::isChecked
     */
    public function test_renders_checked_state_single_value(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active', '0' => 'Inactive'];

        $html = $switchCheckbox->render('status', $options, '1');

        // Only one should have checked attribute (look for the standalone attribute)
        $this->assertEquals(1, preg_match_all('/\s+checked\s/', $html));
    }

    /**
     * Test rendering with checked state (array of values).
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::isChecked
     */
    public function test_renders_checked_state_array_values(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Read', '2' => 'Write', '3' => 'Delete'];
        $checked = ['1', '3'];

        $html = $switchCheckbox->render('permissions', $options, $checked);

        // Two should have checked attribute (look for the standalone attribute)
        $this->assertEquals(2, preg_match_all('/\s+checked\s/', $html));
    }

    /**
     * Test rendering with unchecked state.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::isChecked
     */
    public function test_renders_unchecked_state(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active', '0' => 'Inactive'];

        $html = $switchCheckbox->render('status', $options, null);

        // Should not have checked attribute (but will have aria-checked="false")
        $this->assertEquals(0, preg_match_all('/\s+checked\s/', $html));
        $this->assertStringContainsString('aria-checked="false"', $html);
    }

    /**
     * Test rendering with disabled state.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_disabled_state(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active', '0' => 'Inactive'];
        $attributes = ['disabled' => true];

        $html = $switchCheckbox->render('status', $options, null, $attributes);

        // Both options should be disabled
        $this->assertEquals(2, substr_count($html, 'disabled'));
    }

    /**
     * Test rendering with enabled state (default).
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_enabled_state_default(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];

        $html = $switchCheckbox->render('status', $options, null);

        $this->assertStringNotContainsString('disabled', $html);
    }

    /**
     * Test rendering with multiple options.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_multiple_options(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = [
            '1' => 'Read',
            '2' => 'Write',
            '3' => 'Delete',
            '4' => 'Execute',
        ];

        $html = $switchCheckbox->render('permissions', $options, null);

        // Should have 4 checkboxes
        $this->assertEquals(4, substr_count($html, 'type="checkbox"'));

        // Should have 4 form-control wrappers
        $this->assertEquals(4, substr_count($html, 'class="form-control"'));

        // Should have all labels
        foreach ($options as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    /**
     * Test rendering with DaisyUI structure.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_with_daisyui_structure(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];

        $html = $switchCheckbox->render('status', $options, null);

        // Check DaisyUI classes
        $this->assertStringContainsString('class="form-control"', $html);
        $this->assertStringContainsString('class="label cursor-pointer"', $html);
        $this->assertStringContainsString('label-text', $html); // Now includes dark mode class
        $this->assertStringContainsString('class="toggle', $html);
    }

    /**
     * Test rendering with unique IDs for each option.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_with_unique_ids(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1', '2' => 'Option 2', '3' => 'Option 3'];

        $html = $switchCheckbox->render('test_field', $options, null);

        $this->assertStringContainsString('id="test_field_1"', $html);
        $this->assertStringContainsString('id="test_field_2"', $html);
        $this->assertStringContainsString('id="test_field_3"', $html);
    }

    /**
     * Test rendering with field name array notation.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_with_field_name_array_notation(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];

        $html = $switchCheckbox->render('status', $options, null);

        $this->assertStringContainsString('name="status[]"', $html);
    }

    /**
     * Test rendering with all attributes combined.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getSizeClass
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::isChecked
     */
    public function test_renders_with_all_attributes_combined(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active', '0' => 'Inactive'];
        $attributes = [
            'size' => 'lg',
            'color' => 'success',
            'disabled' => true,
        ];

        $html = $switchCheckbox->render('status', $options, '1', $attributes);

        $this->assertStringContainsString('toggle-lg', $html);
        $this->assertStringContainsString('toggle-success', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('checked', $html);
    }

    /**
     * Test rendering with default attributes when none provided.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getSizeClass
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_renders_with_default_attributes(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];

        $html = $switchCheckbox->render('status', $options, null);

        // Default size is 'md' (no size class)
        $this->assertStringNotContainsString('toggle-sm', $html);
        $this->assertStringNotContainsString('toggle-lg', $html);

        // Default color is 'primary'
        $this->assertStringContainsString('toggle-primary', $html);

        // Default disabled is false
        $this->assertStringNotContainsString('disabled', $html);
    }

    /**
     * Test rendering with empty options array.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::render
     */
    public function test_renders_empty_with_no_options(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = [];

        $html = $switchCheckbox->render('status', $options, null);

        $this->assertEmpty($html);
    }

    /**
     * Test isChecked method with single value.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::isChecked
     */
    public function test_is_checked_with_single_value(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $reflection = new \ReflectionClass($switchCheckbox);
        $method = $reflection->getMethod('isChecked');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($switchCheckbox, '1', '1'));
        $this->assertFalse($method->invoke($switchCheckbox, '2', '1'));
    }

    /**
     * Test isChecked method with array of values.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::isChecked
     */
    public function test_is_checked_with_array_values(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $reflection = new \ReflectionClass($switchCheckbox);
        $method = $reflection->getMethod('isChecked');
        $method->setAccessible(true);

        $checked = ['1', '3', '5'];

        $this->assertTrue($method->invoke($switchCheckbox, '1', $checked));
        $this->assertFalse($method->invoke($switchCheckbox, '2', $checked));
        $this->assertTrue($method->invoke($switchCheckbox, '3', $checked));
        $this->assertFalse($method->invoke($switchCheckbox, '4', $checked));
        $this->assertTrue($method->invoke($switchCheckbox, '5', $checked));
    }

    /**
     * Test getSizeClass method.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getSizeClass
     */
    public function test_get_size_class(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $reflection = new \ReflectionClass($switchCheckbox);
        $method = $reflection->getMethod('getSizeClass');
        $method->setAccessible(true);

        $this->assertEquals('toggle-sm', $method->invoke($switchCheckbox, 'sm'));
        $this->assertEquals('', $method->invoke($switchCheckbox, 'md'));
        $this->assertEquals('toggle-lg', $method->invoke($switchCheckbox, 'lg'));
        $this->assertEquals('', $method->invoke($switchCheckbox, 'invalid'));
    }

    /**
     * Test getColorClass method.
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox::getColorClass
     */
    public function test_get_color_class(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $reflection = new \ReflectionClass($switchCheckbox);
        $method = $reflection->getMethod('getColorClass');
        $method->setAccessible(true);

        // Test admin context
        $this->assertEquals('toggle-primary', $method->invoke($switchCheckbox, 'primary', 'admin'));
        $this->assertEquals('toggle-secondary', $method->invoke($switchCheckbox, 'secondary', 'admin'));
        $this->assertEquals('toggle-accent', $method->invoke($switchCheckbox, 'accent', 'admin'));
        $this->assertEquals('toggle-success', $method->invoke($switchCheckbox, 'success', 'admin'));
        $this->assertEquals('toggle-warning', $method->invoke($switchCheckbox, 'warning', 'admin'));
        $this->assertEquals('toggle-error', $method->invoke($switchCheckbox, 'error', 'admin'));
        $this->assertEquals('toggle-primary', $method->invoke($switchCheckbox, 'invalid', 'admin'));

        // Test public context (primary becomes info)
        $this->assertEquals('toggle-info', $method->invoke($switchCheckbox, 'primary', 'public'));
        $this->assertEquals('toggle-secondary', $method->invoke($switchCheckbox, 'secondary', 'public'));
    }
}
