<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Fields;

use Canvastack\Canvastack\Components\Form\Fields\CheckboxField;
use Canvastack\Canvastack\Components\Form\Fields\DateField;
use Canvastack\Canvastack\Components\Form\Fields\DateTimeField;
use Canvastack\Canvastack\Components\Form\Fields\EmailField;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\Fields\FileField;
use Canvastack\Canvastack\Components\Form\Fields\HiddenField;
use Canvastack\Canvastack\Components\Form\Fields\NumberField;
use Canvastack\Canvastack\Components\Form\Fields\PasswordField;
use Canvastack\Canvastack\Components\Form\Fields\RadioField;
use Canvastack\Canvastack\Components\Form\Fields\SelectField;
use Canvastack\Canvastack\Components\Form\Fields\TextareaField;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Fields\TimeField;
use Canvastack\Canvastack\Tests\TestCase;
use InvalidArgumentException;

class FieldFactoryTest extends TestCase
{
    protected FieldFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new FieldFactory();
    }

    public function test_can_create_text_field(): void
    {
        $field = $this->factory->make('text', 'username', 'Username');

        $this->assertInstanceOf(TextField::class, $field);
    }

    public function test_can_create_email_field(): void
    {
        $field = $this->factory->make('email', 'email', 'Email');

        $this->assertInstanceOf(EmailField::class, $field);
    }

    public function test_can_create_password_field(): void
    {
        $field = $this->factory->make('password', 'password', 'Password');

        $this->assertInstanceOf(PasswordField::class, $field);
    }

    public function test_can_create_number_field(): void
    {
        $field = $this->factory->make('number', 'age', 'Age');

        $this->assertInstanceOf(NumberField::class, $field);
    }

    public function test_can_create_textarea_field(): void
    {
        $field = $this->factory->make('textarea', 'description', 'Description');

        $this->assertInstanceOf(TextareaField::class, $field);
    }

    public function test_can_create_select_field(): void
    {
        $field = $this->factory->make('select', 'status', 'Status', []);

        $this->assertInstanceOf(SelectField::class, $field);
    }

    public function test_can_create_checkbox_field(): void
    {
        $field = $this->factory->make('checkbox', 'interests', 'Interests', []);

        $this->assertInstanceOf(CheckboxField::class, $field);
    }

    public function test_can_create_radio_field(): void
    {
        $field = $this->factory->make('radio', 'gender', 'Gender', []);

        $this->assertInstanceOf(RadioField::class, $field);
    }

    public function test_can_create_file_field(): void
    {
        $field = $this->factory->make('file', 'avatar', 'Avatar');

        $this->assertInstanceOf(FileField::class, $field);
    }

    public function test_can_create_date_field(): void
    {
        $field = $this->factory->make('date', 'birth_date', 'Birth Date');

        $this->assertInstanceOf(DateField::class, $field);
    }

    public function test_can_create_datetime_field(): void
    {
        $field = $this->factory->make('datetime', 'created_at', 'Created At');

        $this->assertInstanceOf(DateTimeField::class, $field);
    }

    public function test_can_create_time_field(): void
    {
        $field = $this->factory->make('time', 'start_time', 'Start Time');

        $this->assertInstanceOf(TimeField::class, $field);
    }

    public function test_can_create_hidden_field(): void
    {
        $field = $this->factory->make('hidden', 'id', null, '123');

        $this->assertInstanceOf(HiddenField::class, $field);
    }

    public function test_throws_exception_for_unknown_field_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown field type: unknown');

        $this->factory->make('unknown', 'field', 'Field');
    }

    public function test_can_register_custom_field_type(): void
    {
        $this->factory->register('custom', TextField::class);

        $field = $this->factory->make('custom', 'custom_field', 'Custom');

        $this->assertInstanceOf(TextField::class, $field);
    }

    public function test_get_all_registered_field_types(): void
    {
        $types = $this->factory->getFieldTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('text', $types);
        $this->assertArrayHasKey('select', $types);
        $this->assertArrayHasKey('email', $types);
    }
}
