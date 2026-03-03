<?php

declare(strict_types=1);

namespace Canvastack\Tests\Unit\Support\Config;

use Canvastack\Canvastack\Support\Config\ConfigValidator;
use Canvastack\Canvastack\Tests\TestCase;

class ConfigValidatorTest extends TestCase
{
    protected ConfigValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ConfigValidator();
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $result = $this->validator->validate('app', [
            'name' => '',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    /** @test */
    public function it_validates_string_fields()
    {
        $result = $this->validator->validate('app', [
            'name' => 123, // Should be string
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    /** @test */
    public function it_validates_integer_fields()
    {
        $result = $this->validator->validate('performance', [
            'chunk_size' => 'not-a-number',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('chunk_size', $result['errors']);
    }

    /** @test */
    public function it_validates_boolean_fields()
    {
        $result = $this->validator->validate('app', [
            'maintenance' => 'not-a-boolean',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('maintenance', $result['errors']);
    }

    /** @test */
    public function it_validates_url_fields()
    {
        $result = $this->validator->validate('app', [
            'base_url' => 'not-a-url',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('base_url', $result['errors']);
    }

    /** @test */
    public function it_validates_max_length()
    {
        $result = $this->validator->validate('app', [
            'name' => str_repeat('a', 300), // Max is 255
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('name', $result['errors']);
    }

    /** @test */
    public function it_validates_min_value()
    {
        $result = $this->validator->validate('performance', [
            'chunk_size' => 5, // Min is 10
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('chunk_size', $result['errors']);
    }

    /** @test */
    public function it_validates_max_value()
    {
        $result = $this->validator->validate('performance', [
            'chunk_size' => 2000, // Max is 1000
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('chunk_size', $result['errors']);
    }

    /** @test */
    public function it_validates_size()
    {
        $result = $this->validator->validate('app', [
            'lang' => 'eng', // Should be 2 characters
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('lang', $result['errors']);
    }

    /** @test */
    public function it_validates_in_rule()
    {
        $result = $this->validator->validate('cache', [
            'driver' => 'invalid', // Should be redis, file, or array
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('driver', $result['errors']);
    }

    /** @test */
    public function it_passes_valid_settings()
    {
        $result = $this->validator->validate('app', [
            'name' => 'Valid App Name',
            'description' => 'Valid description',
            'base_url' => 'https://example.com',
            'lang' => 'en',
            'maintenance' => false,
        ]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_skips_unknown_keys()
    {
        $result = $this->validator->validate('app', [
            'unknown_key' => 'value',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_returns_error_for_unknown_group()
    {
        $result = $this->validator->validate('unknown_group', [
            'key' => 'value',
        ]);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('group', $result['errors']);
    }

    /** @test */
    public function it_can_add_custom_rules()
    {
        $this->validator->addRule('custom_group', 'custom_key', ['required', 'string']);

        $rules = $this->validator->getRules('custom_group');

        $this->assertArrayHasKey('custom_key', $rules);
        $this->assertEquals(['required', 'string'], $rules['custom_key']);
    }

    /** @test */
    public function it_can_get_rules_for_group()
    {
        $rules = $this->validator->getRules('app');

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
    }

    /** @test */
    public function it_returns_empty_array_for_unknown_group_rules()
    {
        $rules = $this->validator->getRules('unknown_group');

        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }

    /** @test */
    public function it_validates_multiple_errors()
    {
        $result = $this->validator->validate('app', [
            'name' => '', // Required
            'base_url' => 'not-a-url', // Invalid URL
            'lang' => 'eng', // Wrong size
        ]);

        $this->assertFalse($result['valid']);
        $this->assertCount(3, $result['errors']);
        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('base_url', $result['errors']);
        $this->assertArrayHasKey('lang', $result['errors']);
    }

    /** @test */
    public function it_accepts_boolean_as_string()
    {
        $result = $this->validator->validate('app', [
            'maintenance' => 'true',
        ]);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_accepts_integer_as_string()
    {
        $result = $this->validator->validate('performance', [
            'chunk_size' => '100',
        ]);

        $this->assertTrue($result['valid']);
    }
}
