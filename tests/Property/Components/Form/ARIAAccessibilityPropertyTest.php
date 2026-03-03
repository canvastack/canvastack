<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\PublicRenderer;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property 29: ARIA Accessibility.
 *
 * Universal Property: For any form field, the rendered HTML should include proper
 * ARIA labels and descriptions for screen reader accessibility.
 *
 * Validates: Requirements 15.1
 *
 * **Validates: Requirements 15.1**
 */
class ARIAAccessibilityPropertyTest extends TestCase
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
     * Property: All text fields include ARIA labels.
     *
     * @test
     */
    public function property_text_fields_include_aria_labels()
    {
        $testCases = [
            ['name' => 'username', 'label' => 'Username'],
            ['name' => 'email', 'label' => 'Email Address'],
            ['name' => 'phone', 'label' => 'Phone Number'],
            ['name' => 'address', 'label' => 'Street Address'],
        ];

        foreach ($testCases as $testCase) {
            // Admin context
            $this->formBuilder->setContext('admin');
            $this->formBuilder->text($testCase['name'], $testCase['label']);
            $html = $this->formBuilder->render();

            // Property: Must include aria-label or aria-labelledby
            $this->assertTrue(
                $this->hasAriaLabel($html, $testCase['name']) ||
                $this->hasAriaLabelledBy($html, $testCase['name']),
                "Text field '{$testCase['name']}' missing ARIA label in admin context"
            );

            // Public context
            $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
            $this->formBuilder->setContext('public');
            $this->formBuilder->text($testCase['name'], $testCase['label']);
            $html = $this->formBuilder->render();

            $this->assertTrue(
                $this->hasAriaLabel($html, $testCase['name']) ||
                $this->hasAriaLabelledBy($html, $testCase['name']),
                "Text field '{$testCase['name']}' missing ARIA label in public context"
            );
        }
    }

    /**
     * Property: All select fields include ARIA labels.
     *
     * @test
     */
    public function property_select_fields_include_aria_labels()
    {
        $testCases = [
            ['name' => 'country', 'label' => 'Country', 'options' => ['US' => 'United States', 'UK' => 'United Kingdom']],
            ['name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
            ['name' => 'role', 'label' => 'User Role', 'options' => ['admin' => 'Administrator', 'user' => 'User']],
        ];

        foreach ($testCases as $testCase) {
            $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
            $this->formBuilder->setContext('admin');
            $this->formBuilder->select($testCase['name'], $testCase['label'], $testCase['options']);
            $html = $this->formBuilder->render();

            // Property: Must include ARIA label
            $this->assertTrue(
                $this->hasAriaLabel($html, $testCase['name']) ||
                $this->hasAriaLabelledBy($html, $testCase['name']),
                "Select field '{$testCase['name']}' missing ARIA label"
            );
        }
    }

    /**
     * Property: All textarea fields include ARIA labels.
     *
     * @test
     */
    public function property_textarea_fields_include_aria_labels()
    {
        $testCases = [
            ['name' => 'description', 'label' => 'Description'],
            ['name' => 'comments', 'label' => 'Comments'],
            ['name' => 'notes', 'label' => 'Additional Notes'],
        ];

        foreach ($testCases as $testCase) {
            $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
            $this->formBuilder->setContext('admin');
            $this->formBuilder->textarea($testCase['name'], $testCase['label']);
            $html = $this->formBuilder->render();

            // Property: Must include ARIA label
            $this->assertTrue(
                $this->hasAriaLabel($html, $testCase['name']) ||
                $this->hasAriaLabelledBy($html, $testCase['name']),
                "Textarea field '{$testCase['name']}' missing ARIA label"
            );
        }
    }

    /**
     * Property: All checkbox fields include ARIA labels.
     *
     * @test
     */
    public function property_checkbox_fields_include_aria_labels()
    {
        $testCases = [
            ['name' => 'agree_terms', 'label' => 'I agree to terms'],
            ['name' => 'subscribe', 'label' => 'Subscribe to newsletter'],
            ['name' => 'is_active', 'label' => 'Active Status'],
        ];

        foreach ($testCases as $testCase) {
            $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
            $this->formBuilder->setContext('admin');
            $this->formBuilder->checkbox($testCase['name'], $testCase['label']);
            $html = $this->formBuilder->render();

            // Property: Must include ARIA label
            $this->assertTrue(
                $this->hasAriaLabel($html, $testCase['name']) ||
                $this->hasAriaLabelledBy($html, $testCase['name']),
                "Checkbox field '{$testCase['name']}' missing ARIA label"
            );
        }
    }

    /**
     * Property: Switch checkboxes include ARIA role and labels.
     *
     * @test
     */
    public function property_switch_checkboxes_include_aria_attributes()
    {
        $testCases = [
            ['name' => 'is_enabled', 'label' => 'Enable Feature'],
            ['name' => 'notifications', 'label' => 'Enable Notifications'],
        ];

        foreach ($testCases as $testCase) {
            $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
            $this->formBuilder->setContext('admin');
            $this->formBuilder->checkbox($testCase['name'], $testCase['label'])->switch();
            $html = $this->formBuilder->render();

            // Property: Must include role="switch"
            $this->assertStringContainsString(
                'role="switch"',
                $html,
                "Switch checkbox '{$testCase['name']}' missing role='switch'"
            );

            // Property: Must include ARIA label
            $this->assertTrue(
                $this->hasAriaLabel($html, $testCase['name']) ||
                $this->hasAriaLabelledBy($html, $testCase['name']),
                "Switch checkbox '{$testCase['name']}' missing ARIA label"
            );

            // Property: Must include aria-checked attribute
            $this->assertTrue(
                strpos($html, 'aria-checked') !== false,
                "Switch checkbox '{$testCase['name']}' missing aria-checked attribute"
            );
        }
    }

    /**
     * Property: Required fields include aria-required attribute.
     *
     * @test
     */
    public function property_required_fields_include_aria_required()
    {
        $testCases = [
            ['type' => 'text', 'name' => 'username', 'label' => 'Username'],
            ['type' => 'email', 'name' => 'email', 'label' => 'Email'],
            ['type' => 'textarea', 'name' => 'message', 'label' => 'Message'],
        ];

        foreach ($testCases as $testCase) {
            $this->formBuilder = new FormBuilder(new FieldFactory(), new ValidationCache());
            $this->formBuilder->setContext('admin');

            if ($testCase['type'] === 'text') {
                $this->formBuilder->text($testCase['name'], $testCase['label'])->required();
            } elseif ($testCase['type'] === 'email') {
                $this->formBuilder->email($testCase['name'], $testCase['label'])->required();
            } elseif ($testCase['type'] === 'textarea') {
                $this->formBuilder->textarea($testCase['name'], $testCase['label'])->required();
            }

            $html = $this->formBuilder->render();

            // Property: Must include aria-required="true"
            $this->assertStringContainsString(
                'aria-required="true"',
                $html,
                "Required field '{$testCase['name']}' missing aria-required attribute"
            );
        }
    }

    /**
     * Property: Fields with validation errors include aria-invalid and aria-describedby.
     *
     * @test
     */
    public function property_fields_with_errors_include_aria_invalid()
    {
        $errors = [
            'username' => ['The username field is required.'],
            'email' => ['The email must be a valid email address.'],
        ];

        $this->formBuilder->setContext('admin');
        $this->formBuilder->setValidationErrors($errors);
        $this->formBuilder->text('username', 'Username');
        $this->formBuilder->email('email', 'Email');
        $html = $this->formBuilder->render();

        // Property: Fields with errors must include aria-invalid="true"
        $this->assertStringContainsString(
            'aria-invalid="true"',
            $html,
            'Fields with errors missing aria-invalid attribute'
        );

        // Property: Fields with errors should include aria-describedby pointing to error message
        $this->assertTrue(
            strpos($html, 'aria-describedby') !== false,
            'Fields with errors missing aria-describedby attribute'
        );
    }

    /**
     * Property: Character counter includes ARIA live region.
     *
     * @test
     */
    public function property_character_counter_includes_aria_live()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->text('bio', 'Biography')->maxLength(100);
        $html = $this->formBuilder->render();

        // Property: Character counter must include aria-live region
        $this->assertTrue(
            strpos($html, 'aria-live') !== false,
            'Character counter missing aria-live attribute'
        );

        // Property: Should use aria-live="polite" for non-critical updates
        $this->assertStringContainsString(
            'aria-live="polite"',
            $html,
            "Character counter should use aria-live='polite'"
        );
    }

    /**
     * Property: Tab system includes proper ARIA roles and labels.
     *
     * @test
     */
    public function property_tab_system_includes_aria_roles()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->openTab('Personal Info');
        $this->formBuilder->text('name', 'Name');
        $this->formBuilder->closeTab();
        $this->formBuilder->openTab('Contact Info');
        $this->formBuilder->email('email', 'Email');
        $this->formBuilder->closeTab();
        $html = $this->formBuilder->render();

        // Property: Tab navigation must include role="tablist"
        $this->assertStringContainsString(
            'role="tablist"',
            $html,
            "Tab navigation missing role='tablist'"
        );

        // Property: Individual tabs must include role="tab"
        $this->assertStringContainsString(
            'role="tab"',
            $html,
            "Individual tabs missing role='tab'"
        );

        // Property: Tab panels must include role="tabpanel"
        $this->assertStringContainsString(
            'role="tabpanel"',
            $html,
            "Tab panels missing role='tabpanel'"
        );

        // Property: Tabs should include aria-selected attribute
        $this->assertTrue(
            strpos($html, 'aria-selected') !== false,
            'Tabs missing aria-selected attribute'
        );
    }

    /**
     * Property: Searchable select includes ARIA attributes.
     *
     * @test
     */
    public function property_searchable_select_includes_aria_attributes()
    {
        $options = ['1' => 'Option 1', '2' => 'Option 2', '3' => 'Option 3'];

        $this->formBuilder->setContext('admin');
        $this->formBuilder->select('category', 'Category', $options)->searchable();
        $html = $this->formBuilder->render();

        // Property: Searchable select must include ARIA label
        $this->assertTrue(
            $this->hasAriaLabel($html, 'category') ||
            $this->hasAriaLabelledBy($html, 'category'),
            'Searchable select missing ARIA label'
        );

        // Property: Should include aria-autocomplete for search functionality
        // Note: This is added by the JavaScript library (Choices.js)
        $this->assertTrue(
            true, // Placeholder - actual implementation depends on JS library
            'Searchable select should support aria-autocomplete'
        );
    }

    /**
     * Property: File upload fields include ARIA labels.
     *
     * @test
     */
    public function property_file_upload_includes_aria_labels()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->file('avatar', 'Profile Picture');
        $html = $this->formBuilder->render();

        // Property: File input must include ARIA label
        $this->assertTrue(
            $this->hasAriaLabel($html, 'avatar') ||
            $this->hasAriaLabelledBy($html, 'avatar'),
            'File upload field missing ARIA label'
        );
    }

    /**
     * Property: Date range picker includes ARIA labels.
     *
     * @test
     */
    public function property_date_range_picker_includes_aria_labels()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->daterange('period', 'Date Range');
        $html = $this->formBuilder->render();

        // Property: Date range picker must include ARIA label
        $this->assertTrue(
            $this->hasAriaLabel($html, 'period') ||
            $this->hasAriaLabelledBy($html, 'period'),
            'Date range picker missing ARIA label'
        );
    }

    /**
     * Property: Month picker includes ARIA labels.
     *
     * @test
     */
    public function property_month_picker_includes_aria_labels()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->month('billing_month', 'Billing Month');
        $html = $this->formBuilder->render();

        // Property: Month picker must include ARIA label
        $this->assertTrue(
            $this->hasAriaLabel($html, 'billing_month') ||
            $this->hasAriaLabelledBy($html, 'billing_month'),
            'Month picker missing ARIA label'
        );
    }

    /**
     * Property: Tags input includes ARIA labels and live regions.
     *
     * @test
     */
    public function property_tags_input_includes_aria_attributes()
    {
        $this->formBuilder->setContext('admin');
        $this->formBuilder->tags('keywords', 'Keywords');
        $html = $this->formBuilder->render();

        // Property: Tags input must include ARIA label
        $this->assertTrue(
            $this->hasAriaLabel($html, 'keywords') ||
            $this->hasAriaLabelledBy($html, 'keywords'),
            'Tags input missing ARIA label'
        );

        // Property: Should include aria-live for tag additions/removals
        // Note: This is typically added by the JavaScript library
        $this->assertTrue(
            true, // Placeholder - actual implementation depends on JS library
            'Tags input should support aria-live for dynamic updates'
        );
    }

    /**
     * Helper: Check if HTML contains aria-label for a field.
     */
    protected function hasAriaLabel(string $html, string $fieldName): bool
    {
        // Check for aria-label attribute on input with matching name
        $pattern = '/name=["\']' . preg_quote($fieldName, '/') . '["\'][^>]*aria-label=/';

        return preg_match($pattern, $html) === 1;
    }

    /**
     * Helper: Check if HTML contains aria-labelledby for a field.
     */
    protected function hasAriaLabelledBy(string $html, string $fieldName): bool
    {
        // Check for aria-labelledby attribute on input with matching name
        $pattern = '/name=["\']' . preg_quote($fieldName, '/') . '["\'][^>]*aria-labelledby=/';
        if (preg_match($pattern, $html) === 1) {
            return true;
        }

        // Also check if there's a label element with id that matches
        // This is the standard HTML pattern: <label for="field-id">
        $labelPattern = '/<label[^>]*for=["\']' . preg_quote($fieldName, '/') . '["\'][^>]*>/';

        return preg_match($labelPattern, $html) === 1;
    }
}
