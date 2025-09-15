<?php

echo "=== AUTHORIZATION TEST ===\n\n";

require_once __DIR__ . '/../Security/FormAuthorizationService.php';

use Canvastack\Canvastack\Library\Components\Form\Security\FormAuthorizationService;

// Mock Laravel functions
if (!function_exists('app')) {
    function app($class = null) {
        return new MockEloquentModel();
    }
}

if (!function_exists('auth')) {
    function auth() {
        return new MockAuth();
    }
}

if (!function_exists('class_basename')) {
    function class_basename($class) {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

class MockAuth {
    public function user() {
        return new MockUser();
    }
}

if (!class_exists('Log')) {
    class Log {
        public static function warning($message, $context = []) {
            // Silent
        }
    }
}

class MockEloquentModel {
    public $id;
    public $user_id;
    protected $primaryKey = 'id';
    
    public function getKeyName() {
        return $this->primaryKey;
    }
    
    public function where($column, $value) {
        return new MockQueryBuilder($this, $column, $value);
    }
}

class MockQueryBuilder {
    private $model;
    private $column;
    private $value;
    
    public function __construct($model, $column, $value) {
        $this->model = $model;
        $this->column = $column;
        $this->value = $value;
    }
    
    public function first() {
        if ($this->column === 'id' && $this->value > 0 && $this->value <= 100) {
            $this->model->id = $this->value;
            $this->model->user_id = 1;
            return $this->model;
        }
        return null;
    }
}

class MockUser {
    public $id = 999; // Different from record user_id (1)
    
    public function hasRole($role) {
        return false; // Simulate user without special roles
    }
    
    public function can($permission) {
        return false; // Simulate user without special permissions
    }
}

echo "Testing authorization with fallback...\n\n";

$user = new MockUser();
$modelClass = 'MockEloquentModel';
$recordId = 50;

// Test view action
echo "1. Testing 'view' action...\n";
$canView = FormAuthorizationService::canAccessRecord($modelClass, $recordId, 'view');
echo "   Result: " . ($canView ? "ALLOWED" : "DENIED") . "\n";

// Test edit action
echo "\n2. Testing 'edit' action...\n";
$canEdit = FormAuthorizationService::canAccessRecord($modelClass, $recordId, 'edit');
echo "   Result: " . ($canEdit ? "ALLOWED" : "DENIED") . "\n";

// Test update action
echo "\n3. Testing 'update' action...\n";
$canUpdate = FormAuthorizationService::canAccessRecord($modelClass, $recordId, 'update');
echo "   Result: " . ($canUpdate ? "ALLOWED" : "DENIED") . "\n";

// Test delete action (should be denied)
echo "\n4. Testing 'delete' action...\n";
$canDelete = FormAuthorizationService::canAccessRecord($modelClass, $recordId, 'delete');
echo "   Result: " . ($canDelete ? "ALLOWED" : "DENIED") . "\n";

echo "\n=== AUTHORIZATION TEST COMPLETE ===\n";