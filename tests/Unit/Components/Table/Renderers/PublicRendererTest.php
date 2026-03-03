<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Renderers;

use Canvastack\Canvastack\Components\Table\Renderers\PublicRenderer;
use Canvastack\Canvastack\Tests\TestCase;

class PublicRendererTest extends TestCase
{
    protected PublicRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new PublicRenderer();
    }

    public function test_renders_basic_table(): void
    {
        $data = [
            'columns' => ['id', 'name', 'email'],
            'data' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'total' => 2,
        ];

        $html = $this->renderer->render($data);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('<thead', $html);
        $this->assertStringContainsString('<tbody', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('jane@example.com', $html);
    }

    public function test_applies_hidden_columns(): void
    {
        $renderer = new PublicRenderer([
            'hidden_columns' => ['email'],
        ]);

        $data = [
            'columns' => ['id', 'name', 'email'],
            'data' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('John Doe', $html);
        // Check that email column header is not in the table
        $this->assertStringNotContainsString('<th class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 text-left">email</th>', $html);
        // Check that email value is not in the table body
        $this->assertStringNotContainsString('john@example.com', $html);
    }

    public function test_applies_column_widths(): void
    {
        $renderer = new PublicRenderer([
            'column_widths' => ['name' => 200],
        ]);

        $data = [
            'columns' => [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'label' => 'Name'],
            ],
            'data' => [],
            'total' => 0,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('width: 200px', $html);
    }

    public function test_applies_column_alignments(): void
    {
        $renderer = new PublicRenderer([
            'column_alignments' => [
                'id' => ['align' => 'right', 'header' => true, 'body' => true],
            ],
        ]);

        $data = [
            'columns' => [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'label' => 'Name'],
            ],
            'data' => [
                ['id' => 1, 'name' => 'John'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('text-right', $html);
    }

    public function test_applies_column_colors(): void
    {
        $renderer = new PublicRenderer([
            'column_colors' => [
                'status' => [
                    'background' => '#10b981',
                    'text' => '#ffffff',
                    'header' => true,
                    'body' => true,
                ],
            ],
        ]);

        $data = [
            'columns' => [
                ['name' => 'status', 'label' => 'Status'],
            ],
            'data' => [
                ['status' => 'Active'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('background-color: #10b981', $html);
        $this->assertStringContainsString('color: #ffffff', $html);
    }

    public function test_supports_dark_mode_classes(): void
    {
        $data = [
            'columns' => ['id'],
            'data' => [['id' => 1]],
            'total' => 1,
        ];

        $html = $this->renderer->render($data);

        $this->assertStringContainsString('dark:bg-gray-900', $html);
        $this->assertStringContainsString('dark:border-gray-800', $html);
        $this->assertStringContainsString('dark:text-gray-100', $html);
    }

    public function test_applies_conditional_formatting(): void
    {
        $renderer = new PublicRenderer([
            'column_conditions' => [
                [
                    'field_name' => 'status',
                    'target' => 'cell',
                    'operator' => '==',
                    'value' => 'active',
                    'rule' => 'css style',
                    'action' => 'color: green;',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'status', 'label' => 'Status']],
            'data' => [
                ['status' => 'active'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('color: green;', $html);
    }

    public function test_calculates_formula_columns(): void
    {
        $renderer = new PublicRenderer([
            'formulas' => [
                [
                    'name' => 'total',
                    'label' => 'Total',
                    'fields' => ['price', 'quantity'],
                    'logic' => 'price * quantity',
                    'node_location' => null,
                    'node_after' => true,
                ],
            ],
        ]);

        $data = [
            'columns' => [
                ['name' => 'price', 'label' => 'Price'],
                ['name' => 'quantity', 'label' => 'Quantity'],
            ],
            'data' => [
                ['price' => 10, 'quantity' => 5],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Total', $html);
        $this->assertStringContainsString('50', $html);
    }

    public function test_applies_number_formatting(): void
    {
        $renderer = new PublicRenderer([
            'formats' => [
                [
                    'fields' => ['price'],
                    'decimals' => 2,
                    'separator' => '.',
                    'type' => 'number',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'price', 'label' => 'Price']],
            'data' => [
                ['price' => 1234.5],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('1,234.50', $html);
    }

    public function test_applies_currency_formatting(): void
    {
        $renderer = new PublicRenderer([
            'formats' => [
                [
                    'fields' => ['price'],
                    'decimals' => 2,
                    'separator' => '.',
                    'type' => 'currency',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'price', 'label' => 'Price']],
            'data' => [
                ['price' => 1234.5],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('$1,234.50', $html);
    }

    public function test_does_not_render_action_buttons(): void
    {
        $data = [
            'columns' => ['id', 'name'],
            'data' => [
                ['id' => 1, 'name' => 'John'],
            ],
            'total' => 1,
        ];

        $html = $this->renderer->render($data);

        $this->assertStringNotContainsString('data-lucide="eye"', $html);
        $this->assertStringNotContainsString('data-lucide="edit"', $html);
        $this->assertStringNotContainsString('data-lucide="trash-2"', $html);
    }

    public function test_escapes_html_in_values(): void
    {
        $data = [
            'columns' => ['name'],
            'data' => [
                ['name' => '<script>alert("xss")</script>'],
            ],
            'total' => 1,
        ];

        $html = $this->renderer->render($data);

        // Check that the script tag is escaped in the table body (not in the JavaScript config)
        $this->assertStringContainsString('&lt;script&gt;', $html);
        // Verify the escaped content appears in a table cell
        $this->assertStringContainsString('<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</td>', $html);
    }

    public function test_renders_empty_state(): void
    {
        $data = [
            'columns' => ['id', 'name'],
            'data' => [],
            'total' => 0,
        ];

        $html = $this->renderer->render($data);

        $this->assertStringContainsString('No data available', $html);
    }

    public function test_applies_table_width(): void
    {
        $renderer = new PublicRenderer([
            'table_width' => '100%',
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 0,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('width: 100%', $html);
    }

    public function test_renders_pagination_when_enabled(): void
    {
        $renderer = new PublicRenderer([
            'show_pagination' => true,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 100,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('100', $html);
        $this->assertStringContainsString('items', $html);
    }

    public function test_hides_pagination_when_disabled(): void
    {
        $renderer = new PublicRenderer([
            'show_pagination' => false,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 100,
        ];

        $html = $renderer->render($data);

        // Check that pagination section is not rendered in the visible HTML
        // The pagination div should not be present
        $this->assertStringNotContainsString('<div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">', $html);
        // Check that "Total: X items" text is not in the visible HTML
        $this->assertStringNotContainsString('Total: <span class="font-semibold text-gray-900 dark:text-gray-100">100</span> items', $html);
    }

    public function test_applies_compact_mode(): void
    {
        $renderer = new PublicRenderer([
            'compact' => true,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [['id' => 1]],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('py-2', $html);
    }

    public function test_applies_hover_effect(): void
    {
        $renderer = new PublicRenderer([
            'hoverable' => true,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [['id' => 1]],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('hover:bg-gray-50', $html);
    }

    public function test_responsive_design_classes(): void
    {
        $data = [
            'columns' => ['id'],
            'data' => [['id' => 1]],
            'total' => 1,
        ];

        $html = $this->renderer->render($data);

        $this->assertStringContainsString('overflow-x-auto', $html);
        $this->assertStringContainsString('rounded-xl', $html);
    }
}
