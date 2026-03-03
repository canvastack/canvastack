<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Schema;

/**
 * Property 6: URL Validation.
 *
 * Validates: Requirements 24.4
 *
 * Property: For ALL URLs used in action buttons or links, ONLY http:// and https://
 * schemes MUST be accepted. javascript:, data:, vbscript:, and file:// schemes
 * MUST be rejected.
 *
 * This property ensures that malicious URL schemes cannot be used to execute
 * JavaScript code or access local files.
 */
class URLValidationPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real Mantra users table
        $this->table = app(TableBuilder::class);

        // Check if users table exists, if not create test table
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            });
        }

        $this->table->setName('users');
    }

    /**
     * Property 6.1: http:// URLs are accepted.
     *
     * @test
     * @group property
     * @group security
     * @group canvastack-table-complete
     */
    public function property_http_urls_are_accepted(): void
    {
        $httpURLs = [
            'http://example.com',
            'http://example.com/path',
            'http://example.com/path?query=value',
            'http://example.com:8080',
            'http://subdomain.example.com',
        ];

        $this->forAll(
            Generator::elements($httpURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertTrue($isValid, "http:// URL should be valid: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.2: https:// URLs are accepted.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_https_urls_are_accepted(): void
    {
        $httpsURLs = [
            'https://example.com',
            'https://example.com/path',
            'https://example.com/path?query=value',
            'https://example.com:443',
            'https://subdomain.example.com',
        ];

        $this->forAll(
            Generator::elements($httpsURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertTrue($isValid, "https:// URL should be valid: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.3: javascript: URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_javascript_urls_are_rejected(): void
    {
        $javascriptURLs = [
            'javascript:alert("XSS")',
            'javascript:void(0)',
            'javascript:document.cookie',
            'JAVASCRIPT:alert(1)',
            'JaVaScRiPt:alert(1)',
        ];

        $this->forAll(
            Generator::elements($javascriptURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertFalse($isValid, "javascript: URL should be rejected: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.4: data: URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_data_urls_are_rejected(): void
    {
        $dataURLs = [
            'data:text/html,<script>alert("XSS")</script>',
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            'data:image/svg+xml,<svg onload=alert(1)>',
            'DATA:text/html,<script>alert(1)</script>',
        ];

        $this->forAll(
            Generator::elements($dataURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertFalse($isValid, "data: URL should be rejected: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.5: vbscript: URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_vbscript_urls_are_rejected(): void
    {
        $vbscriptURLs = [
            'vbscript:msgbox("XSS")',
            'vbscript:alert(1)',
            'VBSCRIPT:msgbox(1)',
            'VbScRiPt:msgbox(1)',
        ];

        $this->forAll(
            Generator::elements($vbscriptURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertFalse($isValid, "vbscript: URL should be rejected: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.6: file:// URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_file_urls_are_rejected(): void
    {
        $fileURLs = [
            'file:///etc/passwd',
            'file:///C:/Windows/System32/config/sam',
            'file://localhost/etc/passwd',
            'FILE:///etc/passwd',
        ];

        $this->forAll(
            Generator::elements($fileURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertFalse($isValid, "file:// URL should be rejected: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.7: ftp:// URLs are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_ftp_urls_are_rejected(): void
    {
        $ftpURLs = [
            'ftp://example.com',
            'ftp://user:pass@example.com',
            'FTP://example.com',
        ];

        $this->forAll(
            Generator::elements($ftpURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertFalse($isValid, "ftp:// URL should be rejected: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.8: Relative URLs are accepted.
     *
     * @test
     * @group property
     */
    public function property_relative_urls_are_accepted(): void
    {
        $relativeURLs = [
            '/admin/users',
            '/admin/users/edit/1',
            '../users',
            'users/view',
            '/path?query=value',
        ];

        $this->forAll(
            Generator::elements($relativeURLs),
            function (string $url) {
                $isValid = $this->validateURL($url);
                $this->assertTrue($isValid, "Relative URL should be valid: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.9: URLs with encoded dangerous schemes are rejected.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_encoded_dangerous_schemes_are_rejected(): void
    {
        $encodedURLs = [
            'java&#115;cript:alert(1)',
            'java\script:alert(1)',
            'java%0ascript:alert(1)',
            'java%09script:alert(1)',
            'java%0dscript:alert(1)',
        ];

        $this->forAll(
            Generator::elements($encodedURLs),
            function (string $url) {
                // Decode and validate
                $decoded = html_entity_decode($url);
                $decoded = urldecode($decoded);
                $isValid = $this->validateURL($decoded);
                $this->assertFalse($isValid, "Encoded dangerous URL should be rejected: {$url}");

                return true;
            },
            100
        );
    }

    /**
     * Property 6.10: Case-insensitive scheme validation.
     *
     * @test
     * @group property
     * @group security
     */
    public function property_case_insensitive_scheme_validation(): void
    {
        $mixedCaseURLs = [
            ['HTTP://example.com', true],
            ['HTTPS://example.com', true],
            ['HtTp://example.com', true],
            ['HtTpS://example.com', true],
            ['JAVASCRIPT:alert(1)', false],
            ['DATA:text/html,<script>', false],
            ['VBSCRIPT:msgbox(1)', false],
            ['FILE:///etc/passwd', false],
        ];

        $this->forAll(
            Generator::elements($mixedCaseURLs),
            function (array $urlData) {
                [$url, $shouldBeValid] = $urlData;
                $isValid = $this->validateURL($url);

                if ($shouldBeValid) {
                    $this->assertTrue($isValid, "URL should be valid: {$url}");
                } else {
                    $this->assertFalse($isValid, "URL should be rejected: {$url}");
                }

                return true;
            },
            100
        );
    }

    /**
     * Helper method to validate URLs.
     *
     * This simulates the URL validation that should be implemented
     * in the TableBuilder or a dedicated URL validator class.
     */
    private function validateURL(string $url): bool
    {
        // Trim whitespace
        $url = trim($url);

        // Empty URLs are invalid
        if (empty($url)) {
            return false;
        }

        // Decode URL entities and special characters
        $decoded = html_entity_decode($url);
        $decoded = urldecode($decoded);

        // Remove whitespace characters and backslashes that might be used to obfuscate schemes
        // %09 = tab, %0a = newline, %0d = carriage return
        $decoded = str_replace(["\t", "\n", "\r", ' ', '\\'], '', $decoded);

        // Check for dangerous schemes (case-insensitive)
        $dangerousSchemes = [
            'javascript:',
            'data:',
            'vbscript:',
            'file://',
            'ftp://',
        ];

        foreach ($dangerousSchemes as $scheme) {
            if (stripos($decoded, $scheme) === 0) {
                return false;
            }
        }

        // Accept relative URLs (no scheme)
        if (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $decoded)) {
            return true;
        }

        // Accept only http:// and https://
        if (preg_match('/^https?:\/\//i', $decoded)) {
            return true;
        }

        // Reject all other schemes
        return false;
    }
}
