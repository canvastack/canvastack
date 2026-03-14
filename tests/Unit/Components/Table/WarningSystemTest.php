<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\WarningSystem;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Test for WarningSystem component.
 * 
 * Tests the connection override warning system functionality including:
 * - Configuration reading (enabled/disabled, method)
 * - Warning triggering logic
 * - Log warning method
 * - Message formatting
 * 
 * @covers \Canvastack\Canvastack\Components\Table\WarningSystem
 */
class WarningSystemTest extends TestCase
{
    /**
     * WarningSystem instance.
     */
    protected WarningSystem $warningSystem;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->warningSystem = new WarningSystem();
    }

    /**
     * Test that isEnabled() returns true when warnings are enabled in config.
     *
     * @return void
     */
    public function test_is_enabled_returns_true_when_enabled_in_config(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        
        // Act
        $result = $this->warningSystem->isEnabled();
        
        // Assert
        $this->assertTrue($result, 'isEnabled() should return true when config is true');
    }

    /**
     * Test that isEnabled() returns false when warnings are disabled in config.
     *
     * @return void
     */
    public function test_is_enabled_returns_false_when_disabled_in_config(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', false);
        
        // Act
        $result = $this->warningSystem->isEnabled();
        
        // Assert
        $this->assertFalse($result, 'isEnabled() should return false when config is false');
    }

    /**
     * Test that isEnabled() returns default value when config is not set.
     *
     * @return void
     */
    public function test_is_enabled_returns_default_when_config_not_set(): void
    {
        // Arrange - Set config to a non-existent path to test default
        Config::set('canvastack.table', []); // Clear table config
        
        // Act
        $result = $this->warningSystem->isEnabled();
        
        // Assert
        $this->assertTrue($result, 'isEnabled() should return true (default) when config is not set');
    }

    /**
     * Test that getMethod() returns 'log' when configured.
     *
     * @return void
     */
    public function test_get_method_returns_log_when_configured(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.method', 'log');
        
        // Act
        $result = $this->warningSystem->getMethod();
        
        // Assert
        $this->assertEquals('log', $result, 'getMethod() should return "log" when configured');
    }

    /**
     * Test that getMethod() returns 'toast' when configured.
     *
     * @return void
     */
    public function test_get_method_returns_toast_when_configured(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.method', 'toast');
        
        // Act
        $result = $this->warningSystem->getMethod();
        
        // Assert
        $this->assertEquals('toast', $result, 'getMethod() should return "toast" when configured');
    }

    /**
     * Test that getMethod() returns 'both' when configured.
     *
     * @return void
     */
    public function test_get_method_returns_both_when_configured(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.method', 'both');
        
        // Act
        $result = $this->warningSystem->getMethod();
        
        // Assert
        $this->assertEquals('both', $result, 'getMethod() should return "both" when configured');
    }

    /**
     * Test that getMethod() returns default value when config is not set.
     *
     * @return void
     */
    public function test_get_method_returns_default_when_config_not_set(): void
    {
        // Arrange - Set config to a non-existent path to test default
        Config::set('canvastack.table', []); // Clear table config
        
        // Act
        $result = $this->warningSystem->getMethod();
        
        // Assert
        $this->assertEquals('log', $result, 'getMethod() should return "log" (default) when config is not set');
    }

    /**
     * Test that warnConnectionOverride() does nothing when warnings are disabled.
     *
     * @return void
     */
    public function test_warn_connection_override_does_nothing_when_disabled(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', false);
        Log::shouldReceive('warning')->never();
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            'App\\Models\\User',
            'mysql',
            'pgsql'
        );
        
        // Assert - no exception thrown, Log::warning not called
        $this->assertTrue(true);
    }

    /**
     * Test that warnConnectionOverride() logs warning when method is 'log'.
     *
     * @return void
     */
    public function test_warn_connection_override_logs_when_method_is_log(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'log');
        
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Connection override detected') &&
                       str_contains($message, 'App\\Models\\User') &&
                       str_contains($message, 'mysql') &&
                       str_contains($message, 'pgsql');
            }));
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            'App\\Models\\User',
            'mysql',
            'pgsql'
        );
        
        // Assert - expectations verified by Mockery
        $this->assertTrue(true); // Add assertion to avoid risky test
    }

    /**
     * Test that warnConnectionOverride() logs warning when method is 'both'.
     *
     * @return void
     */
    public function test_warn_connection_override_logs_when_method_is_both(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'both');
        
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Connection override detected');
            }));
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            'App\\Models\\User',
            'mysql',
            'pgsql'
        );
        
        // Assert - expectations verified by Mockery
        $this->assertTrue(true); // Add assertion to avoid risky test
    }

    /**
     * Test that warnConnectionOverride() does not log when method is 'toast'.
     *
     * @return void
     */
    public function test_warn_connection_override_does_not_log_when_method_is_toast(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'toast');
        
        Log::shouldReceive('warning')->never();
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            'App\\Models\\User',
            'mysql',
            'pgsql'
        );
        
        // Assert - expectations verified by Mockery
        $this->assertTrue(true); // Add assertion to avoid risky test
    }

    /**
     * Test that formatMessage() creates properly formatted message.
     *
     * @return void
     */
    public function test_format_message_creates_proper_message(): void
    {
        // Arrange
        $modelClass = 'App\\Models\\User';
        $modelConnection = 'mysql';
        $overrideConnection = 'pgsql';
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('formatMessage');
        $method->setAccessible(true);
        $message = $method->invoke(
            $this->warningSystem,
            $modelClass,
            $modelConnection,
            $overrideConnection
        );
        
        // Assert
        $this->assertStringContainsString('Connection override detected', $message);
        $this->assertStringContainsString('Model: App\\Models\\User', $message);
        $this->assertStringContainsString('Model Connection: mysql', $message);
        $this->assertStringContainsString('Override Connection: pgsql', $message);
        $this->assertStringContainsString('This may cause unexpected behavior', $message);
    }

    /**
     * Test that warning message includes all required context.
     *
     * @return void
     */
    public function test_warning_message_includes_all_context(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'log');
        
        $modelClass = 'App\\Models\\Product';
        $modelConnection = 'mysql_primary';
        $overrideConnection = 'mysql_secondary';
        
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) use ($modelClass, $modelConnection, $overrideConnection) {
                return str_contains($message, $modelClass) &&
                       str_contains($message, $modelConnection) &&
                       str_contains($message, $overrideConnection);
            }));
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            $modelClass,
            $modelConnection,
            $overrideConnection
        );
        
        // Assert - expectations verified by Mockery
        $this->assertTrue(true); // Add assertion to avoid risky test
    }

    /**
     * Test that multiple warning methods can be configured.
     *
     * @return void
     */
    public function test_multiple_warning_methods_supported(): void
    {
        // Test all valid methods
        $validMethods = ['log', 'toast', 'both'];
        
        foreach ($validMethods as $method) {
            // Create a fresh instance for each test to avoid config caching
            $warningSystem = new WarningSystem();
            Config::set('canvastack.table.connection_warning.method', $method);
            $result = $warningSystem->getMethod();
            
            $this->assertEquals(
                $method,
                $result,
                "getMethod() should return '{$method}' when configured"
            );
        }
    }

    /**
     * Test that configuration can be changed at runtime.
     *
     * @return void
     */
    public function test_configuration_can_be_changed_at_runtime(): void
    {
        // Arrange - Start with enabled
        Config::set('canvastack.table.connection_warning.enabled', true);
        $this->assertTrue($this->warningSystem->isEnabled());
        
        // Act - Disable at runtime
        Config::set('canvastack.table.connection_warning.enabled', false);
        
        // Assert - Should reflect new config
        $this->assertFalse($this->warningSystem->isEnabled());
        
        // Act - Change method
        Config::set('canvastack.table.connection_warning.method', 'toast');
        
        // Assert - Should reflect new method
        $this->assertEquals('toast', $this->warningSystem->getMethod());
    }

    /**
     * Test that generateToastScript() returns JavaScript code.
     *
     * @return void
     */
    public function test_generate_toast_script_returns_javascript(): void
    {
        // Arrange
        $message = "Test warning message";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertIsString($script);
        $this->assertStringContainsString('<script>', $script);
        $this->assertStringContainsString('</script>', $script);
        $this->assertStringContainsString('DOMContentLoaded', $script);
        $this->assertStringContainsString('Test warning message', $script);
    }

    /**
     * Test that toast script includes Alpine.js attributes.
     *
     * @return void
     */
    public function test_toast_script_includes_alpine_attributes(): void
    {
        // Arrange
        $message = "Connection override warning";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertStringContainsString('x-data', $script);
        $this->assertStringContainsString('x-show', $script);
        $this->assertStringContainsString('x-transition', $script);
    }

    /**
     * Test that toast script includes DaisyUI alert classes.
     *
     * @return void
     */
    public function test_toast_script_includes_daisyui_classes(): void
    {
        // Arrange
        $message = "Warning message";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertStringContainsString('alert', $script);
        $this->assertStringContainsString('alert-warning', $script);
    }

    /**
     * Test that toast script escapes special characters.
     *
     * @return void
     */
    public function test_toast_script_escapes_special_characters(): void
    {
        // Arrange
        $message = "Warning with 'quotes' and \"double quotes\"";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertStringContainsString("\\'", $script);
        $this->assertStringContainsString('\\"', $script);
    }

    /**
     * Test that toast script handles newlines correctly.
     *
     * @return void
     */
    public function test_toast_script_handles_newlines(): void
    {
        // Arrange
        $message = "Line 1\nLine 2\nLine 3";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert - Check that newlines in the message are escaped
        $this->assertStringContainsString('\\n', $script);
        // The message content should have escaped newlines
        $this->assertStringContainsString('Line 1\\nLine 2\\nLine 3', $script);
    }

    /**
     * Test that warnConnectionOverride() stores toast scripts when method is 'toast'.
     *
     * @return void
     */
    public function test_warn_connection_override_stores_toast_scripts(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'toast');
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            'App\\Models\\User',
            'mysql',
            'pgsql'
        );
        
        $scripts = $this->warningSystem->getToastScripts();
        
        // Assert
        $this->assertIsArray($scripts);
        $this->assertCount(1, $scripts);
        $this->assertStringContainsString('<script>', $scripts[0]);
    }

    /**
     * Test that warnConnectionOverride() stores toast scripts when method is 'both'.
     *
     * @return void
     */
    public function test_warn_connection_override_stores_toast_scripts_when_both(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'both');
        
        Log::shouldReceive('warning')->once();
        
        // Act
        $this->warningSystem->warnConnectionOverride(
            'App\\Models\\User',
            'mysql',
            'pgsql'
        );
        
        $scripts = $this->warningSystem->getToastScripts();
        
        // Assert
        $this->assertCount(1, $scripts);
    }

    /**
     * Test that getToastScripts() returns empty array when no warnings triggered.
     *
     * @return void
     */
    public function test_get_toast_scripts_returns_empty_when_no_warnings(): void
    {
        // Act
        $scripts = $this->warningSystem->getToastScripts();
        
        // Assert
        $this->assertIsArray($scripts);
        $this->assertEmpty($scripts);
    }

    /**
     * Test that renderToastScripts() returns combined scripts.
     *
     * @return void
     */
    public function test_render_toast_scripts_returns_combined_scripts(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'toast');
        
        // Act - Trigger multiple warnings
        $this->warningSystem->warnConnectionOverride('App\\Models\\User', 'mysql', 'pgsql');
        $this->warningSystem->warnConnectionOverride('App\\Models\\Post', 'mysql', 'sqlite');
        
        $rendered = $this->warningSystem->renderToastScripts();
        
        // Assert
        $this->assertIsString($rendered);
        $this->assertStringContainsString('<script>', $rendered);
        $this->assertStringContainsString('User', $rendered);
        $this->assertStringContainsString('Post', $rendered);
    }

    /**
     * Test that toast script includes warning title.
     *
     * @return void
     */
    public function test_toast_script_includes_warning_title(): void
    {
        // Arrange
        $message = "Test message";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertStringContainsString('Connection Override Warning', $script);
    }

    /**
     * Test that toast script includes close button.
     *
     * @return void
     */
    public function test_toast_script_includes_close_button(): void
    {
        // Arrange
        $message = "Test message";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertStringContainsString('btn', $script);
        $this->assertStringContainsString('onclick', $script);
    }

    /**
     * Test that toast script includes auto-dismiss timeout.
     *
     * @return void
     */
    public function test_toast_script_includes_auto_dismiss(): void
    {
        // Arrange
        $message = "Test message";
        
        // Act
        $reflection = new \ReflectionClass($this->warningSystem);
        $method = $reflection->getMethod('generateToastScript');
        $method->setAccessible(true);
        $script = $method->invoke($this->warningSystem, $message);
        
        // Assert
        $this->assertStringContainsString('setTimeout', $script);
        $this->assertStringContainsString('10000', $script); // 10 seconds
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
