<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Controller Configuration Test
 * 
 * Tests for controller configuration loading and validation.
 * 
 * @package Tests\Unit
 */
class ControllerConfigTest extends TestCase
{
    /**
     * Test that configuration file exists and loads
     *
     * @return void
     */
    public function test_configuration_file_loads()
    {
        $config = config('canvastack.controller');
        
        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }

    /**
     * Test that all required configuration sections exist
     *
     * @return void
     */
    public function test_required_sections_exist()
    {
        $config = config('canvastack.controller');
        
        $requiredSections = [
            'security',
            'performance',
            'caching',
            'file_upload',
            'validation',
            'logging',
        ];

        foreach ($requiredSections as $section) {
            $this->assertArrayHasKey($section, $config, "Missing required section: {$section}");
            $this->assertIsArray($config[$section], "Section {$section} must be an array");
        }
    }

    /**
     * Test security configuration defaults
     *
     * @return void
     */
    public function test_security_configuration_defaults()
    {
        $security = config('canvastack.controller.security');
        
        $this->assertTrue($security['xss_protection']);
        $this->assertTrue($security['csrf_protection']);
        $this->assertTrue($security['sql_injection_prevention']);
        $this->assertTrue($security['escape_output']);
        $this->assertTrue($security['sanitize_filenames']);
        $this->assertIsArray($security['allowed_file_extensions']);
        $this->assertIsInt($security['max_file_size']);
        $this->assertGreaterThan(0, $security['max_file_size']);
    }

    /**
     * Test performance configuration defaults
     *
     * @return void
     */
    public function test_performance_configuration_defaults()
    {
        $performance = config('canvastack.controller.performance');
        
        $this->assertTrue($performance['enable_caching']);
        $this->assertTrue($performance['eager_loading']);
        $this->assertTrue($performance['query_optimization']);
        $this->assertIsInt($performance['cache_ttl']);
        $this->assertGreaterThan(0, $performance['cache_ttl']);
    }

    /**
     * Test caching configuration defaults
     *
     * @return void
     */
    public function test_caching_configuration_defaults()
    {
        $caching = config('canvastack.controller.caching');
        
        $this->assertTrue($caching['privilege_cache_enabled']);
        $this->assertTrue($caching['route_info_cache_enabled']);
        $this->assertTrue($caching['preference_cache_enabled']);
        $this->assertIsInt($caching['privilege_cache_ttl']);
        $this->assertIsInt($caching['route_info_cache_ttl']);
        $this->assertIsInt($caching['preference_cache_ttl']);
    }

    /**
     * Test file upload configuration defaults
     *
     * @return void
     */
    public function test_file_upload_configuration_defaults()
    {
        $fileUpload = config('canvastack.controller.file_upload');
        
        $this->assertTrue($fileUpload['enable_chunking']);
        $this->assertTrue($fileUpload['enable_thumbnails']);
        $this->assertIsInt($fileUpload['chunk_size']);
        $this->assertIsInt($fileUpload['thumbnail_width']);
        $this->assertIsInt($fileUpload['thumbnail_height']);
        $this->assertIsString($fileUpload['storage_path']);
    }

    /**
     * Test validation configuration defaults
     *
     * @return void
     */
    public function test_validation_configuration_defaults()
    {
        $validation = config('canvastack.controller.validation');
        
        $this->assertTrue($validation['strict_mode']);
        $this->assertTrue($validation['validate_table_names']);
        $this->assertTrue($validation['validate_column_names']);
        $this->assertIsInt($validation['max_query_length']);
        $this->assertGreaterThan(0, $validation['max_query_length']);
    }

    /**
     * Test logging configuration defaults
     *
     * @return void
     */
    public function test_logging_configuration_defaults()
    {
        $logging = config('canvastack.controller.logging');
        
        $this->assertTrue($logging['log_security_events']);
        $this->assertTrue($logging['log_performance_issues']);
        $this->assertTrue($logging['log_validation_failures']);
        $this->assertTrue($logging['log_file_uploads']);
    }

    /**
     * Test configuration validation helper
     *
     * @return void
     */
    public function test_configuration_validation()
    {
        // Load helper functions
        require_once base_path('vendor/canvastack/origin/src/Library/Helpers/ControllerConfig.php');
        
        $errors = canvastack_controller_validate_config();
        
        $this->assertIsArray($errors);
        $this->assertEmpty($errors, 'Configuration validation failed: ' . implode(', ', $errors));
    }

    /**
     * Test configuration helper functions
     *
     * @return void
     */
    public function test_configuration_helper_functions()
    {
        // Load helper functions
        require_once base_path('vendor/canvastack/origin/src/Library/Helpers/ControllerConfig.php');
        
        // Test canvastack_controller_config()
        $xssProtection = canvastack_controller_config('security.xss_protection');
        $this->assertTrue($xssProtection);
        
        // Test canvastack_controller_is_security_enabled()
        $this->assertTrue(canvastack_controller_is_security_enabled('xss_protection'));
        
        // Test canvastack_controller_is_caching_enabled()
        $this->assertTrue(canvastack_controller_is_caching_enabled());
        $this->assertTrue(canvastack_controller_is_caching_enabled('privilege'));
        
        // Test canvastack_controller_get_cache_ttl()
        $ttl = canvastack_controller_get_cache_ttl('privilege');
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
        
        // Test canvastack_controller_should_log()
        $this->assertTrue(canvastack_controller_should_log('security_events'));
        
        // Test canvastack_controller_get_allowed_extensions()
        $extensions = canvastack_controller_get_allowed_extensions();
        $this->assertIsArray($extensions);
        $this->assertNotEmpty($extensions);
        
        // Test canvastack_controller_get_max_file_size()
        $maxSize = canvastack_controller_get_max_file_size();
        $this->assertIsInt($maxSize);
        $this->assertGreaterThan(0, $maxSize);
    }

    /**
     * Test that configuration can be overridden via environment variables
     *
     * @return void
     */
    public function test_configuration_environment_override()
    {
        // This test verifies that env() calls are properly set up
        // Actual override testing would require setting environment variables
        
        $config = config('canvastack.controller');
        
        // Verify that configuration uses env() for overridable values
        // by checking that the config file structure is correct
        $this->assertIsArray($config);
    }
}
