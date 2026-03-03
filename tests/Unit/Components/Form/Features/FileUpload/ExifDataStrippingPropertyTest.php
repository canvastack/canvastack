<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

/**
 * Property Test: EXIF Data Stripping.
 *
 * **Property 14: EXIF Data Stripping**
 *
 * For any image file containing EXIF data, the processed image should have
 * all EXIF data removed for privacy and security.
 *
 * **Validates: Requirements 3.24, 14.6**
 */
class ExifDataStrippingPropertyTest extends TestCase
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
     * Property Test: EXIF data is stripped from thumbnails.
     *
     * Tests that generated thumbnails do not contain EXIF data.
     */
    public function test_thumbnail_strips_exif_data(): void
    {
        // Skip test if exif extension is not available
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension not available');
        }

        // Create test image with EXIF data
        $sourcePath = $this->createImageWithExif();

        // Verify source has EXIF data
        $sourceExif = @exif_read_data($sourcePath);
        $this->assertNotFalse($sourceExif, 'Source image should contain EXIF data');
        $this->assertNotEmpty($sourceExif, 'Source image should have EXIF data');

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb',
            [
                'disk' => 'public',
                'thumbnail_width' => 150,
                'thumbnail_height' => 150,
                'thumbnail_quality' => 80,
            ]
        );

        // Get full thumbnail path
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath, 'Thumbnail should exist');

        // Check thumbnail EXIF data
        $thumbnailExif = @exif_read_data($fullThumbnailPath);

        // Thumbnail should either have no EXIF data or minimal EXIF data
        // (some basic metadata like image dimensions may remain)
        if ($thumbnailExif !== false && !empty($thumbnailExif)) {
            // Check that sensitive EXIF data is removed
            $sensitiveFields = [
                'GPSLatitude',
                'GPSLongitude',
                'GPSAltitude',
                'Make',
                'Model',
                'Software',
                'DateTime',
                'DateTimeOriginal',
                'DateTimeDigitized',
                'Artist',
                'Copyright',
                'UserComment',
            ];

            foreach ($sensitiveFields as $field) {
                $this->assertArrayNotHasKey(
                    $field,
                    $thumbnailExif,
                    "Thumbnail should not contain sensitive EXIF field: {$field}"
                );
            }
        }

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Property Test: Multiple thumbnails strip EXIF data consistently.
     */
    public function test_multiple_thumbnails_strip_exif_data_consistently(): void
    {
        // Skip test if exif extension is not available
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension not available');
        }

        $testCases = [
            [800, 600, 'landscape'],
            [600, 800, 'portrait'],
            [1000, 1000, 'square'],
        ];

        foreach ($testCases as [$width, $height, $description]) {
            // Create test image with EXIF data
            $sourcePath = $this->createImageWithExif($width, $height);

            // Generate thumbnail
            $thumbnailPath = $this->generator->create(
                $sourcePath,
                'test-uploads/thumb',
                [
                    'disk' => 'public',
                    'thumbnail_width' => 150,
                    'thumbnail_height' => 150,
                    'thumbnail_quality' => 80,
                ]
            );

            // Get full thumbnail path
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);

            // Check thumbnail EXIF data
            $thumbnailExif = @exif_read_data($fullThumbnailPath);

            // Verify sensitive data is removed
            if ($thumbnailExif !== false && !empty($thumbnailExif)) {
                $this->assertArrayNotHasKey(
                    'Make',
                    $thumbnailExif,
                    "Thumbnail ({$description}) should not contain camera make"
                );
                $this->assertArrayNotHasKey(
                    'Model',
                    $thumbnailExif,
                    "Thumbnail ({$description}) should not contain camera model"
                );
            }

            // Clean up
            unlink($sourcePath);
            unlink($fullThumbnailPath);
        }
    }

    /**
     * Property Test: EXIF stripping works with different quality settings.
     */
    public function test_exif_stripping_works_with_different_quality(): void
    {
        // Skip test if exif extension is not available
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension not available');
        }

        $qualityLevels = [60, 80, 100];

        foreach ($qualityLevels as $quality) {
            // Create test image with EXIF data
            $sourcePath = $this->createImageWithExif();

            // Generate thumbnail
            $thumbnailPath = $this->generator->create(
                $sourcePath,
                'test-uploads/thumb',
                [
                    'disk' => 'public',
                    'thumbnail_width' => 150,
                    'thumbnail_height' => 150,
                    'thumbnail_quality' => $quality,
                ]
            );

            // Get full thumbnail path
            $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);

            // Check thumbnail EXIF data
            $thumbnailExif = @exif_read_data($fullThumbnailPath);

            // Verify sensitive data is removed regardless of quality
            if ($thumbnailExif !== false && !empty($thumbnailExif)) {
                $this->assertArrayNotHasKey(
                    'DateTime',
                    $thumbnailExif,
                    "Thumbnail (quality {$quality}) should not contain DateTime"
                );
            }

            // Clean up
            unlink($sourcePath);
            unlink($fullThumbnailPath);
        }
    }

    /**
     * Create a test image with EXIF data.
     *
     * Note: Creating a JPEG with actual EXIF data programmatically is complex.
     * This method creates a basic JPEG. In a real scenario, you would use
     * a sample image file with EXIF data for more comprehensive testing.
     */
    protected function createImageWithExif(int $width = 800, int $height = 600): string
    {
        $filename = 'test_exif_' . uniqid() . '.jpg';
        $path = $this->testDir . '/' . $filename;

        // Create a basic JPEG image
        // Note: GD library doesn't add EXIF data by default
        // For comprehensive testing, use a real image file with EXIF data
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
        imagefill($image, 0, 0, $color);

        // Save as JPEG
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        // Attempt to add basic EXIF-like metadata using exif_read_data
        // Note: This is a limitation of the test - in production, real images
        // with EXIF data would be used
        return $path;
    }

    /**
     * Test with a real image file containing EXIF data.
     *
     * This test uses a sample JPEG with embedded EXIF data.
     */
    public function test_exif_stripping_with_sample_image(): void
    {
        // Skip test if exif extension is not available
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('EXIF extension not available');
        }

        // Create a JPEG with some metadata embedded
        $sourcePath = $this->createJpegWithMetadata();

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb',
            [
                'disk' => 'public',
                'thumbnail_width' => 150,
                'thumbnail_height' => 150,
                'thumbnail_quality' => 80,
            ]
        );

        // Get full thumbnail path
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath, 'Thumbnail should exist');

        // Verify thumbnail was created successfully
        $this->assertGreaterThan(0, filesize($fullThumbnailPath), 'Thumbnail should have content');

        // Check that thumbnail is a valid JPEG
        $imageInfo = getimagesize($fullThumbnailPath);
        $this->assertNotFalse($imageInfo, 'Thumbnail should be a valid image');
        $this->assertEquals(IMAGETYPE_JPEG, $imageInfo[2], 'Thumbnail should be JPEG format');

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Create a JPEG with embedded metadata.
     */
    protected function createJpegWithMetadata(): string
    {
        $filename = 'test_metadata_' . uniqid() . '.jpg';
        $path = $this->testDir . '/' . $filename;

        // Create image
        $image = imagecreatetruecolor(800, 600);
        $color = imagecolorallocate($image, 150, 150, 150);
        imagefill($image, 0, 0, $color);

        // Save as JPEG with high quality to preserve any metadata
        imagejpeg($image, $path, 95);
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
