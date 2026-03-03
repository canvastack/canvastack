<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Repository Interface.
 *
 * Defines the contract for data access repositories.
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     *
     * @param array<string> $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Find a record by ID.
     *
     * @param int $id
     * @param array<string> $columns
     * @return Model|null
     */
    public function find(int $id, array $columns = ['*']): ?Model;

    /**
     * Find a record by ID or fail.
     *
     * @param int $id
     * @param array<string> $columns
     * @return Model
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;

    /**
     * Create a new record.
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update a record.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find records by criteria.
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $columns
     * @return Collection
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;

    /**
     * Find a single record by criteria.
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $columns
     * @return Model|null
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;
}
