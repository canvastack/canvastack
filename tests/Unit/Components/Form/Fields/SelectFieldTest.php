<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Fields;

use Canvastack\Canvastack\Components\Form\Fields\SelectField;
use Canvastack\Canvastack\Tests\TestCase;

class SelectFieldTest extends TestCase
{
    public function test_can_create_select_field(): void
    {
        $options = ['1' => 'Option 1', '2' => 'Option 2'];
        $field = new SelectField('status', 'Status', $options);

        $this->assertEquals('status', $field->getName());
        $this->assertEquals('Status', $field->getLabel());
        $this->assertEquals('select', $field->getType());
        $this->assertEquals($options, $field->getOptions());
    }

    public function test_can_set_options(): void
    {
        $field = new SelectField('status', 'Status');
        $options = ['active' => 'Active', 'inactive' => 'Inactive'];

        $field->options($options);

        $this->assertEquals($options, $field->getOptions());
    }

    public function test_can_set_selected_value(): void
    {
        $field = new SelectField('status', 'Status', ['1' => 'Active', '2' => 'Inactive']);
        $field->setSelected('1');

        $this->assertEquals('1', $field->getSelected());
    }

    public function test_selected_value_from_model_takes_precedence(): void
    {
        $model = (object) ['status' => '2'];

        $field = new SelectField('status', 'Status', ['1' => 'Active', '2' => 'Inactive']);
        $field->setSelected('1');
        $field->setModel($model);

        $this->assertEquals('2', $field->getSelected());
    }

    public function test_can_enable_multiple_selection(): void
    {
        $field = new SelectField('tags', 'Tags', []);
        $field->multiple();

        $this->assertTrue($field->isMultiple());
        $this->assertArrayHasKey('multiple', $field->getAttributes());
    }

    public function test_can_enable_searchable(): void
    {
        $field = new SelectField('country', 'Country', []);
        $field->searchable();

        $this->assertTrue($field->isSearchable());
    }

    public function test_fluent_interface_works(): void
    {
        $field = new SelectField('status', 'Status', []);

        $result = $field->options(['1' => 'Active'])
            ->multiple()
            ->searchable()
            ->required();

        $this->assertSame($field, $result);
    }
}
