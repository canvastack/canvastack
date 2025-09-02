<?php

namespace Canvastack\Canvastack\Library\Components\Utility\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

class CanvatilitySkeletonTest extends TestCase
{
    public function testFacadeMethodsAndU3Behavior()
    {
        // Method presence
        $this->assertTrue(method_exists(Canvatility::class, 'elementValue'));
        $this->assertTrue(method_exists(Canvatility::class, 'assetBasePath'));
        $this->assertTrue(method_exists(Canvatility::class, 'checkStringPath'));

        // Element extraction mirrors legacy behavior
        $html = '<div id="x" class="y">content</div>';
        $this->assertSame('x', Canvatility::elementValue($html, 'div', 'id', false));
        $tag = Canvatility::elementValue($html, 'div', 'id', true);
        $this->assertNotNull($tag);
        $this->assertStringContainsString('<div', $tag);
        $this->assertStringContainsString('id="x"', $tag);

        // Non-match returns null
        $this->assertNull(Canvatility::elementValue('<span data-a="b"></span>', 'div', 'id'));

        // Asset path returns string (may be empty in bare test context)
        $this->assertIsString(Canvatility::assetBasePath());

        // Path resolver mirrors legacy semantics
        $this->assertSame('http://a/b', Canvatility::checkStringPath('http://a/b'));
        $this->assertSame('https://a/b', Canvatility::checkStringPath('https://a/b'));

        // When base empty (tests), non-absolute returns input
        $this->assertSame('a/b', Canvatility::checkStringPath('a/b'));
        $this->assertNull(Canvatility::checkStringPath('   '));
    }
}