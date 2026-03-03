<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Unit Tests for FileValidator.
 *
 * Tests Requirements:
 * - 3.2: Validate file type and size
 * - 3.10: Validate against maximum file size
 * - 3.20: Validate MIME types in addition to extensions
 */
class FileValidatorTest extends TestCase
{
    protected FileValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FileValidator();
    }

    /**
     * Test file size validation passes for files within limit.
     *
     * @test
     */
    public function test_file_size_validation_passes_for_files_within_limit(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.jpg', 1024, 'image/jpeg'); // 1MB
        $options = [
            'max_size' => 5120, // 5MB
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'Validation should pass for file within size limit');
    }

    /**
     * Test file size validation fails for files exceeding limit.
     *
     * @test
     */
    public function test_file_size_validation_fails_for_files_exceeding_limit(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('large.jpg', 6144, 'image/jpeg'); // 6MB
        $options = [
            'max_size' => 5120, // 5MB
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($file, $options);
    }

    /**
     * Test file size validation error message includes size information.
     *
     * @test
     */
    public function test_file_size_validation_error_message_includes_size_info(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('large.jpg', 6144, 'image/jpeg'); // 6MB
        $options = [
            'max_size' => 5120, // 5MB
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        try {
            $this->validator->validate($file, $options);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('file', $errors);
            $this->assertStringContainsString('exceeds maximum allowed size', $errors['file'][0]);
            $this->assertStringContainsString('MB', $errors['file'][0]);
        }
    }

    /**
     * Test file extension validation passes for allowed types.
     *
     * @test
     */
    public function test_file_extension_validation_passes_for_allowed_types(): void
    {
        // Arrange
        $allowedTypes = ['jpg', 'png', 'pdf'];

        foreach ($allowedTypes as $type) {
            $mimeType = match ($type) {
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
            };

            $file = UploadedFile::fake()->create("test.{$type}", 100, $mimeType);
            $options = [
                'max_size' => 5120,
                'allowed_types' => $allowedTypes,
            ];

            // Act & Assert
            $this->validator->validate($file, $options);
            $this->assertTrue(true, "Validation should pass for allowed type .{$type}");
        }
    }

    /**
     * Test file extension validation fails for disallowed types.
     *
     * @test
     */
    public function test_file_extension_validation_fails_for_disallowed_types(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/octet-stream');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg', 'png', 'pdf'],
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($file, $options);
    }

    /**
     * Test file extension validation error message includes allowed types.
     *
     * @test
     */
    public function test_file_extension_validation_error_message_includes_allowed_types(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/octet-stream');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg', 'png', 'pdf'],
        ];

        // Act & Assert
        try {
            $this->validator->validate($file, $options);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('file', $errors);
            $this->assertStringContainsString('File type .exe is not allowed', $errors['file'][0]);
            $this->assertStringContainsString('Allowed types: jpg, png, pdf', $errors['file'][0]);
        }
    }

    /**
     * Test file extension validation is case-insensitive.
     *
     * @test
     */
    public function test_file_extension_validation_is_case_insensitive(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.JPG', 100, 'image/jpeg');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'Validation should be case-insensitive for extensions');
    }

    /**
     * Test MIME type validation passes for matching types.
     *
     * @test
     */
    public function test_mime_type_validation_passes_for_matching_types(): void
    {
        // Arrange
        $validFiles = [
            ['extension' => 'jpg', 'mime' => 'image/jpeg'],
            ['extension' => 'png', 'mime' => 'image/png'],
            ['extension' => 'pdf', 'mime' => 'application/pdf'],
        ];

        foreach ($validFiles as $fileInfo) {
            $file = UploadedFile::fake()->create(
                "test.{$fileInfo['extension']}",
                100,
                $fileInfo['mime']
            );

            $options = [
                'max_size' => 5120,
                'allowed_types' => [$fileInfo['extension']],
            ];

            // Act & Assert
            $this->validator->validate($file, $options);
            $this->assertTrue(
                true,
                "Validation should pass for .{$fileInfo['extension']} with MIME {$fileInfo['mime']}"
            );
        }
    }

    /**
     * Test MIME type validation fails for mismatched types.
     *
     * @test
     */
    public function test_mime_type_validation_fails_for_mismatched_types(): void
    {
        // Arrange: PDF file disguised as JPG
        $file = UploadedFile::fake()->create('fake.jpg', 100, 'application/pdf');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($file, $options);
    }

    /**
     * Test MIME type validation error message mentions security concern.
     *
     * @test
     */
    public function test_mime_type_validation_error_message_mentions_security(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('suspicious.jpg', 100, 'application/pdf');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        try {
            $this->validator->validate($file, $options);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('file', $errors);
            $this->assertStringContainsString('MIME type', $errors['file'][0]);
            $this->assertStringContainsString('does not match', $errors['file'][0]);
        }
    }

    /**
     * Test MIME type validation skips unknown extensions.
     *
     * @test
     */
    public function test_mime_type_validation_skips_unknown_extensions(): void
    {
        // Arrange: Unknown extension should skip MIME validation
        $file = UploadedFile::fake()->create('test.xyz', 100, 'application/octet-stream');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['xyz'],
        ];

        // Act & Assert: Should pass because MIME validation is skipped for unknown extensions
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'MIME validation should be skipped for unknown extensions');
    }

    /**
     * Test validation passes when allowed_types is empty.
     *
     * @test
     */
    public function test_validation_passes_when_allowed_types_is_empty(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.xyz', 100, 'application/octet-stream');
        $options = [
            'max_size' => 5120,
            'allowed_types' => [],
        ];

        // Act & Assert
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'Empty allowed_types should allow any extension');
    }

    /**
     * Test getMimeMap returns expected mappings.
     *
     * @test
     */
    public function test_get_mime_map_returns_expected_mappings(): void
    {
        // Act
        $mimeMap = $this->validator->getMimeMap();

        // Assert
        $this->assertIsArray($mimeMap);
        $this->assertArrayHasKey('jpg', $mimeMap);
        $this->assertArrayHasKey('png', $mimeMap);
        $this->assertArrayHasKey('pdf', $mimeMap);
        $this->assertArrayHasKey('doc', $mimeMap);
        $this->assertArrayHasKey('docx', $mimeMap);

        // Check specific mappings
        $this->assertContains('image/jpeg', $mimeMap['jpg']);
        $this->assertContains('image/png', $mimeMap['png']);
        $this->assertContains('application/pdf', $mimeMap['pdf']);
    }

    /**
     * Test addMimeMapping adds custom mapping.
     *
     * @test
     */
    public function test_add_mime_mapping_adds_custom_mapping(): void
    {
        // Arrange
        $this->validator->addMimeMapping('custom', ['application/x-custom']);

        // Act
        $mimeMap = $this->validator->getMimeMap();

        // Assert
        $this->assertArrayHasKey('custom', $mimeMap);
        $this->assertContains('application/x-custom', $mimeMap['custom']);
    }

    /**
     * Test custom MIME mapping works in validation.
     *
     * @test
     */
    public function test_custom_mime_mapping_works_in_validation(): void
    {
        // Arrange
        $this->validator->addMimeMapping('custom', ['application/x-custom']);
        $file = UploadedFile::fake()->create('test.custom', 100, 'application/x-custom');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['custom'],
        ];

        // Act & Assert
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'Custom MIME mapping should work in validation');
    }

    /**
     * Test validation with multiple MIME types for same extension.
     *
     * @test
     */
    public function test_validation_with_multiple_mime_types_for_same_extension(): void
    {
        // Arrange: JPG can have multiple MIME types
        $mimeTypes = ['image/jpeg', 'image/jpg'];

        foreach ($mimeTypes as $mimeType) {
            $file = UploadedFile::fake()->create('test.jpg', 100, $mimeType);
            $options = [
                'max_size' => 5120,
                'allowed_types' => ['jpg'],
            ];

            // Act & Assert
            $this->validator->validate($file, $options);
            $this->assertTrue(true, "Validation should pass for MIME type {$mimeType}");
        }
    }

    /**
     * Test validation handles file at exact size limit.
     *
     * @test
     */
    public function test_validation_handles_file_at_exact_size_limit(): void
    {
        // Arrange: File exactly at 5MB limit
        $file = UploadedFile::fake()->create('exact.jpg', 5120, 'image/jpeg');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'File at exact size limit should pass validation');
    }

    /**
     * Test validation fails for file one byte over limit.
     *
     * @test
     */
    public function test_validation_fails_for_file_one_byte_over_limit(): void
    {
        // Arrange: File 1 byte over 5MB limit
        $file = UploadedFile::fake()->create('over.jpg', 5121, 'image/jpeg');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->validator->validate($file, $options);
    }

    /**
     * Test validation with default max_size when not specified.
     *
     * @test
     */
    public function test_validation_with_default_max_size_when_not_specified(): void
    {
        // Arrange: No max_size specified, should use default 5120KB
        $file = UploadedFile::fake()->create('test.jpg', 1024, 'image/jpeg');
        $options = [
            'allowed_types' => ['jpg'],
        ];

        // Act & Assert
        $this->validator->validate($file, $options);
        $this->assertTrue(true, 'Should use default max_size when not specified');
    }

    /**
     * Test validation with all common document types.
     *
     * @test
     */
    public function test_validation_with_all_common_document_types(): void
    {
        // Arrange
        $documentTypes = [
            ['extension' => 'doc', 'mime' => 'application/msword'],
            ['extension' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['extension' => 'xls', 'mime' => 'application/vnd.ms-excel'],
            ['extension' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ['extension' => 'txt', 'mime' => 'text/plain'],
            ['extension' => 'csv', 'mime' => 'text/csv'],
        ];

        foreach ($documentTypes as $docType) {
            $file = UploadedFile::fake()->create(
                "document.{$docType['extension']}",
                100,
                $docType['mime']
            );

            $options = [
                'max_size' => 5120,
                'allowed_types' => [$docType['extension']],
            ];

            // Act & Assert
            $this->validator->validate($file, $options);
            $this->assertTrue(
                true,
                "Validation should pass for .{$docType['extension']} documents"
            );
        }
    }
}
