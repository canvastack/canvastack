<?php

namespace Canvastack\Canvastack\Models\Admin\System;

use Canvastack\Canvastack\Models\Core\Model;

/**
 * Created on 2 Jun 2021
 * Time Created : 13:24:01
 *
 * @filesource DynamicTables.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class DynamicTables extends Model
{
    protected $connection = 'mysql';

    public function __construct($sql = null, $connection = 'mysql')
    {
        if (! empty($connection)) {
            $this->connection = $connection;
        }
        if (! empty($sql)) {
            $data = canvastack_query($sql);

            foreach ($data as $key => $value) {
                $this->$key = $value;
            }

            $this->table = canvastack_get_table_name_from_sql($sql);
        }
    }

    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    public function guarded($guarded = [])
    {
        $this->guarded = $guarded;

        return $this;
    }

    private $get_query;

    public function setQuery($sql, $type = 'select')
    {
        $query = canvastack_query($sql, $type);
        $this->get_query = collect($query);

        return $this;
    }

    public function getQueryData()
    {
        return $this->get_query;
    }
}
