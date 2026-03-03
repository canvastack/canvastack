<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileProcessor;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Integration Tests for File Upload Workflow.
 *
 * Tests Requirements:
 * - 3.1: Process uploaded file from request
 * - 3.3: Automatically create thumbnails for images
 * - 3.9: Return file information array
 * - 3.17: Integrate with Laravel's file storage system
 */
class FileUploadIntegrationTest extends TestCase
{
    protected FileProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup fake storage
        Storage::fake('public');

        // Create processor with all dependencies
        $validator = new FileValidator();
        $thumbnailGenerator = new ThumbnailGenerator();
        $this->processor = new FileProcessor($validator, $thumbnailGenerator);
    }

    /**
     * Test complete file upload workflow for image file.
     *
     * @test
     */
    public function test_complete_image_upload_workflow(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $uploadPath = 'uploads/images';

        // Act
        $result = $this->processor->process($file, $uploadPath);

        // Assert - File information returned
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original_filename', $result);
        $this->assertArrayHasKey('stored_filename', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('thumbnail_path', $result);
        $this->assertArrayHasKey('mime_type', $result);
        $this->assertArrayHasKey('file_size', $result);

        // Assert - Original filename preserved
        $this->assertEquals('photo.jpg', $result['original_filename']);

        // Assert - Stored filename is unique
        $this->assertStringContainsString('.jpg', $result['stored_filename']);
        $this->assertNotEquals('photo.jpg', $result['stored_filename']);

        // Assert - File stored in correct path
        $this->assertStringStartsWith($uploadPath, $result['file_path']);
        Storage::disk('public')->assertExists($result['file_path']);

        // Assert - Thumbnail created
        $this->assertNotNull($result['thumbnail_path']);
        $this->assertStringContainsString('thumb', $result['thumbnail_path']);
        Storage::disk('public')->assertExists($result['thumbnail_path']);

        // Assert - MIME type correct
        $this->assertEquals('image/jpeg', $result['mime_type']);

        // Assert - File size recorded
        $this->assertGreaterThan(0, $result['file_size']);
    }

    /**
     * Test complete file upload workflow for non-image file.
     *
     * @test
     */
    public function test_complete_document_upload_workflow(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $uploadPath = 'uploads/documents';

        // Act
        $result = $this->processor->process($file, $uploadPath);

        // Assert - File information returned
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original_filename', $result);
        $this->assertArrayHasKey('stored_filename', $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertArrayHasKey('mime_type', $result);

        // Assert - No thumbnail for non-image
        $this->assertArrayNotHasKey('thumbnail_path', $result);

        // Assert - File stored correctly
        Storage::disk('public')->assertExists($result['file_path']);

        // Assert - MIME type correct
        $this->assertEquals('application/pdf', $result['mime_type']);
    }

    /**
     * Test thumbnail generation for various image formats.
     *
     * @test
     */
    public function test_thumbnail_generation_for_various_image_formats(): void
    {
        // Arrange
        $imageFormats = [
            ['name' => 'photo.jpg', 'mime' => 'image/jpeg'],
            ['name' => 'graphic.png', 'mime' => 'image/png'],
            ['name' => 'animation.gif', 'mime' => 'image/gif'],
        ];

        foreach ($imageFormats as $format) {
            // Act
            $file = UploadedFile::fake()->image($format['name']);
            $result = $this->processor->process($file, 'uploads/images');

            // Assert
            $this->assertArrayHasKey('thumbnail_path', $result);
            Storage::disk('public')->assertExists($result['thumbnail_path']);
            $this->assertStringContainsString('thumb', $result['thumbnail_path']);
        }
    }

    /**
     * Test file storage with custom disk.
     *
     * @test
     */
    public function test_file_storage_with_custom_disk(): void
    {
        // Arrange
        Storage::fake('local');
        $file = UploadedFile::fake()->image('photo.jpg');
        $uploadPath = 'uploads/images';
        $options = ['disk' => 'local'];

        // Act
        $result = $this->processor->process($file, $uploadPath, $options);

        // Assert
        Storage::disk('local')->assertExists($result['file_path']);
        Storage::disk('local')->assertExists($result['thumbnail_path']);
    }

    /**
     * Test file retrieval after upload.
     *
     * @test
     */
    public function test_file_retrieval_after_upload(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $uploadPath = 'uploads/images';

        // Act
        $result = $this->processor->process($file, $uploadPath);

        // Assert - Can retrieve file
        $fileContent = Storage::disk('public')->get($result['file_path']);
        $this->assertNotEmpty($fileContent);

        // Assert - Can retrieve thumbnail
        $thumbnailContent = Storage::disk('public')->get($result['thumbnail_path']);
        $this->assertNotEmpty($thumbnailContent);

        // Assert - Thumbnail is smaller than original
        $this->assertLessThan(strlen($fileContent), strlen($thumbnailContent));
    }

    /**
     * Test multiple file uploads maintain unique filenames.
     *
     * @test
     */
    public function test_multiple_file_uploads_maintain_unique_filenames(): void
    {
        // Arrange
        $uploadPath = 'uploads/images';
        $filenames = [];

        // Act - Upload 5 files with same name
        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->image('photo.jpg');
            $result = $this->processor->process($file, $uploadPath);
            $filenames[] = $result['stored_filename'];
        }

        // Assert - All filenames are unique
        $uniqueFilenames = array_unique($filenames);
        $this->assertCount(5, $uniqueFilenames);
        $this->assertEquals($filenames, $uniqueFilenames);
    }

    /**
     * Test file upload with validation failure.
     *
     * @test
     */
    public function test_file_upload_with_validation_failure(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('large.jpg', 10240, 'image/jpeg'); // 10MB
        $uploadPath = 'uploads/images';
        $options = ['max_size' => 5120]; // 5MB limit

        // Act & Assert
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->processor->process($file, $uploadPath, $options);

        // Assert - No file stored on validation failure
        $files = Storage::disk('public')->allFiles($uploadPath);
        $this->assertEmpty($files);
    }

    /**
     * Test thumbnail generation with custom dimensions.
     *
     * @test
     */
    public function test_thumbnail_generation_with_custom_dimensions(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $uploadPath = 'uploads/images';
        $options = [
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
        ];

        // Act
        $result = $this->processor->process($file, $uploadPath, $options);

        // Assert
        $this->assertArrayHasKey('thumbnail_path', $result);
        Storage::disk('public')->assertExists($result['thumbnail_path']);

        // Verify thumbnail exists and is accessible
        $thumbnailPath = Storage::disk('public')->path($result['thumbnail_path']);
        $this->assertFileExists($thumbnailPath);
    }

    /**
     * Test file upload without thumbnail generation.
     *
     * @test
     */
    public function test_file_upload_without_thumbnail_generation(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg');
        $uploadPath = 'uploads/images';
        $options = ['thumbnail' => false];

        // Act
        $result = $this->processor->process($file, $uploadPath, $options);

        // Assert - No thumbnail created
        $this->assertArrayNotHasKey('thumbnail_path', $result);

        // Assert - Original file still stored
        Storage::disk('public')->assertExists($result['file_path']);
    }

    /**
     * Test legacy API compatibility.
     *
     * @test
     */
    public function test_legacy_api_compatibility(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg');
        $request = new class ($file) {
            private $file;

            public function __construct($file)
            {
                $this->file = $file;
            }

            public function file($name)
            {
                return $this->file;
            }
        };

        $uploadPath = 'uploads/images';
        $fileInfo = ['field' => 'photo'];

        // Act
        $result = $this->processor->fileUpload($uploadPath, $request, $fileInfo);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original_filename', $result);
        $this->assertArrayHasKey('stored_filename', $result);
        $this->assertArrayHasKey('file_path', $result);
        Storage::disk('public')->assertExists($result['file_path']);
    }

    /**
     * Test file upload with special characters in filename.
     *
     * @test
     */
    public function test_file_upload_with_special_characters_in_filename(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo with spaces & special!.jpg');
        $uploadPath = 'uploads/images';

        // Act
        $result = $this->processor->process($file, $uploadPath);

        // Assert - Original filename preserved
        $this->assertEquals('photo with spaces & special!.jpg', $result['original_filename']);

        // Assert - Stored filename is safe
        $this->assertStringContainsString('.jpg', $result['stored_filename']);
        $this->assertMatchesRegularExpression('/^\d+_[a-f0-9]+\.jpg$/', $result['stored_filename']);

        // Assert - File stored successfully
        Storage::disk('public')->assertExists($result['file_path']);
    }

    /**
     * Test concurrent file uploads.
     *
     * @test
     */
    public function test_concurrent_file_uploads(): void
    {
        // Arrange
        $uploadPath = 'uploads/images';
        $results = [];

        // Act - Simulate concurrent uploads
        for ($i = 0; $i < 10; $i++) {
            $file = UploadedFile::fake()->image("photo{$i}.jpg");
            $results[] = $this->processor->process($file, $uploadPath);
        }

        // Assert - All files uploaded successfully
        $this->assertCount(10, $results);

        // Assert - All filenames are unique
        $filenames = array_column($results, 'stored_filename');
        $this->assertCount(10, array_unique($filenames));

        // Assert - All files exist
        foreach ($results as $result) {
            Storage::disk('public')->assertExists($result['file_path']);
            Storage::disk('public')->assertExists($result['thumbnail_path']);
        }
    }

    /**
     * Test file upload preserves file metadata.
     *
     * @test
     */
    public function test_file_upload_preserves_file_metadata(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $uploadPath = 'uploads/documents';

        // Act
        $result = $this->processor->process($file, $uploadPath);

        // Assert - Metadata preserved
        $this->assertEquals('document.pdf', $result['original_filename']);
        $this->assertEquals('application/pdf', $result['mime_type']);
        $this->assertGreaterThan(0, $result['file_size']);
        $this->assertIsInt($result['file_size']);
    }
}
