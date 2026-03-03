<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\AjaxCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * AjaxCache Model Unit Tests.
 *
 * Tests the AjaxCache model functionality including:
 * - Fillable attributes
 * - Casts
 * - Scopes (valid, expired, byCacheKey, bySource)
 * - Helper methods (isValid, isExpired, getResponseData)
 * - Static methods (deleteExpired, deleteBySourceField, getStatistics)
 */
class AjaxCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the form_ajax_cache table for testing
        Schema::create('form_ajax_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255)->unique();
            $table->string('source_field', 100);
            $table->string('source_value', 255);
            $table->json('response_data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('cache_key', 'idx_cache_key');
            $table->index('expires_at', 'idx_expires_at');
            $table->index(['source_field', 'source_value'], 'idx_source_lookup');
        });
    }

    /**
     * Test that cache entry can be created with all attributes.
     */
    public function test_can_create_cache_entry(): void
    {
        $cache = AjaxCache::create([
            'cache_key' => 'test_key_123',
            'source_field' => 'province_id',
            'source_value' => '1',
            'response_data' => [
                ['value' => '1', 'label' => 'City A'],
                ['value' => '2', 'label' => 'City B'],
            ],
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertDatabaseHas('form_ajax_cache', [
            'cache_key' => 'test_key_123',
            'source_field' => 'province_id',
            'source_value' => '1',
        ]);

        $this->assertIsArray($cache->response_data);
        $this->assertCount(2, $cache->response_data);
    }

    /**
     * Test that response_data is cast to array.
     */
    public function test_response_data_is_cast_to_array(): void
    {
        $cache = AjaxCache::create([
            'cache_key' => 'test_key_456',
            'source_field' => 'category_id',
            'source_value' => '10',
            'response_data' => [
                ['value' => '100', 'label' => 'Subcategory A'],
            ],
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertIsArray($cache->response_data);
        $this->assertEquals('100', $cache->response_data[0]['value']);
        $this->assertEquals('Subcategory A', $cache->response_data[0]['label']);
    }

    /**
     * Test that expires_at is cast to Carbon instance.
     */
    public function test_expires_at_is_cast_to_carbon(): void
    {
        $expiresAt = now()->addMinutes(5);

        $cache = AjaxCache::create([
            'cache_key' => 'test_key_789',
            'source_field' => 'country_id',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => $expiresAt,
        ]);

        $this->assertInstanceOf(Carbon::class, $cache->expires_at);
        $this->assertEquals($expiresAt->timestamp, $cache->expires_at->timestamp);
    }

    /**
     * Test valid scope returns only non-expired entries.
     */
    public function test_valid_scope_returns_non_expired_entries(): void
    {
        // Create valid entry
        AjaxCache::create([
            'cache_key' => 'valid_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        // Create expired entry
        AjaxCache::create([
            'cache_key' => 'expired_key',
            'source_field' => 'test',
            'source_value' => '2',
            'response_data' => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        $validEntries = AjaxCache::valid()->get();

        $this->assertCount(1, $validEntries);
        $this->assertEquals('valid_key', $validEntries->first()->cache_key);
    }

    /**
     * Test expired scope returns only expired entries.
     */
    public function test_expired_scope_returns_expired_entries(): void
    {
        // Create valid entry
        AjaxCache::create([
            'cache_key' => 'valid_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        // Create expired entry
        AjaxCache::create([
            'cache_key' => 'expired_key',
            'source_field' => 'test',
            'source_value' => '2',
            'response_data' => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        $expiredEntries = AjaxCache::expired()->get();

        $this->assertCount(1, $expiredEntries);
        $this->assertEquals('expired_key', $expiredEntries->first()->cache_key);
    }

    /**
     * Test byCacheKey scope finds entry by cache key.
     */
    public function test_by_cache_key_scope_finds_entry(): void
    {
        AjaxCache::create([
            'cache_key' => 'unique_key_123',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        $cache = AjaxCache::byCacheKey('unique_key_123')->first();

        $this->assertNotNull($cache);
        $this->assertEquals('unique_key_123', $cache->cache_key);
    }

    /**
     * Test bySource scope finds entries by source field and value.
     */
    public function test_by_source_scope_finds_entries(): void
    {
        AjaxCache::create([
            'cache_key' => 'key_1',
            'source_field' => 'province_id',
            'source_value' => '10',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        AjaxCache::create([
            'cache_key' => 'key_2',
            'source_field' => 'province_id',
            'source_value' => '20',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        $entries = AjaxCache::bySource('province_id', '10')->get();

        $this->assertCount(1, $entries);
        $this->assertEquals('key_1', $entries->first()->cache_key);
    }

    /**
     * Test isValid method returns true for non-expired entry.
     */
    public function test_is_valid_returns_true_for_non_expired_entry(): void
    {
        $cache = AjaxCache::create([
            'cache_key' => 'test_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertTrue($cache->isValid());
    }

    /**
     * Test isValid method returns false for expired entry.
     */
    public function test_is_valid_returns_false_for_expired_entry(): void
    {
        $cache = AjaxCache::create([
            'cache_key' => 'test_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($cache->isValid());
    }

    /**
     * Test isExpired method returns true for expired entry.
     */
    public function test_is_expired_returns_true_for_expired_entry(): void
    {
        $cache = AjaxCache::create([
            'cache_key' => 'test_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->assertTrue($cache->isExpired());
    }

    /**
     * Test isExpired method returns false for non-expired entry.
     */
    public function test_is_expired_returns_false_for_non_expired_entry(): void
    {
        $cache = AjaxCache::create([
            'cache_key' => 'test_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertFalse($cache->isExpired());
    }

    /**
     * Test getResponseData returns array.
     */
    public function test_get_response_data_returns_array(): void
    {
        $responseData = [
            ['value' => '1', 'label' => 'Option 1'],
            ['value' => '2', 'label' => 'Option 2'],
        ];

        $cache = AjaxCache::create([
            'cache_key' => 'test_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => $responseData,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertEquals($responseData, $cache->getResponseData());
    }

    /**
     * Test deleteExpired removes only expired entries.
     */
    public function test_delete_expired_removes_only_expired_entries(): void
    {
        // Create valid entry
        AjaxCache::create([
            'cache_key' => 'valid_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        // Create expired entries
        AjaxCache::create([
            'cache_key' => 'expired_key_1',
            'source_field' => 'test',
            'source_value' => '2',
            'response_data' => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        AjaxCache::create([
            'cache_key' => 'expired_key_2',
            'source_field' => 'test',
            'source_value' => '3',
            'response_data' => [],
            'expires_at' => now()->subMinutes(10),
        ]);

        $deletedCount = AjaxCache::deleteExpired();

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(1, AjaxCache::count());
        $this->assertDatabaseHas('form_ajax_cache', ['cache_key' => 'valid_key']);
    }

    /**
     * Test deleteBySourceField removes entries for specific source field.
     */
    public function test_delete_by_source_field_removes_entries(): void
    {
        AjaxCache::create([
            'cache_key' => 'key_1',
            'source_field' => 'province_id',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        AjaxCache::create([
            'cache_key' => 'key_2',
            'source_field' => 'province_id',
            'source_value' => '2',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        AjaxCache::create([
            'cache_key' => 'key_3',
            'source_field' => 'category_id',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        $deletedCount = AjaxCache::deleteBySourceField('province_id');

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(1, AjaxCache::count());
        $this->assertDatabaseHas('form_ajax_cache', ['source_field' => 'category_id']);
    }

    /**
     * Test getStatistics returns correct statistics.
     */
    public function test_get_statistics_returns_correct_data(): void
    {
        // Create valid entry
        AjaxCache::create([
            'cache_key' => 'valid_key',
            'source_field' => 'test',
            'source_value' => '1',
            'response_data' => [],
            'expires_at' => now()->addMinutes(5),
        ]);

        // Create expired entry
        AjaxCache::create([
            'cache_key' => 'expired_key',
            'source_field' => 'test',
            'source_value' => '2',
            'response_data' => [],
            'expires_at' => now()->subMinutes(5),
        ]);

        $stats = AjaxCache::getStatistics();

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['valid']);
        $this->assertEquals(1, $stats['expired']);
        $this->assertInstanceOf(Carbon::class, $stats['oldest']);
        $this->assertInstanceOf(Carbon::class, $stats['newest']);
    }
}
