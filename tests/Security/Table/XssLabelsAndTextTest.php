<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Illuminate\Support\Facades\DB;

/**
 * Security Test: XSS Prevention in Labels and Text.
 *
 * Requirements: 24.5, 36.3
 *
 * Validates that all user-provided labels and text are properly
 * HTML-escaped to prevent XSS attacks.
 */
class XssLabelsAndTextTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_data (id INT PRIMARY KEY, name VARCHAR(255), value VARCHAR(255))');
        DB::table('test_data')->insert([
            ['id' => 1, 'name' => 'Test 1', 'value' => '100'],
            ['id' => 2, 'name' => 'Test 2', 'value' => '200'],
        ]);
    }

    /** @test */
    public function it_escapes_script_tags_in_table_label()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->label('<script>alert("XSS")</script>Table Title')
            ->setFields(['id', 'name'])
            ->render();

        // Verify script tag is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_escapes_script_tags_in_column_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<script>alert("XSS")</script>ID',
                'name' => '<img src=x onerror="alert(\'XSS\')">Name',
            ])
            ->render();

        // Verify script and img tags are escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
    }

    /** @test */
    public function it_escapes_html_in_action_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields(['id', 'name'])
            ->setActions([
                [
                    'label' => '<script>alert("XSS")</script>View',
                    'url' => '/view/{id}',
                    'icon' => 'eye',
                ],
            ])
            ->render();

        // Verify script tag in action label is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_escapes_html_in_formula_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields(['id', 'name', 'value'])
            ->formula(
                'calculated',
                '<script>alert("XSS")</script>Calculated',
                ['value'],
                'value * 2'
            )
            ->render();

        // Verify script tag in formula label is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_escapes_html_in_merged_column_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields(['id', 'name', 'value'])
            ->mergeColumns(
                '<script>alert("XSS")</script>Combined',
                ['name', 'value']
            )
            ->render();

        // Verify script tag in merged column label is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_escapes_html_in_relation_labels()
    {
        $table = $this->createTableBuilder();

        // Create related table
        DB::statement('CREATE TABLE related_data (id INT PRIMARY KEY, test_data_id INT, info VARCHAR(255))');

        $html = $table->setName('test_data')
            ->setFields(['id', 'name'])
            ->relations(
                new \stdClass(), // Mock model
                'relatedData',
                'info',
                [],
                '<script>alert("XSS")</script>Related Info'
            )
            ->render();

        // Verify script tag in relation label is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_escapes_html_entities_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '< > & " \' / ID',
                'name' => 'Name & Value',
            ])
            ->render();

        // Verify HTML entities are escaped
        $this->assertStringContainsString('&lt;', $html);
        $this->assertStringContainsString('&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    /** @test */
    public function it_escapes_event_handlers_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<div onclick="alert(\'XSS\')">ID</div>',
                'name' => '<span onmouseover="alert(\'XSS\')">Name</span>',
            ])
            ->render();

        // Verify event handlers are escaped
        $this->assertStringContainsString('&lt;div', $html);
        $this->assertStringContainsString('&lt;span', $html);
        $this->assertStringNotContainsString('<div onclick=', $html);
        $this->assertStringNotContainsString('<span onmouseover=', $html);
    }

    /** @test */
    public function it_escapes_iframe_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->label('<iframe src="javascript:alert(\'XSS\')"></iframe>Table')
            ->setFields(['id', 'name'])
            ->render();

        // Verify iframe is escaped
        $this->assertStringContainsString('&lt;iframe', $html);
        $this->assertStringNotContainsString('<iframe src=', $html);
    }

    /** @test */
    public function it_escapes_svg_with_onload_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<svg onload="alert(\'XSS\')">ID</svg>',
            ])
            ->render();

        // Verify svg is escaped
        $this->assertStringContainsString('&lt;svg', $html);
        $this->assertStringNotContainsString('<svg onload=', $html);
    }

    /** @test */
    public function it_escapes_style_tags_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<style>body{display:none}</style>ID',
            ])
            ->render();

        // Verify style tag is escaped
        $this->assertStringContainsString('&lt;style&gt;', $html);
        $this->assertStringNotContainsString('<style>', $html);
    }

    /** @test */
    public function it_escapes_object_and_embed_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<object data="malicious.swf">ID</object>',
                'name' => '<embed src="malicious.swf">Name</embed>',
            ])
            ->render();

        // Verify object and embed are escaped
        $this->assertStringContainsString('&lt;object', $html);
        $this->assertStringContainsString('&lt;embed', $html);
        $this->assertStringNotContainsString('<object data=', $html);
        $this->assertStringNotContainsString('<embed src=', $html);
    }

    /** @test */
    public function it_escapes_base64_encoded_content_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4=">ID',
            ])
            ->render();

        // Verify img tag is escaped
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img src="data:', $html);
    }

    /** @test */
    public function it_preserves_safe_text_in_labels()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->label('My Safe Table Title')
            ->setFields([
                'id' => 'ID Number',
                'name' => 'Full Name',
                'value' => 'Value (USD)',
            ])
            ->render();

        // Verify safe text is preserved
        $this->assertStringContainsString('My Safe Table Title', $html);
        $this->assertStringContainsString('ID Number', $html);
        $this->assertStringContainsString('Full Name', $html);
        $this->assertStringContainsString('Value (USD)', $html);
    }

    /** @test */
    public function it_escapes_multiple_xss_attempts_in_single_label()
    {
        $table = $this->createTableBuilder();

        $html = $table->setName('test_data')
            ->setFields([
                'id' => '<script>alert(1)</script><img src=x onerror=alert(2)><svg onload=alert(3)>ID',
            ])
            ->render();

        // Verify all XSS attempts are escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('&lt;svg', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload=', $html);
    }
}
