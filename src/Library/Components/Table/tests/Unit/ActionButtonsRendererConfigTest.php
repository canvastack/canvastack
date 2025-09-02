<?php

declare(strict_types=1);

namespace Canvastack\Table\Tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\ActionButtonsRenderer;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../Support/LegacyHelperStubs.php';

final class ActionButtonsRendererConfigTest extends TestCase
{
    public function test_fallback_respects_useFieldTargetURL_when_not_id(): void
    {
        $row = ['id' => 1, 'uuid' => 'abc-123'];
        $dt = (object) [
            'useFieldTargetURL' => 'uuid',
            'columns' => [
                'items' => [
                    'actions' => ['view'],
                ],
            ],
        ];

        $html = ActionButtonsRenderer::render($row, $dt, 'items');

        $this->assertIsString($html);
        $this->assertStringContainsString('btn-view', $html);
        $this->assertStringContainsString('/view/abc-123', $html, 'URL should use uuid value as target');
        $this->assertStringNotContainsString('/view/1', $html, 'Should not use id when useFieldTargetURL provided');
    }

    public function test_actions_true_defaults_and_removed_applied(): void
    {
        $row = ['id' => 9];
        $dt = (object) [
            'columns' => [
                'users' => [
                    'actions' => true,
                ],
            ],
            'button_removed' => ['delete', 'insert'],
        ];

        $html = ActionButtonsRenderer::render($row, $dt, 'users');

        $this->assertIsString($html);
        // Defaults: view, insert, edit, delete ; removed applies
        $this->assertStringContainsString('btn-view', $html);
        $this->assertStringContainsString('btn-edit', $html);
        $this->assertStringNotContainsString('btn-delete', $html);
        $this->assertStringNotContainsString('btn-insert', $html);
    }
}
