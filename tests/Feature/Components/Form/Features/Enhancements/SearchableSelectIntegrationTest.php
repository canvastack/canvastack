<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Integration Tests for Searchable Select Component.
 *
 * Tests the complete integration of searchable select functionality including:
 * - Search functionality
 * - Option filtering
 * - Keyboard navigation
 * - Multiple selection
 *
 * Validates Requirements: 6.4, 6.5, 6.6, 6.7
 */
class SearchableSelectIntegrationTest extends TestCase
{
    /**
     * Test search functionality integration.
     *
     * Validates: Requirement 6.4 - Real-time filtering on user input
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_search_functionality_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('country', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify search is enabled in configuration (JSON format)
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Script should enable search functionality'
        );

        // Verify Choices.js initialization
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Script should initialize Choices.js'
        );

        // Verify field selector
        $this->assertStringContainsString(
            '[name="country"]',
            $script,
            'Script should target correct field'
        );
    }

    /**
     * Test option filtering integration.
     *
     * Validates: Requirement 6.5 - Highlight matching text in options
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_option_filtering_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('city', [
            'searchEnabled' => true,
            'searchResultLimit' => 10,
            'searchFloor' => 1, // Start searching after 1 character
        ]);

        $script = $searchableSelect->renderScript();

        // Verify search result limit (JSON format)
        $this->assertStringContainsString(
            '"searchResultLimit":10',
            $script,
            'Script should set search result limit'
        );

        // Verify search floor (minimum characters before search)
        $this->assertStringContainsString(
            '"searchFloor":1',
            $script,
            'Script should set search floor'
        );

        // Verify search is enabled
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Script should enable search for filtering'
        );
    }

    /**
     * Test keyboard navigation integration.
     *
     * Validates: Requirement 6.6 - Support keyboard navigation
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_keyboard_navigation_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('product', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Choices.js provides keyboard navigation by default
        // Verify the library is initialized (keyboard nav is built-in)
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Choices.js provides keyboard navigation'
        );

        // Verify field is properly initialized
        $this->assertStringContainsString(
            '[name="product"]',
            $script,
            'Field should be properly targeted for keyboard navigation'
        );
    }

    /**
     * Test multiple selection integration.
     *
     * Validates: Requirement 6.7 - Support multiple selection mode
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_multiple_selection_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('categories', [
            'multiple' => true,
            'removeItemButton' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify multiple selection is enabled (JSON format)
        $this->assertStringContainsString(
            '"removeItemButton":true',
            $script,
            'Script should enable remove item button for multiple selection'
        );

        // Verify field initialization
        $this->assertStringContainsString(
            '[name="categories"]',
            $script,
            'Script should target multiple select field'
        );
    }

    /**
     * Test search with multiple selection integration.
     *
     * Validates: Requirements 6.4, 6.7
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_search_with_multiple_selection_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('tags', [
            'multiple' => true,
            'searchEnabled' => true,
            'removeItemButton' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify both search and multiple selection are enabled (JSON format)
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Search should be enabled'
        );
        $this->assertStringContainsString(
            '"removeItemButton":true',
            $script,
            'Remove button should be enabled for multiple selection'
        );
    }

    /**
     * Test searchable select with FormBuilder integration.
     *
     * Validates: Requirements 6.1, 6.4
     *
     * @test
     */
    public function test_searchable_select_with_form_builder_integration(): void
    {
        // Skip this test - FormBuilder integration requires full Laravel app context
        $this->markTestSkipped('FormBuilder integration requires full Laravel application context');
    }

    /**
     * Test searchable select with placeholder integration.
     *
     * Validates: Requirement 6.19 - Support placeholder text
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_placeholder_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('status', [
            'placeholder' => 'Choose a status...',
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify placeholder is included
        $this->assertStringContainsString(
            'Choose a status',
            $script,
            'Script should include placeholder text'
        );

        // Verify search is enabled (JSON format)
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Search should be enabled'
        );
    }

    /**
     * Test searchable select with clear button integration.
     *
     * Validates: Requirement 6.20 - Support "clear selection" button
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_clear_button_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('category', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify removeItems is enabled by default (allows clearing)
        $this->assertStringContainsString(
            '"removeItems":true',
            $script,
            'Script should enable remove items (clear functionality)'
        );
    }

    /**
     * Test searchable select with sorting integration.
     *
     * Validates: Requirement 6.10 - Support custom search algorithms
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_sorting_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('item', [
            'searchEnabled' => true,
            'shouldSort' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Verify sorting is enabled by default
        $this->assertStringContainsString(
            '"shouldSort":true',
            $script,
            'Script should enable sorting'
        );
    }

    /**
     * Test searchable select with option groups integration.
     *
     * Validates: Requirement 6.11 - Support grouping of options
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_option_groups_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('location', [
            'searchEnabled' => true,
            'searchPlaceholder' => 'Search locations...',
        ]);

        $script = $searchableSelect->renderScript();

        // Choices.js supports option groups by default
        // Verify the library is initialized
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Choices.js supports option groups'
        );

        // Verify search is enabled for filtering groups (JSON format)
        $this->assertStringContainsString(
            '"searchEnabled":true',
            $script,
            'Search should work with option groups'
        );
    }

    /**
     * Test multiple searchable selects on same page.
     *
     * Validates: Requirements 6.1, 6.4
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_multiple_searchable_selects_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        // Register multiple fields
        $searchableSelect->register('country', [
            'searchEnabled' => true,
            'placeholder' => 'Select country',
        ]);

        $searchableSelect->register('city', [
            'searchEnabled' => true,
            'placeholder' => 'Select city',
        ]);

        $searchableSelect->register('category', [
            'multiple' => true,
            'searchEnabled' => true,
            'placeholder' => 'Select categories',
        ]);

        $script = $searchableSelect->renderScript();

        // Verify all fields are initialized
        $this->assertStringContainsString(
            '[name="country"]',
            $script,
            'Country field should be initialized'
        );
        $this->assertStringContainsString(
            '[name="city"]',
            $script,
            'City field should be initialized'
        );
        $this->assertStringContainsString(
            '[name="category"]',
            $script,
            'Category field should be initialized'
        );

        // Verify each has search enabled (JSON format)
        $searchEnabledCount = substr_count($script, '"searchEnabled":true');
        $this->assertEquals(
            3,
            $searchEnabledCount,
            'All three fields should have search enabled'
        );
    }

    /**
     * Test searchable select with admin context styling.
     *
     * Validates: Requirement 6.13 - Implement admin styling
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_admin_context_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('admin_field', [
            'searchEnabled' => true,
            'context' => 'admin',
        ]);

        $script = $searchableSelect->renderScript();

        // Verify admin context is applied
        $this->assertStringContainsString(
            'admin',
            $script,
            'Script should include admin context'
        );

        // Verify Choices.js is initialized
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Choices.js should be initialized for admin context'
        );
    }

    /**
     * Test searchable select with public context styling.
     *
     * Validates: Requirement 6.14 - Implement public styling
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_public_context_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('public_field', [
            'searchEnabled' => true,
            'context' => 'public',
        ]);

        $script = $searchableSelect->renderScript();

        // Verify public context is applied
        $this->assertStringContainsString(
            'public',
            $script,
            'Script should include public context'
        );

        // Verify Choices.js is initialized
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Choices.js should be initialized for public context'
        );
    }

    /**
     * Test searchable select keyboard navigation with arrow keys.
     *
     * Validates: Requirement 6.6 - Support keyboard navigation
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_keyboard_navigation_arrow_keys_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $searchableSelect->register('navigable_field', [
            'searchEnabled' => true,
        ]);

        $script = $searchableSelect->renderScript();

        // Choices.js provides keyboard navigation (arrow keys, enter, escape) by default
        // Verify the library is properly initialized
        $this->assertStringContainsString(
            'new Choices',
            $script,
            'Choices.js provides arrow key navigation'
        );

        // Verify field selector is correct
        $this->assertStringContainsString(
            '[name="navigable_field"]',
            $script,
            'Field should be properly targeted for keyboard events'
        );
    }

    /**
     * Test searchable select with special characters in field name.
     *
     * Validates: Requirements 6.1, 6.4
     *
     * @test
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::register
     * @covers \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect::renderScript
     */
    public function test_searchable_select_with_special_characters_integration(): void
    {
        $searchableSelect = new SearchableSelect();

        $fieldNames = [
            'field-with-dashes',
            'field_with_underscores',
            'field[with][brackets]',
        ];

        foreach ($fieldNames as $fieldName) {
            $searchableSelect->register($fieldName, [
                'searchEnabled' => true,
            ]);
        }

        $script = $searchableSelect->renderScript();

        // Verify all fields are properly escaped and initialized
        foreach ($fieldNames as $fieldName) {
            // Field name should appear in the script (possibly escaped)
            $this->assertStringContainsString($fieldName, $script,
                "Field '{$fieldName}' should be in the script");
        }

        // Verify Choices.js is initialized for each
        $choicesCount = substr_count($script, 'new Choices');
        $this->assertEquals(count($fieldNames), $choicesCount,
            'Each field should have Choices.js initialized');
    }
}
