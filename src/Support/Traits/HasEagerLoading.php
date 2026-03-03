<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Traits;

/**
 * HasEagerLoading Trait.
 *
 * Provides eager loading functionality for repositories and query builders.
 */
trait HasEagerLoading
{
    /**
     * Relations to eager load.
     *
     * @var array<string>
     */
    protected array $eagerLoad = [];

    /**
     * Set relations to eager load.
     *
     * @param array<string> $relations
     * @return self
     */
    public function with(array $relations): self
    {
        $this->eagerLoad = $relations;

        return $this;
    }

    /**
     * Add a relation to eager load.
     *
     * @param string $relation
     * @return self
     */
    public function addWith(string $relation): self
    {
        if (!in_array($relation, $this->eagerLoad)) {
            $this->eagerLoad[] = $relation;
        }

        return $this;
    }

    /**
     * Get eager load relations.
     *
     * @return array<string>
     */
    public function getEagerLoad(): array
    {
        return $this->eagerLoad;
    }

    /**
     * Clear eager load relations.
     *
     * @return self
     */
    public function clearEagerLoad(): self
    {
        $this->eagerLoad = [];

        return $this;
    }

    /**
     * Check if a relation is set for eager loading.
     *
     * @param string $relation
     * @return bool
     */
    public function hasEagerLoad(string $relation): bool
    {
        return in_array($relation, $this->eagerLoad);
    }
}
