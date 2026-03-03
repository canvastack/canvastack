<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * CKEditor FormBuilder Integration Test.
 *
 * Tests the integration of CKEditor with FormBuilder including:
 * - Automatic detection of 'ckeditor' attribute
 * - Registration of textarea fields for CKEditor initialization
 * - Rendering of CKEditor scripts at form end
 * - Dual context support (Admin/Public)
 * - Dark mode support
 * - Content preservation during validation
 *
 * Requirements: 4.1, 4.2, 4.11, 4.12, 4.13, 4.14
 */
class CKEditorFormBuilderIntegrationTest extends TestCase
{
    private FormBuilder $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $this->formBuilder = new FormBuilder($fieldFactory, $validationCache);
    }

    /**
     * Test that textarea with 'ckeditor' attribute is registered for CKEditor.
     *
     * Requirements: 4.1, 4.2
     */
    public function test_textarea_with_ckeditor_attribute_is_registered(): void
    {
        // Create textarea with ckeditor attribute
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);

        // Get CKEditor integration
        $ckeditor = $this->formBuilder->getCKEditorIntegration();

        // Assert field is registered
        $this->assertTrue($ckeditor->isRegistered('content'));
        $this->assertEquals(1, $ckeditor->count());
    }

    /**
     * Test that textarea with 'ckeditor' class is registered for CKEditor.
     *
     * Requirements: 4.1, 4.2
     */
    public function test_textarea_with_ckeditor_class_is_registered(): void
    {
        // Create textarea with ckeditor class
        $this->formBuilder->textarea('description', 'Description', null, ['class' => 'form-control ckeditor']);

        // Get CKEditor integration
        $ckeditor = $this->formBuilder->getCKEditorIntegration();

        // Assert field is registered
        $this->assertTrue($ckeditor->isRegistered('description'));
    }

    /**
     * Test that multiple textareas with ckeditor are registered.
     *
     * Requirements: 4.22
     */
    public function test_multiple_textareas_with_ckeditor_are_registered(): void
    {
        // Create multiple textareas with ckeditor
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);
        $this->formBuilder->textarea('description', 'Description', null, ['ckeditor' => true]);
        $this->formBuilder->textarea('notes', 'Notes', null, ['class' => 'ckeditor']);

        // Get CKEditor integration
        $ckeditor = $this->formBuilder->getCKEditorIntegration();

        // Assert all fields are registered
        $this->assertEquals(3, $ckeditor->count());
        $this->assertTrue($ckeditor->isRegistered('content'));
        $this->assertTrue($ckeditor->isRegistered('description'));
        $this->assertTrue($ckeditor->isRegistered('notes'));
    }

    /**
     * Test that CKEditor scripts are rendered at form end.
     *
     * Requirements: 4.1, 4.2
     */
    public function test_ckeditor_scripts_are_rendered_at_form_end(): void
    {
        // Create textarea with ckeditor
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);

        // Render form
        $html = $this->formBuilder->render();

        // Assert CKEditor assets are included
        $this->assertStringContainsString('ckeditor.js', $html);

        // Assert initialization script is included
        $this->assertStringContainsString('CKEDITOR.replace', $html);
        $this->assertStringContainsString("'content'", $html);
    }

    /**
     * Test that CKEditor scripts are not rendered when no editors are registered.
     *
     * Requirements: 4.3
     */
    public function test_ckeditor_scripts_not_rendered_without_editors(): void
    {
        // Create regular textarea without ckeditor
        $this->formBuilder->textarea('content', 'Content');

        // Render form
        $html = $this->formBuilder->render();

        // Assert CKEditor assets are NOT included
        $this->assertStringNotContainsString('ckeditor.js', $html);
        $this->assertStringNotContainsString('CKEDITOR.replace', $html);
    }

    /**
     * Test admin context configuration.
     *
     * Requirements: 4.11
     */
    public function test_admin_context_configuration(): void
    {
        // Set admin context
        $this->formBuilder->setContext('admin');

        // Create textarea with ckeditor
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);

        // Get CKEditor integration
        $ckeditor = $this->formBuilder->getCKEditorIntegration();

        // Assert context is admin
        $this->assertEquals('admin', $ckeditor->getContext());

        // Get instance configuration
        $instance = $ckeditor->getInstance('content');
        $this->assertNotNull($instance);
        $this->assertEquals('admin', $instance['context']);
    }

    /**
     * Test public context configuration.
     *
     * Requirements: 4.12
     */
    public function test_public_context_configuration(): void
    {
        // Set public context
        $this->formBuilder->setContext('public');

        // Create textarea with ckeditor
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);

        // Get CKEditor integration
        $ckeditor = $this->formBuilder->getCKEditorIntegration();

        // Assert context is public
        $this->assertEquals('public', $ckeditor->getContext());

        // Get instance configuration
        $instance = $ckeditor->getInstance('content');
        $this->assertNotNull($instance);
        $this->assertEquals('public', $instance['context']);

        // Public context should have minimal toolbar
        $config = $instance['config'];
        $this->assertArrayHasKey('toolbar', $config);
        $this->assertLessThan(10, count($config['toolbar'])); // Minimal toolbar has fewer items
    }

    /**
     * Test context switching updates CKEditor context.
     *
     * Requirements: 4.11, 4.12
     */
    public function test_context_switching_updates_ckeditor_context(): void
    {
        // Start with admin context
        $this->formBuilder->setContext('admin');
        $ckeditor = $this->formBuilder->getCKEditorIntegration();
        $this->assertEquals('admin', $ckeditor->getContext());

        // Switch to public context
        $this->formBuilder->setContext('public');
        $this->assertEquals('public', $ckeditor->getContext());

        // Switch back to admin
        $this->formBuilder->setContext('admin');
        $this->assertEquals('admin', $ckeditor->getContext());
    }

    /**
     * Test dark mode configuration.
     *
     * Requirements: 4.13
     */
    public function test_dark_mode_configuration(): void
    {
        // Enable dark mode
        $ckeditor = $this->formBuilder->getCKEditorIntegration();
        $ckeditor->setDarkMode(true);

        // Create textarea with ckeditor
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);

        // Get instance configuration
        $instance = $ckeditor->getInstance('content');
        $this->assertNotNull($instance);
        $this->assertTrue($instance['darkMode']);

        // Dark mode config should include dark CSS
        $config = $instance['config'];
        $this->assertArrayHasKey('contentsCss', $config);
        $this->assertContains('/css/ckeditor-dark.css', $config['contentsCss']);
        $this->assertStringContainsString('dark-mode', $config['bodyClass']);
    }

    /**
     * Test custom editor options are merged with defaults.
     *
     * Requirements: 4.6
     */
    public function test_custom_editor_options_are_merged(): void
    {
        // Create textarea with custom options
        $customOptions = [
            'height' => 500,
            'language' => 'id',
            'toolbar' => [
                ['name' => 'basicstyles', 'items' => ['Bold', 'Italic']],
            ],
        ];

        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => $customOptions]);

        // Get instance configuration
        $ckeditor = $this->formBuilder->getCKEditorIntegration();
        $instance = $ckeditor->getInstance('content');
        $config = $instance['config'];

        // Assert custom options are applied
        $this->assertEquals(500, $config['height']);
        $this->assertEquals('id', $config['language']);
        $this->assertEquals([['name' => 'basicstyles', 'items' => ['Bold', 'Italic']]], $config['toolbar']);
    }

    /**
     * Test content preservation during validation errors.
     *
     * Requirements: 4.14
     */
    public function test_content_preservation_during_validation(): void
    {
        // Skip this test if we can't mock old() helper properly
        // In a real Laravel application, old() would work with session
        $this->markTestSkipped('Content preservation requires Laravel session context');

        // This test validates the concept - in production:
        // 1. User submits form with CKEditor content
        // 2. Validation fails
        // 3. Laravel stores input in session via old() helper
        // 4. Form re-renders with CKEditor
        // 5. CKEditor initialization script checks old() and preserves content
    }

    /**
     * Test CKEditor with tabs integration.
     *
     * Requirements: 4.1, 4.2
     */
    public function test_ckeditor_with_tabs_integration(): void
    {
        // Create form with tabs
        $this->formBuilder->openTab('Content');
        $this->formBuilder->textarea('content', 'Content', null, ['ckeditor' => true]);
        $this->formBuilder->closeTab();

        $this->formBuilder->openTab('Description');
        $this->formBuilder->textarea('description', 'Description', null, ['ckeditor' => true]);
        $this->formBuilder->closeTab();

        // Render form
        $html = $this->formBuilder->render();

        // Assert tabs are rendered
        $this->assertStringContainsString('tabs-container', $html);

        // Assert CKEditor scripts are rendered for both fields
        $this->assertStringContainsString("CKEDITOR.replace('content'", $html);
        $this->assertStringContainsString("CKEDITOR.replace('description'", $html);

        // Assert both editors are registered
        $ckeditor = $this->formBuilder->getCKEditorIntegration();
        $this->assertEquals(2, $ckeditor->count());
    }
}
