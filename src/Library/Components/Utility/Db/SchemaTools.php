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
        
        try {
            $conn = DB::connection($connection);
            return $conn->getSchemaBuilder()->getColumnType($table, $column);
        } catch (\Doctrine\DBAL\Exception $e) {
            // Handle ENUM and other unsupported types by falling back to raw SQL
            if (strpos($e->getMessage(), 'enum') !== false || strpos($e->getMessage(), 'Unknown database type') !== false) {
                return self::getColumnTypeRaw($table, $column, $connection);
            }
            throw $e;
        }
    }
    
    /**
     * Get column type using raw SQL query (fallback for ENUM and other unsupported types)
     */
    private static function getColumnTypeRaw(string $table, string $column, ?string $connection = null): string
    {
        if ($connection === null) {
            $connection = config('database.default', 'mysql');
        }
        
        $conn = DB::connection($connection);
        $result = $conn->select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
        
        if (empty($result)) {
            return 'unknown';
        }
        
        $type = $result[0]->Type ?? 'unknown';
        
        // Normalize common types
        if (strpos($type, 'enum') === 0) {
            return 'enum';
        } elseif (strpos($type, 'varchar') === 0) {
            return 'string';
        } elseif (strpos($type, 'int') !== false) {
            return 'integer';
        } elseif (strpos($type, 'text') !== false) {
            return 'text';
        } elseif (strpos($type, 'timestamp') !== false || strpos($type, 'datetime') !== false) {
            return 'datetime';
        } elseif (strpos($type, 'date') !== false) {
            return 'date';
        }
        
        return $type;
    }
}