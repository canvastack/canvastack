<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\TableCacheInvalidator;
use Canvastack\Canvastack\Components\Table\Support\TableCacheManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Mockery;

/**
 * Test for TableCacheInvalidator.
 */
class TableCacheInvalidatorTest extends TestCase
{
    protected TableCacheInvalidator $invalidator;
    protected TableCacheManager $cacheManager;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = Mockery::mock(TableCacheManager::class);
        $this->invalidator = new TableCacheInvalidator($this->cacheManager);
    }

    /**
     * Teardown test environment.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that invalidator can be instantiated.
     */
    public function test_invalidator_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TableCacheInvalidator::class, $this->invalidator);
    }

    /**
     * Test that cache manager can be retrieved.
     */
    public function test_cache_manager_can_be_retrieved(): void
    {
        $manager = $this->invalidator->getCacheManager();

        $this->assertInstanceOf(TableCacheManager::class, $manager);
        $this->assertSame($this->cacheManager, $manager);
    }

    /**
     * Test that cache manager can be set.
     */
    public function test_cache_manager_can_be_set(): void
    {
        $newManager = Mockery::mock(TableCacheManager::class);
        
        $this->invalidator->setCacheManager($newManager);
        
        $this->assertSame($newManager, $this->invalidator->getCacheManager());
    }

    /**
     * Test that model events are returned.
     */
    public function test_model_events_are_returned(): void
    {
        $events = $this->invalidator->getModelEvents();

        $this->assertIsArray($events);
        $this->assertContains('created', $events);
        $this->assertContains('updated', $events);
        $this->assertContains('deleted', $events);
        $this->assertContains('restored', $events);
        $this->assertContains('forceDeleted', $events);
    }

    /**
     * Test that register creates event listeners.
     */
    public function test_register_creates_event_listeners(): void
    {
        $modelClass = TestModel::class;
        $this->invalidator->register($modelClass);

        // Verify listeners were registered by checking if registered
        $this->assertTrue($this->invalidator->isRegistered($modelClass));
    }

    /**
     * Test that register works with model instance.
     */
    public function test_register_works_with_model_instance(): void
    {
        $model = new TestModel();
        $this->invalidator->register($model);

        // Verify listeners were registered
        $this->assertTrue($this->invalidator->isRegistered($model));
    }

    /**
     * Test that invalidateModelCache clears all related caches.
     */
    public function test_invalidate_model_cache_clears_all_related_caches(): void
    {
        $modelClass = TestModel::class;

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->once()
            ->with($modelClass);

        $this->invalidator->invalidateModelCache($modelClass);
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that invalidateModelCache clears related model caches when instance provided.
     */
    public function test_invalidate_model_cache_clears_related_model_caches(): void
    {
        $model = new TestModel();
        $modelClass = get_class($model);

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->atLeast()
            ->once();

        $this->invalidator->invalidateModelCache($modelClass, $model);
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that invalidateInstance works with model instance.
     */
    public function test_invalidate_instance_works_with_model_instance(): void
    {
        $model = new TestModel();
        $modelClass = get_class($model);

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->atLeast()
            ->once();

        $this->invalidator->invalidateInstance($model);
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that invalidateMultiple works with array of models.
     */
    public function test_invalidate_multiple_works_with_array_of_models(): void
    {
        $model1 = new TestModel();
        $model2 = TestModel::class;

        $this->cacheManager->shouldReceive('clearModelCache')
            ->twice();

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->twice();

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->atLeast()
            ->once();

        $this->invalidator->invalidateMultiple([$model1, $model2]);
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that clearAll clears all table caches.
     */
    public function test_clear_all_clears_all_table_caches(): void
    {
        $this->cacheManager->shouldReceive('clearAllTableCaches')
            ->once();

        $this->invalidator->clearAll();
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that registerMultiple registers multiple models.
     */
    public function test_register_multiple_registers_multiple_models(): void
    {
        $models = [TestModel::class, AnotherTestModel::class];
        $this->invalidator->registerMultiple($models);

        foreach ($models as $modelClass) {
            $this->assertTrue($this->invalidator->isRegistered($modelClass));
        }
    }

    /**
     * Test that unregister removes event listeners.
     */
    public function test_unregister_removes_event_listeners(): void
    {
        $modelClass = TestModel::class;
        
        // Register first
        $this->invalidator->register($modelClass);
        $this->assertTrue($this->invalidator->isRegistered($modelClass));
        
        // Then unregister
        $this->invalidator->unregister($modelClass);

        // Verify listeners were removed
        $this->assertFalse($this->invalidator->isRegistered($modelClass));
    }

    /**
     * Test that isRegistered checks if model has listeners.
     */
    public function test_is_registered_checks_if_model_has_listeners(): void
    {
        $modelClass = TestModel::class;

        // Should not be registered initially
        $this->assertFalse($this->invalidator->isRegistered($modelClass));

        // Register
        $this->invalidator->register($modelClass);

        // Should be registered now
        $this->assertTrue($this->invalidator->isRegistered($modelClass));
    }

    /**
     * Test that triggerInvalidation manually triggers cache invalidation.
     */
    public function test_trigger_invalidation_manually_triggers_cache_invalidation(): void
    {
        $modelClass = TestModel::class;

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->once()
            ->with($modelClass);

        $this->invalidator->triggerInvalidation($modelClass, 'created');
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that triggerInvalidation ignores invalid events.
     */
    public function test_trigger_invalidation_ignores_invalid_events(): void
    {
        $modelClass = TestModel::class;

        $this->cacheManager->shouldNotReceive('clearModelCache');
        $this->cacheManager->shouldNotReceive('clearFilterCache');
        $this->cacheManager->shouldNotReceive('clearRelationshipCache');

        $this->invalidator->triggerInvalidation($modelClass, 'invalid_event');
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that event listener triggers cache invalidation on model created.
     */
    public function test_event_listener_triggers_cache_invalidation_on_model_created(): void
    {
        $modelClass = TestModel::class;

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->atLeast()
            ->once();

        // Register listeners
        $this->invalidator->register($modelClass);

        // Fire event
        $model = new TestModel();
        Event::dispatch("eloquent.created: {$modelClass}", $model);
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that event listener triggers cache invalidation on model updated.
     */
    public function test_event_listener_triggers_cache_invalidation_on_model_updated(): void
    {
        $modelClass = TestModel::class;

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->atLeast()
            ->once();

        // Register listeners
        $this->invalidator->register($modelClass);

        // Fire event
        $model = new TestModel();
        Event::dispatch("eloquent.updated: {$modelClass}", $model);
        
        // Verify expectations
        $this->assertTrue(true);
    }

    /**
     * Test that event listener triggers cache invalidation on model deleted.
     */
    public function test_event_listener_triggers_cache_invalidation_on_model_deleted(): void
    {
        $modelClass = TestModel::class;

        $this->cacheManager->shouldReceive('clearModelCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearFilterCache')
            ->once()
            ->with($modelClass);

        $this->cacheManager->shouldReceive('clearRelationshipCache')
            ->atLeast()
            ->once();

        // Register listeners
        $this->invalidator->register($modelClass);

        // Fire event
        $model = new TestModel();
        Event::dispatch("eloquent.deleted: {$modelClass}", $model);
        
        // Verify expectations
        $this->assertTrue(true);
    }
}

/**
 * Test model for testing purposes.
 */
class TestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email'];
}

/**
 * Another test model for testing purposes.
 */
class AnotherTestModel extends Model
{
    protected $table = 'another_test_models';
    protected $fillable = ['title'];
}
