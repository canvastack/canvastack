# 📖 INSPECTOR USAGE GUIDE

**Version**: 1.0.0  
**Target Audience**: Developers, QA Engineers, DevOps  
**Prerequisites**: Basic PHP and Laravel knowledge

---

## 🚀 **QUICK START**

### **Step 1: Enable Inspector**
```php
// .env file
CANVASTACK_INSPECTOR_ENABLED=true
APP_ENV=local

// OR in config/canvastack.php
'datatables' => [
    'inspector' => [
        'enabled' => true,
    ]
]
```

### **Step 2: Basic Usage**
```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\Inspector;

// RECOMMENDED: Clean single-line usage (automatically extracts all context)
Inspector::inspect($this);

// LEGACY: Manual array construction (still supported but not recommended)
Inspector::inspect([
    'table_name' => 'users',
    'model_type' => 'User',
    'columns' => ['id', 'name', 'email'],
    'filters' => ['status' => 'active'],
    // ... other context data
]);
```

### **Step 3: View Results**
```bash
# Check generated files
ls storage/app/datatable-inspector/

# View latest diagnostic file
cat storage/app/datatable-inspector/users_*.json
```

---

## 🔧 **DETAILED CONFIGURATION**

### **Environment Variables**
```bash
# Enable/disable Inspector
CANVASTACK_INSPECTOR_ENABLED=true

# Set storage path (relative to storage/app)
CANVASTACK_INSPECTOR_STORAGE_PATH=datatable-inspector

# Set maximum files to keep
CANVASTACK_INSPECTOR_MAX_FILES=100

# Set cleanup days
CANVASTACK_INSPECTOR_CLEANUP_DAYS=7

# Set maximum file size (bytes)
CANVASTACK_INSPECTOR_MAX_FILE_SIZE=10485760
```

### **Configuration File**
```php
// config/canvastack.php
'datatables' => [
    'inspector' => [
        'enabled' => env('CANVASTACK_INSPECTOR_ENABLED', false),
        'mode' => env('CANVASTACK_INSPECTOR_MODE', 'file'),
        'storage_path' => env('CANVASTACK_INSPECTOR_STORAGE_PATH', 'datatable-inspector'),
        'max_files' => env('CANVASTACK_INSPECTOR_MAX_FILES', 100),
        'cleanup_days' => env('CANVASTACK_INSPECTOR_CLEANUP_DAYS', 7),
        'max_file_size' => env('CANVASTACK_INSPECTOR_MAX_FILE_SIZE', 10485760),
        'include_trace' => true,
        'include_request_data' => true,
        'exclude_sensitive' => true,
        'format_json' => true,
        'compress_old_files' => false,
    ]
]
```

---

## 📊 **USAGE PATTERNS**

### **1. Clean Diagnostic Logging (RECOMMENDED)**
```php
// Single line - automatically extracts all relevant context from $this
Inspector::inspect($this);
```

**What gets captured automatically**:
- Object class information
- All public properties
- Common datatable properties (table_name, model_type, etc.)
- Method results from common getters
- Global data object if available
- Request information
- Performance metrics

### **2. Legacy Diagnostic Logging (SUPPORTED)**
```php
// Manual array construction - verbose but explicit
Inspector::inspect([
    'table_name' => $tableName,
    'model_type' => $modelType,
    'model_source' => $modelSource,
    'columns' => [
        'lists' => $columnLists,
        'blacklist' => $blacklist,
        'format' => $formatData,
        'relations' => $relations,
        'orderby' => $orderBy,
        'clickable' => $clickableColumns,
        'actions' => $actionList,
        'removed' => $removedPrivileges,
    ],
    'joins' => [
        'foreign_keys' => $foreignKeys,
        'selected' => $selectedFields,
    ],
    'filters' => [
        'where' => $whereConditions,
        'applied' => $appliedFilters,
        'raw_params' => request()->all(),
    ],
    'paging' => [
        'start' => $start,
        'length' => $length,
        'total' => $total,
    ],
    'row' => [
        'attributes' => $rowAttributes,
        'urlTarget' => $urlTarget,
    ],
]);
```

### **2. Quick Debug Dumps**
```php
// Quick dump for immediate debugging
Inspector::dump($debugData, 'debug_point_1');

// Dump with context
Inspector::dump([
    'query_result' => $queryResult,
    'processing_time' => microtime(true) - $startTime,
    'memory_usage' => memory_get_usage(true),
], 'performance_check');
```

### **3. Conditional Logging**
```php
// Only log in specific conditions (clean approach)
if (request()->has('debug') || app()->environment('local')) {
    Inspector::inspect($this);
}

// Log only for specific tables (assuming $this has table_name property)
if (in_array($this->table_name ?? '', ['users', 'orders', 'products'])) {
    Inspector::inspect($this);
}

// Legacy conditional logging
if (request()->has('debug')) {
    Inspector::inspect($contextData);
}
```

### **4. Performance Monitoring**
```php
// Clean approach - performance metrics captured automatically
Inspector::inspect($this);

// Legacy approach - manual performance tracking
$startTime = microtime(true);
$startMemory = memory_get_usage(true);

// ... datatable processing ...

Inspector::inspect([
    'table_name' => $tableName,
    'performance' => [
        'execution_time' => microtime(true) - $startTime,
        'memory_used' => memory_get_usage(true) - $startMemory,
        'peak_memory' => memory_get_peak_usage(true),
    ],
    // ... other context data
]);
```

---

## 📁 **FILE ORGANIZATION**

### **File Naming Convention**
```
{table_name}_{route_name}_{timestamp}_{unique_id}.json

Examples:
users_admin.users.index_20241230_143022_abc123.json
products_api.products.list_20241230_143045_def456.json
orders_dashboard.orders_20241230_143101_ghi789.json
```

### **Directory Structure**
```
storage/app/datatable-inspector/
├── users_admin.users.index_20241230_143022_abc123.json
├── products_api.products.list_20241230_143045_def456.json
├── orders_dashboard.orders_20241230_143101_ghi789.json
└── quick-dumps/
    ├── dump_20241230_143200_xyz001.json
    └── dump_20241230_143215_xyz002.json
```

### **File Content Structure**
```json
{
    "meta": {
        "timestamp": "2024-12-30T14:30:22+00:00",
        "inspector_version": "1.0.0",
        "capture_id": "capture_676c8e5e2a1b47.12345678",
        "php_version": "8.2.12",
        "memory_usage": 12582912,
        "memory_peak": 15728640
    },
    "request": {
        "included": true,
        "method": "GET",
        "url": "https://example.com/admin/users",
        "path": "admin/users",
        "query": {...},
        "headers": {...},
        "route": {...},
        "user": {...},
        "session": {...}
    },
    "datatable": {
        "table_name": "users",
        "model_type": "User",
        "columns": {...},
        "joins": {...},
        "filters": {...},
        "paging": {...},
        "enriched": {...}
    },
    "performance": {...},
    "environment": {...},
    "trace": [...]
}
```

---

## 🔍 **ANALYZING DIAGNOSTIC DATA**

### **1. Manual Analysis**
```bash
# View latest file
ls -la storage/app/datatable-inspector/ | head -5

# Pretty print JSON
cat storage/app/datatable-inspector/users_*.json | jq '.'

# Extract specific data
cat storage/app/datatable-inspector/users_*.json | jq '.datatable.columns'

# Search for patterns
grep -r "error" storage/app/datatable-inspector/
```

### **2. Programmatic Analysis**
```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Storage\FileManager;

// Get recent files
$files = FileManager::getFiles('*.json', 10);

// Read and analyze
foreach ($files as $fileInfo) {
    $data = FileManager::read($fileInfo['path']);
    
    // Analyze performance
    $executionTime = $data['performance']['execution_time'] ?? 0;
    if ($executionTime > 1.0) {
        echo "Slow operation detected: {$fileInfo['name']}\n";
    }
    
    // Analyze memory usage
    $memoryUsage = $data['meta']['memory_usage'] ?? 0;
    if ($memoryUsage > 50 * 1024 * 1024) { // 50MB
        echo "High memory usage: {$fileInfo['name']}\n";
    }
}
```

### **3. Statistical Analysis**
```php
// Get storage statistics
$stats = FileManager::getStats();
echo "Total files: {$stats['total_files']}\n";
echo "Total size: {$stats['total_size_human']}\n";
echo "Oldest file: {$stats['oldest_file']}\n";
echo "Newest file: {$stats['newest_file']}\n";
```

---

## 🧪 **TESTING INTEGRATION**

### **1. Test Data Generation**
```php
// In your test setup
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Testing\ReplaySupport;

class DatatableTest extends TestCase
{
    public function testDatatableScenarios()
    {
        $scenarios = ReplaySupport::getScenarios();
        
        foreach ($scenarios as $scenario) {
            $this->runDatatableTest($scenario);
        }
    }
    
    private function runDatatableTest($scenario)
    {
        // Use scenario data to test datatable
        $result = $this->datatable->process($scenario['datatable']);
        
        // Assert expected behavior
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('data', $result);
    }
}
```

### **2. Scenario Validation**
```php
// Validate captured scenarios
$scenarios = ReplaySupport::getScenarios();
foreach ($scenarios as $scenario) {
    $validation = ReplaySupport::validateScenario($scenario);
    if (!$validation['valid']) {
        echo "Invalid scenario: {$validation['error']}\n";
    }
}
```

---

## 🛠️ **MAINTENANCE TASKS**

### **1. Manual Cleanup**
```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\Inspector;

// Clean up files older than 7 days
$cleanedCount = Inspector::cleanup(7);
echo "Cleaned up {$cleanedCount} files\n";

// Clean up files older than 1 day
$cleanedCount = Inspector::cleanup(1);
echo "Cleaned up {$cleanedCount} files\n";
```

### **2. Automated Cleanup**
```php
// In a scheduled command or cron job
class CleanupInspectorFiles extends Command
{
    public function handle()
    {
        $cleanedCount = Inspector::cleanup();
        $this->info("Cleaned up {$cleanedCount} inspector files");
    }
}
```

### **3. Storage Monitoring**
```bash
# Monitor storage usage
du -sh storage/app/datatable-inspector/

# Count files
find storage/app/datatable-inspector/ -name "*.json" | wc -l

# Find large files
find storage/app/datatable-inspector/ -name "*.json" -size +1M
```

---

## 🚨 **TROUBLESHOOTING**

### **Common Issues**

#### **Inspector Not Working**
```php
// Check status
$status = Inspector::status();
var_dump($status);

// Common fixes:
// 1. Check environment: APP_ENV=local
// 2. Check configuration: CANVASTACK_INSPECTOR_ENABLED=true
// 3. Check storage permissions: chmod 775 storage/app/
```

#### **Files Not Generated**
```bash
# Check directory permissions
ls -la storage/app/
mkdir -p storage/app/datatable-inspector
chmod 775 storage/app/datatable-inspector

# Check disk space
df -h

# Check PHP error logs
tail -f storage/logs/laravel.log
```

#### **Large File Sizes**
```php
// Reduce data capture
Inspector::inspect([
    'table_name' => $tableName,
    // Only include essential data
    'columns' => ['lists' => $columnLists],
    'filters' => ['applied' => $appliedFilters],
]);

// Or use summarized data
$summarizedData = JsonFormatter::summarize($largeData, 2, 5);
Inspector::inspect($summarizedData);
```

---

## 📈 **BEST PRACTICES**

### **1. Performance Optimization**
- Only enable Inspector in development/testing environments
- Use conditional logging for specific scenarios
- Regularly clean up old files
- Monitor storage usage

### **2. Data Security**
- Keep sensitive data exclusion enabled
- Regularly review captured data
- Use appropriate file permissions
- Clean up files before deployment

### **3. Development Workflow**
- Use Inspector for debugging complex issues
- Capture scenarios for test data generation
- Monitor performance during development
- Document findings and solutions

### **4. Team Collaboration**
- Share diagnostic files for issue reproduction
- Use consistent naming conventions
- Document Inspector usage in team guidelines
- Regular cleanup and maintenance

---

**Next Steps**: Review the **API Reference** for detailed method documentation and advanced usage patterns.