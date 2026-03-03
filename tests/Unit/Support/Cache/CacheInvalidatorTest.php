<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Cache;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Support\Cache\CacheManager;
use Canvastack\Canvastack\Support\Cache\CacheInvalidator;
use Canvastack\Canvastack\Support\Cache\CacheTags;

/**
 * Test for CacheInvalidator.
 */
class CacheInvalidatorTest extends TestCase
{
    protected CacheManager $cache;
    protected CacheInvalidator $invalidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new CacheManager([
            'driver' => 'file',
            'prefix' => 'test',
            'ttl' => 3600,
            'file' => [
                'path' => sys_get_temp_dir() . '/canvastack-cache-test',
            ],
        ]);

        $this->invalidator = new CacheInvalidator($this->cache);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        parent::tearDown();
    }

    /**
     * Test that invalidator can be instantiated.
     */
    public function test_invalidator_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CacheInvalidator::class, $this->invalidator);
    }

    /**
     * Test that forms cache can be invalidated.
     */
    public function test_forms_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::FORMS])->put('form_key', 'form_value', 60);
        
        $this->assertEquals('form_value', $this->cache->get('form_key'));
        
        $this->invalidator->invalidateForms();
        
        $this->assertNull($this->cache->get('form_key'));
    }

    /**
     * Test that specific form cache can be invalidated.
     */
    public function test_specific_form_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::form('user_form')])->put('user_form_key', 'value', 60);
        $this->cache->tags([CacheTags::form('post_form')])->put('post_form_key', 'value', 60);
        
        $this->invalidator->invalidateForms('user_form');
        
        $this->assertNull($this->cache->get('user_form_key'));
        $this->assertEquals('value', $this->cache->get('post_form_key'));
    }

    /**
     * Test that tables cache can be invalidated.
     */
    public function test_tables_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::TABLES])->put('table_key', 'table_value', 60);
        
        $this->invalidator->invalidateTables();
        
        $this->assertNull($this->cache->get('table_key'));
    }

    /**
     * Test that charts cache can be invalidated.
     */
    public function test_charts_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::CHARTS])->put('chart_key', 'chart_value', 60);
        
        $this->invalidator->invalidateCharts();
        
        $this->assertNull($this->cache->get('chart_key'));
    }

    /**
     * Test that RBAC cache can be invalidated.
     */
    public function test_rbac_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::RBAC])->put('rbac_key', 'rbac_value', 60);
        
        $this->invalidator->invalidateRbac();
        
        $this->assertNull($this->cache->get('rbac_key'));
    }

    /**
     * Test that permissions cache can be invalidated.
     */
    public function test_permissions_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::PERMISSIONS])->put('perm_key', 'perm_value', 60);
        
        $this->invalidator->invalidatePermissions();
        
        $this->assertNull($this->cache->get('perm_key'));
    }

    /**
     * Test that roles cache can be invalidated.
     */
    public function test_roles_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::ROLES])->put('role_key', 'role_value', 60);
        
        $this->invalidator->invalidateRoles();
        
        $this->assertNull($this->cache->get('role_key'));
    }

    /**
     * Test that themes cache can be invalidated.
     */
    public function test_themes_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::THEMES])->put('theme_key', 'theme_value', 60);
        
        $this->invalidator->invalidateThemes();
        
        $this->assertNull($this->cache->get('theme_key'));
    }

    /**
     * Test that locales cache can be invalidated.
     */
    public function test_locales_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::LOCALES])->put('locale_key', 'locale_value', 60);
        
        $this->invalidator->invalidateLocales();
        
        $this->assertNull($this->cache->get('locale_key'));
    }

    /**
     * Test that user cache can be invalidated.
     */
    public function test_user_cache_can_be_invalidated(): void
    {
        $this->cache->tags([CacheTags::user(1)])->put('user_1_key', 'user_value', 60);
        
        $this->invalidator->invalidateUser(1);
        
        $this->assertNull($this->cache->get('user_1_key'));
    }

    /**
     * Test that all cache can be invalidated.
     */
    public function test_all_cache_can_be_invalidated(): void
    {
        $this->cache->put('key1', 'value1', 60);
        $this->cache->put('key2', 'value2', 60);
        
        $this->invalidator->invalidateAll();
        
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    /**
     * Test that cache can be invalidated by custom tags.
     */
    public function test_cache_can_be_invalidated_by_custom_tags(): void
    {
        $this->cache->tags(['custom_tag'])->put('custom_key', 'custom_value', 60);
        
        $this->invalidator->invalidateByTags(['custom_tag']);
        
        $this->assertNull($this->cache->get('custom_key'));
    }
}
