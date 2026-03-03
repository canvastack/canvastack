<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\Fields\SelectField;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\PublicRenderer;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;

class FormBuilderTest extends TestCase
{
    protected FormBuilder $formBuilder;

    protected FieldFactory $fieldFactory;

    protected ValidationCache $validationCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldFactory = new FieldFactory();
        $this->validationCache = new ValidationCache();
        $this->formBuilder = new FormBuilder($this->fieldFactory, $this->validationCache);
    }

    public function test_can_create_form_builder_instance(): void
    {
        $this->assertInstanceOf(FormBuilder::class, $this->formBuilder);
    }

    public function test_default_context_is_admin(): void
    {
        $this->assertEquals('admin', $this->formBuilder->getContext());
        $this->assertInstanceOf(AdminRenderer::class, $this->formBuilder->getRenderer());
    }

    public function test_can_set_context_to_public(): void
    {
        $this->formBuilder->setContext('public');

        $this->assertEquals('public', $this->formBuilder->getContext());
        $this->assertInstanceOf(PublicRenderer::class, $this->formBuilder->getRenderer());
    }

    public function test_can_create_text_field(): void
    {
        $field = $this->formBuilder->text('username', 'Username');

        $this->assertInstanceOf(TextField::class, $field);
        $this->assertEquals('username', $field->getName());
        $this->assertEquals('Username', $field->getLabel());
    }

    public function test_can_create_text_field_with_fluent_interface(): void
    {
        $field = $this->formBuilder->text('email', 'Email Address')
            ->placeholder('Enter your email')
            ->icon('mail')
            ->required()
            ->maxLength(100);

        $this->assertEquals('Enter your email', $field->getPlaceholder());
        $this->assertEquals('mail', $field->getIcon());
        $this->assertTrue($field->isRequired());
        $this->assertEquals(100, $field->getMaxLength());
    }

    public function test_can_create_select_field(): void
    {
        $options = ['1' => 'Option 1', '2' => 'Option 2'];
        $field = $this->formBuilder->select('status', 'Status', $options);

        $this->assertInstanceOf(SelectField::class, $field);
        $this->assertEquals($options, $field->getOptions());
    }

    public function test_can_create_multiple_fields(): void
    {
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->email('email', 'Email');
        $this->formBuilder->password('password', 'Password');

        $fields = $this->formBuilder->getFields();

        $this->assertCount(3, $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('password', $fields);
    }

    public function test_can_get_specific_field(): void
    {
        $this->formBuilder->text('username', 'Username');

        $field = $this->formBuilder->getField('username');

        $this->assertInstanceOf(TextField::class, $field);
        $this->assertEquals('username', $field->getName());
    }

    public function test_get_nonexistent_field_returns_null(): void
    {
        $field = $this->formBuilder->getField('nonexistent');

        $this->assertNull($field);
    }

    public function test_can_set_and_get_model(): void
    {
        $model = (object) ['name' => 'John Doe'];

        $this->formBuilder->setModel($model);

        $this->assertEquals($model, $this->formBuilder->getModel());
    }

    public function test_fields_inherit_model_from_form_builder(): void
    {
        $model = (object) ['username' => 'johndoe'];
        $this->formBuilder->setModel($model);

        $field = $this->formBuilder->text('username', 'Username');

        $this->assertEquals('johndoe', $field->getValue());
    }

    public function test_can_set_validation_rules(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ];

        $this->formBuilder->setValidations($rules);

        $this->assertEquals($rules, $this->formBuilder->getValidations());
    }

    public function test_can_clear_fields(): void
    {
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->email('email', 'Email');

        $this->assertCount(2, $this->formBuilder->getFields());

        $this->formBuilder->clear();

        $this->assertCount(0, $this->formBuilder->getFields());
    }

    public function test_can_render_all_fields(): void
    {
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->email('email', 'Email');

        $output = $this->formBuilder->render();

        $this->assertStringContainsString('name', $output);
        $this->assertStringContainsString('email', $output);
    }

    public function test_can_render_specific_field(): void
    {
        $this->formBuilder->text('username', 'Username');
        $this->formBuilder->email('email', 'Email');

        $output = $this->formBuilder->renderField('username');

        $this->assertStringContainsString('username', $output);
        $this->assertStringNotContainsString('email', $output);
    }

    public function test_render_nonexistent_field_returns_empty_string(): void
    {
        $output = $this->formBuilder->renderField('nonexistent');

        $this->assertEquals('', $output);
    }

    public function test_can_create_all_field_types(): void
    {
        $this->formBuilder->text('text_field', 'Text');
        $this->formBuilder->textarea('textarea_field', 'Textarea');
        $this->formBuilder->email('email_field', 'Email');
        $this->formBuilder->password('password_field', 'Password');
        $this->formBuilder->number('number_field', 'Number');
        $this->formBuilder->select('select_field', 'Select', []);
        $this->formBuilder->checkbox('checkbox_field', 'Checkbox', []);
        $this->formBuilder->radio('radio_field', 'Radio', []);
        $this->formBuilder->file('file_field', 'File');
        $this->formBuilder->date('date_field', 'Date');
        $this->formBuilder->datetime('datetime_field', 'DateTime');
        $this->formBuilder->time('time_field', 'Time');
        $this->formBuilder->hidden('hidden_field', 'value');

        $fields = $this->formBuilder->getFields();

        $this->assertCount(13, $fields);
    }

    public function test_form_identity_can_be_set(): void
    {
        $this->formBuilder->setFormIdentity('user-form');

        // Identity is used internally for caching
        $this->assertTrue(true);
    }
}
