<?php
namespace Canvastack\Canvastack\Library\Components\Table;

use Canvastack\Canvastack\Library\Components\Table\Craft\Builder;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\FixedColumnsManager;

/**
 * Created on 12 Apr 2021
 * Time Created : 19:24:03
 *
 * Marhaban Yaa RAMADHAN
 *
 * @filesource Objects.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
class Objects extends Builder
{
    use \Canvastack\Canvastack\Library\Components\Form\Elements\Tab;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\ColumnsConfigTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\AlignAndStyleTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\ActionsTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\ChartRenderTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\LifecycleStateTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\ModelQueryTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\ListBuilderTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\RelationsTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\FilterSearchTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\FormattingTrait;
    use \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits\ColumnSetTrait;

    public $elements = [];
    public $element_name = [];
    public $records = [];
    public $columns = [];
    public $labels = [];
    public $relations = [];
    public $connection;
    private $params = [];
    private $setDatatable = true;
    private $tableType = 'datatable';

    /**
     * --[openTabHTMLForm]--
     */
    private $opentabHTML = '--[openTabHTMLForm]--';

    public function __construct()
    {
        $this->element_name['table'] = $this->tableType;
        $this->variables['table_class'] = 'table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';
    }

    public function method($method)
    {
        $this->method = $method;
    }

    public $labelTable = null;

    public function label($label)
    {
        $this->labelTable = $label;
    }

    /**
     * Menentukan kolom mana yang akan di set fixed (tetap)
     *
     * Fungsi ini digunakan untuk menentukan kolom mana yang akan di set fixed
     * (tetap) di dalam datatable. Kolom yang di set fixed akan tetap di posisi
     * yang sama meskipipun di scroll horisontal.
     *
     * @param  int  $left_pos : Kolom yang akan di set fixed di sebelah kiri
     *                        Jika di set maka kolom akan tetap di posisi yang
     *                        sama meskipun di scroll horisontal.
     *                        Nilai 0 berarti kolom pertama, 1 berarti kolom
     *                        kedua, dan seterusnya.
     * @param  int  $right_pos : Kolom yang akan di set fixed di sebelah kanan
     *                        Jika di set maka kolom akan tetap di posisi yang
     *                        sama meskipun di scroll horisontal.
     *                        Nilai 0 berarti kolom pertama, 1 berarti kolom
     *                        kedua, dan seterusnya.
     *
     * Contoh :
     * $this->fixedColumns(0, 1);
     * maka kolom pertama dan kolom terakhir akan di set fixed.
     */
    public function fixedColumns($left_pos = null, $right_pos = null)
    {
        FixedColumnsManager::setFixedColumns($this->variables, $left_pos, $right_pos);
    }

    /**
     * Hapus fixed columns yang sebelumnya di set
     *
     * Fungsi ini digunakan untuk menghapus fixed columns yang sebelumnya di set
     * melalui fungsi fixedColumns. Jika fungsi ini di panggil maka fixed columns
     * akan di hapus dan tidak akan di render di datatable.
     *
     * Contoh :
     * $this->fixedColumns(0, 1);
     * $this->clearFixedColumns();
     * maka fixed columns akan di hapus dan tidak akan di render di datatable.
     */
    public function clearFixedColumns()
    {
        FixedColumnsManager::clearFixedColumns($this->variables);
    }

    /**
     * Set Sortable Column(s)
     *
     * @param  string|array  $columns
     */
    public function sortable($columns = null)
    {
        $this->variables['sortable_columns'] = [];
        $this->variables['sortable_columns'] = $this->checkColumnSet($columns);
    }

    /**
     * Set Clickable Column(s)
     *
     * @param  string|array  $columns
     */
    public function clickable($columns = null)
    {
        $this->variables['clickable_columns'] = [];
        $this->variables['clickable_columns'] = $this->checkColumnSet($columns);
    }

    private function check_column_exist($table_name, $fields, $connection = 'mysql')
    {
        $fieldset = [];
        foreach ($fields as $field) {
            if (canvastack_check_table_columns($table_name, $field, $connection)) {
                $fieldset[] = $field;
            }
        }

        return $fieldset;
    }

    private $variables = [];

    /**
     * Buat List(s) Data Table
     *
     * Fungsi ini digunakan untuk membuat list data table, yang dapat digunakan untuk menampilkan data dari database.
     * Fungsi ini juga dapat digunakan untuk membuat list data table dengan fitur server side, yaitu dengan mengirimkan data melalui AJAX.
     *
     * @param  string  $table_name
     * 	: Nama tabel yang akan di tampilkan dalam list data table.
     * 	: Jika nama tabel tidak di set maka akan menggunakan nama tabel yang di set melalui fungsi model().
     * @param  array  $fields
     * 	: Daftar kolom yang akan di tampilkan dalam list data table.
     * 	: Jika kolom tidak di set maka akan menampilkan semua kolom yang ada di tabel.
     * @param  bool|string|array  $actions
     * 	: Tombol aksi yang akan di tampilkan dalam list data table.
     * 	: Jika di set sebagai boolean true maka akan menampilkan tombol aksi default yaitu view, edit, delete.
     * 	: Jika di set sebagai string maka akan menampilkan tombol aksi custom.
     * 	: Jika di set sebagai array maka akan menampilkan tombol aksi custom yang di definisikan dalam array.
     * 	: Contoh penggunaan:
     * 	: $this->lists('users', [], ['view', 'edit', 'delete']);
     * 	: $this->lists('users', [], 'view|primary|fa-eye');
     * @param  bool  $server_side
     * 	: Jika di set sebagai true maka akan menggunakan server side untuk mengirimkan data.
     * 	: Jika di set sebagai false maka akan menggunakan client side untuk mengirimkan data.
     * @param  bool  $numbering
     * 	: Jika di set sebagai true maka akan menampilkan nomor urut dalam list data table.
     * 	: Jika di set sebagai false maka tidak akan menampilkan nomor urut dalam list data table.
     * @param  array  $attributes
     * 	: Atribut yang akan di tambahkan dalam list data table.
     * 	: Contoh penggunaan:
     * 	: $this->lists('users', [], [], [], [], ['class' => 'table-striped']);
     * @param  bool  $server_side_custom_url
     * 	: Jika di set sebagai true maka akan menggunakan URL custom untuk mengirimkan data dalam server side.
     * 	: Jika di set sebagai false maka akan menggunakan URL default untuk mengirimkan data dalam server side.
     *
     * Contoh penggunaan:
     *
     * $this->lists('users', ['nama', 'alamat'], true, true, true, [], false);
     *
     * Maka akan menampilkan list data table dengan nama tabel 'users', kolom 'nama' dan 'alamat', tombol aksi view, edit, delete, server side, dan nomor urut.
     */
    public function lists(string $table_name = null, $fields = [], $actions = true, $server_side = true, $numbering = true, $attributes = [], $server_side_custom_url = false)
    {
        $table_name = $this->resolveTableName($table_name);
        $this->tableName = $table_name;
        $this->records['index_lists'] = $numbering;

        if (is_array($fields)) {
            // Parse fields and inline labels (colon-separated)
            [$fields, $fieldset_added] = $this->parseFieldsAndLabels($fields);

            $fields = $this->resolveFields($table_name, $fields, $fieldset_added);

            // RELATIONS + LABELS META SHAPER (delegated)
            if (! isset($this->columns[$table_name]) || ! is_array($this->columns[$table_name])) {
                $this->columns[$table_name] = [];
            }
            if (! isset($this->labels) || ! is_array($this->labels)) {
                $this->labels = [];
            }
            $columnsMeta = &$this->columns[$table_name];
            $labelsRef = &$this->labels;
            $fields = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\RelationsAndMetaShaper::apply(
                $fields,
                $fieldset_added,
                (array) ($this->relational_data ?? []),
                (string) $table_name,
                $columnsMeta,
                $labelsRef
            );
        }

        $search_columns = false;
        if (! empty($this->search_columns)) {
            if ($this->all_columns === $this->search_columns) {
                $search_columns = $fields;
            } else {
                $search_columns = $this->search_columns;
            }
        }
        $this->search_columns = $search_columns;

        if (false === $actions) {
            $actions = [];
        }
        $this->columns[$table_name]['lists'] = $fields;
        $this->columns[$table_name]['actions'] = $actions;

        $this->applyColumnsFlagsFromVariables($table_name);
        if (! empty($this->button_removed)) {
            $this->columns[$table_name]['button_removed'] = $this->button_removed;
        }

        $this->tableID[$table_name] = canvastack_clean_strings("Cocanvastack_{$this->tableType}_".$table_name.'_'.canvastack_random_strings(50, false));
        $attributes['table_id'] = $this->tableID[$table_name];
        $attributes['table_class'] = canvastack_clean_strings("Cocanvastack_{$this->tableType}_").' '.$this->variables['table_class'];
        if (! empty($this->variables['background_color'])) {
            $attributes['bg_color'] = $this->variables['background_color'];
        }

        if (! empty($this->variables['on_load'])) {
            if (! empty($this->variables['on_load']['display_limit_rows'])) {
                $this->params[$table_name]['on_load']['display_limit_rows'] = $this->variables['on_load']['display_limit_rows'];
            }
        }

        if (! empty($this->variables['fixed_columns'])) {
            $this->params[$table_name]['fixed_columns'] = $this->variables['fixed_columns'];
        }

        $this->buildParams($table_name, $actions, $numbering, $attributes, $server_side, $server_side_custom_url);

        $this->applyConditionsForTable($table_name);

        if (! empty($this->filter_model)) {
            $this->params[$table_name]['filter_model'] = $this->filter_model;
        }

        $label = null;
        if (! empty($this->variables['table_name'])) {
            $label = $this->variables['table_name'];
        }

        if ('datatable' === $this->tableType) {
            $this->renderDatatable($table_name, $this->columns, $this->params, $label);
        } else {
            $this->renderGeneralTable($table_name, $this->columns, $this->params);
        }
    }

    private function renderDatatable($name, $columns = [], $attributes = [], $label = null)
    {
        if (! empty($this->variables['table_data_model'])) {
            $attributes[$name]['model'] = $this->variables['table_data_model'];
            asort($attributes[$name]);
        }

        $columns[$name]['filters'] = [];
        if (! empty($this->search_columns)) {
            $columns[$name]['filters'] = $this->search_columns;
        }

        $this->setMethod($this->method);

        if (! empty($this->labelTable)) {
            $label = $this->labelTable.':setLabelTable';
            $this->labelTable = null;
        }

        $this->draw($this->tableID[$name], $this->table($name, $columns, $attributes, $label));
    }

    private function renderGeneralTable($name, $columns = [], $attributes = [])
    {
        dd($columns);
    }

    /**
     * Resolve table name from provided value and variables state.
     */
    private function resolveTableName(?string $table_name): ?string
    {
        return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists\TableNameResolver::resolve(
            $table_name,
            $this->variables,
            $this->params,
            $this->modelProcessing
        );
    }

    /**
     * Parse fields and inline labels (label via colon separator)
     * Returns: [normalizedFields, fieldsetAdded]
     */
    private function parseFieldsAndLabels(array $fields): array
    {
        return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists\FieldsAndLabelsParser::parse($fields, $this->labels);
    }

    /**
     * Apply flags/attributes from $this->variables into $this->columns meta for given table.
     */
    private function applyColumnsFlagsFromVariables(string $table_name): void
    {
        \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\ColumnsFlagsApplier::apply(
            $table_name,
            $this->columns,
            $this->variables
        );
    }

    /**
     * Build $this->params for a table name.
     */
    private function buildParams(string $table_name, $actions, bool $numbering, array $attributes, bool $server_side, bool $server_side_custom_url): void
    {
        \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists\ParamsBuilder::build(
            $table_name,
            $this->params,
            $this->variables,
            $actions,
            $numbering,
            $attributes,
            $server_side,
            $server_side_custom_url,
            $this->button_removed
        );
    }

    /**
     * Resolve fields list including existence checks and modelProcessing fallback.
     * Returns normalized list of fields.
     */
    private function resolveFields(string $table_name, array $fields, array $fieldset_added): array
    {
        $context = [
            'connection' => $this->connection,
            'variables' => &$this->variables,
            'modelProcessing' => &$this->modelProcessing,
        ];
        return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists\FieldsResolver::resolve(
            $table_name,
            $fields,
            $fieldset_added,
            $context
        );
    }

    /**
     * Normalize raw where conditions into the expected structure.
     * Mirrors legacy behavior; no functional changes intended.
     */
    private function normalizeWhereConditions(array $raw): array
    {
        return \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\WhereConditionsNormalizer::normalize($raw);
    }
}
