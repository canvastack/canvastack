# StateManager Integration in TableBuilder

## 📦 Location

- **StateManager Class**: `packages/canvastack/canvastack/src/Components/Table/State/StateManager.php`
- **TableBuilder Integration**: `packages/canvastack/canvastack/src/Components/Table/TableBuilder.php`
- **Tests**: `packages/canvastack/canvastack/tests/Unit/Components/Table/TableBuilderStateIntegrationTest.php`

## 🎯 Overview

The StateManager provides state management for TableBuilder, enabling:
- **State Tracking**: Save and retrieve configuration state
- **State History**: Track all state changes for debugging
- **Configuration Isolation**: Prevent config bleeding between tabs
- **Clearable Variables**: Manage clearable configuration variables

## 🔧 Integration Points

### 1. StateManager Property

TableBuilder now includes a StateManager instance:

```php
protected StateManager $stateManager;
```

Initialized in constructor:

```php
public function __construct(
    QueryOptimizer $queryOptimizer,
    FilterBuilder $filterBuilder,
    SchemaInspector $schemaInspector,
    ColumnValidator $columnValidator
) {
    // ... other initialization
    $this->stateManager = new StateManager();
}
```

### 2. clearVar() Method

Enhanced to integrate with StateManager:

```php
public function clearVar(string $name): self
{
    // Clear from StateManager first
    $this->stateManager->clearVar($name);
    
    // Then clear from properties
    match ($name) {
        'merged_columns' => $this->mergedColumns = [],
        'fixed_columns' => $this->fixedLeft = null,
        // ... other cases
    };
    
    return $this;
}
```

**Supported variable names**:
- `merged_columns` - Clear merged columns
- `fixed_columns` - Clear fixed columns
- `hidden_columns` - Clear hidden columns
- `formats` - Clear column formats
- `conditions` - Clear column conditions
- `alignments` - Clear column alignments
- `filters` - Clear filter groups

### 3. clearOnLoad() Method

Enhanced to clear all clearable configuration:

```php
public function clearOnLoad(): self
{
    // Reset display limit
    $this->displayLimit = 10;
    
    // Clear all clearable configuration variables via StateManager
    $this->stateManager->clearClearableVars();
    
    // Reset clearable properties
    $this->mergedColumns = [];
    $this->fixedLeft = null;
    $this->fixedRight = null;
    $this->hiddenColumns = [];
    $this->formats = [];
    $this->columnConditions = [];
    $this->columnAlignments = [];
    $this->filterGroups = [];
    
    // Also clear tab configuration if using tabs
    if ($this->tabManager->hasTabs()) {
        $this->tabManager->clearConfig();
    }

    return $this;
}
```

### 4. clearFixedColumns() Method

Enhanced to integrate with StateManager:

```php
public function clearFixedColumns(): self
{
    // Clear from StateManager
    $this->stateManager->clearVar('fixed_columns');
    
    // Reset properties
    $this->fixedLeft = null;
    $this->fixedRight = null;

    return $this;
}
```

### 5. captureCurrentConfig() Method

Enhanced to save configuration snapshots:

```php
protected function captureCurrentConfig(): array
{
    $config = [
        'connection' => $this->connection,
        'sortable' => $this->sortableColumns,
        'searchable' => $this->searchableColumns,
        // ... other config
    ];
    
    // Save configuration snapshot to StateManager
    $this->stateManager->saveState('captured_config', $config);
    
    return $config;
}
```

### 6. getStateManager() Method

Provides access to StateManager for advanced use cases:

```php
public function getStateManager(): StateManager
{
    return $this->stateManager;
}
```

## 📝 Usage Examples

### Example 1: Clear Specific Variable

```php
// Set merged columns
$table->mergeColumns('Merged Header', ['col1', 'col2', 'col3']);

// Later, clear merged columns
$table->clearVar('merged_columns');
```

### Example 2: Clear All Configuration

```php
// Set various configurations
$table->fixedColumns(2, 1);
$table->mergeColumns('Merged', ['col1', 'col2']);
$table->setHiddenColumns(['col3']);

// Clear all clearable configuration
$table->clearOnLoad();
```

### Example 3: Clear Fixed Columns

```php
// Set fixed columns
$table->fixedColumns(5);

// Later, clear fixed columns
$table->clearFixedColumns();
```

### Example 4: Configuration Isolation Between Tabs

```php
// Tab 1 configuration
$table->openTab('Summary');
$table->fixedColumns(2, 1);
$table->mergeColumns('Merged', ['col1', 'col2']);
$table->lists('table1', ['id', 'name'], false);
$table->closeTab();

// Clear configuration to prevent bleeding
$table->clearOnLoad();

// Tab 2 configuration (isolated)
$table->openTab('Detail');
$table->fixedColumns(3, 0); // Different config
$table->lists('table2', ['id', 'email'], false);
$table->closeTab();
```

### Example 5: Access State History for Debugging

```php
$stateManager = $table->getStateManager();

// Perform operations
$table->fixedColumns(2, 1);
$table->clearFixedColumns();

// Get state history
$history = $stateManager->getStateHistory();

foreach ($history as $change) {
    echo "Key: {$change['key']}\n";
    echo "Old: " . json_encode($change['old']) . "\n";
    echo "New: " . json_encode($change['new']) . "\n";
    echo "Timestamp: {$change['timestamp']}\n";
}
```

## 🎭 Common Patterns

### Pattern 1: Multi-Tab with Configuration Isolation

```php
// Keren Pro Controller Example
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    
    // Tab 1: Summary
    $table->openTab('Summary');
    $table->fixedColumns(2);
    $table->mergeColumns('Total', ['col1', 'col2']);
    $table->lists('summary_table', ['id', 'name'], false);
    $table->closeTab();
    
    // Clear to prevent config bleeding
    $table->clearOnLoad();
    
    // Tab 2: Detail (different config)
    $table->openTab('Detail');
    $table->fixedColumns(3);
    $table->lists('detail_table', ['id', 'email'], false);
    $table->closeTab();
    
    return view('keren-pro.index', ['table' => $table]);
}
```

### Pattern 2: Conditional Configuration Clearing

```php
// Clear specific variables based on conditions
if ($needsReset) {
    $table->clearVar('merged_columns');
    $table->clearVar('fixed_columns');
} else {
    // Keep existing configuration
}
```

### Pattern 3: State Debugging

```php
// Enable state tracking for debugging
$stateManager = $table->getStateManager();

// Perform operations
$table->fixedColumns(2, 1);
$table->mergeColumns('Merged', ['col1', 'col2']);

// Check current state
$allState = $stateManager->getAllState();
Log::debug('Current state:', $allState);

// Check state history
$history = $stateManager->getStateHistory();
Log::debug('State history:', $history);
```

## 🧪 Testing

### Unit Tests

```php
public function test_clear_var_clears_from_state_manager(): void
{
    $stateManager = $this->table->getStateManager();
    
    // Save some state
    $stateManager->saveState('merged_columns', ['col1', 'col2']);
    $this->assertTrue($stateManager->hasState('merged_columns'));
    
    // Clear via TableBuilder
    $this->table->clearVar('merged_columns');
    
    // Verify cleared from StateManager
    $this->assertFalse($stateManager->hasState('merged_columns'));
}

public function test_clear_on_load_clears_clearable_vars(): void
{
    $stateManager = $this->table->getStateManager();
    
    // Save some clearable state
    $stateManager->saveState('merged_columns', ['col1', 'col2']);
    $stateManager->saveState('fixed_columns', ['left' => 2]);
    
    // Clear via clearOnLoad
    $this->table->clearOnLoad();
    
    // Verify all clearable vars are cleared
    $this->assertFalse($stateManager->hasState('merged_columns'));
    $this->assertFalse($stateManager->hasState('fixed_columns'));
}
```

### Integration Tests

```php
public function test_configuration_isolation_between_tabs(): void
{
    $stateManager = $this->table->getStateManager();
    
    // Tab 1 config
    $this->table->fixedColumns(2, 1);
    $this->table->openTab('Tab 1');
    $this->table->lists('users', ['id', 'name'], false);
    $config1 = $stateManager->getState('captured_config');
    
    // Clear and set Tab 2 config
    $this->table->clearOnLoad();
    $this->table->fixedColumns(3, 0);
    $this->table->openTab('Tab 2');
    $this->table->lists('permissions', ['id', 'name'], false);
    $config2 = $stateManager->getState('captured_config');
    
    // Verify configs are different
    $this->assertNotEquals($config1['fixedColumns'], $config2['fixedColumns']);
}
```

## 💡 Tips & Best Practices

1. **Always Clear Between Tabs**: Use `clearOnLoad()` between tabs to prevent configuration bleeding
2. **Use Specific Clears**: Use `clearVar()` for specific variables instead of `clearOnLoad()` when you only need to reset certain config
3. **Track State for Debugging**: Use `getStateHistory()` to debug configuration issues
4. **Test Configuration Isolation**: Always test that tab configurations don't bleed into each other

## 🔗 Related Components

- [TabManager](./tab-manager.md) - Tab system integration
- [TableBuilder](./table-builder.md) - Main table component
- [SessionManager](./session-manager.md) - Session persistence (coming soon)

## 📚 Resources

- [StateManager API Reference](../api/state-manager.md)
- [TableBuilder API Reference](../api/table-builder.md)
- [Task 1.2.2 Specification](../../.kiro/specs/tablebuilder-origin-parity/tasks.md)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Completed  
**Task**: 1.2.2 Integrate StateManager into TableBuilder
