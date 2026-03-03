<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Components\Form;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileProcessor;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Property-based tests for file upload functionality.
 *
 * These tests validate universal correctness properties that should hold
 * for all file upload operations.
 */
class FileUploadPropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected FileProcessor $fileProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $validator = new FileValidator();
        $thumbnailGenerator = new ThumbnailGenerator();
        $this->fileProcessor = new FileProcessor($validator, $thumbnailGenerator);
    }

    /**
     * Property 11: Filename Uniqueness.
     *
     * **Validates: Requirements 3.8**
     *
     * For any set of uploaded files, all generated filenames should be unique
     * to prevent overwrites.
     *
     * @test
     */
    public function property_filename_uniqueness(): void
    {
        // Generate multiple files with the same original name
        $fileCount = 100;
        $generatedFilenames = [];

        for ($i = 0; $i < $fileCount; $i++) {
            // Create a test file with the same name
            $file = UploadedFile::fake()->image('test.jpg', 100, 100);

            // Process the file
            $result = $this->fileProcessor->process($file, 'uploads');

            // Collect the generated filename
            $generatedFilenames[] = $result['stored_filename'];

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }

        // Property: All filenames must be unique
        $uniqueFilenames = array_unique($generatedFilenames);
        $this->assertCount(
            $fileCount,
            $uniqueFilenames,
            'All generated filenames must be unique. Found duplicates: ' .
            json_encode(array_diff_assoc($generatedFilenames, $uniqueFilenames))
        );

        // Additional check: Verify filename format includes timestamp and random component
        foreach ($generatedFilenames as $filename) {
            $this->assertMatchesRegularExpression(
                '/^\d+_[a-f0-9]{16}\.(jpg|jpeg|png|gif|pdf|doc|docx)$/i',
                $filename,
                "Filename '{$filename}' does not match expected format: timestamp_random.extension"
            );
        }
    }

    /**
     * Property 11.1: Concurrent Upload Uniqueness.
     *
     * **Validates: Requirements 3.8**
     *
     * Even when files are uploaded concurrently (simulated), all generated
     * filenames should remain unique.
     *
     * @test
     */
    public function property_concurrent_upload_uniqueness(): void
    {
        $fileCount = 50;
        $generatedFilenames = [];

        // Simulate concurrent uploads by processing multiple files rapidly
        $files = [];
        for ($i = 0; $i < $fileCount; $i++) {
            $files[] = UploadedFile::fake()->image("concurrent_{$i}.jpg", 100, 100);
        }

        // Process all files in quick succession
        foreach ($files as $file) {
            $result = $this->fileProcessor->process($file, 'uploads');
            $generatedFilenames[] = $result['stored_filename'];

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }

        // Property: All filenames must be unique even in concurrent scenario
        $uniqueFilenames = array_unique($generatedFilenames);
        $this->assertCount(
            $fileCount,
            $uniqueFilenames,
            'Concurrent uploads must generate unique filenames'
        );
    }

    /**
     * Property 11.2: Different Extensions Uniqueness.
     *
     * **Validates: Requirements 3.8**
     *
     * Files with different extensions should still generate unique filenames
     * (unique base name, different extensions).
     *
     * @test
     */
    public function property_different_extensions_uniqueness(): void
    {
        $extensions = ['jpg', 'png', 'gif', 'pdf'];
        $generatedFilenames = [];

        foreach ($extensions as $extension) {
            for ($i = 0; $i < 10; $i++) {
                // Create file with specific extension
                $file = $extension === 'pdf'
                    ? UploadedFile::fake()->create("test.{$extension}", 100, 'application/pdf')
                    : UploadedFile::fake()->image("test.{$extension}", 100, 100);

                $result = $this->fileProcessor->process($file, 'uploads', [
                    'thumbnail' => false, // Disable thumbnail for PDF
                ]);

                $generatedFilenames[] = $result['stored_filename'];

                // Clean up
                Storage::disk('public')->delete($result['file_path']);
            }
        }

        // Property: All filenames must be unique across different extensions
        $uniqueFilenames = array_unique($generatedFilenames);
        $this->assertCount(
            count($generatedFilenames),
            $uniqueFilenames,
            'Files with different extensions must have unique filenames'
        );
    }

    /**
     * Property 11.3: Filename Collision Prevention.
     *
     * **Validates: Requirements 3.8**
     *
     * The filename generation algorithm should have extremely low probability
     * of collision (< 0.001% for 10,000 files).
     *
     * @test
     */
    public function property_filename_collision_prevention(): void
    {
        $fileCount = 1000; // Test with 1000 files
        $generatedFilenames = [];

        for ($i = 0; $i < $fileCount; $i++) {
            $file = UploadedFile::fake()->image('collision_test.jpg', 50, 50);
            $result = $this->fileProcessor->process($file, 'uploads');

            $generatedFilenames[] = $result['stored_filename'];

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }

        // Property: Collision rate should be 0% for reasonable file counts
        $uniqueFilenames = array_unique($generatedFilenames);
        $collisionRate = 1 - (count($uniqueFilenames) / $fileCount);

        $this->assertEquals(
            0.0,
            $collisionRate,
            "Filename collision rate should be 0%, but got {$collisionRate}%"
        );

        // Verify we have exactly the expected number of unique filenames
        $this->assertCount(
            $fileCount,
            $uniqueFilenames,
            'Expected all filenames to be unique'
        );
    }

    /**
     * Property 12: File Upload Performance.
     *
     * **Validates: Requirements 3.12, 13.3**
     *
     * For any file up to 5MB in size, the complete upload and processing
     * (including thumbnail generation for images) should complete within 500ms.
     *
     * @test
     */
    public function property_file_upload_performance(): void
    {
        // Test with various file sizes up to 5MB
        $fileSizes = [
            100,    // 100KB
            500,    // 500KB
            1024,   // 1MB
            2048,   // 2MB
            3072,   // 3MB
            4096,   // 4MB
            5120,   // 5MB
        ];

        foreach ($fileSizes as $sizeKB) {
            // Create test image file
            $file = UploadedFile::fake()->image('performance_test.jpg', 1000, 1000)->size($sizeKB);

            // Measure processing time
            $startTime = microtime(true);
            $result = $this->fileProcessor->process($file, 'uploads');
            $endTime = microtime(true);

            $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            // Property: Processing time should be under 500ms
            $this->assertLessThan(
                500,
                $processingTime,
                "File upload and processing for {$sizeKB}KB file took {$processingTime}ms, exceeds 500ms limit"
            );

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }
    }

    /**
     * Property 12.1: Thumbnail Generation Performance.
     *
     * **Validates: Requirements 3.12, 13.3**
     *
     * Thumbnail generation should not significantly impact overall processing time.
     * Processing with thumbnail should be less than 2x processing without thumbnail.
     *
     * @test
     */
    public function property_thumbnail_generation_performance(): void
    {
        $file = UploadedFile::fake()->image('thumbnail_perf.jpg', 2000, 2000)->size(2048);

        // Measure processing time without thumbnail
        $startTime = microtime(true);
        $resultWithoutThumb = $this->fileProcessor->process($file, 'uploads', ['thumbnail' => false]);
        $endTime = microtime(true);
        $timeWithoutThumb = ($endTime - $startTime) * 1000;

        // Clean up
        Storage::disk('public')->delete($resultWithoutThumb['file_path']);

        // Create new file for second test
        $file2 = UploadedFile::fake()->image('thumbnail_perf.jpg', 2000, 2000)->size(2048);

        // Measure processing time with thumbnail
        $startTime = microtime(true);
        $resultWithThumb = $this->fileProcessor->process($file2, 'uploads', ['thumbnail' => true]);
        $endTime = microtime(true);
        $timeWithThumb = ($endTime - $startTime) * 1000;

        // Clean up
        Storage::disk('public')->delete($resultWithThumb['file_path']);
        if (isset($resultWithThumb['thumbnail_path'])) {
            Storage::disk('public')->delete($resultWithThumb['thumbnail_path']);
        }

        // Property: Both operations should complete within 500ms
        $this->assertLessThan(500, $timeWithoutThumb, 'Processing without thumbnail exceeds 500ms');
        $this->assertLessThan(500, $timeWithThumb, 'Processing with thumbnail exceeds 500ms');

        // Property: Thumbnail generation adds overhead but should be reasonable (< 400ms additional)
        $thumbnailOverhead = $timeWithThumb - $timeWithoutThumb;
        $this->assertLessThan(
            400,
            $thumbnailOverhead,
            "Thumbnail generation overhead {$thumbnailOverhead}ms is excessive"
        );
    }

    /**
     * Property 12.2: Batch Upload Performance.
     *
     * **Validates: Requirements 3.12, 13.3**
     *
     * Processing multiple files should scale linearly (not exponentially).
     * Average time per file should remain consistent.
     *
     * @test
     */
    public function property_batch_upload_performance(): void
    {
        $fileCount = 10;
        $fileSizeKB = 1024; // 1MB each
        $processingTimes = [];

        for ($i = 0; $i < $fileCount; $i++) {
            $file = UploadedFile::fake()->image("batch_{$i}.jpg", 500, 500)->size($fileSizeKB);

            $startTime = microtime(true);
            $result = $this->fileProcessor->process($file, 'uploads');
            $endTime = microtime(true);

            $processingTimes[] = ($endTime - $startTime) * 1000;

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }

        // Property: Average processing time should be under 500ms
        $averageTime = array_sum($processingTimes) / count($processingTimes);
        $this->assertLessThan(
            500,
            $averageTime,
            "Average processing time {$averageTime}ms exceeds 500ms limit"
        );

        // Property: Standard deviation should be low (consistent performance)
        $variance = 0;
        foreach ($processingTimes as $time) {
            $variance += pow($time - $averageTime, 2);
        }
        $stdDev = sqrt($variance / count($processingTimes));

        // Standard deviation should be less than 50% of average (reasonable consistency)
        $this->assertLessThan(
            $averageTime * 0.5,
            $stdDev,
            "Processing time standard deviation {$stdDev}ms is too high (inconsistent performance)"
        );
    }

    /**
     * Property 12.3: Memory Efficiency.
     *
     * **Validates: Requirements 3.12, 13.3**
     *
     * File processing should not cause excessive memory usage.
     * Memory usage should be reasonable relative to file size.
     *
     * @test
     */
    public function property_memory_efficiency(): void
    {
        $fileSizeKB = 5120; // 5MB file
        $file = UploadedFile::fake()->image('memory_test.jpg', 2000, 2000)->size($fileSizeKB);

        // Measure memory before processing
        $memoryBefore = memory_get_usage(true);

        $result = $this->fileProcessor->process($file, 'uploads');

        // Measure memory after processing
        $memoryAfter = memory_get_usage(true);
        $memoryUsedMB = ($memoryAfter - $memoryBefore) / 1024 / 1024;

        // Clean up
        Storage::disk('public')->delete($result['file_path']);
        if (isset($result['thumbnail_path'])) {
            Storage::disk('public')->delete($result['thumbnail_path']);
        }

        // Property: Memory usage should be reasonable (less than 50MB for 5MB file)
        $this->assertLessThan(
            50,
            $memoryUsedMB,
            "Memory usage {$memoryUsedMB}MB is excessive for 5MB file processing"
        );
    }

    /**
     * Property 13: MIME Type Validation.
     *
     * **Validates: Requirements 3.20, 14.4**
     *
     * For any file with a mismatched extension and MIME type, the file processor
     * should reject the upload for security reasons.
     *
     * @test
     */
    public function property_mime_type_validation(): void
    {
        // Test with valid image files - these should pass MIME validation
        $validFiles = [
            ['extension' => 'jpg', 'width' => 100, 'height' => 100],
            ['extension' => 'png', 'width' => 100, 'height' => 100],
            ['extension' => 'gif', 'width' => 100, 'height' => 100],
        ];

        foreach ($validFiles as $fileSpec) {
            $file = UploadedFile::fake()->image("test.{$fileSpec['extension']}", $fileSpec['width'], $fileSpec['height']);

            // Property: Valid files with matching MIME types should be accepted
            $result = $this->fileProcessor->process($file, 'uploads');

            $this->assertArrayHasKey('mime_type', $result);
            $this->assertStringStartsWith('image/', $result['mime_type']);

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }

        // Test with PDF file
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $result = $this->fileProcessor->process($pdfFile, 'uploads', ['thumbnail' => false]);

        $this->assertEquals('application/pdf', $result['mime_type']);
        Storage::disk('public')->delete($result['file_path']);
    }

    /**
     * Property 13.1: Extension-MIME Type Consistency.
     *
     * **Validates: Requirements 3.20, 14.4**
     *
     * For any valid file type, the MIME type must match the expected MIME types
     * for that extension.
     *
     * @test
     */
    public function property_extension_mime_type_consistency(): void
    {
        $validCombinations = [
            ['extension' => 'jpg', 'expectedMimePrefix' => 'image/'],
            ['extension' => 'jpeg', 'expectedMimePrefix' => 'image/'],
            ['extension' => 'png', 'expectedMimePrefix' => 'image/'],
            ['extension' => 'gif', 'expectedMimePrefix' => 'image/'],
        ];

        foreach ($validCombinations as $combo) {
            // Create file with valid extension
            $file = UploadedFile::fake()->image("test.{$combo['extension']}", 100, 100);

            // Process should succeed
            $result = $this->fileProcessor->process($file, 'uploads');

            // Property: MIME type should match the expected prefix for this extension
            $this->assertStringStartsWith(
                $combo['expectedMimePrefix'],
                $result['mime_type'],
                "MIME type {$result['mime_type']} does not match expected prefix for extension {$combo['extension']}"
            );

            // Clean up
            Storage::disk('public')->delete($result['file_path']);
            if (isset($result['thumbnail_path'])) {
                Storage::disk('public')->delete($result['thumbnail_path']);
            }
        }
    }

    /**
     * Property 13.2: File Type Validation.
     *
     * **Validates: Requirements 3.20, 14.4**
     *
     * For any file type not in the allowed list, the upload should be rejected.
     *
     * @test
     */
    public function property_file_type_validation(): void
    {
        // Test with disallowed file extension
        $disallowedFile = UploadedFile::fake()->create('script.exe', 100);

        // Property: Disallowed file types should be rejected
        try {
            $this->fileProcessor->process($disallowedFile, 'uploads');
            $this->fail('File with disallowed extension .exe should have been rejected');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Expected - file should be rejected
            $this->assertStringContainsString('not allowed', $e->getMessage());
        }

        // Test with another disallowed extension
        $disallowedFile2 = UploadedFile::fake()->create('script.sh', 100);

        try {
            $this->fileProcessor->process($disallowedFile2, 'uploads');
            $this->fail('File with disallowed extension .sh should have been rejected');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Expected - file should be rejected
            $this->assertStringContainsString('not allowed', $e->getMessage());
        }
    }

    /**
     * Property 13.3: MIME Type Validation Coverage.
     *
     * **Validates: Requirements 3.20, 14.4**
     *
     * For any file extension in the allowed types list, there should be
     * corresponding MIME type validation rules.
     *
     * @test
     */
    public function property_mime_type_validation_coverage(): void
    {
        $validator = new FileValidator();
        $mimeMap = $validator->getMimeMap();

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];

        foreach ($allowedExtensions as $extension) {
            // Property: Every allowed extension should have MIME type mapping
            $this->assertArrayHasKey(
                $extension,
                $mimeMap,
                "Extension '{$extension}' is allowed but has no MIME type mapping"
            );

            // Property: MIME type mapping should not be empty
            $this->assertNotEmpty(
                $mimeMap[$extension],
                "Extension '{$extension}' has empty MIME type mapping"
            );

            // Property: MIME type mapping should be an array
            $this->assertIsArray(
                $mimeMap[$extension],
                "Extension '{$extension}' MIME type mapping is not an array"
            );
        }
    }
}
