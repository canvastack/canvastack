<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database;

use Canvastack\Canvastack\Tests\Fixtures\Factories\UserFactory;
use Canvastack\Canvastack\Tests\Fixtures\Models\City;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\Province;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for verifying test database schema.
 *
 * This test ensures that all required columns exist in test tables.
 */
class TestSchemaTest extends TestCase
{
    /**
     * Test that users table has active column.
     *
     * @return void
     */
    public function test_users_table_has_active_column(): void
    {
        $schema = Capsule::schema();

        $this->assertTrue(
            $schema->hasColumn('users', 'active'),
            'users table should have active column'
        );
    }

    /**
     * Test that posts table has status column.
     *
     * @return void
     */
    public function test_posts_table_has_status_column(): void
    {
        $schema = Capsule::schema();

        $this->assertTrue(
            $schema->hasColumn('posts', 'status'),
            'posts table should have status column'
        );
    }

    /**
     * Test that user can be created with active column.
     *
     * @return void
     */
    public function test_user_can_be_created_with_active_column(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'active' => true,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->active);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'active' => 1,
        ]);
    }

    /**
     * Test that user factory includes active column.
     *
     * @return void
     */
    public function test_user_factory_includes_active_column(): void
    {
        $user = UserFactory::new()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->active);
        $this->assertTrue($user->active);
    }

    /**
     * Test that post can be created with status column.
     *
     * @return void
     */
    public function test_post_can_be_created_with_status_column(): void
    {
        $user = UserFactory::new()->create();

        $post = Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('draft', $post->status);
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'status' => 'draft',
        ]);
    }

    /**
     * Test that post status can be updated.
     *
     * @return void
     */
    public function test_post_status_can_be_updated(): void
    {
        $user = UserFactory::new()->create();

        $post = Post::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);

        $post->update(['status' => 'published']);

        $this->assertEquals('published', $post->fresh()->status);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);
    }

    /**
     * Test that test_provinces table exists.
     *
     * @return void
     */
    public function test_provinces_table_exists(): void
    {
        $schema = Capsule::schema();

        $this->assertTrue(
            $schema->hasTable('test_provinces'),
            'test_provinces table should exist'
        );
    }

    /**
     * Test that test_provinces table has required columns.
     *
     * @return void
     */
    public function test_provinces_table_has_required_columns(): void
    {
        $schema = Capsule::schema();

        $columns = ['id', 'name', 'code', 'created_at', 'updated_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('test_provinces', $column),
                "test_provinces table should have {$column} column"
            );
        }
    }

    /**
     * Test that test_cities table exists.
     *
     * @return void
     */
    public function test_cities_table_exists(): void
    {
        $schema = Capsule::schema();

        $this->assertTrue(
            $schema->hasTable('test_cities'),
            'test_cities table should exist'
        );
    }

    /**
     * Test that test_cities table has required columns.
     *
     * @return void
     */
    public function test_cities_table_has_required_columns(): void
    {
        $schema = Capsule::schema();

        $columns = ['id', 'province_id', 'name', 'code', 'created_at', 'updated_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn('test_cities', $column),
                "test_cities table should have {$column} column"
            );
        }
    }

    /**
     * Test that province factory works correctly.
     *
     * @return void
     */
    public function test_province_factory_works(): void
    {
        $province = Province::factory()->create();

        $this->assertInstanceOf(Province::class, $province);
        $this->assertNotEmpty($province->name);
        $this->assertNotEmpty($province->code);
        $this->assertDatabaseHas('test_provinces', [
            'id' => $province->id,
        ]);
    }

    /**
     * Test that city factory works correctly.
     *
     * @return void
     */
    public function test_city_factory_works(): void
    {
        $city = City::factory()->create();

        $this->assertInstanceOf(City::class, $city);
        $this->assertNotEmpty($city->name);
        $this->assertNotEmpty($city->code);
        $this->assertNotNull($city->province_id);
        $this->assertDatabaseHas('test_cities', [
            'id' => $city->id,
        ]);
    }

    /**
     * Test that city-province relationship works.
     *
     * @return void
     */
    public function test_city_province_relationship_works(): void
    {
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);

        $this->assertEquals($province->id, $city->province->id);
        $this->assertEquals($province->name, $city->province->name);
        $this->assertTrue($province->cities->contains($city));
    }
}
