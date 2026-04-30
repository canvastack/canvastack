<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;

/**
 * Integration tests for Template Helper functions with ThemeAdapter system.
 *
 * Tests grid system, breadcrumb, and sidebar helper functions with all three templates.
 *
 * Task 9.4: Write integration tests for Grid System
 * Task 9.6: Write integration tests for Breadcrumb
 * Task 9.8: Write integration tests for Sidebar
 */
class TemplateHelperIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::reset();
    }

    protected function tearDown(): void
    {
        ThemeAdapterResolver::reset();
        parent::tearDown();
    }

    /**
     * Set the active template via Laravel config so canvastack_current_template()
     * returns the desired value, then reset the resolver cache.
     */
    private function setTemplate(string $template): void
    {
        config(['canvastack.settings.template' => $template]);
        ThemeAdapterResolver::reset();
    }

    // ── Grid System Tests (Task 9.4) ──────────────────────────────────────

    /**
     * @test
     * Task 9.4: Test canvastack_gird('start') with default template produces Bootstrap 4 grid
     */
    public function test_grid_start_with_default_template_produces_bootstrap4_grid(): void
    {
        $this->setTemplate('default');

        $output = canvastack_gird('start');

        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('class="row"', $output);
        $this->assertStringContainsString('class="col col-12"', $output);
    }

    /**
     * @test
     * Task 9.4: Test canvastack_gird('start') with canvasign template produces Bootstrap 5 grid
     */
    public function test_grid_start_with_canvasign_template_produces_bootstrap5_grid(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_gird('start');

        // Bootstrap 5 uses same grid classes as Bootstrap 4
        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('class="row"', $output);
        $this->assertStringContainsString('class="col col-12"', $output);
    }

    /**
     * @test
     * Task 9.4: Test canvastack_gird('start') with canvas template produces Tailwind grid
     */
    public function test_grid_start_with_canvas_template_produces_tailwind_grid(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_gird('start');

        $this->assertStringContainsString('class="container mx-auto"', $output);
        $this->assertStringContainsString('class="flex flex-wrap"', $output);
        $this->assertStringContainsString('class="col w-full"', $output);
    }

    /**
     * @test
     * Task 9.4: Test canvastack_gird('container-fluid') with all three templates
     */
    public function test_grid_container_fluid_with_all_templates(): void
    {
        // Default template
        $this->setTemplate('default');
        $output = canvastack_gird('container-fluid');
        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('class="row"', $output);

        // Canvasign template
        $this->setTemplate('canvasign');
        $output = canvastack_gird('container-fluid');
        $this->assertStringContainsString('class="container"', $output);
        $this->assertStringContainsString('class="row"', $output);

        // Canvas template
        $this->setTemplate('canvas');
        $output = canvastack_gird('container-fluid');
        $this->assertStringContainsString('class="container mx-auto"', $output);
        $this->assertStringContainsString('class="flex flex-wrap"', $output);
    }

    /**
     * @test
     * Task 9.4: Test canvastack_set_gird_column() with various column configurations
     */
    public function test_grid_set_column_with_various_configurations(): void
    {
        // Default template - 2 columns (12/2 = 6)
        $this->setTemplate('default');
        $output = canvastack_set_gird_column('<p>Content</p>', 2);
        $this->assertStringContainsString('class="col col-6"', $output);
        $this->assertStringContainsString('<p>Content</p>', $output);

        // Default template - 3 columns (12/3 = 4)
        $output = canvastack_set_gird_column('<p>Content</p>', 3);
        $this->assertStringContainsString('class="col col-4"', $output);

        // Canvas template - 2 columns
        $this->setTemplate('canvas');
        $output = canvastack_set_gird_column('<p>Content</p>', 2);
        $this->assertStringContainsString('class="col w-6/12"', $output);

        // Canvas template - 3 columns
        $output = canvastack_set_gird_column('<p>Content</p>', 3);
        $this->assertStringContainsString('class="col w-4/12"', $output);
    }

    /**
     * @test
     * Task 9.4: Test canvastack_gird('end') closes grid structure
     */
    public function test_grid_end_closes_structure(): void
    {
        $output = canvastack_gird('end');
        $this->assertEquals('</div></div></div>', $output);
    }

    /**
     * @test
     * Task 9.4: Test backward compatibility with existing layouts
     */
    public function test_grid_backward_compatibility_with_default_template(): void
    {
        $this->setTemplate('default');

        // Test complete grid structure
        $start = canvastack_gird('start');
        $content = '<p>Page content</p>';
        $end = canvastack_gird('end');

        $fullOutput = $start . $content . $end;

        // Verify structure matches Bootstrap 4 expectations
        $this->assertStringContainsString('<div class="container">', $fullOutput);
        $this->assertStringContainsString('<div class="row">', $fullOutput);
        $this->assertStringContainsString('<div class="col col-12">', $fullOutput);
        $this->assertStringContainsString('<p>Page content</p>', $fullOutput);
        $this->assertStringContainsString('</div></div></div>', $fullOutput);
    }

    /**
     * @test
     * Task 9.4: Test grid with custom column offset
     */
    public function test_grid_with_column_offset(): void
    {
        $this->setTemplate('default');

        // set_column = 3 means 12 - 3 = 9 columns
        $output = canvastack_gird('start', 3);
        $this->assertStringContainsString('class="col col-9"', $output);

        // Canvas template with offset
        $this->setTemplate('canvas');
        $output = canvastack_gird('start', 3);
        $this->assertStringContainsString('class="col w-9/12"', $output);
    }

    /**
     * @test
     * Task 9.4: Test grid single div mode
     */
    public function test_grid_single_div_mode(): void
    {
        $output = canvastack_gird('custom-class', false, '<p>Content</p>', true);
        $this->assertEquals('<div class="custom-class"><p>Content</p></div>', $output);
    }

    /**
     * @test
     * Task 9.4: Test grid with custom class name
     */
    public function test_grid_with_custom_class_name(): void
    {
        $this->setTemplate('default');

        $output = canvastack_gird('my-custom-wrapper');
        $this->assertStringContainsString('class="row"', $output);
        $this->assertStringContainsString('class="my-custom-wrapper"', $output);
    }

    // ── Breadcrumb Tests (Task 9.6) ───────────────────────────────────────

    /**
     * @test
     * Task 9.6: Test canvastack_breadcrumb() with default template produces Bootstrap 4 breadcrumb
     */
    public function test_breadcrumb_with_default_template_produces_bootstrap4(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'Dashboard',
            ['Home' => '/home', 'Users' => '/users', 0 => 'Dashboard']
        );

        // Bootstrap 4 classes
        $this->assertStringContainsString('class="breadcrumbs-area clearfix"', $output);
        $this->assertStringContainsString('class="page-title pull-left"', $output);
        $this->assertStringContainsString('class="breadcrumbs pull-right"', $output);
        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('href="/home"', $output);
        $this->assertStringContainsString('href="/users"', $output);
    }

    /**
     * @test
     * Task 9.6: Test canvastack_breadcrumb() with canvasign template produces Bootstrap 5 breadcrumb
     */
    public function test_breadcrumb_with_canvasign_template_produces_bootstrap5(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_breadcrumb(
            'Dashboard',
            ['Home' => '/home', 'Users' => '/users', 0 => 'Dashboard']
        );

        // Bootstrap 5 classes (float-end instead of pull-right)
        $this->assertStringContainsString('class="breadcrumbs-area clearfix"', $output);
        $this->assertStringContainsString('class="page-title float-start"', $output);
        $this->assertStringContainsString('class="breadcrumbs float-end"', $output);
        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('href="/home"', $output);
        $this->assertStringContainsString('href="/users"', $output);
    }

    /**
     * @test
     * Task 9.6: Test canvastack_breadcrumb() with canvas template produces Tailwind breadcrumb
     */
    public function test_breadcrumb_with_canvas_template_produces_tailwind(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_breadcrumb(
            'Dashboard',
            ['Home' => '/home', 'Users' => '/users', 0 => 'Dashboard']
        );

        // Tailwind classes
        $this->assertStringContainsString('class="flex items-center', $output);
        $this->assertStringContainsString('class="text-lg font-semibold text-gray-800"', $output);
        $this->assertStringContainsString('class="flex items-center space-x-2 text-sm ml-auto"', $output);
        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('href="/home"', $output);
        $this->assertStringContainsString('href="/users"', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with single link
     */
    public function test_breadcrumb_with_single_link(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'User Profile',
            ['Home' => '/home']
        );

        $this->assertStringContainsString('User Profile', $output);
        $this->assertStringContainsString('href="/home"', $output);
        $this->assertStringContainsString('Home', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with multiple links
     */
    public function test_breadcrumb_with_multiple_links(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'Edit User',
            [
                'Home' => '/home',
                'Users' => '/users',
                'User Details' => '/users/123',
                0 => 'Edit'
            ]
        );

        $this->assertStringContainsString('Edit User', $output);
        $this->assertStringContainsString('href="/home"', $output);
        $this->assertStringContainsString('href="/users"', $output);
        $this->assertStringContainsString('href="/users/123"', $output);
        $this->assertStringContainsString('Edit', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with no links
     */
    public function test_breadcrumb_with_no_links(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb('Dashboard', []);

        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('class="breadcrumbs-area clearfix"', $output);
        // Should not contain breadcrumbs list when no links provided
        $this->assertStringNotContainsString('<ul class="breadcrumbs', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with icons (icon_title)
     */
    public function test_breadcrumb_with_icon_title(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'Dashboard',
            ['Home' => '/home'],
            'home' // icon_title
        );

        // Note: icon_title is not used in default (non-blankon) type
        // This test verifies the function accepts the parameter without error
        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('href="/home"', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with blankon type (legacy)
     */
    public function test_breadcrumb_with_blankon_type(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'Dashboard',
            ['Home' => '/home', 'Users' => '/users'],
            'home', // icon_title
            ['home', 'users'], // icon_links
            'blankon' // type
        );

        // Blankon type uses different structure
        $this->assertStringContainsString('class="header-content"', $output);
        $this->assertStringContainsString('class="breadcrumb-wrapper hidden-xs"', $output);
        $this->assertStringContainsString('<ol class="breadcrumb">', $output);
        $this->assertStringContainsString('fa fa-home', $output);
        $this->assertStringContainsString('Dashboard', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb backward compatibility with existing breadcrumbs
     */
    public function test_breadcrumb_backward_compatibility(): void
    {
        $this->setTemplate('default');

        // Test that existing breadcrumb calls still work
        $output = canvastack_breadcrumb(
            'System Settings',
            ['Home' => '/', 'Settings' => '/settings', 0 => 'System']
        );

        // Verify Bootstrap 4 structure is preserved
        $this->assertStringContainsString('class="row align-items-center"', $output);
        $this->assertStringContainsString('class="col-sm-12"', $output);
        $this->assertStringContainsString('class="breadcrumbs-area clearfix"', $output);
        $this->assertStringContainsString('class="page-title pull-left"', $output);
        $this->assertStringContainsString('class="breadcrumbs pull-right"', $output);
        $this->assertStringContainsString('System Settings', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with special characters in title
     */
    public function test_breadcrumb_with_special_characters(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'User & Settings',
            ['Home' => '/home']
        );

        // Verify HTML escaping
        $this->assertStringContainsString('User &amp; Settings', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb with underscore in link title
     */
    public function test_breadcrumb_with_underscore_in_link_title(): void
    {
        $this->setTemplate('default');

        $output = canvastack_breadcrumb(
            'User Profile',
            ['user_management' => '/users', 0 => 'Profile']
        );

        // Verify underscore is converted to camelcase
        $this->assertStringContainsString('User Management', $output);
        $this->assertStringNotContainsString('user_management', $output);
    }

    /**
     * @test
     * Task 9.6: Test breadcrumb structure consistency across templates
     */
    public function test_breadcrumb_structure_consistency_across_templates(): void
    {
        $title = 'Dashboard';
        $links = ['Home' => '/home', 'Users' => '/users', 0 => 'Dashboard'];

        // Default template
        $this->setTemplate('default');
        $defaultOutput = canvastack_breadcrumb($title, $links);
        $this->assertStringContainsString('Dashboard', $defaultOutput);
        $this->assertStringContainsString('Home', $defaultOutput);
        $this->assertStringContainsString('Users', $defaultOutput);

        // Canvasign template
        $this->setTemplate('canvasign');
        $canvasignOutput = canvastack_breadcrumb($title, $links);
        $this->assertStringContainsString('Dashboard', $canvasignOutput);
        $this->assertStringContainsString('Home', $canvasignOutput);
        $this->assertStringContainsString('Users', $canvasignOutput);

        // Canvas template
        $this->setTemplate('canvas');
        $canvasOutput = canvastack_breadcrumb($title, $links);
        $this->assertStringContainsString('Dashboard', $canvasOutput);
        $this->assertStringContainsString('Home', $canvasOutput);
        $this->assertStringContainsString('Users', $canvasOutput);

        // All templates should produce valid HTML with title and links
        $this->assertNotEmpty($defaultOutput);
        $this->assertNotEmpty($canvasignOutput);
        $this->assertNotEmpty($canvasOutput);
    }

    // ── Sidebar Tests (Task 9.8) ──────────────────────────────────────────

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_content() with default template (simple type)
     */
    public function test_sidebar_content_simple_type_with_default_template(): void
    {
        $this->setTemplate('default');

        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg" alt="User">',
            'John Doe',
            'Administrator',
            false // simple type
        );

        // Bootstrap 4 classes
        $this->assertStringContainsString('class="sidebar-content"', $output);
        $this->assertStringContainsString('class="media"', $output);
        $this->assertStringContainsString('class="media-body"', $output);
        $this->assertStringContainsString('class="media-heading"', $output);
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('Administrator', $output);
        $this->assertStringContainsString('<img src="/avatar.jpg" alt="User">', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_content() with canvasign template (simple type)
     */
    public function test_sidebar_content_simple_type_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg" alt="User">',
            'Jane Smith',
            'Manager',
            false // simple type
        );

        // Bootstrap 5 uses same media classes as Bootstrap 4
        $this->assertStringContainsString('class="sidebar-content"', $output);
        $this->assertStringContainsString('class="media"', $output);
        $this->assertStringContainsString('class="media-body"', $output);
        $this->assertStringContainsString('Jane Smith', $output);
        $this->assertStringContainsString('Manager', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_content() with canvas template (simple type)
     */
    public function test_sidebar_content_simple_type_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg" alt="User">',
            'Bob Johnson',
            'Developer',
            false // simple type
        );

        // Tailwind classes
        $this->assertStringContainsString('class="sidebar-content p-4"', $output);
        $this->assertStringContainsString('class="flex items-start gap-3"', $output);
        $this->assertStringContainsString('class="flex-1"', $output);
        $this->assertStringContainsString('class="text-base font-semibold text-gray-800"', $output);
        $this->assertStringContainsString('Bob Johnson', $output);
        $this->assertStringContainsString('Developer', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_content() with user panel type (default template)
     */
    public function test_sidebar_content_user_panel_type_with_default_template(): void
    {
        $this->setTemplate('default');

        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg" alt="User">',
            false,
            false,
            true // user panel type
        );

        // Bootstrap 4 user panel structure
        $this->assertStringContainsString('class="user-panel light"', $output);
        $this->assertStringContainsString('data-toggle="collapse"', $output);
        $this->assertStringContainsString('href="#userInfoBox"', $output);
        $this->assertStringContainsString('class="list-group mt-3 shadow"', $output);
        $this->assertStringContainsString('class="list-group-item list-group-item-action', $output);
        $this->assertStringContainsString('Profile', $output);
        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('Log Out', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_content() with user panel type (canvasign template)
     */
    public function test_sidebar_content_user_panel_type_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg" alt="User">',
            false,
            false,
            true // user panel type
        );

        // Bootstrap 5 user panel structure (data-bs-toggle instead of data-toggle)
        $this->assertStringContainsString('class="user-panel light"', $output);
        $this->assertStringContainsString('data-bs-toggle="collapse"', $output);
        $this->assertStringContainsString('href="#userInfoBox"', $output);
        $this->assertStringContainsString('class="list-group mt-3 shadow"', $output);
        $this->assertStringContainsString('Profile', $output);
        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('Log Out', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_content() with user panel type (canvas template)
     */
    public function test_sidebar_content_user_panel_type_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg" alt="User">',
            false,
            false,
            true // user panel type
        );

        // Tailwind user panel structure
        $this->assertStringContainsString('class="user-panel p-4 bg-white rounded-lg shadow"', $output);
        $this->assertStringContainsString('data-toggle="collapse"', $output);
        $this->assertStringContainsString('href="#userInfoBox"', $output);
        $this->assertStringContainsString('class="mt-3 space-y-1 rounded-lg shadow-md overflow-hidden"', $output);
        $this->assertStringContainsString('class="flex items-center gap-2', $output);
        $this->assertStringContainsString('Profile', $output);
        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('Log Out', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_category() with default template
     */
    public function test_sidebar_category_with_default_template(): void
    {
        $this->setTemplate('default');

        $output = canvastack_sidebar_category('Main Menu', 'bars', 'right');

        // Bootstrap 4 classes
        $this->assertStringContainsString('class="sidebar-category"', $output);
        $this->assertStringContainsString('Main Menu', $output);
        $this->assertStringContainsString('class="pull-right"', $output);
        $this->assertStringContainsString('fa fa-bars', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_category() with canvasign template
     */
    public function test_sidebar_category_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_sidebar_category('Settings', 'cog', 'right');

        // Bootstrap 5 classes (float-end instead of pull-right)
        $this->assertStringContainsString('class="sidebar-category"', $output);
        $this->assertStringContainsString('Settings', $output);
        $this->assertStringContainsString('class="float-end"', $output);
        $this->assertStringContainsString('fa fa-cog', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_category() with canvas template
     */
    public function test_sidebar_category_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_sidebar_category('Dashboard', 'home', 'right');

        // Tailwind classes (ml-auto instead of pull-right)
        $this->assertStringContainsString('class="sidebar-category"', $output);
        $this->assertStringContainsString('Dashboard', $output);
        $this->assertStringContainsString('class="ml-auto"', $output);
        $this->assertStringContainsString('fa fa-home', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_sidebar_category() with left icon position
     */
    public function test_sidebar_category_with_left_icon_position(): void
    {
        $this->setTemplate('default');

        $output = canvastack_sidebar_category('Navigation', 'list', 'left');

        // Bootstrap 4 pull-left
        $this->assertStringContainsString('class="pull-left"', $output);
        $this->assertStringContainsString('fa fa-list', $output);

        // Canvasign template
        $this->setTemplate('canvasign');
        $output = canvastack_sidebar_category('Navigation', 'list', 'left');
        $this->assertStringContainsString('class="float-start"', $output);

        // Canvas template
        $this->setTemplate('canvas');
        $output = canvastack_sidebar_category('Navigation', 'list', 'left');
        $this->assertStringContainsString('class="mr-auto"', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with default template (old type)
     */
    public function test_set_avatar_old_type_with_default_template(): void
    {
        $this->setTemplate('default');

        $output = canvastack_set_avatar(
            'John Doe',
            '/profile/123',
            '/avatar.jpg',
            'online',
            true // old type
        );

        // Bootstrap 4 classes
        $this->assertStringContainsString('class="pull-left has-notif avatar"', $output);
        $this->assertStringContainsString('href="/profile/123"', $output);
        $this->assertStringContainsString('src="/avatar.jpg"', $output);
        $this->assertStringContainsString('alt="John Doe"', $output);
        $this->assertStringContainsString('class="online"', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with canvasign template (old type)
     */
    public function test_set_avatar_old_type_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_set_avatar(
            'Jane Smith',
            '/profile/456',
            '/avatar2.jpg',
            'offline',
            true // old type
        );

        // Bootstrap 5 classes (float-start instead of pull-left)
        $this->assertStringContainsString('class="float-start has-notif avatar"', $output);
        $this->assertStringContainsString('href="/profile/456"', $output);
        $this->assertStringContainsString('src="/avatar2.jpg"', $output);
        $this->assertStringContainsString('alt="Jane Smith"', $output);
        $this->assertStringContainsString('class="offline"', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with canvas template (old type)
     */
    public function test_set_avatar_old_type_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_set_avatar(
            'Bob Johnson',
            '/profile/789',
            '/avatar3.jpg',
            'online',
            true // old type
        );

        // Tailwind classes (mr-auto instead of pull-left)
        $this->assertStringContainsString('class="mr-auto has-notif avatar"', $output);
        $this->assertStringContainsString('href="/profile/789"', $output);
        $this->assertStringContainsString('src="/avatar3.jpg"', $output);
        $this->assertStringContainsString('alt="Bob Johnson"', $output);
        $this->assertStringContainsString('class="online"', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with default template (new type)
     */
    public function test_set_avatar_new_type_with_default_template(): void
    {
        $this->setTemplate('default');

        $output = canvastack_set_avatar(
            'Alice Brown',
            false,
            '/avatar4.jpg',
            'online',
            false // new type
        );

        // Bootstrap 4 classes
        $this->assertStringContainsString('class="pull-left image"', $output);
        $this->assertStringContainsString('class="pull-left info"', $output);
        $this->assertStringContainsString('class="user-avatar"', $output);
        $this->assertStringContainsString('src="/avatar4.jpg"', $output);
        $this->assertStringContainsString('Alice Brown', $output);
        $this->assertStringContainsString('online', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with canvasign template (new type)
     */
    public function test_set_avatar_new_type_with_canvasign_template(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_set_avatar(
            'Charlie Davis',
            false,
            '/avatar5.jpg',
            'online',
            false // new type
        );

        // Bootstrap 5 classes (float-start instead of pull-left)
        $this->assertStringContainsString('class="float-start image"', $output);
        $this->assertStringContainsString('class="float-start info"', $output);
        $this->assertStringContainsString('class="user-avatar"', $output);
        $this->assertStringContainsString('src="/avatar5.jpg"', $output);
        $this->assertStringContainsString('Charlie Davis', $output);
        $this->assertStringContainsString('online', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with canvas template (new type)
     */
    public function test_set_avatar_new_type_with_canvas_template(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_set_avatar(
            'Diana Evans',
            false,
            '/avatar6.jpg',
            'online',
            false // new type
        );

        // Tailwind classes (mr-auto instead of pull-left)
        $this->assertStringContainsString('class="mr-auto image"', $output);
        $this->assertStringContainsString('class="mr-auto info"', $output);
        $this->assertStringContainsString('class="user-avatar"', $output);
        $this->assertStringContainsString('src="/avatar6.jpg"', $output);
        $this->assertStringContainsString('Diana Evans', $output);
        $this->assertStringContainsString('online', $output);
    }

    /**
     * @test
     * Task 9.8: Test canvastack_set_avatar() with default image fallback
     */
    public function test_set_avatar_with_default_image_fallback(): void
    {
        $this->setTemplate('default');

        $output = canvastack_set_avatar(
            'Test User',
            false,
            false, // no image provided
            'online',
            false
        );

        // Should use default image
        $this->assertStringContainsString('assets/templates/default/images/user-m.png', $output);
        $this->assertStringContainsString('Test User', $output);
    }

    /**
     * @test
     * Task 9.8: Test float classes are framework-appropriate
     */
    public function test_float_classes_are_framework_appropriate(): void
    {
        // Default template - pull-left/pull-right
        $this->setTemplate('default');
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertEquals('pull-left', $adapter->getFloatLeftClass());
        $this->assertEquals('pull-right', $adapter->getFloatRightClass());

        // Canvasign template - float-start/float-end
        $this->setTemplate('canvasign');
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertEquals('float-start', $adapter->getFloatLeftClass());
        $this->assertEquals('float-end', $adapter->getFloatRightClass());

        // Canvas template - mr-auto/ml-auto
        $this->setTemplate('canvas');
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertEquals('mr-auto', $adapter->getFloatLeftClass());
        $this->assertEquals('ml-auto', $adapter->getFloatRightClass());
    }

    /**
     * @test
     * Task 9.8: Test backward compatibility with existing sidebar layouts
     */
    public function test_sidebar_backward_compatibility_with_default_template(): void
    {
        $this->setTemplate('default');

        // Test simple sidebar content
        $output = canvastack_sidebar_content(
            '<img src="/avatar.jpg">',
            'User Name',
            'Role',
            false
        );

        // Verify Bootstrap 4 structure is preserved
        $this->assertStringContainsString('class="sidebar-content"', $output);
        $this->assertStringContainsString('class="media"', $output);
        $this->assertStringContainsString('class="media-body"', $output);

        // Test sidebar category
        $categoryOutput = canvastack_sidebar_category('Menu', 'bars');
        $this->assertStringContainsString('class="sidebar-category"', $categoryOutput);
        $this->assertStringContainsString('class="pull-right"', $categoryOutput);

        // Test avatar
        $avatarOutput = canvastack_set_avatar('User', false, '/avatar.jpg');
        $this->assertStringContainsString('class="pull-left', $avatarOutput);
    }
}


