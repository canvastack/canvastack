<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * ModelQueryTrait
 *
 * Handles model/sql source selection and table-level configs (server-side, orderby, naming, connection).
 */
trait ModelQueryTrait
{
    public function setDatatableType($set = true)
    {
        $this->setDatatable = $set;
        if (true !== $this->setDatatable) {
            $this->tableType = 'self::table';
        }
        $this->element_name['table'] = $this->tableType;
    }

    public function setName($table_name)
    {
        $this->variables['table_name'] = $table_name;
    }

    public function setFields($fields)
    {
        $this->variables['table_fields'] = $fields;
    }

    public function model($model)
    {
        $this->variables['table_data_model'] = $model;
    }

    public function runModel($model_object, $function_name, $strict)
    {
        $connection = 'mysql';
        if (null !== $this->connection) {
            $connection = $this->connection;
        }

        $modelFunction = $function_name;
        $tableFunction = $function_name;
        if (canvastack_string_contained($function_name, '::')) {
            $split = explode('::', $function_name);
            $modelFunction = $split[0];
            $tableFunction = "$split[1]_$split[0]";
        }

        $this->variables['model_processing'] = [];
        $this->variables['model_processing']['model'] = $model_object;
        $this->variables['model_processing']['function'] = $modelFunction;
        $this->variables['model_processing']['connection'] = $connection;
        $this->variables['model_processing']['table'] = $tableFunction;
        $this->variables['model_processing']['strict'] = $strict;
    }

    public function query($sql)
    {
        $this->variables['query'] = $sql;
        $this->model('sql');
    }

    public function setServerSide($server_side = true)
    {
        $this->variables['table_server_side'] = $server_side;
    }

    public function orderby($column, $order = 'asc')
    {
        $this->variables['orderby_column'] = [];
        $this->variables['orderby_column'] = ['column' => $column, 'order' => $order];
    }

    public function connection($db_connection)
    {
        $this->connection = $db_connection;
    }

    public function resetConnection()
    {
        $this->connection = null;
    }
}
