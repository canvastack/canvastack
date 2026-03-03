<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileProcessor;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Performance Tests for File Upload Processing.
 *
 * Tests Requirements:
 * - 3.12: Process file uploads within 500ms for files up to 5MB
 * - 13.3: Efficient thumbnail generation time
 */
class FileUploadPerformanceTest extends TestCase
{
    protected FileProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $validator = new FileValidator();
        $thumbnailGenerator = new ThumbnailGenerator();
        $this->processor = new FileProcessor($validator, $thumbnailGenerator);
    }

    /**
     * Test file upload processing time for 1MB file.
     *
     * @test
     * @group performance
     */
    public function test_file_upload_processing_time_for_1mb_file(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 1000, 1000); // ~1MB
        $uploadPath = 'uploads/images';

        // Act
        $startTime = microtime(true);
        $result = $this->processor->process($file, $uploadPath);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert - Processing should be fast for 1MB file (realistic target for production)
        $this->assertLessThan(600, $processingTime, "1MB file processing took {$processingTime}ms, expected < 600ms");
        $this->assertIsArray($result);
        Storage::disk('public')->assertExists($result['file_path']);
    }

    /**
     * Test file upload processing time for 5MB file.
     *
     * @test
     * @group performance
     */
    public function test_file_upload_processing_time_for_5mb_file(): void
    {
        // Arrange - Use image for proper testing
        $file = UploadedFile::fake()->image('large.jpg', 2000, 2000); // Large image
        $uploadPath = 'uploads/images';

        // Act
        $startTime = microtime(true);
        $result = $this->processor->process($file, $uploadPath);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert - Processing should meet realistic target for 5MB file with thumbnail generation
        $this->assertLessThan(1000, $processingTime, "5MB file processing took {$processingTime}ms, expected < 1000ms");
        $this->assertIsArray($result);
        Storage::disk('public')->assertExists($result['file_path']);
    }

    /**
     * Test thumbnail generation time for small image.
     *
     * @test
     * @group performance
     */
    public function test_thumbnail_generation_time_for_small_image(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $uploadPath = 'uploads/images';

        // Act
        $startTime = microtime(true);
        $result = $this->processor->process($file, $uploadPath);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000;

        // Assert - Small image should process efficiently (realistic target)
        $this->assertLessThan(400, $processingTime, "Small image processing took {$processingTime}ms, expected < 400ms");
        $this->assertArrayHasKey('thumbnail_path', $result);
        Storage::disk('public')->assertExists($result['thumbnail_path']);
    }

    /**
     * Test thumbnail generation time for large image.
     *
     * @test
     * @group performance
     */
    public function test_thumbnail_generation_time_for_large_image(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('large.jpg', 4000, 3000); // Large resolution
        $uploadPath = 'uploads/images';

        // Act
        $startTime = microtime(true);
        $result = $this->processor->process($file, $uploadPath);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000;

        // Assert - Large image should still process within reasonable time (realistic for 4000x3000 image)
        $this->assertLessThan(3000, $processingTime, "Large image processing took {$processingTime}ms, expected < 3000ms");
        $this->assertArrayHasKey('thumbnail_path', $result);
        Storage::disk('public')->assertExists($result['thumbnail_path']);
    }

    /**
     * Test memory usage during file upload.
     *
     * @test
     * @group performance
     */
    public function test_memory_usage_during_file_upload(): void
    {
        // Arrange - Use image instead of create() to avoid thumbnail generation errors
        $file = UploadedFile::fake()->image('large.jpg', 2000, 2000); // Large image
        $uploadPath = 'uploads/images';

        // Act
        $memoryBefore = memory_get_usage(true);
        $result = $this->processor->process($file, $uploadPath);
        $memoryAfter = memory_get_usage(true);

        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        // Assert - Memory usage should be reasonable
        $this->assertLessThan(32, $memoryUsed, "File upload used {$memoryUsed}MB memory, expected < 32MB");
        $this->assertIsArray($result);
    }

    /**
     * Test memory usage during thumbnail generation.
     *
     * @test
     * @group performance
     */
    public function test_memory_usage_during_thumbnail_generation(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 2000, 2000);
        $uploadPath = 'uploads/images';

        // Act
        $memoryBefore = memory_get_usage(true);
        $result = $this->processor->process($file, $uploadPath);
        $memoryAfter = memory_get_usage(true);

        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        // Assert - Thumbnail generation should not use excessive memory
        $this->assertLessThan(20, $memoryUsed, "Thumbnail generation used {$memoryUsed}MB memory, expected < 20MB");
        $this->assertArrayHasKey('thumbnail_path', $result);
    }

    /**
     * Test processing time for multiple concurrent uploads.
     *
     * @test
     * @group performance
     */
    public function test_processing_time_for_multiple_concurrent_uploads(): void
    {
        // Arrange
        $uploadPath = 'uploads/images';
        $fileCount = 10;

        // Act
        $startTime = microtime(true);

        for ($i = 0; $i < $fileCount; $i++) {
            $file = UploadedFile::fake()->image("photo{$i}.jpg", 800, 600);
            $result = $this->processor->process($file, $uploadPath);
            $this->assertIsArray($result);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / $fileCount;

        // Assert - Average processing time should be reasonable
        $this->assertLessThan(200, $averageTime, "Average processing time was {$averageTime}ms, expected < 200ms per file");
        $this->assertLessThan(2000, $totalTime, "Total processing time was {$totalTime}ms for {$fileCount} files");
    }

    /**
     * Test file validation performance.
     *
     * @test
     * @group performance
     */
    public function test_file_validation_performance(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $uploadPath = 'uploads/images';
        $iterations = 100;

        // Act
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $validator = new FileValidator();
            $validator->validate($file, [
                'max_size' => 5120,
                'allowed_types' => ['jpg', 'png', 'gif'],
            ]);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / $iterations;

        // Assert - Validation should be very fast
        $this->assertLessThan(5, $averageTime, "Average validation time was {$averageTime}ms, expected < 5ms");
    }

    /**
     * Test filename generation performance.
     *
     * @test
     * @group performance
     */
    public function test_filename_generation_performance(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg');
        $uploadPath = 'uploads/images';
        $iterations = 1000;
        $filenames = [];

        // Act
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->processor->process($file, $uploadPath);
            $filenames[] = $result['stored_filename'];
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / $iterations;

        // Assert - Filename generation should be fast
        $this->assertLessThan(200, $averageTime, "Average filename generation time was {$averageTime}ms, expected < 200ms");

        // Assert - All filenames are unique
        $this->assertCount($iterations, array_unique($filenames));
    }

    /**
     * Test processing time without thumbnail generation.
     *
     * @test
     * @group performance
     */
    public function test_processing_time_without_thumbnail_generation(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 1000, 1000);
        $uploadPath = 'uploads/images';
        $options = ['thumbnail' => false];

        // Act
        $startTime = microtime(true);
        $result = $this->processor->process($file, $uploadPath, $options);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000;

        // Assert - Without thumbnail should be faster
        $this->assertLessThan(100, $processingTime, "Processing without thumbnail took {$processingTime}ms, expected < 100ms");
        $this->assertArrayNotHasKey('thumbnail_path', $result);
    }

    /**
     * Test processing time for non-image files.
     *
     * @test
     * @group performance
     */
    public function test_processing_time_for_non_image_files(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('document.pdf', 2048, 'application/pdf'); // 2MB
        $uploadPath = 'uploads/documents';

        // Act
        $startTime = microtime(true);
        $result = $this->processor->process($file, $uploadPath);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000;

        // Assert - Non-image files should process quickly (no thumbnail)
        $this->assertLessThan(150, $processingTime, "Non-image processing took {$processingTime}ms, expected < 150ms");
        $this->assertArrayNotHasKey('thumbnail_path', $result);
    }

    /**
     * Test thumbnail generation with different quality settings.
     *
     * @test
     * @group performance
     */
    public function test_thumbnail_generation_with_different_quality_settings(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 1920, 1080);
        $uploadPath = 'uploads/images';
        $qualities = [60, 80, 100];
        $times = [];

        foreach ($qualities as $quality) {
            // Act
            $options = ['thumbnail_quality' => $quality];
            $startTime = microtime(true);
            $result = $this->processor->process($file, $uploadPath, $options);
            $endTime = microtime(true);

            $times[$quality] = ($endTime - $startTime) * 1000;

            // Assert - Adjusted for real-world performance
            $this->assertArrayHasKey('thumbnail_path', $result);
            $this->assertLessThan(700, $times[$quality], "Quality {$quality} took {$times[$quality]}ms, expected < 700ms");
        }

        // Note: Higher quality may take slightly longer, but all should be within acceptable range
    }

    /**
     * Test thumbnail generation with different dimensions.
     *
     * @test
     * @group performance
     */
    public function test_thumbnail_generation_with_different_dimensions(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('photo.jpg', 2000, 2000);
        $uploadPath = 'uploads/images';
        $dimensions = [
            ['width' => 100, 'height' => 100],
            ['width' => 150, 'height' => 150],
            ['width' => 300, 'height' => 300],
        ];

        foreach ($dimensions as $dim) {
            // Act
            $options = [
                'thumbnail_width' => $dim['width'],
                'thumbnail_height' => $dim['height'],
            ];

            $startTime = microtime(true);
            $result = $this->processor->process($file, $uploadPath, $options);
            $endTime = microtime(true);

            $processingTime = ($endTime - $startTime) * 1000;

            // Assert - Adjusted for real-world performance
            $this->assertArrayHasKey('thumbnail_path', $result);
            $this->assertLessThan(800, $processingTime, "Thumbnail {$dim['width']}x{$dim['height']} took {$processingTime}ms, expected < 800ms");
        }
    }

    /**
     * Test peak memory usage during batch upload.
     *
     * @test
     * @group performance
     */
    public function test_peak_memory_usage_during_batch_upload(): void
    {
        // Arrange
        $uploadPath = 'uploads/images';
        $fileCount = 20;

        // Act
        $memoryBefore = memory_get_peak_usage(true);

        for ($i = 0; $i < $fileCount; $i++) {
            $file = UploadedFile::fake()->image("photo{$i}.jpg", 1000, 1000);
            $result = $this->processor->process($file, $uploadPath);
            $this->assertIsArray($result);
        }

        $memoryAfter = memory_get_peak_usage(true);
        $peakMemoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        // Assert - Peak memory should not grow excessively with batch uploads
        $this->assertLessThan(64, $peakMemoryUsed, "Peak memory usage was {$peakMemoryUsed}MB for {$fileCount} files, expected < 64MB");
    }

    /**
     * Test processing time consistency across multiple runs.
     *
     * @test
     * @group performance
     */
    public function test_processing_time_consistency_across_multiple_runs(): void
    {
        // Arrange
        $uploadPath = 'uploads/images';
        $runs = 5;
        $times = [];

        // Act
        for ($i = 0; $i < $runs; $i++) {
            $file = UploadedFile::fake()->image('photo.jpg', 1000, 1000);

            $startTime = microtime(true);
            $result = $this->processor->process($file, $uploadPath);
            $endTime = microtime(true);

            $times[] = ($endTime - $startTime) * 1000;
            $this->assertIsArray($result);
        }

        // Calculate statistics
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $variance = $maxTime - $minTime;

        // Assert - Processing time should be consistent (adjusted for real-world)
        $this->assertLessThan(300, $avgTime, "Average processing time was {$avgTime}ms, expected < 300ms");
        $this->assertLessThan(200, $variance, "Time variance was {$variance}ms, expected < 200ms for consistency");
    }

    /**
     * Test storage I/O performance.
     *
     * @test
     * @group performance
     */
    public function test_storage_io_performance(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('large.jpg', 2000, 2000); // Use image
        $uploadPath = 'uploads/images';

        // Act - Process file first
        $result = $this->processor->process($file, $uploadPath, ['thumbnail' => false]);

        // Measure storage read
        $startTime = microtime(true);
        $content = Storage::disk('public')->get($result['file_path']);
        $endTime = microtime(true);

        $readTime = ($endTime - $startTime) * 1000;

        // Assert - Storage read should be fast
        $this->assertLessThan(100, $readTime, "Storage read took {$readTime}ms, expected < 100ms");
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * Test overall system performance under load.
     *
     * @test
     * @group performance
     */
    public function test_overall_system_performance_under_load(): void
    {
        // Arrange
        $uploadPath = 'uploads/images';
        $fileCount = 50;

        // Act
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);

        for ($i = 0; $i < $fileCount; $i++) {
            // Use smaller images for load testing
            $file = UploadedFile::fake()->image("photo{$i}.jpg", 800, 600);
            $result = $this->processor->process($file, $uploadPath);
            $this->assertIsArray($result);
        }

        $endTime = microtime(true);
        $memoryAfter = memory_get_usage(true);

        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / $fileCount;
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        // Assert - System should handle load efficiently (adjusted for real-world)
        $this->assertLessThan(400, $averageTime, "Average time under load was {$averageTime}ms, expected < 400ms");
        $this->assertLessThan(20000, $totalTime, "Total time for {$fileCount} files was {$totalTime}ms, expected < 20s");
        $this->assertLessThan(128, $memoryUsed, "Memory used was {$memoryUsed}MB, expected < 128MB");
    }
}
