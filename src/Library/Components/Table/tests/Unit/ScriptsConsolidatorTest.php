<?php

namespace Canvastack\Canvastack\Library\Components\Table\tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\ScriptsConsolidator;
use PHPUnit\Framework\TestCase;

class ScriptsConsolidatorTest extends TestCase
{
    public function test_normalize_merges_js_and_add_js_preserving_order(): void
    {
        $input = [
            'css' => ['a.css', 'b.css'],
            'js' => ['a.js'],
            'add_js' => ['inline1', 'inline2'],
        ];

        $out = ScriptsConsolidator::normalize($input);

        $this->assertSame(['a.css', 'b.css'], $out['css']);
        $this->assertSame(['a.js', 'inline1', 'inline2'], $out['js']);
    }

    public function test_normalize_accepts_scalar_values_and_casts_to_arrays(): void
    {
        $input = [
            'css' => 'single.css',
            'js' => 'single.js',
            'add_js' => 'inline',
        ];

        $out = ScriptsConsolidator::normalize($input);

        $this->assertSame(['single.css'], $out['css']);
        $this->assertSame(['single.js', 'inline'], $out['js']);
    }

    public function test_normalize_works_with_only_add_js(): void
    {
        $input = [
            'add_js' => ['inlineOnly1', 'inlineOnly2'],
        ];

        $out = ScriptsConsolidator::normalize($input);

        $this->assertSame([], $out['css']);
        $this->assertSame(['inlineOnly1', 'inlineOnly2'], $out['js']);
    }

    public function test_normalize_empty_returns_empty_arrays(): void
    {
        $out = ScriptsConsolidator::normalize([]);
        $this->assertSame([], $out['css']);
        $this->assertSame([], $out['js']);
    }
}
