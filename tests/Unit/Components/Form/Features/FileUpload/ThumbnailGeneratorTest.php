<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

/**
 * Unit Tests for ThumbnailGenerator.
 *
 * Tests the thumbnail generation functionality including:
 * - Thumbnail creation with default options
 * - Custom dimensions and quality
 * - Aspect ratio preservation
 * - Directory creation
 * - File deletion
 * - Driver detection
 */
class ThumbnailGeneratorTest extends TestCase
{
    protected ThumbnailGenerator $generator;

    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ThumbnailGenerator();

        // Setup test directory
        Storage::fake('public');
        $this->testDir = Storage::disk('public')->path('test-uploads');
        if (!file_exists($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testDir)) {
            $this->deleteDirectory($this->testDir);
        }

        parent::tearDown();
    }

    /**
     * Test thumbnail creation with default options.
     */
    public function test_creates_thumbnail_with_default_options(): void
    {
        // Create source image
        $sourcePath = $this->createTestImage(800, 600);

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb'
        );

        // Assert thumbnail was created
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath);

        // Assert thumbnail has correct dimensions (default 150x150)
        $imageInfo = getimagesize($fullThumbnailPath);
        $this->assertLessThanOrEqual(150, $imageInfo[0], 'Thumbnail width should not exceed 150px');
        $this->assertLessThanOrEqual(150, $imageInfo[1], 'Thumbnail height should not exceed 150px');

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Test thumbnail creation with custom dimensions.
     */
    public function test_creates_thumbnail_with_custom_dimensions(): void
    {
        // Create source image
        $sourcePath = $this->createTestImage(1920, 1080);

        // Generate thumbnail with custom dimensions
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb',
            [
                'thumbnail_width' => 200,
                'thumbnail_height' => 200,
            ]
        );

        // Assert thumbnail was created
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath);

        // Assert thumbnail has correct dimensions
        $imageInfo = getimagesize($fullThumbnailPath);
        $this->assertLessThanOrEqual(200, $imageInfo[0], 'Thumbnail width should not exceed 200px');
        $this->assertLessThanOrEqual(200, $imageInfo[1], 'Thumbnail height should not exceed 200px');

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Test thumbnail creation with custom quality.
     */
    public function test_creates_thumbnail_with_custom_quality(): void
    {
        // Create source image
        $sourcePath = $this->createTestImage(800, 600);

        // Generate thumbnail with low quality
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb',
            [
                'thumbnail_quality' => 50,
            ]
        );

        // Assert thumbnail was created
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath);

        // Assert thumbnail file size is smaller due to lower quality
        $this->assertLessThan(
            filesize($sourcePath),
            filesize($fullThumbnailPath),
            'Thumbnail should be smaller than source'
        );

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Test thumbnail directory is created if it doesn't exist.
     */
    public function test_creates_thumbnail_directory_if_not_exists(): void
    {
        // Create source image
        $sourcePath = $this->createTestImage(800, 600);

        // Generate thumbnail in non-existent directory
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/new-thumb-dir',
            [
                'disk' => 'public',
            ]
        );

        // Assert directory was created
        $thumbnailDir = Storage::disk('public')->path('test-uploads/new-thumb-dir');
        $this->assertDirectoryExists($thumbnailDir);

        // Assert thumbnail was created
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath);

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
        rmdir($thumbnailDir);
    }

    /**
     * Test thumbnail preserves aspect ratio.
     */
    public function test_thumbnail_preserves_aspect_ratio(): void
    {
        // Create landscape image
        $sourcePath = $this->createTestImage(1600, 900);

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb',
            [
                'thumbnail_width' => 150,
                'thumbnail_height' => 150,
            ]
        );

        // Get thumbnail dimensions
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $imageInfo = getimagesize($fullThumbnailPath);

        // Calculate aspect ratios
        $sourceAspectRatio = 1600 / 900;
        $thumbnailAspectRatio = $imageInfo[0] / $imageInfo[1];

        // Assert aspect ratio is preserved (within 1% tolerance)
        $difference = abs($sourceAspectRatio - $thumbnailAspectRatio) / $sourceAspectRatio * 100;
        $this->assertLessThanOrEqual(1.0, $difference, 'Aspect ratio should be preserved');

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Test thumbnail deletion.
     */
    public function test_deletes_thumbnail(): void
    {
        // Create source image
        $sourcePath = $this->createTestImage(800, 600);

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb'
        );

        // Assert thumbnail exists
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath);

        // Delete thumbnail
        $result = $this->generator->delete($thumbnailPath);

        // Assert deletion was successful
        $this->assertTrue($result);
        $this->assertFileDoesNotExist($fullThumbnailPath);

        // Clean up
        unlink($sourcePath);
    }

    /**
     * Test deleting non-existent thumbnail returns false.
     */
    public function test_delete_non_existent_thumbnail_returns_false(): void
    {
        $result = $this->generator->delete('non-existent/thumbnail.jpg');

        $this->assertFalse($result);
    }

    /**
     * Test driver detection.
     */
    public function test_detects_image_driver(): void
    {
        $driverName = $this->generator->getDriverName();

        $this->assertContains($driverName, ['gd', 'imagick']);
    }

    /**
     * Test thumbnail is created as JPEG format.
     */
    public function test_thumbnail_is_jpeg_format(): void
    {
        // Create source image
        $sourcePath = $this->createTestImage(800, 600);

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb'
        );

        // Assert thumbnail is JPEG
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $imageInfo = getimagesize($fullThumbnailPath);
        $this->assertEquals(IMAGETYPE_JPEG, $imageInfo[2], 'Thumbnail should be JPEG format');

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Test thumbnail filename matches source filename.
     */
    public function test_thumbnail_filename_matches_source(): void
    {
        // Create source image with specific filename
        $sourceFilename = 'test_image_12345.jpg';
        $sourcePath = $this->testDir . '/' . $sourceFilename;

        $image = imagecreatetruecolor(800, 600);
        $color = imagecolorallocate($image, 100, 150, 200);
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $sourcePath, 80);
        imagedestroy($image);

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb'
        );

        // Assert thumbnail has same filename
        $this->assertStringContainsString($sourceFilename, $thumbnailPath);

        // Clean up
        unlink($sourcePath);
        unlink(Storage::disk('public')->path($thumbnailPath));
    }

    /**
     * Create a test image with specified dimensions.
     */
    protected function createTestImage(int $width, int $height): string
    {
        $filename = 'test_' . $width . 'x' . $height . '_' . uniqid() . '.jpg';
        $path = $this->testDir . '/' . $filename;

        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $path, 80);
        imagedestroy($image);

        return $path;
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
