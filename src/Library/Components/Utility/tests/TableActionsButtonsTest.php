<?php

namespace Canvastack\Canvastack\Library\Components\Utility\tests;

use PHPUnit\Framework\TestCase;
use Canvastack\Canvastack\Library\Components\Utility\Canvatility;

final class TableActionsButtonsTest extends TestCase
{
    public function test_builds_paths_and_custom_buttons()
    {
        // fake row
        $row = (object) ['id' => 5, 'deleted_at' => null];

        // Shim helpers used by Actions::build
        require_once __DIR__.'/__stubs.php';

        $html = Canvatility::tableActionButtons($row, 'id', '/users', ['approve|success|check', 'export|info|download'], ['delete']);

        $this->assertStringContainsString('/users/5', $html);
        $this->assertStringContainsString('/users/5/edit', $html);
        // delete disabled by removed_button
        $this->assertStringNotContainsString('/users/5/delete', $html);
        // custom buttons
        $this->assertStringContainsString('/users/5/approve', $html);
        $this->assertStringContainsString('btn-approve', $html);
        $this->assertStringContainsString('fa-check', $html);
        $this->assertStringContainsString('/users/5/export', $html);
        $this->assertStringContainsString('btn-export', $html);
        $this->assertStringContainsString('fa-download', $html);
    }
}