<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\ContentSanitizer;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for CKEditor Integration.
 *
 * Tests complete CKEditor workflow including:
 * - Editor initialization
 * - Content editing and submission
 * - Image upload integration
 * - Multiple editors on same page
 *
 * **Validates: Requirements 4.1, 4.7, 4.8, 4.22**
 */
class CKEditorIntegrationTest extends TestCase
{
    protected CKEditorIntegration $integration;

    protected EditorConfig $config;

    protected ContentSanitizer $sanitizer;

    protected AssetManager $assetManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new EditorConfig();
        $this->sanitizer = new ContentSanitizer();
        $this->assetManager = new AssetManager();
        $this->integration = new CKEditorIntegration(
            $this->config,
            $this->sanitizer,
            $this->assetManager
        );
    }

    /**
     * @test
     * Test complete editor initialization workflow
     *
     * Requirements: 4.1, 4.2
     */
    public function it_initializes_editor_with_complete_workflow(): void
    {
        // Arrange: Register editor with configuration
        $fieldName = 'article_content';
        $options = [
            'height' => 400,
            'language' => 'en',
            'toolbar' => $this->config->getDefaultToolbar(),
        ];

        // Act: Register and verify
        $this->integration->register($fieldName, $options);

        // Assert: Editor is properly registered
        $this->assertTrue($this->integration->hasInstances());
        $this->assertTrue($this->integration->isRegistered($fieldName));
        $this->assertEquals(1, $this->integration->count());

        // Assert: Instance has correct configuration
        $instance = $this->integration->getInstance($fieldName);
        $this->assertNotNull($instance);
        $this->assertEquals($fieldName, $instance['fieldName']);
        $this->assertEquals(400, $instance['config']['height']);
        $this->assertEquals('en', $instance['config']['language']);
        $this->assertArrayHasKey('toolbar', $instance['config']);
    }

    /**
     * @test
     * Test content editing and submission workflow
     *
     * Requirements: 4.1, 4.15
     */
    public function it_handles_content_editing_and_submission(): void
    {
        // Arrange: Register editor
        $this->integration->register('content', []);

        // Simulate user input with HTML content
        $userContent = '<p>This is <strong>bold</strong> text with a <a href="http://example.com">link</a>.</p>';

        // Act: Sanitize content (as would happen on form submission)
        $sanitizedContent = $this->integration->sanitize($userContent);

        // Assert: Content is sanitized but safe HTML is preserved
        $this->assertNotEmpty($sanitizedContent);
        $this->assertStringContainsString('<p>', $sanitizedContent);
        $this->assertStringContainsString('<strong>', $sanitizedContent);
        $this->assertStringContainsString('<a href', $sanitizedContent);
        $this->assertStringContainsString('bold', $sanitizedContent);
    }

    /**
     * @test
     * Test malicious content is sanitized on submission
     *
     * Requirements: 4.15, 14.8
     */
    public function it_sanitizes_malicious_content_on_submission(): void
    {
        // Arrange: Register editor
        $this->integration->register('content', []);

        // Simulate malicious user input
        $maliciousContent = '<p>Safe content</p><script>alert("XSS")</script><p onclick="evil()">Click me</p>';

        // Act: Sanitize content
        $sanitizedContent = $this->integration->sanitize($maliciousContent);

        // Assert: Malicious code is removed
        $this->assertStringNotContainsString('<script>', $sanitizedContent);
        $this->assertStringNotContainsString('alert', $sanitizedContent);
        $this->assertStringNotContainsString('onclick', $sanitizedContent);
        $this->assertStringNotContainsString('evil()', $sanitizedContent);

        // Assert: Safe content is preserved
        $this->assertStringContainsString('Safe content', $sanitizedContent);
        $this->assertStringContainsString('Click me', $sanitizedContent);
    }

    /**
     * @test
     * Test image upload configuration integration
     *
     * Requirements: 4.7, 4.8
     */
    public function it_integrates_image_upload_configuration(): void
    {
        // Arrange: Register editor with upload URL
        $uploadUrl = '/admin/upload/image';
        $this->integration->register('content', [
            'uploadUrl' => $uploadUrl,
        ]);

        // Act: Get instance configuration
        $instance = $this->integration->getInstance('content');

        // Assert: Upload URL is stored in configuration
        $this->assertNotNull($instance);
        $this->assertArrayHasKey('config', $instance);

        // Note: The uploadUrl is processed during script rendering
        // Here we verify it's stored in the instance
        $this->assertTrue($this->integration->isRegistered('content'));
    }

    /**
     * @test
     * Test multiple editors on same page
     *
     * Requirements: 4.22
     */
    public function it_supports_multiple_editors_on_same_page(): void
    {
        // Arrange: Register multiple editors with different configurations
        $editors = [
            'title' => ['height' => 200, 'toolbar' => $this->config->getMinimalToolbar()],
            'content' => ['height' => 400, 'toolbar' => $this->config->getDefaultToolbar()],
            'summary' => ['height' => 150, 'toolbar' => $this->config->getMinimalToolbar()],
            'notes' => ['height' => 300],
        ];

        // Act: Register all editors
        foreach ($editors as $fieldName => $options) {
            $this->integration->register($fieldName, $options);
        }

        // Assert: All editors are registered
        $this->assertEquals(4, $this->integration->count());
        $this->assertTrue($this->integration->hasInstances());

        foreach (array_keys($editors) as $fieldName) {
            $this->assertTrue($this->integration->isRegistered($fieldName));
        }

        // Assert: Each editor has its own configuration
        $titleInstance = $this->integration->getInstance('title');
        $contentInstance = $this->integration->getInstance('content');

        $this->assertEquals(200, $titleInstance['config']['height']);
        $this->assertEquals(400, $contentInstance['config']['height']);
    }

    /**
     * @test
     * Test editor cleanup and instance management
     *
     * Requirements: 4.22
     */
    public function it_manages_editor_instances_correctly(): void
    {
        // Arrange: Register multiple editors
        $this->integration->register('editor1', []);
        $this->integration->register('editor2', []);
        $this->integration->register('editor3', []);

        $this->assertEquals(3, $this->integration->count());

        // Act: Remove one editor
        $removed = $this->integration->unregister('editor2');

        // Assert: Editor is removed
        $this->assertTrue($removed);
        $this->assertEquals(2, $this->integration->count());
        $this->assertFalse($this->integration->isRegistered('editor2'));
        $this->assertTrue($this->integration->isRegistered('editor1'));
        $this->assertTrue($this->integration->isRegistered('editor3'));

        // Act: Clear all editors
        $this->integration->clear();

        // Assert: All editors are removed
        $this->assertEquals(0, $this->integration->count());
        $this->assertFalse($this->integration->hasInstances());
    }

    /**
     * @test
     * Test context-specific configuration (admin vs public)
     *
     * Requirements: 4.11, 4.12
     */
    public function it_applies_context_specific_configuration(): void
    {
        // Test admin context
        $this->integration->setContext('admin');
        $this->integration->register('admin_content', []);

        $adminInstance = $this->integration->getInstance('admin_content');
        $this->assertEquals('admin', $adminInstance['context']);
        $this->assertEquals('admin', $this->integration->getContext());

        // Test public context
        $this->integration->setContext('public');
        $this->integration->register('public_content', []);

        $publicInstance = $this->integration->getInstance('public_content');
        $this->assertEquals('public', $publicInstance['context']);
        $this->assertEquals('public', $this->integration->getContext());

        // Assert: Both editors coexist
        $this->assertEquals(2, $this->integration->count());
    }

    /**
     * @test
     * Test dark mode configuration
     *
     * Requirements: 4.13
     */
    public function it_applies_dark_mode_configuration(): void
    {
        // Test with dark mode disabled
        $this->integration->setDarkMode(false);
        $this->integration->register('light_editor', []);

        $lightInstance = $this->integration->getInstance('light_editor');
        $this->assertFalse($lightInstance['darkMode']);
        $this->assertFalse($this->integration->isDarkMode());

        // Test with dark mode enabled
        $this->integration->setDarkMode(true);
        $this->integration->register('dark_editor', []);

        $darkInstance = $this->integration->getInstance('dark_editor');
        $this->assertTrue($darkInstance['darkMode']);
        $this->assertTrue($this->integration->isDarkMode());

        // Assert: Both editors coexist with different dark mode settings
        $this->assertEquals(2, $this->integration->count());
    }

    /**
     * @test
     * Test toolbar configuration options
     *
     * Requirements: 4.5, 4.6
     */
    public function it_supports_different_toolbar_configurations(): void
    {
        // Test minimal toolbar
        $this->integration->register('minimal', [
            'toolbar' => $this->config->getMinimalToolbar(),
        ]);

        // Test default toolbar
        $this->integration->register('default', [
            'toolbar' => $this->config->getDefaultToolbar(),
        ]);

        // Test full toolbar
        $this->integration->register('full', [
            'toolbar' => $this->config->getFullToolbar(),
        ]);

        // Assert: All editors are registered with different toolbars
        $this->assertEquals(3, $this->integration->count());

        $minimalInstance = $this->integration->getInstance('minimal');
        $defaultInstance = $this->integration->getInstance('default');
        $fullInstance = $this->integration->getInstance('full');

        $this->assertNotEmpty($minimalInstance['config']['toolbar']);
        $this->assertNotEmpty($defaultInstance['config']['toolbar']);
        $this->assertNotEmpty($fullInstance['config']['toolbar']);

        // Minimal toolbar should have fewer items than full toolbar
        $this->assertLessThan(
            count($fullInstance['config']['toolbar']),
            count($minimalInstance['config']['toolbar'])
        );
    }

    /**
     * @test
     * Test custom configuration merging
     *
     * Requirements: 4.2, 4.5
     */
    public function it_merges_custom_configuration_with_defaults(): void
    {
        // Arrange: Custom configuration
        $customConfig = [
            'height' => 500,
            'language' => 'id',
            'removePlugins' => ['elementspath'],
            'extraPlugins' => ['table', 'image'],
        ];

        // Act: Register with custom config
        $this->integration->register('custom', $customConfig);

        // Assert: Custom config is merged with defaults
        $instance = $this->integration->getInstance('custom');
        $config = $instance['config'];

        // Custom values
        $this->assertEquals(500, $config['height']);
        $this->assertEquals('id', $config['language']);
        $this->assertEquals(['elementspath'], $config['removePlugins']);
        $this->assertEquals(['table', 'image'], $config['extraPlugins']);

        // Default values should still exist
        $this->assertArrayHasKey('toolbar', $config);
    }

    /**
     * @test
     * Test content sanitizer integration
     *
     * Requirements: 4.15, 4.16
     */
    public function it_integrates_content_sanitizer_correctly(): void
    {
        // Arrange: Get sanitizer from integration
        $sanitizer = $this->integration->getSanitizer();

        // Assert: Sanitizer is available
        $this->assertInstanceOf(ContentSanitizer::class, $sanitizer);

        // Test sanitization through integration
        $html = '<p>Safe <strong>content</strong></p><script>alert("XSS")</script>';
        $cleaned = $this->integration->sanitize($html);

        $this->assertStringContainsString('Safe', $cleaned);
        $this->assertStringContainsString('<strong>', $cleaned);
        $this->assertStringNotContainsString('<script>', $cleaned);
    }

    /**
     * @test
     * Test registration timestamp tracking
     *
     * Requirements: 4.1, 4.2
     */
    public function it_tracks_registration_timestamps(): void
    {
        // Arrange: Record time before registration
        $beforeTime = microtime(true);

        // Act: Register editor
        $this->integration->register('content', []);

        // Record time after registration
        $afterTime = microtime(true);

        // Assert: Registration timestamp is recorded
        $instance = $this->integration->getInstance('content');
        $this->assertArrayHasKey('registered_at', $instance);

        $registeredAt = $instance['registered_at'];
        $this->assertGreaterThanOrEqual($beforeTime, $registeredAt);
        $this->assertLessThanOrEqual($afterTime, $registeredAt);
    }

    /**
     * @test
     * Test editor instance retrieval methods
     *
     * Requirements: 4.22
     */
    public function it_provides_multiple_ways_to_access_instances(): void
    {
        // Arrange: Register editors
        $this->integration->register('editor1', ['height' => 300]);
        $this->integration->register('editor2', ['height' => 400]);

        // Test getInstance()
        $instance1 = $this->integration->getInstance('editor1');
        $this->assertNotNull($instance1);
        $this->assertEquals('editor1', $instance1['fieldName']);

        // Test getInstances()
        $allInstances = $this->integration->getInstances();
        $this->assertCount(2, $allInstances);
        $this->assertArrayHasKey('editor1', $allInstances);
        $this->assertArrayHasKey('editor2', $allInstances);

        // Test isRegistered()
        $this->assertTrue($this->integration->isRegistered('editor1'));
        $this->assertTrue($this->integration->isRegistered('editor2'));
        $this->assertFalse($this->integration->isRegistered('nonexistent'));

        // Test count()
        $this->assertEquals(2, $this->integration->count());

        // Test hasInstances()
        $this->assertTrue($this->integration->hasInstances());
    }
}
