<?php

namespace Canvastack\Canvastack\Library\Components\Utility\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

class TableUiAddActionButtonByStringTest extends TestCase
{
    public function testBooleanTrueGeneratesDefaultActions()
    {
        $out = Canvatility::addActionButtonByString(true);
        $this->assertArrayHasKey('view', $out);
        $this->assertSame(['color' => 'success', 'icon' => 'eye'], $out['view']);
        $this->assertArrayHasKey('edit', $out);
        $this->assertSame(['color' => 'primary', 'icon' => 'pencil'], $out['edit']);
        $this->assertArrayHasKey('delete', $out);
        $this->assertSame(['color' => 'danger', 'icon' => 'times'], $out['delete']);
    }

    public function testPipeStringParsesToMap()
    {
        $out = Canvatility::addActionButtonByString('approve|success|check');
        $this->assertArrayHasKey('approve', $out);
        $this->assertSame(['color' => 'success', 'icon' => 'check'], $out['approve']);
    }

    public function testStringWithoutPipeDefaultsToEcho()
    {
        $out = Canvatility::addActionButtonByString('export');
        $this->assertArrayHasKey('export', $out);
        $this->assertSame('export', $out['export']);
    }

    public function testPipeStringWithIsArrayTrueBehavesSame()
    {
        $out = Canvatility::addActionButtonByString('export|default|link', true);
        $this->assertArrayHasKey('export', $out);
        $this->assertSame(['color' => 'default', 'icon' => 'link'], $out['export']);
    }

    public function testSimpleStringWithIsArrayTrueBehavesSame()
    {
        $out = Canvatility::addActionButtonByString('customOnly', true);
        $this->assertArrayHasKey('customOnly', $out);
        $this->assertSame('customOnly', $out['customOnly']);
    }

    public function testBooleanTrueWithIsArrayTrueBehavesSame()
    {
        $out = Canvatility::addActionButtonByString(true, true);
        $this->assertArrayHasKey('view', $out);
        $this->assertSame(['color' => 'success', 'icon' => 'eye'], $out['view']);
        $this->assertArrayHasKey('edit', $out);
        $this->assertSame(['color' => 'primary', 'icon' => 'pencil'], $out['edit']);
        $this->assertArrayHasKey('delete', $out);
        $this->assertSame(['color' => 'danger', 'icon' => 'times'], $out['delete']);
    }
}