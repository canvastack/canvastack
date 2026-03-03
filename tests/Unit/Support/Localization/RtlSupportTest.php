<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\RtlSupport;
use Canvastack\Canvastack\Tests\TestCase;

class RtlSupportTest extends TestCase
{
    protected RtlSupport $rtlSupport;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LocaleManager
        $this->localeManager = $this->createMock(LocaleManager::class);

        $this->rtlSupport = new RtlSupport($this->localeManager);
    }

    public function test_is_rtl_for_arabic()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertTrue($this->rtlSupport->isRtl('ar'));
    }

    public function test_is_rtl_for_english()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertFalse($this->rtlSupport->isRtl('en'));
    }

    public function test_get_direction_ltr()
    {
        $this->localeManager->method('getDirection')->with('en')->willReturn('ltr');

        $this->assertEquals('ltr', $this->rtlSupport->getDirection('en'));
    }

    public function test_get_direction_rtl()
    {
        $this->localeManager->method('getDirection')->with('ar')->willReturn('rtl');

        $this->assertEquals('rtl', $this->rtlSupport->getDirection('ar'));
    }

    public function test_get_opposite_direction_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('rtl', $this->rtlSupport->getOppositeDirection('en'));
    }

    public function test_get_opposite_direction_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('ltr', $this->rtlSupport->getOppositeDirection('ar'));
    }

    public function test_get_start_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('left', $this->rtlSupport->getStart('en'));
    }

    public function test_get_start_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('right', $this->rtlSupport->getStart('ar'));
    }

    public function test_get_end_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('right', $this->rtlSupport->getEnd('en'));
    }

    public function test_get_end_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('left', $this->rtlSupport->getEnd('ar'));
    }

    public function test_get_float_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('left', $this->rtlSupport->getFloat('en'));
    }

    public function test_get_float_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('right', $this->rtlSupport->getFloat('ar'));
    }

    public function test_get_text_align_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('left', $this->rtlSupport->getTextAlign('en'));
    }

    public function test_get_text_align_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('right', $this->rtlSupport->getTextAlign('ar'));
    }

    public function test_get_margin_start_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('margin-left', $this->rtlSupport->getMarginStart('en'));
    }

    public function test_get_margin_start_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('margin-right', $this->rtlSupport->getMarginStart('ar'));
    }

    public function test_get_margin_end_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('margin-right', $this->rtlSupport->getMarginEnd('en'));
    }

    public function test_get_margin_end_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('margin-left', $this->rtlSupport->getMarginEnd('ar'));
    }

    public function test_convert_css_property_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('margin-left', $this->rtlSupport->convertCssProperty('margin-left', 'en'));
    }

    public function test_convert_css_property_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('margin-right', $this->rtlSupport->convertCssProperty('margin-left', 'ar'));
        $this->assertEquals('margin-left', $this->rtlSupport->convertCssProperty('margin-right', 'ar'));
    }

    public function test_get_dir_attribute()
    {
        $this->localeManager->method('getDirection')->with('en')->willReturn('ltr');

        $this->assertEquals('ltr', $this->rtlSupport->getDirAttribute('en'));
    }

    public function test_get_lang_attribute()
    {
        $this->localeManager->method('getLocale')->willReturn('en');

        $this->assertEquals('en', $this->rtlSupport->getLangAttribute());
    }

    public function test_get_rtl_class_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('ltr', $this->rtlSupport->getRtlClass('en'));
    }

    public function test_get_rtl_class_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('rtl', $this->rtlSupport->getRtlClass('ar'));
    }

    public function test_get_tailwind_classes_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $classes = $this->rtlSupport->getTailwindClasses('en');

        $this->assertIsArray($classes);
        $this->assertContains('ltr', $classes);
        $this->assertContains('text-left', $classes);
    }

    public function test_get_tailwind_classes_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $classes = $this->rtlSupport->getTailwindClasses('ar');

        $this->assertIsArray($classes);
        $this->assertContains('rtl', $classes);
        $this->assertContains('text-right', $classes);
    }

    public function test_flip_icon_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $result = $this->rtlSupport->flipIcon('arrow-left', 'en');

        $this->assertEquals('arrow-left', $result);
    }

    public function test_flip_icon_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $result = $this->rtlSupport->flipIcon('arrow-left', 'ar');

        $this->assertStringContainsString('flip-rtl', $result);
    }

    public function test_get_flip_transform_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals('scaleX(1)', $this->rtlSupport->getFlipTransform('en'));
    }

    public function test_get_flip_transform_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals('scaleX(-1)', $this->rtlSupport->getFlipTransform('ar'));
    }

    public function test_get_rotation_angle_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $this->assertEquals(0, $this->rtlSupport->getRotationAngle('en'));
    }

    public function test_get_rotation_angle_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $this->assertEquals(180, $this->rtlSupport->getRotationAngle('ar'));
    }

    public function test_get_logical_property_ltr()
    {
        $this->localeManager->method('isRtl')->with('en')->willReturn(false);

        $result = $this->rtlSupport->getLogicalProperty('margin-start', '10px', 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('margin-left', $result);
        $this->assertEquals('10px', $result['margin-left']);
    }

    public function test_get_logical_property_rtl()
    {
        $this->localeManager->method('isRtl')->with('ar')->willReturn(true);

        $result = $this->rtlSupport->getLogicalProperty('margin-start', '10px', 'ar');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('margin-right', $result);
        $this->assertEquals('10px', $result['margin-right']);
    }
}
