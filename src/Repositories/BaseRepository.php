<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Repositories;

use Canvastack\Canvastack\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Repository.
 *
 * Provides common data access methods for Eloquent models.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Create a new repository instance.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Relations to eager load.
     *
     * @var array<string>
     */
    protected array $with = [];

    /**
     * Get all records.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        return $query->get($columns);
    }

    /**
     * Set relations to eager load.
     *
     * @param array<string> $relations
     * @return self
     */
    public function with(array $relations): self
    {
        $this->with = $relations;

        return $this;
    }

    /**
     * Find a record by ID.
     *
     * @param int $id
     * @param array<string> $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        return $query->find($id, $columns);
    }

    /**
     * Find a record by ID or fail.
     *
     * @param int $id
     * @param array<string> $columns
     * @return Model
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        return $query->findOrFail($id, $columns);
    }

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->findOrFail($id);

        return $record->update($data);
    }

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $record = $this->findOrFail($id);

        return $record->delete();
    }

    /**
     * Find records by criteria.
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $columns
     * @return Collection
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->get($columns);
    }

    /**
     * Find a single record by criteria.
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $columns
     * @return Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        foreach ($criteria as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first($columns);
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
