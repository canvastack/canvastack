<?php

echo "=== QUICK AUTH TEST ===\n\n";

require_once __DIR__ . '/../Security/FormAuthorizationService.php';

use Canvastack\Canvastack\Library\Components\Form\Security\FormAuthorizationService;

// Mock functions
if (!function_exists('app')) {
    function app($class = null) {
        return new MockModel();
    }
}

if (!function_exists('auth')) {
    function auth() {
        return new MockAuth();
    }
}

if (!function_exists('class_basename')) {
    function class_basename($class) {
        return basename(str_replace('\\', '/', $class));
    }
}

if (!class_exists('Log')) {
    class Log {
        public static function warning($message, $context = []) {}
    }
}

class MockAuth {
    public function user() {
        return new MockUser();
    }
}

class MockUser {
    public $id = 1;
    
    public function hasRole($role) {
        return false;
    }
    
    public function can($permission) {
        return false;
    }
}

class MockModel {
    public $id;
    public $user_id;
    
    public function getKeyName() {
        return 'id';
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
        if ($this->value > 0 && $this->value <= 100) {
            $this->model->id = $this->value;
            $this->model->user_id = 2; // Different from user ID
            return $this->model;
        }
        return null; // Record not found
    }
}

echo "Testing authorization scenarios...\n\n";

// Test 1: Record exists, user doesn't own it
echo "1. Record exists, user doesn't own it:\n";
$result1 = FormAuthorizationService::canAccessRecord('MockModel', 50, 'update');
echo "   Result: " . ($result1 ? "ALLOWED" : "DENIED") . "\n";

// Test 2: Record doesn't exist
echo "\n2. Record doesn't exist:\n";
$result2 = FormAuthorizationService::canAccessRecord('MockModel', 999, 'update');
echo "   Result: " . ($result2 ? "ALLOWED" : "DENIED") . "\n";

// Test 3: Invalid model class
echo "\n3. Invalid model class:\n";
$result3 = FormAuthorizationService::canAccessRecord('NonExistentModel', 50, 'update');
echo "   Result: " . ($result3 ? "ALLOWED" : "DENIED") . "\n";

echo "\n=== QUICK AUTH TEST COMPLETE ===\n";