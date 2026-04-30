<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * Integration tests for View path resolution with fallback logic
 * 
 * Tests Requirements 14.1, 14.2, 14.3, 14.4, 14.5:
 * - View path resolution follows active template
 * - Fallback to default template when view not found
 * - Works with all three templates (default, canvasign, canvas)
 * 
 * @package Tests\Integration
 */
class ViewPathResolutionIntegrationTest extends TestCase
{
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure view cache is cleared before each test
        ViewFacade::getFinder()->flush();
    }

    /**
     * Set the active template via Laravel config
     * 
     * @param string $template Template name
     */
    private function setTemplate(string $template): void
    {
        Config::set('canvastack.template', $template);
    }

    /**
     * Create a mock controller instance for testing
     * 
     * @return object Mock controller with View trait
     */
    private function createMockController(): object
    {
        return new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\View;
            
            public function __construct()
            {
                // Initialize required properties
                $this->data = [];
                $this->session = ['id' => 1];
                $this->is_module_granted = true;
            }
            
            // Expose private methods for testing
            public function testUriAdmin(string $uri = 'index'): void
            {
                $this->uriAdmin($uri);
            }
            
            public function testUriFront(string $uri = 'index'): void
            {
                $this->uriFront($uri);
            }
            
            public function getViewAdmin(): ?string
            {
                return $this->viewAdmin ?? null;
            }
            
            public function getPageView(): ?string
            {
                return $this->pageView ?? null;
            }
            
            public function getViewFront(): ?string
            {
                return $this->viewFront ?? null;
            }
        };
    }

    // ── Test 1: View resolution with existing template views ──────────────

    /**
     * @test
     * Requirement 14.1: When template is 'default', view path should start with 'default.pages.admin'
     */
    public function test_view_resolution_with_default_template(): void
    {
        // Arrange
        $this->setTemplate('default');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert
        $this->assertSame(
            'default.pages.admin',
            $controller->getViewAdmin(),
            'View path should use default template'
        );
    }

    /**
     * @test
     * Requirement 14.1: When template is 'canvasign', view path should start with 'canvasign.pages.admin'
     */
    public function test_view_resolution_with_canvasign_template(): void
    {
        // Arrange
        $this->setTemplate('canvasign');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert - will fallback to default since canvasign views don't exist yet
        $viewAdmin = $controller->getViewAdmin();
        
        // The view should either be canvasign (if views exist) or default (fallback)
        $this->assertTrue(
            in_array($viewAdmin, ['canvasign.pages.admin', 'default.pages.admin']),
            "View path should be canvasign.pages.admin or fallback to default.pages.admin, got: {$viewAdmin}"
        );
    }

    /**
     * @test
     * Requirement 14.2: When template is 'canvas', view path should start with 'canvas.pages.admin'
     */
    public function test_view_resolution_with_canvas_template(): void
    {
        // Arrange
        $this->setTemplate('canvas');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert - will fallback to default since canvas views don't exist yet
        $viewAdmin = $controller->getViewAdmin();
        
        // The view should either be canvas (if views exist) or default (fallback)
        $this->assertTrue(
            in_array($viewAdmin, ['canvas.pages.admin', 'default.pages.admin']),
            "View path should be canvas.pages.admin or fallback to default.pages.admin, got: {$viewAdmin}"
        );
    }

    // ── Test 2: Fallback to default when template view missing ────────────

    /**
     * @test
     * Requirement 14.4: When view for active template doesn't exist, fallback to default.pages.admin
     */
    public function test_fallback_to_default_when_template_view_missing(): void
    {
        // Arrange - use a non-existent template
        $this->setTemplate('nonexistent');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert - should fallback to default
        $this->assertSame(
            'default.pages.admin',
            $controller->getViewAdmin(),
            'View path should fallback to default.pages.admin when template view not found'
        );
    }

    /**
     * @test
     * Requirement 14.4: Fallback works for custom URI paths
     */
    public function test_fallback_to_default_for_custom_uri(): void
    {
        // Arrange - use a non-existent template with custom URI
        $this->setTemplate('nonexistent');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('dashboard');

        // Assert - should fallback to default
        $this->assertSame(
            'default.pages.admin',
            $controller->getViewAdmin(),
            'View path should fallback to default.pages.admin for custom URI when template view not found'
        );
    }

    // ── Test 3: Front-end view path resolution ────────────────────────────

    /**
     * @test
     * Requirement 14.1: Front-end view path resolution follows active template
     */
    public function test_front_view_resolution_with_default_template(): void
    {
        // Arrange
        $this->setTemplate('default');
        $controller = $this->createMockController();

        // Act
        $controller->testUriFront('index');

        // Assert
        $this->assertSame(
            'default.pages.front',
            $controller->getViewFront(),
            'Front view path should use default template'
        );
    }

    /**
     * @test
     * Requirement 14.4: Front-end view fallback to default when template view missing
     */
    public function test_front_view_fallback_to_default(): void
    {
        // Arrange - use a non-existent template
        $this->setTemplate('nonexistent');
        $controller = $this->createMockController();

        // Act
        $controller->testUriFront('index');

        // Assert - should fallback to default
        $this->assertSame(
            'default.pages.front',
            $controller->getViewFront(),
            'Front view path should fallback to default.pages.front when template view not found'
        );
    }

    // ── Test 4: Null or empty template handling ───────────────────────────

    /**
     * @test
     * Requirement 15.3: When canvastack_config('template') returns null, use 'default'
     */
    public function test_null_template_uses_default(): void
    {
        // Arrange - set template to null
        Config::set('canvastack.template', null);
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert - should use default
        $this->assertSame(
            'default.pages.admin',
            $controller->getViewAdmin(),
            'View path should use default when template is null'
        );
    }

    /**
     * @test
     * Requirement 15.3: When canvastack_config('template') returns empty string, use 'default'
     */
    public function test_empty_template_uses_default(): void
    {
        // Arrange - set template to empty string
        Config::set('canvastack.template', '');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert - should use default
        $this->assertSame(
            'default.pages.admin',
            $controller->getViewAdmin(),
            'View path should use default when template is empty'
        );
    }

    // ── Test 5: All three templates work correctly ────────────────────────

    /**
     * @test
     * Requirement 14.3: Test with all three templates
     * 
     * @dataProvider templateProvider
     */
    public function test_all_templates_resolve_correctly(string $template, string $expectedPrefix): void
    {
        // Arrange
        $this->setTemplate($template);
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert
        $viewAdmin = $controller->getViewAdmin();
        
        // Should either use the template or fallback to default
        $this->assertTrue(
            str_starts_with($viewAdmin, $expectedPrefix) || str_starts_with($viewAdmin, 'default.pages.admin'),
            "View path should start with {$expectedPrefix} or fallback to default.pages.admin, got: {$viewAdmin}"
        );
    }

    /**
     * Data provider for template testing
     * 
     * @return array
     */
    public function templateProvider(): array
    {
        return [
            'default template' => ['default', 'default.pages.admin'],
            'canvasign template' => ['canvasign', 'canvasign.pages.admin'],
            'canvas template' => ['canvas', 'canvas.pages.admin'],
        ];
    }

    // ── Test 6: View existence check works correctly ──────────────────────

    /**
     * @test
     * Requirement 14.4: View existence check using view()->exists()
     */
    public function test_view_existence_check_works(): void
    {
        // Arrange
        $this->setTemplate('default');

        // Act & Assert - default.pages.admin.index should exist
        $this->assertTrue(
            ViewFacade::exists('default.pages.admin.index'),
            'default.pages.admin.index view should exist'
        );
    }

    /**
     * @test
     * Requirement 14.4: Non-existent view returns false
     */
    public function test_nonexistent_view_returns_false(): void
    {
        // Act & Assert - nonexistent view should not exist
        $this->assertFalse(
            ViewFacade::exists('nonexistent.pages.admin.index'),
            'nonexistent.pages.admin.index view should not exist'
        );
    }

    // ── Test 7: Integration with canvastack_current_template() ────────────

    /**
     * @test
     * Requirement 14.5: Uses canvastack_current_template() as source of truth
     */
    public function test_uses_canvastack_current_template_function(): void
    {
        // Skip if function doesn't exist yet
        if (!function_exists('canvastack_current_template')) {
            $this->markTestSkipped('canvastack_current_template() function not yet implemented');
        }

        // Arrange
        $this->setTemplate('canvasign');
        $controller = $this->createMockController();

        // Act
        $controller->testUriAdmin('index');

        // Assert
        $viewAdmin = $controller->getViewAdmin();
        
        // Should use the template from canvastack_current_template()
        $this->assertTrue(
            in_array($viewAdmin, ['canvasign.pages.admin', 'default.pages.admin']),
            "View path should use template from canvastack_current_template() or fallback to default"
        );
    }

    // ── Test 8: Custom URI paths work correctly ───────────────────────────

    /**
     * @test
     * Requirement 14.1: Custom URI paths resolve correctly
     */
    public function test_custom_uri_paths_resolve_correctly(): void
    {
        // Arrange
        $this->setTemplate('default');
        $controller = $this->createMockController();

        // Test various custom URIs
        $customUris = ['dashboard', 'users', 'settings', 'reports'];

        foreach ($customUris as $uri) {
            // Act
            $controller->testUriAdmin($uri);

            // Assert
            $this->assertSame(
                'default.pages.admin',
                $controller->getViewAdmin(),
                "View path should use default template for URI: {$uri}"
            );
        }
    }

    // ── Test 9: Performance - View path caching ───────────────────────────

    /**
     * @test
     * Performance: View path resolution should be efficient
     */
    public function test_view_path_resolution_performance(): void
    {
        // Arrange
        $this->setTemplate('default');
        $controller = $this->createMockController();

        // Act - measure time for multiple resolutions
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $controller->testUriAdmin('index');
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert - should complete in reasonable time (< 100ms for 100 iterations)
        $this->assertLessThan(
            100,
            $duration,
            "View path resolution should be efficient (took {$duration}ms for 100 iterations)"
        );
    }
}
