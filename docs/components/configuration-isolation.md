# Configuration Isolation Between Tabs

## Overview

This document describes the configuration isolation mechanism implemented to prevent configuration bleeding between tabs in the TableBuilder component.

## Problem Statement

When using multiple tabs in TableBuilder, configuration from one tab could "bleed" into another tab if not properly isolated. For example:

```php
// Tab 1: Configure with merged columns
$table->openTab('Summary');
$table->mergeColumns('Full Name', ['first_name', 'last_name'], 'top');
$table->lists('users', ['id', 'first_name', 'last_name'], false);
$table->closeTab();

// Tab 2: Should NOT have merged columns
$table->openTab('Detail');
$table->lists('users', ['id', 'email', 'phone'], false);
$table->closeTab();

// PROBLEM: Without isolation, Tab 2 would inherit the merged columns from Tab 1
```

## Solution

### Deferred Configuration Reset

The solution implements a **deferred reset** mechanism:

1. When `closeTab()` is called, it marks that a config reset is needed
2. When `openTab()` is called for the next tab, it performs the reset
3. If no more tabs are opened, the last tab's configuration is preserved for rendering

This approach ensures:
- ✅ Configuration doesn't bleed between tabs
- ✅ Last tab's configuration is preserved for rendering
- ✅ Backward compatibility with existing code

### Implementation

**File**: `packages/canvastack/canvastack/src/Components/Table/TableBuilder.php`

#### 1. New Property: `$needsConfigReset`

```php
/**
 * Flag to indicate if config needs to be reset before opening next tab.
 * This implements deferred reset to prevent config bleeding while preserving
 * config for the last tab.
 *
 * @var bool
 */
protected bool $needsConfigReset = false;
```

#### 2. Enhanced `closeTab()` Method

```php
public function closeTab(): self
{
    $this->tabManager->closeTab();
    
    // Mark that we need to reset config before the next tab opens
    // This is a deferred reset - it will happen in openTab() if called
    $this->needsConfigReset = true;
    
    return $this;
}
```

#### 3. Enhanced `openTab()` Method

```php
public function openTab(string $name): self
{
    // If a previous tab was closed, reset config before opening new tab
    if ($this->needsConfigReset) {
        $this->resetConfigForNextTab();
        $this->needsConfigReset = false;
    }
    
    $this->tabManager->openTab($name);
    
    return $this;
}
```

#### 4. `resetConfigForNextTab()` Method

```php
protected function resetConfigForNextTab(): void
{
    // Clear all clearable configuration variables via StateManager
    $this->stateManager->clearClearableVars();
    
    // Reset clearable properties to their default values
    // These are the properties that should NOT bleed between tabs
    
    // Column configuration
    $this->mergedColumns = [];
    $this->fixedLeft = null;
    $this->fixedRight = null;
    $this->hiddenColumns = [];
    $this->columnAlignments = [];
    $this->columnColors = [];
    $this->columnWidths = [];
    
    // Formatting and conditions
    $this->formats = [];
    $this->columnConditions = [];
    $this->formulas = [];
    
    // Filters and search
    $this->filterGroups = [];
    $this->sortableColumns = null;
    $this->searchableColumns = null;
    $this->clickableColumns = null;
    
    // Actions
    $this->actions = [];
    $this->removedButtons = [];
    
    // Relations
    $this->relations = [];
    $this->fieldReplacements = [];
    $this->eagerLoad = [];
    
    // Display options
    $this->displayLimit = 10;
    
    // Performance settings
    $this->cacheTime = null;
    $this->useCache = false;
    
    // NOTE: We do NOT reset these properties as they should persist:
    // - $this->context (admin/public context)
    // - $this->model (base model for the controller)
    // - $this->connection (database connection)
    // - $this->permission (RBAC permission)
    // - $this->tableName (can be overridden per tab via lists())
    
    // Log the reset for debugging
    $this->stateManager->saveState('config_reset', [
        'timestamp' => microtime(true),
        'reason' => 'openTab() called after closeTab() - preventing config bleeding',
    ]);
}
```

## What Gets Reset

The following configuration properties are automatically reset when `closeTab()` is called:

### Column Configuration
- Merged columns
- Fixed columns (left and right)
- Hidden columns
- Column alignments
- Column colors
- Column widths

### Formatting and Conditions
- Format rules
- Column conditions
- Formula columns

### Filters and Search
- Filter groups
- Sortable columns
- Searchable columns
- Clickable columns

### Actions
- Custom actions
- Removed buttons

### Relations
- Relationship configurations
- Field replacements
- Eager load relationships

### Display Options
- Display limit (reset to 10)

### Performance Settings
- Cache time
- Cache enabled flag

## What Persists

The following properties are NOT reset and persist across tabs:

- **Context**: Admin or public rendering context
- **Model**: Base Eloquent model
- **Connection**: Database connection name
- **Permission**: RBAC permission string
- **Table Name**: Can be overridden per tab via `lists()`

## Usage Example

```php
use Canvastack\Canvastack\Components\Table\TableBuilder;

public function index(TableBuilder $table)
{
    $table->setContext('admin');
    $table->setModel(new User());
    
    // Tab 1: Summary with merged columns and fixed columns
    $table->openTab('Summary');
    $table->mergeColumns('Full Name', ['first_name', 'last_name'], 'top');
    $table->fixedColumns(2, 0);
    $table->displayRowsLimitOnLoad(50);
    $table->lists('users', ['id', 'first_name', 'last_name', 'email'], false);
    $table->closeTab(); // Automatically resets configuration
    
    // Tab 2: Detail with different configuration
    $table->openTab('Detail');
    // No merged columns, no fixed columns - clean state!
    $table->setCenterColumns(['status']);
    $table->displayRowsLimitOnLoad(25);
    $table->lists('users', ['id', 'email', 'phone', 'status'], false);
    $table->closeTab(); // Automatically resets configuration
    
    // Tab 3: Another clean state
    $table->openTab('Activity');
    $table->sortable(['created_at']);
    $table->searchable(['action', 'description']);
    $table->lists('user_activities', ['id', 'action', 'description', 'created_at'], false);
    $table->closeTab();
    
    return view('users.index', ['table' => $table]);
}
```

## Testing

Comprehensive tests have been implemented to verify configuration isolation:

**File**: `packages/canvastack/canvastack/tests/Unit/Components/Table/Tab/ConfigurationIsolationTest.php`

### Test Coverage

1. ✅ Merged columns don't bleed between tabs
2. ✅ Fixed columns don't bleed between tabs
3. ✅ Column alignments don't bleed between tabs
4. ✅ Column colors don't bleed between tabs
5. ✅ Display limit doesn't bleed between tabs
6. ✅ Actions don't bleed between tabs
7. ✅ Eager load relationships don't bleed between tabs
8. ✅ Sortable columns don't bleed between tabs
9. ✅ Searchable columns don't bleed between tabs
10. ✅ Comprehensive configuration isolation across multiple tabs
11. ✅ Context and model persist across tabs (as expected)
12. ✅ State manager tracks configuration resets

### Running Tests

```bash
cd packages/canvastack/canvastack
./vendor/bin/phpunit tests/Unit/Components/Table/Tab/ConfigurationIsolationTest.php
```

## Benefits

1. **Predictable Behavior**: Each tab starts with a clean state, making behavior predictable
2. **No Side Effects**: Configuration in one tab doesn't affect other tabs
3. **Easier Debugging**: Issues are isolated to individual tabs
4. **Better Maintainability**: Developers don't need to manually reset configuration
5. **Automatic**: No manual intervention required - happens automatically on `closeTab()`

## State Tracking

The StateManager tracks all configuration resets for debugging purposes:

```php
$stateManager = $table->getStateManager();
$history = $stateManager->getStateHistory();

// Find config reset entries
$resetEntries = array_filter($history, function ($entry) {
    return $entry['key'] === 'config_reset';
});

foreach ($resetEntries as $entry) {
    echo "Reset at: " . $entry['new']['timestamp'] . "\n";
    echo "Reason: " . $entry['new']['reason'] . "\n";
}
```

## Migration Guide

If you have existing code that relies on configuration bleeding between tabs, you'll need to explicitly set the configuration for each tab:

### Before (Relying on Config Bleeding)

```php
// ❌ BAD: Relying on config bleeding
$table->mergeColumns('Name', ['first_name', 'last_name'], 'top');

$table->openTab('Tab 1');
$table->lists('users', ['id', 'first_name', 'last_name'], false);
$table->closeTab();

$table->openTab('Tab 2');
// Expecting merged columns from Tab 1 - WON'T WORK!
$table->lists('users', ['id', 'first_name', 'last_name'], false);
$table->closeTab();
```

### After (Explicit Configuration)

```php
// ✅ GOOD: Explicit configuration per tab
$table->openTab('Tab 1');
$table->mergeColumns('Name', ['first_name', 'last_name'], 'top');
$table->lists('users', ['id', 'first_name', 'last_name'], false);
$table->closeTab();

$table->openTab('Tab 2');
$table->mergeColumns('Name', ['first_name', 'last_name'], 'top'); // Explicitly set again
$table->lists('users', ['id', 'first_name', 'last_name'], false);
$table->closeTab();
```

## Related Documentation

- [Tab System](./tab-system.md) - Complete tab system documentation
- [State Manager](./state-manager.md) - State management documentation
- [TableBuilder API](../api/table.md) - Complete TableBuilder API reference

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Implemented  
**Task**: Prevent config bleeding between tabs
