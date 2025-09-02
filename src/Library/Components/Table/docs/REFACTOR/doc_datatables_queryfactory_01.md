# Dokumentasi Function Mapping: QueryFactory Extraction (PR-2)

**Tanggal**: 2025-08-30  
**Action**: PR-2 - Ekstraksi Query Building Logic ke QueryFactory  
**Target**: Memindahkan logic query building dari orchestrator ke modul terpisah

## 1. IDENTIFIKASI FUNCTIONS TARGET

### 1.1 Query Building Logic yang Akan Diekstrak

#### **AREA 1: Relationship/Joins Logic**
**Location**: `Datatables.php` lines 163-183
```php
// CHECK RELATIONSHIP DATATABLES	
if (!empty($column_data[$table_name]['foreign_keys'])) {
    $fieldsets     = [];
    $joinFields    = ["{$table_name}.*"];
    foreach ($column_data[$table_name]['foreign_keys'] as $fkey1 => $fkey2) {
        $ftables    = explode('.', $fkey1);
        $model_data = $model_data->leftJoin($ftables[0], $fkey1, '=', $fkey2);
        $fieldsets[$ftables[0]] = canvastack_get_table_columns($ftables[0]);
    }
    
    foreach ($fieldsets as $fstname => $fieldRows) {
        foreach ($fieldRows as $fieldset) {
            if ('id' === $fieldset) {
                $joinFields[] = "{$fstname}.{$fieldset} as {$fstname}_{$fieldset}";
            } else {
                $joinFields[] = "{$fstname}.{$fieldset}";
            }
        }
    }
    $model_data = $model_data->select($joinFields);
}
```

#### **AREA 2: Where Conditions Logic**
**Location**: `Datatables.php` lines 190-212
```php
// Conditions [ Where ]
$model_condition  = [];
$where_conditions = [];
if (!empty($data->datatables->conditions[$table_name]['where'])) {
    foreach ($data->datatables->conditions[$table_name]['where'] as $conditional_where) {
        if (!is_array($conditional_where['value'])) {
            $where_conditions['o'][] = [$conditional_where['field_name'], $conditional_where['operator'], $conditional_where['value']];
        } else {
            $where_conditions['i'][$conditional_where['field_name']] = $conditional_where['value'];
        }
    }
    
    if (!empty($where_conditions['o'])) $model_condition = $model_data->where($where_conditions['o']);
    if (empty($model_condition))        $model_condition = $model_data;
    
    if (!empty($where_conditions['i'])) {
        foreach ($where_conditions['i'] as $if => $iv) {
            $model_condition = $model_condition->whereIn($if, $iv);
        }
    }
    
    $model = $model_condition;
}
```

#### **AREA 3: Filter Processing Logic**
**Location**: `Datatables.php` lines 214-274
```php
// Filter
$fstrings	= [];
$_ajax_url	= 'renderDataTables';

if (!empty($where_conditions)) {
    $model_filters = $model_condition;
} else {
    $model_filters = $model_data;
}

if (!empty($filters) && true == $filters) {
    foreach ($filters as $name => $value) {
        if ('filters'!== $name && '' !== $value) {
            if (
                $name !== $_ajax_url &&
                $name !== 'draw'     &&
                $name !== 'columns'  &&
                $name !== 'order'    &&
                $name !== 'start'    &&
                $name !== 'length'   &&
                $name !== 'search'   &&
                $name !== 'difta'    &&
                $name !== '_token'   &&
                $name !== '_'
            ) {
                if (!is_array($value)) {
                    $fstrings[]    = [$name => urldecode($value)];
                } else {
                    foreach ($value as $val) {
                        $fstrings[] = [$name => urldecode($val)];
                    }
                }
            }
        }
    }
}

if (!empty($fstrings)) {
    $filters = [];
    foreach ($fstrings as $fdata) {
        foreach ($fdata as $fkey => $fvalue) {
            $filters[$fkey][] = $fvalue;
        }
    }
    
    if (!empty($filters)) {
        $fconds = [];
        foreach ($filters as $fieldname => $rowdata) {
            foreach ($rowdata as $dataRow) {
                $fconds[$fieldname] = $dataRow;
            }
        }
        
        $model = $model_filters->where($fconds);
    }
    $limitTotal = count($model->get());
} else {
    $model      = $model_filters->where("{$table_name}.{$firstField}", '!=', null);
    $limitTotal = count($model_filters->get());
}
```

#### **AREA 4: Pagination & Limits Logic**
**Location**: `Datatables.php` lines 185-188, 276-281
```php
$limitTotal      = 0;
$limit           = [];
$limit['start']  = 0;
$limit['length'] = 10;

// ... (after filter processing)

$limit['total'] = intval($limitTotal);

if (!empty(request()->get('start')))  $limit['start']  = request()->get('start');
if (!empty(request()->get('length'))) $limit['length'] = request()->get('length');

$model->skip($limit['start'])->take($limit['length']);
```

#### **AREA 5: Order By Logic**
**Location**: `Datatables.php` lines 295-300
```php
if (!empty($order_by)) {
    $orderBy = $order_by;
    $datatables->order(function ($query) use($orderBy) {$query->orderBy($orderBy['column'], $orderBy['order']);});
} else {
    $orderBy = ['column' => $data->datatables->columns[$table_name]['lists'][0], 'order' => 'desc'];
}
```

---

## 2. RENCANA EKSTRAKSI

### 2.1 Target Structure

#### **Contract Interface**
**File**: `packages/canvastack/canvastack/src/Library/Components/Table/Craft/Datatables/Contracts/QueryFactoryInterface.php`
```php
<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts;

interface QueryFactoryInterface
{
    public function buildQuery($model_data, $data, $table_name, $filters = null, $order_by = null): array;
    public function applyJoins($model_data, $foreign_keys, $table_name): array;
    public function applyWhereConditions($model_data, $conditions): mixed;
    public function applyFilters($model, $filters, $table_name, $firstField): array;
    public function applyPagination($model, $start = null, $length = null): mixed;
    public function calculateTotals($model, $model_filters): int;
}
```

#### **Implementation Class**
**File**: `packages/canvastack/canvastack/src/Library/Components/Table/Craft/Datatables/Query/QueryFactory.php`
```php
<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\QueryFactoryInterface;

class QueryFactory implements QueryFactoryInterface
{
    // Implementation methods here
}
```

### 2.2 Function Mapping Plan

| Legacy Location | New Location | Method Name | Responsibility |
|----------------|--------------|-------------|----------------|
| Lines 163-183 | `QueryFactory::applyJoins()` | Join processing | Handle foreign keys & select fields |
| Lines 190-212 | `QueryFactory::applyWhereConditions()` | Where clauses | Process where conditions |
| Lines 214-274 | `QueryFactory::applyFilters()` | Filter processing | Handle request filters |
| Lines 185-188, 276-281 | `QueryFactory::applyPagination()` | Pagination | Handle start/length limits |
| Lines 295-300 | `QueryFactory::applyOrdering()` | Order by | Handle sorting logic |
| Combined | `QueryFactory::buildQuery()` | Main orchestrator | Coordinate all query building |

### 2.3 Orchestrator Integration

#### **BEFORE (Legacy)**
```php
// Lines 163-300+ in Datatables.php
// All query building logic inline
```

#### **AFTER (Refactored)**
```php
// In Datatables.php around line 163
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\QueryFactory;

// Replace lines 163-300+ with:
$queryResult = QueryFactory::buildQuery($model_data, $data, $table_name, $filters, $order_by);
$model = $queryResult['model'];
$limit = $queryResult['limit'];
$joinFields = $queryResult['joinFields'] ?? null;
```

---

## 3. BEHAVIOR PRESERVATION REQUIREMENTS

### 3.1 Critical Behaviors to Preserve

1. **Join Logic Quirks**:
   - ID fields aliased as `{table}_{field}` 
   - Other fields keep original names
   - `leftJoin` usage (not inner join)

2. **Where Conditions Logic**:
   - Array vs non-array value handling
   - `whereIn` for array values
   - Exact operator precedence

3. **Filter Processing**:
   - Reserved field exclusions (exact list)
   - URL decode behavior
   - Filter consolidation logic

4. **Pagination Defaults**:
   - Default start: 0, length: 10
   - Request parameter override behavior

5. **Count Calculations**:
   - `count($model->get())` vs `count($model_filters->get())`
   - Timing of count calculations

### 3.2 Edge Cases to Handle

1. **Empty Conditions**: Fallback to `$model_data`
2. **No Filters**: Default where clause with `!= null`
3. **Missing Order**: Default to first column desc
4. **Join Field Conflicts**: ID aliasing logic

---

## 4. TESTING STRATEGY

### 4.1 Unit Tests Required
- `QueryFactoryTest.php` in `tests/Unit/Components/Table/Query/`
- Test each method independently
- Mock dependencies appropriately

### 4.2 Integration Tests
- Test full `buildQuery()` method with real data
- Validate query result structure
- Compare SQL output with legacy

### 4.3 HybridCompare Validation
- Run on production routes
- Verify `no_diff` status
- Monitor query performance

---

## 5. IMPLEMENTATION PHASES

### Phase 1: Create Contract & Implementation
1. Create `QueryFactoryInterface`
2. Create `QueryFactory` class
3. Implement all methods with legacy behavior

### Phase 2: Wire Orchestrator
1. Import `QueryFactory` in `Datatables.php`
2. Replace inline logic with factory calls
3. Preserve all variable names and flow

### Phase 3: Testing & Validation
1. Run unit tests
2. Run integration tests  
3. Execute HybridCompare validation
4. Verify production routes

---

**Status**: ✅ **ANALYSIS COMPLETE**  
**Next Step**: Implement QueryFactory classes  
**Risk Level**: **MEDIUM** (complex query logic with many edge cases)