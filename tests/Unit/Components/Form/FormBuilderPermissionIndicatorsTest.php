<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use ReflectionClass;

/**
 * Test for FormBuilder permission indicators.
 *
 * Requirements:
 * - Requirement 7.6: Display message when fields are hidden due to permissions
 * - Requirement 18.1: Use theme colors for permission-related UI elements
 * - Requirement 17.1: Use i18n for all permission-related messages
 */
class FormBuilderPermissionIndicatorsTest extends TestCase
{
    protected FormBuilder $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = $this->app->make(FormBuilder::class);
    }

    /**
     * Set hidden fields using reflection.
     *
     * @param array $hiddenFields
     * @return void
     */
    protected function setHiddenFields(array $hiddenFields): void
    {
        $reflection = new ReflectionClass($this->form);
        $property = $reflection->getProperty('hiddenFields');
        $property->setAccessible(true);
        $property->setValue($this->form, $hiddenFields);
    }

    /**
     * Call protected method using reflection.
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    protected function callProtectedMethod(string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($this->form);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->form, $args);
    }

    /**
     * Test that permission indicators are not shown when no fields are hidden.
     *
     * @return void
     */
    public function test_no_indicators_when_no_fields_hidden(): void
    {
        $this->form->setContext('admin');
        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');

        // No hidden fields set
        $html = $this->callProtectedMethod('renderPermissionIndicators');

        $this->assertEmpty($html);
        $this->assertStringNotContainsString('alert alert-info', $html);
        $this->assertStringNotContainsString('data-lucide="lock"', $html);
    }

    /**
     * Test that permission indicator is shown for single hidden field.
     *
     * @return void
     */
    public function test_indicator_shown_for_single_hidden_field(): void
    {
        $this->form->setContext('admin');

        // Create a mock field
        $field = new TextField('email', 'Email');

        // Set hidden fields
        $this->setHiddenFields([
            'email' => [
                'field' => $field,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that indicator is present
        $this->assertStringContainsString('alert alert-info', $html);
        $this->assertStringContainsString('data-lucide="lock"', $html);

        // Check that it mentions the hidden field
        $this->assertStringContainsString('Email', $html);
    }

    /**
     * Test that permission indicator is shown for multiple hidden fields.
     *
     * @return void
     */
    public function test_indicator_shown_for_multiple_hidden_fields(): void
    {
        $this->form->setContext('admin');

        // Create mock fields
        $emailField = new TextField('email', 'Email');
        $phoneField = new TextField('phone', 'Phone');

        // Set hidden fields
        $this->setHiddenFields([
            'email' => [
                'field' => $emailField,
                'reason' => 'column_level_denied',
            ],
            'phone' => [
                'field' => $phoneField,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that indicator is present
        $this->assertStringContainsString('alert alert-info', $html);
        $this->assertStringContainsString('data-lucide="lock"', $html);

        // Check that it mentions multiple fields
        $this->assertStringContainsString('2', $html);
    }

    /**
     * Test that permission indicator uses theme colors.
     *
     * @return void
     */
    public function test_indicator_uses_theme_colors(): void
    {
        $this->form->setContext('admin');

        // Create a mock field
        $field = new TextField('email', 'Email');

        // Set hidden fields
        $this->setHiddenFields([
            'email' => [
                'field' => $field,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that theme colors are used in style attribute
        $this->assertStringContainsString('style=', $html);
        $this->assertStringContainsString('background:', $html);
        $this->assertStringContainsString('color:', $html);
        $this->assertStringContainsString('border:', $html);
    }

    /**
     * Test that permission indicator uses i18n for messages.
     *
     * @return void
     */
    public function test_indicator_uses_i18n_messages(): void
    {
        // Set locale to English
        App::setLocale('en');

        $this->form->setContext('admin');

        // Create a mock field
        $field = new TextField('email', 'Email');

        // Set hidden fields
        $this->setHiddenFields([
            'email' => [
                'field' => $field,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that English translation is used
        $this->assertStringContainsString('hidden due to permissions', $html);

        // Now test with Indonesian locale
        App::setLocale('id');

        // Create new form instance for clean state
        $this->form = $this->app->make(FormBuilder::class);
        $this->form->setContext('admin');

        // Set hidden fields again
        $this->setHiddenFields([
            'email' => [
                'field' => $field,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that Indonesian translation is used
        $this->assertStringContainsString('disembunyikan karena izin', $html);
    }

    /**
     * Test that JSON attribute hidden fields show separate indicator.
     *
     * @return void
     */
    public function test_indicator_shown_for_json_attribute_fields(): void
    {
        $this->form->setContext('admin');

        // Create a mock field
        $field = new TextField('metadata.seo.title', 'SEO Title');

        // Set hidden fields with JSON attribute reason
        $this->setHiddenFields([
            'metadata.seo.title' => [
                'field' => $field,
                'reason' => 'json_attribute_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that indicator is present
        $this->assertStringContainsString('alert alert-info', $html);
        $this->assertStringContainsString('data-lucide="lock"', $html);

        // Check that it mentions nested field
        $this->assertStringContainsString('nested', $html);
    }

    /**
     * Test that indicators can be disabled via config.
     *
     * @return void
     */
    public function test_indicators_can_be_disabled_via_config(): void
    {
        // Disable indicators in config
        config(['canvastack-rbac.fine_grained.show_indicators' => false]);

        $this->form->setContext('admin');

        // Create a mock field
        $field = new TextField('email', 'Email');

        // Set hidden fields
        $this->setHiddenFields([
            'email' => [
                'field' => $field,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that no indicator is shown
        $this->assertEmpty($html);
        $this->assertStringNotContainsString('alert alert-info', $html);
        $this->assertStringNotContainsString('data-lucide="lock"', $html);
    }

    /**
     * Test that theme color fallback works when theme system is unavailable.
     *
     * @return void
     */
    public function test_theme_color_fallback(): void
    {
        $this->form->setContext('admin');

        // Create a mock field
        $field = new TextField('email', 'Email');

        // Set hidden fields
        $this->setHiddenFields([
            'email' => [
                'field' => $field,
                'reason' => 'column_level_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that fallback colors are used (hex codes)
        $this->assertMatchesRegularExpression('/#[0-9a-f]{6}/i', $html);
    }

    /**
     * Test that both column and JSON fields show separate indicators.
     *
     * @return void
     */
    public function test_separate_indicators_for_column_and_json_fields(): void
    {
        $this->form->setContext('admin');

        // Create mock fields
        $emailField = new TextField('email', 'Email');
        $jsonField = new TextField('metadata.seo.title', 'SEO Title');

        // Set hidden fields with different reasons
        $this->setHiddenFields([
            'email' => [
                'field' => $emailField,
                'reason' => 'column_level_denied',
            ],
            'metadata.seo.title' => [
                'field' => $jsonField,
                'reason' => 'json_attribute_denied',
            ],
        ]);

        $html = $this->callProtectedMethod('renderPermissionIndicators');

        // Check that both indicators are present
        $this->assertStringContainsString('alert alert-info', $html);

        // Should have 2 separate alert divs
        $this->assertEquals(2, substr_count($html, 'alert alert-info'));

        // Should mention both types
        $this->assertStringContainsString('field', $html);
        $this->assertStringContainsString('nested', $html);
    }
}
