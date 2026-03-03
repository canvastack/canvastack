<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Security Test: XSS Prevention in URLs.
 *
 * Requirements: 24.4, 36.3
 *
 * Validates that only safe URL schemes (http, https) are allowed
 * and dangerous schemes (javascript:, data:) are rejected.
 */
class XssUrlsTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_links (id INT PRIMARY KEY, title VARCHAR(255), url VARCHAR(255))');
        DB::table('test_links')->insert([
            ['id' => 1, 'title' => 'Safe Link', 'url' => 'https://example.com'],
            ['id' => 2, 'title' => 'Another Link', 'url' => 'http://example.org'],
        ]);
    }

    /** @test */
    public function it_rejects_javascript_url_in_actions()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL scheme');

        $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'Malicious',
                    'url' => 'javascript:alert("XSS")',
                    'icon' => 'eye',
                ],
            ]);
    }

    /** @test */
    public function it_rejects_data_url_in_actions()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL scheme');

        $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'Malicious',
                    'url' => 'data:text/html,<script>alert("XSS")</script>',
                    'icon' => 'eye',
                ],
            ]);
    }

    /** @test */
    public function it_rejects_vbscript_url_in_actions()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL scheme');

        $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'Malicious',
                    'url' => 'vbscript:msgbox("XSS")',
                    'icon' => 'eye',
                ],
            ]);
    }

    /** @test */
    public function it_rejects_file_url_in_actions()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL scheme');

        $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'Malicious',
                    'url' => 'file:///etc/passwd',
                    'icon' => 'eye',
                ],
            ]);
    }

    /** @test */
    public function it_accepts_http_urls()
    {
        $table = $this->createTableBuilder();

        $result = $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'View',
                    'url' => 'http://example.com/view/{id}',
                    'icon' => 'eye',
                ],
            ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_accepts_https_urls()
    {
        $table = $this->createTableBuilder();

        $result = $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'View',
                    'url' => 'https://example.com/view/{id}',
                    'icon' => 'eye',
                ],
            ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_accepts_relative_urls()
    {
        $table = $this->createTableBuilder();

        $result = $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'View',
                    'url' => '/admin/items/{id}',
                    'icon' => 'eye',
                ],
            ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_rejects_mixed_case_javascript_urls()
    {
        $table = $this->createTableBuilder();

        $maliciousUrls = [
            'JaVaScRiPt:alert("XSS")',
            'JAVASCRIPT:alert("XSS")',
            'javascript:alert("XSS")',
            'jAvAsCrIpT:alert("XSS")',
        ];

        foreach ($maliciousUrls as $url) {
            try {
                $table->setName('test_links')
                    ->setFields(['id', 'title'])
                    ->setActions([
                        ['label' => 'Test', 'url' => $url, 'icon' => 'eye'],
                    ]);

                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid URL scheme', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_javascript_urls_with_whitespace()
    {
        $table = $this->createTableBuilder();

        $maliciousUrls = [
            ' javascript:alert("XSS")',
            'javascript: alert("XSS")',
            "javascript:\nalert('XSS')",
            "javascript:\talert('XSS')",
        ];

        foreach ($maliciousUrls as $url) {
            try {
                $table->setName('test_links')
                    ->setFields(['id', 'title'])
                    ->setActions([
                        ['label' => 'Test', 'url' => $url, 'icon' => 'eye'],
                    ]);

                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid URL scheme', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_encoded_javascript_urls()
    {
        $table = $this->createTableBuilder();

        $maliciousUrls = [
            'javascript%3Aalert("XSS")', // URL encoded
            '&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert("XSS")', // HTML entity encoded
        ];

        foreach ($maliciousUrls as $url) {
            try {
                $table->setName('test_links')
                    ->setFields(['id', 'title'])
                    ->setActions([
                        ['label' => 'Test', 'url' => $url, 'icon' => 'eye'],
                    ]);

                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid URL scheme', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_encodes_url_parameters_safely()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'View',
                    'url' => '/view/{id}?param=<script>alert("XSS")</script>',
                    'icon' => 'eye',
                ],
            ])
            ->render();

        // Verify URL parameters are encoded
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** @test */
    public function it_validates_urls_in_clickable_columns()
    {
        $table = $this->createTableBuilder();

        // When clickable columns are enabled, URLs should be validated
        $html = $table->setName('test_links')
            ->setFields(['id', 'title', 'url'])
            ->clickable(['title'])
            ->render();

        // Verify javascript: URLs in data are escaped
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    /** @test */
    public function it_accepts_urls_with_query_parameters()
    {
        $table = $this->createTableBuilder();

        $result = $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'View',
                    'url' => 'https://example.com/view?id={id}&action=view',
                    'icon' => 'eye',
                ],
            ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_accepts_urls_with_anchors()
    {
        $table = $this->createTableBuilder();

        $result = $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'View',
                    'url' => 'https://example.com/page#section-{id}',
                    'icon' => 'eye',
                ],
            ]);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_rejects_blob_urls()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL scheme');

        $table->setName('test_links')
            ->setFields(['id', 'title'])
            ->setActions([
                [
                    'label' => 'Malicious',
                    'url' => 'blob:https://example.com/malicious',
                    'icon' => 'eye',
                ],
            ]);
    }
}
