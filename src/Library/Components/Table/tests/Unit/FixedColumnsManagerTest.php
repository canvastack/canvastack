<?php

namespace Canvastack\Canvastack\Library\Components\Table\tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\FixedColumnsManager;
use PHPUnit\Framework\TestCase;

class FixedColumnsManagerTest extends TestCase
{
    public function test_set_fixed_columns_sets_left_and_right_when_non_empty(): void
    {
        $vars = [];
        FixedColumnsManager::setFixedColumns($vars, 1, 2);
        $this->assertArrayHasKey('fixed_columns', $vars);
        $this->assertSame(1, $vars['fixed_columns']['left']);
        $this->assertSame(2, $vars['fixed_columns']['right']);
    }

    public function test_set_fixed_columns_only_left(): void
    {
        $vars = [];
        FixedColumnsManager::setFixedColumns($vars, 3, null);
        $this->assertArrayHasKey('fixed_columns', $vars);
        $this->assertSame(3, $vars['fixed_columns']['left']);
        $this->assertArrayNotHasKey('right', $vars['fixed_columns']);
    }

    public function test_set_fixed_columns_only_right(): void
    {
        $vars = [];
        FixedColumnsManager::setFixedColumns($vars, null, 4);
        $this->assertArrayHasKey('fixed_columns', $vars);
        $this->assertSame(4, $vars['fixed_columns']['right']);
        $this->assertArrayNotHasKey('left', $vars['fixed_columns']);
    }

    public function test_clear_fixed_columns_unsets(): void
    {
        $vars = [];
        FixedColumnsManager::setFixedColumns($vars, 1, 2);
        $this->assertArrayHasKey('fixed_columns', $vars);

        FixedColumnsManager::clearFixedColumns($vars);
        $this->assertArrayNotHasKey('fixed_columns', $vars);
    }
}