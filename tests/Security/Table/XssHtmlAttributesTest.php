<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use InvalidArgumentException;

/**
 * Security Test: XSS Prevention in HTML Attributes.
 *
 * Requirements: 24.2, 36.3
 *
 * Validates that malicious HTML attributes (event handlers, javascript: URLs)
 * are rejected by the addAttributes() method.
 */
class XssHtmlAttributesTest extends SecurityTestCase
{
    /** @test */
    public function it_rejects_onclick_attribute()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['onclick' => 'alert("XSS")']);
    }

    /** @test */
    public function it_rejects_onload_attribute()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['onload' => 'alert("XSS")']);
    }

    /** @test */
    public function it_rejects_onerror_attribute()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['onerror' => 'alert("XSS")']);
    }

    /** @test */
    public function it_rejects_onmouseover_attribute()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['onmouseover' => 'alert("XSS")']);
    }

    /** @test */
    public function it_rejects_all_event_handler_attributes()
    {
        $table = $this->createTableBuilder();

        $eventHandlers = [
            'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover',
            'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup',
            'onload', 'onunload', 'onfocus', 'onblur', 'onchange', 'onsubmit',
            'onreset', 'onselect', 'onerror', 'onabort',
        ];

        foreach ($eventHandlers as $handler) {
            try {
                $table->addAttributes([$handler => 'alert("XSS")']);
                $this->fail("Expected InvalidArgumentException for attribute: {$handler}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid HTML attribute', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_javascript_url_in_href()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['href' => 'javascript:alert("XSS")']);
    }

    /** @test */
    public function it_rejects_javascript_url_in_src()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['src' => 'javascript:alert("XSS")']);
    }

    /** @test */
    public function it_rejects_data_url_in_href()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['href' => 'data:text/html,<script>alert("XSS")</script>']);
    }

    /** @test */
    public function it_rejects_data_url_in_src()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['src' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4=']);
    }

    /** @test */
    public function it_rejects_mixed_case_javascript_url()
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
                $table->addAttributes(['href' => $url]);
                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid HTML attribute', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_mixed_case_data_url()
    {
        $table = $this->createTableBuilder();

        $maliciousUrls = [
            'DaTa:text/html,<script>alert("XSS")</script>',
            'DATA:text/html,<script>alert("XSS")</script>',
            'data:text/html,<script>alert("XSS")</script>',
        ];

        foreach ($maliciousUrls as $url) {
            try {
                $table->addAttributes(['src' => $url]);
                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid HTML attribute', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_javascript_url_with_whitespace()
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
                $table->addAttributes(['href' => $url]);
                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid HTML attribute', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_rejects_javascript_url_with_encoding()
    {
        $table = $this->createTableBuilder();

        $maliciousUrls = [
            'javascript%3Aalert("XSS")', // URL encoded
            '&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert("XSS")', // HTML entity encoded
        ];

        foreach ($maliciousUrls as $url) {
            try {
                $table->addAttributes(['href' => $url]);
                $this->fail("Expected InvalidArgumentException for URL: {$url}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid HTML attribute', $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_accepts_safe_html_attributes()
    {
        $table = $this->createTableBuilder();

        $safeAttributes = [
            'class' => 'table table-striped',
            'id' => 'my-table',
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => 'My Table',
            'role' => 'grid',
            'aria-label' => 'Data table',
        ];

        $result = $table->addAttributes($safeAttributes);

        $this->assertInstanceOf(TableBuilder::class, $result);
    }

    /** @test */
    public function it_accepts_safe_http_urls()
    {
        $table = $this->createTableBuilder();

        $safeUrls = [
            'http://example.com',
            'https://example.com',
            'https://example.com/path?query=value',
            'https://example.com#anchor',
        ];

        foreach ($safeUrls as $url) {
            $result = $table->addAttributes(['href' => $url]);
            $this->assertInstanceOf(TableBuilder::class, $result);
        }
    }

    /** @test */
    public function it_rejects_vbscript_url()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['href' => 'vbscript:msgbox("XSS")']);
    }

    /** @test */
    public function it_rejects_file_url()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTML attribute');

        $table->addAttributes(['href' => 'file:///etc/passwd']);
    }

    /** @test */
    public function it_rejects_multiple_malicious_attributes()
    {
        $table = $this->createTableBuilder();

        $this->expectException(InvalidArgumentException::class);

        $table->addAttributes([
            'class' => 'safe-class',
            'onclick' => 'alert("XSS")',
            'id' => 'safe-id',
        ]);
    }

    /** @test */
    public function it_validates_each_attribute_independently()
    {
        $table = $this->createTableBuilder();

        // First add safe attributes
        $table->addAttributes(['class' => 'table', 'id' => 'my-table']);

        // Then try to add malicious attribute
        $this->expectException(InvalidArgumentException::class);

        $table->addAttributes(['onclick' => 'alert("XSS")']);
    }
}
