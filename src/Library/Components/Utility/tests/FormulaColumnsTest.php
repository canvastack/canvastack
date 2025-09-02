<?php

namespace Tests\Utility;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Table\FormulaColumns;

class FormulaColumnsTest extends TestCase
{
    public function test_inserts_before_after_first_last_with_validation()
    {
        $columns = ['id', 'name', 'action'];
        $data = [
            [
                'name' => 'total',
                'label' => 'Total',
                'field_lists' => ['amount'],
                'node_location' => 'last',
                'node_after' => true,
            ],
            [
                'name' => 'rank',
                'label' => 'Rank',
                'field_lists' => ['id'],
                'node_location' => 'first',
                'node_after' => false,
            ],
            [
                'name' => 'after_name',
                'label' => 'After Name',
                'field_lists' => ['name'],
                'node_location' => 'name',
                'node_after' => true,
            ],
            [
                'name' => 'invalid_target',
                'label' => 'Invalid',
                'field_lists' => ['x'],
                'node_location' => 'not_exists',
                'node_after' => true,
            ],
        ];

        $result = FormulaColumns::set($columns, $data);
        $this->assertEquals(['rank', 'id', 'name', 'after_name', 'action', 'total'], $result);
    }
}