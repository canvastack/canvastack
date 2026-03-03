<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Fields\CheckboxField;
use Canvastack\Canvastack\Components\Form\Fields\HiddenField;
use Canvastack\Canvastack\Components\Form\Fields\SelectField;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Tests\TestCase;

class AdminRendererTest extends TestCase
{
    protected AdminRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new AdminRenderer();
    }

    public function test_can_render_text_field(): void
    {
        $field = new TextField('username', 'Username');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('username', $output);
        $this->assertStringContainsString('Username', $output);
        $this->assertStringContainsString('type="text"', $output);
    }

    public function test_renders_required_indicator(): void
    {
        $field = new TextField('email', 'Email');
        $field->required();

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('text-red-500', $output);
        $this->assertStringContainsString('*', $output);
    }

    public function test_renders_field_with_icon(): void
    {
        $field = new TextField('email', 'Email');
        $field->icon('mail', 'left');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('data-lucide="mail"', $output);
        $this->assertStringContainsString('pl-10', $output);
    }

    public function test_renders_field_with_placeholder(): void
    {
        $field = new TextField('username', 'Username');
        $field->placeholder('Enter your username');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('placeholder="Enter your username"', $output);
    }

    public function test_renders_help_text(): void
    {
        $field = new TextField('password', 'Password');
        $field->help('Must be at least 8 characters');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('Must be at least 8 characters', $output);
        $this->assertStringContainsString('text-xs', $output);
    }

    public function test_renders_select_field(): void
    {
        $options = ['1' => 'Option 1', '2' => 'Option 2'];
        $field = new SelectField('status', 'Status', $options);

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('Option 1', $output);
        $this->assertStringContainsString('Option 2', $output);
    }

    public function test_renders_select_with_selected_value(): void
    {
        $options = ['1' => 'Active', '2' => 'Inactive'];
        $field = new SelectField('status', 'Status', $options);
        $field->setSelected('2');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('value="2" selected', $output);
    }

    public function test_renders_checkbox_field(): void
    {
        $options = ['1' => 'Option 1', '2' => 'Option 2'];
        $field = new CheckboxField('interests', 'Interests', $options);

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('type="checkbox"', $output);
        $this->assertStringContainsString('Option 1', $output);
        $this->assertStringContainsString('Option 2', $output);
    }

    public function test_renders_checkbox_with_checked_values(): void
    {
        $options = ['1' => 'Sports', '2' => 'Music'];
        $field = new CheckboxField('interests', 'Interests', $options);
        $field->setChecked(['1']);

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('value="1"', $output);
        $this->assertStringContainsString('checked', $output);
    }

    public function test_renders_hidden_field_without_wrapper(): void
    {
        $field = new HiddenField('id', null, '123');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('value="123"', $output);
        $this->assertStringNotContainsString('<div class="mb-5">', $output);
    }

    public function test_renders_field_with_tailwind_classes(): void
    {
        $field = new TextField('name', 'Name');

        $output = $this->renderer->render($field);

        $this->assertStringContainsString('rounded-xl', $output);
        $this->assertStringContainsString('dark:bg-gray-800', $output);
        $this->assertStringContainsString('focus:ring-indigo-500', $output);
    }

    public function test_renders_label(): void
    {
        $field = new TextField('username', 'Username');

        $label = $this->renderer->renderLabel($field);

        $this->assertStringContainsString('<label', $label);
        $this->assertStringContainsString('Username', $label);
        $this->assertStringContainsString('for="username"', $label);
    }

    public function test_renders_input(): void
    {
        $field = new TextField('email', 'Email', 'test@example.com');

        $input = $this->renderer->renderInput($field);

        $this->assertStringContainsString('type="text"', $input);
        $this->assertStringContainsString('value="test@example.com"', $input);
    }

    public function test_escapes_html_in_values(): void
    {
        $field = new TextField('name', 'Name', '<script>alert("xss")</script>');

        $output = $this->renderer->render($field);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }
}
