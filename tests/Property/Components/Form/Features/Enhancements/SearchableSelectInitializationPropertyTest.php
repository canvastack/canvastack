<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Searchable Select Initialization.
 *
 * Validates Requirements: 6.1
 *
 * Property 20: Searchable Select Initialization
 *
 * Universal Property:
 * For any select field registered as searchable, the component should generate
 * valid Choices.js initialization code that properly targets the field and
 * configures search functionality.
 *
 * Specific Properties:
 * 1. For any field name, registration stores the field in instances array
 * 2. For any field name, rendered script contains DOMContentLoaded event listener
 * 3. For any field name, rendered script contains querySelector for the field
 * 4. For any field name, rendered script contains Choices constructor call
 * 5. For any field name, rendered script contains searchEnabled: true in config
 * 6. For any options, configuration is properly JSON encoded
 * 7. For any multiple option, removeItemButton is set correctly
 * 8. For any placeholder option, placeholder config is set correctly
 * 9. For any registered instances, hasInstances returns true
 * 10. For no registered instances, hasInstances returns false
 * 11. For no registered instances, renderScript returns empty string
 * 12. For any field name with special characters, script handles it safely
 */
class SearchableSelectInitializationPropertyTest extends TestCase
{
    /**
     * Property 20.1: For any field name, registration stores the field in instances array.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_registration_stores_field_in_instances(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register($fieldName);

        $instances = $searchableSelect->getInstances();
        $this->assertArrayHasKey($fieldName, $instances);
    }

    /**
     * Property 20.2: For any field name, rendered script contains DOMContentLoaded event listener.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_script_contains_dom_content_loaded(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('DOMContentLoaded', $script);
        $this->assertStringContainsString('addEventListener', $script);
    }

    /**
     * Property 20.3: For any field name, rendered script contains querySelector for the field.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_script_contains_query_selector(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('querySelector', $script);
        $this->assertStringContainsString("select[name=\"{$fieldName}\"]", $script);
    }

    /**
     * Property 20.4: For any field name, rendered script contains Choices constructor call.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_script_contains_choices_constructor(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('new Choices', $script);
    }

    /**
     * Property 20.5: For any field name, rendered script contains searchEnabled: true in config.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_rendered_script_contains_search_enabled(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('"searchEnabled":true', $script);
    }

    /**
     * Property 20.6: For any options, configuration is properly JSON encoded.
     *
     * @test
     * @dataProvider optionsProvider
     */
    public function property_configuration_is_json_encoded(array $options): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('test_field', $options);

        $script = $searchableSelect->renderScript();

        // Script should contain valid JSON configuration
        $this->assertStringContainsString('{', $script);
        $this->assertStringContainsString('}', $script);

        // Extract JSON from script and validate it's valid JSON
        preg_match('/new Choices\([^,]+,\s*(\{[^}]+\})\)/', $script, $matches);
        if (!empty($matches[1])) {
            $json = $matches[1];
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded);
        }
    }

    /**
     * Property 20.7: For any multiple option, removeItemButton is set correctly.
     *
     * @test
     * @dataProvider multipleOptionProvider
     */
    public function property_multiple_option_sets_remove_item_button(bool $multiple): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('test_field', ['multiple' => $multiple]);

        $script = $searchableSelect->renderScript();

        $expectedValue = $multiple ? 'true' : 'false';
        $this->assertStringContainsString("\"removeItemButton\":{$expectedValue}", $script);
    }

    /**
     * Property 20.8: For any placeholder option, placeholder config is set correctly.
     *
     * @test
     * @dataProvider placeholderProvider
     */
    public function property_placeholder_option_is_configured(string $placeholderText): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('test_field', ['placeholder_text' => $placeholderText]);

        $script = $searchableSelect->renderScript();

        // Placeholder text should be JSON encoded in the script
        $encodedPlaceholder = json_encode($placeholderText);
        $this->assertStringContainsString("\"placeholderValue\":{$encodedPlaceholder}", $script);
    }

    /**
     * Property 20.9: For any registered instances, hasInstances returns true.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_has_instances_returns_true_when_registered(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();

        $this->assertFalse($searchableSelect->hasInstances());

        $searchableSelect->register($fieldName);

        $this->assertTrue($searchableSelect->hasInstances());
    }

    /**
     * Property 20.10: For no registered instances, hasInstances returns false.
     *
     * @test
     */
    public function property_has_instances_returns_false_when_empty(): void
    {
        $searchableSelect = new SearchableSelect();

        $this->assertFalse($searchableSelect->hasInstances());
    }

    /**
     * Property 20.11: For no registered instances, renderScript returns empty string.
     *
     * @test
     */
    public function property_render_script_returns_empty_when_no_instances(): void
    {
        $searchableSelect = new SearchableSelect();

        $script = $searchableSelect->renderScript();

        $this->assertEmpty($script);
    }

    /**
     * Property 20.12: For any field name with special characters, script handles it safely.
     *
     * @test
     * @dataProvider specialCharacterFieldNameProvider
     */
    public function property_special_characters_handled_safely(string $fieldName, string $safeVariableName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        // Variable name should be sanitized (brackets removed)
        $this->assertStringContainsString("select_{$safeVariableName}", $script);

        // Original field name should still be in querySelector
        $this->assertStringContainsString("select[name=\"{$fieldName}\"]", $script);
    }

    /**
     * Property 20.13: For any search placeholder option, it's included in config.
     *
     * @test
     * @dataProvider searchPlaceholderProvider
     */
    public function property_search_placeholder_is_configured(string $searchPlaceholder): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('test_field', ['search_placeholder' => $searchPlaceholder]);

        $script = $searchableSelect->renderScript();

        $encodedPlaceholder = json_encode($searchPlaceholder);
        $this->assertStringContainsString("\"searchPlaceholderValue\":{$encodedPlaceholder}", $script);
    }

    /**
     * Property 20.14: For any sort option, it's included in config.
     *
     * @test
     * @dataProvider sortOptionProvider
     */
    public function property_sort_option_is_configured(bool $sort): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('test_field', ['sort' => $sort]);

        $script = $searchableSelect->renderScript();

        $expectedValue = $sort ? 'true' : 'false';
        $this->assertStringContainsString("\"shouldSort\":{$expectedValue}", $script);
    }

    /**
     * Property 20.15: For multiple registered fields, all are initialized.
     *
     * @test
     */
    public function property_multiple_fields_all_initialized(): void
    {
        $searchableSelect = new SearchableSelect();
        $fields = ['country', 'province', 'city'];

        foreach ($fields as $field) {
            $searchableSelect->register($field);
        }

        $script = $searchableSelect->renderScript();

        // Each field should have its own initialization
        foreach ($fields as $field) {
            $this->assertStringContainsString("select[name=\"{$field}\"]", $script);
            $safeFieldName = str_replace(['[', ']'], ['_', ''], $field);
            $this->assertStringContainsString("select_{$safeFieldName}", $script);
        }
    }

    /**
     * Property 20.16: For any default options, default values are used.
     *
     * @test
     */
    public function property_default_options_are_used(): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register('test_field');

        $script = $searchableSelect->renderScript();

        // Default values
        $this->assertStringContainsString('"searchEnabled":true', $script);
        $this->assertStringContainsString('"searchPlaceholderValue":"Search..."', $script);
        $this->assertStringContainsString('"removeItemButton":false', $script);
        $this->assertStringContainsString('"shouldSort":true', $script);
        $this->assertStringContainsString('"placeholder":true', $script);
        $this->assertStringContainsString('"placeholderValue":"Select an option"', $script);
    }

    /**
     * Property 20.17: For any configuration, allowHTML is set to false for security.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_allow_html_is_false_for_security(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('"allowHTML":false', $script);
    }

    /**
     * Property 20.18: For any field, script is wrapped in script tags.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_script_is_wrapped_in_script_tags(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        // Script should contain asset loading tags and script tags
        $this->assertStringContainsString('<script>', $script);
        $this->assertStringContainsString('</script>', $script);
        // Should also contain asset loading (CSS and JS)
        $this->assertStringContainsString('choices.js', $script);
    }

    /**
     * Property 20.19: For any field, null check is performed before initialization.
     *
     * @test
     * @dataProvider fieldNameProvider
     */
    public function property_null_check_before_initialization(string $fieldName): void
    {
        $searchableSelect = new SearchableSelect();
        $searchableSelect->register($fieldName);

        $script = $searchableSelect->renderScript();

        $safeFieldName = str_replace(['[', ']'], ['_', ''], $fieldName);
        $this->assertStringContainsString("if (select_{$safeFieldName})", $script);
    }

    /**
     * Property 20.20: For any custom options, they override defaults.
     *
     * @test
     */
    public function property_custom_options_override_defaults(): void
    {
        $searchableSelect = new SearchableSelect();
        $customOptions = [
            'search_placeholder' => 'Type to search...',
            'placeholder_text' => 'Choose one',
            'sort' => false,
            'multiple' => true,
        ];

        $searchableSelect->register('test_field', $customOptions);

        $script = $searchableSelect->renderScript();

        $this->assertStringContainsString('"searchPlaceholderValue":"Type to search..."', $script);
        $this->assertStringContainsString('"placeholderValue":"Choose one"', $script);
        $this->assertStringContainsString('"shouldSort":false', $script);
        $this->assertStringContainsString('"removeItemButton":true', $script);
    }

    /**
     * Data provider for field names.
     *
     * @return array<array<string>>
     */
    public static function fieldNameProvider(): array
    {
        return [
            ['country'],
            ['province_id'],
            ['city'],
            ['category'],
            ['status'],
        ];
    }

    /**
     * Data provider for options.
     *
     * @return array<array<array>>
     */
    public static function optionsProvider(): array
    {
        return [
            [[]],
            [['multiple' => true]],
            [['placeholder_text' => 'Select...']],
            [['search_placeholder' => 'Search...', 'sort' => false]],
        ];
    }

    /**
     * Data provider for multiple option.
     *
     * @return array<array<bool>>
     */
    public static function multipleOptionProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Data provider for placeholder text.
     *
     * @return array<array<string>>
     */
    public static function placeholderProvider(): array
    {
        return [
            ['Select an option'],
            ['Choose one'],
            ['Pick a value'],
            ['-- Select --'],
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
            ['user[country]', 'user_country'],
            ['data[province_id]', 'data_province_id'],
            ['form[city]', 'form_city'],
        ];
    }

    /**
     * Data provider for search placeholder.
     *
     * @return array<array<string>>
     */
    public static function searchPlaceholderProvider(): array
    {
        return [
            ['Search...'],
            ['Type to search'],
            ['Find option'],
        ];
    }

    /**
     * Data provider for sort option.
     *
     * @return array<array<bool>>
     */
    public static function sortOptionProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
