<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Http\Middleware;

use Canvastack\Canvastack\Components\Table\Support\ThemePreferenceLoader;
use Canvastack\Canvastack\Http\Middleware\LoadUserThemePreference;
use Canvastack\Canvastack\Support\Integration\UserPreferences;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * LoadUserThemePreference Middleware Unit Tests.
 *
 * Tests for automatic theme preference loading middleware (Requirement 51.10).
 *
 * Validates:
 * - Middleware loads user's theme preference on every request
 * - Middleware continues request processing after loading theme
 * - Theme is applied before views are rendered
 */
class LoadUserThemePreferenceTest extends TestCase
{
    protected LoadUserThemePreference $middleware;

    protected ThemePreferenceLoader $loader;

    protected UserPreferences $preferences;

    protected ThemeManager $themeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preferences = app(UserPreferences::class);
        $this->themeManager = app(ThemeManager::class);
        $this->loader = new ThemePreferenceLoader($this->preferences, $this->themeManager);
        $this->middleware = new LoadUserThemePreference($this->loader);

        // Clear any existing preferences
        $this->preferences->clear();

        // Ensure themes are loaded
        $this->themeManager->initialize();
    }

    protected function tearDown(): void
    {
        // Clean up preferences
        $this->preferences->clear();

        parent::tearDown();
    }

    /**
     * Test that middleware can be instantiated.
     *
     * @return void
     */
    public function test_middleware_can_be_instantiated(): void
    {
        $this->assertInstanceOf(LoadUserThemePreference::class, $this->middleware);
    }

    /**
     * Test middleware loads theme preference on request.
     *
     * Requirement 51.10: Middleware should load user's theme preference automatically.
     *
     * @return void
     */
    public function test_middleware_loads_theme_preference(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $preferredTheme = $themes[0];

        // Set user preference
        $this->preferences->setTheme($preferredTheme);

        // Create request
        $request = Request::create('/test', 'GET');

        // Track if next middleware was called
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return response('OK');
        };

        // Handle request
        $response = $this->middleware->handle($request, $next);

        // Verify next middleware was called
        $this->assertTrue($nextCalled, 'Next middleware should be called');

        // Verify theme was loaded
        $this->assertEquals($preferredTheme, $this->themeManager->current()->getName());

        // Verify response is correct
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test middleware continues when no preference is set.
     *
     * Requirement 51.10: Middleware should not fail when no preference exists.
     *
     * @return void
     */
    public function test_middleware_continues_when_no_preference(): void
    {
        // Ensure no preference is set
        $this->preferences->forget('theme');

        // Create request
        $request = Request::create('/test', 'GET');

        // Track if next middleware was called
        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;

            return response('OK');
        };

        // Handle request
        $response = $this->middleware->handle($request, $next);

        // Verify next middleware was called
        $this->assertTrue($nextCalled, 'Next middleware should be called even without preference');

        // Verify response is correct
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test middleware loads theme before view rendering.
     *
     * Requirement 51.10: Theme should be loaded before views are rendered.
     *
     * @return void
     */
    public function test_middleware_loads_theme_before_view_rendering(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $preferredTheme = $themes[0];

        // Set user preference
        $this->preferences->setTheme($preferredTheme);

        // Create request
        $request = Request::create('/test', 'GET');

        // Capture theme name when next middleware runs
        $themeWhenNextRuns = null;
        $next = function ($req) use (&$themeWhenNextRuns) {
            $themeWhenNextRuns = $this->themeManager->current()->getName();

            return response('OK');
        };

        // Handle request
        $this->middleware->handle($request, $next);

        // Verify theme was already loaded when next middleware ran
        $this->assertEquals($preferredTheme, $themeWhenNextRuns);
    }

    /**
     * Test middleware handles multiple requests correctly.
     *
     * Requirement 51.10: Middleware should work correctly on every request.
     *
     * @return void
     */
    public function test_middleware_handles_multiple_requests(): void
    {
        // Get available themes
        $themes = $this->themeManager->names();
        $this->assertNotEmpty($themes, 'At least one theme must be available for testing');

        $preferredTheme = $themes[0];

        // Set user preference
        $this->preferences->setTheme($preferredTheme);

        // Create next closure
        $next = function ($req) {
            return response('OK');
        };

        // Handle multiple requests
        for ($i = 0; $i < 3; $i++) {
            $request = Request::create('/test-' . $i, 'GET');
            $response = $this->middleware->handle($request, $next);

            // Verify theme is loaded correctly each time
            $this->assertEquals($preferredTheme, $this->themeManager->current()->getName());
            $this->assertEquals('OK', $response->getContent());
        }
    }

    /**
     * Test middleware passes request to next middleware.
     *
     * Requirement 51.10: Middleware should not block request pipeline.
     *
     * @return void
     */
    public function test_middleware_passes_request_to_next(): void
    {
        // Create request
        $request = Request::create('/test', 'GET');

        // Track request passed to next middleware
        $passedRequest = null;
        $next = function ($req) use (&$passedRequest) {
            $passedRequest = $req;

            return response('OK');
        };

        // Handle request
        $this->middleware->handle($request, $next);

        // Verify same request was passed
        $this->assertSame($request, $passedRequest);
    }

    /**
     * Test middleware returns response from next middleware.
     *
     * Requirement 51.10: Middleware should return response from pipeline.
     *
     * @return void
     */
    public function test_middleware_returns_response_from_next(): void
    {
        // Create request
        $request = Request::create('/test', 'GET');

        // Create custom response
        $expectedResponse = response('Custom Response', 201);
        $next = function ($req) use ($expectedResponse) {
            return $expectedResponse;
        };

        // Handle request
        $actualResponse = $this->middleware->handle($request, $next);

        // Verify same response was returned
        $this->assertSame($expectedResponse, $actualResponse);
        $this->assertEquals('Custom Response', $actualResponse->getContent());
        $this->assertEquals(201, $actualResponse->getStatusCode());
    }
}
