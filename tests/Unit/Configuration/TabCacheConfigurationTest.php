<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Configuration;

use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Tab Cache Configuration.
 *
 * Validates that tab cache configuration is properly loaded and accessible.
 */
class TabCacheConfigurationTest extends TestCase
{
    /**
     * Test that tab cache configuration exists.
     *
     * @return void
     */
    public function test_tab_cache_configuration_exists(): void
    {
        $config = config('canvastack.cache.tab_system');
        
        $this->assertIsArray($config, 'Tab cache configuration should be an array');
        $this->assertNotEmpty($config, 'Tab cache configuration should not be empty');
    }

    /**
     * Test that tab cache can be enabled/disabled.
     *
     * @return void
     */
    public function test_tab_cache_enabled_setting(): void
    {
        $enabled = config('canvastack.cache.tab_system.enabled');
        
        $this->assertIsBool($enabled, 'Tab cache enabled should be boolean');
    }

    /**
     * Test that tab cache TTL is configured.
     *
     * @return void
     */
    public function test_tab_cache_ttl_setting(): void
    {
        $ttl = config('canvastack.cache.tab_system.ttl');
        
        $this->assertIsInt($ttl, 'Tab cache TTL should be integer');
        $this->assertGreaterThan(0, $ttl, 'Tab cache TTL should be positive');
    }

    /**
     * Test that client cache configuration exists.
     *
     * @return void
     */
    public function test_client_cache_configuration_exists(): void
    {
        $clientCache = config('canvastack.cache.tab_system.client_cache');
        
        $this->assertIsArray($clientCache, 'Client cache configuration should be an array');
        $this->assertArrayHasKey('enabled', $clientCache, 'Client cache should have enabled key');
        $this->assertArrayHasKey('storage', $clientCache, 'Client cache should have storage key');
    }

    /**
     * Test that client cache enabled is boolean.
     *
     * @return void
     */
    public function test_client_cache_enabled_is_boolean(): void
    {
        $enabled = config('canvastack.cache.tab_system.client_cache.enabled');
        
        $this->assertIsBool($enabled, 'Client cache enabled should be boolean');
    }

    /**
     * Test that client cache storage is valid.
     *
     * @return void
     */
    public function test_client_cache_storage_is_valid(): void
    {
        $storage = config('canvastack.cache.tab_system.client_cache.storage');
        
        $this->assertIsString($storage, 'Client cache storage should be string');
        $this->assertContains(
            $storage,
            ['memory', 'sessionStorage'],
            'Client cache storage should be memory or sessionStorage'
        );
    }

    /**
     * Test that server cache configuration exists.
     *
     * @return void
     */
    public function test_server_cache_configuration_exists(): void
    {
        $serverCache = config('canvastack.cache.tab_system.server_cache');
        
        $this->assertIsArray($serverCache, 'Server cache configuration should be an array');
        $this->assertArrayHasKey('enabled', $serverCache, 'Server cache should have enabled key');
        $this->assertArrayHasKey('driver', $serverCache, 'Server cache should have driver key');
        $this->assertArrayHasKey('prefix', $serverCache, 'Server cache should have prefix key');
    }

    /**
     * Test that server cache enabled is boolean.
     *
     * @return void
     */
    public function test_server_cache_enabled_is_boolean(): void
    {
        $enabled = config('canvastack.cache.tab_system.server_cache.enabled');
        
        $this->assertIsBool($enabled, 'Server cache enabled should be boolean');
    }

    /**
     * Test that server cache prefix is string.
     *
     * @return void
     */
    public function test_server_cache_prefix_is_string(): void
    {
        $prefix = config('canvastack.cache.tab_system.server_cache.prefix');
        
        $this->assertIsString($prefix, 'Server cache prefix should be string');
        $this->assertNotEmpty($prefix, 'Server cache prefix should not be empty');
    }

    /**
     * Test that invalidation configuration exists.
     *
     * @return void
     */
    public function test_invalidation_configuration_exists(): void
    {
        $invalidation = config('canvastack.cache.tab_system.invalidation');
        
        $this->assertIsArray($invalidation, 'Invalidation configuration should be an array');
        $this->assertArrayHasKey('auto', $invalidation, 'Invalidation should have auto key');
        $this->assertArrayHasKey('events', $invalidation, 'Invalidation should have events key');
        $this->assertArrayHasKey('methods', $invalidation, 'Invalidation should have methods key');
    }

    /**
     * Test that auto invalidation is boolean.
     *
     * @return void
     */
    public function test_auto_invalidation_is_boolean(): void
    {
        $auto = config('canvastack.cache.tab_system.invalidation.auto');
        
        $this->assertIsBool($auto, 'Auto invalidation should be boolean');
    }

    /**
     * Test that invalidation events is array.
     *
     * @return void
     */
    public function test_invalidation_events_is_array(): void
    {
        $events = config('canvastack.cache.tab_system.invalidation.events');
        
        $this->assertIsArray($events, 'Invalidation events should be an array');
    }

    /**
     * Test that invalidation methods configuration exists.
     *
     * @return void
     */
    public function test_invalidation_methods_configuration_exists(): void
    {
        $methods = config('canvastack.cache.tab_system.invalidation.methods');
        
        $this->assertIsArray($methods, 'Invalidation methods should be an array');
        $this->assertArrayHasKey('on_save', $methods, 'Methods should have on_save key');
        $this->assertArrayHasKey('on_delete', $methods, 'Methods should have on_delete key');
        $this->assertArrayHasKey('on_request', $methods, 'Methods should have on_request key');
    }

    /**
     * Test that key generation configuration exists.
     *
     * @return void
     */
    public function test_key_generation_configuration_exists(): void
    {
        $keyGeneration = config('canvastack.cache.tab_system.key_generation');
        
        $this->assertIsArray($keyGeneration, 'Key generation configuration should be an array');
        $this->assertArrayHasKey('include', $keyGeneration, 'Key generation should have include key');
        $this->assertArrayHasKey('format', $keyGeneration, 'Key generation should have format key');
    }

    /**
     * Test that key generation include configuration is valid.
     *
     * @return void
     */
    public function test_key_generation_include_is_valid(): void
    {
        $include = config('canvastack.cache.tab_system.key_generation.include');
        
        $this->assertIsArray($include, 'Key generation include should be an array');
        $this->assertArrayHasKey('table_id', $include, 'Include should have table_id key');
        $this->assertArrayHasKey('tab_index', $include, 'Include should have tab_index key');
        $this->assertArrayHasKey('user_id', $include, 'Include should have user_id key');
        $this->assertArrayHasKey('filters', $include, 'Include should have filters key');
        $this->assertArrayHasKey('sorting', $include, 'Include should have sorting key');
        $this->assertArrayHasKey('pagination', $include, 'Include should have pagination key');
        
        // All values should be boolean
        foreach ($include as $key => $value) {
            $this->assertIsBool($value, "Include {$key} should be boolean");
        }
    }

    /**
     * Test that key generation format is string.
     *
     * @return void
     */
    public function test_key_generation_format_is_string(): void
    {
        $format = config('canvastack.cache.tab_system.key_generation.format');
        
        $this->assertIsString($format, 'Key generation format should be string');
        $this->assertNotEmpty($format, 'Key generation format should not be empty');
        $this->assertStringContainsString('{prefix}', $format, 'Format should contain {prefix} placeholder');
        $this->assertStringContainsString('{table_id}', $format, 'Format should contain {table_id} placeholder');
        $this->assertStringContainsString('{tab_index}', $format, 'Format should contain {tab_index} placeholder');
    }

    /**
     * Test that monitoring configuration exists.
     *
     * @return void
     */
    public function test_monitoring_configuration_exists(): void
    {
        $monitoring = config('canvastack.cache.tab_system.monitoring');
        
        $this->assertIsArray($monitoring, 'Monitoring configuration should be an array');
        $this->assertArrayHasKey('enabled', $monitoring, 'Monitoring should have enabled key');
        $this->assertArrayHasKey('log_hits', $monitoring, 'Monitoring should have log_hits key');
        $this->assertArrayHasKey('log_misses', $monitoring, 'Monitoring should have log_misses key');
        $this->assertArrayHasKey('track_ratio', $monitoring, 'Monitoring should have track_ratio key');
    }

    /**
     * Test that monitoring settings are boolean.
     *
     * @return void
     */
    public function test_monitoring_settings_are_boolean(): void
    {
        $monitoring = config('canvastack.cache.tab_system.monitoring');
        
        $this->assertIsBool($monitoring['enabled'], 'Monitoring enabled should be boolean');
        $this->assertIsBool($monitoring['log_hits'], 'Monitoring log_hits should be boolean');
        $this->assertIsBool($monitoring['log_misses'], 'Monitoring log_misses should be boolean');
        $this->assertIsBool($monitoring['track_ratio'], 'Monitoring track_ratio should be boolean');
    }

    /**
     * Test that tabs cache tag exists in main cache configuration.
     *
     * @return void
     */
    public function test_tabs_cache_tag_exists(): void
    {
        $tags = config('canvastack.cache.tags');
        
        $this->assertIsArray($tags, 'Cache tags should be an array');
        $this->assertArrayHasKey('tabs', $tags, 'Cache tags should have tabs key');
        $this->assertEquals('canvastack:tabs', $tags['tabs'], 'Tabs cache tag should be canvastack:tabs');
    }

    /**
     * Test that tabs TTL exists in main cache configuration.
     *
     * @return void
     */
    public function test_tabs_ttl_exists_in_main_cache(): void
    {
        $ttl = config('canvastack.cache.ttl');
        
        $this->assertIsArray($ttl, 'Cache TTL should be an array');
        $this->assertArrayHasKey('tabs', $ttl, 'Cache TTL should have tabs key');
        $this->assertIsInt($ttl['tabs'], 'Tabs TTL should be integer');
        $this->assertGreaterThan(0, $ttl['tabs'], 'Tabs TTL should be positive');
    }

    /**
     * Test that environment variables can override configuration.
     *
     * @return void
     */
    public function test_environment_variables_override_configuration(): void
    {
        // Set environment variable
        putenv('CANVASTACK_TAB_CACHE_ENABLED=false');
        
        // Reload configuration
        config(['canvastack.cache.tab_system.enabled' => env('CANVASTACK_TAB_CACHE_ENABLED', true)]);
        
        $enabled = config('canvastack.cache.tab_system.enabled');
        
        $this->assertFalse($enabled, 'Environment variable should override configuration');
        
        // Clean up
        putenv('CANVASTACK_TAB_CACHE_ENABLED');
    }

    /**
     * Test that default values are sensible.
     *
     * @return void
     */
    public function test_default_values_are_sensible(): void
    {
        // Cache should be enabled by default
        $this->assertTrue(
            config('canvastack.cache.tab_system.enabled'),
            'Tab cache should be enabled by default'
        );
        
        // Client cache should be enabled by default
        $this->assertTrue(
            config('canvastack.cache.tab_system.client_cache.enabled'),
            'Client cache should be enabled by default'
        );
        
        // Server cache should be enabled by default
        $this->assertTrue(
            config('canvastack.cache.tab_system.server_cache.enabled'),
            'Server cache should be enabled by default'
        );
        
        // Auto invalidation should be enabled by default
        $this->assertTrue(
            config('canvastack.cache.tab_system.invalidation.auto'),
            'Auto invalidation should be enabled by default'
        );
        
        // TTL should be reasonable (10 minutes = 600 seconds)
        $this->assertEquals(
            600,
            config('canvastack.cache.tab_system.ttl'),
            'Default TTL should be 600 seconds (10 minutes)'
        );
    }

    /**
     * Test that configuration is backward compatible.
     *
     * @return void
     */
    public function test_configuration_is_backward_compatible(): void
    {
        // Existing cache configuration should still exist
        $this->assertNotNull(config('canvastack.cache.enabled'), 'Main cache enabled should exist');
        $this->assertNotNull(config('canvastack.cache.driver'), 'Main cache driver should exist');
        $this->assertNotNull(config('canvastack.cache.ttl'), 'Main cache TTL should exist');
        $this->assertNotNull(config('canvastack.cache.tags'), 'Main cache tags should exist');
        
        // New tab cache configuration should not break existing configuration
        $this->assertIsArray(config('canvastack.cache.ttl'), 'Cache TTL should still be an array');
        $this->assertIsArray(config('canvastack.cache.tags'), 'Cache tags should still be an array');
    }
}
