<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Property Test 9: File Type Validation.
 *
 * **Validates: Requirements 3.2, 14.4**
 *
 * Property: For all uploaded files, the validator must correctly validate
 * file extensions and MIME types to prevent malicious uploads.
 *
 * This property test ensures that:
 * 1. Files with allowed extensions pass validation
 * 2. Files with disallowed extensions fail validation
 * 3. Files with mismatched MIME types fail validation (security)
 * 4. Validation messages are descriptive and helpful
 */
class FileTypeValidationPropertyTest extends TestCase
{
    protected FileValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FileValidator();
    }

    /**
     * Property: Files with allowed extensions pass validation.
     *
     * @test
     */
    public function property_files_with_allowed_extensions_pass_validation(): void
    {
        // Arrange: Test all common allowed file types
        $allowedTypes = [
            ['extension' => 'jpg', 'mime' => 'image/jpeg'],
            ['extension' => 'jpeg', 'mime' => 'image/jpeg'],
            ['extension' => 'png', 'mime' => 'image/png'],
            ['extension' => 'gif', 'mime' => 'image/gif'],
            ['extension' => 'pdf', 'mime' => 'application/pdf'],
            ['extension' => 'doc', 'mime' => 'application/msword'],
            ['extension' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        foreach ($allowedTypes as $fileType) {
            // Create a fake file with correct extension and MIME type
            $file = UploadedFile::fake()->create(
                "test.{$fileType['extension']}",
                100, // 100 KB
                $fileType['mime']
            );

            $options = [
                'max_size' => 5120, // 5MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            ];

            // Act & Assert: Validation should pass without throwing exception
            try {
                $this->validator->validate($file, $options);
                $this->assertTrue(true, "File with extension .{$fileType['extension']} passed validation");
            } catch (ValidationException $e) {
                $this->fail("File with allowed extension .{$fileType['extension']} failed validation: " . $e->getMessage());
            }
        }
    }

    /**
     * Property: Files with disallowed extensions fail validation.
     *
     * @test
     */
    public function property_files_with_disallowed_extensions_fail_validation(): void
    {
        // Arrange: Test disallowed file types
        $disallowedTypes = ['exe', 'bat', 'sh', 'php', 'js', 'html'];

        foreach ($disallowedTypes as $extension) {
            $file = UploadedFile::fake()->create(
                "malicious.{$extension}",
                100,
                'application/octet-stream'
            );

            $options = [
                'max_size' => 5120,
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            ];

            // Act & Assert: Validation should fail
            try {
                $this->validator->validate($file, $options);
                $this->fail("File with disallowed extension .{$extension} should have failed validation");
            } catch (ValidationException $e) {
                $errors = $e->errors();
                $this->assertArrayHasKey('file', $errors);
                $this->assertStringContainsString(
                    "File type .{$extension} is not allowed",
                    $errors['file'][0],
                    'Error message should mention disallowed file type'
                );
            }
        }
    }

    /**
     * Property: Files with mismatched MIME types fail validation (security).
     *
     * @test
     */
    public function property_files_with_mismatched_mime_types_fail_validation(): void
    {
        // Arrange: Test files with extension/MIME type mismatch
        $mismatchedFiles = [
            ['extension' => 'jpg', 'mime' => 'application/pdf', 'description' => 'PDF disguised as JPG'],
            ['extension' => 'png', 'mime' => 'text/html', 'description' => 'HTML disguised as PNG'],
            ['extension' => 'pdf', 'mime' => 'image/jpeg', 'description' => 'JPEG disguised as PDF'],
            ['extension' => 'docx', 'mime' => 'application/zip', 'description' => 'ZIP disguised as DOCX'],
        ];

        foreach ($mismatchedFiles as $fileInfo) {
            $file = UploadedFile::fake()->create(
                "suspicious.{$fileInfo['extension']}",
                100,
                $fileInfo['mime']
            );

            $options = [
                'max_size' => 5120,
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            ];

            // Act & Assert: Validation should fail due to MIME type mismatch
            try {
                $this->validator->validate($file, $options);
                $this->fail("{$fileInfo['description']} should have failed MIME type validation");
            } catch (ValidationException $e) {
                $errors = $e->errors();
                $this->assertArrayHasKey('file', $errors);
                $this->assertStringContainsString(
                    'MIME type',
                    $errors['file'][0],
                    "Error message should mention MIME type mismatch for {$fileInfo['description']}"
                );
            }
        }
    }

    /**
     * Property: File size validation works correctly for all file types.
     *
     * @test
     */
    public function property_file_size_validation_works_for_all_types(): void
    {
        // Arrange: Test files exceeding size limit
        $fileTypes = [
            ['extension' => 'jpg', 'mime' => 'image/jpeg'],
            ['extension' => 'pdf', 'mime' => 'application/pdf'],
            ['extension' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        foreach ($fileTypes as $fileType) {
            // Create file larger than max size (6MB > 5MB limit)
            $file = UploadedFile::fake()->create(
                "large.{$fileType['extension']}",
                6144, // 6MB in KB
                $fileType['mime']
            );

            $options = [
                'max_size' => 5120, // 5MB limit
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            ];

            // Act & Assert: Validation should fail due to size
            try {
                $this->validator->validate($file, $options);
                $this->fail('File with size 6MB should have failed validation (limit: 5MB)');
            } catch (ValidationException $e) {
                $errors = $e->errors();
                $this->assertArrayHasKey('file', $errors);
                $this->assertStringContainsString(
                    'exceeds maximum allowed size',
                    $errors['file'][0],
                    'Error message should mention size limit'
                );
            }
        }
    }

    /**
     * Property: Validation error messages are descriptive and helpful.
     *
     * @test
     */
    public function property_validation_error_messages_are_descriptive(): void
    {
        // Test 1: Extension error message includes allowed types
        $file1 = UploadedFile::fake()->create('test.exe', 100, 'application/octet-stream');
        $options1 = [
            'max_size' => 5120,
            'allowed_types' => ['jpg', 'png', 'pdf'],
        ];

        try {
            $this->validator->validate($file1, $options1);
            $this->fail('Should have thrown validation exception');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertStringContainsString('Allowed types: jpg, png, pdf', $errors['file'][0]);
        }

        // Test 2: Size error message includes actual and max size
        $file2 = UploadedFile::fake()->create('large.jpg', 6144, 'image/jpeg');
        $options2 = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        try {
            $this->validator->validate($file2, $options2);
            $this->fail('Should have thrown validation exception');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertStringContainsString('MB', $errors['file'][0]);
            $this->assertStringContainsString('exceeds', $errors['file'][0]);
        }

        // Test 3: MIME type error message mentions security concern
        $file3 = UploadedFile::fake()->create('fake.jpg', 100, 'application/pdf');
        $options3 = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        try {
            $this->validator->validate($file3, $options3);
            $this->fail('Should have thrown validation exception');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertStringContainsString('MIME type', $errors['file'][0]);
            $this->assertStringContainsString('does not match', $errors['file'][0]);
        }
    }

    /**
     * Property: Validator handles edge cases correctly.
     *
     * @test
     */
    public function property_validator_handles_edge_cases(): void
    {
        // Test 1: Empty allowed_types array (should allow all extensions)
        $file1 = UploadedFile::fake()->create('test.xyz', 100, 'application/octet-stream');
        $options1 = [
            'max_size' => 5120,
            'allowed_types' => [],
        ];

        try {
            $this->validator->validate($file1, $options1);
            $this->assertTrue(true, 'Empty allowed_types should allow any extension');
        } catch (ValidationException $e) {
            $this->fail('Empty allowed_types should not throw exception for extension');
        }

        // Test 2: File exactly at size limit (should pass)
        $file2 = UploadedFile::fake()->create('exact.jpg', 5120, 'image/jpeg');
        $options2 = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        try {
            $this->validator->validate($file2, $options2);
            $this->assertTrue(true, 'File exactly at size limit should pass');
        } catch (ValidationException $e) {
            $this->fail('File at exact size limit should not throw exception');
        }

        // Test 3: File with uppercase extension (should be case-insensitive)
        $file3 = UploadedFile::fake()->create('test.JPG', 100, 'image/jpeg');
        $options3 = [
            'max_size' => 5120,
            'allowed_types' => ['jpg'],
        ];

        try {
            $this->validator->validate($file3, $options3);
            $this->assertTrue(true, 'Uppercase extension should be handled case-insensitively');
        } catch (ValidationException $e) {
            $this->fail('Uppercase extension should not throw exception: ' . $e->getMessage());
        }
    }

    /**
     * Property: MIME type validation is comprehensive for security.
     *
     * @test
     */
    public function property_mime_type_validation_is_comprehensive(): void
    {
        // Arrange: Get all MIME mappings from validator
        $mimeMap = $this->validator->getMimeMap();

        // Assert: All common file types have MIME mappings
        $requiredExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        foreach ($requiredExtensions as $extension) {
            $this->assertArrayHasKey(
                $extension,
                $mimeMap,
                "MIME map should include {$extension}"
            );
            $this->assertIsArray($mimeMap[$extension]);
            $this->assertNotEmpty($mimeMap[$extension]);
        }

        // Test: Each mapped extension validates correctly
        foreach ($mimeMap as $extension => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $file = UploadedFile::fake()->create(
                    "test.{$extension}",
                    100,
                    $mimeType
                );

                $options = [
                    'max_size' => 5120,
                    'allowed_types' => [$extension],
                ];

                try {
                    $this->validator->validate($file, $options);
                    $this->assertTrue(
                        true,
                        "File .{$extension} with MIME {$mimeType} should pass validation"
                    );
                } catch (ValidationException $e) {
                    $this->fail(
                        "File .{$extension} with valid MIME {$mimeType} failed: " . $e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * Property: Validator can be extended with custom MIME mappings.
     *
     * @test
     */
    public function property_validator_supports_custom_mime_mappings(): void
    {
        // Arrange: Add custom MIME mapping
        $this->validator->addMimeMapping('custom', ['application/x-custom']);

        // Act: Create file with custom extension
        $file = UploadedFile::fake()->create('test.custom', 100, 'application/x-custom');
        $options = [
            'max_size' => 5120,
            'allowed_types' => ['custom'],
        ];

        // Assert: Validation should pass with custom mapping
        try {
            $this->validator->validate($file, $options);
            $this->assertTrue(true, 'Custom MIME mapping should work');
        } catch (ValidationException $e) {
            $this->fail('Custom MIME mapping failed: ' . $e->getMessage());
        }

        // Assert: Mismatched MIME should still fail
        $file2 = UploadedFile::fake()->create('test.custom', 100, 'application/pdf');

        try {
            $this->validator->validate($file2, $options);
            $this->fail('Mismatched MIME should fail even with custom mapping');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('MIME type', $e->errors()['file'][0]);
        }
    }
}
