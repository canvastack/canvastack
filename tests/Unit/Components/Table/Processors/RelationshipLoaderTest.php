<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Processors;

use Canvastack\Canvastack\Components\Table\Processors\RelationshipLoader;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * Unit tests for RelationshipLoader.
 */
class RelationshipLoaderTest extends TestCase
{
    private RelationshipLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = new RelationshipLoader();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test loadRelations with valid relationships.
     */
    public function test_load_relations_with_valid_relationships(): void
    {
        // Create an anonymous class that extends Model with the relationships
        $model = new class () extends Model {
            public function user()
            {
                return $this->belongsTo(Model::class);
            }

            public function category()
            {
                return $this->belongsTo(Model::class);
            }
        };

        // Mock the load method
        $modelMock = Mockery::mock($model)->makePartial();
        $modelMock->shouldReceive('load')
            ->once()
            ->with(['user', 'category'])
            ->andReturn($modelMock);

        $result = $this->loader->loadRelations($modelMock, ['user', 'category']);

        $this->assertSame($modelMock, $result);
    }

    /**
     * Test loadRelations with empty relations array.
     */
    public function test_load_relations_with_empty_array(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldNotReceive('load');

        $result = $this->loader->loadRelations($model, []);

        $this->assertSame($model, $result);
    }

    /**
     * Test loadRelations throws exception for non-existent relationship.
     */
    public function test_load_relations_throws_exception_for_invalid_relationship(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage("Relationship method 'nonExistent' does not exist");

        $model = Mockery::mock(Model::class);
        $model->shouldReceive('load')->never();

        $this->loader->loadRelations($model, ['nonExistent']);
    }

    /**
     * Test replaceFieldValues with valid replacements.
     */
    public function test_replace_field_values_with_valid_replacements(): void
    {
        // Create mock related model
        $relatedModel = Mockery::mock(Model::class)->makePartial();
        $relatedModel->name = 'John Doe';

        // Create mock main model with shouldIgnoreMissing to allow setAttribute
        $model = Mockery::mock(Model::class)->makePartial()->shouldIgnoreMissing();
        $model->user_id = 1;
        $model->shouldReceive('relationLoaded')
            ->with('user')
            ->andReturn(true);
        $model->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($relatedModel);
        $model->user = $relatedModel;

        $collection = new Collection([$model]);

        $replacements = [
            [
                'field' => 'user_id',
                'relation' => 'user',
                'display' => 'name',
            ],
        ];

        $result = $this->loader->replaceFieldValues($collection, $replacements);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /**
     * Test replaceFieldValues with empty replacements.
     */
    public function test_replace_field_values_with_empty_replacements(): void
    {
        $collection = new Collection([]);

        $result = $this->loader->replaceFieldValues($collection, []);

        $this->assertSame($collection, $result);
    }

    /**
     * Test replaceFieldValues handles missing relationship.
     */
    public function test_replace_field_values_handles_missing_relationship(): void
    {
        $model = Mockery::mock(Model::class)->makePartial()->shouldIgnoreMissing();
        $model->user_id = 1;
        $model->shouldReceive('relationLoaded')
            ->with('user')
            ->andReturn(false);

        $collection = new Collection([$model]);

        $replacements = [
            [
                'field' => 'user_id',
                'relation' => 'user',
                'display' => 'name',
            ],
        ];

        $result = $this->loader->replaceFieldValues($collection, $replacements);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(1, $result->first()->user_id);
    }

    /**
     * Test replaceFieldValues handles null related model.
     */
    public function test_replace_field_values_handles_null_related_model(): void
    {
        $model = Mockery::mock(Model::class)->makePartial()->shouldIgnoreMissing();
        $model->user_id = 1;
        $model->shouldReceive('relationLoaded')
            ->with('user')
            ->andReturn(true);
        $model->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn(null);
        $model->user = null;

        $collection = new Collection([$model]);

        $replacements = [
            [
                'field' => 'user_id',
                'relation' => 'user',
                'display' => 'name',
            ],
        ];

        $result = $this->loader->replaceFieldValues($collection, $replacements);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test cacheRelationalData stores data in cache.
     */
    public function test_cache_relational_data_stores_in_cache(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('put')
            ->once()
            ->with('test_key', ['data'], 300)
            ->andReturn(true);

        $this->loader->cacheRelationalData('test_key', ['data'], 300);

        // Add assertion to avoid risky test warning
        $this->assertTrue(true);
    }

    /**
     * Test cacheRelationalData with custom TTL.
     */
    public function test_cache_relational_data_with_custom_ttl(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('put')
            ->once()
            ->with('test_key', ['data'], 600)
            ->andReturn(true);

        $this->loader->cacheRelationalData('test_key', ['data'], 600);

        // Add assertion to avoid risky test warning
        $this->assertTrue(true);
    }

    /**
     * Test getCachedRelationalData retrieves from cache.
     */
    public function test_get_cached_relational_data_retrieves_from_cache(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('get')
            ->once()
            ->with('test_key')
            ->andReturn(['cached_data']);

        $result = $this->loader->getCachedRelationalData('test_key');

        $this->assertEquals(['cached_data'], $result);
    }

    /**
     * Test getCachedRelationalData returns null when not found.
     */
    public function test_get_cached_relational_data_returns_null_when_not_found(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('get')
            ->once()
            ->with('test_key')
            ->andReturn(null);

        $result = $this->loader->getCachedRelationalData('test_key');

        $this->assertNull($result);
    }

    /**
     * Test invalidateCache removes data from cache.
     */
    public function test_invalidate_cache_removes_from_cache(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('forget')
            ->once()
            ->with('test_key')
            ->andReturn(true);

        $this->loader->invalidateCache('test_key');

        // Add assertion to avoid risky test warning
        $this->assertTrue(true);
    }

    /**
     * Test generateCacheKey creates unique key.
     */
    public function test_generate_cache_key_creates_unique_key(): void
    {
        $key = $this->loader->generateCacheKey(
            'App\\Models\\User',
            ['posts', 'comments']
        );

        $this->assertStringContainsString('table_relations', $key);
        $this->assertStringContainsString('User', $key);
        $this->assertStringContainsString('posts_comments', $key);
    }

    /**
     * Test generateCacheKey with conditions.
     */
    public function test_generate_cache_key_with_conditions(): void
    {
        $key1 = $this->loader->generateCacheKey(
            'App\\Models\\User',
            ['posts'],
            ['status' => 'active']
        );

        $key2 = $this->loader->generateCacheKey(
            'App\\Models\\User',
            ['posts'],
            ['status' => 'inactive']
        );

        $this->assertNotEquals($key1, $key2);
    }

    /**
     * Test generateCacheKey without conditions.
     */
    public function test_generate_cache_key_without_conditions(): void
    {
        $key = $this->loader->generateCacheKey(
            'App\\Models\\User',
            ['posts']
        );

        $this->assertStringContainsString('table_relations:User:posts', $key);
    }

    /**
     * Test replaceFieldValues with incomplete replacement config.
     */
    public function test_replace_field_values_with_incomplete_config(): void
    {
        $model = Mockery::mock(Model::class);
        $collection = new Collection([$model]);

        // Missing 'display' key
        $replacements = [
            [
                'field' => 'user_id',
                'relation' => 'user',
            ],
        ];

        $result = $this->loader->replaceFieldValues($collection, $replacements);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test replaceFieldValues with multiple replacements.
     */
    public function test_replace_field_values_with_multiple_replacements(): void
    {
        $userModel = Mockery::mock(Model::class)->makePartial();
        $userModel->name = 'John Doe';

        $categoryModel = Mockery::mock(Model::class)->makePartial();
        $categoryModel->title = 'Technology';

        $model = Mockery::mock(Model::class)->makePartial()->shouldIgnoreMissing();
        $model->user_id = 1;
        $model->category_id = 2;
        $model->shouldReceive('relationLoaded')
            ->with('user')
            ->andReturn(true);
        $model->shouldReceive('relationLoaded')
            ->with('category')
            ->andReturn(true);
        $model->shouldReceive('getAttribute')
            ->with('user')
            ->andReturn($userModel);
        $model->shouldReceive('getAttribute')
            ->with('category')
            ->andReturn($categoryModel);
        $model->user = $userModel;
        $model->category = $categoryModel;

        $collection = new Collection([$model]);

        $replacements = [
            [
                'field' => 'user_id',
                'relation' => 'user',
                'display' => 'name',
            ],
            [
                'field' => 'category_id',
                'relation' => 'category',
                'display' => 'title',
            ],
        ];

        $result = $this->loader->replaceFieldValues($collection, $replacements);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    /**
     * Test cacheRelationalData handles cache failures gracefully.
     */
    public function test_cache_relational_data_handles_failures_gracefully(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache error'));

        // Should not throw exception
        $this->loader->cacheRelationalData('test_key', ['data']);

        $this->assertTrue(true); // If we get here, the test passed
    }

    /**
     * Test getCachedRelationalData handles cache failures gracefully.
     */
    public function test_get_cached_relational_data_handles_failures_gracefully(): void
    {
        // Mock config to return 'redis'
        config(['cache.default' => 'redis']);

        Cache::shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('Cache error'));

        $result = $this->loader->getCachedRelationalData('test_key');

        $this->assertNull($result);
    }
}
