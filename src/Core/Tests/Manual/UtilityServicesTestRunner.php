<?php

/**
 * Manual Test Runner untuk Utility Services
 * 
 * Test runner untuk Phase 3 utility services tanpa PHPUnit
 * 
 * @author dev@canvastack.com
 * @created 2024-12-19
 * @version 1.0
 */

// Include required files
require_once __DIR__ . '/../../Contracts/SessionManagerInterface.php';
require_once __DIR__ . '/../../Contracts/AssetManagerInterface.php';
require_once __DIR__ . '/../../Contracts/RoleHandlerInterface.php';
require_once __DIR__ . '/../../Services/SessionManagerService.php';
require_once __DIR__ . '/../../Services/AssetManagerService.php';
require_once __DIR__ . '/../../Services/RoleHandlerService.php';

use Canvastack\Canvastack\Core\Services\SessionManagerService;
use Canvastack\Canvastack\Core\Services\AssetManagerService;
use Canvastack\Canvastack\Core\Services\RoleHandlerService;
use Canvastack\Canvastack\Core\Contracts\SessionManagerInterface;
use Canvastack\Canvastack\Core\Contracts\AssetManagerInterface;
use Canvastack\Canvastack\Core\Contracts\RoleHandlerInterface;

class UtilityServicesTestRunner
{
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private array $results = [];

    public function runAllTests(): void
    {
        echo "🧪 PHASE 3 UTILITY SERVICES TEST RUNNER\n";
        echo "=====================================\n\n";

        $this->testServiceInstantiation();
        $this->testSessionManagerOperations();
        $this->testAssetManagerOperations();
        $this->testRoleHandlerOperations();
        $this->testServiceIntegration();
        $this->testErrorHandling();

        $this->printSummary();
    }

    private function testServiceInstantiation(): void
    {
        echo "📦 Testing Service Instantiation...\n";

        // Test SessionManager
        $this->runTest('SessionManager instantiation', function() {
            $service = new SessionManagerService();
            return $service instanceof SessionManagerInterface;
        });

        // Test AssetManager
        $this->runTest('AssetManager instantiation', function() {
            $service = new AssetManagerService();
            return $service instanceof AssetManagerInterface;
        });

        // Test RoleHandler
        $this->runTest('RoleHandler instantiation', function() {
            $service = new RoleHandlerService();
            return $service instanceof RoleHandlerInterface;
        });

        echo "\n";
    }

    private function testSessionManagerOperations(): void
    {
        echo "👤 Testing SessionManager Operations...\n";

        $sessionManager = new SessionManagerService();

        $this->runTest('Initial logged in state', function() use ($sessionManager) {
            return $sessionManager->isLoggedIn() === false;
        });

        $this->runTest('Get session data', function() use ($sessionManager) {
            $data = $sessionManager->getSession(true);
            return is_array($data);
        });

        $this->runTest('Get session roles', function() use ($sessionManager) {
            $roles = $sessionManager->getSessionRoles();
            return is_array($roles);
        });

        $this->runTest('Group check', function() use ($sessionManager) {
            return $sessionManager->groupCheck('admin') === false;
        });

        $this->runTest('Get current user ID', function() use ($sessionManager) {
            return $sessionManager->getCurrentUserId() === null;
        });

        $this->runTest('Session data with default', function() use ($sessionManager) {
            return $sessionManager->getSessionData('nonexistent', 'default') === 'default';
        });

        echo "\n";
    }

    private function testAssetManagerOperations(): void
    {
        echo "🎨 Testing AssetManager Operations...\n";

        $assetManager = new AssetManagerService();

        $this->runTest('Get script node', function() use ($assetManager) {
            return $assetManager->getScriptNode() === 'diyScriptNode::';
        });

        $this->runTest('Script registration check', function() use ($assetManager) {
            return $assetManager->isScriptRegistered('test.js', 'js') === false;
        });

        $this->runTest('Get JS scripts', function() use ($assetManager) {
            $scripts = $assetManager->getScripts('js');
            return is_array($scripts);
        });

        $this->runTest('Get CSS scripts', function() use ($assetManager) {
            $scripts = $assetManager->getScripts('css');
            return is_array($scripts);
        });

        $this->runTest('Clear scripts', function() use ($assetManager) {
            $assetManager->clearScripts();
            return empty($assetManager->getScripts('js')) && empty($assetManager->getScripts('css'));
        });

        $this->runTest('Get all scripts', function() use ($assetManager) {
            $allScripts = $assetManager->getAllScripts();
            return is_array($allScripts) && isset($allScripts['js']) && isset($allScripts['css']);
        });

        echo "\n";
    }

    private function testRoleHandlerOperations(): void
    {
        echo "🔐 Testing RoleHandler Operations...\n";

        $roleHandler = new RoleHandlerService();

        $this->runTest('Initial role alias', function() use ($roleHandler) {
            return $roleHandler->getRoleAlias() === ['admin'];
        });

        $this->runTest('Initial role info', function() use ($roleHandler) {
            return $roleHandler->getRoleInfo() === [];
        });

        $this->runTest('Set role alias', function() use ($roleHandler) {
            $roleHandler->setRoleAlias(['admin', 'user']);
            return $roleHandler->getRoleAlias() === ['admin', 'user'];
        });

        $this->runTest('Set role info', function() use ($roleHandler) {
            $roleHandler->setRoleInfo(['National', 'Regional']);
            return $roleHandler->getRoleInfo() === ['National', 'Regional'];
        });

        $this->runTest('Init handler', function() use ($roleHandler) {
            $roleHandler->initHandler();
            return $roleHandler->getRoleAlias() === ['admin', 'internal'] && 
                   $roleHandler->getRoleInfo() === ['National'];
        });

        $session = [
            'user_group' => 'admin',
            'group_alias' => 'National',
            'id' => 1,
            'username' => 'testuser'
        ];

        $this->runTest('Has role check', function() use ($roleHandler, $session) {
            return $roleHandler->hasRole('admin', $session) === true &&
                   $roleHandler->hasRole('user', $session) === false;
        });

        $this->runTest('Root check', function() use ($roleHandler, $session) {
            $rootSession = ['user_group' => 'root'];
            return $roleHandler->isRoot($session) === false &&
                   $roleHandler->isRoot($rootSession) === true;
        });

        $this->runTest('Can access check', function() use ($roleHandler, $session) {
            return $roleHandler->canAccess('resource', $session) === true;
        });

        $this->runTest('Get user permissions', function() use ($roleHandler, $session) {
            $permissions = $roleHandler->getUserPermissions($session);
            return is_array($permissions) && in_array('read', $permissions);
        });

        echo "\n";
    }

    private function testServiceIntegration(): void
    {
        echo "🔗 Testing Service Integration...\n";

        // Test AssetManager with mock template
        $mockTemplate = new class {
            public function js($script, $position, $asCode) {
                return "js:{$script}:{$position}:" . ($asCode ? 'code' : 'file');
            }
            
            public function css($script, $position) {
                return "css:{$script}:{$position}";
            }
        };

        $this->runTest('AssetManager with template', function() use ($mockTemplate) {
            $assetManager = new AssetManagerService($mockTemplate);
            $jsResult = $assetManager->addJs('test.js', 'bottom', false);
            $cssResult = $assetManager->addCss('test.css', 'top');
            
            return $jsResult === 'js:test.js:bottom:file' && 
                   $cssResult === 'css:test.css:top';
        });

        // Test RoleHandler with callback
        $this->runTest('RoleHandler with callback', function() {
            $callbackCalled = false;
            $callback = function($data, $operator) use (&$callbackCalled) {
                $callbackCalled = true;
            };
            
            $roleHandler = new RoleHandlerService($callback);
            $roleHandler->setFilterCallback($callback);
            
            // This should not throw an exception
            $roleHandler->applySessionFilters(['user_group' => 'user']);
            
            return true; // Test passes if no exception thrown
        });

        echo "\n";
    }

    private function testErrorHandling(): void
    {
        echo "⚠️  Testing Error Handling...\n";

        $this->runTest('SessionManager error handling', function() {
            try {
                $sessionManager = new SessionManagerService();
                $sessionManager->setSession();
                return true; // No exception thrown
            } catch (\Throwable $e) {
                return false;
            }
        });

        $this->runTest('AssetManager error handling', function() {
            try {
                $assetManager = new AssetManagerService();
                $assetManager->addJs('test.js');
                return true; // No exception thrown
            } catch (\Throwable $e) {
                return false;
            }
        });

        $this->runTest('RoleHandler error handling', function() {
            try {
                $roleHandler = new RoleHandlerService();
                $roleHandler->applySessionFilters([]);
                return true; // No exception thrown
            } catch (\Throwable $e) {
                return false;
            }
        });

        echo "\n";
    }

    private function runTest(string $testName, callable $testFunction): void
    {
        $this->totalTests++;
        
        try {
            $result = $testFunction();
            if ($result) {
                $this->passedTests++;
                $this->results[] = "✅ {$testName}";
                echo "✅ {$testName}\n";
            } else {
                $this->failedTests++;
                $this->results[] = "❌ {$testName}";
                echo "❌ {$testName}\n";
            }
        } catch (\Throwable $e) {
            $this->failedTests++;
            $this->results[] = "❌ {$testName} (Exception: {$e->getMessage()})";
            echo "❌ {$testName} (Exception: {$e->getMessage()})\n";
        }
    }

    private function printSummary(): void
    {
        echo "📊 TEST SUMMARY\n";
        echo "===============\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        echo "Success Rate: " . round(($this->passedTests / $this->totalTests) * 100, 2) . "%\n\n";

        if ($this->failedTests > 0) {
            echo "❌ FAILED TESTS:\n";
            foreach ($this->results as $result) {
                if (strpos($result, '❌') === 0) {
                    echo $result . "\n";
                }
            }
            echo "\n";
        }

        if ($this->passedTests === $this->totalTests) {
            echo "🎉 ALL TESTS PASSED! Phase 3 Utility Services are working correctly.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the implementation.\n";
        }
    }
}

// Mock canvastack functions for testing
if (!function_exists('canvastack_sessions')) {
    function canvastack_sessions() {
        return [];
    }
}

if (!function_exists('canvastack_template_config')) {
    function canvastack_template_config($key) {
        return [];
    }
}

if (!function_exists('canvastack_current_template')) {
    function canvastack_current_template() {
        return 'default';
    }
}

if (!function_exists('canvastack_config')) {
    function canvastack_config($key) {
        return 'test_value';
    }
}

// Run the tests
$testRunner = new UtilityServicesTestRunner();
$testRunner->runAllTests();