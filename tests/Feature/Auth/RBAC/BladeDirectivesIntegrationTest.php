<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;

/**
 * Integration test for Blade directives with Fine-Grained Permissions.
 *
 * Tests @canAccessRow, @canAccessColumn, @canAccessJsonAttribute directives.
 */
class BladeDirectivesIntegrationTest extends TestCase
{
    /**
     * Test model class.
     */
    protected $postModel;

    /**
     * Auth guard mock.
     */
    protected static $authGuard = null;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard mock
        if (self::$authGuard === null) {
            self::$authGuard = new class () {
                protected $user = null;

                public function user()
                {
                    return $this->user;
                }

                public function id()
                {
                    return $this->user ? $this->user->id : null;
                }

                public function check()
                {
                    return $this->user !== null;
                }

                public function setUser($user)
                {
                    $this->user = $user;
                }
            };
        }

        // Bind to container
        $app = \Illuminate\Container\Container::getInstance();
        $app->singleton('auth', function () {
            return self::$authGuard;
        });

        // Create test table
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Define test model
        $this->postModel = new class () extends Model {
            protected $table = 'test_posts';

            protected $fillable = ['title', 'content', 'user_id', 'status', 'metadata'];

            protected $casts = [
                'metadata' => 'array',
            ];
        };
    }

    /**
     * Set authenticated user.
     *
     * @param object $user
     * @return void
     */
    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    /**
     * Cleanup test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->dropIfExists('test_posts');

        parent::tearDown();
    }

    /**
     * Test @canAccessRow directive shows content when allowed.
     *
     * @return void
     */
    public function test_can_access_row_directive_shows_content_when_allowed(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'My Post',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Create blade template
        $template = "@canAccessRow('posts.edit', \$post)<div>Edit Button</div>@endcanAccessRow";

        // Act
        $compiled = Blade::compileString($template);

        // Evaluate the compiled template
        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert
        $this->assertStringContainsString('Edit Button', $output);
    }

    /**
     * Test @canAccessRow directive hides content when denied.
     *
     * @return void
     */
    public function test_can_access_row_directive_hides_content_when_denied(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Other Post',
            'content' => 'Content',
            'user_id' => 999, // Different user
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Create blade template
        $template = "@canAccessRow('posts.edit', \$post)<div>Edit Button</div>@endcanAccessRow";

        // Act
        $compiled = Blade::compileString($template);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert
        $this->assertStringNotContainsString('Edit Button', $output);
    }

    /**
     * Test @canAccessColumn directive shows content when allowed.
     *
     * @return void
     */
    public function test_can_access_column_directive_shows_content_when_allowed(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title', 'content'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Create blade template
        $template = "@canAccessColumn('posts.edit', \$post, 'title')<input name=\"title\">@endcanAccessColumn";

        // Act
        $compiled = Blade::compileString($template);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert
        $this->assertStringContainsString('<input name="title">', $output);
    }

    /**
     * Test @canAccessColumn directive hides content when denied.
     *
     * @return void
     */
    public function test_can_access_column_directive_hides_content_when_denied(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title'],
                'denied_columns' => ['status'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Create blade template
        $template = "@canAccessColumn('posts.edit', \$post, 'status')<input name=\"status\">@endcanAccessColumn";

        // Act
        $compiled = Blade::compileString($template);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert
        $this->assertStringNotContainsString('<input name="status">', $output);
    }

    /**
     * Test @canAccessJsonAttribute directive shows content when allowed.
     *
     * @return void
     */
    public function test_can_access_json_attribute_directive_shows_content_when_allowed(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
            'metadata' => ['seo' => ['title' => 'SEO Title']],
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
                'denied_paths' => [],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Create blade template
        $template = "@canAccessJsonAttribute('posts.edit', \$post, 'metadata', 'seo.title')<input name=\"seo_title\">@endcanAccessJsonAttribute";

        // Act
        $compiled = Blade::compileString($template);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert
        $this->assertStringContainsString('<input name="seo_title">', $output);
    }

    /**
     * Test blade directives work with guest users.
     *
     * @return void
     */
    public function test_blade_directives_work_with_guest_users(): void
    {
        // Arrange - No authenticated user
        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => 1,
        ]);

        // Create blade template
        $template = "@canAccessRow('posts.edit', \$post)<div>Edit Button</div>@endcanAccessRow";

        // Act
        $compiled = Blade::compileString($template);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert - Content should be hidden for guest
        $this->assertStringNotContainsString('Edit Button', $output);
    }

    /**
     * Test blade directives can be nested.
     *
     * @return void
     */
    public function test_blade_directives_can_be_nested(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
            'module' => 'posts',
        ]);

        $user = User::create([
            'name' => 'John Editor',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $post = $this->postModel::create([
            'title' => 'Post',
            'content' => 'Content',
            'user_id' => $user->id,
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'conditions' => ['user_id' => '{{auth.id}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        PermissionRule::create([
            'permission_id' => $permission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->postModel),
                'allowed_columns' => ['title'],
                'denied_columns' => [],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $this->actingAs($user);

        // Create nested blade template
        $template = "@canAccessRow('posts.edit', \$post)@canAccessColumn('posts.edit', \$post, 'title')<input name=\"title\">@endcanAccessColumn@endcanAccessRow";

        // Act
        $compiled = Blade::compileString($template);

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        // Assert
        $this->assertStringContainsString('<input name="title">', $output);
    }
}
