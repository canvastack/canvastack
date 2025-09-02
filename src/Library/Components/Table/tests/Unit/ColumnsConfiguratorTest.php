<?php

namespace Canvastack\Canvastack\Library\Components\Table\tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\ColumnsConfigurator;
use PHPUnit\Framework\TestCase;

class ColumnsConfiguratorTest extends TestCase
{
    public function test_merge_columns_sets_structure(): void
    {
        $vars = [];
        ColumnsConfigurator::mergeColumns($vars, 'Nama', ['first_name', 'last_name'], 'top');

        $this->assertArrayHasKey('merged_columns', $vars);
        $this->assertArrayHasKey('Nama', $vars['merged_columns']);
        $this->assertSame('top', $vars['merged_columns']['Nama']['position']);
        $this->assertSame(2, $vars['merged_columns']['Nama']['counts']);
        $this->assertSame(['first_name', 'last_name'], $vars['merged_columns']['Nama']['columns']);
    }

    public function test_set_hidden_columns_assigns_fields(): void
    {
        $vars = [];
        ColumnsConfigurator::setHiddenColumns($vars, ['secret', 'internal']);
        $this->assertSame(['secret', 'internal'], $vars['hidden_columns']);
    }

    public function test_force_raw_columns_normalizes_to_unique_strings(): void
    {
        $vars = [];
        ColumnsConfigurator::forceRawColumns($vars, ['html', 'html', 123]);
        sort($vars['raw_columns_forced']);
        $this->assertSame(['123', 'html'], $vars['raw_columns_forced']);

        $vars2 = [];
        ColumnsConfigurator::forceRawColumns($vars2, 'html');
        $this->assertSame(['html'], $vars2['raw_columns_forced']);
    }

    public function test_set_field_as_image_normalizes_to_unique_strings(): void
    {
        $vars = [];
        ColumnsConfigurator::setFieldAsImage($vars, ['avatar', 'avatar', 10]);
        sort($vars['image_fields']);
        $this->assertSame(['10', 'avatar'], $vars['image_fields']);

        $vars2 = [];
        ColumnsConfigurator::setFieldAsImage($vars2, 'avatar');
        $this->assertSame(['avatar'], $vars2['image_fields']);
    }
}
