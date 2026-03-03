<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\FileUpload;

use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileProcessor;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Security Tests for File Upload.
 *
 * Tests Requirements:
 * - 3.2: Validate uploaded file against allowed file types
 * - 3.10: Validate file size limits
 * - 3.20: Validate MIME types in addition to file extensions
 * - 14.4: Reject malicious files
 */
class FileUploadSecurityTest extends TestCase
{
    protected FileProcessor $processor;

    protected FileValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->validator = new FileValidator();
        $thumbnailGenerator = new ThumbnailGenerator();
        $this->processor = new FileProcessor($this->validator, $thumbnailGenerator);
    }

    /**
     * Test file type validation rejects executable files.
     *
     * @test
     */
    public function test_file_type_validation_rejects_executable_files(): void
    {
        // Arrange
        $maliciousFiles = [
            ['name' => 'malware.exe', 'mime' => 'application/octet-stream'],
            ['name' => 'script.bat', 'mime' => 'application/x-bat'],
            ['name' => 'shell.sh', 'mime' => 'application/x-sh'],
            ['name' => 'command.cmd', 'mime' => 'application/x-cmd'],
        ];

        foreach ($maliciousFiles as $fileInfo) {
            // Act & Assert
            $file = UploadedFile::fake()->create($fileInfo['name'], 100, $fileInfo['mime']);

            $this->expectException(ValidationException::class);
            $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg', 'png', 'pdf'],
            ]);
        }
    }

    /**
     * Test file type validation rejects script files.
     *
     * @test
     */
    public function test_file_type_validation_rejects_script_files(): void
    {
        // Arrange
        $scriptFiles = [
            ['name' => 'malicious.php', 'mime' => 'application/x-php'],
            ['name' => 'script.js', 'mime' => 'application/javascript'],
            ['name' => 'code.py', 'mime' => 'text/x-python'],
            ['name' => 'shell.pl', 'mime' => 'text/x-perl'],
        ];

        foreach ($scriptFiles as $fileInfo) {
            // Act & Assert
            $file = UploadedFile::fake()->create($fileInfo['name'], 100, $fileInfo['mime']);

            $this->expectException(ValidationException::class);
            $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg', 'png', 'pdf'],
            ]);
        }
    }

    /**
     * Test MIME type validation prevents file type spoofing.
     *
     * @test
     */
    public function test_mime_type_validation_prevents_file_type_spoofing(): void
    {
        // Arrange - Executable disguised as image
        $file = UploadedFile::fake()->create('fake.jpg', 100, 'application/octet-stream');

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('MIME type');

        $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg'],
        ]);
    }

    /**
     * Test MIME type validation detects PDF disguised as image.
     *
     * @test
     */
    public function test_mime_type_validation_detects_pdf_disguised_as_image(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('fake.jpg', 100, 'application/pdf');

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not match');

        $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg'],
        ]);
    }

    /**
     * Test file size limit prevents denial of service attacks.
     *
     * @test
     */
    public function test_file_size_limit_prevents_denial_of_service_attacks(): void
    {
        // Arrange - Very large file
        $file = UploadedFile::fake()->create('huge.jpg', 102400, 'image/jpeg'); // 100MB

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');

        $this->processor->process($file, 'uploads', [
            'max_size' => 5120, // 5MB limit
            'allowed_types' => ['jpg'],
        ]);
    }

    /**
     * Test file size validation with various attack sizes.
     *
     * @test
     */
    public function test_file_size_validation_with_various_attack_sizes(): void
    {
        // Arrange
        $attackSizes = [
            10240,  // 10MB
            51200,  // 50MB
            102400, // 100MB
        ];

        foreach ($attackSizes as $size) {
            // Act & Assert
            $file = UploadedFile::fake()->create('large.jpg', $size, 'image/jpeg');

            $this->expectException(ValidationException::class);
            $this->processor->process($file, 'uploads', [
                'max_size' => 5120,
                'allowed_types' => ['jpg'],
            ]);
        }
    }

    /**
     * Test malicious filename with path traversal attempt.
     *
     * @test
     */
    public function test_malicious_filename_with_path_traversal_attempt(): void
    {
        // Arrange - Filename with path traversal
        $file = UploadedFile::fake()->image('../../../etc/passwd.jpg');

        // Act
        $result = $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg'],
        ]);

        // Assert - Stored filename is safe (no path traversal)
        $this->assertStringNotContainsString('..', $result['stored_filename']);
        $this->assertStringNotContainsString('/', $result['stored_filename']);
        $this->assertMatchesRegularExpression('/^\d+_[a-f0-9]+\.jpg$/', $result['stored_filename']);

        // Assert - File is stored in correct location
        Storage::disk('public')->assertExists($result['file_path']);
        $this->assertStringStartsWith('uploads/', $result['file_path']);
    }

    /**
     * Test malicious filename with null bytes.
     *
     * @test
     */
    public function test_malicious_filename_with_null_bytes(): void
    {
        // Arrange - Filename with null byte
        $file = UploadedFile::fake()->image("malicious\x00.jpg");

        // Act
        $result = $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg'],
        ]);

        // Assert - Stored filename is safe
        $this->assertStringNotContainsString("\x00", $result['stored_filename']);
        $this->assertMatchesRegularExpression('/^\d+_[a-f0-9]+\.jpg$/', $result['stored_filename']);
    }

    /**
     * Test double extension attack.
     *
     * @test
     */
    public function test_double_extension_attack(): void
    {
        // Arrange - File with double extension (use image to avoid thumbnail generation error)
        $file = UploadedFile::fake()->image('malicious.php.jpg');

        // Act
        $result = $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg'],
        ]);

        // Assert - Only last extension is used
        $this->assertStringEndsWith('.jpg', $result['stored_filename']);
        $this->assertStringNotContainsString('.php', $result['stored_filename']);
    }

    /**
     * Test validation error messages don't leak sensitive information.
     *
     * @test
     */
    public function test_validation_error_messages_dont_leak_sensitive_information(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/octet-stream');

        // Act & Assert
        try {
            $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg', 'png'],
            ]);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $message = $errors['file'][0];

            // Assert - Error message is informative but not revealing system details
            $this->assertStringNotContainsString('/', $message);
            $this->assertStringNotContainsString('\\', $message);
            $this->assertStringNotContainsString('storage', $message);
            $this->assertStringNotContainsString('path', $message);
        }
    }

    /**
     * Test MIME type whitelist approach.
     *
     * @test
     */
    public function test_mime_type_whitelist_approach(): void
    {
        // Arrange - Only specific MIME types should be allowed
        $allowedFiles = [
            ['name' => 'photo.jpg', 'mime' => 'image/jpeg', 'ext' => 'jpg', 'isImage' => true],
            ['name' => 'graphic.png', 'mime' => 'image/png', 'ext' => 'png', 'isImage' => true],
            ['name' => 'document.pdf', 'mime' => 'application/pdf', 'ext' => 'pdf', 'isImage' => false],
        ];

        foreach ($allowedFiles as $fileInfo) {
            // Act
            if ($fileInfo['isImage']) {
                $file = UploadedFile::fake()->image($fileInfo['name']);
            } else {
                $file = UploadedFile::fake()->create($fileInfo['name'], 100, $fileInfo['mime']);
            }

            $result = $this->processor->process($file, 'uploads', [
                'allowed_types' => [$fileInfo['ext']],
                'thumbnail' => $fileInfo['isImage'], // Only create thumbnail for images
            ]);

            // Assert
            $this->assertIsArray($result);
            Storage::disk('public')->assertExists($result['file_path']);
        }
    }

    /**
     * Test rejection of files with no extension.
     *
     * @test
     */
    public function test_rejection_of_files_with_no_extension(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('noextension', 100, 'application/octet-stream');

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg', 'png', 'pdf'],
        ]);
    }

    /**
     * Test case-insensitive extension validation.
     *
     * @test
     */
    public function test_case_insensitive_extension_validation(): void
    {
        // Arrange - Mixed case extensions
        $files = [
            UploadedFile::fake()->image('photo.JPG'),
            UploadedFile::fake()->image('photo.Jpg'),
            UploadedFile::fake()->image('photo.jPg'),
        ];

        foreach ($files as $file) {
            // Act
            $result = $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg'],
            ]);

            // Assert
            $this->assertIsArray($result);
            Storage::disk('public')->assertExists($result['file_path']);
        }
    }

    /**
     * Test SVG file upload security (potential XSS vector).
     *
     * @test
     */
    public function test_svg_file_upload_security(): void
    {
        // Arrange - SVG files can contain JavaScript
        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

        // Act & Assert - SVG should be rejected unless explicitly allowed
        $this->expectException(ValidationException::class);
        $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg', 'png', 'gif'],
        ]);
    }

    /**
     * Test HTML file upload rejection.
     *
     * @test
     */
    public function test_html_file_upload_rejection(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('page.html', 100, 'text/html');

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg', 'png', 'pdf'],
        ]);
    }

    /**
     * Test XML file upload rejection (XXE attack vector).
     *
     * @test
     */
    public function test_xml_file_upload_rejection(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('data.xml', 100, 'application/xml');

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->processor->process($file, 'uploads', [
            'allowed_types' => ['jpg', 'png', 'pdf'],
        ]);
    }

    /**
     * Test archive file upload security.
     *
     * @test
     */
    public function test_archive_file_upload_security(): void
    {
        // Arrange - Archive files can contain malicious content
        $archiveFiles = [
            ['name' => 'archive.zip', 'mime' => 'application/zip'],
            ['name' => 'archive.rar', 'mime' => 'application/x-rar-compressed'],
            ['name' => 'archive.tar', 'mime' => 'application/x-tar'],
        ];

        foreach ($archiveFiles as $fileInfo) {
            // Act & Assert
            $file = UploadedFile::fake()->create($fileInfo['name'], 100, $fileInfo['mime']);

            $this->expectException(ValidationException::class);
            $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg', 'png', 'pdf'],
            ]);
        }
    }

    /**
     * Test that validation happens before file storage.
     *
     * @test
     */
    public function test_validation_happens_before_file_storage(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/octet-stream');

        // Act & Assert
        try {
            $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg', 'png'],
            ]);
            $this->fail('Should have thrown ValidationException');
        } catch (ValidationException $e) {
            // Assert - No files stored on validation failure
            $files = Storage::disk('public')->allFiles('uploads');
            $this->assertEmpty($files);
        }
    }

    /**
     * Test multiple validation failures.
     *
     * @test
     */
    public function test_multiple_validation_failures(): void
    {
        // Arrange - File that fails both size and type validation
        $file = UploadedFile::fake()->create('huge.exe', 10240, 'application/octet-stream');

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->processor->process($file, 'uploads', [
            'max_size' => 5120,
            'allowed_types' => ['jpg', 'png'],
        ]);
    }

    /**
     * Test MIME type validation for common image formats.
     *
     * @test
     */
    public function test_mime_type_validation_for_common_image_formats(): void
    {
        // Arrange
        $validImages = [
            ['name' => 'photo.jpg', 'mime' => 'image/jpeg'],
            ['name' => 'graphic.png', 'mime' => 'image/png'],
            ['name' => 'animation.gif', 'mime' => 'image/gif'],
        ];

        foreach ($validImages as $imageInfo) {
            // Act - Use image() to create actual image files
            $file = UploadedFile::fake()->image($imageInfo['name']);
            $result = $this->processor->process($file, 'uploads', [
                'allowed_types' => ['jpg', 'png', 'gif'],
            ]);

            // Assert
            $this->assertIsArray($result);
            // Note: fake()->image() may not set exact MIME type, so we just verify it's an image
            $this->assertStringStartsWith('image/', $result['mime_type']);
        }
    }

    /**
     * Test custom MIME type mapping for security.
     *
     * @test
     */
    public function test_custom_mime_type_mapping_for_security(): void
    {
        // Arrange - Add custom secure MIME mapping
        $this->validator->addMimeMapping('secure', ['application/x-secure']);

        $file = UploadedFile::fake()->create('file.secure', 100, 'application/x-secure');

        // Act
        $result = $this->processor->process($file, 'uploads', [
            'allowed_types' => ['secure'],
        ]);

        // Assert
        $this->assertIsArray($result);
        Storage::disk('public')->assertExists($result['file_path']);
    }

    /**
     * Test that malicious files are never stored.
     *
     * @test
     */
    public function test_malicious_files_are_never_stored(): void
    {
        // Arrange
        $maliciousFiles = [
            UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
            UploadedFile::fake()->create('script.php', 100, 'application/x-php'),
            UploadedFile::fake()->create('shell.sh', 100, 'application/x-sh'),
        ];

        foreach ($maliciousFiles as $file) {
            // Act & Assert
            try {
                $this->processor->process($file, 'uploads', [
                    'allowed_types' => ['jpg', 'png', 'pdf'],
                ]);
            } catch (ValidationException $e) {
                // Expected - validation should fail
            }
        }

        // Assert - No malicious files stored
        $allFiles = Storage::disk('public')->allFiles('uploads');
        $this->assertEmpty($allFiles);
    }
}
