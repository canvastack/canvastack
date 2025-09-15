<?php

namespace Canvastack\Canvastack\Tests\Security;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Canvastack\Canvastack\Library\Components\Form\Security\HtmlSanitizer;
use Canvastack\Canvastack\Library\Components\Form\Elements\File;
use Canvastack\Canvastack\Library\Components\Form\Objects;

/**
 * Phase 1 Security Tests - Critical Vulnerabilities
 * 
 * Tests for:
 * - V001: Path Traversal Prevention
 * - V002: File Upload Security
 * - V003: XSS Prevention
 * - V007: Directory Permissions
 * - V008: CSRF Protection
 */
class Phase1SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Test V001: Path Traversal Prevention
     */
    public function test_filename_sanitization_prevents_path_traversal()
    {
        $fileComponent = new class {
            use \Canvastack\Canvastack\Library\Components\Form\Elements\File;
            
            public function testSanitizeFilename($filename)
            {
                return $this->sanitizeFilename($filename);
            }
        };

        // Test path traversal attempts
        $maliciousFilenames = [
            '../../../etc/passwd' => 'etc_passwd',
            '..\\..\\windows\\system32\\config\\sam' => 'windows_system32_config_sam',
            '<script>alert(1)</script>.php' => '_script_alert_1___script_.php',
            'normal-file.jpg' => 'normal-file.jpg',
            '' => 'upload_', // Should generate random name
            str_repeat('a', 200) . '.jpg' => substr(str_repeat('a', 100), 0, 100) . '.jpg'
        ];

        foreach ($maliciousFilenames as $malicious => $expected) {
            $result = $fileComponent->testSanitizeFilename($malicious);
            
            // Check that result doesn't contain path traversal
            $this->assertStringNotContainsString('..', $result);
            $this->assertStringNotContainsString('/', $result);
            $this->assertStringNotContainsString('\\', $result);
            
            // Check length limit
            $this->assertLessThanOrEqual(104, strlen($result)); // 100 + '.ext'
            
            if ($malicious === '') {
                $this->assertStringStartsWith('upload_', $result);
            }
        }
    }

    /**
     * Test V002: File Upload Security - Extension Validation
     */
    public function test_file_upload_blocks_dangerous_extensions()
    {
        $fileComponent = new class {
            use \Canvastack\Canvastack\Library\Components\Form\Elements\File;
            
            public function testValidateFileType($file)
            {
                return $this->validateFileType($file);
            }
        };

        // Test dangerous file types
        $dangerousFiles = [
            'malicious.php' => 'application/x-php',
            'script.js' => 'application/javascript',
            'executable.exe' => 'application/x-msdownload',
            'shell.sh' => 'application/x-sh'
        ];

        foreach ($dangerousFiles as $filename => $mimeType) {
            $file = UploadedFile::fake()->create($filename, 100, $mimeType);
            
            $this->expectException(\InvalidArgumentException::class);
            $fileComponent->testValidateFileType($file);
        }
    }

    /**
     * Test V002: File Upload Security - MIME Type Validation
     */
    public function test_file_upload_validates_mime_types()
    {
        $fileComponent = new class {
            use \Canvastack\Canvastack\Library\Components\Form\Elements\File;
            
            public function testValidateFileType($file)
            {
                return $this->validateFileType($file);
            }
        };

        // Test valid file types
        $validFiles = [
            'image.jpg' => 'image/jpeg',
            'document.pdf' => 'application/pdf',
            'text.txt' => 'text/plain'
        ];

        foreach ($validFiles as $filename => $mimeType) {
            $file = UploadedFile::fake()->create($filename, 100, $mimeType);
            
            $result = $fileComponent->testValidateFileType($file);
            $this->assertTrue($result);
        }
    }

    /**
     * Test V003: XSS Prevention - HTML Sanitization
     */
    public function test_html_sanitizer_prevents_xss()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>' => '',
            '<img src="x" onerror="alert(1)">' => '',
            'javascript:alert(1)' => 'javascript:alert(1)', // Should be escaped
            '<p>Safe content</p>' => '<p>Safe content</p>',
            '<a href="http://example.com">Link</a>' => '<a href="http://example.com">Link</a>'
        ];

        foreach ($xssPayloads as $payload => $expected) {
            $result = HtmlSanitizer::clean($payload);
            
            // Should not contain script tags
            $this->assertStringNotContainsString('<script', $result);
            $this->assertStringNotContainsString('onerror=', $result);
            $this->assertStringNotContainsString('javascript:', $result);
        }
    }

    /**
     * Test V003: XSS Prevention - Attribute Sanitization
     */
    public function test_attribute_sanitization()
    {
        $maliciousAttributes = [
            'onclick' => 'alert(1)',
            'onload' => 'malicious()',
            'href' => 'javascript:alert(1)',
            'src' => 'data:text/html,<script>alert(1)</script>',
            'normal' => 'safe-value'
        ];

        $cleaned = HtmlSanitizer::cleanAttributes($maliciousAttributes);

        foreach ($cleaned as $key => $value) {
            $this->assertStringNotContainsString('<script', $value);
            $this->assertStringNotContainsString('javascript:', $value);
            $this->assertStringNotContainsString('data:text/html', $value);
        }
    }

    /**
     * Test V003: XSS Detection
     */
    public function test_xss_detection()
    {
        $xssInputs = [
            '<script>alert(1)</script>',
            'javascript:alert(1)',
            '<img onerror="alert(1)">',
            '<iframe src="malicious"></iframe>',
            'expression(alert(1))',
            'vbscript:msgbox(1)'
        ];

        foreach ($xssInputs as $input) {
            $this->assertTrue(HtmlSanitizer::containsXSS($input));
        }

        $safeInputs = [
            'Normal text',
            '<p>Safe HTML</p>',
            'user@example.com',
            'https://example.com'
        ];

        foreach ($safeInputs as $input) {
            $this->assertFalse(HtmlSanitizer::containsXSS($input));
        }
    }

    /**
     * Test V007: Directory Permissions
     */
    public function test_secure_directory_creation()
    {
        $fileComponent = new class {
            use \Canvastack\Canvastack\Library\Components\Form\Elements\File;
            
            public function testCreateSecureDirectory($path)
            {
                return $this->createSecureDirectory($path);
            }
        };

        $testPath = storage_path('test_secure_dir');
        
        // Clean up if exists
        if (is_dir($testPath)) {
            rmdir($testPath);
        }

        $fileComponent->testCreateSecureDirectory($testPath);

        // Check directory was created
        $this->assertTrue(is_dir($testPath));

        // Check permissions (on Unix systems)
        if (PHP_OS_FAMILY !== 'Windows') {
            $perms = fileperms($testPath) & 0777;
            $this->assertEquals(0755, $perms);
        }

        // Check .htaccess was created
        $this->assertTrue(file_exists($testPath . '/.htaccess'));
        
        // Check index.html was created
        $this->assertTrue(file_exists($testPath . '/index.html'));

        // Check .htaccess content
        $htaccessContent = file_get_contents($testPath . '/.htaccess');
        $this->assertStringContainsString('Options -Indexes', $htaccessContent);
        $this->assertStringContainsString('Deny from all', $htaccessContent);

        // Clean up
        unlink($testPath . '/.htaccess');
        unlink($testPath . '/index.html');
        rmdir($testPath);
    }

    /**
     * Test V008: CSRF Protection
     */
    public function test_csrf_token_added_to_forms()
    {
        $formObject = new Objects();
        
        // Capture output
        ob_start();
        $formObject->open('test.route', 'POST');
        $elements = $formObject->elements;
        ob_end_clean();

        // Check that CSRF token was added
        $formHtml = implode('', $elements);
        $this->assertStringContainsString('_token', $formHtml);
        $this->assertStringContainsString('csrf_token()', $formHtml);
    }

    /**
     * Integration Test: Complete Form Security
     */
    public function test_complete_form_security_integration()
    {
        $formObject = new Objects();
        
        // Test form with XSS attempt
        $formObject->draw('<script>alert("XSS")</script>');
        
        $elements = $formObject->elements;
        $formHtml = implode('', $elements);
        
        // Should not contain script tags
        $this->assertStringNotContainsString('<script', $formHtml);
        
        // Test that normal content is preserved
        $formObject->draw('<p>Normal content</p>');
        $elements = $formObject->elements;
        $formHtml = implode('', $elements);
        
        $this->assertStringContainsString('<p>Normal content</p>', $formHtml);
    }
}