<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Renderers;

use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Tests\TestCase;

class AdminRendererTest extends TestCase
{
    protected AdminRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new AdminRenderer();
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
        $renderer = new AdminRenderer([
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
        $this->assertStringNotContainsString('<th class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-left"><div class="flex items-center gap-2 cursor-pointer hover:text-indigo-600 dark:hover:text-indigo-400">email', $html);
        // Check that email value is not in the table body
        $this->assertStringNotContainsString('john@example.com', $html);
    }

    public function test_applies_column_widths(): void
    {
        $renderer = new AdminRenderer([
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
        $renderer = new AdminRenderer([
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
        $renderer = new AdminRenderer([
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

    public function test_applies_conditional_formatting_with_css_style(): void
    {
        $renderer = new AdminRenderer([
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

    public function test_applies_conditional_formatting_with_prefix(): void
    {
        $renderer = new AdminRenderer([
            'column_conditions' => [
                [
                    'field_name' => 'price',
                    'target' => 'cell',
                    'operator' => null,
                    'value' => null,
                    'rule' => 'prefix',
                    'action' => '$',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'price', 'label' => 'Price']],
            'data' => [
                ['price' => '100'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('$100', $html);
    }

    public function test_applies_conditional_formatting_with_suffix(): void
    {
        $renderer = new AdminRenderer([
            'column_conditions' => [
                [
                    'field_name' => 'discount',
                    'target' => 'cell',
                    'operator' => null,
                    'value' => null,
                    'rule' => 'suffix',
                    'action' => '%',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'discount', 'label' => 'Discount']],
            'data' => [
                ['discount' => '10'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('10%', $html);
    }

    public function test_applies_conditional_formatting_with_replace(): void
    {
        $renderer = new AdminRenderer([
            'column_conditions' => [
                [
                    'field_name' => 'status',
                    'target' => 'cell',
                    'operator' => '==',
                    'value' => '1',
                    'rule' => 'replace',
                    'action' => 'Active',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'status', 'label' => 'Status']],
            'data' => [
                ['status' => '1'],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Active', $html);
        // Check that the status cell contains "Active" and not "1"
        // Note: We can't use assertStringNotContainsString('>1<') because pagination contains "1"
        $this->assertStringContainsString('<td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">Active</td>', $html);
    }

    public function test_calculates_formula_columns(): void
    {
        $renderer = new AdminRenderer([
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

    public function test_handles_division_by_zero_in_formulas(): void
    {
        $renderer = new AdminRenderer([
            'formulas' => [
                [
                    'name' => 'average',
                    'label' => 'Average',
                    'fields' => ['total', 'count'],
                    'logic' => 'total / count',
                    'node_location' => null,
                    'node_after' => true,
                ],
            ],
        ]);

        $data = [
            'columns' => [
                ['name' => 'total', 'label' => 'Total'],
                ['name' => 'count', 'label' => 'Count'],
            ],
            'data' => [
                ['total' => 100, 'count' => 0],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        // Should not crash, should show empty or dash
        $this->assertStringContainsString('Average', $html);
    }

    public function test_applies_number_formatting(): void
    {
        $renderer = new AdminRenderer([
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
        $renderer = new AdminRenderer([
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

    public function test_applies_percentage_formatting(): void
    {
        $renderer = new AdminRenderer([
            'formats' => [
                [
                    'fields' => ['discount'],
                    'decimals' => 1,
                    'separator' => '.',
                    'type' => 'percentage',
                ],
            ],
        ]);

        $data = [
            'columns' => [['name' => 'discount', 'label' => 'Discount']],
            'data' => [
                ['discount' => 15.5],
            ],
            'total' => 1,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('15.5%', $html);
    }

    public function test_renders_action_buttons(): void
    {
        $data = [
            'columns' => ['id', 'name'],
            'data' => [
                ['id' => 1, 'name' => 'John'],
            ],
            'total' => 1,
        ];

        $html = $this->renderer->render($data);

        $this->assertStringContainsString('data-lucide="eye"', $html);
        $this->assertStringContainsString('data-lucide="edit"', $html);
        $this->assertStringContainsString('data-lucide="trash-2"', $html);
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
        $this->assertStringContainsString('data-lucide="inbox"', $html);
    }

    public function test_applies_table_width(): void
    {
        $renderer = new AdminRenderer([
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

    public function test_renders_search_bar_when_enabled(): void
    {
        $renderer = new AdminRenderer([
            'show_search' => true,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 0,
        ];

        $html = $renderer->render($data);

        $this->assertStringContainsString('Search...', $html);
        $this->assertStringContainsString('data-lucide="search"', $html);
    }

    public function test_hides_search_bar_when_disabled(): void
    {
        $renderer = new AdminRenderer([
            'show_search' => false,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 0,
        ];

        $html = $renderer->render($data);

        $this->assertStringNotContainsString('Search...', $html);
    }

    public function test_renders_pagination_when_enabled(): void
    {
        $renderer = new AdminRenderer([
            'show_pagination' => true,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 100,
        ];

        $html = $renderer->render($data);

        // Pagination is handled by DataTables JavaScript, not server-side HTML
        // Check that DataTables script is included (which handles pagination)
        $this->assertStringContainsString('DataTable', $html);
        $this->assertStringContainsString('<script>', $html);
        
        // Check that pagination info translations are included in the script
        $this->assertStringContainsString('info:', $html);
        $this->assertStringContainsString('paginate:', $html);
    }

    public function test_hides_pagination_when_disabled(): void
    {
        $renderer = new AdminRenderer([
            'show_pagination' => false,
        ]);

        $data = [
            'columns' => ['id'],
            'data' => [],
            'total' => 100,
        ];

        $html = $renderer->render($data);

        $this->assertStringNotContainsString('results', $html);
    }
}
