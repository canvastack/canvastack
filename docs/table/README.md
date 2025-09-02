# CanvaStack Table System (DataTables)

Server-rendered table builder integrated with Yajra DataTables for powerful listings, action buttons, dynamic columns (including formula columns), and optional server-side processing. You can use the Facade (`Canvatility`) or the controller plugin `$this->table` for full lifecycle integration.

- Namespace: `Canvastack\\Canvastack\\Library\\Components\\Utility\\Html\\TableUi`
- Facade: `Canvatility::*`
- Controller plugin: `$this->table` (instance of `Library\\Components\\Table\\Objects`)
- Related modules: `Utility\\Table\\{Actions, Filters, FormulaColumns}`

## Key Features

- Controller-first orchestration with `$this->table->lists()` that wires model, columns, actions, filters, and rendering through `$this->render()`.
- Quick generation of HTML table markup with optional container wrappers via Facade.
- Standard action buttons (view/edit/delete) and custom buttons via string or array.
- Modal content helpers for inline dialogs.
- Server-side DataTables with optimized defaults: processing, deferRender, fixedHeader.
- Filters and formula columns for computed output.

## Controller Integration: Using $this->table

The base controller installs a table plugin via trait `Components\Table`. Typical pattern:

```php
use Canvastack\\Canvastack\\Controllers\\Core\\Controller as CoreController;
use App\\Models\\User;

class UserController extends CoreController
{
    public function __construct() {
        parent::__construct(User::class, 'system.accounts.user');
    }

    public function index() {
        $this->setPage('Users');

        // Optional: tune variables before building
        $this->table->label('User Listing');           // custom title
        $this->table->sortable(['name', 'email']);     // sortable columns
        $this->table->clickable(['name']);             // make cells clickable
        $this->table->fixedColumns(0, 1);              // freeze first and last column

        // Build datatable from model (auto-resolves model when needed)
        $this->table->lists(
            'users',                // table name; if null it resolves from model
            ['no', 'id', 'name', 'email'],
            true,                   // actions: true => default [view, edit, delete]
            true,                   // server_side
            true,                   // numbering (adds No/ID)
            ['class' => 'table-bordered'],
            false                   // custom server-side URL (false => default)
        );

        return $this->render();
    }
}
```

Under the hood
- `$this->table->lists()` normalizes columns and labels, attaches actions, and stores render params.
- `$this->render()` calls `$this->table->render(...)` which triggers `Builder::table()`; the final HTML is added into the page content.
- DataTables JSON is handled through `View::initRenderDatatables()` using GET or POST depending on configuration.

### Actions

- Pass `true` to show default buttons (view, edit, delete).
- Provide a string like `'primary|Export:/export'` for a custom action.
- Provide an array of strings for multiple custom actions. Internally parsed by `addActionButtonByString()`.

### Fixed Columns

```php
$this->table->fixedColumns(0, 1);   // freeze first and last columns
$this->table->clearFixedColumns();  // remove previous fixed settings
```

### Sorting/Clickable Columns

```php
$this->table->sortable(['name', 'email']);
$this->table->clickable(['email']);
```

## Facade Quick Start (alternative)

```php
use Canvastack\\Canvastack\\Library\\Components\\Utility\\Canvatility;

$header = ['no', 'id', 'name', 'email', 'Action'];
$rows = [
  ['1', '101', 'Alice', 'alice@example.com', Canvatility::createActionButtons('/u/101', '/u/101/edit', false)],
  ['2', '102', 'Bob',   'bob@example.com',   Canvatility::createActionButtons('/u/102', false, false)],
];

$tableHtml = Canvatility::generateTable(
  'Users', 'users', $header, $rows, ['class' => 'table table-bordered'],
  false,   // numbering
  true     // containers
);
```

## Server-side Mode (Facade)

```php
$tableHtml = Canvatility::generateTable(
  'Users', 'users', $header, [], ['class' => 'table table-bordered'],
  false, true,
  true,                         // server_side
  route('users.data')           // server_side_custom_url
);
```

Controller using Yajra:

```php
use App\\Models\\User;
use Yajra\\DataTables\\DataTables;

public function data() {
  return DataTables::of(User::query())
    ->addColumn('Action', function ($row) {
      return Canvatility::createActionButtons(
        route('users.show', $row->id),
        route('users.edit', $row->id),
        false
      );
    })
    ->toJson();
}
```

Expected JSON follows DataTables spec (`draw`, `recordsTotal`, `recordsFiltered`, `data`).

## Action Buttons (Facade)

```php
// Quickly generate common buttons
$html = Canvatility::createActionButtons($viewUrl, $editUrl, $deleteUrl);

// Add custom buttons from string
$actions = Canvatility::addActionButtonByString('primary|Export:/export');
$html = Canvatility::createActionButtons($viewUrl, false, false, $actions);
```

## Modal Content

```php
$modal = Canvatility::modalContentHtml('user-detail', 'User Detail', [
  '<p>Name: Alice</p>',
  '<p>Email: alice@example.com</p>',
]);
```

## Filters

```php
$normalized = Canvatility::filterNormalize([
  'status' => 'active',
  'from'   => '2025-08-01',
  'to'     => '2025-08-31',
]);
```

## Formula Columns (overview)

Use `Utility\\Table\\FormulaColumns` for computed output such as status badges, derived fields, or concatenation. Keep logic fast for server-side rendering. See tests under `src/Library/Components/Utility/tests` for examples.

## Performance Tips

- Enable server-side for large datasets.
- Use `deferRender: true` and avoid heavy HTML in each cell when possible.
- Compute columns efficiently; avoid running per-row queries.

## API Reference (Selected)

Controller plugin:
- `$this->table->label($label)`
- `$this->table->sortable($columns)`
- `$this->table->clickable($columns)`
- `$this->table->fixedColumns($left_pos = null, $right_pos = null)` / `$this->table->clearFixedColumns()`
- `$this->table->lists(string $table_name = null, $fields = [], $actions = true, $server_side = true, $numbering = true, $attributes = [], $server_side_custom_url = false)`

Facade:
- `Canvatility::generateTable($title, $title_id, array $header, array $body, array $attributes = [], $numbering = false, $containers = true, $server_side = false, $server_side_custom_url = false): string`
- `Canvatility::createActionButtons($view = false, $edit = false, $delete = false, $add_action = [], $as_root = false): string`
- `Canvatility::addActionButtonByString($action, bool $is_array = false): array`
- `Canvatility::modalContentHtml(string $name, string $title, array $elements): string`
- `Canvatility::tableRowAttr(string $value, $attributes): string`

For advanced control, review `Html/TableUi.php`, `Table/Actions.php`, `Table/Filters.php`, `Table/FormulaColumns.php`, and `Library/Components/Table/Objects.php`.