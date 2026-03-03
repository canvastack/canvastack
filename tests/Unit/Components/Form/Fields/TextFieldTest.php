<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Fields;

use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Tests\TestCase;

class TextFieldTest extends TestCase
{
    public function test_can_create_text_field(): void
    {
        $field = new TextField('username', 'Username');

        $this->assertEquals('username', $field->getName());
        $this->assertEquals('Username', $field->getLabel());
        $this->assertEquals('text', $field->getType());
    }

    public function test_label_is_auto_generated_if_not_provided(): void
    {
        $field = new TextField('first_name');

        $this->assertEquals('First Name', $field->getLabel());
    }

    public function test_can_set_value(): void
    {
        $field = new TextField('username', 'Username', 'johndoe');

        $this->assertEquals('johndoe', $field->getValue());
    }

    public function test_can_set_placeholder(): void
    {
        $field = new TextField('username', 'Username');
        $field->placeholder('Enter your username');

        $this->assertEquals('Enter your username', $field->getPlaceholder());
    }

    public function test_can_set_icon(): void
    {
        $field = new TextField('email', 'Email');
        $field->icon('mail', 'left');

        $this->assertEquals('mail', $field->getIcon());
        $this->assertEquals('left', $field->getIconPosition());
    }

    public function test_can_mark_as_required(): void
    {
        $field = new TextField('username', 'Username');
        $field->required();

        $this->assertTrue($field->isRequired());
    }

    public function test_can_set_max_length(): void
    {
        $field = new TextField('username', 'Username');
        $field->maxLength(50);

        $this->assertEquals(50, $field->getMaxLength());
    }

    public function test_can_set_min_length(): void
    {
        $field = new TextField('username', 'Username');
        $field->minLength(3);

        $this->assertEquals(3, $field->getMinLength());
    }

    public function test_can_add_css_class(): void
    {
        $field = new TextField('username', 'Username');
        $field->addClass('custom-class');

        $attributes = $field->getAttributes();

        $this->assertStringContainsString('custom-class', $attributes['class']);
    }

    public function test_can_set_custom_attribute(): void
    {
        $field = new TextField('username', 'Username');
        $field->attribute('data-test', 'value');

        $attributes = $field->getAttributes();

        $this->assertEquals('value', $attributes['data-test']);
    }

    public function test_can_set_help_text(): void
    {
        $field = new TextField('username', 'Username');
        $field->help('Enter a unique username');

        $this->assertEquals('Enter a unique username', $field->getHelpText());
    }

    public function test_can_set_validation_rules(): void
    {
        $field = new TextField('username', 'Username');
        $field->rules(['required', 'string', 'max:50']);

        $this->assertEquals(['required', 'string', 'max:50'], $field->getValidationRules());
    }

    public function test_can_add_single_validation_rule(): void
    {
        $field = new TextField('username', 'Username');
        $field->rule('required');
        $field->rule('string');

        $this->assertEquals(['required', 'string'], $field->getValidationRules());
    }

    public function test_fluent_interface_returns_self(): void
    {
        $field = new TextField('username', 'Username');

        $result = $field->placeholder('test')
            ->icon('user')
            ->required()
            ->maxLength(50);

        $this->assertSame($field, $result);
    }

    public function test_value_from_model_takes_precedence(): void
    {
        $model = (object) ['username' => 'model_value'];

        $field = new TextField('username', 'Username', 'default_value');
        $field->setModel($model);

        $this->assertEquals('model_value', $field->getValue());
    }

    public function test_returns_default_value_if_model_property_not_exists(): void
    {
        $model = (object) ['other_field' => 'value'];

        $field = new TextField('username', 'Username', 'default_value');
        $field->setModel($model);

        $this->assertEquals('default_value', $field->getValue());
    }
}
