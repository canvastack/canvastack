<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Unit Tests for Tab Class.
 *
 * Tests Requirements: 1.1, 1.2, 1.4, 1.11
 */
class TabTest extends TestCase
{
    /**
     * Test tab creation with label.
     *
     * @test
     */
    public function test_can_create_tab_with_label(): void
    {
        $tab = new Tab('Personal Info');

        $this->assertEquals('Personal Info', $tab->getLabel());
        $this->assertEquals('tab-personal-info', $tab->getId());
    }

    /**
     * Test tab creation with active class.
     *
     * @test
     */
    public function test_can_create_tab_with_active_class(): void
    {
        $tab = new Tab('Personal Info', 'active');

        $this->assertTrue($tab->isActive());
        $this->assertTrue($tab->hasClass('active'));
    }

    /**
     * Test tab creation with custom class.
     *
     * @test
     */
    public function test_can_create_tab_with_custom_class(): void
    {
        $tab = new Tab('Personal Info', 'custom-class');

        $this->assertTrue($tab->hasClass('custom-class'));
        $this->assertFalse($tab->isActive());
    }

    /**
     * Test tab creation with boolean true.
     *
     * @test
     */
    public function test_can_create_tab_with_boolean_true(): void
    {
        $tab = new Tab('Personal Info', true);

        $this->assertTrue($tab->isActive());
        $this->assertTrue($tab->hasClass('active'));
    }

    /**
     * Test adding field to tab.
     *
     * @test
     */
    public function test_can_add_field_to_tab(): void
    {
        $tab = new Tab('Personal Info');
        $field = new TextField('name', 'Name');

        $tab->addField($field);

        $this->assertCount(1, $tab->getFields());
        $this->assertSame($field, $tab->getFields()[0]);
    }

    /**
     * Test adding multiple fields.
     *
     * @test
     */
    public function test_can_add_multiple_fields(): void
    {
        $tab = new Tab('Personal Info');

        $field1 = new TextField('name', 'Name');
        $field2 = new TextField('email', 'Email');
        $field3 = new TextField('phone', 'Phone');

        $tab->addField($field1);
        $tab->addField($field2);
        $tab->addField($field3);

        $this->assertCount(3, $tab->getFields());
        $this->assertEquals(3, $tab->getFieldCount());
    }

    /**
     * Test adding content to tab.
     *
     * @test
     */
    public function test_can_add_content_to_tab(): void
    {
        $tab = new Tab('Personal Info');
        $html = '<p>Custom content</p>';

        $tab->addContent($html);

        $this->assertCount(1, $tab->getContent());
        $this->assertEquals($html, $tab->getContent()[0]);
    }

    /**
     * Test adding multiple content blocks.
     *
     * @test
     */
    public function test_can_add_multiple_content_blocks(): void
    {
        $tab = new Tab('Personal Info');

        $tab->addContent('<p>Content 1</p>');
        $tab->addContent('<div>Content 2</div>');
        $tab->addContent('<span>Content 3</span>');

        $this->assertCount(3, $tab->getContent());
    }

    /**
     * Test set active method.
     *
     * @test
     */
    public function test_can_set_active(): void
    {
        $tab = new Tab('Personal Info');
        $this->assertFalse($tab->isActive());

        $tab->setActive(true);
        $this->assertTrue($tab->isActive());
        $this->assertTrue($tab->hasClass('active'));

        $tab->setActive(false);
        $this->assertFalse($tab->isActive());
        $this->assertFalse($tab->hasClass('active'));
    }

    /**
     * Test add class method.
     *
     * @test
     */
    public function test_can_add_class(): void
    {
        $tab = new Tab('Personal Info');

        $tab->addClass('custom-class');
        $this->assertTrue($tab->hasClass('custom-class'));

        // Adding same class twice should not duplicate
        $tab->addClass('custom-class');
        $this->assertCount(1, $tab->getClasses());
    }

    /**
     * Test remove class method.
     *
     * @test
     */
    public function test_can_remove_class(): void
    {
        $tab = new Tab('Personal Info', 'custom-class');
        $this->assertTrue($tab->hasClass('custom-class'));

        $tab->removeClass('custom-class');
        $this->assertFalse($tab->hasClass('custom-class'));
    }

    /**
     * Test has errors method.
     *
     * @test
     */
    public function test_has_errors_detects_field_errors(): void
    {
        $tab = new Tab('Personal Info');
        $field = new TextField('email', 'Email');
        $tab->addField($field);

        $errors = ['email' => 'Email is required'];

        $this->assertTrue($tab->hasErrors($errors));
    }

    /**
     * Test has errors returns false when no errors.
     *
     * @test
     */
    public function test_has_errors_returns_false_when_no_errors(): void
    {
        $tab = new Tab('Personal Info');
        $field = new TextField('name', 'Name');
        $tab->addField($field);

        $errors = ['other_field' => 'Error'];

        $this->assertFalse($tab->hasErrors($errors));
    }

    /**
     * Test has errors with array field names.
     *
     * @test
     */
    public function test_has_errors_detects_array_field_errors(): void
    {
        $tab = new Tab('Personal Info');
        $field = new TextField('items[]', 'Items');
        $tab->addField($field);

        $errors = ['items.0' => 'Item is required'];

        $this->assertTrue($tab->hasErrors($errors));
    }

    /**
     * Test get fields with errors.
     *
     * @test
     */
    public function test_get_fields_with_errors(): void
    {
        $tab = new Tab('Personal Info');

        $field1 = new TextField('name', 'Name');
        $field2 = new TextField('email', 'Email');
        $field3 = new TextField('phone', 'Phone');

        $tab->addField($field1);
        $tab->addField($field2);
        $tab->addField($field3);

        $errors = [
            'email' => 'Email is required',
            'phone' => 'Phone is invalid',
        ];

        $fieldsWithErrors = $tab->getFieldsWithErrors($errors);

        $this->assertCount(2, $fieldsWithErrors);
        $this->assertSame($field2, $fieldsWithErrors[0]);
        $this->assertSame($field3, $fieldsWithErrors[1]);
    }

    /**
     * Test is empty method.
     *
     * @test
     */
    public function test_is_empty_returns_true_when_empty(): void
    {
        $tab = new Tab('Personal Info');

        $this->assertTrue($tab->isEmpty());
    }

    /**
     * Test is empty returns false with fields.
     *
     * @test
     */
    public function test_is_empty_returns_false_with_fields(): void
    {
        $tab = new Tab('Personal Info');
        $field = new TextField('name', 'Name');
        $tab->addField($field);

        $this->assertFalse($tab->isEmpty());
    }

    /**
     * Test is empty returns false with content.
     *
     * @test
     */
    public function test_is_empty_returns_false_with_content(): void
    {
        $tab = new Tab('Personal Info');
        $tab->addContent('<p>Content</p>');

        $this->assertFalse($tab->isEmpty());
    }

    /**
     * Test to array method.
     *
     * @test
     */
    public function test_to_array_returns_correct_structure(): void
    {
        $tab = new Tab('Personal Info', 'active');
        $field = new TextField('name', 'Name');
        $tab->addField($field);
        $tab->addContent('<p>Content</p>');

        $array = $tab->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Personal Info', $array['label']);
        $this->assertEquals('tab-personal-info', $array['id']);
        $this->assertContains('active', $array['classes']);
        $this->assertTrue($array['active']);
        $this->assertEquals(1, $array['field_count']);
        $this->assertEquals(1, $array['content_count']);
        $this->assertFalse($array['is_empty']);
    }

    /**
     * Test ID generation with special characters.
     *
     * @test
     */
    public function test_id_generation_with_special_characters(): void
    {
        $tab1 = new Tab('Personal Info & Details');
        $this->assertEquals('tab-personal-info-details', $tab1->getId());

        $tab2 = new Tab('Contact (Primary)');
        $this->assertEquals('tab-contact-primary', $tab2->getId());

        $tab3 = new Tab('Address - Home');
        $this->assertEquals('tab-address-home', $tab3->getId());
    }

    /**
     * Test fluent interface for set active.
     *
     * @test
     */
    public function test_set_active_returns_self(): void
    {
        $tab = new Tab('Personal Info');

        $result = $tab->setActive(true);

        $this->assertSame($tab, $result);
    }

    /**
     * Test fluent interface for add class.
     *
     * @test
     */
    public function test_add_class_returns_self(): void
    {
        $tab = new Tab('Personal Info');

        $result = $tab->addClass('custom-class');

        $this->assertSame($tab, $result);
    }

    /**
     * Test fluent interface for remove class.
     *
     * @test
     */
    public function test_remove_class_returns_self(): void
    {
        $tab = new Tab('Personal Info', 'custom-class');

        $result = $tab->removeClass('custom-class');

        $this->assertSame($tab, $result);
    }
}
