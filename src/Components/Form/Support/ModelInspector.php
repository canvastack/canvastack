<?php

namespace Canvastack\Canvastack\Components\Form\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

/**
 * ModelInspector - Inspects Eloquent models for traits and configuration.
 *
 * Provides methods to detect model capabilities like soft deletes,
 * with caching for performance optimization.
 */
class ModelInspector
{
    /**
     * Cache duration in seconds (5 minutes).
     */
    protected int $cacheDuration = 300;

    /**
     * Check if a model uses the SoftDeletes trait.
     *
     * @param object $model The model instance to inspect
     * @return bool True if model uses SoftDeletes trait
     */
    public function usesSoftDeletes(object $model): bool
    {
        if (!$model instanceof Model) {
            return false;
        }

        $modelClass = get_class($model);
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($model) {
            return $this->hasTrait($model, SoftDeletes::class);
        });
    }

    /**
     * Check if a model uses a specific trait.
     *
     * @param object $model The model instance to inspect
     * @param string $traitName The fully qualified trait name
     * @return bool True if model uses the trait
     */
    public function hasTrait(object $model, string $traitName): bool
    {
        $reflection = new ReflectionClass($model);
        $traits = $this->getAllTraits($reflection);

        return in_array($traitName, $traits);
    }

    /**
     * Get the soft delete column name for a model.
     *
     * @param object $model The model instance to inspect
     * @return string The soft delete column name (default: 'deleted_at')
     */
    public function getSoftDeleteColumn(object $model): string
    {
        if (!$model instanceof Model) {
            return 'deleted_at';
        }

        // Check if model has getDeletedAtColumn method
        if (method_exists($model, 'getDeletedAtColumn')) {
            return $model->getDeletedAtColumn();
        }

        // Check for DELETED_AT constant
        $reflection = new ReflectionClass($model);
        if ($reflection->hasConstant('DELETED_AT')) {
            return $reflection->getConstant('DELETED_AT');
        }

        // Default Laravel soft delete column
        return 'deleted_at';
    }

    /**
     * Get all traits used by a class, including parent classes.
     *
     * @param ReflectionClass $reflection The reflection class
     * @return array Array of trait names
     */
    protected function getAllTraits(ReflectionClass $reflection): array
    {
        $traits = [];

        // Get traits from current class
        foreach ($reflection->getTraits() as $trait) {
            $traits[] = $trait->getName();
            // Recursively get traits used by this trait
            $traits = array_merge($traits, $this->getAllTraits($trait));
        }

        // Get traits from parent class
        $parent = $reflection->getParentClass();
        if ($parent) {
            $traits = array_merge($traits, $this->getAllTraits($parent));
        }

        return array_unique($traits);
    }

    /**
     * Clear cached inspection results for a model.
     *
     * @param string $modelClass The model class name
     * @return void
     */
    public function clearCache(string $modelClass): void
    {
        $cacheKey = "model_inspector:soft_deletes:{$modelClass}";
        Cache::forget($cacheKey);
    }

    /**
     * Set cache duration in seconds.
     *
     * @param int $seconds Cache duration
     * @return self
     */
    public function setCacheDuration(int $seconds): self
    {
        $this->cacheDuration = $seconds;

        return $this;
    }
}
