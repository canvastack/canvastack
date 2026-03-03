<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Database;

use Canvastack\Canvastack\Tests\Fixtures\Models\City;
use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\Province;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test Model Relationships.
 *
 * Ensures all test models have proper relationships and foreign keys.
 */
class ModelRelationshipsTest extends TestCase
{
    /**
     * Test that User has posts relationship.
     *
     * @return void
     */
    public function test_user_has_posts_relationship(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->posts);
        $this->assertCount(1, $user->posts);
        $this->assertEquals($post->id, $user->posts->first()->id);
    }

    /**
     * Test that Post belongs to User.
     *
     * @return void
     */
    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
        $this->assertEquals($user->name, $post->user->name);
    }

    /**
     * Test that Post factory creates user automatically.
     *
     * @return void
     */
    public function test_post_factory_creates_user_automatically(): void
    {
        $post = Post::factory()->create();

        $this->assertNotNull($post->user_id);
        $this->assertInstanceOf(User::class, $post->user);
        $this->assertDatabaseHas('users', ['id' => $post->user_id]);
    }

    /**
     * Test that Province has cities relationship.
     *
     * @return void
     */
    public function test_province_has_cities_relationship(): void
    {
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $province->cities);
        $this->assertCount(1, $province->cities);
        $this->assertEquals($city->id, $province->cities->first()->id);
    }

    /**
     * Test that City belongs to Province.
     *
     * @return void
     */
    public function test_city_belongs_to_province(): void
    {
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);

        $this->assertInstanceOf(Province::class, $city->province);
        $this->assertEquals($province->id, $city->province->id);
        $this->assertEquals($province->name, $city->province->name);
    }

    /**
     * Test that City factory creates province automatically.
     *
     * @return void
     */
    public function test_city_factory_creates_province_automatically(): void
    {
        $city = City::factory()->create();

        $this->assertNotNull($city->province_id);
        $this->assertInstanceOf(Province::class, $city->province);
        $this->assertDatabaseHas('test_provinces', ['id' => $city->province_id]);
    }

    /**
     * Test that deleting user cascades to posts.
     *
     * @return void
     */
    public function test_deleting_user_cascades_to_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $postId = $post->id;

        // Force delete user (bypass soft deletes)
        $user->forceDelete();

        // Post should be deleted due to cascade
        $this->assertDatabaseMissing('posts', ['id' => $postId]);
    }

    /**
     * Test that deleting province cascades to cities.
     *
     * @return void
     */
    public function test_deleting_province_cascades_to_cities(): void
    {
        $province = Province::factory()->create();
        $city = City::factory()->create(['province_id' => $province->id]);

        $cityId = $city->id;

        // Delete province
        $province->delete();

        // City should be deleted due to cascade
        $this->assertDatabaseMissing('test_cities', ['id' => $cityId]);
    }

    /**
     * Test that User can have multiple posts.
     *
     * @return void
     */
    public function test_user_can_have_multiple_posts(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->posts);
        $this->assertEquals($posts->pluck('id')->sort()->values(), $user->posts->pluck('id')->sort()->values());
    }

    /**
     * Test that Province can have multiple cities.
     *
     * @return void
     */
    public function test_province_can_have_multiple_cities(): void
    {
        $province = Province::factory()->create();
        $cities = City::factory()->count(3)->create(['province_id' => $province->id]);

        $this->assertCount(3, $province->cities);
        $this->assertEquals($cities->pluck('id')->sort()->values(), $province->cities->pluck('id')->sort()->values());
    }

    /**
     * Test that Post forUser method works.
     *
     * @return void
     */
    public function test_post_for_user_method_works(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $post->user_id);
        $this->assertEquals($user->id, $post->user->id);
    }

    /**
     * Test that eager loading works for User posts.
     *
     * @return void
     */
    public function test_eager_loading_works_for_user_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        // Eager load posts
        $userWithPosts = User::with('posts')->find($user->id);

        $this->assertCount(3, $userWithPosts->posts);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $userWithPosts->posts);
    }

    /**
     * Test that eager loading works for Province cities.
     *
     * @return void
     */
    public function test_eager_loading_works_for_province_cities(): void
    {
        $province = Province::factory()->create();
        City::factory()->count(3)->create(['province_id' => $province->id]);

        // Eager load cities
        $provinceWithCities = Province::with('cities')->find($province->id);

        $this->assertCount(3, $provinceWithCities->cities);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $provinceWithCities->cities);
    }
}
