<?php

namespace Canvastack\Canvastack\Library\Components\Utility\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

class TableUiModalContentHtmlTest extends TestCase
{
    public function testModalContentHtmlMatchesExpectedMarkup()
    {
        $name = 'users_cdyFILTERmodalBOX';
        $title = 'Users';
        $elements = [
            '<input type="text" name="q" class="form-control" />',
            '<select name="role" class="form-control"><option>Admin</option></select>',
        ];

        $html = Canvatility::modalContentHtml($name, $title, $elements);

        $expected = ''
            . '<div class="modal-body">'
            . '<div id="'.$name.'">'
            . implode('', $elements)
            . '</div>'
            . '</div>'
            . '<div class="modal-footer">'
            . '<div class="diy-action-box">'
            . '<button type="reset" id="'.$name.'-cancel" class="btn btn-danger btn-slideright pull-right" data-dismiss="modal">Cancel</button>'
            . '<button id="users_submitFilterButton" class="btn btn-primary btn-slideright pull-right" type="submit">'
            . '<i class="fa fa-filter"></i> &nbsp; Filter Data '.$title
            . '</button>'
            . '<button id="exportFilterButton'.$name.'" class="btn btn-info btn-slideright pull-right btn-export-csv hide" type="button">Export to CSV</button>'
            . '</div>'
            . '</div>';

        $this->assertSame($expected, $html);
    }
}