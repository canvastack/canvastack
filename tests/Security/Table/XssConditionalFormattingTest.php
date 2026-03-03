<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Illuminate\Support\Facades\DB;

/**
 * Security Test: XSS Prevention in Conditional Formatting.
 *
 * Requirements: 24.6, 36.3
 *
 * Validates that action text in columnCondition() is properly sanitized
 * to prevent XSS attacks through conditional formatting rules.
 */
class XssConditionalFormattingTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_products (id INT PRIMARY KEY, name VARCHAR(255), status VARCHAR(50), price DECIMAL(10,2))');
        DB::table('test_products')->insert([
            ['id' => 1, 'name' => 'Product A', 'status' => 'active', 'price' => 99.99],
            ['id' => 2, 'name' => 'Product B', 'status' => 'inactive', 'price' => 49.99],
            ['id' => 3, 'name' => 'Product C', 'status' => 'pending', 'price' => 149.99],
        ]);
    }

    /** @test */
    public function it_sanitizes_script_tags_in_prefix_action()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<script>alert("XSS")</script>✓ '
            )
            ->render();

        // Verify script tag is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_sanitizes_script_tags_in_suffix_action()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'suffix',
                ' <script>alert("XSS")</script>'
            )
            ->render();

        // Verify script tag is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_sanitizes_script_tags_in_replace_action()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'replace',
                '<script>alert("XSS")</script>ACTIVE'
            )
            ->render();

        // Verify script tag is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_sanitizes_img_tags_with_onerror()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<img src=x onerror="alert(\'XSS\')">'
            )
            ->render();

        // Verify img tag is escaped
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img src=x onerror=', $html);
    }

    /** @test */
    public function it_sanitizes_event_handlers_in_action_text()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<div onclick="alert(\'XSS\')">✓</div>'
            )
            ->render();

        // Verify event handler is escaped
        $this->assertStringContainsString('&lt;div', $html);
        $this->assertStringNotContainsString('<div onclick=', $html);
    }

    /** @test */
    public function it_sanitizes_iframe_in_action_text()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'replace',
                '<iframe src="javascript:alert(\'XSS\')"></iframe>'
            )
            ->render();

        // Verify iframe is escaped
        $this->assertStringContainsString('&lt;iframe', $html);
        $this->assertStringNotContainsString('<iframe src=', $html);
    }

    /** @test */
    public function it_sanitizes_svg_with_onload()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<svg onload="alert(\'XSS\')"><circle/></svg>'
            )
            ->render();

        // Verify svg is escaped
        $this->assertStringContainsString('&lt;svg', $html);
        $this->assertStringNotContainsString('<svg onload=', $html);
    }

    /** @test */
    public function it_sanitizes_anchor_with_javascript_url()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<a href="javascript:alert(\'XSS\')">Click</a>'
            )
            ->render();

        // Verify anchor is escaped
        $this->assertStringContainsString('&lt;a href=', $html);
        $this->assertStringNotContainsString('<a href="javascript:', $html);
    }

    /** @test */
    public function it_sanitizes_style_tags_in_action_text()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<style>body{display:none}</style>'
            )
            ->render();

        // Verify style tag is escaped
        $this->assertStringContainsString('&lt;style&gt;', $html);
        $this->assertStringNotContainsString('<style>', $html);
    }

    /** @test */
    public function it_sanitizes_object_and_embed_tags()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<object data="malicious.swf"></object>'
            )
            ->render();

        // Verify object tag is escaped
        $this->assertStringContainsString('&lt;object', $html);
        $this->assertStringNotContainsString('<object data=', $html);
    }

    /** @test */
    public function it_sanitizes_html_entities_in_action_text()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '< > & " \' / '
            )
            ->render();

        // Verify HTML entities are escaped
        $this->assertStringContainsString('&lt;', $html);
        $this->assertStringContainsString('&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    /** @test */
    public function it_sanitizes_base64_encoded_scripts()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4=">'
            )
            ->render();

        // Verify img tag is escaped
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img src="data:', $html);
    }

    /** @test */
    public function it_sanitizes_prefix_and_suffix_array()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix&suffix',
                [
                    '<script>alert(1)</script>[',
                    ']<script>alert(2)</script>',
                ]
            )
            ->render();

        // Verify both prefix and suffix are escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_allows_safe_text_in_action()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '✓ Active: '
            )
            ->render();

        // Verify safe text is preserved
        $this->assertStringContainsString('✓ Active:', $html);
    }

    /** @test */
    public function it_sanitizes_css_style_action_with_javascript()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'css style',
                'background: url("javascript:alert(\'XSS\')")'
            )
            ->render();

        // Verify javascript: URL is removed from CSS
        $this->assertStringNotContainsString('javascript:alert', $html);
    }

    /** @test */
    public function it_sanitizes_multiple_xss_attempts_in_single_action()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition(
                'status',
                'cell',
                '==',
                'active',
                'prefix',
                '<script>alert(1)</script><img src=x onerror=alert(2)><svg onload=alert(3)>'
            )
            ->render();

        // Verify all XSS attempts are escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('&lt;svg', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload=', $html);
    }

    /** @test */
    public function it_sanitizes_action_text_for_row_target()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_products')
            ->setFields(['id', 'name', 'status', 'price'])
            ->columnCondition(
                'price',
                'row',
                '>',
                100,
                'css style',
                'background: url("javascript:alert(\'XSS\')"); color: red'
            )
            ->render();

        // Verify javascript: URL is removed even for row target
        $this->assertStringNotContainsString('javascript:alert', $html);
    }
}
