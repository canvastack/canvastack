<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\BladeDirectives;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Test for RBAC Blade Directives.
 *
 * These tests verify that Blade directives compile correctly.
 * Full integration testing with actual permission checks should be done in feature tests.
 */
class BladeDirectivesTest extends TestCase
{
    protected BladeCompiler $blade;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create Blade compiler instance
        $this->blade = new BladeCompiler(
            new \Illuminate\Filesystem\Filesystem(),
            __DIR__ . '/../../../../storage/framework/views'
        );

        // Bind blade.compiler to container
        Container::getInstance()->instance('blade.compiler', $this->blade);
    }

    /**
     * Test that @canAccessRow directive is registered.
     *
     * @return void
     */
    public function test_can_access_row_directive_is_registered(): void
    {
        BladeDirectives::register();
        $directives = $this->blade->getCustomDirectives();
        $this->assertArrayHasKey('canAccessRow', $directives);
    }

    /**
     * Test that @canAccessColumn directive is registered.
     *
     * @return void
     */
    public function test_can_access_column_directive_is_registered(): void
    {
        BladeDirectives::register();
        $directives = $this->blade->getCustomDirectives();
        $this->assertArrayHasKey('canAccessColumn', $directives);
    }

    /**
     * Test that @canAccessJsonAttribute directive is registered.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_is_registered(): void
    {
        BladeDirectives::register();
        $directives = $this->blade->getCustomDirectives();
        $this->assertArrayHasKey('canAccessJsonAttribute', $directives);
    }

    /**
     * Test that @canAccessRow directive compiles correctly.
     *
     * @return void
     */
    public function test_can_access_row_directive_compiles_correctly(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post)<button>Edit</button>@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('<?php if', $compiled);
        $this->assertStringContainsString('canAccessRow', $compiled);
        $this->assertStringContainsString('posts.edit', $compiled);
        $this->assertStringContainsString('$post', $compiled);
        $this->assertStringContainsString('<button>Edit</button>', $compiled);
        $this->assertStringContainsString('endif', $compiled);
    }

    /**
     * Test that @canAccessRow directive compiles with correct parameters.
     *
     * @return void
     */
    public function test_can_access_row_directive_compiles_with_parameters(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('users.delete', \$user)<span>Delete</span>@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('users.delete', $compiled);
        $this->assertStringContainsString('$user', $compiled);
        $this->assertStringContainsString('<span>Delete</span>', $compiled);
    }

    /**
     * Test that @canAccessRow directive can be nested.
     *
     * @return void
     */
    public function test_can_access_row_directive_can_be_nested(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post)<div class=\"actions\"><button>Edit</button><button>Delete</button></div>@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('<div class="actions">', $compiled);
        $this->assertStringContainsString('<button>Edit</button>', $compiled);
        $this->assertStringContainsString('<button>Delete</button>', $compiled);
    }

    /**
     * Test that multiple @canAccessRow directives can be used.
     *
     * @return void
     */
    public function test_multiple_can_access_row_directives(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post)<button>Edit</button>@endcanAccessRow @canAccessRow('posts.delete', \$post)<button>Delete</button>@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('posts.edit', $compiled);
        $this->assertStringContainsString('posts.delete', $compiled);
        $this->assertStringContainsString('<button>Edit</button>', $compiled);
        $this->assertStringContainsString('<button>Delete</button>', $compiled);
    }

    /**
     * Test that @canAccessRow directive works with complex model expressions.
     *
     * @return void
     */
    public function test_can_access_row_directive_with_complex_expressions(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post->fresh())<button>Edit</button>@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('$post->fresh()', $compiled);
    }

    /**
     * Test that @canAccessColumn directive compiles correctly.
     *
     * @return void
     */
    public function test_can_access_column_directive_compiles_correctly(): void
    {
        BladeDirectives::register();
        $template = "@canAccessColumn('posts.edit', \$post, 'status')<input type=\"text\" name=\"status\">@endcanAccessColumn";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('<?php if', $compiled);
        $this->assertStringContainsString('canAccessColumn', $compiled);
        $this->assertStringContainsString('posts.edit', $compiled);
        $this->assertStringContainsString('$post', $compiled);
        $this->assertStringContainsString('status', $compiled);
        $this->assertStringContainsString('<input type="text" name="status">', $compiled);
        $this->assertStringContainsString('endif', $compiled);
    }

    /**
     * Test that @canAccessColumn directive compiles with correct parameters.
     *
     * @return void
     */
    public function test_can_access_column_directive_compiles_with_parameters(): void
    {
        BladeDirectives::register();
        $template = "@canAccessColumn('users.edit', \$user, 'email')<span>{{ \$user->email }}</span>@endcanAccessColumn";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('users.edit', $compiled);
        $this->assertStringContainsString('$user', $compiled);
        $this->assertStringContainsString('email', $compiled);
        $this->assertStringContainsString('<span>', $compiled);
    }

    /**
     * Test that @canAccessColumn directive can be nested.
     *
     * @return void
     */
    public function test_can_access_column_directive_can_be_nested(): void
    {
        BladeDirectives::register();
        $template = "@canAccessColumn('posts.edit', \$post, 'content')<div class=\"editor\"><textarea name=\"content\">{{ \$post->content }}</textarea></div>@endcanAccessColumn";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('<div class="editor">', $compiled);
        $this->assertStringContainsString('<textarea name="content">', $compiled);
    }

    /**
     * Test that multiple @canAccessColumn directives can be used.
     *
     * @return void
     */
    public function test_multiple_can_access_column_directives(): void
    {
        BladeDirectives::register();
        $template = "@canAccessColumn('posts.edit', \$post, 'title')<input name=\"title\">@endcanAccessColumn @canAccessColumn('posts.edit', \$post, 'status')<select name=\"status\"></select>@endcanAccessColumn";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('title', $compiled);
        $this->assertStringContainsString('status', $compiled);
        $this->assertStringContainsString('<input name="title">', $compiled);
        $this->assertStringContainsString('<select name="status">', $compiled);
    }

    /**
     * Test that @canAccessColumn directive works with different column names.
     *
     * @return void
     */
    public function test_can_access_column_directive_with_different_columns(): void
    {
        BladeDirectives::register();
        $columns = ['title', 'content', 'status', 'published_at', 'featured'];

        foreach ($columns as $column) {
            $template = "@canAccessColumn('posts.edit', \$post, '{$column}')<input name=\"{$column}\">@endcanAccessColumn";
            $compiled = $this->blade->compileString($template);
            $this->assertStringContainsString($column, $compiled);
        }
    }

    /**
     * Test that @canAccessColumn and @canAccessRow directives can be combined.
     *
     * @return void
     */
    public function test_can_access_column_and_row_directives_combined(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post)@canAccessColumn('posts.edit', \$post, 'status')<input name=\"status\">@endcanAccessColumn@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('canAccessRow', $compiled);
        $this->assertStringContainsString('canAccessColumn', $compiled);
        $this->assertStringContainsString('status', $compiled);
    }

    /**
     * Test that @canAccessJsonAttribute directive compiles correctly.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_compiles_correctly(): void
    {
        BladeDirectives::register();
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'seo.title')<input type=\"text\" name=\"metadata[seo][title]\">@endcanAccessJsonAttribute";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('<?php if', $compiled);
        $this->assertStringContainsString('canAccessJsonAttribute', $compiled);
        $this->assertStringContainsString('posts.edit', $compiled);
        $this->assertStringContainsString('$post', $compiled);
        $this->assertStringContainsString('metadata', $compiled);
        $this->assertStringContainsString('seo.title', $compiled);
        $this->assertStringContainsString('<input type="text" name="metadata[seo][title]">', $compiled);
        $this->assertStringContainsString('endif', $compiled);
    }

    /**
     * Test that @canAccessJsonAttribute directive compiles with correct parameters.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_compiles_with_parameters(): void
    {
        BladeDirectives::register();
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'social.twitter')<input name=\"metadata[social][twitter]\">@endcanAccessJsonAttribute";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('posts.edit', $compiled);
        $this->assertStringContainsString('$post', $compiled);
        $this->assertStringContainsString('metadata', $compiled);
        $this->assertStringContainsString('social.twitter', $compiled);
        $this->assertStringContainsString('<input name="metadata[social][twitter]">', $compiled);
    }

    /**
     * Test that @canAccessJsonAttribute directive can be nested.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_can_be_nested(): void
    {
        BladeDirectives::register();
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'seo')<div class=\"seo-fields\"><input name=\"metadata[seo][title]\"><input name=\"metadata[seo][description]\"></div>@endcanAccessJsonAttribute";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('<div class="seo-fields">', $compiled);
        $this->assertStringContainsString('<input name="metadata[seo][title]">', $compiled);
        $this->assertStringContainsString('<input name="metadata[seo][description]">', $compiled);
    }

    /**
     * Test that multiple @canAccessJsonAttribute directives can be used.
     *
     * @return void
     */
    public function test_multiple_can_access_json_attribute_directives(): void
    {
        BladeDirectives::register();
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'seo.title')<input name=\"seo_title\">@endcanAccessJsonAttribute @canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'social.twitter')<input name=\"twitter\">@endcanAccessJsonAttribute";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('seo.title', $compiled);
        $this->assertStringContainsString('social.twitter', $compiled);
        $this->assertStringContainsString('<input name="seo_title">', $compiled);
        $this->assertStringContainsString('<input name="twitter">', $compiled);
    }

    /**
     * Test that @canAccessJsonAttribute directive works with wildcard paths.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_with_wildcard_paths(): void
    {
        BladeDirectives::register();
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'seo.*')<div class=\"seo-section\">SEO Fields</div>@endcanAccessJsonAttribute";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('seo.*', $compiled);
        $this->assertStringContainsString('<div class="seo-section">SEO Fields</div>', $compiled);
    }

    /**
     * Test that @canAccessJsonAttribute directive works with nested paths.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_with_nested_paths(): void
    {
        BladeDirectives::register();
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'layout.header.background.color')<input type=\"color\" name=\"header_bg\">@endcanAccessJsonAttribute";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('layout.header.background.color', $compiled);
        $this->assertStringContainsString('<input type="color" name="header_bg">', $compiled);
    }

    /**
     * Test that all three directives can be combined.
     *
     * @return void
     */
    public function test_all_three_directives_combined(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post)@canAccessColumn('posts.edit', \$post, 'metadata')@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'seo.title')<input name=\"seo_title\">@endcanAccessJsonAttribute@endcanAccessColumn@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('canAccessRow', $compiled);
        $this->assertStringContainsString('canAccessColumn', $compiled);
        $this->assertStringContainsString('canAccessJsonAttribute', $compiled);
        $this->assertStringContainsString('metadata', $compiled);
        $this->assertStringContainsString('seo.title', $compiled);
    }

    /**
     * Test that directives use Blade::check() for conditional rendering.
     *
     * @return void
     */
    public function test_directives_use_blade_check(): void
    {
        BladeDirectives::register();
        $template = "@canAccessRow('posts.edit', \$post)<button>Edit</button>@endcanAccessRow";
        $compiled = $this->blade->compileString($template);

        $this->assertStringContainsString('Blade::check', $compiled);
        $this->assertStringContainsString('canAccessRow', $compiled);
    }
}
