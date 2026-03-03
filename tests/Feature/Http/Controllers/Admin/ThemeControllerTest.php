<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Http\Controllers\Admin;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Theme Controller Feature Test.
 *
 * @covers \Canvastack\Canvastack\Http\Controllers\Admin\ThemeController
 */
class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate as admin user
        $this->actingAs($this->createAdminUser());
    }

    public function test_index_displays_theme_management_page(): void
    {
        $response = $this->get(route('admin.themes.index'));

        $response->assertStatus(200);
        $response->assertViewIs('canvastack::admin.themes.index');
        $response->assertViewHas('themes');
        $response->assertViewHas('currentTheme');
        $response->assertViewHas('stats');
        $response->assertViewHas('meta'); // MetaTags component
    }

    public function test_show_displays_theme_details(): void
    {
        $response = $this->get(route('admin.themes.show', 'gradient'));

        $response->assertStatus(200);
        $response->assertViewIs('canvastack::admin.themes.show');
        $response->assertViewHas('theme');
        $response->assertViewHas('themeData');
        $response->assertViewHas('isActive');
        $response->assertViewHas('meta'); // MetaTags component
    }

    public function test_show_returns_404_for_invalid_theme(): void
    {
        $response = $this->get(route('admin.themes.show', 'nonexistent'));

        $response->assertStatus(404);
    }

    public function test_activate_switches_theme(): void
    {
        $response = $this->post(route('admin.themes.activate', 'forest'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
        $this->assertEquals('forest', session('active_theme'));
    }

    public function test_activate_returns_error_for_invalid_theme(): void
    {
        $response = $this->post(route('admin.themes.activate', 'nonexistent'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('error');
    }

    public function test_clear_cache_clears_theme_cache(): void
    {
        $response = $this->post(route('admin.themes.clear-cache'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
    }

    public function test_reload_reloads_themes(): void
    {
        $response = $this->post(route('admin.themes.reload'));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('success');
    }

    public function test_export_downloads_theme_json(): void
    {
        $response = $this->get(route('admin.themes.export', ['gradient', 'json']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');
    }

    public function test_preview_returns_theme_data(): void
    {
        $response = $this->get(route('admin.themes.preview', 'gradient'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'name',
                'display_name',
                'colors',
                'gradient',
                'css_variables',
            ],
        ]);
    }

    public function test_preview_returns_404_for_invalid_theme(): void
    {
        $response = $this->get(route('admin.themes.preview', 'nonexistent'));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_stats_returns_theme_statistics(): void
    {
        $response = $this->get(route('admin.themes.stats'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_themes',
                'active_theme',
                'cache_enabled',
                'cache_ttl',
                'hot_reload',
                'themes_with_dark_mode',
            ],
        ]);
    }

    public function test_index_configures_meta_tags(): void
    {
        $response = $this->get(route('admin.themes.index'));

        $response->assertStatus(200);

        // Verify MetaTags component is passed to view
        $meta = $response->viewData('meta');
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\MetaTags::class, $meta);

        // Verify meta tags contain expected content
        $metaHtml = $meta->tags();
        $this->assertStringContainsString('Theme Management', $metaHtml);
        $this->assertStringContainsString('themes', $metaHtml);
        $this->assertStringContainsString('customization', $metaHtml);
    }

    public function test_show_configures_meta_tags_with_theme_name(): void
    {
        $response = $this->get(route('admin.themes.show', 'gradient'));

        $response->assertStatus(200);

        // Verify MetaTags component is passed to view
        $meta = $response->viewData('meta');
        $this->assertInstanceOf(\Canvastack\Canvastack\Library\Components\MetaTags::class, $meta);

        // Verify meta tags contain theme-specific content
        $metaHtml = $meta->tags();
        $this->assertStringContainsString('Gradient', $metaHtml);
        $this->assertStringContainsString('theme', $metaHtml);
    }

    /**
     * Create an admin user for testing.
     *
     * @return \Tests\Fixtures\Models\TestUser
     */
    protected function createAdminUser()
    {
        return \Tests\Fixtures\Models\TestUser::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }
}
