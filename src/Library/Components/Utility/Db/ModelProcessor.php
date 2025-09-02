<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Db;

final class ModelProcessor
{
    /**
     * Process a model function based on config array $data[$name].
     * Expected keys: model, function, connection, strict
     */
    public static function process(array $data, string $name): void
    {
        if (empty($data[$name])) {
            return;
        }
        $cfg = $data[$name];
        $model = $cfg['model'] ?? null;
        $method = $cfg['function'] ?? null;
        $conn = $cfg['connection'] ?? null;
        $strict = $cfg['strict'] ?? null;

        if (!$model || !$method || !method_exists($model, $method)) {
            return;
        }

        // Match legacy: toggle strict if requested
        if ($strict === false && is_string($conn) && $conn !== '') {
            if (function_exists('canvastack_db')) {
                canvastack_db('purge', $conn);
            }
            config()->set("database.connections.{$conn}.strict", false);
            if (function_exists('canvastack_db')) {
                canvastack_db('reconnect');
            }
        }

        $model->{$method}();
    }
}