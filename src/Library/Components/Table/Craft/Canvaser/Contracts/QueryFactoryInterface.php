<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

interface QueryFactoryInterface
{
    /**
     * Build complete query with all processing steps
     *
     * @param  mixed  $model_data Base model/query builder
     * @param  object  $data Datatables configuration data
     * @param  string  $table_name Primary table name
     * @param  mixed  $filters Request filters
     * @param  array|null  $order_by Order configuration
     * @return array Query result with model, limit, joinFields
     */
    public function buildQuery($model_data, $data, string $table_name, $filters = null, ?array $order_by = null): array;

    /**
     * Apply joins based on foreign keys configuration
     *
     * @param  mixed  $model_data Base model/query builder
     * @param  array  $foreign_keys Foreign key configuration
     * @param  string  $table_name Primary table name
     * @return array Result with model and joinFields
     */
    public function applyJoins($model_data, array $foreign_keys, string $table_name): array;

    /**
     * Apply where conditions from datatables configuration
     *
     * @param  mixed  $model_data Base model/query builder
     * @param  array  $conditions Where conditions configuration
     * @return mixed Modified query builder
     */
    public function applyWhereConditions($model_data, array $conditions);

    /**
     * Apply request filters to query
     *
     * @param  mixed  $model Base query builder
     * @param  mixed  $filters Request filters
     * @param  string  $table_name Primary table name
     * @param  string  $firstField First field for default where
     * @return array Result with model and limitTotal
     */
    public function applyFilters($model, $filters, string $table_name, string $firstField): array;

    /**
     * Apply pagination to query
     *
     * @param  mixed  $model Query builder
     * @param  int|null  $start Start offset
     * @param  int|null  $length Page length
     * @return mixed Modified query builder
     */
    public function applyPagination($model, ?int $start = null, ?int $length = null);

    /**
     * Calculate total records
     *
     * @param  mixed  $model Query builder for filtered count
     * @param  mixed  $model_filters Query builder for total count
     * @return int Total record count
     */
    public function calculateTotals($model, $model_filters): int;
}
