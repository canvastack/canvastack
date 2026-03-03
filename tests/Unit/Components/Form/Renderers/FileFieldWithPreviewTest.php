<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Renderers;

use Canvastack\Canvastack\Components\Form\Fields\FileField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Integration tests for FileField with ImagePreview.
 *
 * **Validates: Requirements 3.13, 3.22**
 */
class FileFieldWithPreviewTest extends TestCase
{
    protected AdminRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new AdminRenderer();
    }

    /**
     * Test file field renders without preview by default.
     */
    public function test_file_field_renders_without_preview_by_default(): void
    {
        $field = new FileField('avatar', 'Avatar');

        $html = $this->renderer->render($field);

        $this->assertStringContainsString('<input type="file"', $html);
        $this->assertStringNotContainsString('image-preview-container', $html);
    }

    /**
     * Test file field renders with preview when enabled.
     *
     * **Validates: Requirement 3.13**
     */
    public function test_file_field_renders_with_preview_when_enabled(): void
    {
        $field = new FileField('avatar', 'Avatar');
        $field->preview(true);

        $html = $this->renderer->render($field);

        $this->assertStringContainsString('<input type="file"', $html);
        $this->assertStringContainsString('image-preview-container', $html);
        $this->assertStringContainsString('id="preview-avatar"', $html);
    }

    /**
     * Test file field with preview shows current image.
     */
    public function test_file_field_with_preview_shows_current_image(): void
    {
        $field = new FileField('avatar', 'Avatar');
        $field->preview(true);
        $field->setValue('uploads/current-avatar.jpg');

        $html = $this->renderer->render($field);

        $this->assertStringContainsString('image-preview-container', $html);
        $this->assertStringContainsString('storage/uploads/current-avatar.jpg', $html);
    }

    /**
     * Test file field with preview includes dark mode classes.
     *
     * **Validates: Requirement 3.22**
     */
    public function test_file_field_with_preview_includes_dark_mode(): void
    {
        $field = new FileField('avatar', 'Avatar');
        $field->preview(true);

        $html = $this->renderer->render($field);

        // File input dark mode
        $this->assertStringContainsString('dark:bg-gray-700', $html);
        $this->assertStringContainsString('dark:border-gray-600', $html);

        // Preview dark mode
        $this->assertStringContainsString('dark:shadow-gray-700', $html);
        $this->assertStringContainsString('dark:text-gray-500', $html);
    }

    /**
     * Test file field with preview includes JavaScript.
     */
    public function test_file_field_with_preview_includes_javascript(): void
    {
        $field = new FileField('avatar', 'Avatar');
        $field->preview(true);

        $html = $this->renderer->render($field);

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('FileReader()', $html);
        $this->assertStringContainsString('readAsDataURL', $html);
    }

    /**
     * Test multiple file fields with preview have unique IDs.
     */
    public function test_multiple_file_fields_with_preview_have_unique_ids(): void
    {
        $field1 = new FileField('avatar', 'Avatar');
        $field1->preview(true);

        $field2 = new FileField('cover_image', 'Cover Image');
        $field2->preview(true);

        $html1 = $this->renderer->render($field1);
        $html2 = $this->renderer->render($field2);

        $this->assertStringContainsString('id="preview-avatar"', $html1);
        $this->assertStringContainsString('id="preview-cover_image"', $html2);

        $this->assertStringNotContainsString('preview-cover_image', $html1);
        $this->assertStringNotContainsString('preview-avatar', $html2);
    }

    /**
     * Test file field preview works with fluent interface.
     */
    public function test_file_field_preview_works_with_fluent_interface(): void
    {
        $field = (new FileField('avatar', 'Avatar'))
            ->preview()
            ->accept('image/*')
            ->maxSize(2048);

        $html = $this->renderer->render($field);

        $this->assertStringContainsString('image-preview-container', $html);
        $this->assertStringContainsString('accept="image/*"', $html);
    }
}
