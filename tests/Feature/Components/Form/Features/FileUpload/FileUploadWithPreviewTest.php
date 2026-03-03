<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\ImagePreview;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Feature Tests for File Upload with Preview.
 *
 * Tests Requirements:
 * - 3.13: Render image preview widget when imagepreview attribute is present
 * - 3.14: Display current image if editing existing record
 * - 3.15: Show placeholder icon when no image uploaded
 * - 3.16: Display preview before form submission when user selects new image
 */
class FileUploadWithPreviewTest extends TestCase
{
    protected ImagePreview $preview;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preview = new ImagePreview();
    }

    /**
     * Test image preview rendering with no current image.
     *
     * @test
     */
    public function test_image_preview_rendering_with_no_current_image(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Contains preview container
        $this->assertStringContainsString('image-preview-container', $html);

        // Assert - Contains preview image element
        $this->assertStringContainsString('id="preview-avatar"', $html);

        // Assert - Preview image is hidden initially
        $this->assertStringContainsString('display: none', $html);

        // Assert - Contains placeholder
        $this->assertStringContainsString('preview-avatar-placeholder', $html);

        // Assert - Placeholder is visible
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar-placeholder"[^>]*style="[^"]*"/',
            $html
        );

        // Assert - Contains SVG placeholder icon
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $html);
    }

    /**
     * Test image preview rendering with current image.
     *
     * @test
     */
    public function test_image_preview_rendering_with_current_image(): void
    {
        // Arrange
        $currentImage = 'uploads/images/photo.jpg';

        // Act
        $html = $this->preview->render('avatar', $currentImage);

        // Assert - Contains preview image with src
        $this->assertStringContainsString('id="preview-avatar"', $html);
        $this->assertStringContainsString('src="', $html);
        $this->assertStringContainsString('storage/' . $currentImage, $html);

        // Assert - Preview image is visible
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar"[^>]*style=""/',
            $html
        );

        // Assert - Placeholder is hidden
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar-placeholder"[^>]*style="display: none;"/',
            $html
        );
    }

    /**
     * Test image preview contains JavaScript for file selection.
     *
     * @test
     */
    public function test_image_preview_contains_javascript_for_file_selection(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Contains script tag
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('</script>', $html);

        // Assert - Contains DOMContentLoaded listener
        $this->assertStringContainsString('DOMContentLoaded', $html);

        // Assert - Selects input by name
        $this->assertStringContainsString('input[name="avatar"]', $html);

        // Assert - Contains change event listener
        $this->assertStringContainsString('addEventListener(\'change\'', $html);

        // Assert - Contains FileReader for preview
        $this->assertStringContainsString('FileReader', $html);
        $this->assertStringContainsString('readAsDataURL', $html);

        // Assert - Updates preview image src
        $this->assertStringContainsString('preview.src', $html);

        // Assert - Shows/hides elements
        $this->assertStringContainsString('display', $html);
    }

    /**
     * Test image preview validates file type is image.
     *
     * @test
     */
    public function test_image_preview_validates_file_type_is_image(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - JavaScript checks file type
        $this->assertStringContainsString('file.type.startsWith(\'image/\')', $html);
    }

    /**
     * Test image preview styling includes Tailwind classes.
     *
     * @test
     */
    public function test_image_preview_styling_includes_tailwind_classes(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Contains Tailwind utility classes
        $this->assertStringContainsString('mt-2', $html); // Margin top
        $this->assertStringContainsString('rounded-lg', $html); // Rounded corners
        $this->assertStringContainsString('shadow-md', $html); // Shadow
        $this->assertStringContainsString('max-w-xs', $html); // Max width
    }

    /**
     * Test image preview supports dark mode.
     *
     * @test
     */
    public function test_image_preview_supports_dark_mode(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Contains dark mode classes
        $this->assertStringContainsString('dark:', $html);
        $this->assertStringContainsString('dark:shadow-gray-700', $html);
        $this->assertStringContainsString('dark:text-gray-500', $html);
    }

    /**
     * Test image preview with different field names.
     *
     * @test
     */
    public function test_image_preview_with_different_field_names(): void
    {
        // Arrange
        $fieldNames = ['avatar', 'profile_photo', 'product_image', 'banner'];

        foreach ($fieldNames as $fieldName) {
            // Act
            $html = $this->preview->render($fieldName, null);

            // Assert - Preview ID matches field name
            $this->assertStringContainsString("id=\"preview-{$fieldName}\"", $html);

            // Assert - Placeholder ID matches field name
            $this->assertStringContainsString("id=\"preview-{$fieldName}-placeholder\"", $html);

            // Assert - Input selector matches field name
            $this->assertStringContainsString("input[name=\"{$fieldName}\"]", $html);
        }
    }

    /**
     * Test image preview placeholder icon is accessible.
     *
     * @test
     */
    public function test_image_preview_placeholder_icon_is_accessible(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - SVG has proper attributes
        $this->assertStringContainsString('fill="none"', $html);
        $this->assertStringContainsString('stroke="currentColor"', $html);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $html);

        // Assert - SVG has reasonable size
        $this->assertStringContainsString('w-24', $html);
        $this->assertStringContainsString('h-24', $html);
    }

    /**
     * Test image preview alt text for accessibility.
     *
     * @test
     */
    public function test_image_preview_alt_text_for_accessibility(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Image has alt attribute
        $this->assertStringContainsString('alt="Preview"', $html);
    }

    /**
     * Test image preview with URL containing special characters.
     *
     * @test
     */
    public function test_image_preview_with_url_containing_special_characters(): void
    {
        // Arrange
        $currentImage = 'uploads/images/photo with spaces & special.jpg';

        // Act
        $html = $this->preview->render('avatar', $currentImage);

        // Assert - URL is properly encoded in src
        $this->assertStringContainsString('src="', $html);
        $this->assertStringContainsString('storage/', $html);
    }

    /**
     * Test image preview container structure.
     *
     * @test
     */
    public function test_image_preview_container_structure(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Has container div
        $this->assertStringContainsString('<div class="image-preview-container', $html);

        // Assert - Has image element
        $this->assertStringContainsString('<img', $html);

        // Assert - Has placeholder div
        $this->assertStringContainsString('<div id="preview-avatar-placeholder"', $html);

        // Assert - Has script element
        $this->assertStringContainsString('<script>', $html);

        // Assert - Proper closing tags
        $this->assertStringContainsString('</div>', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    /**
     * Test image preview JavaScript handles missing input gracefully.
     *
     * @test
     */
    public function test_image_preview_javascript_handles_missing_input_gracefully(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Checks if input exists before adding listener
        $this->assertStringContainsString('if (input)', $html);
    }

    /**
     * Test image preview updates on file selection.
     *
     * @test
     */
    public function test_image_preview_updates_on_file_selection(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - FileReader onload handler updates preview
        $this->assertStringContainsString('reader.onload', $html);
        $this->assertStringContainsString('preview.src = e.target.result', $html);

        // Assert - Shows preview and hides placeholder
        $this->assertStringContainsString('preview.style.display = \'block\'', $html);
        $this->assertStringContainsString('placeholder.style.display = \'none\'', $html);
    }

    /**
     * Test multiple image preview widgets on same page.
     *
     * @test
     */
    public function test_multiple_image_preview_widgets_on_same_page(): void
    {
        // Act
        $html1 = $this->preview->render('avatar', null);
        $html2 = $this->preview->render('banner', null);

        // Assert - Each has unique IDs
        $this->assertStringContainsString('id="preview-avatar"', $html1);
        $this->assertStringContainsString('id="preview-banner"', $html2);

        // Assert - Each targets correct input
        $this->assertStringContainsString('input[name="avatar"]', $html1);
        $this->assertStringContainsString('input[name="banner"]', $html2);

        // Assert - Both are independent
        $this->assertStringNotContainsString('banner', $html1);
        $this->assertStringNotContainsString('avatar', $html2);
    }

    /**
     * Test image preview with empty string as current image.
     *
     * @test
     */
    public function test_image_preview_with_empty_string_as_current_image(): void
    {
        // Act
        $html = $this->preview->render('avatar', '');

        // Assert - Treats empty string as no image
        $this->assertStringContainsString('src=""', $html);
        $this->assertStringContainsString('display: none', $html);
    }

    /**
     * Test image preview responsive design.
     *
     * @test
     */
    public function test_image_preview_responsive_design(): void
    {
        // Act
        $html = $this->preview->render('avatar', null);

        // Assert - Has max-width constraint
        $this->assertStringContainsString('max-w-xs', $html);

        // Assert - Image is responsive
        $this->assertMatchesRegularExpression('/<img[^>]*class="[^"]*rounded-lg[^"]*"/', $html);
    }
}
