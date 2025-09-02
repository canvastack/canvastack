<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Json;

final class JsonTools
{
    /**
     * Legacy clear json helper: remove quotes around keys 'data' and 'name', then replace all quotes with single quotes
     */
    public static function clear(string $data): string
    {
        $json = str_replace('"data"', 'data', $data);
        $json = str_replace('"name"', 'name', $json);
        $json = str_replace('"', "'", $json);
        return $json;
    }
}