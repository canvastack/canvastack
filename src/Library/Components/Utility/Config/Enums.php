<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Config;

final class Enums
{
    /** Active box values depending on language */
    public static function activeBox(bool $en = true): array
    {
        if ($en) {
            return [null => ''] + ['No', 'Yes'];
        }
        return [null => ''] + ['Tidak Aktif', 'Aktif'];
    }

    /** Request status list; can return label by index when $num !== false */
    public static function requestStatus(bool $en = true, $num = false)
    {
        $data = $en ? ['Pending', 'Accept', 'Blocked', 'Ban'] : ['Pending', 'Terima', 'Block', 'Ban'];
        if ($num === false) {
            return $data;
        }
        return $data[$num];
    }

    /** Map 0/1 to No/Yes */
    public static function activeValue($value): string
    {
        return (1 === (int) $value) ? 'Yes' : 'No';
    }
}