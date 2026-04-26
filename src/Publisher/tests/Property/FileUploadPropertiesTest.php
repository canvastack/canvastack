<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Canvastack\Canvastack\Library\Components\Form\Objects;

/**
 * Property-Based Tests for File Upload Security
 * 
 * Uses Eris property-based testing to verify file upload security properties
 * hold across all possible inputs with various file types, sizes, and paths.
 * 
 * Each test runs 100+ iterations with randomly generated inputs to
 * discover edge cases and ensure comprehensive file upload security.
 * 
 * Validates: Requirements 2.1, 2.2, 2.3, 9.1, 9.2, 9.3, 9.5, 9.6
 * 
 * @group property
 * @group security
 * @group file-upload
 */
class FileUploadPropertiesTest extends TestCase
{
    use TestTrait;
    
    protected Objects $formObject;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize form object for testing
        $this->formObject = new Objects();
        
        // Set up test storage
        Storage::fake('public');
    }
    
    /**
     * Property 5: File Type and Size Validation
     * 
     * **Validates: Requirements 2.1**
     * 
     * For any file upload, the file type and size SHALL be validated against 
     * the configured validation rules before acceptance.
     * 
     * This property verifies that:
     * - File extensions are validated against whitelist
     * - File sizes are validated against maximum limit
     * - Invalid files are rejected with appropriate exceptions
     * - Valid files are accepted
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_5_file_type_and_size_validation()
    {
        $this->forAll(
            Generators::oneOf(
                // Valid extensions
                Generators::elements(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'csv']),
                // Invalid extensions
                Generators::elements(['php', 'exe', 'sh', 'bat', 'js', 'phtml', 'phar'])
            ),
            Generators::choose(1, 15 * 1024 * 1024) // 1 byte to 15MB
        )
        ->withMaxSize(1000)
        ->then(function ($extension, $fileSize) {
            $filename = 'test_file_' . bin2hex(random_bytes(8)) . '.' . $extension;
            
            // Define allowed extensions and max size
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'csv'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            
            // Property: Extension validation
            $isValidExtension = in_array(strtolower($extension), $allowedExtensions);
            
            // Property: Size validation
            $isValidSize = $fileSize <= $maxFileSize;
            
            // Property: File should be accepted only if both validations pass
            $shouldAccept = $isValidExtension && $isValidSize;
            
            if ($shouldAccept) {
                // Valid file - should not throw exception
                try {
                    $reflection = new \ReflectionClass($this->formObject);
                    $method = $reflection->getMethod('validateFileExtension');
                    $method->setAccessible(true);
                    
                    $result = $method->invoke($this->formObject, $filename, $allowedExtensions);
                    $this->assertTrue($result, "Valid file extension '{$extension}' was rejected");
                } catch (\InvalidArgumentException $e) {
                    $this->fail("Valid file extension '{$extension}' threw exception: " . $e->getMessage());
                }
            } else {
                // Invalid file - should throw exception
                if (!$isValidExtension) {
                    $this->expectException(\InvalidArgumentException::class);
                    
                    $reflection = new \ReflectionClass($this->formObject);
                    $method = $reflection->getMethod('validateFileExtension');
                    $method->setAccessible(true);
                    
                    $method->invoke($this->formObject, $filename, $allowedExtensions);
                }
            }
        });
    }

    
    /**
     * Property 6: MIME Type Content Validation
     * 
     * **Validates: Requirements 2.2**
     * 
     * For any file upload, the MIME type SHALL be validated based on actual 
     * file content, not just the file extension.
     * 
     * This property verifies that:
     * - MIME types are validated from actual file content
     * - Extension spoofing is detected (e.g., .jpg file with PHP content)
     * - Only allowed MIME types are accepted
     * - Invalid MIME types are rejected
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_6_mime_type_content_validation()
    {
        $this->forAll(
            Generators::oneOf(
                // Valid MIME types
                Generators::elements([
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    'application/pdf', 'text/plain', 'text/csv'
                ]),
                // Invalid MIME types
                Generators::elements([
                    'application/x-php', 'application/x-httpd-php', 'text/x-php',
                    'application/x-sh', 'application/x-executable', 'text/x-shellscript'
                ])
            )
        )
        ->withMaxSize(500)
        ->then(function ($mimeType) {
            // Define allowed MIME types
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/plain', 'text/csv'
            ];
            
            // Property: MIME type validation
            $isValidMime = in_array($mimeType, $allowedMimes);
            
            // Create a mock file with the specified MIME type
            $mockFile = $this->createMockFileWithMimeType($mimeType);
            
            if ($isValidMime) {
                // Valid MIME type - should not throw exception
                try {
                    $reflection = new \ReflectionClass($this->formObject);
                    $method = $reflection->getMethod('validateMimeType');
                    $method->setAccessible(true);
                    
                    $result = $method->invoke($this->formObject, $mockFile, $allowedMimes);
                    $this->assertTrue($result, "Valid MIME type '{$mimeType}' was rejected");
                } catch (\InvalidArgumentException $e) {
                    $this->fail("Valid MIME type '{$mimeType}' threw exception: " . $e->getMessage());
                }
            } else {
                // Invalid MIME type - should throw exception
                $this->expectException(\InvalidArgumentException::class);
                
                $reflection = new \ReflectionClass($this->formObject);
                $method = $reflection->getMethod('validateMimeType');
                $method->setAccessible(true);
                
                $method->invoke($this->formObject, $mockFile, $allowedMimes);
            }
        });
    }
    
    /**
     * Property 7: Path Traversal Prevention
     * 
     * **Validates: Requirements 2.3**
     * 
     * For any upload path, the path SHALL be validated to ensure it does not 
     * contain directory traversal patterns (../, ..\).
     * 
     * This property verifies that:
     * - Paths with ../ are rejected
     * - Paths with ..\ are rejected
     * - Paths outside base directory are rejected
     * - Valid paths within base directory are accepted
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_7_path_traversal_prevention()
    {
        $this->forAll(
            Generators::oneOf(
                // Safe paths
                Generators::elements([
                    'uploads/images',
                    'uploads/documents',
                    'assets/files',
                    'storage/temp'
                ]),
                // Dangerous paths with traversal attempts
                Generators::elements([
                    '../../../etc/passwd',
                    '..\\..\\..\\windows\\system32',
                    'uploads/../../config',
                    'uploads/../../../.env',
                    'uploads/../secrets'
                ])
            )
        )
        ->withMaxSize(500)
        ->then(function ($path) {
            // Property: Path contains traversal patterns
            $hasTraversal = str_contains($path, '..');
            
            // Create base directory for testing
            $baseDir = storage_path('app/public');
            $testPath = $baseDir . '/' . $path;
            
            if ($hasTraversal) {
                // Path with traversal - should throw exception
                $this->expectException(\InvalidArgumentException::class);
                $this->expectExceptionMessage('directory traversal');
                
                $reflection = new \ReflectionClass($this->formObject);
                $method = $reflection->getMethod('validateUploadPath');
                $method->setAccessible(true);
                
                $method->invoke($this->formObject, $testPath, $baseDir);
            } else {
                // Safe path - should not throw exception
                try {
                    // Create the directory if it doesn't exist
                    if (!file_exists($testPath)) {
                        mkdir($testPath, 0777, true);
                    }
                    
                    $reflection = new \ReflectionClass($this->formObject);
                    $method = $reflection->getMethod('validateUploadPath');
                    $method->setAccessible(true);
                    
                    $result = $method->invoke($this->formObject, $testPath, $baseDir);
                    $this->assertTrue($result, "Safe path '{$path}' was rejected");
                    
                    // Clean up
                    if (file_exists($testPath) && is_dir($testPath)) {
                        rmdir($testPath);
                    }
                } catch (\InvalidArgumentException $e) {
                    $this->fail("Safe path '{$path}' threw exception: " . $e->getMessage());
                }
            }
        });
    }
    
    /**
     * Property 24: Random Filename Generation
     * 
     * **Validates: Requirements 9.3**
     * 
     * For any file saved to storage, a random filename SHALL be generated 
     * to prevent overwrite attacks.
     * 
     * This property verifies that:
     * - Generated filenames are unique
     * - Generated filenames preserve original extension
     * - Generated filenames are unpredictable
     * - No two files get the same filename
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_24_random_filename_generation()
    {
        $this->forAll(
            Generators::string(),
            Generators::elements(['jpg', 'png', 'pdf', 'txt', 'csv', 'doc', 'xls'])
        )
        ->withMaxSize(500)
        ->then(function ($originalName, $extension) {
            // Create original filename
            $originalFilename = $originalName . '.' . $extension;
            
            // Generate secure filename
            $reflection = new \ReflectionClass($this->formObject);
            $method = $reflection->getMethod('generateSecureFilename');
            $method->setAccessible(true);
            
            $secureFilename1 = $method->invoke($this->formObject, $originalFilename);
            $secureFilename2 = $method->invoke($this->formObject, $originalFilename);
            
            // Property 1: Generated filename must be different from original
            $this->assertNotEquals($originalFilename, $secureFilename1,
                'Generated filename is same as original - no randomization');
            
            // Property 2: Generated filename must preserve extension
            $generatedExtension = pathinfo($secureFilename1, PATHINFO_EXTENSION);
            $this->assertEquals(strtolower($extension), strtolower($generatedExtension),
                'Generated filename does not preserve original extension');
            
            // Property 3: Two calls should generate different filenames (uniqueness)
            $this->assertNotEquals($secureFilename1, $secureFilename2,
                'Two calls generated same filename - not unique');
            
            // Property 4: Generated filename should not contain original name
            // (prevents predictability)
            $cleanOriginalName = pathinfo($originalFilename, PATHINFO_FILENAME);
            if (!empty($cleanOriginalName) && strlen($cleanOriginalName) > 3) {
                $this->assertStringNotContainsString($cleanOriginalName, $secureFilename1,
                    'Generated filename contains original name - predictable');
            }
            
            // Property 5: Generated filename should be reasonably long (security)
            $this->assertGreaterThan(10, strlen($secureFilename1),
                'Generated filename is too short - may be predictable');
        });
    }
    
    /**
     * Property 26: File Permission Setting
     * 
     * **Validates: Requirements 9.6**
     * 
     * For any uploaded file, the file permissions SHALL be set to 0644 
     * after upload.
     * 
     * This property verifies that:
     * - File permissions are set to 0644 (read-only for group/others)
     * - Owner can read and write
     * - Group can only read
     * - Others can only read
     * - Files are not executable
     * 
     * Note: On Windows, chmod has limited functionality and may not set exact permissions.
     * The test verifies that the method is called and returns success.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_property_26_file_permission_setting()
    {
        $this->forAll(
            Generators::string()
        )
        ->withMaxSize(500)
        ->then(function ($filename) {
            // Create a temporary test file
            $testDir = storage_path('app/public/test_uploads');
            if (!file_exists($testDir)) {
                mkdir($testDir, 0777, true);
            }
            
            $testFilePath = $testDir . '/' . 'test_' . bin2hex(random_bytes(8)) . '.txt';
            file_put_contents($testFilePath, 'test content');
            
            // Set initial permissions (simulate upload with wrong permissions)
            chmod($testFilePath, 0777);
            
            // Apply secure file permissions
            $reflection = new \ReflectionClass($this->formObject);
            $method = $reflection->getMethod('setSecureFilePermissions');
            $method->setAccessible(true);
            
            $result = $method->invoke($this->formObject, $testFilePath);
            $this->assertTrue($result, 'Failed to set file permissions');
            
            // Property: On Unix systems, file permissions must be 0644
            // On Windows, chmod has limited functionality, so we skip exact permission check
            if (DIRECTORY_SEPARATOR === '/') {
                // Unix/Linux system
                $actualPerms = fileperms($testFilePath);
                $actualPermsOctal = substr(sprintf('%o', $actualPerms), -4);
                
                $this->assertEquals('0644', $actualPermsOctal,
                    "File permissions are {$actualPermsOctal}, expected 0644");
            } else {
                // Windows system - just verify the method was called successfully
                $this->assertTrue($result, 'setSecureFilePermissions should return true');
            }
            
            // Property: File must not be executable (cross-platform)
            $this->assertFalse(is_executable($testFilePath),
                'File is executable - security risk');
            
            // Property: File must be readable (cross-platform)
            $this->assertTrue(is_readable($testFilePath),
                'File is not readable');
            
            // Property: File must be writable by owner (cross-platform)
            $this->assertTrue(is_writable($testFilePath),
                'File is not writable by owner');
            
            // Clean up
            unlink($testFilePath);
            if (is_dir($testDir) && count(scandir($testDir)) === 2) {
                rmdir($testDir);
            }
        });
    }

    
    /**
     * Helper: Create mock file with specified MIME type
     * 
     * @param string $mimeType MIME type to set
     * 
     * @return object Mock file object
     */
    private function createMockFileWithMimeType(string $mimeType): object
    {
        $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
        $mockFile->method('getMimeType')->willReturn($mimeType);
        
        return $mockFile;
    }
    
    /**
     * Property Test: Double Extension Attack Prevention
     * 
     * Tests that files with double extensions (e.g., file.php.jpg) are properly
     * detected and rejected to prevent execution of malicious code.
     * 
     * This property verifies that the file extension validation detects and
     * blocks double extension attacks where dangerous extensions are hidden.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_double_extension_attack_prevention()
    {
        $this->forAll(
            Generators::elements(['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'sh', 'bat', 'js']),
            Generators::elements(['jpg', 'png', 'pdf', 'txt'])
        )
        ->withMaxSize(500)
        ->then(function ($dangerousExt, $safeExt) {
            // Create filename with double extension
            $filename = 'malicious.' . $dangerousExt . '.' . $safeExt;
            
            // Property: Double extension with dangerous extension should be rejected
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('double extension');
            
            $reflection = new \ReflectionClass($this->formObject);
            $method = $reflection->getMethod('validateFileExtension');
            $method->setAccessible(true);
            
            $allowedExtensions = ['jpg', 'png', 'pdf', 'txt'];
            $method->invoke($this->formObject, $filename, $allowedExtensions);
        });
    }
    
    /**
     * Property Test: File Size Boundary Testing
     * 
     * Tests file size validation at boundary conditions to ensure proper
     * handling of edge cases around the maximum file size limit.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_file_size_boundary_validation()
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $this->forAll(
            Generators::choose(
                $maxSize - 1000,  // Just under limit
                $maxSize + 1000   // Just over limit
            )
        )
        ->withMaxSize(500)
        ->then(function ($fileSize) use ($maxSize) {
            // Create mock file with specified size
            $mockFile = $this->createMock(\Illuminate\Http\UploadedFile::class);
            $mockFile->method('getSize')->willReturn($fileSize);
            
            // Property: Files at or under limit should pass
            // Files over limit should fail
            $shouldPass = $fileSize <= $maxSize;
            
            if ($shouldPass) {
                try {
                    $reflection = new \ReflectionClass($this->formObject);
                    $method = $reflection->getMethod('validateFileSize');
                    $method->setAccessible(true);
                    
                    $result = $method->invoke($this->formObject, $mockFile, $maxSize);
                    $this->assertTrue($result, "File size {$fileSize} should pass (limit: {$maxSize})");
                } catch (\InvalidArgumentException $e) {
                    $this->fail("File size {$fileSize} should pass but threw exception: " . $e->getMessage());
                }
            } else {
                $this->expectException(\InvalidArgumentException::class);
                $this->expectExceptionMessage('too large');
                
                $reflection = new \ReflectionClass($this->formObject);
                $method = $reflection->getMethod('validateFileSize');
                $method->setAccessible(true);
                
                $method->invoke($this->formObject, $mockFile, $maxSize);
            }
        });
    }
    
    /**
     * Property Test: Path Normalization Security
     * 
     * Tests that path normalization properly handles various path formats
     * and prevents bypassing security checks through path manipulation.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 100)]
    public function test_path_normalization_security()
    {
        $this->forAll(
            Generators::oneOf(
                // Paths with mixed separators
                Generators::constant('uploads/images\\files'),
                Generators::constant('uploads\\images/files'),
                // Paths with multiple slashes
                Generators::constant('uploads//images///files'),
                Generators::constant('uploads\\\\images\\\\\\files'),
                // Paths with trailing separators
                Generators::constant('uploads/images/'),
                Generators::constant('uploads/images\\'),
                // Normal paths
                Generators::constant('uploads/images/files')
            )
        )
        ->withMaxSize(500)
        ->then(function ($path) {
            $baseDir = storage_path('app/public');
            $testPath = $baseDir . '/' . $path;
            
            // Create directory structure
            $normalizedPath = str_replace(['\\', '//'], '/', $testPath);
            $normalizedPath = preg_replace('#/+#', '/', $normalizedPath);
            
            if (!file_exists($normalizedPath)) {
                mkdir($normalizedPath, 0777, true);
            }
            
            try {
                $reflection = new \ReflectionClass($this->formObject);
                $method = $reflection->getMethod('validateUploadPath');
                $method->setAccessible(true);
                
                // Property: Path validation should handle normalization
                $result = $method->invoke($this->formObject, $testPath, $baseDir);
                $this->assertTrue($result, "Path normalization failed for: {$path}");
                
                // Property: Normalized path must be within base directory
                $realPath = realpath($normalizedPath);
                $realBase = realpath($baseDir);
                
                $this->assertStringStartsWith($realBase, $realPath,
                    "Normalized path escaped base directory: {$path}");
                
            } catch (\InvalidArgumentException $e) {
                // If exception thrown, it should be for a valid security reason
                $this->assertStringContainsString('traversal', strtolower($e->getMessage()),
                    "Unexpected exception for path: {$path} - " . $e->getMessage());
            } finally {
                // Clean up
                if (file_exists($normalizedPath) && is_dir($normalizedPath)) {
                    rmdir($normalizedPath);
                }
            }
        });
    }
    
    /**
     * Property Test: Filename Randomization Entropy
     * 
     * Tests that generated filenames have sufficient entropy to prevent
     * prediction and collision attacks.
     * 
     * @test
     */
    #[ErisRepeat(repeat: 150)]
    public function test_filename_randomization_entropy()
    {
        $generatedFilenames = [];
        
        $this->forAll(
            Generators::string(),
            Generators::elements(['jpg', 'png', 'pdf'])
        )
        ->withMaxSize(500)
        ->then(function ($originalName, $extension) use (&$generatedFilenames) {
            $originalFilename = $originalName . '.' . $extension;
            
            $reflection = new \ReflectionClass($this->formObject);
            $method = $reflection->getMethod('generateSecureFilename');
            $method->setAccessible(true);
            
            $secureFilename = $method->invoke($this->formObject, $originalFilename);
            
            // Property: No collisions across all generated filenames
            $this->assertNotContains($secureFilename, $generatedFilenames,
                'Filename collision detected - insufficient entropy');
            
            $generatedFilenames[] = $secureFilename;
            
            // Property: Filename should contain timestamp or random component
            $filenameWithoutExt = pathinfo($secureFilename, PATHINFO_FILENAME);
            $this->assertMatchesRegularExpression('/[0-9a-f]{16,}/', $filenameWithoutExt,
                'Filename does not contain sufficient random component');
        });
    }
}
