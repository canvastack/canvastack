<?php

namespace Canvastack\Canvastack\Library\Components\Utility\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

class FormUiTest extends TestCase
{
    public function testButtonMarkupMatchesLegacy()
    {
        $html = Canvatility::formButton(
            'primary',
            'Save',
            ['data-role' => 'action'],
            'a',
            '/save-url',
            'white',
            'round',
            false,
            false,
            'check',
            'green'
        );

        $expected = "<a href=\"/save-url\" class=\"btn  btn-white btn-primary btn-round\"  data-role = 'action' ><i class=\"fa fa-check bigger-120 green\"></i>&nbsp; Save</a>";

        $this->assertSame($expected, $html);
    }

    public function testCheckListMarkupMatchesLegacy()
    {
        $html = Canvatility::formCheckList('agree', '1', 'Agree', true, 'success', 'agree_id');
        $expected = '<div class="ckbox ckbox-success"><input type="checkbox" value="1" name="agree" id="agree_id" checked="checked"><label for="agree_id">&nbsp; Agree</label></div>';
        $this->assertSame($expected, $html);
    }

    public function testSelectboxMergesAttributesLikeLegacy()
    {
        $values = ['1' => 'Admin'];
        $html = Canvatility::formSelectbox('role', $values, null, ['class' => 'custom']);
        // Ensure merged classes preserve chosen classes even when custom class provided
        $this->assertStringContainsString('class="custom chosen-select-deselect chosen-selectbox form-control', $html);
        $this->assertStringContainsString('name="role"', $html);
    }

    public function testAlertMessageSimple()
    {
        $html = Canvatility::formAlertMessage('Saved');
        $this->assertStringContainsString('alert alert-block alert-success', $html);
        // When message is a simple string, ensure it prints inside the block
        $this->assertStringContainsString('Saved', $html);
    }

    public function testTabsHeaderAndContent()
    {
        $header = Canvatility::formCreateHeaderTab('general', 'general_tab', 'active', 'fa fa-cog');
        $content = Canvatility::formCreateContentTab('<p>Hi</p>', 'general_tab', true);
        $this->assertSame('<li class="nav-item"><a class="nav-link active" data-toggle="tab" role="tab" href="#general_tab"><i class="fa fa-cog"></i>General</a></li>', $header);
        $this->assertSame('<div id="general_tab" class="tab-pane fade active show" role="tabpanel"><p>Hi</p></div>', $content);
    }
}