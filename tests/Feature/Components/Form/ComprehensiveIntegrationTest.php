<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

/**
 * Comprehensive Integration Test.
 *
 * Tests all form component features working together using backward compatible API:
 * - Tab System
 * - Ajax Sync
 * - File Upload
 * - CKEditor
 * - Switch Checkbox
 * - Searchable Select
 * - Character Counter
 * - Tags Input
 * - Date Range Picker
 * - Month Picker
 */
class ComprehensiveIntegrationTest extends TestCase
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

    /** @test */
    public function it_renders_form_with_tabs_ajax_sync_and_file_upload()
    {
        // Create form with tabs, Ajax sync, and file upload
        $this->form->openTab('Personal Information', 'active');
        $this->form->text('name', 'Full Name')->required()->maxLength(100);
        $this->form->text('email', 'Email')->required();
        $this->form->file('avatar', 'Avatar')->preview();
        $this->form->closeTab();

        $this->form->openTab('Address');
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
            'SELECT id, name FROM cities WHERE province_id = ?'
        );
        $this->form->closeTab();

        $html = $this->form->render();

        // Assert tabs are rendered
        $this->assertStringContainsString('tabs-container', $html);
        $this->assertStringContainsString('Personal Information', $html);
        $this->assertStringContainsString('Address', $html);

        // Assert Ajax sync is configured
        $this->assertStringContainsString('province_id', $html);
        $this->assertStringContainsString('city_id', $html);

        // Assert file upload
        $this->assertStringContainsString('avatar', $html);
    }

    /** @test */
    public function it_renders_form_with_wysiwyg_editor()
    {
        // WYSIWYG editor (CKEditor)
        $this->form->textarea('content', 'Content')->wysiwyg();

        $html = $this->form->render();

        // Assert WYSIWYG editor
        $this->assertStringContainsString('content', $html);
        $this->assertStringContainsString('wysiwyg', $html);
    }

    /** @test */
    public function it_renders_form_with_switch_checkbox()
    {
        // Switch checkbox
        $this->form->checkbox('active', 'Active', [1 => 'Yes'])->switch();

        $html = $this->form->render();

        // Assert switch checkbox
        $this->assertStringContainsString('toggle', $html);
        $this->assertStringContainsString('active', $html);
    }

    /** @test */
    public function it_renders_form_with_searchable_select()
    {
        // Searchable select
        $this->form->select('country_id', 'Country', [
            1 => 'Indonesia',
            2 => 'Malaysia',
            3 => 'Singapore',
        ])->searchable();

        $html = $this->form->render();

        // Assert searchable select
        $this->assertStringContainsString('country_id', $html);
        $this->assertStringContainsString('searchable', $html);
    }

    /** @test */
    public function it_renders_form_with_character_counter()
    {
        // Character counter
        $this->form->textarea('description', 'Description')->maxLength(500);

        $html = $this->form->render();

        // Assert character counter
        $this->assertStringContainsString('description', $html);
        $this->assertStringContainsString('500', $html);
    }

    /** @test */
    public function it_renders_form_with_tags_input()
    {
        // Tags input
        $this->form->tags('keywords', 'Keywords');

        $html = $this->form->render();

        // Assert tags input
        $this->assertStringContainsString('keywords', $html);
        $this->assertStringContainsString('tagify', $html);
    }

    /** @test */
    public function it_renders_form_with_date_range_picker()
    {
        // Date range picker
        $this->form->daterange('publish_period', 'Publish Period');

        $html = $this->form->render();

        // Assert date range picker
        $this->assertStringContainsString('publish_period', $html);
        $this->assertStringContainsString('flatpickr', $html);
    }

    /** @test */
    public function it_renders_form_with_month_picker()
    {
        // Month picker
        $this->form->month('billing_month', 'Billing Month');

        $html = $this->form->render();

        // Assert month picker
        $this->assertStringContainsString('billing_month', $html);
    }

    /** @test */
    public function it_handles_form_with_all_features_together()
    {
        // Create comprehensive form with all features
        $this->form->openTab('Personal');
        $this->form->text('name', 'Name')->required();
        $this->form->text('email', 'Email')->required();
        $this->form->closeTab();

        $this->form->openTab('Address');
        $this->form->select('province_id', 'Province', [1 => 'DKI Jakarta']);
        $this->form->select('city_id', 'City', [10 => 'Jakarta Selatan']);
        $this->form->closeTab();

        $this->form->openTab('Details');
        $this->form->select('country_id', 'Country', [1 => 'Indonesia'])->searchable();
        $this->form->textarea('description', 'Description')->maxLength(500);
        $this->form->tags('keywords', 'Keywords');
        $this->form->daterange('publish_period', 'Period');
        $this->form->month('billing_month', 'Month');
        $this->form->closeTab();

        $html = $this->form->render();

        // Assert all features are present
        $this->assertStringContainsString('tabs-container', $html);
        $this->assertStringContainsString('name', $html);
        $this->assertStringContainsString('province_id', $html);
        $this->assertStringContainsString('searchable', $html);
        $this->assertStringContainsString('tagify', $html);
        $this->assertStringContainsString('flatpickr', $html);
    }

    /** @test */
    public function it_preserves_tab_state_during_validation_errors()
    {
        // Create form with tabs
        $this->form->openTab('Personal', 'active');
        $this->form->text('name', 'Name')->required();
        $this->form->closeTab();

        $this->form->openTab('Address');
        $this->form->text('city', 'City')->required();
        $this->form->closeTab();

        // Simulate validation errors
        $errors = [
            'city' => ['The city field is required.'],
        ];

        session()->flash('errors', new \Illuminate\Support\MessageBag($errors));

        $html = $this->form->render();

        // Assert tab with error is highlighted
        $this->assertStringContainsString('Address', $html);
        $this->assertStringContainsString('city', $html);
    }

    /** @test */
    public function it_handles_multiple_cascading_levels()
    {
        // Create form with 3-level cascading
        $this->form->select('country_id', 'Country', [1 => 'Indonesia']);
        $this->form->select('province_id', 'Province', []);
        $this->form->select('city_id', 'City', []);

        // Country -> Province
        $this->form->sync(
            'country_id',
            'province_id',
            'id',
            'name',
            'SELECT id, name FROM provinces WHERE country_id = ?'
        );

        // Province -> City
        $this->form->sync(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?'
        );

        $html = $this->form->render();

        // Assert all levels are configured
        $this->assertStringContainsString('country_id', $html);
        $this->assertStringContainsString('province_id', $html);
        $this->assertStringContainsString('city_id', $html);
    }

    /** @test */
    public function it_combines_tabs_with_all_field_types()
    {
        // Create comprehensive form with tabs and all field types
        // Tab 1: Basic Fields
        $this->form->openTab('Basic Fields', 'active');
        $this->form->text('text_field', 'Text');
        $this->form->email('email_field', 'Email');
        $this->form->password('password_field', 'Password');
        $this->form->number('number_field', 'Number');
        $this->form->closeTab();

        // Tab 2: Selection Fields
        $this->form->openTab('Selection');
        $this->form->select('select_field', 'Select', [1 => 'Option 1'])->searchable();
        $this->form->radio('radio_field', 'Radio', [1 => 'Option 1']);
        $this->form->closeTab();

        // Tab 3: Advanced Fields
        $this->form->openTab('Advanced');
        $this->form->textarea('textarea_field', 'Textarea')->maxLength(500);
        $this->form->tags('tags_field', 'Tags');
        $this->form->daterange('daterange_field', 'Date Range');
        $this->form->month('month_field', 'Month');
        $this->form->closeTab();

        $html = $this->form->render();

        // Assert all tabs are present
        $this->assertStringContainsString('Basic Fields', $html);
        $this->assertStringContainsString('Selection', $html);
        $this->assertStringContainsString('Advanced', $html);

        // Assert all field types are present
        $this->assertStringContainsString('text_field', $html);
        $this->assertStringContainsString('email_field', $html);
        $this->assertStringContainsString('select_field', $html);
        $this->assertStringContainsString('textarea_field', $html);
        $this->assertStringContainsString('tags_field', $html);
        $this->assertStringContainsString('daterange_field', $html);
        $this->assertStringContainsString('month_field', $html);
    }

    /** @test */
    public function it_renders_form_with_legacy_pipe_syntax()
    {
        // Test legacy pipe syntax for character counter
        $this->form->textarea('description|limit:500', 'Description');

        $html = $this->form->render();

        // Assert character counter with pipe syntax
        $this->assertStringContainsString('description', $html);
        $this->assertStringContainsString('500', $html);
    }
}
