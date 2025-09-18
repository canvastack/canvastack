<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class ParamsBuilder
{
    /**
     * Build params array for given table.
     * Mutates $params via reference.
     */
    public static function build(string $table_name, array &$params, array $variables, $actions, bool $numbering, array $attributes, bool $server_side, bool $server_side_custom_url, array $button_removed = []): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ParamsBuilder: Building params for table', [
                'table_name' => $table_name,
                'has_actions' => !empty($actions),
                'numbering' => $numbering,
                'server_side' => $server_side,
                'buttons_removed_count' => count($button_removed)
            ]);
        }

        $params[$table_name]['actions'] = $actions;
        $params[$table_name]['buttons_removed'] = $button_removed;

        $params[$table_name]['numbering'] = $numbering;
        $params[$table_name]['attributes'] = $attributes;
        $params[$table_name]['server_side']['status'] = $server_side;
        $params[$table_name]['server_side']['custom_url'] = $server_side_custom_url;

        if (! empty($variables['column_width'])) {
            $params[$table_name]['attributes']['column_width'] = $variables['column_width'];
        }
        if (! empty($variables['url_value'])) {
            $params[$table_name]['url_value'] = $variables['url_value'];
        }
        if (! empty($variables['add_table_attributes'])) {
            $params[$table_name]['attributes']['add_attributes'] = $variables['add_table_attributes'];
        }

        return $params[$table_name];
    }
}