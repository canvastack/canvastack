<?php

namespace Tests\Utility;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

class AttributesToStringTest extends TestCase
{
    public function test_array_to_string_basic()
    {
        $out = Canvatility::attributesToString(['id' => 'tbl', 'class' => 'x']);
        $this->assertSame('id="tbl" class="x"', $out);
    }

    public function test_boolean_true_outputs_bare_attribute()
    {
        $out = Canvatility::attributesToString(['disabled' => true, 'data-x' => '1']);
        $this->assertSame('disabled data-x="1"', $out);
    }

    public function test_boolean_false_and_null_are_skipped()
    {
        $out = Canvatility::attributesToString(['hidden' => false, 'title' => null, 'data' => 'ok']);
        $this->assertSame('data="ok"', $out);
    }

    public function test_scalar_is_returned_trimmed()
    {
        $out = Canvatility::attributesToString('  id="a"  ');
        $this->assertSame('id="a"', $out);
    }
}