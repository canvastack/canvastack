<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Illuminate\Support\Facades\DB;

/**
 * Security Test: XSS Prevention in Cell Values.
 *
 * Requirements: 24.1, 24.8, 36.3
 *
 * Validates that all cell values are properly HTML-escaped to prevent
 * Cross-Site Scripting (XSS) attacks.
 */
class XssCellValuesTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_content (id INT PRIMARY KEY, title VARCHAR(255), description TEXT)');
    }

    /** @test */
    public function it_escapes_script_tags_in_cell_values()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<script>alert("XSS")</script>',
            'description' => 'Normal text',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title', 'description'])
            ->render();

        // Verify script tag is escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;/script&gt;', $html);

        // Verify raw script tag is NOT present
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    /** @test */
    public function it_escapes_img_tags_with_onerror()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<img src=x onerror="alert(\'XSS\')">',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify img tag is escaped
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('onerror=', $html);

        // Verify raw img tag is NOT present
        $this->assertStringNotContainsString('<img src=x onerror=', $html);
    }

    /** @test */
    public function it_escapes_iframe_tags()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify iframe is escaped
        $this->assertStringContainsString('&lt;iframe', $html);
        $this->assertStringContainsString('&lt;/iframe&gt;', $html);

        // Verify raw iframe is NOT present
        $this->assertStringNotContainsString('<iframe src=', $html);
    }

    /** @test */
    public function it_escapes_svg_with_onload()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<svg onload="alert(\'XSS\')">',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify svg is escaped
        $this->assertStringContainsString('&lt;svg', $html);

        // Verify raw svg is NOT present
        $this->assertStringNotContainsString('<svg onload=', $html);
    }

    /** @test */
    public function it_escapes_anchor_tags_with_javascript()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<a href="javascript:alert(\'XSS\')">Click me</a>',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify anchor is escaped
        $this->assertStringContainsString('&lt;a href=', $html);
        $this->assertStringContainsString('&lt;/a&gt;', $html);

        // Verify raw anchor with javascript is NOT present
        $this->assertStringNotContainsString('<a href="javascript:', $html);
    }

    /** @test */
    public function it_escapes_html_entities()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '< > & " \' /',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify HTML entities are escaped
        $this->assertStringContainsString('&lt;', $html);
        $this->assertStringContainsString('&gt;', $html);
        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    /** @test */
    public function it_escapes_event_handlers_in_text()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<div onclick="alert(\'XSS\')">Click</div>',
            'description' => '<span onmouseover="alert(\'XSS\')">Hover</span>',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title', 'description'])
            ->render();

        // Verify event handlers are escaped
        $this->assertStringContainsString('&lt;div', $html);
        $this->assertStringContainsString('&lt;span', $html);

        // Verify raw event handlers are NOT present
        $this->assertStringNotContainsString('<div onclick=', $html);
        $this->assertStringNotContainsString('<span onmouseover=', $html);
    }

    /** @test */
    public function it_escapes_style_tags_with_javascript()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<style>body{background:url("javascript:alert(\'XSS\')")}</style>',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify style tag is escaped
        $this->assertStringContainsString('&lt;style&gt;', $html);
        $this->assertStringContainsString('&lt;/style&gt;', $html);

        // Verify raw style tag is NOT present
        $this->assertStringNotContainsString('<style>', $html);
    }

    /** @test */
    public function it_escapes_object_and_embed_tags()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<object data="javascript:alert(\'XSS\')"></object>',
            'description' => '<embed src="javascript:alert(\'XSS\')">',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title', 'description'])
            ->render();

        // Verify tags are escaped
        $this->assertStringContainsString('&lt;object', $html);
        $this->assertStringContainsString('&lt;embed', $html);

        // Verify raw tags are NOT present
        $this->assertStringNotContainsString('<object data=', $html);
        $this->assertStringNotContainsString('<embed src=', $html);
    }

    /** @test */
    public function it_escapes_base64_encoded_scripts()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4=">',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify img tag is escaped
        $this->assertStringContainsString('&lt;img', $html);

        // Verify raw img tag is NOT present
        $this->assertStringNotContainsString('<img src="data:', $html);
    }

    /** @test */
    public function it_escapes_unicode_encoded_scripts()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '\u003cscript\u003ealert("XSS")\u003c/script\u003e',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify unicode is escaped or handled safely
        $this->assertStringNotContainsString('<script>', $html);
    }

    /** @test */
    public function it_escapes_html_comments_with_scripts()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<!--<script>alert("XSS")</script>-->',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify comment is escaped
        $this->assertStringContainsString('&lt;!--', $html);
        $this->assertStringContainsString('--&gt;', $html);

        // Verify raw comment is NOT present
        $this->assertStringNotContainsString('<!--<script>', $html);
    }

    /** @test */
    public function it_escapes_meta_tags()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<meta http-equiv="refresh" content="0;url=javascript:alert(\'XSS\')">',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify meta tag is escaped
        $this->assertStringContainsString('&lt;meta', $html);

        // Verify raw meta tag is NOT present
        $this->assertStringNotContainsString('<meta http-equiv=', $html);
    }

    /** @test */
    public function it_escapes_form_tags()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<form action="javascript:alert(\'XSS\')"><input type="submit"></form>',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify form tag is escaped
        $this->assertStringContainsString('&lt;form', $html);
        $this->assertStringContainsString('&lt;/form&gt;', $html);

        // Verify raw form tag is NOT present
        $this->assertStringNotContainsString('<form action=', $html);
    }

    /** @test */
    public function it_preserves_safe_text_content()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => 'This is safe text with numbers 123 and symbols !@#$%',
            'description' => 'Another safe text',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title', 'description'])
            ->render();

        // Verify safe text is preserved
        $this->assertStringContainsString('This is safe text', $html);
        $this->assertStringContainsString('numbers 123', $html);
        $this->assertStringContainsString('Another safe text', $html);
    }

    /** @test */
    public function it_escapes_multiple_xss_attempts_in_single_value()
    {
        DB::table('test_content')->insert([
            'id' => 1,
            'title' => '<script>alert(1)</script><img src=x onerror=alert(2)><svg onload=alert(3)>',
            'description' => 'Test',
        ]);

        $table = $this->createTableBuilder();
        $html = $table->setName('test_content')
            ->setFields(['id', 'title'])
            ->render();

        // Verify all XSS attempts are escaped
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('&lt;svg', $html);

        // Verify no raw XSS is present
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload=', $html);
    }
}
