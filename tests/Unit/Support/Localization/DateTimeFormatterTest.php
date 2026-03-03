<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Localization;

use Canvastack\Canvastack\Support\Localization\DateTimeFormatter;
use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Tests\TestCase;
use Carbon\Carbon;

class DateTimeFormatterTest extends TestCase
{
    protected DateTimeFormatter $formatter;

    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LocaleManager
        $this->localeManager = $this->createMock(LocaleManager::class);
        $this->localeManager->method('getLocale')->willReturn('en');

        $this->formatter = new DateTimeFormatter($this->localeManager);
    }

    public function test_format_date_with_default_format()
    {
        $date = '2024-01-15';
        $result = $this->formatter->formatDate($date);

        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    public function test_format_date_with_custom_format()
    {
        $date = '2024-01-15';
        $result = $this->formatter->formatDate($date, 'd/m/Y');

        $this->assertEquals('15/01/2024', $result);
    }

    public function test_format_date_with_null()
    {
        $result = $this->formatter->formatDate(null);

        $this->assertEquals('', $result);
    }

    public function test_format_time()
    {
        $time = '2024-01-15 14:30:45';
        $result = $this->formatter->formatTime($time);

        $this->assertIsString($result);
        $this->assertStringContainsString('14:30', $result);
    }

    public function test_format_datetime()
    {
        $datetime = '2024-01-15 14:30:45';
        $result = $this->formatter->formatDateTime($datetime);

        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
        $this->assertStringContainsString('14:30', $result);
    }

    public function test_diff_for_humans()
    {
        $date = Carbon::now()->subDays(2);
        $result = $this->formatter->diffForHumans($date);

        $this->assertIsString($result);
        $this->assertStringContainsString('ago', $result);
    }

    public function test_format_long_date()
    {
        $date = '2024-01-15';
        $result = $this->formatter->formatLongDate($date);

        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    public function test_format_short_date()
    {
        $date = '2024-01-15';
        $result = $this->formatter->formatShortDate($date);

        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    public function test_get_day_name()
    {
        $date = '2024-01-15'; // Monday
        $result = $this->formatter->getDayName($date);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_short_day_name()
    {
        $date = '2024-01-15'; // Monday
        $result = $this->formatter->getShortDayName($date);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_month_name()
    {
        $date = '2024-01-15';
        $result = $this->formatter->getMonthName($date);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_get_short_month_name()
    {
        $date = '2024-01-15';
        $result = $this->formatter->getShortMonthName($date);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_parse_date()
    {
        $dateString = '2024-01-15';
        $result = $this->formatter->parseDate($dateString, 'Y-m-d');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024', $result->year);
        $this->assertEquals('01', $result->format('m'));
        $this->assertEquals('15', $result->format('d'));
    }

    public function test_parse_datetime()
    {
        $datetimeString = '2024-01-15 14:30:45';
        $result = $this->formatter->parseDateTime($datetimeString, 'Y-m-d H:i:s');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2024', $result->year);
        $this->assertEquals('14', $result->format('H'));
    }

    public function test_calendar()
    {
        $date = Carbon::now();
        $result = $this->formatter->calendar($date);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_is_today()
    {
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();

        $this->assertTrue($this->formatter->isToday($today));
        $this->assertFalse($this->formatter->isToday($yesterday));
        $this->assertFalse($this->formatter->isToday(null));
    }

    public function test_is_yesterday()
    {
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();

        $this->assertFalse($this->formatter->isYesterday($today));
        $this->assertTrue($this->formatter->isYesterday($yesterday));
        $this->assertFalse($this->formatter->isYesterday(null));
    }

    public function test_is_tomorrow()
    {
        $today = Carbon::now();
        $tomorrow = Carbon::tomorrow();

        $this->assertFalse($this->formatter->isTomorrow($today));
        $this->assertTrue($this->formatter->isTomorrow($tomorrow));
        $this->assertFalse($this->formatter->isTomorrow(null));
    }

    public function test_format_with_timezone()
    {
        $datetime = '2024-01-15 14:30:45';
        $result = $this->formatter->formatWithTimezone($datetime, 'UTC');

        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    public function test_to_carbon_with_carbon_instance()
    {
        $carbon = Carbon::now();
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('toCarbon');
        $method->setAccessible(true);

        $result = $method->invoke($this->formatter, $carbon);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals($carbon, $result);
    }

    public function test_to_carbon_with_datetime()
    {
        $datetime = new \DateTime('2024-01-15');
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('toCarbon');
        $method->setAccessible(true);

        $result = $method->invoke($this->formatter, $datetime);

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_to_carbon_with_string()
    {
        $dateString = '2024-01-15';
        $reflection = new \ReflectionClass($this->formatter);
        $method = $reflection->getMethod('toCarbon');
        $method->setAccessible(true);

        $result = $method->invoke($this->formatter, $dateString);

        $this->assertInstanceOf(Carbon::class, $result);
    }
}
