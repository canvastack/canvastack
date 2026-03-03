<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\CharacterCounter;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Character Counter Display.
 *
 * Validates Requirements: 7.1
 *
 * Property 22: Character Counter Display
 *
 * Universal Property:
 * For any text field with `maxLength()` set, the rendered HTML should include
 * a character counter element displaying "X / Y characters" format.
 *
 * Specific Properties:
 * 1. For any field name, rendered HTML contains counter element with unique ID
 * 2. For any maxLength value, rendered HTML displays "0 / {maxLength} characters"
 * 3. For any field name, rendered HTML contains JavaScript for real-time updates
 * 4. For any context (admin/public), rendered HTML uses appropriate styling
 * 5. For any field name, rendered HTML contains ARIA attributes for accessibility
 * 6. For any maxLength value, JavaScript updates counter on input event
 * 7. For any percentage >= 90%, counter changes to amber color
 * 8. For any percentage >= 100%, counter changes to red color
 * 9. For any percentage < 90%, counter uses default gray color
 * 10. For any field name with special characters, counter ID is sanitized
 */
class CharacterCounterDisplayPropertyTest extends TestCase
{
    /**
     * Property 22.1: For any field name, rendered HTML contains counter element with unique ID.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_html_contains_counter_with_unique_id(string $fieldName): void
    {
        $counter = new CharacterCounter();
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Counter ID should be based on field name
        $expectedId = 'counter-' . str_replace(['[', ']', '.'], ['_', '_', '_'], $fieldName);
        $this->assertStringContainsString("id=\"{$expectedId}\"", $html);
    }

    /**
     * Property 22.2: For any maxLength value, rendered HTML displays "0 / {maxLength} characters".
     *
     * @test
     * @dataProvider maxLengthProvider
     */
    public function property_rendered_html_displays_correct_format(int $maxLength): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('<span class="current-count">0</span>', $html);
        $this->assertStringContainsString("<span class=\"max-count\">{$maxLength}</span>", $html);
        $this->assertStringContainsString('characters', $html);
    }

    /**
     * Property 22.3: For any field name, rendered HTML contains JavaScript for real-time updates.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_html_contains_javascript(string $fieldName): void
    {
        $counter = new CharacterCounter();
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('</script>', $html);
        $this->assertStringContainsString('addEventListener', $html);
        $this->assertStringContainsString('input', $html);
        $this->assertStringContainsString('updateCount', $html);
    }

    /**
     * Property 22.4: For any context (admin/public), rendered HTML uses appropriate styling.
     *
     * @test
     * @dataProvider contextProvider
     */
    public function property_rendered_html_uses_context_styling(string $context, string $expectedClass): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength, $context);

        $this->assertStringContainsString($expectedClass, $html);
    }

    /**
     * Property 22.5: For any field name, rendered HTML contains ARIA attributes for accessibility.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_html_contains_aria_attributes(string $fieldName): void
    {
        $counter = new CharacterCounter();
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('aria-atomic="true"', $html);
    }

    /**
     * Property 22.6: For any maxLength value, JavaScript references correct maxLength.
     *
     * @test
     * @dataProvider maxLengthProvider
     */
    public function property_javascript_references_correct_max_length(int $maxLength): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString("const maxLength = {$maxLength};", $html);
    }

    /**
     * Property 22.7: For any field name, JavaScript queries correct field selector.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_javascript_queries_correct_field(string $fieldName): void
    {
        $counter = new CharacterCounter();
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString("querySelector('[name=\"{$fieldName}\"]')", $html);
    }

    /**
     * Property 22.8: For any percentage >= 90%, JavaScript adds amber color class.
     *
     * @test
     */
    public function property_javascript_handles_warning_threshold(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Check for 90% threshold logic
        $this->assertStringContainsString('percentage >= 90', $html);
        $this->assertStringContainsString('text-amber-500', $html);
    }

    /**
     * Property 22.9: For any percentage >= 100%, JavaScript adds red color class.
     *
     * @test
     */
    public function property_javascript_handles_danger_threshold(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Check for 100% threshold logic
        $this->assertStringContainsString('percentage >= 100', $html);
        $this->assertStringContainsString('text-red-500', $html);
    }

    /**
     * Property 22.10: For any field name with special characters, counter ID is sanitized.
     *
     * @test
     * @dataProvider specialCharacterFieldNameProvider
     */
    public function property_counter_id_is_sanitized(string $fieldName, string $expectedIdPart): void
    {
        $counter = new CharacterCounter();
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString("id=\"counter-{$expectedIdPart}\"", $html);
    }

    /**
     * Property 22.11: For any field name, JavaScript uses Unicode-aware character counting.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_javascript_uses_unicode_counting(string $fieldName): void
    {
        $counter = new CharacterCounter();
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        // Check for spread operator for Unicode-aware counting
        $this->assertStringContainsString('[...field.value].length', $html);
    }

    /**
     * Property 22.12: For any maxLength value, counter element contains both current and max spans.
     *
     * @test
     * @dataProvider maxLengthProvider
     */
    public function property_counter_contains_required_spans(int $maxLength): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('class="current-count"', $html);
        $this->assertStringContainsString('class="max-count"', $html);
    }

    /**
     * Property 22.13: For any context, dark mode classes are included.
     *
     * @test
     * @dataProvider contextProvider
     */
    public function property_dark_mode_classes_included(string $context): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength, $context);

        $this->assertStringContainsString('dark:', $html);
    }

    /**
     * Property 22.14: For any field name, JavaScript is wrapped in IIFE to avoid global scope pollution.
     *
     * @test
     */
    public function property_javascript_wrapped_in_iife(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('(function() {', $html);
        $this->assertStringContainsString('})();', $html);
    }

    /**
     * Property 22.15: For any field name, JavaScript waits for DOMContentLoaded.
     *
     * @test
     */
    public function property_javascript_waits_for_dom_ready(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('DOMContentLoaded', $html);
    }

    /**
     * Property 22.16: For any field name, JavaScript includes debouncing for performance.
     *
     * @test
     */
    public function property_javascript_includes_debouncing(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'test_field';
        $maxLength = 100;

        $html = $counter->render($fieldName, $maxLength);

        $this->assertStringContainsString('setTimeout', $html);
        $this->assertStringContainsString('clearTimeout', $html);
    }

    /**
     * Property 22.17: For any combination of parameters, all are applied correctly.
     *
     * @test
     */
    public function property_all_parameters_applied_correctly(): void
    {
        $counter = new CharacterCounter();
        $fieldName = 'description';
        $maxLength = 500;
        $context = 'public';

        $html = $counter->render($fieldName, $maxLength, $context);

        $this->assertStringContainsString('counter-description', $html);
        $this->assertStringContainsString('500', $html);
        $this->assertStringContainsString('text-gray-600', $html); // Public context
    }

    /**
     * Data provider for field names.
     *
     * @return array<array<string>>
     */
    public static function fieldNameProvider(): array
    {
        return [
            ['name'],
            ['description'],
            ['bio'],
            ['comment'],
            ['message'],
        ];
    }

    /**
     * Data provider for maxLength values.
     *
     * @return array<array<int>>
     */
    public static function maxLengthProvider(): array
    {
        return [
            [50],
            [100],
            [255],
            [500],
            [1000],
        ];
    }

    /**
     * Data provider for context values.
     *
     * @return array<array<string>>
     */
    public static function contextProvider(): array
    {
        return [
            ['admin', 'text-gray-500'],
            ['public', 'text-gray-600'],
        ];
    }

    /**
     * Data provider for field names with special characters.
     *
     * @return array<array<string>>
     */
    public static function specialCharacterFieldNameProvider(): array
    {
        return [
            ['user[name]', 'user_name_'],
            ['settings[email]', 'settings_email_'],
            ['data.field', 'data_field'],
            ['items[0][title]', 'items_0__title_'],
        ];
    }
}
