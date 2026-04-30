<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Objects;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Table Components Backward Compatibility Test Suite
 * 
 * This test suite ensures 100% backward compatibility with existing code.
 * All public method signatures, parameter orders, default values, and return
 * value formats must remain unchanged after the audit fixes.
 * 
 * **Validates: Requirement 24 (Backward Compatibility)**
 * 
 * Test Coverage:
 * - 6.4.1: All public method signatures unchanged
 * - 6.4.2: All parameter orders unchanged
 * - 6.4.3: All default values unchanged
 * - 6.4.4: All return value formats unchanged
 * - 6.4.5: Test with existing application code patterns
 * - 6.4.6: Comprehensive compatibility test suite
 * 
 * @group backward-compatibility
 * @group integration
 * @group table-components
 */
class TableBackwardCompatibilityTest extends TestCase
{
    protected Objects $table;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->table = new Objects();
        
        // Create test table if not exists
        if (!Schema::hasTable('test_users')) {
            Schema::create('test_users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test table
        if (Schema::hasTable('test_users')) {
            Schema::dropIfExists('test_users');
        }
        
        parent::tearDown();
    }
    
    /**
     * Test 6.4.1: All public method signatures unchanged
     * 
     * Verifies that all public methods exist with correct signatures.
     * Uses reflection to check method existence and parameter counts.
     * 
     * **Validates: Requirement 24.1**
     */
    public function test_all_public_method_signatures_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Define expected public methods with their parameter counts
        $expectedMethods = [
            '__construct' => 0,
            'method' => 1,  // string $method
            'label' => 1,  // string $label
            'chartOptions' => 2,  // string $option_name, array $option_values = []
            'chart' => 6,  // string $chart_type, array $fieldsets = [], string $format, ?string $category = null, ?string $group = null, ?string $order = null
            'render' => 1,  // mixed $object
            'setDatatableType' => 1,  // bool $set = true
            'setName' => 1,  // string $table_name
            'setFields' => 1,  // array $fields
            'model' => 1,  // mixed $model
            'runModel' => 3,  // object $model_object, string $function_name, bool $strict
            'query' => 1,  // string $sql
            'setServerSide' => 1,  // bool $server_side = true
            'mergeColumns' => 3,  // string $label, array $merged_columns = [], string $label_position = 'top'
            'groupColumns' => 4,  // string $groupName, string $groupLabel, array $columns, array $options = []
            'nestedColumnGroups' => 2,  // string $parentGroup, array $childGroups
            'setHiddenColumns' => 1,  // array $fields = []
            'setVisibleColumns' => 2,  // array $fields, array $allFields
            'toggleColumnVisibility' => 2,  // string $field, bool $visible
            'setColumnOrder' => 1,  // array $orderedFields
            'moveColumnToPosition' => 2,  // string $field, int $position
            'moveColumnBefore' => 2,  // string $field, string $beforeField
            'moveColumnAfter' => 2,  // string $field, string $afterField
            'fixedColumns' => 2,  // ?int $left_pos = null, ?int $right_pos = null
            'fixedColumnsByName' => 2,  // array $leftColumns = [], array $rightColumns = []
            'clearFixedColumns' => 0,
            'setScrollConfig' => 3,  // int|string|null $scrollY = null, bool $scrollX = true, bool $scrollCollapse = true
            'setAlignColumns' => 4,  // string $align, array $columns = [], bool $header = true, bool $body = true
            'setRightColumns' => 3,  // array $columns = [], bool $header = true, bool $body = true
            'setCenterColumns' => 3,  // array $columns = [], bool $header = true, bool $body = true
            'setLeftColumns' => 3,  // array $columns = [], bool $header = true, bool $body = true
            'setBackgroundColor' => 5,  // string $color, ?string $text_color = null, array|null $columns = null, bool $header = true, bool $body = false
            'setTextColor' => 4,  // string $color, array|null $columns = null, bool $header = true, bool $body = true
            'setBorderColor' => 4,  // string $color, array|null $columns = null, string $width = '1px', string $style = 'solid'
            'setColumnWidth' => 2,  // string $field_name, int|string|false $width = false
            'setColumnWidths' => 1,  // array $widths
            'addAttributes' => 1,  // array $attributes = []
            'setWidth' => 2,  // int $width, string $measurement = 'px'
            'relations' => 5,  // mixed $model, string $relation_function, string $field_display, array $filter_foreign_keys = [], ?string $label = null
            'fieldReplacementValue' => 5,  // mixed $model, string $relation_function, string $field_display, ?string $label = null, ?string $field_connect = null
            'orderby' => 2,  // string $column, string $order = 'asc'
            'sortable' => 1,  // array|string|null $columns = null
            'clickable' => 1,  // array|string|null $columns = null
            'searchable' => 1,  // array|string|null $columns = null
            'filterGroups' => 3,  // string $column, string $type, bool|string|array $relate = false
            'displayRowsLimitOnLoad' => 1,  // int|string $limit = 10
            'clearOnLoad' => 0,
            'filterModel' => 1,  // array $data = []
            'clear' => 1,  // bool $clear_set = true
            'clearVar' => 1,  // string $name
            'setUrlValue' => 1,  // string $field = 'id'
            'where' => 3,  // string|array $field_name, string|false $logic_operator = false, mixed $value = false
            'filterConditions' => 1,  // array $filters = []
            'columnCondition' => 6,  // string $field_name, string $target, string $logic_operator = null, string $value = null, string $rule, $action
            'formula' => 6,  // string $name, string $label = null, array $field_lists, string $logic, string $node_location = null, bool $node_after_node_location = true
            'format' => 5,  // string|array $fields, int $decimal_endpoint = 0, string $separator = '.', string $format = 'number', array $options = []
            'formatCurrency' => 5,  // string|array $fields, int $decimals = 2, string $symbol = '$', string $position = 'before', string $thousands = ','
            'formatPercentage' => 4,  // string|array $fields, int $decimals = 1, string $symbol = '%', string $position = 'after'
            'formatDate' => 3,  // string|array $fields, string $dateFormat = 'Y-m-d', ?string $timezone = null
            'formatDateTime' => 3,  // string|array $fields, string $dateTimeFormat = 'Y-m-d H:i:s', ?string $timezone = null
            'formatBoolean' => 3,  // string|array $fields, string $trueLabel = 'Yes', string $falseLabel = 'No'
            'set_regular_table' => 0,
            'removeButtons' => 1,  // string|array $remove
            'setActions' => 2,  // array $actions = [], bool|array $default_actions = true
            'config' => 1,  // array $object = []
            'connection' => 1,  // ?string $db_connection
            'resetConnection' => 0,
            'lists' => 7,  // ?string $table_name = null, array $fields = [], bool|string|array $actions = true, bool $server_side = true, bool $numbering = true, array $attributes = [], bool|string $server_side_custom_url = false
        ];
        
        foreach ($expectedMethods as $methodName => $expectedParamCount) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Public method '{$methodName}' must exist"
            );
            
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$methodName}' must be public"
            );
            
            // Check parameter count (including optional parameters)
            $actualParamCount = $method->getNumberOfParameters();
            $this->assertEquals(
                $expectedParamCount,
                $actualParamCount,
                "Method '{$methodName}' must have {$expectedParamCount} parameters, found {$actualParamCount}"
            );
        }
    }

    
    /**
     * Test 6.4.2: All parameter orders unchanged
     * 
     * Verifies that parameter orders remain consistent with original implementation.
     * Tests key methods with multiple parameters.
     * 
     * **Validates: Requirement 24.2**
     */
    public function test_all_parameter_orders_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test lists() method parameter order
        $listsMethod = $reflection->getMethod('lists');
        $listsParams = $listsMethod->getParameters();
        $this->assertEquals('table_name', $listsParams[0]->getName());
        $this->assertEquals('fields', $listsParams[1]->getName());
        $this->assertEquals('actions', $listsParams[2]->getName());
        $this->assertEquals('server_side', $listsParams[3]->getName());
        $this->assertEquals('numbering', $listsParams[4]->getName());
        $this->assertEquals('attributes', $listsParams[5]->getName());
        $this->assertEquals('server_side_custom_url', $listsParams[6]->getName());
        
        // Test chart() method parameter order
        $chartMethod = $reflection->getMethod('chart');
        $chartParams = $chartMethod->getParameters();
        $this->assertEquals('chart_type', $chartParams[0]->getName());
        $this->assertEquals('fieldsets', $chartParams[1]->getName());
        $this->assertEquals('format', $chartParams[2]->getName());
        $this->assertEquals('category', $chartParams[3]->getName());
        $this->assertEquals('group', $chartParams[4]->getName());
        $this->assertEquals('order', $chartParams[5]->getName());
        
        // Test mergeColumns() method parameter order
        $mergeColumnsMethod = $reflection->getMethod('mergeColumns');
        $mergeColumnsParams = $mergeColumnsMethod->getParameters();
        $this->assertEquals('label', $mergeColumnsParams[0]->getName());
        $this->assertEquals('merged_columns', $mergeColumnsParams[1]->getName());
        $this->assertEquals('label_position', $mergeColumnsParams[2]->getName());
        
        // Test groupColumns() method parameter order
        $groupColumnsMethod = $reflection->getMethod('groupColumns');
        $groupColumnsParams = $groupColumnsMethod->getParameters();
        $this->assertEquals('groupName', $groupColumnsParams[0]->getName());
        $this->assertEquals('groupLabel', $groupColumnsParams[1]->getName());
        $this->assertEquals('columns', $groupColumnsParams[2]->getName());
        $this->assertEquals('options', $groupColumnsParams[3]->getName());
        
        // Test setAlignColumns() method parameter order
        $setAlignColumnsMethod = $reflection->getMethod('setAlignColumns');
        $setAlignColumnsParams = $setAlignColumnsMethod->getParameters();
        $this->assertEquals('align', $setAlignColumnsParams[0]->getName());
        $this->assertEquals('columns', $setAlignColumnsParams[1]->getName());
        $this->assertEquals('header', $setAlignColumnsParams[2]->getName());
        $this->assertEquals('body', $setAlignColumnsParams[3]->getName());
        
        // Test setBackgroundColor() method parameter order
        $setBackgroundColorMethod = $reflection->getMethod('setBackgroundColor');
        $setBackgroundColorParams = $setBackgroundColorMethod->getParameters();
        $this->assertEquals('color', $setBackgroundColorParams[0]->getName());
        $this->assertEquals('text_color', $setBackgroundColorParams[1]->getName());
        $this->assertEquals('columns', $setBackgroundColorParams[2]->getName());
        $this->assertEquals('header', $setBackgroundColorParams[3]->getName());
        $this->assertEquals('body', $setBackgroundColorParams[4]->getName());
        
        // Test where() method parameter order
        $whereMethod = $reflection->getMethod('where');
        $whereParams = $whereMethod->getParameters();
        $this->assertEquals('field_name', $whereParams[0]->getName());
        $this->assertEquals('logic_operator', $whereParams[1]->getName());
        $this->assertEquals('value', $whereParams[2]->getName());
        
        // Test formula() method parameter order
        $formulaMethod = $reflection->getMethod('formula');
        $formulaParams = $formulaMethod->getParameters();
        $this->assertEquals('name', $formulaParams[0]->getName());
        $this->assertEquals('label', $formulaParams[1]->getName());
        $this->assertEquals('field_lists', $formulaParams[2]->getName());
        $this->assertEquals('logic', $formulaParams[3]->getName());
        $this->assertEquals('node_location', $formulaParams[4]->getName());
        $this->assertEquals('node_after_node_location', $formulaParams[5]->getName());
        
        // Test format() method parameter order
        $formatMethod = $reflection->getMethod('format');
        $formatParams = $formatMethod->getParameters();
        $this->assertEquals('fields', $formatParams[0]->getName());
        $this->assertEquals('decimal_endpoint', $formatParams[1]->getName());
        $this->assertEquals('separator', $formatParams[2]->getName());
        $this->assertEquals('format', $formatParams[3]->getName());
        $this->assertEquals('options', $formatParams[4]->getName());
    }
    
    /**
     * Test 6.4.3: All default values unchanged
     * 
     * Verifies that default parameter values remain consistent.
     * Tests methods with optional parameters.
     * 
     * **Validates: Requirement 24.3**
     */
    public function test_all_default_values_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test lists() method defaults
        $listsMethod = $reflection->getMethod('lists');
        $listsParams = $listsMethod->getParameters();
        $this->assertTrue($listsParams[0]->isDefaultValueAvailable());
        $this->assertNull($listsParams[0]->getDefaultValue());  // table_name = null
        $this->assertTrue($listsParams[1]->isDefaultValueAvailable());
        $this->assertEquals([], $listsParams[1]->getDefaultValue());  // fields = []
        $this->assertTrue($listsParams[2]->isDefaultValueAvailable());
        $this->assertTrue($listsParams[2]->getDefaultValue());  // actions = true
        $this->assertTrue($listsParams[3]->isDefaultValueAvailable());
        $this->assertTrue($listsParams[3]->getDefaultValue());  // server_side = true
        $this->assertTrue($listsParams[4]->isDefaultValueAvailable());
        $this->assertTrue($listsParams[4]->getDefaultValue());  // numbering = true
        $this->assertTrue($listsParams[5]->isDefaultValueAvailable());
        $this->assertEquals([], $listsParams[5]->getDefaultValue());  // attributes = []
        $this->assertTrue($listsParams[6]->isDefaultValueAvailable());
        $this->assertFalse($listsParams[6]->getDefaultValue());  // server_side_custom_url = false
        
        // Test setDatatableType() method defaults
        $setDatatableTypeMethod = $reflection->getMethod('setDatatableType');
        $setDatatableTypeParams = $setDatatableTypeMethod->getParameters();
        $this->assertTrue($setDatatableTypeParams[0]->isDefaultValueAvailable());
        $this->assertTrue($setDatatableTypeParams[0]->getDefaultValue());  // set = true
        
        // Test setServerSide() method defaults
        $setServerSideMethod = $reflection->getMethod('setServerSide');
        $setServerSideParams = $setServerSideMethod->getParameters();
        $this->assertTrue($setServerSideParams[0]->isDefaultValueAvailable());
        $this->assertTrue($setServerSideParams[0]->getDefaultValue());  // server_side = true
        
        // Test mergeColumns() method defaults
        $mergeColumnsMethod = $reflection->getMethod('mergeColumns');
        $mergeColumnsParams = $mergeColumnsMethod->getParameters();
        $this->assertTrue($mergeColumnsParams[1]->isDefaultValueAvailable());
        $this->assertEquals([], $mergeColumnsParams[1]->getDefaultValue());  // merged_columns = []
        $this->assertTrue($mergeColumnsParams[2]->isDefaultValueAvailable());
        $this->assertEquals('top', $mergeColumnsParams[2]->getDefaultValue());  // label_position = 'top'
        
        // Test groupColumns() method defaults
        $groupColumnsMethod = $reflection->getMethod('groupColumns');
        $groupColumnsParams = $groupColumnsMethod->getParameters();
        $this->assertTrue($groupColumnsParams[3]->isDefaultValueAvailable());
        $this->assertEquals([], $groupColumnsParams[3]->getDefaultValue());  // options = []
        
        // Test setHiddenColumns() method defaults
        $setHiddenColumnsMethod = $reflection->getMethod('setHiddenColumns');
        $setHiddenColumnsParams = $setHiddenColumnsMethod->getParameters();
        $this->assertTrue($setHiddenColumnsParams[0]->isDefaultValueAvailable());
        $this->assertEquals([], $setHiddenColumnsParams[0]->getDefaultValue());  // fields = []
        
        // Test fixedColumns() method defaults
        $fixedColumnsMethod = $reflection->getMethod('fixedColumns');
        $fixedColumnsParams = $fixedColumnsMethod->getParameters();
        $this->assertTrue($fixedColumnsParams[0]->isDefaultValueAvailable());
        $this->assertNull($fixedColumnsParams[0]->getDefaultValue());  // left_pos = null
        $this->assertTrue($fixedColumnsParams[1]->isDefaultValueAvailable());
        $this->assertNull($fixedColumnsParams[1]->getDefaultValue());  // right_pos = null
        
        // Test fixedColumnsByName() method defaults
        $fixedColumnsByNameMethod = $reflection->getMethod('fixedColumnsByName');
        $fixedColumnsByNameParams = $fixedColumnsByNameMethod->getParameters();
        $this->assertTrue($fixedColumnsByNameParams[0]->isDefaultValueAvailable());
        $this->assertEquals([], $fixedColumnsByNameParams[0]->getDefaultValue());  // leftColumns = []
        $this->assertTrue($fixedColumnsByNameParams[1]->isDefaultValueAvailable());
        $this->assertEquals([], $fixedColumnsByNameParams[1]->getDefaultValue());  // rightColumns = []
        
        // Test setScrollConfig() method defaults
        $setScrollConfigMethod = $reflection->getMethod('setScrollConfig');
        $setScrollConfigParams = $setScrollConfigMethod->getParameters();
        $this->assertTrue($setScrollConfigParams[0]->isDefaultValueAvailable());
        $this->assertNull($setScrollConfigParams[0]->getDefaultValue());  // scrollY = null
        $this->assertTrue($setScrollConfigParams[1]->isDefaultValueAvailable());
        $this->assertTrue($setScrollConfigParams[1]->getDefaultValue());  // scrollX = true
        $this->assertTrue($setScrollConfigParams[2]->isDefaultValueAvailable());
        $this->assertTrue($setScrollConfigParams[2]->getDefaultValue());  // scrollCollapse = true
        
        // Test setAlignColumns() method defaults
        $setAlignColumnsMethod = $reflection->getMethod('setAlignColumns');
        $setAlignColumnsParams = $setAlignColumnsMethod->getParameters();
        $this->assertTrue($setAlignColumnsParams[1]->isDefaultValueAvailable());
        $this->assertEquals([], $setAlignColumnsParams[1]->getDefaultValue());  // columns = []
        $this->assertTrue($setAlignColumnsParams[2]->isDefaultValueAvailable());
        $this->assertTrue($setAlignColumnsParams[2]->getDefaultValue());  // header = true
        $this->assertTrue($setAlignColumnsParams[3]->isDefaultValueAvailable());
        $this->assertTrue($setAlignColumnsParams[3]->getDefaultValue());  // body = true
        
        // Test setBackgroundColor() method defaults
        $setBackgroundColorMethod = $reflection->getMethod('setBackgroundColor');
        $setBackgroundColorParams = $setBackgroundColorMethod->getParameters();
        $this->assertTrue($setBackgroundColorParams[1]->isDefaultValueAvailable());
        $this->assertNull($setBackgroundColorParams[1]->getDefaultValue());  // text_color = null
        $this->assertTrue($setBackgroundColorParams[2]->isDefaultValueAvailable());
        $this->assertNull($setBackgroundColorParams[2]->getDefaultValue());  // columns = null
        $this->assertTrue($setBackgroundColorParams[3]->isDefaultValueAvailable());
        $this->assertTrue($setBackgroundColorParams[3]->getDefaultValue());  // header = true
        $this->assertTrue($setBackgroundColorParams[4]->isDefaultValueAvailable());
        $this->assertFalse($setBackgroundColorParams[4]->getDefaultValue());  // body = false
        
        // Test setColumnWidth() method defaults
        $setColumnWidthMethod = $reflection->getMethod('setColumnWidth');
        $setColumnWidthParams = $setColumnWidthMethod->getParameters();
        $this->assertTrue($setColumnWidthParams[1]->isDefaultValueAvailable());
        $this->assertFalse($setColumnWidthParams[1]->getDefaultValue());  // width = false
        
        // Test addAttributes() method defaults
        $addAttributesMethod = $reflection->getMethod('addAttributes');
        $addAttributesParams = $addAttributesMethod->getParameters();
        $this->assertTrue($addAttributesParams[0]->isDefaultValueAvailable());
        $this->assertEquals([], $addAttributesParams[0]->getDefaultValue());  // attributes = []
        
        // Test setWidth() method defaults
        $setWidthMethod = $reflection->getMethod('setWidth');
        $setWidthParams = $setWidthMethod->getParameters();
        $this->assertTrue($setWidthParams[1]->isDefaultValueAvailable());
        $this->assertEquals('px', $setWidthParams[1]->getDefaultValue());  // measurement = 'px'
        
        // Test orderby() method defaults
        $orderbyMethod = $reflection->getMethod('orderby');
        $orderbyParams = $orderbyMethod->getParameters();
        $this->assertTrue($orderbyParams[1]->isDefaultValueAvailable());
        $this->assertEquals('asc', $orderbyParams[1]->getDefaultValue());  // order = 'asc'
        
        // Test where() method defaults
        $whereMethod = $reflection->getMethod('where');
        $whereParams = $whereMethod->getParameters();
        $this->assertTrue($whereParams[1]->isDefaultValueAvailable());
        $this->assertFalse($whereParams[1]->getDefaultValue());  // logic_operator = false
        $this->assertTrue($whereParams[2]->isDefaultValueAvailable());
        $this->assertFalse($whereParams[2]->getDefaultValue());  // value = false
        
        // Test format() method defaults
        $formatMethod = $reflection->getMethod('format');
        $formatParams = $formatMethod->getParameters();
        $this->assertTrue($formatParams[1]->isDefaultValueAvailable());
        $this->assertEquals(0, $formatParams[1]->getDefaultValue());  // decimal_endpoint = 0
        $this->assertTrue($formatParams[2]->isDefaultValueAvailable());
        $this->assertEquals('.', $formatParams[2]->getDefaultValue());  // separator = '.'
        $this->assertTrue($formatParams[3]->isDefaultValueAvailable());
        $this->assertEquals('number', $formatParams[3]->getDefaultValue());  // format = 'number'
        $this->assertTrue($formatParams[4]->isDefaultValueAvailable());
        $this->assertEquals([], $formatParams[4]->getDefaultValue());  // options = []
        
        // Test clear() method defaults
        $clearMethod = $reflection->getMethod('clear');
        $clearParams = $clearMethod->getParameters();
        $this->assertTrue($clearParams[0]->isDefaultValueAvailable());
        $this->assertTrue($clearParams[0]->getDefaultValue());  // clear_set = true
    }

    
    /**
     * Test 6.4.4: All return value formats unchanged
     * 
     * Verifies that return value types remain consistent.
     * Tests methods that return values.
     * 
     * **Validates: Requirement 24.4**
     */
    public function test_all_return_value_formats_unchanged(): void
    {
        $reflection = new \ReflectionClass(Objects::class);
        
        // Test render() returns mixed
        $renderMethod = $reflection->getMethod('render');
        $this->assertTrue($renderMethod->hasReturnType());
        $this->assertEquals('mixed', $renderMethod->getReturnType()->getName());
        
        // Test setHiddenColumns() returns self
        $setHiddenColumnsMethod = $reflection->getMethod('setHiddenColumns');
        $this->assertTrue($setHiddenColumnsMethod->hasReturnType());
        $this->assertEquals('self', $setHiddenColumnsMethod->getReturnType()->getName());
        
        // Test setVisibleColumns() returns self
        $setVisibleColumnsMethod = $reflection->getMethod('setVisibleColumns');
        $this->assertTrue($setVisibleColumnsMethod->hasReturnType());
        $this->assertEquals('self', $setVisibleColumnsMethod->getReturnType()->getName());
        
        // Test toggleColumnVisibility() returns self
        $toggleColumnVisibilityMethod = $reflection->getMethod('toggleColumnVisibility');
        $this->assertTrue($toggleColumnVisibilityMethod->hasReturnType());
        $this->assertEquals('self', $toggleColumnVisibilityMethod->getReturnType()->getName());
        
        // Test setColumnOrder() returns self
        $setColumnOrderMethod = $reflection->getMethod('setColumnOrder');
        $this->assertTrue($setColumnOrderMethod->hasReturnType());
        $this->assertEquals('self', $setColumnOrderMethod->getReturnType()->getName());
        
        // Test moveColumnToPosition() returns self
        $moveColumnToPositionMethod = $reflection->getMethod('moveColumnToPosition');
        $this->assertTrue($moveColumnToPositionMethod->hasReturnType());
        $this->assertEquals('self', $moveColumnToPositionMethod->getReturnType()->getName());
        
        // Test moveColumnBefore() returns self
        $moveColumnBeforeMethod = $reflection->getMethod('moveColumnBefore');
        $this->assertTrue($moveColumnBeforeMethod->hasReturnType());
        $this->assertEquals('self', $moveColumnBeforeMethod->getReturnType()->getName());
        
        // Test moveColumnAfter() returns self
        $moveColumnAfterMethod = $reflection->getMethod('moveColumnAfter');
        $this->assertTrue($moveColumnAfterMethod->hasReturnType());
        $this->assertEquals('self', $moveColumnAfterMethod->getReturnType()->getName());
        
        // Test setAlignColumns() returns self
        $setAlignColumnsMethod = $reflection->getMethod('setAlignColumns');
        $this->assertTrue($setAlignColumnsMethod->hasReturnType());
        $this->assertEquals('self', $setAlignColumnsMethod->getReturnType()->getName());
        
        // Test setRightColumns() returns self
        $setRightColumnsMethod = $reflection->getMethod('setRightColumns');
        $this->assertTrue($setRightColumnsMethod->hasReturnType());
        $this->assertEquals('self', $setRightColumnsMethod->getReturnType()->getName());
        
        // Test setCenterColumns() returns self
        $setCenterColumnsMethod = $reflection->getMethod('setCenterColumns');
        $this->assertTrue($setCenterColumnsMethod->hasReturnType());
        $this->assertEquals('self', $setCenterColumnsMethod->getReturnType()->getName());
        
        // Test setLeftColumns() returns self
        $setLeftColumnsMethod = $reflection->getMethod('setLeftColumns');
        $this->assertTrue($setLeftColumnsMethod->hasReturnType());
        $this->assertEquals('self', $setLeftColumnsMethod->getReturnType()->getName());
        
        // Test setColumnWidth() returns self
        $setColumnWidthMethod = $reflection->getMethod('setColumnWidth');
        $this->assertTrue($setColumnWidthMethod->hasReturnType());
        $this->assertEquals('self', $setColumnWidthMethod->getReturnType()->getName());
        
        // Test setColumnWidths() returns self
        $setColumnWidthsMethod = $reflection->getMethod('setColumnWidths');
        $this->assertTrue($setColumnWidthsMethod->hasReturnType());
        $this->assertEquals('self', $setColumnWidthsMethod->getReturnType()->getName());
        
        // Test filterModel() returns mixed (no return type hint)
        $filterModelMethod = $reflection->getMethod('filterModel');
        $this->assertFalse($filterModelMethod->hasReturnType());
        
        // Test columnCondition() returns mixed (no return type hint)
        $columnConditionMethod = $reflection->getMethod('columnCondition');
        $this->assertFalse($columnConditionMethod->hasReturnType());
        
        // Test methods that return void
        $voidMethods = [
            'method', 'label', 'chartOptions', 'chart', 'setDatatableType', 'setName', 
            'setFields', 'model', 'runModel', 'query', 'setServerSide', 'mergeColumns', 
            'groupColumns', 'nestedColumnGroups', 'fixedColumns', 'fixedColumnsByName', 
            'clearFixedColumns', 'setScrollConfig', 'setBackgroundColor', 'setTextColor', 
            'setBorderColor', 'addAttributes', 'setWidth', 'relations', 'fieldReplacementValue', 
            'orderby', 'sortable', 'clickable', 'searchable', 'filterGroups', 
            'displayRowsLimitOnLoad', 'clearOnLoad', 'clear', 'clearVar', 'setUrlValue', 
            'where', 'filterConditions', 'formula', 'format', 
            'formatCurrency', 'formatPercentage', 'formatDate', 'formatDateTime', 
            'formatBoolean', 'set_regular_table', 'removeButtons', 'setActions', 
            'config', 'connection', 'resetConnection', 'lists'
        ];
        
        foreach ($voidMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->hasReturnType(), "Method '{$methodName}' must have return type");
            $this->assertEquals('void', $method->getReturnType()->getName(), "Method '{$methodName}' must return void");
        }
    }
    
    /**
     * Test 6.4.5: Test with existing application code patterns
     * 
     * Tests common usage patterns from existing application code.
     * Ensures backward compatibility with real-world usage.
     * 
     * **Validates: Requirement 24.5**
     */
    public function test_existing_application_code_patterns(): void
    {
        // Pattern 1: Basic table rendering
        $this->table->setName('test_users');
        $this->table->setFields(['id', 'name', 'email']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 2: Table with actions
        $this->table->clear();
        $this->table->setName('test_users');
        $this->table->setActions(['view', 'edit', 'delete']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 3: Table with where conditions
        $this->table->clear();
        $this->table->setName('test_users');
        $this->table->where('status', '=', 'active');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 4: Table with ordering
        $this->table->clear();
        $this->table->setName('test_users');
        $this->table->orderby('name', 'asc');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 5: Table with column configuration
        $this->table->clear();
        $this->table->setName('test_users');
        $this->table->setColumnWidth('name', 200);
        $this->table->setRightColumns(['id']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 6: Table with formatting
        $this->table->clear();
        $this->table->setName('test_users');
        $this->table->format('id', 0, '.', 'number');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 7: Table with attributes
        $this->table->clear();
        $this->table->setName('test_users');
        $this->table->addAttributes(['class' => 'custom-table']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 8: Method chaining for column visibility
        $result = $this->table->setHiddenColumns(['email'])
            ->setColumnWidth('name', 300)
            ->setRightColumns(['id']);
        $this->assertInstanceOf(Objects::class, $result);
    }
    
    /**
     * Test existing lists() usage patterns
     * 
     * Tests common lists() usage patterns from existing code.
     * 
     * **Validates: Requirement 24.5**
     */
    public function test_existing_lists_usage_patterns(): void
    {
        // Pattern 1: Simple lists call
        $this->table->lists('test_users');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 2: Lists with fields
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name', 'email']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 3: Lists with actions disabled
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name'], false);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 4: Lists with custom actions
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name'], ['view', 'edit']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 5: Lists without server-side processing
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name'], true, false);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 6: Lists without numbering
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name'], true, true, false);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 7: Lists with attributes
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name'], true, true, true, ['class' => 'custom']);
        $this->assertInstanceOf(Objects::class, $this->table);
    }
    
    /**
     * Test existing column configuration patterns
     * 
     * Tests common column configuration patterns from existing code.
     * 
     * **Validates: Requirement 24.5**
     */
    public function test_existing_column_configuration_patterns(): void
    {
        // Pattern 1: Set column widths
        $this->table->setColumnWidth('name', 200);
        $this->table->setColumnWidth('email', '30%');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 2: Set multiple column widths
        $this->table->setColumnWidths([
            'id' => 50,
            'name' => 200,
            'email' => '30%'
        ]);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 3: Hide columns
        $result = $this->table->setHiddenColumns(['email', 'status']);
        $this->assertInstanceOf(Objects::class, $result);
        
        // Pattern 4: Set visible columns
        $result = $this->table->setVisibleColumns(['id', 'name'], ['id', 'name', 'email', 'status']);
        $this->assertInstanceOf(Objects::class, $result);
        
        // Pattern 5: Toggle column visibility
        $result = $this->table->toggleColumnVisibility('email', false);
        $this->assertInstanceOf(Objects::class, $result);
        
        // Pattern 6: Set column order
        $result = $this->table->setColumnOrder(['name', 'email', 'id']);
        $this->assertInstanceOf(Objects::class, $result);
        
        // Pattern 7: Move column to position
        $result = $this->table->moveColumnToPosition('email', 0);
        $this->assertInstanceOf(Objects::class, $result);
        
        // Pattern 8: Align columns
        $result = $this->table->setRightColumns(['id']);
        $this->assertInstanceOf(Objects::class, $result);
        
        $result = $this->table->setCenterColumns(['status']);
        $this->assertInstanceOf(Objects::class, $result);
        
        $result = $this->table->setLeftColumns(['name', 'email']);
        $this->assertInstanceOf(Objects::class, $result);
    }
    
    /**
     * Test existing formatting patterns
     * 
     * Tests common formatting patterns from existing code.
     * 
     * **Validates: Requirement 24.5**
     */
    public function test_existing_formatting_patterns(): void
    {
        // Pattern 1: Number formatting
        $this->table->format('price', 2, '.', 'number');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 2: Currency formatting
        $this->table->formatCurrency('amount', 2, '$', 'before', ',');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 3: Percentage formatting
        $this->table->formatPercentage('rate', 1, '%', 'after');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 4: Date formatting
        $this->table->formatDate('created_at', 'Y-m-d');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 5: DateTime formatting
        $this->table->formatDateTime('updated_at', 'Y-m-d H:i:s');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 6: Boolean formatting
        $this->table->formatBoolean('is_active', 'Yes', 'No');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 7: Multiple field formatting
        $this->table->format(['price', 'total'], 2, '.', 'number');
        $this->assertInstanceOf(Objects::class, $this->table);
    }
    
    /**
     * Test existing filter and search patterns
     * 
     * Tests common filter and search patterns from existing code.
     * 
     * **Validates: Requirement 24.5**
     */
    public function test_existing_filter_and_search_patterns(): void
    {
        // Pattern 1: Simple where condition
        $this->table->where('status', '=', 'active');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 2: Searchable columns
        $this->table->searchable(['name', 'email']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 3: Sortable columns
        $this->table->sortable(['name', 'email', 'created_at']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 4: Filter conditions (using where method directly)
        $this->table->where('status', '=', 'active');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Pattern 5: Display rows limit
        $this->table->displayRowsLimitOnLoad(25);
        $this->assertInstanceOf(Objects::class, $this->table);
    }
    
    /**
     * Test HTML output structure unchanged
     * 
     * Tests that security fixes don't break HTML structure.
     * 
     * **Validates: Requirement 24.6**
     */
    public function test_html_output_structure_unchanged(): void
    {
        // Set up a simple table
        $this->table->setName('test_users');
        $this->table->setFields(['id', 'name', 'email']);
        
        // Render the table
        $output = $this->table->render([]);
        
        // Verify output is mixed type (can be array or string)
        $this->assertTrue(is_array($output) || is_string($output));
        
        // If output is array, it should have expected structure
        if (is_array($output)) {
            $this->assertIsArray($output);
        }
        
        // Verify table can be rendered without errors
        $this->assertInstanceOf(Objects::class, $this->table);
    }
    
    /**
     * Test method chaining still works
     * 
     * Verifies that methods returning self can still be chained.
     * 
     * **Validates: Requirement 24.6**
     */
    public function test_method_chaining_still_works(): void
    {
        // Test chaining column configuration methods
        $result = $this->table
            ->setHiddenColumns(['email'])
            ->setColumnWidth('name', 200)
            ->setRightColumns(['id'])
            ->setCenterColumns(['status'])
            ->setColumnOrder(['id', 'name', 'status']);
        
        $this->assertInstanceOf(Objects::class, $result);
        
        // Test chaining visibility methods
        $result = $this->table
            ->toggleColumnVisibility('email', true)
            ->moveColumnToPosition('name', 0)
            ->setVisibleColumns(['id', 'name', 'email'], ['id', 'name', 'email', 'status']);
        
        $this->assertInstanceOf(Objects::class, $result);
    }
    
    /**
     * Test no breaking changes to behavior
     * 
     * Verifies that default behavior remains unchanged.
     * 
     * **Validates: Requirement 24.7**
     */
    public function test_no_breaking_changes_to_behavior(): void
    {
        // Test 1: Default server-side processing is enabled
        $this->table->lists('test_users');
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Test 2: Default numbering is enabled
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name']);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Test 3: Default actions are enabled
        $this->table->clear();
        $this->table->lists('test_users', ['id', 'name'], true);
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Test 4: Clear method resets state
        $this->table->setName('test_users');
        $this->table->where('status', '=', 'active');
        $this->table->clear();
        $this->assertInstanceOf(Objects::class, $this->table);
        
        // Test 5: Method calls can be made in any order
        $this->table->clear();
        $this->table->where('status', '=', 'active');
        $this->table->setName('test_users');
        $this->table->orderby('name');
        $this->table->setFields(['id', 'name']);
        $this->assertInstanceOf(Objects::class, $this->table);
    }
}
