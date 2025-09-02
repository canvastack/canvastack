<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

final class MappingUi
{
    /**
     * Build structure from legacy inputs (buffers/fieldbuff-style) into headers/body arrays.
     * $config expects keys: name, field_id, value_id, data, buffers, fieldbuff
     */
    public static function build(array $config): array
    {
        $name = (string)($config['name'] ?? 'mapping');
        $field_id = (string)($config['field_id'] ?? 'field');
        $value_id = (string)($config['value_id'] ?? 'value');
        $data = (array)($config['data'] ?? []);
        $buffers = $config['buffers'] ?? [];
        $fieldbuff = $config['fieldbuff'] ?? [];

        $rows = [];
        $headers = ['Field', 'Value'];

        if (!empty($buffers)) {
            $id = explode('__node__', $field_id)[0];
            $n = 0;
            foreach (($buffers[$id] ?? []) as $field_info => $value) {
                $n++;
                $rowFieldId = $field_id;
                $rowValueId = $value_id;
                if ($n > 1) {
                    $rowFieldId = $fieldbuff['ranid'][$field_info] ?? $field_id;
                    $rowValueId = $fieldbuff['ranval'][$field_info] ?? $value_id;
                }
                $rows[] = [
                    'field' => $data['field_name'][$value->target_table][$value->target_field_name] ?? '',
                    'value' => $data['field_value'][$value->target_table][$field_info] ?? '',
                    'remove_id' => $rowFieldId,
                    'remove_value_id' => $rowValueId,
                    'icon' => $n > 1 ? 'fa fa-minus-circle danger' : 'fa fa-recycle warning',
                    'removable' => $n > 1,
                ];
            }
        } else {
            $rows[] = [
                'field' => (string)($data['field_name'] ?? ''),
                'value' => (string)($data['field_value'] ?? ''),
                'remove_id' => $field_id,
                'remove_value_id' => $value_id,
                'icon' => 'fa fa-recycle warning',
                'removable' => false,
            ];
        }

        return [
            'name' => $name,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Render HTML table matching legacy canvastack_draw_query_map_page_table output closely.
     */
    public static function render(array $config): string
    {
        $name = (string)($config['name'] ?? 'mapping');
        $field_id = (string)($config['field_id'] ?? 'field');
        $value_id = (string)($config['value_id'] ?? 'value');
        $data = (array)($config['data'] ?? []);
        $buffers = $config['buffers'] ?? [];
        $fieldbuff = $config['fieldbuff'] ?? [];

        $fieldID = $field_id;
        $trClass = '';
        $o = "<table class=\"table mapping-table display responsive relative-box {$name}\"><tbody>";

        if (!empty($buffers)) {
            $n = 0;
            $id = explode('__node__', $field_id)[0];
            foreach (($buffers[$id] ?? []) as $field_info => $value) {
                $n++;
                $ico = 'fa fa-recycle warning';
                $script = '';
                $rowFieldId = $field_id;
                $rowValueId = $value_id;
                $trClass = '';
                if ($n > 1) {
                    $rowFieldId = $fieldbuff['ranid'][$field_info] ?? $field_id;
                    $rowValueId = $fieldbuff['ranval'][$field_info] ?? $value_id;
                    $trClass = " role-add-{$fieldID}";
                    $ico = 'fa fa-minus-circle danger';
                    $ajax = $data['ajax_field_name'] ?? '';
                    $script = "<script type='text/javascript'>$(document).ready(function() { rowButtonRemovalMapRoles('{$rowFieldId}', '{$rowValueId}'); mappingPageFieldnameValues('{$rowFieldId}', '{$rowValueId}', '{$ajax}'); });</script>";
                }

                $o .= "<tr id=\"row-box-{$rowFieldId}\" class=\"relative-box row-box-{$fieldID}{$trClass}\">";
                $o .= "<td class=\"qmap-box-{$fieldID} field-name-box\">";
                $o .= $data['field_name'][$value->target_table][$value->target_field_name] ?? '';
                $o .= '</td>';
                $o .= "<td class=\"qmap-box-{$fieldID} relative-box field-value-box\">";
                $o .= $data['field_value'][$value->target_table][$field_info] ?? '';
                $spanAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                    'id' => "remove-row{$rowFieldId}",
                    'class' => "remove-row{$fieldID} multi-chain-buttons",
                    'style' => ''
                ]);
                $o .= '<span '.$spanAttr.'>';
                $o .= "<i class='{$ico}' aria-hidden='true'></i>";
                $o .= '</span>';
                $o .= $script;
                $o .= '</td>';
                $o .= '</tr>';
            }
        } else {
            $o .= "<tr id=\"row-box-{$field_id}\" class=\"relative-box row-box-{$field_id}\">";
            $o .= "<td class=\"qmap-box-{$field_id} field-name-box\">";
            $o .= (string)($data['field_name'] ?? '');
            $o .= '</td>';
            $o .= "<td class=\"qmap-box-{$field_id} relative-box field-value-box\">";
            $o .= (string)($data['field_value'] ?? '');
            $spanAttr = \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString([
                'id' => "remove-row{$field_id}",
                'class' => "remove-row{$field_id} multi-chain-buttons",
                'style' => 'display:none;'
            ]);
            $o .= '<span '.$spanAttr.'>';
            $o .= "<i class='fa fa-recycle warning' aria-hidden='true'></i>";
            $o .= '</span>';
            $o .= '</td>';
            $o .= '</tr>';
        }

        $o .= '</tbody></table>';
        return $o;
    }
}