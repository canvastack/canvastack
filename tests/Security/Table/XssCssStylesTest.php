<?php

namespace Canvastack\Canvastack\Tests\Security\Table;

use Illuminate\Support\Facades\DB;

/**
 * Security Test: XSS Prevention in CSS Styles.
 *
 * Requirements: 24.3, 36.3
 *
 * Validates that CSS styles are sanitized to prevent XSS attacks
 * through javascript: URLs and expression() functions.
 */
class XssCssStylesTest extends SecurityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('CREATE TABLE test_items (id INT PRIMARY KEY, name VARCHAR(255), status VARCHAR(50))');
        DB::table('test_items')->insert([
            ['id' => 1, 'name' => 'Item 1', 'status' => 'active'],
            ['id' => 2, 'name' => 'Item 2', 'status' => 'inactive'],
        ]);
    }

    /** @test */
    public function it_sanitizes_javascript_url_in_background()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'background: url("javascript:alert(\'XSS\')")';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name', 'status'])
            ->columnCondition('status', 'cell', '==', 'active', 'css style', $maliciousStyle)
            ->render();

        // Verify javascript: URL is removed or sanitized
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringNotContainsString('url("javascript:', $html);
    }

    /** @test */
    public function it_sanitizes_expression_function_in_css()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'width: expression(alert("XSS"))';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify expression() is removed or sanitized
        $this->assertStringNotContainsString('expression(alert', $html);
        $this->assertStringNotContainsString('expression(', $html);
    }

    /** @test */
    public function it_sanitizes_behavior_property()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'behavior: url(xss.htc)';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify behavior property is removed
        $this->assertStringNotContainsString('behavior:', $html);
    }

    /** @test */
    public function it_sanitizes_import_with_javascript()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = '@import "javascript:alert(\'XSS\')"';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify @import with javascript is removed
        $this->assertStringNotContainsString('@import "javascript:', $html);
    }

    /** @test */
    public function it_sanitizes_mixed_case_javascript_in_css()
    {
        $table = $this->createTableBuilder();

        $maliciousStyles = [
            'background: url("JaVaScRiPt:alert(\'XSS\')")',
            'background: url("JAVASCRIPT:alert(\'XSS\')")',
            'background: url("javascript:alert(\'XSS\')")',
        ];

        foreach ($maliciousStyles as $style) {
            $html = $table->setName('test_items')
                ->setFields(['id', 'name'])
                ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $style)
                ->render();

            $this->assertStringNotContainsString('javascript:', strtolower($html));
        }
    }

    /** @test */
    public function it_sanitizes_mixed_case_expression_in_css()
    {
        $table = $this->createTableBuilder();

        $maliciousStyles = [
            'width: ExPrEsSiOn(alert("XSS"))',
            'width: EXPRESSION(alert("XSS"))',
            'width: expression(alert("XSS"))',
        ];

        foreach ($maliciousStyles as $style) {
            $html = $table->setName('test_items')
                ->setFields(['id', 'name'])
                ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $style)
                ->render();

            $this->assertStringNotContainsString('expression(', strtolower($html));
        }
    }

    /** @test */
    public function it_sanitizes_vbscript_in_css()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'background: url("vbscript:msgbox(\'XSS\')")';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify vbscript: URL is removed
        $this->assertStringNotContainsString('vbscript:', $html);
    }

    /** @test */
    public function it_sanitizes_data_url_with_script_in_css()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'background: url("data:text/html,<script>alert(\'XSS\')</script>")';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify data: URL with script is removed
        $this->assertStringNotContainsString('data:text/html,<script>', $html);
    }

    /** @test */
    public function it_allows_safe_css_properties()
    {
        $table = $this->createTableBuilder();

        $safeStyles = [
            'color: red',
            'background-color: #FF0000',
            'font-weight: bold',
            'text-align: center',
            'padding: 10px',
            'margin: 5px',
            'border: 1px solid black',
        ];

        foreach ($safeStyles as $style) {
            $html = $table->setName('test_items')
                ->setFields(['id', 'name'])
                ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $style)
                ->render();

            // Should not throw exception and should render
            $this->assertNotEmpty($html);
        }
    }

    /** @test */
    public function it_allows_safe_background_images()
    {
        $table = $this->createTableBuilder();

        $safeStyles = [
            'background: url("/images/bg.png")',
            'background: url("https://example.com/image.jpg")',
            'background: url(\'http://example.com/bg.gif\')',
        ];

        foreach ($safeStyles as $style) {
            $html = $table->setName('test_items')
                ->setFields(['id', 'name'])
                ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $style)
                ->render();

            // Should not throw exception
            $this->assertNotEmpty($html);
        }
    }

    /** @test */
    public function it_sanitizes_css_with_encoded_javascript()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'background: url("&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;alert(\'XSS\')")';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify encoded javascript is sanitized
        $this->assertStringNotContainsString('&#106;&#97;&#118;', $html);
    }

    /** @test */
    public function it_sanitizes_multiple_dangerous_patterns_in_single_style()
    {
        $table = $this->createTableBuilder();

        $maliciousStyle = 'background: url("javascript:alert(1)"); width: expression(alert(2)); behavior: url(xss.htc)';

        $html = $table->setName('test_items')
            ->setFields(['id', 'name'])
            ->columnCondition('name', 'cell', '==', 'Item 1', 'css style', $maliciousStyle)
            ->render();

        // Verify all dangerous patterns are removed
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('expression(', $html);
        $this->assertStringNotContainsString('behavior:', $html);
    }
}
