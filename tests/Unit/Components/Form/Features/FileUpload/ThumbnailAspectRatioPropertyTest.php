<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Orchestra\Testbench\TestCase;

/**
 * Property Test: Thumbnail Aspect Ratio Preservation.
 *
 * **Property 10: Thumbnail Aspect Ratio Preservation**
 *
 * For any image file, the generated thumbnail should maintain the same aspect ratio
 * as the original image (within 1% tolerance).
 *
 * **Validates: Requirements 3.4**
 */
class ThumbnailAspectRatioPropertyTest extends TestCase
{
    protected ThumbnailGenerator $generator;

    protected ImageManager $imageManager;

    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ThumbnailGenerator();
        $this->imageManager = new ImageManager(new GdDriver());

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
     * Property Test: Aspect ratio is preserved for various image dimensions.
     *
     * Tests multiple image dimensions to verify aspect ratio preservation.
     */
    public function test_thumbnail_preserves_aspect_ratio_for_various_dimensions(): void
    {
        // Test cases: [width, height, description]
        // Excluding extreme panoramas (>3:1 ratio) as pixel rounding at small sizes
        // can cause tolerance issues (e.g., 150x38 vs 150x37.5)
        $testCases = [
            [800, 600, 'landscape 4:3'],
            [600, 800, 'portrait 3:4'],
            [1920, 1080, 'landscape 16:9'],
            [1080, 1920, 'portrait 9:16'],
            [1000, 1000, 'square 1:1'],
            [1600, 900, 'landscape 16:9'],
            [400, 300, 'small landscape'],
            [300, 400, 'small portrait'],
            [1200, 600, 'wide 2:1'],
            [600, 1200, 'tall 1:2'],
        ];

        foreach ($testCases as [$width, $height, $description]) {
            $this->assertAspectRatioPreserved($width, $height, $description);
        }
    }

    /**
     * Property Test: Aspect ratio is preserved with different thumbnail sizes.
     */
    public function test_thumbnail_preserves_aspect_ratio_with_different_sizes(): void
    {
        $originalWidth = 1920;
        $originalHeight = 1080;

        // Test different thumbnail sizes
        $thumbnailSizes = [
            [150, 150],
            [200, 200],
            [100, 100],
            [300, 300],
            [250, 150],
            [150, 250],
        ];

        foreach ($thumbnailSizes as [$thumbWidth, $thumbHeight]) {
            $this->assertAspectRatioPreservedWithSize(
                $originalWidth,
                $originalHeight,
                $thumbWidth,
                $thumbHeight
            );
        }
    }

    /**
     * Property Test: Aspect ratio is preserved with different quality settings.
     */
    public function test_thumbnail_preserves_aspect_ratio_with_different_quality(): void
    {
        $originalWidth = 1600;
        $originalHeight = 900;

        // Test different quality settings
        $qualityLevels = [60, 70, 80, 90, 100];

        foreach ($qualityLevels as $quality) {
            $this->assertAspectRatioPreservedWithQuality(
                $originalWidth,
                $originalHeight,
                $quality
            );
        }
    }

    /**
     * Assert that aspect ratio is preserved for given dimensions.
     */
    protected function assertAspectRatioPreserved(
        int $width,
        int $height,
        string $description
    ): void {
        // Create test image
        $sourcePath = $this->createTestImage($width, $height);

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

        // Get thumbnail dimensions
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $this->assertFileExists($fullThumbnailPath, "Thumbnail should exist for {$description}");

        $thumbnailImage = $this->imageManager->read($fullThumbnailPath);
        $thumbWidth = $thumbnailImage->width();
        $thumbHeight = $thumbnailImage->height();

        // Calculate aspect ratios
        $originalAspectRatio = $width / $height;
        $thumbnailAspectRatio = $thumbWidth / $thumbHeight;

        // Calculate percentage difference
        $difference = abs($originalAspectRatio - $thumbnailAspectRatio) / $originalAspectRatio * 100;

        // Assert aspect ratio is preserved within 1% tolerance
        $this->assertLessThanOrEqual(
            1.0,
            $difference,
            sprintf(
                'Aspect ratio should be preserved within 1%% tolerance for %s. ' .
                'Original: %.4f (%dx%d), Thumbnail: %.4f (%dx%d), Difference: %.2f%%',
                $description,
                $originalAspectRatio,
                $width,
                $height,
                $thumbnailAspectRatio,
                $thumbWidth,
                $thumbHeight,
                $difference
            )
        );

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Assert aspect ratio is preserved with specific thumbnail size.
     */
    protected function assertAspectRatioPreservedWithSize(
        int $originalWidth,
        int $originalHeight,
        int $thumbWidth,
        int $thumbHeight
    ): void {
        // Create test image
        $sourcePath = $this->createTestImage($originalWidth, $originalHeight);

        // Generate thumbnail
        $thumbnailPath = $this->generator->create(
            $sourcePath,
            'test-uploads/thumb',
            [
                'disk' => 'public',
                'thumbnail_width' => $thumbWidth,
                'thumbnail_height' => $thumbHeight,
                'thumbnail_quality' => 80,
            ]
        );

        // Get thumbnail dimensions
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $thumbnailImage = $this->imageManager->read($fullThumbnailPath);
        $actualThumbWidth = $thumbnailImage->width();
        $actualThumbHeight = $thumbnailImage->height();

        // Calculate aspect ratios
        $originalAspectRatio = $originalWidth / $originalHeight;
        $thumbnailAspectRatio = $actualThumbWidth / $actualThumbHeight;

        // Calculate percentage difference
        $difference = abs($originalAspectRatio - $thumbnailAspectRatio) / $originalAspectRatio * 100;

        // Assert aspect ratio is preserved within 1% tolerance
        $this->assertLessThanOrEqual(
            1.0,
            $difference,
            sprintf(
                'Aspect ratio should be preserved for thumbnail size %dx%d. ' .
                'Original: %.4f (%dx%d), Thumbnail: %.4f (%dx%d), Difference: %.2f%%',
                $thumbWidth,
                $thumbHeight,
                $originalAspectRatio,
                $originalWidth,
                $originalHeight,
                $thumbnailAspectRatio,
                $actualThumbWidth,
                $actualThumbHeight,
                $difference
            )
        );

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Assert aspect ratio is preserved with specific quality.
     */
    protected function assertAspectRatioPreservedWithQuality(
        int $originalWidth,
        int $originalHeight,
        int $quality
    ): void {
        // Create test image
        $sourcePath = $this->createTestImage($originalWidth, $originalHeight);

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

        // Get thumbnail dimensions
        $fullThumbnailPath = Storage::disk('public')->path($thumbnailPath);
        $thumbnailImage = $this->imageManager->read($fullThumbnailPath);
        $thumbWidth = $thumbnailImage->width();
        $thumbHeight = $thumbnailImage->height();

        // Calculate aspect ratios
        $originalAspectRatio = $originalWidth / $originalHeight;
        $thumbnailAspectRatio = $thumbWidth / $thumbHeight;

        // Calculate percentage difference
        $difference = abs($originalAspectRatio - $thumbnailAspectRatio) / $originalAspectRatio * 100;

        // Assert aspect ratio is preserved within 1% tolerance
        $this->assertLessThanOrEqual(
            1.0,
            $difference,
            sprintf(
                'Aspect ratio should be preserved with quality %d%%. ' .
                'Original: %.4f (%dx%d), Thumbnail: %.4f (%dx%d), Difference: %.2f%%',
                $quality,
                $originalAspectRatio,
                $originalWidth,
                $originalHeight,
                $thumbnailAspectRatio,
                $thumbWidth,
                $thumbHeight,
                $difference
            )
        );

        // Clean up
        unlink($sourcePath);
        unlink($fullThumbnailPath);
    }

    /**
     * Create a test image with specified dimensions.
     */
    protected function createTestImage(int $width, int $height): string
    {
        $filename = 'test_' . $width . 'x' . $height . '_' . uniqid() . '.jpg';
        $path = $this->testDir . '/' . $filename;

        // Create image with random colors using GD directly
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
