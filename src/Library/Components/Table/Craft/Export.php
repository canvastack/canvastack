<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Illuminate\Support\Facades\Response;

/**
 * Created on Dec 7, 2022
 *
 * Time Created : 11:46:28 PM
 * Filename     : Export.php
 *
 * @filesource Export.php
 *
 * @author     wisnuwidi @Incodiy - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Export
{
    public $delimeter = '|';

    private $export_path = 'assets/resources/exports';

    public function csv($path = null, $link = null)
    {
        return $this->process('csv', $path, $link);
    }

    private function normalizeFilters($filters = [])
    {
        $filterData = [];

        foreach ($filters as $filter_data) {
            if (is_array($filter_data['value'])) {
                foreach ($filter_data['value'] as $filterValues) {
                    $filterData[$filter_data['field_name']]['value'][][] = $filterValues;
                }
            } else {
                $filterData[$filter_data['field_name']]['value'][][] = $filter_data['value'];
            }
        }

        $_filters = [];
        foreach ($filterData as $node => $nodeValues) {
            $_filters[$node]['field_name'] = $node;
            $_filters[$node]['operator'] = '=';
            foreach ($nodeValues['value'] as $values) {
                $_filters[$node]['value'][] = $values[0];
            }
        }
        unset($filterData);

        foreach ($_filters as $dataFilters) {
            $filterData[] = $dataFilters;
        }

        return $filterData;
    }

    private function process($type = 'csv', $path = null, $link = null)
    {
        if (empty($path)) {
            $path = $this->export_path;
        } else {
            $this->export_path = $path;
        }

        $data = [];
        if (! empty($_GET['exportDataTables'])) {
            if (true == $_GET['exportDataTables']) {
                if (! empty($_POST['lurExp'])) {
                    $link = canvastack_decrypt($_POST['lurExp']);
                }
                unset($_POST['lurExp']);
                unset($_POST['exportData']);

                $filterPage = [];
                if (! empty($_POST['ftrExp'])) {
                    $fDataPost = canvastack_filter_data_normalizer($_POST['ftrExp']);
                    unset($_POST['ftrExp']);

                    if (! empty($fDataPost)) {
                        foreach ($fDataPost as $filterPostData) {
                            $filterPage[$filterPostData['field_name']] = $filterPostData['value'];
                        }
                    }
                }

                $table_source = $_GET['difta']['name'];
                $model_source = $_GET['difta']['source'];
                $token = $_POST['_token'];
                unset($_POST['_token']);

                $filters = $_POST;
                if (! empty($filterPage)) {
                    $postsInitPage = [];
                    foreach ($filterPage as $fpageName => $fpageValues) {
                        $postsInitPage[$fpageName] = $fpageValues;
                        if (! empty($_POST[$fpageName])) {
                            $postsInitPage[$fpageName] = $_POST[$fpageName];
                            unset($_POST[$fpageName]);
                        }
                    }

                    $filters = array_merge_recursive($postsInitPage, $_POST);
                }

                if ('dynamics' === $model_source) {
                    $model = new DynamicTables(null, $link);
                    $model->setTable($table_source);
                    if (! empty(array_filter($filters))) {
                        $filterData = [];
                        foreach ($filters as $field_name => $field_value) {
                            if (! empty($field_value)) {
                                if (is_array($field_value)) {
                                    foreach ($field_value as $n => $fvalue) {
                                        if (! empty($fvalue)) {
                                            $filterData[$field_name][$n] = $fvalue;
                                        }
                                    }
                                } else {
                                    $filterData[$field_name] = $field_value;
                                }
                            }
                        }

                        $filters = [];
                        foreach ($filterData as $fieldData => $fieldValues) {
                            if (! is_array($fieldValues)) {
                                $filters[$fieldData] = $fieldValues;
                            }
                        }
                        $model = $model->where($filters);

                        foreach ($filterData as $fieldData => $fieldValues) {
                            if (is_array($fieldValues)) {
                                $model->whereIn($fieldData, $fieldValues);
                            }
                        }
                    }
                    $data[$table_source]['model'] = get_class($model);

                    foreach ($model->get() as $i => $mod) {
                        foreach ($mod->getAttributes() as $fieldname => $fieldvalue) {
                            $data[$table_source]['export']['head'][$fieldname] = $fieldname;
                            $data[$table_source]['export']['values'][$i][$fieldname] = $fieldvalue;
                        }
                    }

                    if (! empty($data)) {
                        $user = auth()->user()->username;
                        $time = date('Ymd');
                        $path = "{$path}/{$user}/{$token}/{$time}/{$table_source}";

                        if ('csv' === $type) {
                            return $this->exportCSV($data[$table_source]['export'], $path, "{$user}-$table_source");
                        }
                    }
                }
            }
        }
    }

    private function generate($type = 'csv', $data = [], $path = null, $filename = 'diyExportData')
    {
        $pathFile = public_path();
        if (! file_exists($pathFile."/{$path}")) {
            canvastack_make_dir($pathFile."/{$path}", 0777, true, true);
        }

        $filepath = str_replace('\/', '/', $pathFile."/{$path}/{$filename}.{$type}");
        $headers = [
            'Content-Type' => 'text/'.$type,
            'Content-Disposition' => 'attachment; filename='.$filename.'.'.$type,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = $data['head'];
        $values = $data['values'];
        $rows = [];
        foreach ($values as $i => $valueData) {
            foreach ($valueData as $fieldname => $value) {
                $rows[$i][$fieldname] = $value;
            }
        }

        if ('csv' === $type) {
            self::createFileCSV($filepath, $columns, $rows);
        }

        if (false === canvastack_string_contained(canvastack_config('baseURL'), 'public')) {
            $uri = url(canvastack_config('baseURL').'/'.$path.'/'.$filename.'.'.$type);
        } else {
            $uri = url()->asset(str_replace('\\', '/', explode('public', $filepath)[1]));
        }

        Response::streamDownload($uri, "{$filename}.{$type}", $headers);

        return json_encode(['diyExportStreamPath' => $uri]);
    }

    private static function createFileCSV($filepath, $columns, $rows)
    {
        $_columns = [];
        foreach ($columns as $column) {
            $columnLabel = ucwords(str_replace('_', ' ', $column));
            $_columns[$columnLabel] = $columnLabel;
        }
        $columns = $_columns;
        $handle = fopen($filepath, 'w');
        fputcsv($handle, array_values($columns), '|');
        foreach ($rows as $row) {
            fputcsv($handle, str_replace(';', ' ', $row), '|');
        }
        fclose($handle);
    }

    private function exportCSV($data, $path = null, $filename = 'diyExportDataCSV')
    {
        return $this->generate('csv', $data, $path, $filename);
    }
}
