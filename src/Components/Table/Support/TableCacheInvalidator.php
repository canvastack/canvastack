<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

/**
 * TableCacheInvalidator
 * 
 * Listens to model events and automatically invalidates related table caches.
 * Ensures cache consistency by clearing caches when data changes.
 * 
 * @package Canvastack\Canvastack\Components\Table\Support
 */
class TableCacheInvalidator
{
    /**
     * Cache manager instance.
     */
    protected TableCacheManager $cacheManager;

    /**
     * Model events to listen for.
     */
    protected const MODEL_EVENTS = [
        'created',
        'updated',
        'deleted',
        'restored',
        'forceDeleted',
    ];

    /**
     * Constructor.
     * 
     * @param TableCacheManager $cacheManager Cache manager instance
     */
    public function __construct(TableCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Register event listeners for a model.
     * 
     * @param string|Model $model Model class name or instance
     * @return void
     */
    public function register(string|Model $model): void
    {
        $modelClass = is_string($model) ? $model : get_class($model);

        foreach (self::MODEL_EVENTS as $event) {
            $this->registerEvent($modelClass, $event);
        }
    }

    /**
     * Register event listener for specific model event.
     * 
     * @param string $modelClass Model class name
     * @param string $event Event name
     * @return void
     */
    protected function registerEvent(string $modelClass, string $event): void
    {
        $eventName = "eloquent.{$event}: {$modelClass}";

        Event::listen($eventName, function ($model) use ($modelClass) {
            $this->invalidateModelCache($modelClass, $model);
        });
    }

    /**
     * Invalidate all caches related to a model.
     * 
     * @param string $modelClass Model class name
     * @param Model|null $model Model instance (optional)
     * @return void
     */
    public function invalidateModelCache(string $modelClass, ?Model $model = null): void
    {
        // Clear main table cache
        $this->cacheManager->clearModelCache($modelClass);

        // Clear filter caches
        $this->clearFilterCaches($modelClass);

        // Clear relationship caches
        $this->clearRelationshipCaches($modelClass, $model);
    }

    /**
     * Clear filter caches for a model.
     * 
     * @param string $modelClass Model class name
     * @return void
     */
    protected function clearFilterCaches(string $modelClass): void
    {
        $this->cacheManager->clearFilterCache($modelClass);
    }

    /**
     * Clear relationship caches for a model.
     * 
     * @param string $modelClass Model class name
     * @param Model|null $model Model instance (optional)
     * @return void
     */
    protected function clearRelationshipCaches(string $modelClass, ?Model $model = null): void
    {
        // Clear relationship cache for this model
        $this->cacheManager->clearRelationshipCache($modelClass);

        // If model instance provided, clear related models' caches
        if ($model) {
            $this->clearRelatedModelCaches($model);
        }
    }

    /**
     * Clear caches for related models.
     * 
     * @param Model $model Model instance
     * @return void
     */
    protected function clearRelatedModelCaches(Model $model): void
    {
        // Get all relationships defined on the model
        $relationships = $this->getModelRelationships($model);

        foreach ($relationships as $relationName) {
            try {
                // Get the related model class
                $relation = $model->$relationName();
                $relatedClass = get_class($relation->getRelated());

                // Clear cache for related model
                $this->cacheManager->clearModelCache($relatedClass);
                $this->cacheManager->clearRelationshipCache($relatedClass);
            } catch (\Throwable $e) {
                // Skip if relationship cannot be loaded
                continue;
            }
        }
    }

    /**
     * Get all relationship method names from a model.
     * 
     * @param Model $model Model instance
     * @return array Relationship method names
     */
    protected function getModelRelationships(Model $model): array
    {
        $relationships = [];
        $class = get_class($model);
        $methods = get_class_methods($class);

        foreach ($methods as $method) {
            // Skip magic methods and non-public methods
            if (str_starts_with($method, '__') || str_starts_with($method, 'get')) {
                continue;
            }

            try {
                $reflection = new \ReflectionMethod($class, $method);

                // Only check public methods
                if (!$reflection->isPublic()) {
                    continue;
                }

                // Check if method returns a Relation
                $returnType = $reflection->getReturnType();
                if ($returnType && !$returnType->isBuiltin()) {
                    $returnTypeName = $returnType->getName();
                    
                    // Check if it's a relation class
                    if (str_contains($returnTypeName, 'Illuminate\\Database\\Eloquent\\Relations')) {
                        $relationships[] = $method;
                    }
                }
            } catch (\Throwable $e) {
                // Skip if reflection fails
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Invalidate cache for specific model instance.
     * 
     * @param Model $model Model instance
     * @return void
     */
    public function invalidateInstance(Model $model): void
    {
        $this->invalidateModelCache(get_class($model), $model);
    }

    /**
     * Invalidate cache for multiple models.
     * 
     * @param array $models Array of model class names or instances
     * @return void
     */
    public function invalidateMultiple(array $models): void
    {
        foreach ($models as $model) {
            if (is_string($model)) {
                $this->invalidateModelCache($model);
            } elseif ($model instanceof Model) {
                $this->invalidateInstance($model);
            }
        }
    }

    /**
     * Clear all table caches.
     * 
     * @return void
     */
    public function clearAll(): void
    {
        $this->cacheManager->clearAllTableCaches();
    }

    /**
     * Register invalidation for multiple models.
     * 
     * @param array $models Array of model class names
     * @return void
     */
    public function registerMultiple(array $models): void
    {
        foreach ($models as $model) {
            $this->register($model);
        }
    }

    /**
     * Unregister event listeners for a model.
     * 
     * @param string|Model $model Model class name or instance
     * @return void
     */
    public function unregister(string|Model $model): void
    {
        $modelClass = is_string($model) ? $model : get_class($model);

        foreach (self::MODEL_EVENTS as $event) {
            $eventName = "eloquent.{$event}: {$modelClass}";
            Event::forget($eventName);
        }
    }

    /**
     * Check if model has registered listeners.
     * 
     * @param string|Model $model Model class name or instance
     * @return bool True if model has listeners
     */
    public function isRegistered(string|Model $model): bool
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        $eventName = "eloquent.created: {$modelClass}";

        return Event::hasListeners($eventName);
    }

    /**
     * Get cache manager instance.
     * 
     * @return TableCacheManager Cache manager
     */
    public function getCacheManager(): TableCacheManager
    {
        return $this->cacheManager;
    }

    /**
     * Set cache manager instance.
     * 
     * @param TableCacheManager $cacheManager Cache manager
     * @return void
     */
    public function setCacheManager(TableCacheManager $cacheManager): void
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get list of model events being listened to.
     * 
     * @return array Model events
     */
    public function getModelEvents(): array
    {
        return self::MODEL_EVENTS;
    }

    /**
     * Manually trigger cache invalidation for a model event.
     * 
     * @param string|Model $model Model class name or instance
     * @param string $event Event name (created, updated, deleted, etc.)
     * @return void
     */
    public function triggerInvalidation(string|Model $model, string $event): void
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        $modelInstance = is_string($model) ? null : $model;

        if (in_array($event, self::MODEL_EVENTS)) {
            $this->invalidateModelCache($modelClass, $modelInstance);
        }
    }
}
