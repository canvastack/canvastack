<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Cache;

use Canvastack\Canvastack\Support\Cache\CacheManager;
use Canvastack\Canvastack\Support\Cache\QueryCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;

/**
 * Test for QueryCache.
 */
class QueryCacheTest extends TestCase
{
    protected QueryCache $queryCache;

    protected CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = Mockery::mock(CacheManager::class);
        $this->queryCache = new QueryCache($this->cacheManager);
    }

    /**
     * Test that query cache can remember query results.
     */
    public function test_remember_caches_query_results(): void
    {
        $model = Mockery::mock(Model::class);
        $query = Mockery::mock(Builder::class);

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM users');
        $query->shouldReceive('getBindings')->andReturn([]);
        $query->shouldReceive('getModel')->andReturn($model);
        $query->shouldReceive('get')->andReturn(collect(['user1', 'user2']));

        $model->shouldReceive('getTable')->andReturn('users');

        $this->cacheManager->shouldReceive('tags')
            ->with(['queries'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn(collect(['user1', 'user2']));

        $result = $this->queryCache->remember($query);

        $this->assertCount(2, $result);
    }

    /**
     * Test that query cache can remember single result.
     */
    public function test_remember_one_caches_single_result(): void
    {
        $model = Mockery::mock(Model::class);
        $query = Mockery::mock(Builder::class);

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM users WHERE id = ?');
        $query->shouldReceive('getBindings')->andReturn([1]);
        $query->shouldReceive('getModel')->andReturn($model);
        $query->shouldReceive('first')->andReturn('user1');

        $model->shouldReceive('getTable')->andReturn('users');

        $this->cacheManager->shouldReceive('tags')
            ->with(['queries'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn('user1');

        $result = $this->queryCache->rememberOne($query);

        $this->assertEquals('user1', $result);
    }

    /**
     * Test that query cache can remember count.
     */
    public function test_remember_count_caches_count_result(): void
    {
        $model = Mockery::mock(Model::class);
        $query = Mockery::mock(Builder::class);

        $query->shouldReceive('toSql')->andReturn('SELECT COUNT(*) FROM users');
        $query->shouldReceive('getBindings')->andReturn([]);
        $query->shouldReceive('getModel')->andReturn($model);
        $query->shouldReceive('count')->andReturn(10);

        $model->shouldReceive('getTable')->andReturn('users');

        $this->cacheManager->shouldReceive('tags')
            ->with(['queries'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn(10);

        $result = $this->queryCache->rememberCount($query);

        $this->assertEquals(10, $result);
    }

    /**
     * Test that query cache uses custom TTL.
     */
    public function test_remember_uses_custom_ttl(): void
    {
        $model = Mockery::mock(Model::class);
        $query = Mockery::mock(Builder::class);

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM users');
        $query->shouldReceive('getBindings')->andReturn([]);
        $query->shouldReceive('getModel')->andReturn($model);
        $query->shouldReceive('get')->andReturn(collect([]));

        $model->shouldReceive('getTable')->andReturn('users');

        $this->cacheManager->shouldReceive('tags')
            ->with(['queries'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('remember')
            ->with(Mockery::type('string'), 600, Mockery::type('Closure'))
            ->once()
            ->andReturn(collect([]));

        $this->queryCache->remember($query, 600);

        $this->assertTrue(true);
    }

    /**
     * Test that query cache uses additional tags.
     */
    public function test_remember_uses_additional_tags(): void
    {
        $model = Mockery::mock(Model::class);
        $query = Mockery::mock(Builder::class);

        $query->shouldReceive('toSql')->andReturn('SELECT * FROM users');
        $query->shouldReceive('getBindings')->andReturn([]);
        $query->shouldReceive('getModel')->andReturn($model);
        $query->shouldReceive('get')->andReturn(collect([]));

        $model->shouldReceive('getTable')->andReturn('users');

        $this->cacheManager->shouldReceive('tags')
            ->with(['queries', 'users', 'custom'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn(collect([]));

        $this->queryCache->remember($query, null, ['users', 'custom']);

        $this->assertTrue(true);
    }

    /**
     * Test that query cache can invalidate by tags.
     */
    public function test_invalidate_flushes_cache_by_tags(): void
    {
        $this->cacheManager->shouldReceive('tags')
            ->with(['queries', 'users'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->queryCache->invalidate(['users']);

        $this->assertTrue($result);
    }

    /**
     * Test that query cache can invalidate all.
     */
    public function test_invalidate_all_flushes_all_query_caches(): void
    {
        $this->cacheManager->shouldReceive('tags')
            ->with(['queries'])
            ->andReturnSelf();

        $this->cacheManager->shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $result = $this->queryCache->invalidateAll();

        $this->assertTrue($result);
    }

    /**
     * Test that query cache can set default TTL.
     */
    public function test_set_default_ttl_changes_default_ttl(): void
    {
        $this->cacheManager->shouldReceive('getDriver')->andReturn('redis');

        $this->queryCache->setDefaultTtl(600);

        $stats = $this->queryCache->getStats();

        $this->assertEquals(600, $stats['default_ttl']);
    }

    /**
     * Test that query cache can set tags.
     */
    public function test_set_tags_changes_default_tags(): void
    {
        $this->cacheManager->shouldReceive('getDriver')->andReturn('redis');

        $this->queryCache->setTags(['custom', 'tags']);

        $stats = $this->queryCache->getStats();

        $this->assertEquals(['custom', 'tags'], $stats['tags']);
    }

    /**
     * Test that query cache can add tag.
     */
    public function test_add_tag_adds_tag_to_list(): void
    {
        $this->cacheManager->shouldReceive('getDriver')->andReturn('redis');

        $this->queryCache->addTag('custom');

        $stats = $this->queryCache->getStats();

        $this->assertContains('custom', $stats['tags']);
    }

    /**
     * Test that query cache generates unique keys for different queries.
     */
    public function test_generate_key_creates_unique_keys_for_different_queries(): void
    {
        $model1 = Mockery::mock(Model::class);
        $query1 = Mockery::mock(Builder::class);

        $query1->shouldReceive('toSql')->andReturn('SELECT * FROM users');
        $query1->shouldReceive('getBindings')->andReturn([]);
        $query1->shouldReceive('getModel')->andReturn($model1);
        $query1->shouldReceive('get')->andReturn(collect([]));

        $model1->shouldReceive('getTable')->andReturn('users');

        $model2 = Mockery::mock(Model::class);
        $query2 = Mockery::mock(Builder::class);

        $query2->shouldReceive('toSql')->andReturn('SELECT * FROM posts');
        $query2->shouldReceive('getBindings')->andReturn([]);
        $query2->shouldReceive('getModel')->andReturn($model2);
        $query2->shouldReceive('get')->andReturn(collect([]));

        $model2->shouldReceive('getTable')->andReturn('posts');

        $key1 = null;
        $key2 = null;

        $this->cacheManager->shouldReceive('tags')->andReturnSelf();
        $this->cacheManager->shouldReceive('remember')
            ->twice()
            ->andReturnUsing(function ($key, $ttl, $callback) use (&$key1, &$key2) {
                if ($key1 === null) {
                    $key1 = $key;
                } else {
                    $key2 = $key;
                }
                return $callback();
            });

        $this->queryCache->remember($query1);
        $this->queryCache->remember($query2);

        $this->assertNotEquals($key1, $key2);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
