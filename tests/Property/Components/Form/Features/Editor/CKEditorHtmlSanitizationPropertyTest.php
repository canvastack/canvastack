<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form\Features\Editor;

use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\ContentSanitizer;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use PHPUnit\Framework\TestCase;

/**
 * Property Test: CKEditor HTML Sanitization.
 *
 * **Property 16: CKEditor HTML Sanitization**
 *
 * For any HTML content containing malicious scripts, the sanitized version
 * should have all script tags and dangerous attributes removed while
 * preserving safe HTML.
 *
 * **Validates: Requirements 4.15, 14.8**
 */
class CKEditorHtmlSanitizationPropertyTest extends TestCase
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
     * Property: Script tags are always removed from HTML content.
     *
     * @test
     */
    public function property_script_tags_are_removed(): void
    {
        $testCases = [
            '<script>alert("XSS")</script>',
            '<p>Safe content</p><script>alert("XSS")</script>',
            '<script src="malicious.js"></script><p>Content</p>',
            '<p>Before</p><script>evil()</script><p>After</p>',
            '<SCRIPT>alert("XSS")</SCRIPT>', // Case insensitive
            '<script type="text/javascript">alert("XSS")</script>',
        ];

        foreach ($testCases as $html) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: No script tags should remain
            $this->assertStringNotContainsStringIgnoringCase(
                '<script',
                $sanitized,
                "Script tag was not removed from: {$html}"
            );
            $this->assertStringNotContainsStringIgnoringCase(
                '</script>',
                $sanitized,
                "Script closing tag was not removed from: {$html}"
            );
        }
    }

    /**
     * Property: Event handler attributes are removed from HTML content.
     *
     * @test
     */
    public function property_event_handlers_are_removed(): void
    {
        $testCases = [
            '<img src="image.jpg" onclick="alert(\'XSS\')">',
            '<a href="#" onmouseover="alert(\'XSS\')">Link</a>',
            '<div onload="malicious()">Content</div>',
            '<p onerror="alert(\'XSS\')">Text</p>',
            '<button onfocus="evil()">Click</button>',
            '<input type="text" onchange="hack()">',
        ];

        foreach ($testCases as $html) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: No event handler attributes should remain
            $this->assertDoesNotMatchRegularExpression(
                '/\bon\w+\s*=/i',
                $sanitized,
                "Event handler attribute was not removed from: {$html}"
            );
        }
    }

    /**
     * Property: JavaScript protocol in links is removed.
     *
     * @test
     */
    public function property_javascript_protocol_is_removed(): void
    {
        $testCases = [
            '<a href="javascript:alert(\'XSS\')">Link</a>',
            '<a href="JAVASCRIPT:void(0)">Link</a>',
            '<a href="javascript:malicious()">Click</a>',
            '<img src="javascript:alert(\'XSS\')">',
        ];

        foreach ($testCases as $html) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: No javascript: protocol should remain
            $this->assertStringNotContainsStringIgnoringCase(
                'javascript:',
                $sanitized,
                "JavaScript protocol was not removed from: {$html}"
            );
        }
    }

    /**
     * Property: Safe HTML tags are preserved.
     *
     * @test
     */
    public function property_safe_html_is_preserved(): void
    {
        $testCases = [
            '<p>Paragraph text</p>' => '<p>',
            '<strong>Bold text</strong>' => '<strong>',
            '<em>Italic text</em>' => '<em>',
            '<h1>Heading</h1>' => '<h1>',
            '<ul><li>Item</li></ul>' => '<ul>',
            '<a href="https://example.com">Link</a>' => '<a',
            '<img src="image.jpg" alt="Image">' => '<img',
            '<table><tr><td>Cell</td></tr></table>' => '<table>',
        ];

        foreach ($testCases as $html => $expectedTag) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: Safe HTML tags should be preserved
            $this->assertStringContainsString(
                $expectedTag,
                $sanitized,
                "Safe HTML tag was removed from: {$html}"
            );
        }
    }

    /**
     * Property: Safe attributes are preserved.
     *
     * @test
     */
    public function property_safe_attributes_are_preserved(): void
    {
        $testCases = [
            '<a href="https://example.com" title="Example">Link</a>' => ['href', 'title'],
            '<img src="image.jpg" alt="Image" width="100" height="100">' => ['src', 'alt', 'width', 'height'],
            '<div class="container">Content</div>' => ['class'],
            '<table><tr><td colspan="2">Cell</td></tr></table>' => ['colspan'],
        ];

        foreach ($testCases as $html => $expectedAttributes) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: Safe attributes should be preserved
            foreach ($expectedAttributes as $attr) {
                $this->assertMatchesRegularExpression(
                    "/{$attr}=/i",
                    $sanitized,
                    "Safe attribute '{$attr}' was removed from: {$html}"
                );
            }
        }
    }

    /**
     * Property: Dangerous attributes are removed.
     *
     * @test
     */
    public function property_dangerous_attributes_are_removed(): void
    {
        $testCases = [
            '<img src="image.jpg" onerror="alert(\'XSS\')">',
            '<div style="background: url(javascript:alert(\'XSS\'))">Content</div>',
            '<a href="#" onclick="malicious()">Link</a>',
            '<p onload="evil()">Text</p>',
        ];

        $dangerousAttributes = ['onerror', 'onclick', 'onload', 'onmouseover', 'onfocus'];

        foreach ($testCases as $html) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: Dangerous attributes should be removed
            foreach ($dangerousAttributes as $attr) {
                $this->assertDoesNotMatchRegularExpression(
                    "/{$attr}=/i",
                    $sanitized,
                    "Dangerous attribute '{$attr}' was not removed from: {$html}"
                );
            }
        }
    }

    /**
     * Property: Nested malicious content is removed.
     *
     * @test
     */
    public function property_nested_malicious_content_is_removed(): void
    {
        $testCases = [
            '<div><script>alert("XSS")</script><p>Safe</p></div>',
            '<p>Text <script>evil()</script> more text</p>',
            '<ul><li><script>hack()</script>Item</li></ul>',
            '<table><tr><td><script>malicious()</script>Cell</td></tr></table>',
        ];

        foreach ($testCases as $html) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: No script tags should remain, even nested
            $this->assertStringNotContainsStringIgnoringCase(
                '<script',
                $sanitized,
                "Nested script tag was not removed from: {$html}"
            );

            // Property: Safe content should be preserved
            $this->assertNotEmpty(
                trim(strip_tags($sanitized)),
                "All content was removed from: {$html}"
            );
        }
    }

    /**
     * Property: Empty input returns empty output.
     *
     * @test
     */
    public function property_empty_input_returns_empty_output(): void
    {
        $testCases = ['', '   ', "\n", "\t"];

        foreach ($testCases as $html) {
            $sanitized = $this->sanitizer->clean($html);

            // Property: Empty input should return empty output
            $this->assertEmpty(
                trim($sanitized),
                'Empty input did not return empty output'
            );
        }
    }

    /**
     * Property: CKEditorIntegration sanitize method delegates to ContentSanitizer.
     *
     * @test
     */
    public function property_integration_sanitize_delegates_to_sanitizer(): void
    {
        $testCases = [
            '<p>Safe content</p><script>alert("XSS")</script>',
            '<img src="image.jpg" onerror="alert(\'XSS\')">',
            '<a href="javascript:alert(\'XSS\')">Link</a>',
        ];

        foreach ($testCases as $html) {
            $sanitizedDirect = $this->sanitizer->clean($html);
            $sanitizedViaIntegration = $this->integration->sanitize($html);

            // Property: Both methods should produce identical results
            $this->assertEquals(
                $sanitizedDirect,
                $sanitizedViaIntegration,
                "Integration sanitize method did not delegate correctly for: {$html}"
            );
        }
    }

    /**
     * Property: Multiple sanitization passes produce idempotent results.
     *
     * @test
     */
    public function property_sanitization_is_idempotent(): void
    {
        $testCases = [
            '<p>Safe content</p><script>alert("XSS")</script>',
            '<img src="image.jpg" onerror="alert(\'XSS\')">',
            '<div><p>Text</p><script>evil()</script></div>',
        ];

        foreach ($testCases as $html) {
            $firstPass = $this->sanitizer->clean($html);
            $secondPass = $this->sanitizer->clean($firstPass);
            $thirdPass = $this->sanitizer->clean($secondPass);

            // Property: Multiple passes should produce identical results
            $this->assertEquals(
                $firstPass,
                $secondPass,
                "Second sanitization pass changed the output for: {$html}"
            );
            $this->assertEquals(
                $secondPass,
                $thirdPass,
                "Third sanitization pass changed the output for: {$html}"
            );
        }
    }

    /**
     * Property: Complex real-world content is properly sanitized.
     *
     * @test
     */
    public function property_complex_content_is_sanitized(): void
    {
        $complexHtml = <<<HTML
        <div class="article">
            <h1>Article Title</h1>
            <p>This is a <strong>safe</strong> paragraph with <em>formatting</em>.</p>
            <script>alert("This should be removed")</script>
            <img src="image.jpg" alt="Safe image" onerror="alert('XSS')">
            <a href="https://example.com" onclick="malicious()">Safe link</a>
            <ul>
                <li>Item 1</li>
                <li>Item 2 <script>evil()</script></li>
            </ul>
            <table>
                <tr>
                    <td>Cell 1</td>
                    <td onload="hack()">Cell 2</td>
                </tr>
            </table>
        </div>
        HTML;

        $sanitized = $this->sanitizer->clean($complexHtml);

        // Property: No malicious content should remain
        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);
        $this->assertDoesNotMatchRegularExpression('/\bon\w+\s*=/i', $sanitized);

        // Property: Safe content should be preserved
        $this->assertStringContainsString('<h1>Article Title</h1>', $sanitized);
        $this->assertStringContainsString('<strong>safe</strong>', $sanitized);
        $this->assertStringContainsString('<em>formatting</em>', $sanitized);
        $this->assertStringContainsString('<img', $sanitized);
        $this->assertStringContainsString('src="image.jpg"', $sanitized);
        $this->assertStringContainsString('<a', $sanitized);
        $this->assertStringContainsString('href="https://example.com"', $sanitized);
        $this->assertStringContainsString('<ul>', $sanitized);
        $this->assertStringContainsString('<table>', $sanitized);
    }
}
