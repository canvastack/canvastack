<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Traits;

use Canvastack\Canvastack\Support\Traits\HasEagerLoading;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for HasEagerLoading trait.
 */
class HasEagerLoadingTest extends TestCase
{
    protected object $instance;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class using the trait
        $this->instance = new class
        {
            use HasEagerLoading;
        };
    }

    /**
     * Test that with() sets eager load relations.
     */
    public function test_with_sets_eager_load_relations(): void
    {
        $this->instance->with(['user', 'posts']);

        $relations = $this->instance->getEagerLoad();

        $this->assertEquals(['user', 'posts'], $relations);
    }

    /**
     * Test that addWith() adds a relation.
     */
    public function test_add_with_adds_relation(): void
    {
        $this->instance->with(['user']);
        $this->instance->addWith('posts');

        $relations = $this->instance->getEagerLoad();

        $this->assertContains('user', $relations);
        $this->assertContains('posts', $relations);
    }

    /**
     * Test that addWith() doesn't add duplicate relations.
     */
    public function test_add_with_prevents_duplicates(): void
    {
        $this->instance->with(['user']);
        $this->instance->addWith('user');

        $relations = $this->instance->getEagerLoad();

        $this->assertCount(1, $relations);
    }

    /**
     * Test that getEagerLoad() returns relations.
     */
    public function test_get_eager_load_returns_relations(): void
    {
        $this->instance->with(['user', 'posts']);

        $relations = $this->instance->getEagerLoad();

        $this->assertIsArray($relations);
        $this->assertCount(2, $relations);
    }

    /**
     * Test that clearEagerLoad() clears relations.
     */
    public function test_clear_eager_load_clears_relations(): void
    {
        $this->instance->with(['user', 'posts']);
        $this->instance->clearEagerLoad();

        $relations = $this->instance->getEagerLoad();

        $this->assertEmpty($relations);
    }

    /**
     * Test that hasEagerLoad() checks if relation exists.
     */
    public function test_has_eager_load_checks_relation_exists(): void
    {
        $this->instance->with(['user', 'posts']);

        $this->assertTrue($this->instance->hasEagerLoad('user'));
        $this->assertTrue($this->instance->hasEagerLoad('posts'));
        $this->assertFalse($this->instance->hasEagerLoad('comments'));
    }

    /**
     * Test that with() returns self for chaining.
     */
    public function test_with_returns_self_for_chaining(): void
    {
        $result = $this->instance->with(['user']);

        $this->assertSame($this->instance, $result);
    }

    /**
     * Test that addWith() returns self for chaining.
     */
    public function test_add_with_returns_self_for_chaining(): void
    {
        $result = $this->instance->addWith('user');

        $this->assertSame($this->instance, $result);
    }

    /**
     * Test that clearEagerLoad() returns self for chaining.
     */
    public function test_clear_eager_load_returns_self_for_chaining(): void
    {
        $result = $this->instance->clearEagerLoad();

        $this->assertSame($this->instance, $result);
    }

    /**
     * Test that eager load is empty by default.
     */
    public function test_eager_load_is_empty_by_default(): void
    {
        $relations = $this->instance->getEagerLoad();

        $this->assertEmpty($relations);
    }
}
