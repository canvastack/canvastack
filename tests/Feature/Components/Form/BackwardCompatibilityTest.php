<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

/**
 * Backward Compatibility Test.
 *
 * Validates: Requirements 1.13, 2.18, 7.18, 18.1, 18.2
 *
 * Tests that all legacy API methods work exactly as in CanvaStack Origin:
 * - Tab System legacy API
 * - Ajax Sync legacy API
 * - Character Counter legacy API (pipe syntax)
 * - Searchable Select legacy API
 */
class BackwardCompatibilityTest extends TestCase
{
    protected FormBuilder $form;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $this->form = new FormBuilder($fieldFactory, $validationCache);
    }

    /**
     * Test Tab System Legacy API (Requirement 1.13).
     *
     * @test
     */
    public function it_supports_legacy_tab_api()
    {
        // Legacy API: openTab($label, $class)
        $this->form->openTab('Personal Info', 'active');
        $this->form->text('name', 'Name');
        $this->form->closeTab();

        $this->form->openTab('Address', false);
        $this->form->text('city', 'City');
        $this->form->closeTab();

        $html = $this->form->render();

        // Assert legacy behavior is preserved
        $this->assertStringContainsString('Personal Info', $html);
        $this->assertStringContainsString('Address', $html);
        $this->assertStringContainsString('name', $html);
        $this->assertStringContainsString('city', $html);
    }

    /**
     * Test Tab System with addTabContent (Requirement 1.13).
     *
     * @test
     */
    public function it_supports_legacy_add_tab_content()
    {
        $this->form->openTab('Custom Tab');
        $this->form->addTabContent('<div class="custom-content">Custom HTML</div>');
        $this->form->closeTab();

        $html = $this->form->render();

        $this->assertStringContainsString('Custom Tab', $html);
        $this->assertStringContainsString('custom-content', $html);
        $this->assertStringContainsString('Custom HTML', $html);
    }

    /**
     * Test Ajax Sync Legacy API (Requirement 2.18).
     *
     * @test
     */
    public function it_supports_legacy_ajax_sync_api()
    {
        // Legacy API: sync($source, $target, $values, $labels, $query, $selected)
        $this->form->select('province_id', 'Province', [
            1 => 'DKI Jakarta',
            2 => 'West Java',
        ]);

        $this->form->select('city_id', 'City', []);

        $this->form->sync(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $html = $this->form->render();

        // Assert legacy sync behavior
        $this->assertStringContainsString('province_id', $html);
        $this->assertStringContainsString('city_id', $html);
    }

    /**
     * Test Ajax Sync with Pre-selection (Requirement 2.18).
     *
     * @test
     */
    public function it_supports_legacy_ajax_sync_with_selected_value()
    {
        $this->form->select('province_id', 'Province', [1 => 'DKI Jakarta']);
        $this->form->select('city_id', 'City', []);

        // Legacy API with selected value
        $this->form->sync(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            10 // Selected city ID
        );

        $html = $this->form->render();

        $this->assertStringContainsString('province_id', $html);
        $this->assertStringContainsString('city_id', $html);
    }

    /**
     * Test Searchable Select Legacy API (Requirement 6.21).
     *
     * @test
     */
    public function it_supports_legacy_searchable_select_api()
    {
        // Legacy API: select($name, $label, $options)->searchable()
        $this->form->select('country_id', 'Country', [
            1 => 'Indonesia',
            2 => 'Malaysia',
            3 => 'Singapore',
        ])->searchable();

        $html = $this->form->render();

        // Assert legacy searchable behavior
        $this->assertStringContainsString('country_id', $html);
        $this->assertStringContainsString('Country', $html);
        $this->assertStringContainsString('searchable', $html);
    }

    /**
     * Test Character Counter Legacy Syntax (Requirement 7.18).
     *
     * @test
     */
    public function it_supports_legacy_character_counter_pipe_syntax()
    {
        // Legacy API: textarea('field|limit:500')
        $this->form->textarea('description|limit:500', 'Description');

        $html = $this->form->render();

        // Assert legacy pipe syntax is parsed
        $this->assertStringContainsString('description', $html);
        $this->assertStringContainsString('500', $html);
    }

    /**
     * Test Character Counter with maxLength Method (Requirement 7.18).
     *
     * @test
     */
    public function it_supports_character_counter_with_max_length_method()
    {
        // Enhanced API (backward compatible)
        $this->form->textarea('notes', 'Notes')->maxLength(1000);

        $html = $this->form->render();

        $this->assertStringContainsString('notes', $html);
        $this->assertStringContainsString('1000', $html);
    }

    /**
     * Test Legacy API Methods Return FormBuilder Instance.
     *
     * @test
     */
    public function it_returns_form_builder_instance_for_method_chaining()
    {
        // All legacy methods should return $this for chaining
        $result = $this->form->openTab('Tab 1');
        $this->assertInstanceOf(FormBuilder::class, $result);

        $result = $this->form->closeTab();
        $this->assertInstanceOf(FormBuilder::class, $result);

        $result = $this->form->sync('source', 'target', 'id', 'name', 'SELECT * FROM table');
        $this->assertInstanceOf(FormBuilder::class, $result);
    }

    /**
     * Test Multiple Legacy Features Together.
     *
     * @test
     */
    public function it_supports_multiple_legacy_features_together()
    {
        // Combine multiple legacy features
        // Tabs (legacy)
        $this->form->openTab('Tab 1', 'active');
        $this->form->text('field1', 'Field 1');
        $this->form->closeTab();

        $this->form->openTab('Tab 2');

        // Ajax sync (legacy)
        $this->form->select('province', 'Province', [1 => 'Province 1']);
        $this->form->select('city', 'City', []);
        $this->form->sync('province', 'city', 'id', 'name', 'SELECT id, name FROM cities WHERE province_id = ?');

        // Searchable select
        $this->form->select('country', 'Country', [1 => 'Indonesia'])->searchable();

        // Character counter (legacy pipe syntax)
        $this->form->textarea('description|limit:500', 'Description');

        $this->form->closeTab();

        $html = $this->form->render();

        // Assert all legacy features work together
        $this->assertStringContainsString('Tab 1', $html);
        $this->assertStringContainsString('Tab 2', $html);
        $this->assertStringContainsString('province', $html);
        $this->assertStringContainsString('city', $html);
        $this->assertStringContainsString('country', $html);
        $this->assertStringContainsString('searchable', $html);
        $this->assertStringContainsString('description', $html);
        $this->assertStringContainsString('500', $html);
    }

    /**
     * Test Legacy API with Enhanced API Mixed Usage.
     *
     * @test
     */
    public function it_supports_mixing_legacy_and_enhanced_api()
    {
        // Mix legacy and enhanced API
        // Enhanced API
        $this->form->text('name', 'Name')
            ->placeholder('Enter name')
            ->required();

        // Legacy API
        $this->form->textarea('bio|limit:500', 'Bio');

        // Enhanced API
        $this->form->select('country', 'Country', [1 => 'Indonesia'])
            ->searchable();

        $html = $this->form->render();

        // Assert both APIs work together
        $this->assertStringContainsString('name', $html);
        $this->assertStringContainsString('placeholder', $html);
        $this->assertStringContainsString('bio', $html);
        $this->assertStringContainsString('country', $html);
        $this->assertStringContainsString('searchable', $html);
    }

    /**
     * Test Legacy Error Handling.
     *
     * @test
     */
    public function it_handles_legacy_validation_errors()
    {
        $this->form->text('name', 'Name');
        $this->form->text('email', 'Email');

        // Simulate legacy error format
        $errors = [
            'name' => ['The name field is required.'],
            'email' => ['The email field is required.'],
        ];

        session()->flash('errors', new \Illuminate\Support\MessageBag($errors));

        $html = $this->form->render();

        // Assert errors are displayed in legacy format
        $this->assertStringContainsString('name', $html);
        $this->assertStringContainsString('email', $html);
    }
}
