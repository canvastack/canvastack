<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Switch Checkbox Rendering.
 *
 * Validates Requirements: 5.1
 *
 * Property 18: Switch Checkbox Rendering
 *
 * Universal Property:
 * For any checkbox field with `check_type` set to `switch`, the rendered HTML
 * should contain DaisyUI toggle component markup instead of standard checkbox markup.
 *
 * Specific Properties:
 * 1. For any field name, rendered HTML contains input with type="checkbox"
 * 2. For any field name, rendered HTML contains class="toggle"
 * 3. For any size variant (sm, md, lg), rendered HTML contains appropriate size class
 * 4. For any color variant, rendered HTML contains appropriate color class
 * 5. For any checked value, rendered HTML contains checked attribute
 * 6. For any disabled state, rendered HTML contains disabled attribute
 * 7. For any option, rendered HTML contains DaisyUI form-control wrapper
 * 8. For any option, rendered HTML contains label with label-text class
 */
class SwitchCheckboxRenderingPropertyTest extends TestCase
{
    /**
     * Property 18.1: For any field name, rendered HTML contains input with type="checkbox".
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_html_contains_checkbox_input(string $fieldName): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1'];

        $html = $switchCheckbox->render($fieldName, $options, null);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString("name=\"{$fieldName}[]\"", $html);
    }

    /**
     * Property 18.2: For any field name, rendered HTML contains class="toggle".
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_html_contains_toggle_class(string $fieldName): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1'];

        $html = $switchCheckbox->render($fieldName, $options, null);

        $this->assertStringContainsString('class="toggle', $html);
    }

    /**
     * Property 18.3: For any size variant (sm, md, lg), rendered HTML contains appropriate size class.
     *
     * @test
     * @dataProvider sizeVariantProvider
     */
    public function property_rendered_html_contains_size_class(string $size, string $expectedClass): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1'];
        $attributes = ['size' => $size];

        $html = $switchCheckbox->render('test_field', $options, null, $attributes);

        if ($expectedClass !== '') {
            $this->assertStringContainsString($expectedClass, $html);
        } else {
            // For 'md' (default), no size class is added
            $this->assertStringNotContainsString('toggle-sm', $html);
            $this->assertStringNotContainsString('toggle-lg', $html);
        }
    }

    /**
     * Property 18.4: For any color variant, rendered HTML contains appropriate color class.
     *
     * @test
     * @dataProvider colorVariantProvider
     */
    public function property_rendered_html_contains_color_class(string $color, string $expectedClass): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1'];
        $attributes = ['color' => $color];

        $html = $switchCheckbox->render('test_field', $options, null, $attributes);

        $this->assertStringContainsString($expectedClass, $html);
    }

    /**
     * Property 18.5: For any checked value, rendered HTML contains checked attribute.
     *
     * @test
     * @dataProvider checkedValueProvider
     */
    public function property_rendered_html_contains_checked_attribute($checkedValue): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1', '2' => 'Option 2', '3' => 'Option 3'];

        $html = $switchCheckbox->render('test_field', $options, $checkedValue);

        // Count how many checked attributes should be present (look for 'checked' as standalone attribute)
        $expectedCheckedCount = is_array($checkedValue) ? count($checkedValue) : 1;

        // Use regex to match 'checked' as an attribute (not in class names)
        preg_match_all('/\s+checked\s*(?:=|>|\s)/', $html, $matches);
        $actualCheckedCount = count($matches[0]);

        $this->assertEquals($expectedCheckedCount, $actualCheckedCount);
    }

    /**
     * Property 18.6: For any disabled state, rendered HTML contains disabled attribute.
     *
     * @test
     */
    public function property_rendered_html_contains_disabled_attribute(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1', '2' => 'Option 2'];
        $attributes = ['disabled' => true];

        $html = $switchCheckbox->render('test_field', $options, null, $attributes);

        // Should have disabled attribute for each option
        $disabledCount = substr_count($html, 'disabled');
        $this->assertEquals(count($options), $disabledCount);
    }

    /**
     * Property 18.7: For any option, rendered HTML contains DaisyUI form-control wrapper.
     *
     * @test
     * @dataProvider optionsProvider
     */
    public function property_rendered_html_contains_form_control_wrapper(array $options): void
    {
        $switchCheckbox = new SwitchCheckbox();

        $html = $switchCheckbox->render('test_field', $options, null);

        // Should have form-control div for each option
        $formControlCount = substr_count($html, 'class="form-control"');
        $this->assertEquals(count($options), $formControlCount);
    }

    /**
     * Property 18.8: For any option, rendered HTML contains label with label-text class.
     *
     * @test
     * @dataProvider optionsProvider
     */
    public function property_rendered_html_contains_label_text(array $options): void
    {
        $switchCheckbox = new SwitchCheckbox();

        $html = $switchCheckbox->render('test_field', $options, null);

        // Should have label-text class for each option (may have additional classes)
        preg_match_all('/class="[^"]*label-text[^"]*"/', $html, $matches);
        $labelTextCount = count($matches[0]);
        $this->assertEquals(count($options), $labelTextCount);

        // Verify each label text is present
        foreach ($options as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    /**
     * Property 18.9: For any option value, rendered HTML contains unique ID.
     *
     * @test
     */
    public function property_rendered_html_contains_unique_ids(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1', '2' => 'Option 2', '3' => 'Option 3'];

        $html = $switchCheckbox->render('test_field', $options, null);

        // Each option should have a unique ID
        $this->assertStringContainsString('id="test_field_1"', $html);
        $this->assertStringContainsString('id="test_field_2"', $html);
        $this->assertStringContainsString('id="test_field_3"', $html);
    }

    /**
     * Property 18.10: For any multiple options, all are rendered.
     *
     * @test
     * @dataProvider multipleOptionsProvider
     */
    public function property_all_options_are_rendered(array $options): void
    {
        $switchCheckbox = new SwitchCheckbox();

        $html = $switchCheckbox->render('test_field', $options, null);

        // Count checkboxes
        $checkboxCount = substr_count($html, 'type="checkbox"');
        $this->assertEquals(count($options), $checkboxCount);

        // Verify each option value and label
        foreach ($options as $value => $label) {
            $this->assertStringContainsString("value=\"{$value}\"", $html);
            $this->assertStringContainsString($label, $html);
        }
    }

    /**
     * Property 18.11: For any empty options array, no HTML is rendered.
     *
     * @test
     */
    public function property_empty_options_renders_no_html(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = [];

        $html = $switchCheckbox->render('test_field', $options, null);

        $this->assertEmpty($html);
    }

    /**
     * Property 18.12: For any combination of attributes, all are applied correctly.
     *
     * @test
     */
    public function property_all_attributes_applied_correctly(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Active'];
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
     * Property 18.13: For any default attributes, default values are used.
     *
     * @test
     */
    public function property_default_attributes_are_used(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1'];

        $html = $switchCheckbox->render('test_field', $options, null);

        // Default size is 'md' (no size class)
        $this->assertStringNotContainsString('toggle-sm', $html);
        $this->assertStringNotContainsString('toggle-lg', $html);

        // Default color is 'primary'
        $this->assertStringContainsString('toggle-primary', $html);

        // Default disabled is false
        $this->assertStringNotContainsString('disabled', $html);
    }

    /**
     * Property 18.14: For any label with cursor-pointer class, it's clickable.
     *
     * @test
     */
    public function property_label_has_cursor_pointer(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1'];

        $html = $switchCheckbox->render('test_field', $options, null);

        $this->assertStringContainsString('class="label cursor-pointer"', $html);
    }

    /**
     * Property 18.15: For any array of checked values, all are marked as checked.
     *
     * @test
     */
    public function property_array_checked_values_all_marked(): void
    {
        $switchCheckbox = new SwitchCheckbox();
        $options = ['1' => 'Option 1', '2' => 'Option 2', '3' => 'Option 3', '4' => 'Option 4'];
        $checkedValues = ['1', '3'];

        $html = $switchCheckbox->render('test_field', $options, $checkedValues);

        // Should have exactly 2 checked attributes (use regex to match standalone attribute)
        preg_match_all('/\s+checked\s*(?:=|>|\s)/', $html, $matches);
        $checkedCount = count($matches[0]);
        $this->assertEquals(2, $checkedCount);
    }

    /**
     * Data provider for field names.
     *
     * @return array<array<string>>
     */
    public static function fieldNameProvider(): array
    {
        return [
            ['status'],
            ['is_active'],
            ['permissions'],
            ['features'],
            ['settings'],
        ];
    }

    /**
     * Data provider for size variants.
     *
     * @return array<array<string>>
     */
    public static function sizeVariantProvider(): array
    {
        return [
            ['sm', 'toggle-sm'],
            ['md', ''], // Default, no class
            ['lg', 'toggle-lg'],
        ];
    }

    /**
     * Data provider for color variants.
     *
     * @return array<array<string>>
     */
    public static function colorVariantProvider(): array
    {
        return [
            ['primary', 'toggle-primary'],
            ['secondary', 'toggle-secondary'],
            ['accent', 'toggle-accent'],
            ['success', 'toggle-success'],
            ['warning', 'toggle-warning'],
            ['error', 'toggle-error'],
        ];
    }

    /**
     * Data provider for checked values.
     *
     * @return array<array<mixed>>
     */
    public static function checkedValueProvider(): array
    {
        return [
            ['1'],
            ['2'],
            [['1', '2']],
            [['1', '2', '3']],
        ];
    }

    /**
     * Data provider for options.
     *
     * @return array<array<array<string>>>
     */
    public static function optionsProvider(): array
    {
        return [
            [['1' => 'Option 1']],
            [['1' => 'Active', '0' => 'Inactive']],
            [['yes' => 'Yes', 'no' => 'No']],
        ];
    }

    /**
     * Data provider for multiple options.
     *
     * @return array<array<array<string>>>
     */
    public static function multipleOptionsProvider(): array
    {
        return [
            [['1' => 'Option 1', '2' => 'Option 2']],
            [['1' => 'Read', '2' => 'Write', '3' => 'Delete']],
            [['1' => 'Feature A', '2' => 'Feature B', '3' => 'Feature C', '4' => 'Feature D']],
        ];
    }
}
