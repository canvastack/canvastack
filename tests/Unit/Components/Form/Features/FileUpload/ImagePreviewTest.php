<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\ImagePreview;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Unit tests for ImagePreview component.
 *
 * **Validates: Requirements 3.13, 3.14, 3.15, 3.16**
 */
class ImagePreviewTest extends TestCase
{
    protected ImagePreview $preview;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preview = new ImagePreview();
    }

    /**
     * Test preview HTML generation.
     *
     * **Validates: Requirement 3.13**
     */
    public function test_render_generates_preview_html(): void
    {
        $html = $this->preview->render('avatar');

        $this->assertStringContainsString('image-preview-container', $html);
        $this->assertStringContainsString('id="preview-avatar"', $html);
        $this->assertStringContainsString('id="preview-avatar-placeholder"', $html);
    }

    /**
     * Test placeholder display when no image.
     *
     * **Validates: Requirement 3.15**
     */
    public function test_render_shows_placeholder_when_no_image(): void
    {
        $html = $this->preview->render('avatar', null);

        // Image should be hidden
        $this->assertStringContainsString('style="display: none;"', $html);

        // Placeholder should be visible (not hidden)
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar-placeholder".*?style=""/',
            $html
        );
    }

    /**
     * Test current image display when editing.
     *
     * **Validates: Requirement 3.14**
     */
    public function test_render_displays_current_image_when_provided(): void
    {
        $html = $this->preview->render('avatar', 'uploads/avatar.jpg');

        // Image should be visible with correct src
        $this->assertStringContainsString('src="http://localhost/storage/uploads/avatar.jpg"', $html);

        // Image should not be hidden (style should be empty)
        $this->assertStringContainsString('id="preview-avatar"', $html);
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar"[^>]*style=""/s',
            $html
        );

        // Placeholder should be hidden
        $this->assertStringContainsString('id="preview-avatar-placeholder"', $html);
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar-placeholder"[^>]*style="display: none;"/s',
            $html
        );
    }

    /**
     * Test JavaScript preview functionality.
     *
     * **Validates: Requirement 3.16**
     */
    public function test_render_includes_javascript_for_preview(): void
    {
        $html = $this->preview->render('avatar');

        // Check for JavaScript code
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('document.addEventListener(\'DOMContentLoaded\'', $html);

        // Check for file input listener
        $this->assertStringContainsString('input[name="avatar"]', $html);
        $this->assertStringContainsString('addEventListener(\'change\'', $html);

        // Check for FileReader usage
        $this->assertStringContainsString('FileReader()', $html);
        $this->assertStringContainsString('readAsDataURL', $html);

        // Check for preview update logic
        $this->assertStringContainsString('preview.src = e.target.result', $html);
        $this->assertStringContainsString('preview.style.display = \'block\'', $html);
        $this->assertStringContainsString('placeholder.style.display = \'none\'', $html);
    }

    /**
     * Test dark mode styling support.
     *
     * **Validates: Requirement 3.22**
     */
    public function test_render_includes_dark_mode_classes(): void
    {
        $html = $this->preview->render('avatar');

        $this->assertStringContainsString('dark:shadow-gray-700', $html);
        $this->assertStringContainsString('dark:text-gray-500', $html);
    }

    /**
     * Test preview container structure.
     */
    public function test_render_has_correct_container_structure(): void
    {
        $html = $this->preview->render('avatar');

        // Check for container
        $this->assertStringContainsString('<div class="image-preview-container mt-2">', $html);

        // Check for image element
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('alt="Preview"', $html);
        $this->assertStringContainsString('class="rounded-lg shadow-md max-w-xs', $html);

        // Check for placeholder SVG
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('class="w-24 h-24"', $html);
    }

    /**
     * Test field name sanitization in IDs.
     */
    public function test_render_sanitizes_field_name_in_ids(): void
    {
        $html = $this->preview->render('user_avatar');

        $this->assertStringContainsString('id="preview-user_avatar"', $html);
        $this->assertStringContainsString('id="preview-user_avatar-placeholder"', $html);
    }

    /**
     * Test image type validation in JavaScript.
     *
     * **Validates: Requirement 3.16**
     */
    public function test_render_includes_image_type_validation(): void
    {
        $html = $this->preview->render('avatar');

        // Check for image type validation
        $this->assertStringContainsString('file.type.startsWith(\'image/\')', $html);
    }

    /**
     * Test preview with special characters in field name.
     */
    public function test_render_handles_special_characters_in_field_name(): void
    {
        $html = $this->preview->render('user[avatar]');

        $this->assertStringContainsString('id="preview-user[avatar]"', $html);
        $this->assertStringContainsString('input[name="user[avatar]"]', $html);
    }

    /**
     * Test preview with empty string as current image.
     */
    public function test_render_treats_empty_string_as_no_image(): void
    {
        $html = $this->preview->render('avatar', '');

        // Should behave like null - show placeholder
        $this->assertStringContainsString('src=""', $html);

        // Image should be hidden
        $this->assertStringContainsString('id="preview-avatar"', $html);
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar"[^>]*style="display: none;"/s',
            $html
        );

        // Placeholder should be visible
        $this->assertStringContainsString('id="preview-avatar-placeholder"', $html);
        $this->assertMatchesRegularExpression(
            '/id="preview-avatar-placeholder"[^>]*style=""/s',
            $html
        );
    }

    /**
     * Test asset helper usage for image URL.
     */
    public function test_render_uses_asset_helper_for_image_url(): void
    {
        $html = $this->preview->render('avatar', 'uploads/test.jpg');

        // Should use asset() helper which prepends base URL
        $this->assertStringContainsString('storage/uploads/test.jpg', $html);
    }

    /**
     * Test SVG placeholder icon structure.
     */
    public function test_render_includes_proper_svg_placeholder(): void
    {
        $html = $this->preview->render('avatar');

        // Check SVG attributes
        $this->assertStringContainsString('fill="none"', $html);
        $this->assertStringContainsString('stroke="currentColor"', $html);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $html);

        // Check for image icon path
        $this->assertStringContainsString('<path', $html);
        $this->assertStringContainsString('stroke-linecap="round"', $html);
        $this->assertStringContainsString('stroke-linejoin="round"', $html);
    }

    /**
     * Test multiple preview instances don't conflict.
     */
    public function test_multiple_previews_have_unique_ids(): void
    {
        $html1 = $this->preview->render('avatar');
        $html2 = $this->preview->render('cover_image');

        // Each should have unique IDs
        $this->assertStringContainsString('id="preview-avatar"', $html1);
        $this->assertStringContainsString('id="preview-cover_image"', $html2);

        // IDs should not overlap
        $this->assertStringNotContainsString('preview-cover_image', $html1);
        $this->assertStringNotContainsString('preview-avatar', $html2);
    }

    /**
     * Test Tailwind CSS classes are applied.
     */
    public function test_render_applies_tailwind_classes(): void
    {
        $html = $this->preview->render('avatar');

        // Container classes
        $this->assertStringContainsString('mt-2', $html);

        // Image classes
        $this->assertStringContainsString('rounded-lg', $html);
        $this->assertStringContainsString('shadow-md', $html);
        $this->assertStringContainsString('max-w-xs', $html);

        // Placeholder classes
        $this->assertStringContainsString('text-gray-400', $html);
        $this->assertStringContainsString('w-24', $html);
        $this->assertStringContainsString('h-24', $html);
    }
}
