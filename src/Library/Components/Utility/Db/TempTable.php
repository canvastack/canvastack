<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Db;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TempTable
{
    /**
     * Create a temp_ table using a SELECT SQL.
     * Matches legacy behavior including temporary strict toggle.
     */
    public static function create(string $tableName, string $sql, bool $strict = true, string $conn = 'mysql'): void
    {
        $tableName = str_replace('temp_', '', $tableName);

        // Drop if exists
        if (Schema::hasTable("temp_{$tableName}")) {
            Schema::dropIfExists("temp_{$tableName}");
        }

        $strictConfig = config("database.connections.{$conn}.strict");

        // Toggle strict off if requested
        if ($strict === false) {
            DB::purge($conn);
            config()->set("database.connections.{$conn}.strict", false);
            DB::reconnect();
        }

        // Optionally validate the SQL (safe no-op if helper missing)
        if (function_exists('canvastack_query')) {
            try {
                canvastack_query($sql, 'SELECT');
            } catch (\Throwable $e) {
                // ignore; creation may still succeed depending on SQL
            }
        }

        // Create table as SELECT
        DB::unprepared("CREATE TABLE temp_{$tableName} {$sql}");

        // Restore strict
        if ($strict === false) {
            DB::purge($conn);
            config()->set("database.connections.{$conn}.strict", $strictConfig);
            DB::reconnect();
        }
    }
}