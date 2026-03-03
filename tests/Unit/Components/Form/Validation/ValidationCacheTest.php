<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Validation;

use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class ValidationCacheTest extends TestCase
{
    protected ValidationCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new ValidationCache();
        Cache::tags(['forms', 'validations'])->flush();
    }

    protected function tearDown(): void
    {
        Cache::tags(['forms', 'validations'])->flush();
        parent::tearDown();
    }

    public function test_can_store_validation_rules(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ];

        $result = $this->cache->put('user-form', $rules);

        $this->assertTrue($result);
    }

    public function test_can_retrieve_validation_rules(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ];

        $this->cache->put('user-form', $rules);
        $retrieved = $this->cache->get('user-form');

        $this->assertEquals($rules, $retrieved);
    }

    public function test_returns_null_for_nonexistent_cache(): void
    {
        $result = $this->cache->get('nonexistent-form');

        $this->assertNull($result);
    }

    public function test_can_check_if_cache_exists(): void
    {
        $rules = ['name' => 'required'];

        $this->cache->put('user-form', $rules);

        $this->assertTrue($this->cache->has('user-form'));
        $this->assertFalse($this->cache->has('nonexistent-form'));
    }

    public function test_can_forget_cached_rules(): void
    {
        $rules = ['name' => 'required'];

        $this->cache->put('user-form', $rules);
        $this->assertTrue($this->cache->has('user-form'));

        $this->cache->forget('user-form');

        $this->assertFalse($this->cache->has('user-form'));
    }

    public function test_can_flush_all_validation_caches(): void
    {
        $this->cache->put('form-1', ['field1' => 'required']);
        $this->cache->put('form-2', ['field2' => 'required']);

        $this->cache->flush();

        $this->assertFalse($this->cache->has('form-1'));
        $this->assertFalse($this->cache->has('form-2'));
    }

    public function test_remember_method_caches_callback_result(): void
    {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;

            return ['name' => 'required'];
        };

        $result1 = $this->cache->remember('user-form', $callback);
        $result2 = $this->cache->remember('user-form', $callback);

        $this->assertEquals(['name' => 'required'], $result1);
        $this->assertEquals(['name' => 'required'], $result2);
        $this->assertEquals(1, $callCount); // Callback should only be called once
    }

    public function test_can_set_custom_ttl(): void
    {
        $rules = ['name' => 'required'];

        $this->cache->put('user-form', $rules, 7200);

        $this->assertTrue($this->cache->has('user-form'));
    }

    public function test_can_set_and_get_default_ttl(): void
    {
        $this->cache->setDefaultTtl(7200);

        $this->assertEquals(7200, $this->cache->getDefaultTtl());
    }

    public function test_default_ttl_is_3600_seconds(): void
    {
        $cache = new ValidationCache();

        $this->assertEquals(3600, $cache->getDefaultTtl());
    }
}
