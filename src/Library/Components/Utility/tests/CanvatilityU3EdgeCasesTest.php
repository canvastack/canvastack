<?php

namespace Canvastack\Canvastack\Library\Components\Utility\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

class CanvatilityU3EdgeCasesTest extends TestCase
{
    public function testAbsoluteUrlsRemainUnchangedAndTrimmed()
    {
        $this->assertSame('http://example.com/a', Canvatility::checkStringPath('  http://example.com/a  '));
        $this->assertSame('https://example.com/a?b=1#c', Canvatility::checkStringPath('https://example.com/a?b=1#c'));
    }

    public function testRelativePathsReturnInputWhenBaseEmpty()
    {
        // In bare test context, base path is empty, so relative returns trimmed input
        $this->assertSame('a/b', Canvatility::checkStringPath('a/b'));
        $this->assertSame('/a/b', Canvatility::checkStringPath('  /a/b  '));
        $this->assertSame('a/b/', Canvatility::checkStringPath('a/b/'));
    }

    public function testExistCheckOnRelativeWithoutBaseReturnsNull()
    {
        // With existCheck=true and no absolute URL, it should return null (cannot verify existence)
        $this->assertNull(Canvatility::checkStringPath('a/b', true));
    }

    public function testEmptyOrWhitespaceReturnsNull()
    {
        $this->assertNull(Canvatility::checkStringPath(''));
        $this->assertNull(Canvatility::checkStringPath('    '));
    }

    public function testAssetBasePathReturnsString()
    {
        // Might be empty in test context, but should be a string
        $this->assertIsString(Canvatility::assetBasePath());
    }
}