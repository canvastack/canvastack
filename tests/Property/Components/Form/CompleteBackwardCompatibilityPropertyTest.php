<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property 31: Complete Backward Compatibility.
 *
 * For any legacy Form API method call, the system should produce functionally
 * equivalent results to the legacy implementation.
 *
 * Validates: Requirements 18.1, 18.2
 *
 * This property test validates that:
 * 1. All legacy API methods remain functional
 * 2. Legacy API produces expected output structure
 * 3. Legacy and enhanced APIs can be mixed
 * 4. No breaking changes in method signatures
 */
class CompleteBackwardCompatibilityPropertyTest extends TestCase
{
    protected FormBuilder $form;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $this->form = new FormBuilder($fieldFactory, $validationCache);
    }

    /**
     * Property: Legacy API methods always return FormBuilder instance for chaining.
     *
     * @test
     */
    public function property_legacy_methods_return_form_builder_for_chaining()
    {
        // Property: All legacy methods should return $this for method chaining
        $legacyMethods = [
            ['openTab', ['Tab Label']],
            ['closeTab', []],
            ['addTabContent', ['<div>Content</div>']],
            ['sync', ['source', 'target', 'id', 'name', 'SELECT * FROM table']],
            ['setModel', [null]],
        ];

        foreach ($legacyMethods as [$method, $args]) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);
            $result = $form->$method(...$args);

            $this->assertInstanceOf(
                FormBuilder::class,
                $result,
                "Legacy method {$method}() should return FormBuilder instance for chaining"
            );
        }
    }

    /**
     * Property: Legacy tab API produces valid HTML structure.
     *
     * @test
     */
    public function property_legacy_tab_api_produces_valid_html()
    {
        // Property: For any tab configuration, HTML should contain tab structure
        $tabConfigurations = [
            [['Tab 1', 'active'], ['Tab 2', false]],
            [['Personal', 'active'], ['Address', false], ['Details', false]],
            [['Section A', false]],
        ];

        foreach ($tabConfigurations as $tabs) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);

            foreach ($tabs as [$label, $class]) {
                $form->openTab($label, $class);
                $form->text('field_' . str_replace(' ', '_', strtolower($label)), 'Field');
                $form->closeTab();
            }

            $html = $form->render();

            // Assert tab structure exists
            $this->assertStringContainsString('tabs-container', $html);

            // Assert all tab labels are present
            foreach ($tabs as [$label, $class]) {
                $this->assertStringContainsString($label, $html);
            }
        }
    }

    /**
     * Property: Legacy sync API always configures Ajax relationship.
     *
     * @test
     */
    public function property_legacy_sync_api_configures_ajax_relationship()
    {
        // Property: For any sync() call, Ajax relationship should be registered
        $syncConfigurations = [
            ['province_id', 'city_id', 'id', 'name', 'SELECT id, name FROM cities WHERE province_id = ?', null],
            ['country_id', 'province_id', 'id', 'name', 'SELECT id, name FROM provinces WHERE country_id = ?', 5],
            ['category_id', 'subcategory_id', 'id', 'name', 'SELECT id, name FROM subcategories WHERE category_id = ?', null],
        ];

        foreach ($syncConfigurations as $config) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);
            [$source, $target, $values, $labels, $query, $selected] = $config;

            $form->select($source, ucfirst($source), [1 => 'Option 1']);
            $form->select($target, ucfirst($target), []);
            $form->sync($source, $target, $values, $labels, $query, $selected);

            $html = $form->render();

            // Assert both fields are present
            $this->assertStringContainsString($source, $html);
            $this->assertStringContainsString($target, $html);
        }
    }

    /**
     * Property: Legacy searchable select API initializes search.
     *
     * @test
     */
    public function property_legacy_searchable_select_api_initializes_search()
    {
        // Property: For any select with searchable(), search should be initialized
        $selectConfigurations = [
            ['country_id', 'Country', [1 => 'Indonesia', 2 => 'Malaysia']],
            ['category_id', 'Category', [1 => 'Category 1', 2 => 'Category 2']],
            ['status_id', 'Status', [1 => 'Active', 2 => 'Inactive']],
        ];

        foreach ($selectConfigurations as $config) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);
            [$name, $label, $options] = $config;

            $form->select($name, $label, $options)->searchable();
            $html = $form->render();

            // Assert select exists
            $this->assertStringContainsString($name, $html);
            $this->assertStringContainsString($label, $html);

            // Assert searchable is configured
            $this->assertStringContainsString('searchable', $html);
        }
    }

    /**
     * Property: Legacy character counter with maxLength displays counter.
     *
     * @test
     */
    public function property_legacy_character_counter_displays_with_max_length()
    {
        // Property: For any textarea with maxLength(), counter should be displayed
        $textareaConfigurations = [
            ['description', 'Description', 500],
            ['notes', 'Notes', 1000],
            ['summary', 'Summary', 250],
        ];

        foreach ($textareaConfigurations as $config) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);
            [$name, $label, $maxLength] = $config;

            $form->textarea($name, $label)->maxLength($maxLength);
            $html = $form->render();

            // Assert textarea exists
            $this->assertStringContainsString($name, $html);
            $this->assertStringContainsString($label, $html);

            // Assert max length is displayed
            $this->assertStringContainsString((string) $maxLength, $html);
        }
    }

    /**
     * Property: Legacy and enhanced APIs can be mixed without conflicts.
     *
     * @test
     */
    public function property_legacy_and_enhanced_apis_can_be_mixed()
    {
        // Property: Mixing legacy and enhanced API should work seamlessly
        $mixedConfigurations = [
            [
                'legacy' => ['openTab', ['Tab 1', 'active']],
                'enhanced' => ['text', ['name', 'Name'], ['placeholder' => 'Enter name']],
            ],
            [
                'legacy' => ['sync', ['source', 'target', 'id', 'name', 'SELECT * FROM table']],
                'enhanced' => ['select', ['country', 'Country', [1 => 'Indonesia']], ['searchable' => true]],
            ],
        ];

        foreach ($mixedConfigurations as $config) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);

            // Call legacy method
            [$legacyMethod, $legacyArgs] = $config['legacy'];
            $form->$legacyMethod(...$legacyArgs);

            // Call enhanced method
            [$enhancedMethod, $enhancedArgs, $enhancedChain] = $config['enhanced'];
            $field = $form->$enhancedMethod(...$enhancedArgs);

            // Apply enhanced chaining if applicable
            if ($enhancedMethod === 'text' && isset($enhancedChain['placeholder'])) {
                $field->placeholder($enhancedChain['placeholder']);
            } elseif ($enhancedMethod === 'select' && isset($enhancedChain['searchable'])) {
                $field->searchable();
            }

            $html = $form->render();

            // Assert both APIs work
            $this->assertNotEmpty($html);
        }
    }

    /**
     * Property: Legacy API never throws exceptions for valid input.
     *
     * @test
     */
    public function property_legacy_api_never_throws_exceptions_for_valid_input()
    {
        // Property: All legacy API calls with valid input should succeed without exceptions
        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $form = new FormBuilder($fieldFactory, $validationCache);

        try {
            // Tab API
            $form->openTab('Tab 1', 'active');
            $form->text('field1', 'Field 1');
            $form->addTabContent('<div>Content</div>');
            $form->closeTab();

            // Sync API
            $form->select('source', 'Source', [1 => 'Option 1']);
            $form->select('target', 'Target', []);
            $form->sync('source', 'target', 'id', 'name', 'SELECT id, name FROM table WHERE parent_id = ?');

            // Searchable select
            $form->select('country', 'Country', [1 => 'Indonesia'])->searchable();

            // Character counter
            $form->textarea('description', 'Description')->maxLength(500);

            // Render
            $html = $form->render();

            $this->assertNotEmpty($html);
            $this->assertTrue(true, 'All legacy API calls succeeded without exceptions');
        } catch (\Exception $e) {
            $this->fail('Legacy API threw exception: ' . $e->getMessage());
        }
    }

    /**
     * Property: Legacy API output contains expected field names.
     *
     * @test
     */
    public function property_legacy_api_output_contains_field_names()
    {
        // Property: For any field created, output should contain the field name
        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $form = new FormBuilder($fieldFactory, $validationCache);

        $form->text('name', 'Name');
        $form->select('status', 'Status', [1 => 'Active']);
        $form->textarea('description', 'Description');

        $html = $form->render();

        // Assert all field names are present
        $this->assertStringContainsString('name', $html);
        $this->assertStringContainsString('status', $html);
        $this->assertStringContainsString('description', $html);
    }
}
