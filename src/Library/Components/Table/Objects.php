<?php
namespace Canvastack\Origin\Library\Components\Table;

use Canvastack\Origin\Library\Components\Table\Craft\Builder;
use Canvastack\Origin\Library\Components\Form\Elements\Tab;
use Canvastack\Origin\Library\Components\Charts\Objects as Chart;
use PhpParser\Node\Expr\BinaryOp\Identical;

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
 * @email     wisnuwidi@canvastack.com
 */
class Objects extends Builder {
    use Tab;
    
    public $elements      = [];
    public $element_name  = [];
    public $records       = [];
    public $columns       = [];
    public $labels        = [];
    public $relations     = [];
    public $connection;
    
    private $params       = [];
    private $setDatatable = true;
    private $tableType    = 'datatable';
    
    /**
     * --[openTabHTMLForm]--
     */
    private $opentabHTML  = '--[openTabHTMLForm]--';
    
    public function __construct() {
        $this->element_name['table']    = $this->tableType;
        $this->variables['table_class'] = 'table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';
    }
    
    public function method($method) {
        $this->method = $method;
    }
    
    public $labelTable = null;
    public function label($label) {
        $this->labelTable = $label;
    }
    
    private function chartCanvas() {
        return new Chart();
    }
    
    private $chartOptions = [];
    public function chartOptions($option_name, $option_values = []) {
        $this->chartOptions[$option_name] = $option_values;
    }
    
    private $syncElements = false;
    public function chart($chart_type, $fieldsets = [], $format, $category = null, $group = null, $order = null) {
        $chart             = $this->chartCanvas();
        $chart->connection = $this->connection;
        $chart->syncWith($this);
        
        if (!empty($this->chartOptions)) {
            foreach ($this->chartOptions as $optName => $optValues) {
                $chart->{$optName}($optValues);
            }
            unset($this->chartOptions);
        }
        
        $chart->{$chart_type}($this->tableName, $fieldsets, $format, $category, $group, $order);
        
        $this->element_name['chart']      = $chart->chartLibrary;
        $tableIdentity                    = $this->tableID[$this->tableName];
        $canvas                           = [];
        $canvas['chart'][$tableIdentity]  = $chart->elements;
        $initTable                        = [];
        $initTable['chart']               = $this->tableID[$this->tableName];
        
        $tableElement                     = $this->elements[$tableIdentity];
        $canvasElement                    = $canvas['chart'][$tableIdentity];
        $defaultPageFilters               = [];
        if (!empty($this->filter_contents[$tableIdentity]['conditions']['where'])) {
            $defaultPageFilters           = $this->filter_contents[$tableIdentity]['conditions']['where'];
        }
        
        $this->syncElements[$tableIdentity]['identity']['chart_info']    = $chart->identities;
        $this->syncElements[$tableIdentity]['identity']['filter_table']  = "{$tableIdentity}_cdyFILTERForm";
        
        $this->syncElements[$tableIdentity]['datatables']['type']        = $chart_type;
        $this->syncElements[$tableIdentity]['datatables']['source']      = $this->tableName;
        $this->syncElements[$tableIdentity]['datatables']['fields']      = $fieldsets;
        $this->syncElements[$tableIdentity]['datatables']['format']      = $format;
        $this->syncElements[$tableIdentity]['datatables']['category']    = $category;
        $this->syncElements[$tableIdentity]['datatables']['group']       = $group;
        $this->syncElements[$tableIdentity]['datatables']['order']       = $order;
        $this->syncElements[$tableIdentity]['datatables']['page_filter'] = ['where' => $defaultPageFilters];
        
        $chart->modifyFilterTable($this->syncElements[$tableIdentity]);
        
        $syncElements = [];
        $syncElements['chart'][$tableIdentity] = $tableElement . $chart->script_chart['js'] . implode('', $canvasElement);
        
        $this->draw($initTable, $syncElements);
    }
    
    public $filter_scripts = [];
    private function draw($initial, $data = []) {
        if ($data) {
            $multiElements = [];
            if (is_array($initial)) {
                foreach ($initial as $syncElements) {
                    if (is_array($data)) {
                        foreach ($data as $dataValue) {
                            $initData = $dataValue[$syncElements];
                            if (is_array($initData)) {
                                $multiElements[$syncElements] = implode('', $initData);
                            } else {
                                $multiElements[$syncElements] = $initData;
                            }
                        }
                    }
                    $this->elements[$syncElements] = $multiElements[$syncElements];
                }
            } else {
                $this->elements[$initial] = $data;
            }
            
            if (!empty($this->filter_object->add_scripts)) {
                if (true === array_key_exists('add_js', $this->filter_object->add_scripts)) {
                    $scriptCss = [];
                    if (isset($this->filter_object->add_scripts['css'])) {
                        $scriptCss = $this->filter_object->add_scripts['css'];
                        unset($this->filter_object->add_scripts['css']);
                    }
                    
                    $scriptJs = [];
                    if (isset($this->filter_object->add_scripts['js'])) {
                        $scriptJs = $this->filter_object->add_scripts['js'];
                        unset($this->filter_object->add_scripts['js']);
                    }
                    $scriptAdd = $this->filter_object->add_scripts['add_js'];
                    unset($this->filter_object->add_scripts['add_js']);
                    
                    $this->filter_scripts['css'] = $scriptCss;
                    
                    $JSScripts = [];
                    $JSScripts = $scriptJs;
                    foreach ($scriptAdd as $addScripts) {
                        $JSScripts[] = $addScripts;
                    }
                    
                    foreach ($JSScripts as $js) {
                        $this->filter_scripts['js'][] = $js;
                    }
                    
                } else {
                    $this->filter_scripts = $this->filter_object->add_scripts;
                }
            }
        } else {
            $this->elements[] = $initial;
        }
    }
    
    public function render($object) {
        $tabObj = "";
        if (true === is_array($object)) $tabObj = implode('', $object);
        
        if (true === canvas_string_contained($tabObj, $this->opentabHTML)) {
            return $this->renderTab($object);
        } else {
            return $object;
        }
    }
    
    public function setDatatableType($set = true) {
        $this->setDatatable = $set;
        if (true !== $this->setDatatable) $this->tableType = 'self::table';
        $this->element_name['table'] = $this->tableType;
    }
    
    public function setName($table_name) {
        $this->variables['table_name'] = $table_name;
    }
    
    public function setFields($fields) {
        $this->variables['table_fields'] = $fields;
    }
    
    public function model($model) {
        $this->variables['table_data_model'] = $model;
    }
    
    /**
     * Call Model Function
     * 	: Can be used when we would create temp table and render it (before) $this->table->list() function
     *
     * @param object $model_object
     * @param string $function_name
     * @param bool $strict
     *
     * @return object
     */
    public function runModel($model_object, $function_name, $strict) {
        $connection = 'mysql';
        if (null !== $this->connection) $connection = $this->connection;
        
        $modelFunction = $function_name;
        $tableFunction = $function_name;
        if (canvas_string_contained($function_name, '::')) {
            $split = explode('::', $function_name);
            $modelFunction = $split[0];
            $tableFunction = "$split[1]_$split[0]";
        }
        
        $this->variables['model_processing']               = [];
        $this->variables['model_processing']['model']      = $model_object;
        $this->variables['model_processing']['function']   = $modelFunction;
        $this->variables['model_processing']['connection'] = $connection;
        $this->variables['model_processing']['table']      = $tableFunction;
        $this->variables['model_processing']['strict']     = $strict;
    }
    
    public function query($sql) {
        $this->variables['query'] = $sql;
        $this->model('sql');
    }
    
    public function setServerSide($server_side = true) {
        $this->variables['table_server_side'] = $server_side;
    }
    
    /**
     * Merge Columns
     *
     * Digunakan untuk menggabungkan beberapa kolom menjadi satu kolom, maka
     * kolom tersebut akan memiliki label gabungan dan value dari gabungan
     * kolom-kolom yang di merge.
     *
     * @param string $label : Kolom gabungan yang akan digunakan sebagai label
     * @param array $merged_columns : Kolom-kolom yang akan di merge
     * @param string $label_position : Posisi label (top, bottom, left, right)
     *
     * Contoh :
     * $this->mergeColumns('Nama', ['first_name', 'last_name'], 'top');
     * maka kolom 'first_name' dan kolom 'last_name' akan digabungkan menjadi
     * satu kolom dengan label 'Nama' dan value gabungan dari 2 kolom tersebut
     * dan posisi labelnya di atas.
     */
    public function mergeColumns($label, $merged_columns = [], $label_position = 'top') {
        $this->variables['merged_columns'][$label] = ['position' => $label_position, 'counts' => count($merged_columns), 'columns' => $merged_columns];
    }
    
    public $hidden_columns = [];
    public function setHiddenColumns($fields = []) {
        $this->variables['hidden_columns'] = $fields;
    }
    
    /**
     * Menentukan kolom mana yang akan di set fixed (tetap)
     *
     * Fungsi ini digunakan untuk menentukan kolom mana yang akan di set fixed
     * (tetap) di dalam datatable. Kolom yang di set fixed akan tetap di posisi
     * yang sama meskipun di scroll horisontal.
     *
     * @param int $left_pos : Kolom yang akan di set fixed di sebelah kiri
     *                        Jika di set maka kolom akan tetap di posisi yang
     *                        sama meskipun di scroll horisontal.
     *                        Nilai 0 berarti kolom pertama, 1 berarti kolom
     *                        kedua, dan seterusnya.
     * @param int $right_pos : Kolom yang akan di set fixed di sebelah kanan
     *                        Jika di set maka kolom akan tetap di posisi yang
     *                        sama meskipun di scroll horisontal.
     *                        Nilai 0 berarti kolom pertama, 1 berarti kolom
     *                        kedua, dan seterusnya.
     *
     * Contoh :
     * $this->fixedColumns(0, 1);
     * maka kolom pertama dan kolom terakhir akan di set fixed.
     */
    public function fixedColumns($left_pos = null, $right_pos = null) {
        if (!empty($left_pos))  $this->variables['fixed_columns']['left']  = $left_pos;
        if (!empty($right_pos)) $this->variables['fixed_columns']['right'] = $right_pos;
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
    public function clearFixedColumns() {
        if (!empty($this->variables['fixed_columns'])) unset($this->variables['fixed_columns']);
    }
    
    
    /**
     * Fungsi ini digunakan untuk mengatur align kolom di dalam datatable.
     *
     * @param string $align : Nilai align yang di inginkan, bisa berupa "left",
     *                        "center", atau "right".
     * @param array  $columns : Kolom mana yang akan di set align, jika di kosongkan
     *                          maka akan di set ke semua kolom.
     * @param boolean $header : Jika true maka akan di set ke header kolom.
     * @param boolean $body : Jika true maka akan di set ke body kolom.
     *
     * Contoh :
     * $this->setAlignColumns('center', ['name', 'address'], true, false);
     * maka kolom "name" dan "address" akan di set align center di header saja.
     */
    public function setAlignColumns(string $align, $columns = [], $header = true, $body = true) {
        $this->variables['text_align'][$align] = ['columns' => $columns, 'header' => $header, 'body' => $body];
    }
    
    /**
     * Fungsi ini digunakan untuk mengatur align kolom di dalam datatable menjadi right/kanan.
     *
     * @param array  $columns : Kolom mana yang akan di set align right/kanan, jika di kosongkan maka semua kolom akan di set align right/kanan.
     * @param boolean $header : Jika true maka akan di set ke header kolom.
     * @param boolean $body : Jika true maka akan di set ke body kolom.
     *
     * Contoh :
     * $this->setRightColumns(['name', 'address'], true, false);
     * maka kolom "name" dan "address" akan di set align right/kanan di header saja.
     */
    public function setRightColumns($columns = [], $header = true, $body = true) {
        $this->setAlignColumns('right', $columns, $header, $body);
    }
    
    /**
     * Fungsi ini digunakan untuk mengatur align kolom di dalam datatable menjadi center/tengah.
     *
     * @param array  $columns : Kolom mana yang akan di set align center/tengah, jika di kosongkan maka semua kolom akan di set align center/tengah.
     * @param boolean $header : Jika true maka akan di set ke header kolom. Default true.
     * @param boolean $body : Jika true maka akan di set ke body kolom. Default false.
     *
     * Contoh :
     * $this->setCenterColumns(['name', 'address'], true, false);
     * maka kolom "name" dan "address" akan di set align center/tengah di header saja.
     */
    public function setCenterColumns($columns = [], $header = true, $body = false) {
        $this->setAlignColumns('center', $columns, $header, $body);
    }
    
    /**
     * Fungsi ini digunakan untuk mengatur align kolom di dalam datatable menjadi left/kiri.
     *
     * @param array  $columns : Kolom mana yang akan di set align left/kiri, jika di kosongkan maka semua kolom akan di set align left/kiri.
     * @param boolean $header : Jika true maka akan di set ke header kolom. Default true.
     * @param boolean $body : Jika true maka akan di set ke body kolom. Default true.
     *
     * Contoh :
     * $this->setLeftColumns(['name', 'address'], true, false);
     * maka kolom "name" dan "address" akan di set align left/kiri di header saja.
     */
    public function setLeftColumns($columns = [], $header = true, $body = true) {
        $this->setAlignColumns('left', $columns, $header, $body);
    }
    
    /**
     * Fungsi ini digunakan untuk mengatur warna background kolom di dalam datatable.
     *
     * @param string $color : Nilai warna yang di inginkan dalam format hex (cth: #ffffff).
     * @param string $text_color : Nilai warna teks yang di inginkan dalam format hex (cth: #000000).
     * @param array  $columns : Kolom mana yang akan di set warna background, jika di kosongkan maka semua kolom akan di set warna background.
     * @param boolean $header : Jika true maka akan di set ke header kolom. Default true.
     * @param boolean $body : Jika true maka akan di set ke body kolom. Default false.
     *
     * Contoh :
     * $this->setBackgroundColor('#f5f5f5', '#000000', ['name', 'address'], true, false);
     * maka kolom "name" dan "address" akan di set warna background #f5f5f5 dan teks #000000 di header saja.
     */
    public function setBackgroundColor($color, $text_color = null, $columns = null, $header = true, $body = false) {
        $this->variables['background_color'][$color] = ['code' => $color, 'text' => $text_color, 'columns' => $columns, 'header' => $header, 'body' => $body];
    }
    
    /**
     * Fungsi ini digunakan untuk mengatur lebar kolom di dalam datatable.
     *
     * @param string $field_name : Nama kolom yang akan di set lebar.
     * @param int $width : Nilai lebar kolom yang di inginkan dalam satuan pixel (px).
     *                    Jika di kosongkan maka lebar kolom akan di set secara otomatis.
     *
     * Contoh :
     * $this->setColumnWidth('name', 200);
     * maka kolom "name" akan di set lebar 200px.
     */
    public function setColumnWidth($field_name, $width = false) {
        $this->variables['column_width'][$field_name] = $width;
    }
    
    /**
     * Menambahkan atribut khusus ke dalam tabel.
     *
     * Fungsi ini digunakan untuk menambahkan atribut HTML ke dalam elemen tabel,
     * seperti 'class', 'style', atau atribut lainnya yang diperlukan.
     *
     * @param array $attributes : Array berisi pasangan kunci dan nilai dari atribut
     *                            yang akan ditambahkan ke dalam tabel.
     *                            Contoh: ['class' => 'my-class', 'style' => 'width:100%;']
     *
     * Contoh penggunaan:
     * $this->addAttributes(['class' => 'table-striped', 'style' => 'width:100%;']);
     * Maka, atribut 'class' dan 'style' akan ditambahkan ke elemen tabel.
     */
    public function addAttributes($attributes = []) {
        $this->variables['add_table_attributes'] = $attributes;
    }
    
    /**
     * Mengatur lebar elemen tabel secara keseluruhan.
     *
     * Fungsi ini digunakan untuk mengatur lebar elemen tabel secara keseluruhan
     * dengan menggunakan satuan pengukuran yang diinginkan.
     *
     * @param int $width : Lebar elemen tabel yang diinginkan dalam satuan pengukuran
     *                    yang diinginkan. Misal: 100, 200, 300, dst.
     * @param string $measurement : Satuan pengukuran yang diinginkan. Misal: 'px', '%', 'em', dst.
     *
     * Contoh penggunaan:
     * $this->setWidth(1000, 'px');
     * Maka lebar elemen tabel akan diatur menjadi 1000px.
     */
    public function setWidth(int $width, string $measurement = 'px') {
        return $this->addAttributes(['style' => "min-width:{$width}{$measurement};"]);
    }
    
    /**
     * Semua kolom
     *
     * Properti ini digunakan untuk mengindikasikan bahwa fungsi sebelumnya
     * akan dijalankan untuk semua kolom yang ada di dalam tabel.
     *
     * Contoh penggunaan:
     * $this->setBackgroundColor('#f5f5f5', '#000000', $this->all_columns, true, false);
     * maka semua kolom akan di set warna background #f5f5f5 dan teks #000000 di header saja.
     */
    private $all_columns = 'all::columns';
    
    /**
     * Memeriksa dan mengatur set kolom.
     *
     * Fungsi ini digunakan untuk memeriksa apakah parameter kolom kosong atau tidak.
     * Jika kolom kosong, maka akan mengembalikan nilai default berdasarkan kondisi.
     * Jika kolom tidak kosong, maka akan mengembalikan kolom tersebut.
     *
     * @param mixed $columns : Kolom yang akan diperiksa. Bisa berisi array kolom
     *                         tertentu atau kosong.
     *
     * @return array Mengembalikan array dengan kunci 'all::columns' yang bernilai true
     *               atau false jika kolom kosong, atau mengembalikan kolom yang diberikan.
     *
     * Contoh penggunaan:
     *
     * // Menggunakan semua kolom
     * $hasil = $this->checkColumnSet(null);
     * // $hasil akan berisi ['all::columns' => true]
     *
     * // Menggunakan kolom tertentu
     * $hasil = $this->checkColumnSet(['nama', 'alamat']);
     * // $hasil akan berisi ['nama', 'alamat']
     */
    private function checkColumnSet($columns) {
        if (empty($columns)) {
            if (false === $columns) {
                $value = [$this->all_columns => false];
            } else {
                $value = [$this->all_columns => true];
            }
        } else {
            $value = $columns;
        }
        
        return $value;
    }
    
    /**
     * Relational Data
     *
     * Properti ini digunakan untuk menyimpan data hasil relasi antara tabel.
     * Data yang disimpan berupa array associative yang berisi kunci relasi
     * dan nilai berupa array yang berisi data relasi.
     *
     * Contoh penggunaan:
     *
     * // Misal kita memiliki relasi antara tabel users dan tabel roles
     * // dengan nama relasi "user_roles"
     * $this->relational_data = [
     *     'user_roles' => [
     *         'user_id' => 1,
     *         'role_id' => 1,
     *         'role_name' => 'Admin',
     *     ],
     * ];
     *
     * // Maka kita dapat mengakses data relasi dengan cara berikut:
     * $role_name = $this->relational_data['user_roles']['role_name'];
     */
    public $relational_data = [];
    
    /**
     * Menyimpan data hasil relasi antara tabel.
     *
     * Fungsi ini digunakan untuk menyimpan data hasil relasi antara tabel.
     * Data yang disimpan berupa array associative yang berisi kunci relasi
     * dan nilai berupa array yang berisi data relasi.
     *
     * Properti yang digunakan:
     *
     * - $relation_function : Nama relasi yang digunakan.
     * - $fieldname : Nama kolom yang akan di gunakan sebagai target.
     * - $label : Label yang akan di gunakan untuk nama kolom.
     *
     * Contoh penggunaan:
     *
     * // Misal kita memiliki relasi antara tabel users dan tabel roles
     * // dengan nama relasi "user_roles"
     * $this->setRelationData('user_roles', 'users:id', 'role_name');
     *
     * // Maka kita dapat mengakses data relasi dengan cara berikut:
     * $role_name = $this->relational_data['user_roles']['field_target']['role_name']['relation_data'][$user_id]['field_value'];
     *
     * @param object $model
     * @param string $relation_function
     * @param string $field_display
     * @param array  $filter_foreign_keys :[
     *			'base_user_group:user_id' => 'users:id',
     *			'base_group:id'           => 'base_user_group:group_id'
     *	]
     * @param string $label
     * @param string $field_connect
     *
     * @return array
     */
    private function relation_draw($relation, $relation_function, $fieldname, $label) {
        if (!empty($relation->{$relation_function})) {
            $dataRelate = $relation->{$relation_function}->getAttributes();
            $relateKEY  = intval($relation['id']);
        } else {
            $dataRelate = $relation->getAttributes();
            $relateKEY  = intval($dataRelate['id']);
        }
        
        $fieldReplacement = null;
        if (canvas_string_contained($fieldname, '::')) {
            $fieldsplit       = explode('::', $fieldname);
            $fieldReplacement = $fieldsplit[0];
            $fieldname        = $fieldsplit[1];
            $data_relation    = $dataRelate[$fieldname];
            $data_value       = $dataRelate[$fieldname];
        } else {
            $data_relation    = $dataRelate[$fieldname];
            $data_value       = $dataRelate[$fieldname];
        }
        
        if (!empty($data_relation)) {
            $fieldset = $fieldname;
            if (!is_empty($fieldReplacement)) $fieldset = $fieldReplacement;
            
            $this->relational_data[$relation_function]['field_target'][$fieldset]['field_name']  = $fieldset;
            $this->relational_data[$relation_function]['field_target'][$fieldset]['field_label'] = $label;
            
            if (!empty($relation->pivot)) {
                foreach ($relation->pivot->getAttributes() as $pivot_field => $pivot_data) {
                    $this->relational_data[$relation_function]['field_target'][$fieldset]['relation_data'][$relateKEY][$pivot_field] = $pivot_data;
                }
            }
            
            $this->relational_data[$relation_function]['field_target'][$fieldset]['relation_data'][$relateKEY]['field_value'] = $data_value;
        }
    }
    
    /**
     * Set Relation Data Table
     *
     * @param object $model
     * @param string $relation_function
     * @param string $field_display
     * @param array  $filter_foreign_keys :[
     *			'base_user_group:user_id' => 'users:id',
     *			'base_group:id'           => 'base_user_group:group_id'
     *	]
     * @param string $label
     * @param string $field_connect
     *
     * @return array
     */
    private function relationship($model, $relation_function, $field_display, $filter_foreign_keys = [], $label = null, $field_connect = null) {
        if (!empty($model->with($relation_function)->get())) {
            $relational_data = $model->with($relation_function)->get();
            if (empty($label)) {
                $label = ucwords(canvas_clean_strings($field_display, ' '));
            }
            
            foreach ($relational_data as $item) {
                if (!empty($item->{$relation_function})) {
                    if (canvas_is_collection($item->{$relation_function})) {
                        foreach ($item->{$relation_function} as $relation) {
                            $this->relation_draw($relation, $relation_function, $field_display, $label);
                        }
                    } else {
                        $this->relation_draw($item, $relation_function, "{$field_connect}::{$field_display}", $label);
                    }
                }
            }
            
            if (!empty($filter_foreign_keys)) $this->relational_data[$relation_function]['foreign_keys'] = $filter_foreign_keys;
        }
    }
    
    /**
     * Set Simple Relation Data Table
     *
     * @param object $model
     * @param string $relation_function
     * @param string $field_display
     * @param array  $filter_foreign_keys :[
     *			'base_user_group:user_id' => 'users:id',
     *			'base_group:id'           => 'base_user_group:group_id'
     *	]
     * @param string $label
     *
     * @return array
     */
    public function relations($model, $relation_function, $field_display, $filter_foreign_keys = [], $label = null) {
        return $this->relationship($model, $relation_function, $field_display, $filter_foreign_keys, $label, null);
    }
    
    /**
     * Change Fieldname Value With Relational Data
     *
     * @param object $model
     * @param string $relation_function
     * @param string $field_display
     * @param string $label
     * @param string $field_connect
     *
     * @return array
     */
    public function fieldReplacementValue($model, $relation_function, $field_display, $label = null, $field_connect = null) {
        return $this->relationship($model, $relation_function, $field_display, [], $label, $field_connect);
    }
    
    public function orderby($column, $order = 'asc') {
        $this->variables['orderby_column'] = [];
        $this->variables['orderby_column'] = ['column' => $column, 'order' => $order];
    }
    
    /**
     * Set Sortable Column(s)
     *
     * @param string|array $columns
     */
    public function sortable($columns = null) {
        $this->variables['sortable_columns'] = [];
        $this->variables['sortable_columns'] = $this->checkColumnSet($columns);
    }
    
    /**
     * Set Clickable Column(s)
     *
     * @param string|array $columns
     */
    public function clickable($columns = null) {
        $this->variables['clickable_columns'] = [];
        $this->variables['clickable_columns'] = $this->checkColumnSet($columns);
    }
    
    public $search_columns = false;
    
    /**
     * Menentukan kolom mana yang dapat dicari di dalam datatable.
     *
     * Fungsi ini digunakan untuk mengatur kolom-kolom yang dapat digunakan sebagai filter pencarian.
     * Jika parameter kolom tidak diisi, maka secara default semua kolom akan digunakan.
     *
     * @param string|array $columns : Kolom yang ingin diatur sebagai kolom pencarian. Bisa berisi nama kolom atau array nama-nama kolom.
     *
     * Properti:
     * - $this->variables['searchable_columns'] : Menyimpan daftar kolom yang dapat dicari.
     * - $this->search_columns : Menyimpan kolom yang akan digunakan untuk filter pencarian.
     * - $this->all_columns : Menandakan semua kolom di dalam tabel.
     *
     * Contoh penggunaan:
     *
     * // Menggunakan semua kolom untuk pencarian
     * $this->searchable();
     * // atau
     * $this->searchable(null);
     *
     * // Menggunakan kolom tertentu untuk pencarian
     * $this->searchable(['nama', 'alamat']);
     */
    public function searchable($columns = null) {
        $this->variables['searchable_columns'] = [];
        $this->variables['searchable_columns'] = $this->checkColumnSet($columns);
        if (empty($columns)) {
            if (false === $columns) {
                $filter_columns = false;
            } else {
                $filter_columns = $this->all_columns;
            }
        } else {
            $filter_columns = $columns;
        }
        
        $this->search_columns = $filter_columns;
    }
    
    /**
     * Set Searching Data Filter
     *
     * @param string $column
     * 		: field name target
     * @param string $type
     * 		: inputbox     [no relational data $relate auto set with false],
     *         datebox      [no relational data $relate auto set with false],
     *         daterangebox [no relational data $relate auto set with false],
     *         selectbox    [single or multi],
     *         checkbox,
     *         radiobox
     * @param boolean|string|array $relate
     * 		: if false = no relational Data
     * 		: if true  = relational data set to all others columns/fieldname members
     * 		: if (string) fieldname / other column = relate to just one that column target was setted
     * 		: if (array) fieldnames / others any columns = relate to any that column target was setted
     */
    public function filterGroups($column, $type, $relate = false) {
        $filters           = [];
        $filters['column'] = $column;
        $filters['type']   = $type;
        $filters['relate'] = $relate;
        
        $this->variables['filter_groups'][] = $filters;
    }
    
    /**
     * Mengatur batasan jumlah baris yang akan ditampilkan saat pemuatan awal.
     *
     * Fungsi ini digunakan untuk mengatur jumlah baris yang ditampilkan ketika tabel
     * pertama kali dimuat. Pengguna dapat menentukan jumlah baris dalam bentuk angka
     * atau menggunakan string '*' atau 'all' untuk menampilkan semua baris.
     *
     * @param mixed $limit : Batasan jumlah baris yang akan ditampilkan. Bisa berupa
     *                       integer untuk jumlah baris tertentu atau string '*'/'all'
     *                       untuk menampilkan semua baris.
     *
     * Contoh penggunaan:
     *
     * // Menampilkan 10 baris pada pemuatan awal
     * $this->displayRowsLimitOnLoad(10);
     *
     * // Menampilkan semua baris pada pemuatan awal
     * $this->displayRowsLimitOnLoad('all');
     */
    public function displayRowsLimitOnLoad($limit = 10) {
        if (is_string($limit)) {
            if (in_array(strtolower($limit), ['*', 'all'])) {
                $this->variables['on_load']['display_limit_rows'] = '*';
            }
        } else {
            $this->variables['on_load']['display_limit_rows'] = intval($limit);
        }
    }
    
    public function clearOnLoad() {
        unset($this->variables['on_load']['display_limit_rows']);
    }
    
    protected $filter_model = [];
    public function filterModel(array $data = []) {
        $this->filter_model = $data;
    }
    
    private function check_column_exist($table_name, $fields, $connection = 'mysql') {
        $fieldset = [];
        foreach ($fields as $field) {
            if (canvas_check_table_columns($table_name, $field, $connection)) {
                $fieldset[] = $field;
            }
        }
        
        return $fieldset;
    }
    
    private $clear_variables = null;
    private function clearVariables($clear_set = true) {
        $this->clear_variables = $clear_set;
        if (true === $this->clear_variables) {
            $this->clear_all_variables();
        }
    }
    
    public function clear($clear_set = true) {
        return $this->clearVariables($clear_set);
    }
    
    public function clearVar($name) {
        $this->variables[$name] = [];
    }
    
    
    public $useFieldTargetURL = 'id';
    public function setUrlValue($field = 'id') {
        $this->variables['url_value'] = $field;
        $this->useFieldTargetURL = $field;
    }
    
    private $variables = [];
    private function clear_all_variables() {
        $this->variables['on_load']              = [];
        $this->variables['url_value']            = [];
        $this->variables['merged_columns']       = [];
        $this->variables['text_align']           = [];
        $this->variables['background_color']     = [];
        $this->variables['attributes']           = [];
        $this->variables['orderby_column']       = [];
        $this->variables['sortable_columns']     = [];
        $this->variables['clickable_columns']    = [];
        $this->variables['searchable_columns']   = [];
        $this->variables['filter_groups']        = [];
        $this->variables['column_width']         = [];
        $this->variables['format_data']          = [];
        $this->variables['add_table_attributes'] = [];
        $this->variables['fixed_columns']        = [];
        $this->variables['model_processing']     = [];
    }
    
    public $conditions = [];
    public function where($field_name, $logic_operator = false, $value = false) {
        $this->conditions['where'] = [];
        if (is_array($field_name)) {
            foreach ($field_name as $fieldname => $fieldvalue) {
                $this->conditions['where'][] = [
                    'field_name' => $fieldname,
                    'operator'   => '=',
                    'value'      => $fieldvalue
                ];
            }
        } else {
            $this->conditions['where'][] = [
                'field_name' => $field_name,
                'operator'   => $logic_operator,
                'value'      => $value
            ];
        }
    }
    
    /**
     * Filter Table
     *
     * @param array $filters
     * 		: $this->model_filters
     * @return array
     */
    public function filterConditions($filters = []) {
        return $this->where($filters);
    }
    
    /**
     * Buat Kondisi Kolom Berdasarkan Nilai Tertentu
     *
     * Fungsi ini digunakan untuk membuat kondisi kolom berdasarkan nilai tertentu.
     * Kondisi ini berguna untuk mengatur tampilan kolom berdasarkan nilai yang di dapat dari database.
     *
     * @param string $field_name
     * 		: Nama kolom yang akan di set kondisi.
     * @param string $target
     * 		: Target kolom yang akan di set kondisi. Bisa berupa 'row', 'cell', atau 'field_name'.
     * 		: Jika target adalah 'row', maka kondisi akan di set ke baris yang berisi data kolom tersebut.
     * 		: Jika target adalah 'cell', maka kondisi akan di set ke kolom yang berisi data tersebut.
     * 		: Jika target adalah 'field_name', maka kondisi akan di set ke kolom yang berisi data tersebut.
     * @param string $logic_operator
     * 		: Operator logika yang digunakan untuk membandingkan nilai kolom dengan nilai yang di set.
     * 		: Bisa berupa '==', '!=', '===', '!==', '>', '<', '>=', '<='.
     * @param string $value
     * 		: Nilai yang di set sebagai perbandingan dengan nilai kolom.
     * @param string $rule
     * 		: Aturan yang digunakan untuk mengatur tampilan kolom berdasarkan nilai yang di dapat.
     * 		: Bisa berupa 'css style', 'prefix', 'suffix', 'prefix&suffix', 'replace', 'integer', 'float', 'float|2'.
     * @param string|array $action
     * 		: Aksi yang akan di lakukan jika kondisi terpenuhi.
     * 		: Jika di set sebagai string, maka akan menggantikan url button dengan url yang di set.
     * 		: Jika di set sebagai array, maka akan di gunakan untuk aturan 'prefix&suffix'.
     * 		: Array pertama akan di set sebagai prefix dan array terakhir akan di set sebagai suffix.
     *
     * Contoh penggunaan:
     * $this->table->columnCondition('text_field', 'cell', '!==', 'Testing', 'prefix', '! ');
     * maka kolom "text_field" akan di set dengan prefix "!" jika nilai kolom tidak sama dengan "Testing".
     *
     * Contoh lain:
     * $this->table->columnCondition('user_status', 'action', '==', 'Disabled', 'replace', 'url::action_check|danger|volume-off');
     * maka kolom "user_status" akan di set dengan menggantikan url button dengan url "action_check" jika nilai kolom sama dengan "Disabled".
     */
    public function columnCondition(string $field_name, string $target, string $logic_operator = null, string $value = null, string $rule, $action) {
        $this->conditions['columns'][] = [
            'field_name'     => $field_name,
            'field_target'   => $target,
            'logic_operator' => $logic_operator,
            'value'          => $value,
            'rule'           => $rule,
            'action'         => $action
        ];
    }
    
    public $formula = [];
    
    /**
     * Membuat Formula Untuk Menghitung Nilai Kolom
     *
     * Fungsi ini digunakan untuk membuat formula yang dapat digunakan untuk menghitung nilai kolom tertentu.
     * Formula ini dapat digunakan untuk menghitung nilai kolom yang dihitung berdasarkan beberapa kolom lainnya.
     *
     * @param string $name
     * 		: Nama dari formula yang akan dibuat.
     * 		: Nama ini akan digunakan sebagai nama kolom yang dihitung.
     * @param string $label
     * 		: Label dari formula yang akan dibuat.
     * 		: Label ini akan digunakan sebagai nama tampilan dari kolom yang dihitung.
     * @param array $field_lists
     * 		: Daftar kolom yang akan digunakan untuk menghitung nilai formula.
     * 		: Kolom-kolom ini harus berupa array yang berisi nama-nama kolom yang diinginkan.
     * @param string $logic
     * 		: Operator logika yang digunakan untuk menghitung nilai formula.
     * 		: Operator logika ini dapat berupa '+', '-', '*', '/', '%', '||', '&&'.
     * @param string $node_location
     * 		: Lokasi node yang akan di isi dengan hasil perhitungan formula.
     * 		: Jika di set, maka hasil perhitungan formula akan di isi ke node yang di set.
     * 		: Jika tidak di set, maka hasil perhitungan formula akan di isi ke node yang sama dengan nama formula.
     * @param bool $node_after_node_location
     * 		: Jika true, maka hasil perhitungan formula akan di isi setelah node yang di set.
     * 		: Jika false, maka hasil perhitungan formula akan di isi sebelum node yang di set.
     *
     * Contoh penggunaan:
     * $this->table->formula('total', 'Total', ['harga', 'jumlah'], '*', 'tbody', true);
     * maka akan membuat formula dengan nama 'total' yang akan menghitung nilai kolom 'harga' dan 'jumlah' dengan operator '*' dan akan di isi ke node 'tbody' setelah node yang sama dengan nama formula.
     */
    public function formula(string $name, string $label = null, array $field_lists, string $logic, string $node_location = null, bool $node_after_node_location = true) {
        $this->labels[$name]           = $label;
        $this->conditions['formula'][] = [
            'name'          => $name,
            'label'         => $label,
            'field_lists'   => $field_lists,
            'logic'         => $logic,
            'node_location' => $node_location,
            'node_after'    => $node_after_node_location
        ];
    }
    
    /**
     * Format Data
     *
     * Fungsi ini digunakan untuk mengatur format penampilan data di dalam tabel.
     * Fungsi ini dapat digunakan untuk mengatur format penampilan data berupa angka, boolean, atau string.
     *
     * @param string|array $fields
     * 		: Nama kolom yang akan di format.
     * 		: Jika di set sebagai string, maka hanya kolom dengan nama yang di set yang akan di format.
     * 		: Jika di set sebagai array, maka beberapa kolom dengan nama yang di set akan di format.
     * @param int $decimal_endpoint
     * 		: Jumlah desimal yang akan di tampilkan.
     * 		: Jika di set maka akan menampilkan jumlah desimal yang di set.
     * 		: Jika tidak di set maka akan menampilkan jumlah desimal sesuai dengan default.
     * @param string $separator
     * 		: Pemisah desimal yang akan di gunakan.
     * 		: Jika di set maka akan menggunakan pemisah desimal yang di set.
     * 		: Jika tidak di set maka akan menggunakan pemisah desimal yang default (".").
     * @param string $format
     * 		: Tipe format yang akan di gunakan.
     * 		: Jika di set maka akan menggunakan tipe format yang di set.
     * 		: Jika tidak di set maka akan menggunakan tipe format yang default ("number").
     *
     * Contoh penggunaan:
     * $this->table->format('harga', 2, ',', 'number');
     * maka kolom "harga" akan di format dengan menggunakan 2 desimal, pemisah desimal "," dan tipe format "number".
     */
    public function format($fields, int $decimal_endpoint = 0, $separator = '.', $format = 'number') {
        if (is_array($fields)) {
            foreach ($fields as $field) {
                $this->variables['format_data'][$field] = [
                    'field_name'       => $field,
                    'decimal_endpoint' => $decimal_endpoint,
                    'format_type'      => $format,
                    'separator'        => $separator
                ];
            }
            
        } else {
            $this->variables['format_data'][$fields] = [
                'field_name'          => $fields,
                'decimal_endpoint'    => $decimal_endpoint,
                'format_type'         => $format,
                'separator'           => $separator
            ];
        }
    }
    
    public function set_regular_table() {
        $this->tableType = 'regular';
    }
    
    public $button_removed = [];
    
    /**
     * Menghapus tombol dari daftar tombol yang tersedia.
     *
     * Fungsi ini digunakan untuk menghapus tombol-tombol tertentu dari daftar tombol
     * yang tersedia. Tombol yang dihapus akan disimpan dalam properti $button_removed.
     *
     * @param mixed $remove : Tombol yang akan dihapus. Bisa berupa string untuk satu tombol
     *                        atau array untuk beberapa tombol.
     *
     * Contoh penggunaan:
     *
     * // Menghapus satu tombol
     * $this->removeButtons('edit');
     *
     * // Menghapus beberapa tombol
     * $this->removeButtons(['view', 'delete']);
     *
     * Maka tombol 'edit' atau tombol 'view' dan 'delete' akan dihapus dari daftar tombol yang tersedia.
     */
    public function removeButtons($remove) {
        if (!empty($remove)) {
            if (is_array($remove)) {
                $this->button_removed = $remove;
            } else {
                $this->button_removed = [$remove];
            }
        }
    }
    
    private $defaultButtons = ['view', 'edit', 'delete'];
    
    /**
     * Mengatur aksi tombol untuk tabel.
     *
     * Fungsi ini digunakan untuk mengatur aksi tombol yang tersedia dalam tabel.
     * Jika parameter $default_actions tidak diatur ke true, maka tombol default akan dihapus.
     *
     * @param array $actions : Daftar aksi tombol yang ingin ditetapkan.
     * @param boolean|array $default_actions : Jika diatur ke false, tombol default akan dihapus.
     *                                        Jika diatur ke array, tombol yang sesuai dalam array akan dihapus.
     *
     * Contoh penggunaan:
     *
     * // Mengatur aksi tombol tanpa tombol default
     * $this->setActions(['custom_action1', 'custom_action2'], false);
     *
     * // Mengatur aksi tombol dengan menghapus tombol default 'edit' dan 'delete'
     * $this->setActions(['custom_action1'], ['edit', 'delete']);
     */
    public function setActions($actions = [], $default_actions = true) {
        if (true !== $default_actions) {
            if (is_array($default_actions)) {
                $this->removeButtons($default_actions);
            } else {
                $this->removeButtons($this->defaultButtons);
            }
        }
    }
    
    private $objectInjections = [];
    public $filterPage = [];
    /**
     * Initiate Configuration
     *
     * @param string $connection
     * @param array $object
     */
    public function config($object = []) {
        if (!empty($this->connection)) {
            $this->connection($this->connection);
        }
        
        if (!empty($this->filter_page)) {
            $this->filterPage = $this->filter_page;
        }
    }
    
    public function connection($db_connection) {
        $this->connection = $db_connection;
    }
    
    public function resetConnection() {
        $this->connection = null;
    }
    
    public $modelProcessing = [];
    public $tableName = [];
    public $tableID   = [];
    
    /**
     * Buat List(s) Data Table
     *
     * Fungsi ini digunakan untuk membuat list data table, yang dapat digunakan untuk menampilkan data dari database.
     * Fungsi ini juga dapat digunakan untuk membuat list data table dengan fitur server side, yaitu dengan mengirimkan data melalui AJAX.
     *
     * @param string $table_name
     * 	: Nama tabel yang akan di tampilkan dalam list data table.
     * 	: Jika nama tabel tidak di set maka akan menggunakan nama tabel yang di set melalui fungsi model().
     * @param array $fields
     * 	: Daftar kolom yang akan di tampilkan dalam list data table.
     * 	: Jika kolom tidak di set maka akan menampilkan semua kolom yang ada di tabel.
     * @param boolean|string|array $actions
     * 	: Tombol aksi yang akan di tampilkan dalam list data table.
     * 	: Jika di set sebagai boolean true maka akan menampilkan tombol aksi default yaitu view, edit, delete.
     * 	: Jika di set sebagai string maka akan menampilkan tombol aksi custom.
     * 	: Jika di set sebagai array maka akan menampilkan tombol aksi custom yang di definisikan dalam array.
     * 	: Contoh penggunaan:
     * 	: $this->lists('users', [], ['view', 'edit', 'delete']);
     * 	: $this->lists('users', [], 'view|primary|fa-eye');
     * @param boolean $server_side
     * 	: Jika di set sebagai true maka akan menggunakan server side untuk mengirimkan data.
     * 	: Jika di set sebagai false maka akan menggunakan client side untuk mengirimkan data.
     * @param boolean $numbering
     * 	: Jika di set sebagai true maka akan menampilkan nomor urut dalam list data table.
     * 	: Jika di set sebagai false maka tidak akan menampilkan nomor urut dalam list data table.
     * @param array $attributes
     * 	: Atribut yang akan di tambahkan dalam list data table.
     * 	: Contoh penggunaan:
     * 	: $this->lists('users', [], [], [], [], ['class' => 'table-striped']);
     * @param boolean $server_side_custom_url
     * 	: Jika di set sebagai true maka akan menggunakan URL custom untuk mengirimkan data dalam server side.
     * 	: Jika di set sebagai false maka akan menggunakan URL default untuk mengirimkan data dalam server side.
     *
     * Contoh penggunaan:
     *
     * $this->lists('users', ['nama', 'alamat'], true, true, true, [], false);
     *
     * Maka akan menampilkan list data table dengan nama tabel 'users', kolom 'nama' dan 'alamat', tombol aksi view, edit, delete, server side, dan nomor urut.
     */
    public function lists(string $table_name = null, $fields = [], $actions = true, $server_side = true, $numbering = true, $attributes = [], $server_side_custom_url = false) {
        if (!empty($this->variables['model_processing'])) {
            if ($table_name !== $this->variables['model_processing']['table']) {
                $table_name = $this->variables['model_processing']['table'];
            }
            
            $this->modelProcessing[$table_name] = $this->variables['model_processing'];
        }
        
        if (null === $table_name) {
            if (!empty($this->variables['table_data_model'])) {
                if ('sql' === $this->variables['table_data_model']) {
                    $sql        = $this->variables['query'];
                    $table_name = canvas_get_table_name_from_sql($sql);
                    $this->params[$table_name]['query'] = $sql;
                } else {
                    $table_name = canvas_get_model_table($this->variables['table_data_model']);
                }
            }
            
            $this->variables['table_name'] = $table_name;
        }
        $this->tableName = $table_name;
        $this->records['index_lists'] = $numbering;
        
        if (is_array($fields)) {
            // Check if any column(s) set label by colon(:) separator
            $recola = [];
            foreach ($fields as $icol => $cols) {
                if (canvas_string_contained($cols, ':')) {
                    $split_cname   = explode(':', $cols);
                    $this->labels[$split_cname[0]] = $split_cname[1];
                    $recola[$icol] = $split_cname[0];
                } else {
                    $recola[$icol] = $cols;
                }
            }
            $fields         = $recola;
            $fieldset_added = $fields;
            
            if (!empty($fields)) {
                // If table was not view
                if (!canvas_string_contained($table_name, 'view_')) {
                    $fields = $this->check_column_exist($table_name, $fields, $this->connection);
                    
                    // Check if any $this->table->runModel() called
                    if (empty($fields) && !empty($this->modelProcessing)) {
                        if (!empty($recola)) $fields = $recola;
                        if (!canvas_schema('hasTable', $table_name)) {
                            canvas_model_processing_table($this->modelProcessing, $table_name);
                        }
                        $fields = canvas_get_table_columns($table_name);
                    }
                    
                }
            } elseif (!empty($this->variables['table_fields'])) {
                $fields = $this->check_column_exist($table_name, $this->variables['table_fields']);
            } else {
                $fields = canvas_get_table_columns($table_name, $this->connection);
                
                if (empty($fields) && !empty($this->modelProcessing)) {
                    if (!canvas_schema('hasTable', $table_name)) {
                        canvas_model_processing_table($this->modelProcessing, $table_name);
                    }
                    $fields = canvas_get_table_columns($table_name);
                }
            }
            
            // RELATIONAL PROCESS
            $relations        = [];
            $field_relations  = [];
            $fieldset_changed = [];
            if (!empty($this->relational_data)) {
                foreach ($this->relational_data as $relData) {
                    if (!empty($relData['field_target'])) {
                        foreach ($relData['field_target'] as $fr_name => $relation_fields) {
                            $field_relations[$fr_name] = $relation_fields;
                            if (in_array($fr_name, $fields)) {
                                $fieldset_changed[$fr_name] = $fr_name;
                            }
                        }
                    }
                    if (!empty($relData['foreign_keys'])) $this->columns[$table_name]['foreign_keys'] = $relData['foreign_keys'];
                }
            }
            
            if (!empty($field_relations)) {
                $checkFieldSet = array_diff($fieldset_added, $fields);
                if (!empty($fieldset_changed)) {
                    $fieldsetChanged = [];
                    foreach ($fields as $fid => $fval) {
                        if (!empty($fieldset_changed[$fval])) {
                            $fieldsetChanged[$fid] = $fieldset_changed[$fval];
                            unset($fields[$fid]);
                        }
                    }
                    $checkFieldSet = array_merge_recursive_distinct($checkFieldSet, $fieldsetChanged);
                }
                
                if (!empty($checkFieldSet)) {
                    foreach ($checkFieldSet as $index => $field_diff) {
                        if (!empty($field_relations[$field_diff])) {
                            $relational_data                                      = $field_relations[$field_diff];
                            $this->labels[$relational_data['field_name']]         = $relational_data['field_label'];
                            $relations[$index]                                    = $relational_data['field_name'];
                            $this->columns[$table_name]['relations'][$field_diff] = $relational_data;
                        }
                    }
                }
                
                $refields = [];
                if (!empty($relations)) {
                    foreach ($relations as $reid => $relation_name) {
                        $refields = canvas_array_insert($fields, $reid, $relation_name);
                    }
                }
                if (!empty($refields)) $fields = $refields;
            }
        }
        
        $search_columns = false;
        if (!empty($this->search_columns)) {
            if ($this->all_columns === $this->search_columns) {
                $search_columns = $fields;
            } else {
                $search_columns = $this->search_columns;
            }
        }
        $this->search_columns = $search_columns;
        
        if (false === $actions) $actions       = [];
        $this->columns[$table_name]['lists']   = $fields;
        $this->columns[$table_name]['actions'] = $actions;
        
        if (!empty($this->variables['text_align']))         $this->columns[$table_name]['align']         = $this->variables['text_align'];
        if (!empty($this->variables['merged_columns']))     $this->columns[$table_name]['merge']         = $this->variables['merged_columns'];
        if (!empty($this->variables['orderby_column']))     $this->columns[$table_name]['orderby']       = $this->variables['orderby_column'];
        if (!empty($this->variables['clickable_columns']))  $this->columns[$table_name]['clickable']     = $this->variables['clickable_columns'];
        if (!empty($this->variables['sortable_columns']))   $this->columns[$table_name]['sortable']      = $this->variables['sortable_columns'];
        if (!empty($this->variables['searchable_columns'])) $this->columns[$table_name]['searchable']    = $this->variables['searchable_columns'];
        if (!empty($this->variables['filter_groups']))      $this->columns[$table_name]['filter_groups'] = $this->variables['filter_groups'];
        if (!empty($this->variables['format_data']))        $this->columns[$table_name]['format_data']   = $this->variables['format_data'];
        if (!empty($this->variables['hidden_columns'])) {
            $this->columns[$table_name]['hidden_columns'] =  $this->variables['hidden_columns'];
            $this->variables['hidden_columns']            =  [];
        }
        if (!empty($this->button_removed)) {
            $this->columns[$table_name]['button_removed'] = $this->button_removed;
        }
        
        $this->tableID[$table_name] = canvas_clean_strings("canvas_{$this->tableType}_" . $table_name . '_' . canvas_random_strings(50, false));
        $attributes['table_id']     = $this->tableID[$table_name];
        $attributes['table_class']  = canvas_clean_strings("canvas_{$this->tableType}_") . ' ' . $this->variables['table_class'];
        if (!empty($this->variables['background_color'])) $attributes['bg_color'] = $this->variables['background_color'];
        
        if (!empty($this->variables['on_load'])) {
            if (!empty($this->variables['on_load']['display_limit_rows'])) {
                $this->params[$table_name]['on_load']['display_limit_rows'] = $this->variables['on_load']['display_limit_rows'];
            }
        }
        
        if (!empty($this->variables['fixed_columns'])) $this->params[$table_name]['fixed_columns'] = $this->variables['fixed_columns'];
        
        $this->params[$table_name]['actions']                         = $actions;
        $this->params[$table_name]['buttons_removed']                 = $this->button_removed;
        
        $this->params[$table_name]['numbering']                       = $numbering;
        $this->params[$table_name]['attributes']                      = $attributes;
        $this->params[$table_name]['server_side']['status']           = $server_side;
        $this->params[$table_name]['server_side']['custom_url']       = $server_side_custom_url;
        if (!empty($this->variables['column_width'])) {
            $this->params[$table_name]['attributes']['column_width']  = $this->variables['column_width'];
        }
        
        if (!empty($this->variables['url_value'])) {
            $this->params[$table_name]['url_value']  = $this->variables['url_value'];
        }
        
        if (!empty($this->variables['add_table_attributes'])) {
            $this->params[$table_name]['attributes']['add_attributes'] = $this->variables['add_table_attributes'];
        }
        
        if (!empty($this->conditions)) {
            $this->params[$table_name]['conditions']      = $this->conditions;
            if (!empty($this->conditions['formula'])) {
                $this->formula[$table_name]               = $this->conditions['formula'];
                unset($this->conditions['formula']);
                $this->conditions[$table_name]['formula'] = $this->formula[$table_name];
            }
            
            if (!empty($this->conditions['where'])) {
                $whereConds = [];
                foreach ($this->conditions['where'] as $where_conds) {
                    $whereConds[$where_conds['field_name']][$where_conds['operator']]['field_name'][$where_conds['field_name']] = $where_conds['field_name'];
                    $whereConds[$where_conds['field_name']][$where_conds['operator']]['operator'][$where_conds['operator']]     = $where_conds['operator'];
                    $whereConds[$where_conds['field_name']][$where_conds['operator']]['values'][]                               = $where_conds['value'];
                }
                
                $whereConditions = [];
                foreach ($whereConds as $whereFields => $whereFieldValues) {
                    foreach ($whereFieldValues as $whereOperators => $whereOperatorValues) {
                        foreach ($whereOperatorValues as $whereOperatorDataKey => $whereOperatorDataValues) {
                            if ('values' === $whereOperatorDataKey) {
                                if (is_array($whereOperatorDataValues)) {
                                    foreach ($whereOperatorDataValues as $whereOperatorDataValue) {
                                        if (is_array($whereOperatorDataValue)) {
                                            foreach ($whereOperatorDataValue as $_whereOperatorDataValue) {
                                                $whereConditions[$whereFields][$whereOperators][$whereOperatorDataKey][$_whereOperatorDataValue] = $_whereOperatorDataValue;
                                            }
                                        } else {
                                            $whereConditions[$whereFields][$whereOperators][$whereOperatorDataKey][$whereOperatorDataValue] = $whereOperatorDataValue;
                                        }
                                    }
                                }
                            } else {
                                $whereConditions[$whereFields][$whereOperators][$whereOperatorDataKey] = $whereOperatorDataValues;
                            }
                        }
                        
                    }
                }
                
                $whereConditionals = [];
                foreach ($whereConditions as $whereConditionsFieldName => $whereConditionsDataFields) {
                    foreach ($whereConditionsDataFields as $whereOperatorsType => $whereConditionalData) {
                        $whereConditionals[$whereConditionsFieldName][$whereOperatorsType]['field_name'] = $whereConditionsFieldName;
                        $whereConditionals[$whereConditionsFieldName][$whereOperatorsType]['operator']   = $whereOperatorsType;
                        $whereConditionals[$whereConditionsFieldName][$whereOperatorsType]['value']      = $whereConditionalData['values'];
                    }
                }
                
                $whereDataConditions = [];
                foreach ($whereConditionals as $whereConditionalsFieldData) {
                    foreach ($whereConditionalsFieldData as $whereConditionalsFieldSets) {
                        $whereDataConditions[] = $whereConditionalsFieldSets;
                    }
                }
                
                $this->conditions[$table_name]['where'] = $whereDataConditions;
            }
            
            if (!empty($this->conditions['columns'])) {
                $columnCond = $this->conditions['columns'];
                unset($this->conditions['columns']);
                $this->conditions[$table_name]['columns'] = $columnCond;
            }
        }
        
        if (!empty($this->filter_model)) $this->params[$table_name]['filter_model'] = $this->filter_model;
        
        $label = null;
        if (!empty($this->variables['table_name'])) $label = $this->variables['table_name'];
        
        if ('datatable' === $this->tableType) {
            $this->renderDatatable($table_name, $this->columns, $this->params, $label);
        } else {
            $this->renderGeneralTable($table_name, $this->columns, $this->params);
        }
    }
    
    private function renderDatatable($name, $columns = [], $attributes = [], $label = null) {
        if (!empty($this->variables['table_data_model'])) {
            $attributes[$name]['model'] = $this->variables['table_data_model'];
            asort($attributes[$name]);
        }
        
        $columns[$name]['filters'] = [];
        if (!empty($this->search_columns)) {
            $columns[$name]['filters'] = $this->search_columns;
        }
        
        $this->setMethod($this->method);
        
        if (!empty($this->labelTable)) {
            $label = $this->labelTable . ':setLabelTable';
            $this->labelTable = null;
        }
        
        $this->draw($this->tableID[$name], $this->table($name, $columns, $attributes, $label));
    }
    
    private function renderGeneralTable($name, $columns = [], $attributes = []) {
        dd($columns);
    }
}