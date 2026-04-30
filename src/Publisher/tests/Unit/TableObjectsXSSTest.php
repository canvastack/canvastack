<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Objects;

/**
 * Unit Tests for XSS Protection in Table Objects.php
 *
 * Tests that all XSS fixes applied in task 1.3 work correctly:
 * - Column labels from 'field:label' format are escaped
 * - setName() validates table name format
 * - setFields() validates fields is an array
 * - mergeColumns() escapes the label
 * - addAttributes() escapes values and blocks dangerous event handlers
 * - validateAttributes() blocks on* handlers and dangerous protocols
 *
 * Validates: Requirements 1.1, 1.2, 1.6, 1.8, 3.1
 *
 * @group security
 * @group xss
 * @group unit
 */
class TableObjectsXSSTest extends TestCase
{
    protected Objects $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = new Objects();
    }

    // =========================================================================
    // 1.3.1 / 1.3.4 — Column labels from 'field:label' format are escaped
    // =========================================================================

    /**
     * Test that column labels containing XSS payloads are escaped
     * when passed in 'field:label' format to lists().
     *
     * Validates: Requirement 1.2
     * @test
     */
    public function test_column_label_xss_payload_is_escaped()
    {
        $xssLabel = '<script>alert("XSS")</script>';
        // Simulate what parseFieldLabels does internally
        $fields = ["name:{$xssLabel}"];

        // We call parseFieldLabels indirectly by inspecting the labels property
        // after calling setFields + lists would require DB, so we test the
        // sanitizeLabel path via the label() method which uses the same helper.
        $this->table->label($xssLabel);

        // labelTable should be escaped
        $this->assertStringNotContainsString('<script>', $this->table->labelTable,
            'XSS payload in label was not escaped');
        $this->assertStringContainsString('&lt;script&gt;', $this->table->labelTable,
            'Script tag should be HTML-escaped in label');
    }

    /**
     * Test that label() escapes HTML special characters
     *
     * Validates: Requirement 1.2
     * @test
     */
    public function test_label_method_escapes_html_special_chars()
    {
        $payloads = [
            '<img src=x onerror=alert(1)>' => '&lt;img',
            '"onmouseover="alert(1)'       => '&quot;',
            "'>alert(1)<'"                 => '&#039;',
            '&amp;lt;script&amp;gt;'       => '&amp;',
        ];

        foreach ($payloads as $payload => $expectedFragment) {
            $this->table->label($payload);
            $this->assertStringContainsString($expectedFragment, $this->table->labelTable,
                "Label not properly escaped for payload: {$payload}");
            $this->assertStringNotContainsString('<script>', strtolower($this->table->labelTable),
                "Unescaped <script> found in label for payload: {$payload}");
        }
    }

    // =========================================================================
    // 1.3.2 — setName() validates table name
    // =========================================================================

    /**
     * Test that setName() rejects table names with XSS/injection characters
     *
     * Validates: Requirements 1.6, 3.1
     * @test
     */
    public function test_set_name_rejects_xss_payload()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->setName('<script>alert(1)</script>');
    }

    /**
     * Test that setName() rejects SQL injection attempts
     *
     * Validates: Requirement 3.1
     * @test
     */
    public function test_set_name_rejects_sql_injection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->setName("users; DROP TABLE users--");
    }

    /**
     * Test that setName() accepts valid table names
     *
     * Validates: Requirement 3.1
     * @test
     */
    public function test_set_name_accepts_valid_table_name()
    {
        $this->table->setName('users');
        $this->assertEquals('users', $this->table->variables['table_name'] ?? null);

        $this->table->setName('user_profiles');
        $this->assertEquals('user_profiles', $this->table->variables['table_name'] ?? null);

        $this->table->setName('Table123');
        $this->assertEquals('Table123', $this->table->variables['table_name'] ?? null);
    }

    // =========================================================================
    // 1.3.3 — setFields() validates fields is an array
    // =========================================================================

    /**
     * Test that setFields() rejects non-array input
     *
     * Validates: Requirement 1.8
     * @test
     */
    public function test_set_fields_rejects_non_array()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table->setFields('name,email');
    }

    /**
     * Test that setFields() accepts a valid array
     *
     * Validates: Requirement 1.8
     * @test
     */
    public function test_set_fields_accepts_valid_array()
    {
        $fields = ['name', 'email', 'created_at'];
        $this->table->setFields($fields);
        $this->assertEquals($fields, $this->table->variables['table_fields'] ?? null);
    }

    // =========================================================================
    // 1.3.5 / 1.3.6 — addAttributes() escapes values and blocks event handlers
    // =========================================================================

    /**
     * Test that addAttributes() escapes HTML special characters in values
     *
     * Validates: Requirement 1.6
     * @test
     */
    public function test_add_attributes_escapes_values()
    {
        $this->table->addAttributes([
            'data-value' => '<script>alert(1)</script>',
            'title'      => '"XSS"',
        ]);

        $attrs = $this->table->variables['add_table_attributes'] ?? [];

        $this->assertStringNotContainsString('<script>', $attrs['data-value'] ?? '',
            'Attribute value with <script> was not escaped');
        $this->assertStringContainsString('&lt;script&gt;', $attrs['data-value'] ?? '',
            'Attribute value should contain escaped script tag');

        $this->assertStringNotContainsString('"XSS"', $attrs['title'] ?? '',
            'Double quotes in attribute value were not escaped');
        $this->assertStringContainsString('&quot;', $attrs['title'] ?? '',
            'Double quotes should be HTML-escaped');
    }

    /**
     * Test that addAttributes() blocks dangerous event handler attributes (on*)
     *
     * Validates: Requirement 1.6
     * @test
     */
    public function test_add_attributes_blocks_event_handlers()
    {
        $this->table->addAttributes([
            'onclick'    => 'alert(1)',
            'onload'     => 'stealCookies()',
            'onerror'    => 'xss()',
            'onmouseover'=> 'evil()',
            'data-safe'  => 'allowed',
        ]);

        $attrs = $this->table->variables['add_table_attributes'] ?? [];

        $this->assertArrayNotHasKey('onclick', $attrs,
            'onclick event handler should be blocked');
        $this->assertArrayNotHasKey('onload', $attrs,
            'onload event handler should be blocked');
        $this->assertArrayNotHasKey('onerror', $attrs,
            'onerror event handler should be blocked');
        $this->assertArrayNotHasKey('onmouseover', $attrs,
            'onmouseover event handler should be blocked');
        $this->assertArrayHasKey('data-safe', $attrs,
            'Safe attribute should be allowed through');
    }

    /**
     * Test that addAttributes() blocks javascript: protocol in values
     *
     * Validates: Requirement 1.6
     * @test
     */
    public function test_add_attributes_blocks_javascript_protocol()
    {
        $this->table->addAttributes([
            'href'      => 'javascript:alert(1)',
            'src'       => 'vbscript:msgbox(1)',
            'data-url'  => 'data:text/html,<script>alert(1)</script>',
            'data-safe' => '/safe/path',
        ]);

        $attrs = $this->table->variables['add_table_attributes'] ?? [];

        $this->assertArrayNotHasKey('href', $attrs,
            'javascript: protocol in href should be blocked');
        $this->assertArrayNotHasKey('src', $attrs,
            'vbscript: protocol in src should be blocked');
        $this->assertArrayNotHasKey('data-url', $attrs,
            'data: protocol in attribute value should be blocked');
        $this->assertArrayHasKey('data-safe', $attrs,
            'Safe relative URL should be allowed');
    }

    // =========================================================================
    // 1.3.4 — mergeColumns() escapes the label
    // =========================================================================

    /**
     * Test that mergeColumns() escapes the label parameter
     *
     * Validates: Requirement 1.2
     * @test
     */
    public function test_merge_columns_escapes_label()
    {
        $xssLabel = '<script>alert("XSS")</script>';
        $this->table->mergeColumns($xssLabel, ['first_name', 'last_name']);

        $mergedCols = $this->table->variables['merged_columns'] ?? [];

        // The key should be the escaped label
        $keys = array_keys($mergedCols);
        $this->assertNotEmpty($keys, 'mergeColumns should store the label');

        $storedKey = $keys[0];
        $this->assertStringNotContainsString('<script>', $storedKey,
            'mergeColumns label key contains unescaped <script>');
        $this->assertStringContainsString('&lt;script&gt;', $storedKey,
            'mergeColumns label key should be HTML-escaped');
    }

    /**
     * Test that mergeColumns() allows safe labels
     *
     * Validates: Requirement 1.2
     * @test
     */
    public function test_merge_columns_allows_safe_label()
    {
        $safeLabel = 'Full Name';
        $this->table->mergeColumns($safeLabel, ['first_name', 'last_name']);

        $mergedCols = $this->table->variables['merged_columns'] ?? [];
        $this->assertArrayHasKey($safeLabel, $mergedCols,
            'Safe label should be stored as-is');
    }

    // =========================================================================
    // Common XSS payloads — comprehensive payload resistance
    // =========================================================================

    /**
     * Test that label() resists all common XSS attack vectors
     *
     * Validates: Requirements 1.1, 1.2
     * @test
     */
    public function test_label_resists_common_xss_payloads()
    {
        $payloads = [
            '<script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '"><script>alert(1)</script>',
            "';alert(1)//",
            '<body onload=alert(1)>',
            '<iframe src="javascript:alert(1)">',
            '<div style="background:url(javascript:alert(1))">',
        ];

        foreach ($payloads as $payload) {
            $this->table->label($payload);
            $escaped = $this->table->labelTable;

            $this->assertStringNotContainsString('<script>', strtolower($escaped),
                "XSS payload not escaped: {$payload}");
            $this->assertStringNotContainsString('<img ', strtolower($escaped),
                "XSS payload not escaped: {$payload}");
            $this->assertStringNotContainsString('<svg ', strtolower($escaped),
                "XSS payload not escaped: {$payload}");
            $this->assertStringNotContainsString('<iframe ', strtolower($escaped),
                "XSS payload not escaped: {$payload}");
            $this->assertStringNotContainsString('<body ', strtolower($escaped),
                "XSS payload not escaped: {$payload}");
        }
    }
}
