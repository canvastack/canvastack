<?php

declare(strict_types=1);

namespace Tests\Unit\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Features\Tabs\Tab;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\PublicRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Unit tests for TabRenderingTrait.
 *
 * Tests tab navigation HTML generation, tab content panel generation,
 * Alpine.js data attributes, and dark mode styling.
 *
 * Requirements: 1.5, 1.6, 1.8, 1.9, 1.14
 */
class TabRenderingTraitTest extends TestCase
{
    private AdminRenderer $adminRenderer;

    private PublicRenderer $publicRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRenderer = new AdminRenderer();
        $this->publicRenderer = new PublicRenderer();
    }

    /**
     * @test
     * Requirement 1.5: Tab navigation HTML generation
     */
    public function it_generates_tab_navigation_html(): void
    {
        $tabs = [
            new Tab('Personal Info', 'active'),
            new Tab('Address'),
            new Tab('Documents'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify tab navigation container
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('tabs tabs-bordered', $html);

        // Verify all tab labels are present
        $this->assertStringContainsString('Personal Info', $html);
        $this->assertStringContainsString('Address', $html);
        $this->assertStringContainsString('Documents', $html);

        // Verify tab links
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('class="tab', $html);
    }

    /**
     * @test
     * Requirement 1.6: Tab content panel generation
     */
    public function it_generates_tab_content_panels(): void
    {
        $tab1 = new Tab('Tab 1', 'active');
        $tab1->addField(new TextField('name', 'Name'));

        $tab2 = new Tab('Tab 2');
        $tab2->addField(new TextField('email', 'Email'));

        $tabs = [$tab1, $tab2];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify tab content container
        $this->assertStringContainsString('tab-content', $html);

        // Verify tab panels
        $this->assertStringContainsString('role="tabpanel"', $html);
        $this->assertStringContainsString('id="tab-tab-1"', $html);
        $this->assertStringContainsString('id="tab-tab-2"', $html);

        // Verify fields are rendered in panels
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
    }

    /**
     * @test
     * Requirement 1.5, 1.6: Alpine.js data attributes
     */
    public function it_includes_alpine_js_data_attributes(): void
    {
        $tabs = [
            new Tab('Tab 1', 'active'),
            new Tab('Tab 2'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify Alpine.js x-data attribute
        $this->assertStringContainsString('x-data="{ activeTab:', $html);

        // Verify Alpine.js x-show attribute
        $this->assertStringContainsString('x-show="activeTab ===', $html);

        // Verify Alpine.js click handler
        $this->assertStringContainsString('@click.prevent="activeTab =', $html);

        // Verify Alpine.js class binding
        $this->assertStringContainsString(':class="{ \'tab-active\': activeTab ===', $html);

        // Verify Alpine.js transitions
        $this->assertStringContainsString('x-transition:enter', $html);
        $this->assertStringContainsString('x-transition:enter-start', $html);
        $this->assertStringContainsString('x-transition:enter-end', $html);
    }

    /**
     * @test
     * Requirement 1.14: Dark mode styling
     */
    public function it_includes_dark_mode_classes(): void
    {
        $tab1 = new Tab('Tab 1', 'active');
        $tab1->addField(new TextField('email', 'Email'));

        $tabs = [$tab1];

        // Add validation errors to trigger error styling with dark mode
        $errors = [
            'email' => ['Email is required'],
        ];

        $html = $this->adminRenderer->renderTabs($tabs, $errors);

        // Verify dark mode classes in navigation
        $this->assertStringContainsString('dark:border-gray-700', $html);
        $this->assertStringContainsString('dark:text-red-400', $html);
    }

    /**
     * @test
     * Requirement 1.8: Admin context rendering
     */
    public function it_renders_tabs_in_admin_context(): void
    {
        $tabs = [
            new Tab('Admin Tab', 'active'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify DaisyUI tab classes (admin styling)
        $this->assertStringContainsString('tabs tabs-bordered', $html);
        $this->assertStringContainsString('tab tab-lg', $html);

        // Verify HTML structure is present
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('tabs-container', $html);
    }

    /**
     * @test
     * Requirement 1.9: Public context rendering
     */
    public function it_renders_tabs_in_public_context(): void
    {
        $tabs = [
            new Tab('Public Tab', 'active'),
        ];

        $html = $this->publicRenderer->renderTabs($tabs);

        // Public renderer extends admin, so should have same structure
        $this->assertStringContainsString('tabs tabs-bordered', $html);
        $this->assertStringContainsString('tab tab-lg', $html);
        $this->assertStringContainsString('tabs-container', $html);
    }

    /**
     * @test
     * Requirement 1.5: Tab navigation with active state
     */
    public function it_marks_active_tab_in_navigation(): void
    {
        $tabs = [
            new Tab('Tab 1'),
            new Tab('Tab 2', 'active'),
            new Tab('Tab 3'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify active tab is set in Alpine.js data
        $this->assertStringContainsString('activeTab: \'tab-tab-2\'', $html);

        // Verify tab-active class is present
        $this->assertStringContainsString('tab-active', $html);
    }

    /**
     * @test
     * Requirement 1.6: Tab content with custom HTML
     */
    public function it_renders_custom_html_content_in_tabs(): void
    {
        $tab = new Tab('Tab with Content', 'active');
        $tab->addContent('<div class="custom-content">Custom HTML</div>');
        $tab->addContent('<p>More content</p>');

        $tabs = [$tab];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify custom content is included
        $this->assertStringContainsString('custom-content', $html);
        $this->assertStringContainsString('Custom HTML', $html);
        $this->assertStringContainsString('More content', $html);
    }

    /**
     * @test
     * Requirement 1.5: Tab navigation with multiple tabs
     */
    public function it_renders_multiple_tabs_correctly(): void
    {
        $tabs = [];
        for ($i = 1; $i <= 5; $i++) {
            $tabs[] = new Tab("Tab {$i}", $i === 1 ? 'active' : false);
        }

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify all tabs are present
        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("Tab {$i}", $html);
            $this->assertStringContainsString("tab-tab-{$i}", $html);
        }
    }

    /**
     * @test
     * Requirement 1.6: Tab panels with fields
     */
    public function it_renders_fields_within_tab_panels(): void
    {
        $tab1 = new Tab('Personal', 'active');
        $tab1->addField(new TextField('first_name', 'First Name'));
        $tab1->addField(new TextField('last_name', 'Last Name'));

        $tab2 = new Tab('Contact');
        $tab2->addField(new TextField('email', 'Email'));
        $tab2->addField(new TextField('phone', 'Phone'));

        $tabs = [$tab1, $tab2];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify all fields are rendered
        $this->assertStringContainsString('name="first_name"', $html);
        $this->assertStringContainsString('name="last_name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="phone"', $html);

        // Verify field labels
        $this->assertStringContainsString('First Name', $html);
        $this->assertStringContainsString('Last Name', $html);
        $this->assertStringContainsString('Email', $html);
        $this->assertStringContainsString('Phone', $html);
    }

    /**
     * @test
     * Requirement 1.5: ARIA attributes for accessibility
     */
    public function it_includes_aria_attributes_for_accessibility(): void
    {
        $tabs = [
            new Tab('Tab 1', 'active'),
            new Tab('Tab 2'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify ARIA attributes
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('role="tabpanel"', $html);
        $this->assertStringContainsString('aria-controls=', $html);
        $this->assertStringContainsString(':aria-selected=', $html);
        $this->assertStringContainsString(':aria-hidden=', $html);
    }

    /**
     * @test
     * Requirement 1.5: Tab navigation with validation errors
     */
    public function it_highlights_tabs_with_validation_errors(): void
    {
        $tab1 = new Tab('Tab 1', 'active');
        $tab1->addField(new TextField('name', 'Name'));

        $tab2 = new Tab('Tab 2');
        $tab2->addField(new TextField('email', 'Email'));

        $tabs = [$tab1, $tab2];

        // Simulate validation errors
        $errors = [
            'email' => ['Email is required'],
        ];

        $html = $this->adminRenderer->renderTabs($tabs, $errors);

        // Verify error indicator is present
        $this->assertStringContainsString('text-red-600', $html);
        $this->assertStringContainsString('bg-red-500', $html);
        $this->assertStringContainsString('!', $html); // Error badge
    }

    /**
     * @test
     * Requirement 1.5: Active tab selection with validation errors
     */
    public function it_activates_first_tab_with_errors(): void
    {
        $tab1 = new Tab('Tab 1', 'active');
        $tab1->addField(new TextField('name', 'Name'));

        $tab2 = new Tab('Tab 2');
        $tab2->addField(new TextField('email', 'Email'));

        $tab3 = new Tab('Tab 3');
        $tab3->addField(new TextField('phone', 'Phone'));

        $tabs = [$tab1, $tab2, $tab3];

        // Errors in tab 3
        $errors = [
            'phone' => ['Phone is required'],
        ];

        $html = $this->adminRenderer->renderTabs($tabs, $errors);

        // Verify tab 3 is activated (first tab with errors)
        $this->assertStringContainsString('activeTab: \'tab-tab-3\'', $html);
    }

    /**
     * @test
     * Requirement 1.6: Empty tabs render correctly
     */
    public function it_renders_empty_tabs_without_fields(): void
    {
        $tabs = [
            new Tab('Empty Tab 1', 'active'),
            new Tab('Empty Tab 2'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify structure is present even without fields
        $this->assertStringContainsString('tabs-container', $html);
        $this->assertStringContainsString('Empty Tab 1', $html);
        $this->assertStringContainsString('Empty Tab 2', $html);
        $this->assertStringContainsString('role="tabpanel"', $html);
    }

    /**
     * @test
     * Requirement 1.5: Tab IDs are properly generated
     */
    public function it_generates_proper_tab_ids(): void
    {
        $tabs = [
            new Tab('Personal Info', 'active'),
            new Tab('Contact Details'),
            new Tab('Work & Education'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify slugified IDs
        $this->assertStringContainsString('tab-personal-info', $html);
        $this->assertStringContainsString('tab-contact-details', $html);
        $this->assertStringContainsString('tab-work-education', $html);
    }

    /**
     * @test
     * Requirement 1.6: Tab transitions are configured
     */
    public function it_configures_tab_transitions(): void
    {
        $tabs = [
            new Tab('Tab 1', 'active'),
        ];

        $html = $this->adminRenderer->renderTabs($tabs);

        // Verify transition classes
        $this->assertStringContainsString('transition ease-out duration-200', $html);
        $this->assertStringContainsString('opacity-0 transform scale-95', $html);
        $this->assertStringContainsString('opacity-100 transform scale-100', $html);
    }

    /**
     * @test
     * Requirement 1.5, 1.6: Returns empty string for no tabs
     */
    public function it_returns_empty_string_when_no_tabs(): void
    {
        $html = $this->adminRenderer->renderTabs([]);

        $this->assertEmpty($html);
    }

    /**
     * @test
     * Requirement 1.8, 1.9: Both renderers produce valid HTML
     */
    public function it_produces_valid_html_structure_in_both_contexts(): void
    {
        $tabs = [
            new Tab('Tab 1', 'active'),
            new Tab('Tab 2'),
        ];

        $adminHtml = $this->adminRenderer->renderTabs($tabs);
        $publicHtml = $this->publicRenderer->renderTabs($tabs);

        // Both should have proper structure
        foreach ([$adminHtml, $publicHtml] as $html) {
            $this->assertStringContainsString('<div class="tabs-container', $html);
            $this->assertStringContainsString('<div role="tablist"', $html);
            $this->assertStringContainsString('<a', $html);
            $this->assertStringContainsString('</a>', $html);
            $this->assertStringContainsString('<div class="tab-content', $html);
            $this->assertStringContainsString('</div>', $html);
        }
    }
}
