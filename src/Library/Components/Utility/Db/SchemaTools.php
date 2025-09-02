<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Db;

use Illuminate\Support\Facades\DB;

final class SchemaTools
{
    public static function getAllTables(?string $connection = null)
    {
        return collect(DB::connection($connection)->select('show tables'))->map(function ($val) {
            foreach ($val as $tbl) {
                return $tbl;
            }
        });
    }

    public static function hasColumn(string $table, string $column, ?string $connection = null): bool
    {
        if ($connection === null) {
            $connection = config('database.default', 'mysql');
        }
        $conn = DB::connection($connection);
        return $conn->getSchemaBuilder()->hasColumn($table, $column);
    }

    public static function getColumns(string $table, ?string $connection = null): array
    {
        if ($connection === null) {
            $connection = config('database.default', 'mysql');
        }
        $conn = DB::connection($connection);
        return $conn->getSchemaBuilder()->getColumnListing($table);
    }

    public static function getColumnType(string $table, string $column, ?string $connection = null): string
    {
        if ($connection === null) {
            $connection = config('database.default', 'mysql');
        }
        $conn = DB::connection($connection);
        return $conn->getSchemaBuilder()->getColumnType($table, $column);
    }
}