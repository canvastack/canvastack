<?php

declare(strict_types=1);

namespace Canvastack\Table\Tests\Unit;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\ActionButtonsRenderer;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../Support/LegacyHelperStubs.php';

final class ActionButtonsRendererFallbackTest extends TestCase
{
    public function test_fallback_renders_minimal_html_when_helper_returns_empty(): void
    {
        $row = ['id' => 42];
        $dt = (object) [
            'useFieldTargetURL' => 'id',
            'columns' => [
                'users' => [
                    'actions' => ['view', 'edit'],
                ],
            ],
            'button_removed' => ['edit'],
        ];

        $html = ActionButtonsRenderer::render($row, $dt, 'users');

        // Because LegacyHelperStubs returns an empty string, renderer should fall through to minimal output
        $this->assertIsString($html);
        $this->assertStringContainsString('btn-view', $html);
        $this->assertStringNotContainsString('btn-edit', $html); // removed by config
        $this->assertStringContainsString('/view/42', $html);
    }
}
