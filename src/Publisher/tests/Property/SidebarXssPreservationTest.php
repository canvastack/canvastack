<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Preservation Property Tests for Sidebar XSS Protection
 * 
 * **IMPORTANT**: These tests follow observation-first methodology
 * - Observe behavior on UNFIXED code for non-buggy inputs
 * - Write property-based tests capturing observed XSS protection behavior patterns
 * - Tests MUST PASS on unfixed code (confirms baseline XSS protection to preserve)
 * 
 * These tests verify that XSS protection for user-controllable data (menu labels,
 * URLs, ID attributes, submenu items) remains unchanged after the icon rendering fix.
 * 
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 * 
 * @group property
 * @group bugfix
 * @group sidebar-icons
 * @group preservation
 */
class SidebarXssPreservationTest extends TestCase
{
    use TestTrait;
    
    /**
     * Property 2: Preservation - Menu Label XSS Protection
     * 
     * **Validates: Requirements 3.1**
     * 
     * For any menu data that is NOT icon HTML (specifically menu labels),
     * the function SHALL continue to escape them using htmlspecialchars()
     * for XSS protection. Labels are also transformed with canvastack_underscore_to_camelcase().
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES
     * - Menu labels with special characters are properly escaped
     * - XSS attempts like <script>alert('xss')</script> are neutralized
     * - This behavior must be preserved after the icon fix
     * 
     * @test
     */
    #[ErisRepeat(repeat: 50)]
    public function test_property_2_preservation_menu_label_xss_protection()
    {
        $this->forAll(
            // Generate menu labels with XSS attempts and special characters
            Generators::elements([
                '<script>alert("xss")</script>',
                '<img src=x onerror=alert(1)>',
                '"><script>alert(String.fromCharCode(88,83,83))</script>',
                '<iframe src="javascript:alert(\'xss\')">',
                '<body onload=alert(\'xss\')>',
                'Normal & Safe <Label>',
                '<b>Bold</b> and <i>Italic</i>',
            ])
        )
        ->then(function ($maliciousLabel) {
            // Arrange: Create menu data with potentially malicious label
            $url = '/safe-url';
            $icon = ['icon' => '<i class="fa fa-home"></i>'];
            
            // Act: Render sidebar menu
            $output = canvastack_sidebar_menu($maliciousLabel, $url, $icon);
            
            // Assert: Label is transformed with canvastack_underscore_to_camelcase() and then escaped
            // The escaped and transformed version should be present
            $transformedLabel = canvastack_underscore_to_camelcase($maliciousLabel);
            $escapedLabel = htmlspecialchars($transformedLabel, ENT_QUOTES, 'UTF-8');
            $this->assertStringContainsString(
                $escapedLabel,
                $output,
                "Expected menu label to be escaped for XSS protection. " .
                "Original Label: {$maliciousLabel}, Transformed: {$transformedLabel}"
            );
            
            // Assert: Raw malicious content should NOT be executable
            // Check that script tags are escaped (not present as raw HTML)
            if (strpos($maliciousLabel, '<script>') !== false) {
                $this->assertStringNotContainsString(
                    '<script>',
                    strip_tags($output, '<script>'),
                    "Found unescaped <script> tag - XSS vulnerability! " .
                    "Label: {$maliciousLabel}"
                );
            }
            
            // Assert: Check for other dangerous tags
            if (strpos($maliciousLabel, '<iframe') !== false) {
                $this->assertStringNotContainsString(
                    '<iframe',
                    strip_tags($output, '<iframe>'),
                    "Found unescaped <iframe> tag - XSS vulnerability! " .
                    "Label: {$maliciousLabel}"
                );
            }
        });
    }
    
    /**
     * Property 2: Preservation - Menu URL Escaping
     * 
     * **Validates: Requirements 3.2**
     * 
     * For any menu data that is NOT icon HTML (specifically menu URLs),
     * the function SHALL continue to escape them using htmlspecialchars()
     * for XSS protection.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES
     * - URLs are escaped with htmlspecialchars()
     * - Special characters like & are converted to &amp;
     * - This behavior must remain unchanged after the icon fix
     * 
     * @test
     */
    #[ErisRepeat(repeat: 50)]
    public function test_property_2_preservation_menu_url_escaping()
    {
        $this->forAll(
            // Generate various URLs including special characters
            Generators::elements([
                '/normal-url',
                '/url?a=1&b=2&c=3',
                '/url-with-dashes',
                '/url_with_underscores',
                '/url/with/slashes',
                '/url?param=value&other=test',
            ])
        )
        ->then(function ($url) {
            // Arrange: Create menu data with URL
            $label = 'Safe Label';
            $icon = ['icon' => '<i class="fa fa-home"></i>'];
            
            // Act: Render sidebar menu
            $output = canvastack_sidebar_menu($label, $url, $icon);
            
            // Assert: URL should be escaped with htmlspecialchars in href attribute
            $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $this->assertStringContainsString(
                'href="' . $escapedUrl . '"',
                $output,
                "Expected menu URL to be escaped with htmlspecialchars. " .
                "Original URL: {$url}, Escaped: {$escapedUrl}"
            );
        });
    }
    
    /**
     * Property 2: Preservation - Menu ID Attribute Sanitization
     * 
     * **Validates: Requirements 3.3**
     * 
     * For any menu data that is NOT icon HTML (specifically menu ID attributes),
     * the function SHALL continue to sanitize them using canvastack_clean_strings()
     * for valid HTML attributes. Observing actual behavior to document what characters
     * are allowed/removed.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES
     * 
     * @test
     */
    #[ErisRepeat(repeat: 50)]
    public function test_property_2_preservation_menu_id_sanitization()
    {
        $this->forAll(
            // Generate labels that will be used for ID attributes
            Generators::elements([
                'Normal Menu',
                'Menu with spaces',
                'Menu_with_underscores',
                'Menu-with-dashes',
                'Menu123Numbers',
                'UPPERCASE',
                'lowercase',
                'MixedCase',
            ])
        )
        ->then(function ($label) {
            // Arrange: Create menu data
            $url = '/test-url';
            $icon = ['icon' => '<i class="fa fa-home"></i>'];
            
            // Act: Render sidebar menu
            $output = canvastack_sidebar_menu($label, $url, $icon);
            
            // Assert: ID attribute should be present
            preg_match('/id="([^"]+)"/', $output, $matches);
            $this->assertNotEmpty($matches, "Expected to find id attribute in output");
            
            $actualId = $matches[1];
            
            // Assert: ID should not contain dangerous characters that could break HTML
            $this->assertDoesNotMatchRegularExpression(
                '/[<>"\'\\\\]/',
                $actualId,
                "ID attribute contains dangerous characters that could break HTML. " .
                "Label: {$label}, ID: {$actualId}"
            );
            
            // Assert: ID should be a non-empty string
            $this->assertNotEmpty(
                $actualId,
                "ID attribute should not be empty. Label: {$label}"
            );
        });
    }
    
    /**
     * Property 2: Preservation - Nested Submenu Escaping
     * 
     * **Validates: Requirements 3.5**
     * 
     * For any menu data that is NOT icon HTML (specifically nested submenu titles and URLs),
     * the function SHALL continue to escape them properly. Submenu titles are transformed
     * with canvastack_underscore_to_camelcase() and then escaped.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES
     * 
     * @test
     */
    #[ErisRepeat(repeat: 50)]
    public function test_property_2_preservation_nested_submenu_escaping()
    {
        $this->forAll(
            // Generate submenu data with special characters
            Generators::elements([
                ['title' => '<script>alert("xss")</script>', 'url' => '/safe-url'],
                ['title' => '<img src=x onerror=alert(1)>', 'url' => '/url'],
                ['title' => 'Normal Title', 'url' => '/normal-url'],
                ['title' => 'Title & Special', 'url' => '/url'],
                ['title' => '<b>Bold</b> Title', 'url' => '/url'],
            ])
        )
        ->then(function ($submenuData) {
            // Arrange: Create menu with submenu containing potentially malicious data
            $label = 'Parent Menu';
            $links = [
                $submenuData['title'] => $submenuData['url'],
                'Safe Child' => '/safe-child-url',
            ];
            $icon = ['icon' => '<i class="fa fa-home"></i>'];
            
            // Act: Render sidebar menu with submenu
            $output = canvastack_sidebar_menu($label, $links, $icon);
            
            // Assert: Submenu title is transformed and then escaped
            $transformedTitle = canvastack_underscore_to_camelcase($submenuData['title']);
            $escapedTitle = htmlspecialchars($transformedTitle, ENT_QUOTES, 'UTF-8');
            $this->assertStringContainsString(
                $escapedTitle,
                $output,
                "Expected submenu title to be escaped for XSS protection. " .
                "Original Title: {$submenuData['title']}, Transformed: {$transformedTitle}"
            );
            
            // Assert: Submenu URL should be present in href
            $this->assertStringContainsString(
                'href="' . $submenuData['url'] . '"',
                $output,
                "Expected submenu URL to be present in href attribute. " .
                "URL: {$submenuData['url']}"
            );
            
            // Assert: Dangerous content should not be executable
            if (strpos($submenuData['title'], '<script>') !== false) {
                $this->assertStringNotContainsString(
                    '<script>',
                    strip_tags($output, '<script>'),
                    "Found unescaped <script> tag in submenu - XSS vulnerability! " .
                    "Title: {$submenuData['title']}"
                );
            }
        });
    }
    
    /**
     * Property 2: Preservation - Default Fallback Icon Rendering
     * 
     * **Validates: Requirements 3.4**
     * 
     * When icon data is null (but icon parameter is an array with 'icon' key set to null),
     * the function SHALL continue to render the default fallback icon
     * <i class="fa fa-tags"></i>.
     * 
     * Note: Empty string '' does NOT trigger fallback - only null does.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES
     * 
     * @test
     */
    #[ErisRepeat(repeat: 20)]
    public function test_property_2_preservation_default_fallback_icon()
    {
        $this->forAll(
            // Generate null icon scenario (only null triggers fallback, not empty string)
            Generators::constant(['icon' => null])
        )
        ->then(function ($iconData) {
            // Arrange: Create menu data with null icon
            $label = 'Test Menu';
            $url = '/test-url';
            
            // Act: Render sidebar menu
            $output = canvastack_sidebar_menu($label, $url, $iconData);
            
            // Assert: Default fallback icon should be rendered
            $this->assertStringContainsString(
                '<i class="fa fa-tags"></i>',
                $output,
                "Expected default fallback icon to be rendered when icon data is null. " .
                "Icon data: " . json_encode($iconData)
            );
        });
    }
}
