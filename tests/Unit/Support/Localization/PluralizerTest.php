<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Support\Localization\Pluralizer;
use Canvastack\Canvastack\Tests\TestCase;

class PluralizerTest extends TestCase
{
    protected Pluralizer $pluralizer;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LocaleManager
        $this->localeManager = $this->createMock(LocaleManager::class);
        $this->localeManager->method('getLocale')->willReturn('en');

        $this->pluralizer = new Pluralizer($this->localeManager);
    }

    public function test_english_pluralization_one()
    {
        // Mock translation string
        $line = '{0} No items|{1} One item|[2,*] :count items';

        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('parseSegments');
        $method->setAccessible(true);
        $segments = $method->invoke($this->pluralizer, $line);

        $getSegmentMethod = $reflection->getMethod('getSegment');
        $getSegmentMethod->setAccessible(true);
        $result = $getSegmentMethod->invoke($this->pluralizer, $segments, 1, 'en');

        $this->assertStringContainsString('One item', $result);
    }

    public function test_english_pluralization_many()
    {
        // Mock translation string
        $line = '{0} No items|{1} One item|[2,*] :count items';

        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('parseSegments');
        $method->setAccessible(true);
        $segments = $method->invoke($this->pluralizer, $line);

        $getSegmentMethod = $reflection->getMethod('getSegment');
        $getSegmentMethod->setAccessible(true);
        $result = $getSegmentMethod->invoke($this->pluralizer, $segments, 5, 'en');

        $replacePlaceholdersMethod = $reflection->getMethod('replacePlaceholders');
        $replacePlaceholdersMethod->setAccessible(true);
        $result = $replacePlaceholdersMethod->invoke($this->pluralizer, $result, ['count' => 5]);

        $this->assertStringContainsString('5 items', $result);
    }

    public function test_english_pluralization_zero()
    {
        // Mock translation string
        $line = '{0} No items|{1} One item|[2,*] :count items';

        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('parseSegments');
        $method->setAccessible(true);
        $segments = $method->invoke($this->pluralizer, $line);

        $getSegmentMethod = $reflection->getMethod('getSegment');
        $getSegmentMethod->setAccessible(true);
        $result = $getSegmentMethod->invoke($this->pluralizer, $segments, 0, 'en');

        $this->assertStringContainsString('No items', $result);
    }

    public function test_parse_segments_with_explicit_counts()
    {
        $line = '{0} No items|{1} One item|[2,*] :count items';
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('parseSegments');
        $method->setAccessible(true);

        $segments = $method->invoke($this->pluralizer, $line);

        $this->assertArrayHasKey('0', $segments);
        $this->assertArrayHasKey('1', $segments);
        $this->assertArrayHasKey('2,INF', $segments);
    }

    public function test_parse_segments_simple_format()
    {
        $line = 'No items|One item|Many items';
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('parseSegments');
        $method->setAccessible(true);

        $segments = $method->invoke($this->pluralizer, $line);

        $this->assertCount(3, $segments);
    }

    public function test_get_plural_form_english()
    {
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('getPluralForm');
        $method->setAccessible(true);

        $this->assertEquals('one', $method->invoke($this->pluralizer, 1, 'en'));
        $this->assertEquals('other', $method->invoke($this->pluralizer, 2, 'en'));
        $this->assertEquals('other', $method->invoke($this->pluralizer, 0, 'en'));
    }

    public function test_get_plural_form_indonesian()
    {
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('getPluralForm');
        $method->setAccessible(true);

        // Indonesian has no plural distinction
        $this->assertEquals('other', $method->invoke($this->pluralizer, 1, 'id'));
        $this->assertEquals('other', $method->invoke($this->pluralizer, 2, 'id'));
        $this->assertEquals('other', $method->invoke($this->pluralizer, 0, 'id'));
    }

    public function test_get_plural_form_arabic()
    {
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('getPluralForm');
        $method->setAccessible(true);

        $this->assertEquals('zero', $method->invoke($this->pluralizer, 0, 'ar'));
        $this->assertEquals('one', $method->invoke($this->pluralizer, 1, 'ar'));
        $this->assertEquals('two', $method->invoke($this->pluralizer, 2, 'ar'));
        $this->assertEquals('few', $method->invoke($this->pluralizer, 3, 'ar'));
        $this->assertEquals('many', $method->invoke($this->pluralizer, 11, 'ar'));
        $this->assertEquals('other', $method->invoke($this->pluralizer, 100, 'ar'));
    }

    public function test_get_plural_form_russian()
    {
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('getPluralForm');
        $method->setAccessible(true);

        $this->assertEquals('one', $method->invoke($this->pluralizer, 1, 'ru'));
        $this->assertEquals('one', $method->invoke($this->pluralizer, 21, 'ru'));
        $this->assertEquals('few', $method->invoke($this->pluralizer, 2, 'ru'));
        $this->assertEquals('few', $method->invoke($this->pluralizer, 22, 'ru'));
        $this->assertEquals('many', $method->invoke($this->pluralizer, 5, 'ru'));
        $this->assertEquals('many', $method->invoke($this->pluralizer, 11, 'ru'));
    }

    public function test_replace_placeholders()
    {
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('replacePlaceholders');
        $method->setAccessible(true);

        $result = $method->invoke($this->pluralizer, ':count items', ['count' => 5]);
        $this->assertEquals('5 items', $result);

        $result = $method->invoke($this->pluralizer, ':name has :count items', ['name' => 'John', 'count' => 3]);
        $this->assertEquals('John has 3 items', $result);
    }

    public function test_register_custom_rule()
    {
        $customRule = function (int $count): string {
            return $count === 1 ? 'single' : 'multiple';
        };

        $this->pluralizer->registerRule('custom', $customRule);

        $this->assertTrue($this->pluralizer->hasRule('custom'));
    }

    public function test_has_rule()
    {
        $this->assertTrue($this->pluralizer->hasRule('en'));
        $this->assertTrue($this->pluralizer->hasRule('id'));
        $this->assertTrue($this->pluralizer->hasRule('ar'));
        $this->assertFalse($this->pluralizer->hasRule('nonexistent'));
    }

    public function test_get_plural_forms()
    {
        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('getPluralForms');
        $method->setAccessible(true);

        $forms = $method->invoke($this->pluralizer, 'en');
        $this->assertEquals(['one', 'other'], $forms);

        $forms = $method->invoke($this->pluralizer, 'ar');
        $this->assertEquals(['zero', 'one', 'two', 'few', 'many', 'other'], $forms);

        $forms = $method->invoke($this->pluralizer, 'id');
        $this->assertEquals(['other'], $forms);
    }

    public function test_choice_with_array_count()
    {
        // Test Laravel's trans_choice compatibility
        // Mock translation string
        $line = '{0} No items|{1} One item|[2,*] :count items';

        $reflection = new \ReflectionClass($this->pluralizer);
        $method = $reflection->getMethod('parseSegments');
        $method->setAccessible(true);
        $segments = $method->invoke($this->pluralizer, $line);

        $getSegmentMethod = $reflection->getMethod('getSegment');
        $getSegmentMethod->setAccessible(true);
        $result = $getSegmentMethod->invoke($this->pluralizer, $segments, 5, 'en');

        $replacePlaceholdersMethod = $reflection->getMethod('replacePlaceholders');
        $replacePlaceholdersMethod->setAccessible(true);
        $result = $replacePlaceholdersMethod->invoke($this->pluralizer, $result, ['count' => 5]);

        $this->assertStringContainsString('5', $result);
    }
}
