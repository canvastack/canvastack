<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\AssetPathHelper;

class AssetPathHelperTest extends TestCase
{
    public function testHttpAbsoluteUrlPassThrough()
    {
        $this->assertSame('http://ex/a', AssetPathHelper::toPath('http://ex/a', true));
        $this->assertSame('https://ex/a?b=1', AssetPathHelper::toPath('https://ex/a?b=1', true));
    }

    public function testHttpRelativeUsesUtilityOrFallback()
    {
        // In test context, Utility base may be empty -> should return input unchanged
        $this->assertSame('a/b', AssetPathHelper::toPath('a/b', true));
        $this->assertSame('/a/b', AssetPathHelper::toPath('/a/b', true));
    }

    public function testFilesystemMappingFallbacksSafely()
    {
        // Ensure no exception thrown and returns a string even without Laravel app()
        $this->assertIsString(AssetPathHelper::toPath('public/assets/img/x.png', false));
    }
}