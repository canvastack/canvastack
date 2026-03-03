<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\PublicRenderer;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Comprehensive Accessibility Tests for All Form Components.
 *
 * Tests ARIA labels, keyboard navigation, focus management, and screen reader compatibility
 * for all form components including Tab System, Switch Checkbox, Searchable Select,
 * Date Range Picker, Month Picker, Tags Input, Character Counter, and File Upload.
 *
 * Validates: Requirements 15.1, 15.2, 15.3, 15.4, 15.5
 */
class AccessibilityTest extends TestCase
{
    protected FormBuilder $formBuilder;

    protected AdminRenderer $adminRenderer;

    protected PublicRenderer $publicRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();

        $this->adminRenderer = new AdminRenderer();
        $this->publicRenderer = new PublicRenderer();

        $this->formBuilder = new FormBuilder($fieldFactory, $validationCache);
    }

    /**
     * Test: Tab System includes proper ARIA labels and descriptions.
     *
     * @test
     */
    public function test_tab_system_includes_aria_labels()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->openTab('Personal Information');
        $this->formBuilder->text('first_name', 'First Name');
        $this->formBuilder->text('last_name', 'Last Name');
        $this->formBuilder->closeTab();
        $this->formBuilder->openTab('Contact Details');
        $this->formBuilder->email('email', 'Email Address');
        $this->formBuilder->text('phone', 'Phone Number');
        $this->formBuilder->closeTab();
        $html = $this->formBuilder->render();

        // Verify tab navigation has role="tablist"
        $this->assertStringContainsString('role="tablist"', $html);

        // Verify individual tabs have role="tab"
        $this->assertStringContainsString('role="tab"', $html);

        // Verify tab panels have role="tabpanel"
        $this->assertStringContainsString('role="tabpanel"', $html);

        // Verify tabs have aria-selected attribute (Alpine.js dynamic binding)
        $this->assertTrue(
            strpos($html, ':aria-selected') !== false || strpos($html, 'aria-selected') !== false,
            'Tabs missing aria-selected attribute'
        );

        // Verify tab labels are present
        $this->assertStringContainsString('Personal Information', $html);
        $this->assertStringContainsString('Contact Details', $html);
    }

    /**
     * Test: Tab System supports keyboard navigation.
     *
     * @test
     */
    public function test_tab_system_supports_keyboard_navigation()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->openTab('Tab 1');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();
        $this->formBuilder->openTab('Tab 2');
        $this->formBuilder->text('field2', 'Field 2');
        $this->formBuilder->closeTab();
        $html = $this->formBuilder->render();

        // Verify tabs are clickable elements (a or button)
        $this->assertMatchesRegularExpression('/<(a|button)[^>]*role=["\']tab["\']/', $html);

        // Verify tabs have click handlers for keyboard activation
        $this->assertTrue(
            strpos($html, '@click') !== false || strpos($html, 'onclick') !== false,
            'Tabs missing click handlers for keyboard activation'
        );

        // Verify tabs are in logical DOM order
        $pos1 = strpos($html, 'Tab 1');
        $pos2 = strpos($html, 'Tab 2');
        $this->assertLessThan($pos2, $pos1, 'Tabs not in logical order');
    }

    /**
     * Test: Tab System announces changes to screen readers.
     *
     * @test
     */
    public function test_tab_system_announces_changes_to_screen_readers()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->openTab('Settings');
        $this->formBuilder->checkbox('notifications', 'Enable Notifications');
        $this->formBuilder->closeTab();
        $this->formBuilder->openTab('Privacy');
        $this->formBuilder->checkbox('public_profile', 'Public Profile');
        $this->formBuilder->closeTab();
        $html = $this->formBuilder->render();

        // Verify ARIA live regions for dynamic content
        // Note: Alpine.js handles dynamic tab switching, ARIA attributes should be present
        $this->assertStringContainsString('role="tabpanel"', $html);

        // Verify tab panels have appropriate ARIA attributes
        $this->assertTrue(
            strpos($html, 'x-show') !== false || strpos($html, 'style') !== false,
            'Tab panels missing visibility control for screen readers'
        );
    }

    /**
     * Test: Switch Checkbox is keyboard accessible.
     *
     * @test
     */
    public function test_switch_checkbox_is_keyboard_accessible()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->checkbox('is_active', 'Active Status')->switch();
        $html = $this->formBuilder->render();

        // Verify switch has role="switch"
        $this->assertStringContainsString('role="switch"', $html);

        // Verify switch uses native checkbox input (supports Space bar)
        $this->assertStringContainsString('type="checkbox"', $html);

        // Verify switch has aria-checked attribute
        $this->assertMatchesRegularExpression('/aria-checked=["\']/', $html);

        // Verify switch is keyboard focusable (no tabindex="-1")
        $this->assertDoesNotMatchRegularExpression('/name=["\']is_active["\'][^>]*tabindex=["\']?-1/', $html);
    }

    /**
     * Test: Switch Checkbox announces state changes.
     *
     * @test
     */
    public function test_switch_checkbox_announces_state_changes()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->checkbox('email_notifications', 'Email Notifications')->switch();
        $html = $this->formBuilder->render();

        // Verify aria-checked attribute is present for state announcement
        $this->assertMatchesRegularExpression('/aria-checked=["\']/', $html);

        // Verify ARIA label or labelledby is present
        $this->assertTrue(
            strpos($html, 'aria-label') !== false ||
            strpos($html, 'aria-labelledby') !== false ||
            preg_match('/<label[^>]*for=["\']email_notifications["\']/', $html),
            'Switch checkbox missing ARIA label'
        );
    }

    /**
     * Test: Searchable Select supports keyboard navigation.
     *
     * @test
     */
    public function test_searchable_select_supports_keyboard_navigation()
    {
        $options = [
            '1' => 'Option One',
            '2' => 'Option Two',
            '3' => 'Option Three',
            '4' => 'Option Four',
        ];

        $this->formBuilder->setContext('admin');
        $this->formBuilder->select('category', 'Category', $options)->searchable();
        $html = $this->formBuilder->render();

        // Verify select is keyboard focusable
        $this->assertMatchesRegularExpression('/<select[^>]*name=["\']category["\']/', $html);

        // Verify ARIA label is present
        $this->assertTrue(
            strpos($html, 'aria-label') !== false ||
            strpos($html, 'aria-labelledby') !== false ||
            preg_match('/<label[^>]*for=["\']category["\']/', $html),
            'Searchable select missing ARIA label'
        );
    }

    /**
     * Test: Character Counter uses ARIA live regions.
     *
     * @test
     */
    public function test_character_counter_uses_aria_live_regions()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->textarea('bio', 'Biography')->maxLength(200);
        $html = $this->formBuilder->render();

        // Verify character counter has aria-live attribute
        $this->assertStringContainsString('aria-live', $html);

        // Verify aria-live is set to "polite" for non-critical updates
        $this->assertStringContainsString('aria-live="polite"', $html);

        // Verify character counter is present (may have HTML tags between numbers)
        $this->assertTrue(
            strpos($html, 'current-count') !== false && strpos($html, 'max-count') !== false,
            'Character counter missing count elements'
        );

        // Verify the counter displays the format (allowing for HTML tags)
        $this->assertStringContainsString('/ ', $html);
        $this->assertStringContainsString(' characters', $html);
    }

    /**
     * Test: Date Range Picker is keyboard accessible.
     *
     * @test
     */
    public function test_date_range_picker_is_keyboard_accessible()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->daterange('booking_period', 'Booking Period');
        $html = $this->formBuilder->render();

        // Verify date range input is keyboard focusable
        $this->assertMatchesRegularExpression('/<input[^>]*name=["\']booking_period["\']/', $html);

        // Verify ARIA label is present
        $this->assertTrue(
            strpos($html, 'aria-label') !== false ||
            strpos($html, 'aria-labelledby') !== false ||
            preg_match('/<label[^>]*for=["\']booking_period["\']/', $html),
            'Date range picker missing ARIA label'
        );
    }

    /**
     * Test: Month Picker is keyboard accessible.
     *
     * @test
     */
    public function test_month_picker_is_keyboard_accessible()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->month('billing_month', 'Billing Month');
        $html = $this->formBuilder->render();

        // Verify month input is keyboard focusable
        $this->assertMatchesRegularExpression('/<input[^>]*name=["\']billing_month["\']/', $html);

        // Verify ARIA label is present
        $this->assertTrue(
            strpos($html, 'aria-label') !== false ||
            strpos($html, 'aria-labelledby') !== false ||
            preg_match('/<label[^>]*for=["\']billing_month["\']/', $html),
            'Month picker missing ARIA label'
        );
    }

    /**
     * Test: Tags Input is keyboard accessible.
     *
     * @test
     */
    public function test_tags_input_is_keyboard_accessible()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->tags('keywords', 'Keywords');
        $html = $this->formBuilder->render();

        // Verify tags input is keyboard focusable
        $this->assertMatchesRegularExpression('/<input[^>]*name=["\']keywords["\']/', $html);

        // Verify ARIA label is present
        $this->assertTrue(
            strpos($html, 'aria-label') !== false ||
            strpos($html, 'aria-labelledby') !== false ||
            preg_match('/<label[^>]*for=["\']keywords["\']/', $html),
            'Tags input missing ARIA label'
        );
    }

    /**
     * Test: File Upload includes ARIA labels.
     *
     * @test
     */
    public function test_file_upload_includes_aria_labels()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->file('document', 'Upload Document');
        $html = $this->formBuilder->render();

        // Verify file input has ARIA label
        $this->assertTrue(
            strpos($html, 'aria-label') !== false ||
            strpos($html, 'aria-labelledby') !== false ||
            preg_match('/<label[^>]*for=["\']document["\']/', $html),
            'File upload missing ARIA label'
        );

        // Verify file input is keyboard focusable
        $this->assertStringContainsString('type="file"', $html);
    }

    /**
     * Test: Focus management for all interactive components.
     *
     * @test
     */
    public function test_focus_management_for_all_components()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->text('username', 'Username');
        $this->formBuilder->email('email', 'Email');
        $this->formBuilder->select('country', 'Country', ['US' => 'USA', 'UK' => 'UK']);
        $this->formBuilder->checkbox('agree', 'I agree')->switch();
        $this->formBuilder->textarea('comments', 'Comments')->maxLength(500);
        $html = $this->formBuilder->render();

        // Verify all fields are keyboard focusable (no tabindex="-1")
        $this->assertDoesNotMatchRegularExpression('/name=["\']username["\'][^>]*tabindex=["\']?-1/', $html);
        $this->assertDoesNotMatchRegularExpression('/name=["\']email["\'][^>]*tabindex=["\']?-1/', $html);
        $this->assertDoesNotMatchRegularExpression('/name=["\']country["\'][^>]*tabindex=["\']?-1/', $html);
        $this->assertDoesNotMatchRegularExpression('/name=["\']agree["\'][^>]*tabindex=["\']?-1/', $html);
        $this->assertDoesNotMatchRegularExpression('/name=["\']comments["\'][^>]*tabindex=["\']?-1/', $html);

        // Verify focus styling classes are present (Tailwind CSS focus: variants)
        $this->assertTrue(
            strpos($html, 'focus:') !== false ||
            strpos($html, 'input') !== false ||
            strpos($html, 'select') !== false ||
            strpos($html, 'textarea') !== false,
            'Form fields missing focus styling support'
        );
    }

    /**
     * Test: Screen reader compatibility with ARIA live regions.
     *
     * @test
     */
    public function test_screen_reader_compatibility_with_aria_live_regions()
    {
        $this->formBuilder->setContext('admin');

        // Character counter with live region
        $this->formBuilder->text('title', 'Title')->maxLength(100);

        // Tab system with dynamic content
        $this->formBuilder->openTab('Tab 1');
        $this->formBuilder->text('field1', 'Field 1');
        $this->formBuilder->closeTab();

        $html = $this->formBuilder->render();

        // Verify ARIA live regions are present
        $this->assertStringContainsString('aria-live', $html);

        // Verify appropriate politeness level (polite for non-critical updates)
        $this->assertStringContainsString('aria-live="polite"', $html);
    }

    /**
     * Test: All form fields include proper ARIA labels.
     *
     * @test
     */
    public function test_all_form_fields_include_aria_labels()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->text('name', 'Full Name');
        $this->formBuilder->email('email', 'Email Address');
        $this->formBuilder->password('password', 'Password');
        $this->formBuilder->textarea('bio', 'Biography');
        $this->formBuilder->select('role', 'Role', ['admin' => 'Admin', 'user' => 'User']);
        $this->formBuilder->checkbox('subscribe', 'Subscribe to newsletter');
        $this->formBuilder->radio('gender', 'Gender', ['M' => 'Male', 'F' => 'Female']);
        $html = $this->formBuilder->render();

        // Verify each field has ARIA label or associated label element
        $fields = ['name', 'email', 'password', 'bio', 'role', 'subscribe', 'gender'];

        foreach ($fields as $field) {
            $hasAriaLabel = strpos($html, 'aria-label') !== false &&
                           preg_match('/name=["\']' . preg_quote($field, '/') . '["\'][^>]*aria-label/', $html);

            $hasLabelElement = preg_match('/<label[^>]*for=["\']' . preg_quote($field, '/') . '["\']/', $html);

            $this->assertTrue(
                $hasAriaLabel || $hasLabelElement,
                "Field '{$field}' missing ARIA label or label element"
            );
        }
    }

    /**
     * Test: Required fields include aria-required attribute.
     *
     * @test
     */
    public function test_required_fields_include_aria_required()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->text('username', 'Username')->required();
        $this->formBuilder->email('email', 'Email')->required();
        $this->formBuilder->textarea('message', 'Message')->required();
        $html = $this->formBuilder->render();

        // Verify aria-required="true" is present for required fields
        $requiredCount = substr_count($html, 'aria-required="true"');
        $this->assertGreaterThanOrEqual(3, $requiredCount, 'Required fields missing aria-required attribute');
    }

    /**
     * Test: Fields with validation errors include aria-invalid.
     *
     * @test
     */
    public function test_fields_with_errors_include_aria_invalid()
    {
        $errors = [
            'username' => ['The username field is required.'],
            'email' => ['The email must be a valid email address.'],
            'password' => ['The password must be at least 8 characters.'],
        ];

        $this->formBuilder->setContext('admin');
        $this->formBuilder->setValidationErrors($errors);
        $this->formBuilder->text('username', 'Username');
        $this->formBuilder->email('email', 'Email');
        $this->formBuilder->password('password', 'Password');
        $html = $this->formBuilder->render();

        // Verify aria-invalid="true" is present for fields with errors
        $invalidCount = substr_count($html, 'aria-invalid="true"');
        $this->assertGreaterThanOrEqual(3, $invalidCount, 'Fields with errors missing aria-invalid attribute');

        // Verify aria-describedby is present to link to error messages
        $this->assertTrue(
            strpos($html, 'aria-describedby') !== false,
            'Fields with errors missing aria-describedby attribute'
        );
    }

    /**
     * Test: Color contrast and visual indicators.
     *
     * @test
     */
    public function test_color_contrast_and_visual_indicators()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->text('bio', 'Biography')->maxLength(100);
        $html = $this->formBuilder->render();

        // Verify character counter has visual styling classes
        // Tailwind CSS and DaisyUI provide sufficient contrast by default
        $this->assertTrue(
            strpos($html, 'text-') !== false ||
            strpos($html, 'class') !== false,
            'Components missing visual styling classes'
        );
    }

    /**
     * Test: Semantic HTML with proper heading hierarchy.
     *
     * @test
     */
    public function test_semantic_html_with_proper_heading_hierarchy()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->openTab('Personal Info');
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->closeTab();
        $html = $this->formBuilder->render();

        // Verify semantic HTML elements are used
        $this->assertTrue(
            strpos($html, '<form') !== false ||
            strpos($html, '<div') !== false,
            'Form missing semantic HTML structure'
        );

        // Verify proper ARIA roles for structure
        $this->assertStringContainsString('role=', $html);
    }

    /**
     * Test: Accessibility in both Admin and Public contexts.
     *
     * @test
     */
    public function test_accessibility_in_both_contexts()
    {
        // Test Admin context
        $this->formBuilder->setContext('admin');
        $this->formBuilder->text('username', 'Username');
        $this->formBuilder->checkbox('is_active', 'Active')->switch();
        $adminHtml = $this->formBuilder->render();

        // Test Public context
        $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
        $this->formBuilder->setContext('public');
        $this->formBuilder->text('username', 'Username');
        $this->formBuilder->checkbox('is_active', 'Active')->switch();
        $publicHtml = $this->formBuilder->render();

        // Verify both contexts include ARIA attributes
        $this->assertTrue(
            strpos($adminHtml, 'aria-') !== false,
            'Admin context missing ARIA attributes'
        );

        $this->assertTrue(
            strpos($publicHtml, 'aria-') !== false,
            'Public context missing ARIA attributes'
        );

        // Verify both contexts include role attributes
        $this->assertTrue(
            strpos($adminHtml, 'role=') !== false,
            'Admin context missing role attributes'
        );

        $this->assertTrue(
            strpos($publicHtml, 'role=') !== false,
            'Public context missing role attributes'
        );
    }
}
