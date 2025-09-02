<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

final class IconAttributes
{
    /**
     * Parse string "name|icon|position" into structured array with attributes.
     * Returns object-like array: ['name' => string, 'attr' => ['input_icon' => icon, 'icon_position' => position]]
     */
    public static function parse(string $string, array $attributes = [], string $pos = 'left'): array
    {
        $data = [
            'name' => $string,
            'attr' => [],
        ];

        $icon = null;
        $position = $pos;

        if (str_contains($string, '|')) {
            $_string = explode('|', $string);
            $data['name'] = $_string[0] ?? '';
            $icon = $_string[1] ?? null;
            if (count($_string) >= 3) {
                $position = $_string[2];
            }

            $_attr = array_merge_recursive($attributes, ['input_icon' => $icon]);
            $data['attr'] = array_merge_recursive($_attr, ['icon_position' => $position]);
        }

        return $data;
    }
}