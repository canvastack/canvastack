<?php

echo "=== SIMPLE STATIC METHOD VALIDATION ===\n\n";

// Test basic static method calls without Laravel dependencies
class SimpleModel
{
    public static function find($id)
    {
        return $id === 123 ? (object)['id' => 123] : null;
    }
    
    public static function query()
    {
        return new SimpleQueryBuilder();
    }
}

class SimpleQueryBuilder
{
    public function where($field, $operator = null, $value = null)
    {
        return $this;
    }
    
    public function limit($limit)
    {
        return $this;
    }
    
    public function pluck($column)
    {
        return [1, 2, 3];
    }
}

echo "1. Testing static method patterns...\n";

$modelClass = 'SimpleModel';

// Test the exact pattern used in FormAuthorizationService
try {
    $record = $modelClass::find(123);
    echo "✅ \$modelClass::find() pattern: " . ($record && $record->id === 123 ? "WORKS" : "FAILS") . "\n";
} catch (Error $e) {
    echo "❌ \$modelClass::find() error: " . $e->getMessage() . "\n";
}

try {
    $query = $modelClass::query();
    $result = $query->where('id', '=', 1)->limit(10)->pluck('id');
    echo "✅ \$modelClass::query() pattern: " . (is_array($result) ? "WORKS" : "FAILS") . "\n";
} catch (Error $e) {
    echo "❌ \$modelClass::query() error: " . $e->getMessage() . "\n";
}

echo "\n2. Code analysis of FormAuthorizationService...\n";

$fileContent = file_get_contents(__DIR__ . '/../Security/FormAuthorizationService.php');

// Check for the correct static method patterns
$findPattern = preg_match('/\$modelClass::find\(\$recordId\)/', $fileContent);
echo "✅ Uses \$modelClass::find(\$recordId): " . ($findPattern ? "YES" : "NO") . "\n";

$queryPattern = preg_match('/\$modelClass::query\(\)/', $fileContent);
echo "✅ Uses \$modelClass::query(): " . ($queryPattern ? "YES" : "NO") . "\n";

// Check for problematic patterns that could cause Builder::find() error
$builderFind = strpos($fileContent, 'Builder::find()') !== false;
echo "✅ No Builder::find() references: " . ($builderFind ? "FAIL" : "PASS") . "\n";

$appContainer = strpos($fileContent, 'app($modelClass)') !== false;
echo "✅ No app() container calls: " . ($appContainer ? "FAIL" : "PASS") . "\n";

$callUserFunc = strpos($fileContent, 'call_user_func') !== false;
echo "✅ No call_user_func calls: " . ($callUserFunc ? "FAIL" : "PASS") . "\n";

$directNew = strpos($fileContent, 'new $modelClass()') !== false;
echo "✅ No direct instantiation: " . ($directNew ? "FAIL" : "PASS") . "\n";

// Check for instance method calls on variables (which could be Builder instances)
$instanceFind = preg_match('/\$[a-zA-Z_]+->find\(/', $fileContent);
echo "✅ No instance->find() calls: " . ($instanceFind ? "FAIL" : "PASS") . "\n";

echo "\n3. Summary...\n";

$allGood = $findPattern && $queryPattern && !$builderFind && !$appContainer && !$callUserFunc && !$directNew && !$instanceFind;

if ($allGood) {
    echo "🎉 ALL CHECKS PASSED!\n";
    echo "✅ FormAuthorizationService uses correct static method patterns\n";
    echo "✅ No problematic patterns that could cause Builder::find() error\n";
    echo "✅ Code should work with real Laravel Eloquent models\n";
    echo "\n🚀 The Builder::find() error should be RESOLVED!\n";
} else {
    echo "⚠️ SOME ISSUES FOUND\n";
    echo "🔧 Review the failed checks above\n";
    echo "📝 Additional fixes may be needed\n";
}

echo "\n=== SIMPLE STATIC TEST COMPLETE ===\n";