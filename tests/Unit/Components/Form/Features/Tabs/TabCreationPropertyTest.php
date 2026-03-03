<?php

declare(strict_types=1);

namespace Tests\Unit\Components\Form\Features\Tabs;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;
use Canvastack\Canvastack\Components\Form\Features\Tabs\TabManager;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Test: Tab Creation and Association.
 *
 * Validates Requirements: 1.1, 1.2, 1.3
 *
 * Property 1: Tab Creation and Association
 *
 * Universal Properties:
 * 1. For any valid label string, openTab() creates a Tab instance
 * 2. For any Tab created, it becomes the active tab
 * 3. For any field added after openTab(), it is associated with the active tab
 * 4. For any closeTab() call, the active tab becomes null
 * 5. For any sequence of openTab/closeTab, tabs are created in order
 * 6. For any tab with class 'active', isActive() returns true
 * 7. For any field added to a tab, getFields() includes that field
 * 8. For any number of tabs N, getTabs() returns exactly N tabs
 */
class TabCreationPropertyTest extends TestCase
{
    /**
     * Property 1.1: For any valid label string, openTab() creates a Tab instance.
     *
     * @test
     * @dataProvider labelProvider
     */
    public function property_open_tab_creates_tab_instance(string $label): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab($label);

        $this->assertInstanceOf(Tab::class, $tab);
        $this->assertEquals($label, $tab->getLabel());
    }

    /**
     * Property 1.2: For any Tab created, it becomes the active tab.
     *
     * @test
     * @dataProvider labelProvider
     */
    public function property_created_tab_becomes_active(string $label): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab($label);

        $this->assertSame($tab, $tabManager->getActiveTab());
    }

    /**
     * Property 1.3: For any field added after openTab(), it is associated with the active tab.
     *
     * @test
     */
    public function property_fields_associate_with_active_tab(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab('Test Tab');
        $field = new TextField('test_field', 'Test Field');

        $tabManager->addFieldToActiveTab($field);

        $this->assertCount(1, $tab->getFields());
        $this->assertSame($field, $tab->getFields()[0]);
    }

    /**
     * Property 1.4: For any closeTab() call, the active tab becomes null.
     *
     * @test
     */
    public function property_close_tab_clears_active_tab(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tabManager->openTab('Test Tab');
        $this->assertNotNull($tabManager->getActiveTab());

        $tabManager->closeTab();
        $this->assertNull($tabManager->getActiveTab());
    }

    /**
     * Property 1.5: For any sequence of openTab/closeTab, tabs are created in order.
     *
     * @test
     */
    public function property_tabs_created_in_order(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $labels = ['Tab 1', 'Tab 2', 'Tab 3'];

        foreach ($labels as $label) {
            $tabManager->openTab($label);
            $tabManager->closeTab();
        }

        $tabs = $tabManager->getTabs();
        $this->assertCount(3, $tabs);

        foreach ($labels as $index => $label) {
            $this->assertEquals($label, $tabs[$index]->getLabel());
        }
    }

    /**
     * Property 1.6: For any tab with class 'active', isActive() returns true.
     *
     * @test
     */
    public function property_active_class_sets_active_state(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab('Active Tab', 'active');

        $this->assertTrue($tab->isActive());
        $this->assertTrue($tab->hasClass('active'));
    }

    /**
     * Property 1.7: For any field added to a tab, getFields() includes that field.
     *
     * @test
     */
    public function property_get_fields_includes_all_added_fields(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab('Test Tab');
        $fields = [];

        for ($i = 1; $i <= 5; $i++) {
            $field = new TextField("field_{$i}", "Field {$i}");
            $fields[] = $field;
            $tabManager->addFieldToActiveTab($field);
        }

        $tabFields = $tab->getFields();
        $this->assertCount(5, $tabFields);

        foreach ($fields as $index => $field) {
            $this->assertSame($field, $tabFields[$index]);
        }
    }

    /**
     * Property 1.8: For any number of tabs N, getTabs() returns exactly N tabs.
     *
     * @test
     * @dataProvider tabCountProvider
     */
    public function property_get_tabs_returns_exact_count(int $count): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        for ($i = 1; $i <= $count; $i++) {
            $tabManager->openTab("Tab {$i}");
            $tabManager->closeTab();
        }

        $this->assertCount($count, $tabManager->getTabs());
    }

    /**
     * Property 1.9: For any tab without fields, isEmpty() returns true.
     *
     * @test
     */
    public function property_empty_tab_is_empty(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab('Empty Tab');

        $this->assertTrue($tab->isEmpty());
    }

    /**
     * Property 1.10: For any tab with fields, isEmpty() returns false.
     *
     * @test
     */
    public function property_tab_with_fields_is_not_empty(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab('Non-Empty Tab');
        $field = new TextField('test_field', 'Test Field');
        $tabManager->addFieldToActiveTab($field);

        $this->assertFalse($tab->isEmpty());
    }

    /**
     * Property 1.11: For any custom content added, getContent() includes it.
     *
     * @test
     */
    public function property_get_content_includes_all_added_content(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab('Content Tab');
        $contentBlocks = [
            '<p>Content 1</p>',
            '<div>Content 2</div>',
            '<span>Content 3</span>',
        ];

        foreach ($contentBlocks as $content) {
            $tabManager->addTabContent($content);
        }

        $tabContent = $tab->getContent();
        $this->assertCount(3, $tabContent);

        foreach ($contentBlocks as $index => $content) {
            $this->assertEquals($content, $tabContent[$index]);
        }
    }

    /**
     * Property 1.12: For any tab, ID is generated from label slug.
     *
     * @test
     * @dataProvider labelSlugProvider
     */
    public function property_tab_id_generated_from_label_slug(string $label, string $expectedId): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab = $tabManager->openTab($label);

        $this->assertEquals($expectedId, $tab->getId());
    }

    /**
     * Property 1.13: For any field added without active tab, it is not added to any tab.
     *
     * @test
     */
    public function property_field_not_added_without_active_tab(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $field = new TextField('test_field', 'Test Field');
        $tabManager->addFieldToActiveTab($field);

        $this->assertCount(0, $tabManager->getTabs());
    }

    /**
     * Property 1.14: For any multiple tabs, only the last opened is active.
     *
     * @test
     */
    public function property_only_last_opened_tab_is_active(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tab1 = $tabManager->openTab('Tab 1');
        $tab2 = $tabManager->openTab('Tab 2');
        $tab3 = $tabManager->openTab('Tab 3');

        $this->assertSame($tab3, $tabManager->getActiveTab());
        $this->assertNotSame($tab1, $tabManager->getActiveTab());
        $this->assertNotSame($tab2, $tabManager->getActiveTab());
    }

    /**
     * Property 1.15: For any tab manager reset, all tabs are cleared.
     *
     * @test
     */
    public function property_reset_clears_all_tabs(): void
    {
        $renderer = new AdminRenderer();
        $tabManager = new TabManager($renderer);

        $tabManager->openTab('Tab 1');
        $tabManager->openTab('Tab 2');
        $tabManager->openTab('Tab 3');

        $this->assertCount(3, $tabManager->getTabs());

        $tabManager->reset();

        $this->assertCount(0, $tabManager->getTabs());
        $this->assertNull($tabManager->getActiveTab());
    }

    /**
     * Data provider for label strings.
     *
     * @return array<array<string>>
     */
    public static function labelProvider(): array
    {
        return [
            ['Personal Information'],
            ['Contact Details'],
            ['Address'],
            ['Documents'],
            ['Settings'],
            ['Tab with Numbers 123'],
            ['Tab-with-dashes'],
            ['Tab_with_underscores'],
        ];
    }

    /**
     * Data provider for tab counts.
     *
     * @return array<array<int>>
     */
    public static function tabCountProvider(): array
    {
        return [
            [1],
            [2],
            [5],
            [10],
            [20],
        ];
    }

    /**
     * Data provider for label and expected slug.
     *
     * @return array<array<string>>
     */
    public static function labelSlugProvider(): array
    {
        return [
            ['Personal Information', 'tab-personal-information'],
            ['Contact Details', 'tab-contact-details'],
            ['Tab with Spaces', 'tab-tab-with-spaces'],
            ['Tab-with-dashes', 'tab-tab-with-dashes'],
            ['Tab_with_underscores', 'tab-tab-with-underscores'],
        ];
    }
}
