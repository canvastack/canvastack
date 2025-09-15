# Custom Extensions

CanvaStack Table's trait-based architecture allows you to create custom extensions that seamlessly integrate with the existing system. This guide covers creating custom traits, extending functionality, and building reusable components.

## Table of Contents

- [Creating Custom Traits](#creating-custom-traits)
- [Trait Structure and Conventions](#trait-structure-and-conventions)
- [Integration with Core System](#integration-with-core-system)
- [Advanced Custom Features](#advanced-custom-features)
- [Testing Custom Traits](#testing-custom-traits)
- [Publishing and Sharing](#publishing-and-sharing)
- [Real-World Examples](#real-world-examples)
- [Best Practices](#best-practices)

## Creating Custom Traits

### Basic Custom Trait

Create a simple custom trait for additional functionality:

```php
<?php

namespace App\Traits\Table;

trait NotificationTrait
{
    protected $notificationConfig = [
        'enabled' => false,
        'types' => ['success', 'warning', 'error', 'info'],
        'position' => 'top-right',
        'duration' => 5000,
        'sound' => false
    ];

    /**
     * Enable notifications for table actions
     */
    public function enableNotifications($config = [])
    {
        $this->notificationConfig = array_merge($this->notificationConfig, [
            'enabled' => true
        ], $config);

        return $this;
    }

    /**
     * Set notification configuration
     */
    public function setNotificationConfig($config)
    {
        $this->notificationConfig = array_merge($this->notificationConfig, $config);
        return $this;
    }

    /**
     * Add a notification to the queue
     */
    public function addNotification($type, $message, $title = null)
    {
        if (!$this->notificationConfig['enabled']) {
            return $this;
        }

        $notification = [
            'type' => $type,
            'message' => $message,
            'title' => $title,
            'timestamp' => now()->toISOString()
        ];

        session()->push('table_notifications', $notification);

        return $this;
    }

    /**
     * Get pending notifications
     */
    public function getNotifications()
    {
        return session()->get('table_notifications', []);
    }

    /**
     * Clear all notifications
     */
    public function clearNotifications()
    {
        session()->forget('table_notifications');
        return $this;
    }

    /**
     * Render notification JavaScript
     */
    public function renderNotificationScript()
    {
        if (!$this->notificationConfig['enabled']) {
            return '';
        }

        $notifications = $this->getNotifications();
        $config = json_encode($this->notificationConfig);

        return view('table.notifications.script', [
            'notifications' => $notifications,
            'config' => $config
        ])->render();
    }
}
```

### Advanced Custom Trait with Dependencies

Create a more complex trait that depends on other traits:

```php
<?php

namespace App\Traits\Table;

use Canvastack\Canvastack\Objects\Traits\SecurityTrait;
use Canvastack\Canvastack\Objects\Traits\AuditTrait;

trait WorkflowTrait
{
    use SecurityTrait, AuditTrait;

    protected $workflowConfig = [
        'enabled' => false,
        'states' => [],
        'transitions' => [],
        'permissions' => [],
        'auto_transitions' => [],
        'notifications' => []
    ];

    /**
     * Enable workflow functionality
     */
    public function enableWorkflow($config = [])
    {
        $this->workflowConfig = array_merge($this->workflowConfig, [
            'enabled' => true
        ], $config);

        // Set up default workflow states
        if (empty($this->workflowConfig['states'])) {
            $this->setDefaultWorkflowStates();
        }

        return $this;
    }

    /**
     * Define workflow states
     */
    public function setWorkflowStates($states)
    {
        $this->workflowConfig['states'] = $states;
        return $this;
    }

    /**
     * Define workflow transitions
     */
    public function setWorkflowTransitions($transitions)
    {
        $this->workflowConfig['transitions'] = $transitions;
        return $this;
    }

    /**
     * Set workflow permissions
     */
    public function setWorkflowPermissions($permissions)
    {
        $this->workflowConfig['permissions'] = $permissions;
        return $this;
    }

    /**
     * Add workflow action buttons
     */
    public function addWorkflowActions($row)
    {
        if (!$this->workflowConfig['enabled']) {
            return [];
        }

        $currentState = $row->workflow_state ?? 'draft';
        $availableTransitions = $this->getAvailableTransitions($currentState, $row);
        $actions = [];

        foreach ($availableTransitions as $transition) {
            if ($this->canPerformTransition($transition, $row)) {
                $actions[$transition['key']] = [
                    'label' => $transition['label'],
                    'url' => $this->generateTransitionUrl($transition, $row),
                    'class' => $transition['class'] ?? 'btn btn-secondary btn-sm',
                    'icon' => $transition['icon'] ?? 'fas fa-arrow-right',
                    'method' => 'POST',
                    'confirm' => $transition['confirm'] ?? false,
                    'ajax' => [
                        'enabled' => true,
                        'reload_table' => true,
                        'success_message' => $transition['success_message'] ?? 'State updated successfully'
                    ]
                ];
            }
        }

        return $actions;
    }

    /**
     * Process workflow transition
     */
    public function processWorkflowTransition($id, $transition, $data = [])
    {
        $record = $this->findRecord($id);
        
        if (!$record) {
            throw new \Exception('Record not found');
        }

        $currentState = $record->workflow_state ?? 'draft';
        
        if (!$this->canPerformTransition($transition, $record)) {
            throw new \Exception('Transition not allowed');
        }

        // Perform pre-transition actions
        $this->executePreTransitionActions($record, $transition, $data);

        // Update state
        $newState = $this->workflowConfig['transitions'][$transition]['to'];
        $record->workflow_state = $newState;
        $record->workflow_updated_at = now();
        $record->workflow_updated_by = auth()->id();
        $record->save();

        // Log transition
        $this->logWorkflowTransition($record, $currentState, $newState, $transition);

        // Perform post-transition actions
        $this->executePostTransitionActions($record, $transition, $data);

        // Send notifications
        $this->sendWorkflowNotifications($record, $transition);

        // Check for auto-transitions
        $this->checkAutoTransitions($record);

        return $record;
    }

    /**
     * Get available transitions for current state
     */
    protected function getAvailableTransitions($currentState, $row)
    {
        $transitions = [];

        foreach ($this->workflowConfig['transitions'] as $key => $transition) {
            if ($transition['from'] === $currentState || 
                (is_array($transition['from']) && in_array($currentState, $transition['from']))) {
                
                $transitions[] = array_merge($transition, ['key' => $key]);
            }
        }

        return $transitions;
    }

    /**
     * Check if user can perform transition
     */
    protected function canPerformTransition($transition, $row)
    {
        // Check permissions
        if (isset($transition['permission'])) {
            if (!auth()->user()->can($transition['permission'], $row)) {
                return false;
            }
        }

        // Check custom conditions
        if (isset($transition['condition']) && is_callable($transition['condition'])) {
            return $transition['condition']($row, auth()->user());
        }

        return true;
    }

    /**
     * Set default workflow states
     */
    protected function setDefaultWorkflowStates()
    {
        $this->workflowConfig['states'] = [
            'draft' => [
                'label' => 'Draft',
                'color' => 'secondary',
                'icon' => 'fas fa-edit'
            ],
            'pending' => [
                'label' => 'Pending Review',
                'color' => 'warning',
                'icon' => 'fas fa-clock'
            ],
            'approved' => [
                'label' => 'Approved',
                'color' => 'success',
                'icon' => 'fas fa-check'
            ],
            'rejected' => [
                'label' => 'Rejected',
                'color' => 'danger',
                'icon' => 'fas fa-times'
            ]
        ];

        $this->workflowConfig['transitions'] = [
            'submit_for_review' => [
                'from' => 'draft',
                'to' => 'pending',
                'label' => 'Submit for Review',
                'permission' => 'submit-for-review'
            ],
            'approve' => [
                'from' => 'pending',
                'to' => 'approved',
                'label' => 'Approve',
                'permission' => 'approve-items',
                'class' => 'btn btn-success btn-sm'
            ],
            'reject' => [
                'from' => 'pending',
                'to' => 'rejected',
                'label' => 'Reject',
                'permission' => 'reject-items',
                'class' => 'btn btn-danger btn-sm',
                'confirm' => true
            ],
            'revise' => [
                'from' => ['pending', 'rejected'],
                'to' => 'draft',
                'label' => 'Send Back for Revision',
                'permission' => 'revise-items'
            ]
        ];
    }

    /**
     * Execute pre-transition actions
     */
    protected function executePreTransitionActions($record, $transition, $data)
    {
        $transitionConfig = $this->workflowConfig['transitions'][$transition];
        
        if (isset($transitionConfig['pre_actions'])) {
            foreach ($transitionConfig['pre_actions'] as $action) {
                if (is_callable($action)) {
                    $action($record, $data);
                } elseif (is_string($action) && method_exists($this, $action)) {
                    $this->$action($record, $data);
                }
            }
        }
    }

    /**
     * Execute post-transition actions
     */
    protected function executePostTransitionActions($record, $transition, $data)
    {
        $transitionConfig = $this->workflowConfig['transitions'][$transition];
        
        if (isset($transitionConfig['post_actions'])) {
            foreach ($transitionConfig['post_actions'] as $action) {
                if (is_callable($action)) {
                    $action($record, $data);
                } elseif (is_string($action) && method_exists($this, $action)) {
                    $this->$action($record, $data);
                }
            }
        }
    }

    /**
     * Log workflow transition
     */
    protected function logWorkflowTransition($record, $fromState, $toState, $transition)
    {
        $this->auditAccess('workflow_transition', [
            'record_id' => $record->id,
            'from_state' => $fromState,
            'to_state' => $toState,
            'transition' => $transition,
            'user_id' => auth()->id()
        ]);
    }
}
```

## Trait Structure and Conventions

### Naming Conventions

Follow these naming conventions for consistency:

```php
<?php

namespace App\Traits\Table;

trait CustomFeatureTrait  // Always end with 'Trait'
{
    // Configuration property - use camelCase with 'Config' suffix
    protected $customFeatureConfig = [];

    // Public methods - use camelCase, start with verb
    public function enableCustomFeature($config = []) {}
    public function setCustomFeatureOption($key, $value) {}
    public function getCustomFeatureConfig() {}
    public function addCustomFeatureItem($item) {}
    public function removeCustomFeatureItem($key) {}

    // Protected methods - use camelCase, descriptive names
    protected function processCustomFeatureData($data) {}
    protected function validateCustomFeatureConfig($config) {}
    protected function renderCustomFeatureOutput() {}

    // Private methods - use camelCase, specific implementation details
    private function buildCustomFeatureQuery() {}
    private function formatCustomFeatureResponse($data) {}
}
```

### Configuration Structure

Use consistent configuration structure:

```php
trait CustomTrait
{
    protected $customConfig = [
        // Feature toggle
        'enabled' => false,
        
        // Basic options
        'option1' => 'default_value',
        'option2' => [],
        
        // Nested configuration
        'advanced' => [
            'setting1' => true,
            'setting2' => 100
        ],
        
        // Callbacks
        'callbacks' => [
            'before_process' => null,
            'after_process' => null
        ],
        
        // UI options
        'ui' => [
            'position' => 'top',
            'class' => 'custom-feature',
            'template' => 'custom.feature'
        ]
    ];

    public function enableCustom($config = [])
    {
        $this->customConfig = array_merge($this->customConfig, [
            'enabled' => true
        ], $config);

        // Validate configuration
        $this->validateCustomConfig();

        return $this;
    }

    protected function validateCustomConfig()
    {
        // Validation logic
        if (!in_array($this->customConfig['ui']['position'], ['top', 'bottom', 'left', 'right'])) {
            throw new \InvalidArgumentException('Invalid position specified');
        }
    }
}
```

## Integration with Core System

### Hooking into Table Lifecycle

Integrate with the table's lifecycle events:

```php
trait LifecycleIntegrationTrait
{
    protected $lifecycleHooks = [];

    public function enableLifecycleIntegration()
    {
        // Hook into existing lifecycle events
        $this->onBeforeRender([$this, 'beforeRenderHook']);
        $this->onAfterRender([$this, 'afterRenderHook']);
        $this->onBeforeQuery([$this, 'beforeQueryHook']);
        $this->onAfterQuery([$this, 'afterQueryHook']);
        $this->onDataTransform([$this, 'dataTransformHook']);

        return $this;
    }

    public function beforeRenderHook()
    {
        // Custom logic before table renders
        $this->prepareCustomData();
        $this->validateCustomConfiguration();
    }

    public function afterRenderHook($output)
    {
        // Custom logic after table renders
        $this->injectCustomScripts($output);
        $this->logTableRender();
        
        return $output;
    }

    public function beforeQueryHook($query)
    {
        // Modify query before execution
        $this->applyCustomFilters($query);
        $this->addCustomJoins($query);
        
        return $query;
    }

    public function afterQueryHook($data)
    {
        // Process data after query
        $this->enrichDataWithCustomFields($data);
        $this->applyCustomSorting($data);
        
        return $data;
    }

    public function dataTransformHook($data)
    {
        // Transform data for display
        return $data->map(function($item) {
            return $this->transformCustomFields($item);
        });
    }
}
```

### Extending Existing Traits

Extend existing traits to add functionality:

```php
trait ExtendedActionsTrait
{
    use \Canvastack\Canvastack\Objects\Traits\ActionsTrait;

    /**
     * Add bulk actions with custom processing
     */
    public function setBulkActionsWithWorkflow($actions)
    {
        $workflowActions = [];

        foreach ($actions as $key => $action) {
            // Add workflow validation
            $action['validate'] = function($ids) use ($key) {
                return $this->validateBulkWorkflowAction($key, $ids);
            };

            // Add workflow processing
            $action['process'] = function($ids, $data) use ($key) {
                return $this->processBulkWorkflowAction($key, $ids, $data);
            };

            $workflowActions[$key] = $action;
        }

        return $this->setBulkActions($workflowActions);
    }

    /**
     * Generate actions with workflow states
     */
    public function generateWorkflowActions($row)
    {
        $baseActions = $this->generateActionButtons($row);
        $workflowActions = $this->generateWorkflowActionButtons($row);

        return array_merge($baseActions, $workflowActions);
    }

    protected function validateBulkWorkflowAction($action, $ids)
    {
        // Custom validation logic
        foreach ($ids as $id) {
            $record = $this->findRecord($id);
            if (!$this->canPerformBulkAction($action, $record)) {
                return false;
            }
        }
        return true;
    }

    protected function processBulkWorkflowAction($action, $ids, $data)
    {
        $results = [];

        foreach ($ids as $id) {
            try {
                $result = $this->processWorkflowTransition($id, $action, $data);
                $results[] = ['id' => $id, 'success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[] = ['id' => $id, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
```

## Advanced Custom Features

### Custom Filter Types

Create custom filter types:

```php
trait CustomFiltersTrait
{
    protected $customFilterTypes = [];

    public function registerCustomFilterType($type, $config)
    {
        $this->customFilterTypes[$type] = array_merge([
            'template' => null,
            'javascript' => null,
            'validation' => null,
            'query_builder' => null,
            'options_provider' => null
        ], $config);

        return $this;
    }

    public function addCustomFilter($field, $type, $options = [])
    {
        if (!isset($this->customFilterTypes[$type])) {
            throw new \InvalidArgumentException("Custom filter type '{$type}' not registered");
        }

        $filterConfig = array_merge($this->customFilterTypes[$type], $options, [
            'field' => $field,
            'type' => $type
        ]);

        $this->addFilter($field, $filterConfig);

        return $this;
    }

    // Example: Color picker filter
    public function addColorPickerFilter($field, $options = [])
    {
        $this->registerCustomFilterType('color_picker', [
            'template' => 'filters.color_picker',
            'javascript' => 'initColorPickerFilter',
            'validation' => function($value) {
                return preg_match('/^#[a-f0-9]{6}$/i', $value);
            },
            'query_builder' => function($query, $field, $value) {
                return $query->where($field, $value);
            }
        ]);

        return $this->addCustomFilter($field, 'color_picker', $options);
    }

    // Example: Geo-location filter
    public function addGeoLocationFilter($latField, $lngField, $options = [])
    {
        $this->registerCustomFilterType('geo_location', [
            'template' => 'filters.geo_location',
            'javascript' => 'initGeoLocationFilter',
            'validation' => function($value) {
                return isset($value['lat']) && isset($value['lng']) && isset($value['radius']);
            },
            'query_builder' => function($query, $field, $value) use ($latField, $lngField) {
                $lat = $value['lat'];
                $lng = $value['lng'];
                $radius = $value['radius'];
                
                return $query->whereRaw("
                    ST_Distance_Sphere(
                        POINT({$lngField}, {$latField}),
                        POINT(?, ?)
                    ) <= ? * 1000
                ", [$lng, $lat, $radius]);
            }
        ]);

        return $this->addCustomFilter($latField . '_' . $lngField, 'geo_location', $options);
    }
}
```

### Custom Export Formats

Add custom export formats:

```php
trait CustomExportTrait
{
    use \Canvastack\Canvastack\Objects\Traits\ExportTrait;

    protected $customExportFormats = [];

    public function addCustomExportFormat($format, $config)
    {
        $this->customExportFormats[$format] = array_merge([
            'label' => ucfirst($format),
            'icon' => 'fas fa-download',
            'handler' => null,
            'mime_type' => 'application/octet-stream',
            'extension' => $format
        ], $config);

        return $this;
    }

    // Example: JSON export
    public function enableJsonExport($config = [])
    {
        return $this->addCustomExportFormat('json', array_merge([
            'label' => 'JSON Export',
            'icon' => 'fas fa-code',
            'mime_type' => 'application/json',
            'handler' => function($data, $columns, $options) {
                $exportData = $data->map(function($row) use ($columns) {
                    $exportRow = [];
                    foreach ($columns as $column => $label) {
                        $exportRow[$label] = $row->{$column};
                    }
                    return $exportRow;
                });

                $filename = ($options['filename'] ?? 'export') . '.json';
                
                return response()->json($exportData->toArray(), 200, [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            }
        ], $config));
    }

    // Example: XML export
    public function enableXmlExport($config = [])
    {
        return $this->addCustomExportFormat('xml', array_merge([
            'label' => 'XML Export',
            'icon' => 'fas fa-file-code',
            'mime_type' => 'application/xml',
            'handler' => function($data, $columns, $options) {
                $xml = new \SimpleXMLElement('<data/>');
                
                foreach ($data as $row) {
                    $item = $xml->addChild('item');
                    foreach ($columns as $column => $label) {
                        $item->addChild(str_replace(' ', '_', strtolower($label)), htmlspecialchars($row->{$column}));
                    }
                }

                $filename = ($options['filename'] ?? 'export') . '.xml';
                
                return response($xml->asXML(), 200, [
                    'Content-Type' => 'application/xml',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]);
            }
        ], $config));
    }
}
```

## Testing Custom Traits

### Unit Testing Custom Traits

```php
<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Traits\Table\NotificationTrait;
use Canvastack\Canvastack\Objects\Objects;

class NotificationTraitTest extends TestCase
{
    use NotificationTrait;

    protected $table;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->table = new class {
            use NotificationTrait;
        };
    }

    public function test_can_enable_notifications()
    {
        $this->table->enableNotifications([
            'position' => 'bottom-left',
            'duration' => 3000
        ]);

        $config = $this->table->getNotificationConfig();
        
        $this->assertTrue($config['enabled']);
        $this->assertEquals('bottom-left', $config['position']);
        $this->assertEquals(3000, $config['duration']);
    }

    public function test_can_add_notification()
    {
        $this->table->enableNotifications();
        $this->table->addNotification('success', 'Test message', 'Test Title');

        $notifications = $this->table->getNotifications();
        
        $this->assertCount(1, $notifications);
        $this->assertEquals('success', $notifications[0]['type']);
        $this->assertEquals('Test message', $notifications[0]['message']);
        $this->assertEquals('Test Title', $notifications[0]['title']);
    }

    public function test_can_clear_notifications()
    {
        $this->table->enableNotifications();
        $this->table->addNotification('info', 'Test message');
        
        $this->assertCount(1, $this->table->getNotifications());
        
        $this->table->clearNotifications();
        
        $this->assertCount(0, $this->table->getNotifications());
    }

    public function test_notifications_disabled_by_default()
    {
        $this->table->addNotification('error', 'This should not be added');
        
        $this->assertCount(0, $this->table->getNotifications());
    }
}
```

### Integration Testing

```php
<?php

namespace Tests\Feature\Traits;

use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\CustomUserController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkflowTraitIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_actions_appear_in_table()
    {
        $user = User::factory()->create(['workflow_state' => 'draft']);
        
        $controller = new CustomUserController();
        $response = $controller->index();
        
        $this->assertStringContainsString('Submit for Review', $response->getContent());
    }

    public function test_workflow_transition_updates_state()
    {
        $user = User::factory()->create(['workflow_state' => 'draft']);
        
        $controller = new CustomUserController();
        $result = $controller->processWorkflowTransition($user->id, 'submit_for_review');
        
        $this->assertEquals('pending', $result->workflow_state);
        $this->assertNotNull($result->workflow_updated_at);
        $this->assertEquals(auth()->id(), $result->workflow_updated_by);
    }

    public function test_workflow_permissions_are_enforced()
    {
        $user = User::factory()->create(['workflow_state' => 'pending']);
        $regularUser = User::factory()->create();
        
        $this->actingAs($regularUser);
        
        $controller = new CustomUserController();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transition not allowed');
        
        $controller->processWorkflowTransition($user->id, 'approve');
    }
}
```

## Publishing and Sharing

### Creating a Package

Structure your custom traits as a package:

```
my-table-extensions/
├── src/
│   ├── Traits/
│   │   ├── NotificationTrait.php
│   │   ├── WorkflowTrait.php
│   │   └── CustomFiltersTrait.php
│   ├── Views/
│   │   ├── notifications/
│   │   └── filters/
│   ├── Assets/
│   │   ├── js/
│   │   └── css/
│   └── ServiceProvider.php
├── tests/
├── composer.json
└── README.md
```

### Service Provider

```php
<?php

namespace MyCompany\TableExtensions;

use Illuminate\Support\ServiceProvider;

class TableExtensionsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish views
        $this->publishes([
            __DIR__ . '/Views' => resource_path('views/vendor/table-extensions'),
        ], 'table-extensions-views');

        // Publish assets
        $this->publishes([
            __DIR__ . '/Assets' => public_path('vendor/table-extensions'),
        ], 'table-extensions-assets');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/Views', 'table-extensions');
    }

    public function register()
    {
        // Register any services
    }
}
```

### Composer Configuration

```json
{
    "name": "mycompany/canvastack-table-extensions",
    "description": "Custom extensions for CanvaStack Table",
    "type": "library",
    "require": {
        "php": "^8.0",
        "canvastack/canvastack": "^2.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "orchestra/testbench": "^6.0"
    },
    "autoload": {
        "psr-4": {
            "MyCompany\\TableExtensions\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "MyCompany\\TableExtensions\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "MyCompany\\TableExtensions\\TableExtensionsServiceProvider"
            ]
        }
    }
}
```

## Real-World Examples

### Multi-Tenant Trait

```php
trait MultiTenantTrait
{
    protected $tenantConfig = [
        'enabled' => false,
        'tenant_column' => 'tenant_id',
        'auto_filter' => true,
        'tenant_resolver' => null
    ];

    public function enableMultiTenant($config = [])
    {
        $this->tenantConfig = array_merge($this->tenantConfig, [
            'enabled' => true
        ], $config);

        if ($this->tenantConfig['auto_filter']) {
            $this->applyTenantFilter();
        }

        return $this;
    }

    protected function applyTenantFilter()
    {
        $tenantId = $this->resolveTenantId();
        
        $this->onBeforeQuery(function($query) use ($tenantId) {
            return $query->where($this->tenantConfig['tenant_column'], $tenantId);
        });
    }

    protected function resolveTenantId()
    {
        if ($this->tenantConfig['tenant_resolver']) {
            return call_user_func($this->tenantConfig['tenant_resolver']);
        }

        // Default: get from authenticated user
        return auth()->user()->tenant_id ?? null;
    }
}
```

### Versioning Trait

```php
trait VersioningTrait
{
    protected $versioningConfig = [
        'enabled' => false,
        'version_column' => 'version',
        'show_versions' => false,
        'compare_versions' => false
    ];

    public function enableVersioning($config = [])
    {
        $this->versioningConfig = array_merge($this->versioningConfig, [
            'enabled' => true
        ], $config);

        $this->addVersioningActions();

        return $this;
    }

    protected function addVersioningActions()
    {
        $this->setActions(array_merge($this->getActions(), [
            'view_versions' => [
                'label' => 'Versions',
                'url' => '/records/{id}/versions',
                'class' => 'btn btn-info btn-sm',
                'icon' => 'fas fa-history',
                'modal' => [
                    'enabled' => true,
                    'size' => 'modal-lg',
                    'title' => 'Version History'
                ]
            ],
            'compare_versions' => [
                'label' => 'Compare',
                'url' => '/records/{id}/compare',
                'class' => 'btn btn-secondary btn-sm',
                'icon' => 'fas fa-code-compare',
                'condition' => function($row) {
                    return $row->version > 1;
                }
            ]
        ]));
    }
}
```

## Best Practices

### 1. Follow Single Responsibility Principle

Each trait should have a single, well-defined purpose:

```php
// Good: Focused on notifications only
trait NotificationTrait
{
    // Only notification-related methods
}

// Bad: Mixed responsibilities
trait MixedTrait
{
    // Notification methods
    // Export methods  
    // Workflow methods
    // etc.
}
```

### 2. Use Dependency Injection

Inject dependencies rather than creating them:

```php
trait EmailTrait
{
    protected $mailer;

    public function setMailer($mailer)
    {
        $this->mailer = $mailer;
        return $this;
    }

    protected function sendEmail($to, $subject, $body)
    {
        if (!$this->mailer) {
            $this->mailer = app('mailer');
        }

        return $this->mailer->send($to, $subject, $body);
    }
}
```

### 3. Provide Configuration Validation

Always validate configuration:

```php
trait ConfigurableTrait
{
    protected function validateConfig($config)
    {
        $required = ['option1', 'option2'];
        
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("Required option '{$key}' is missing");
            }
        }

        if (isset($config['position']) && !in_array($config['position'], ['top', 'bottom'])) {
            throw new \InvalidArgumentException("Invalid position: {$config['position']}");
        }
    }
}
```

### 4. Document Your Traits

Provide comprehensive documentation:

```php
/**
 * Notification Trait
 * 
 * Provides notification functionality for table actions.
 * 
 * @example
 * ```php
 * $this->table->enableNotifications([
 *     'position' => 'top-right',
 *     'duration' => 5000
 * ]);
 * ```
 */
trait NotificationTrait
{
    /**
     * Enable notifications with optional configuration
     * 
     * @param array $config Configuration options
     * @return $this
     */
    public function enableNotifications($config = [])
    {
        // Implementation
    }
}
```

### 5. Handle Errors Gracefully

Implement proper error handling:

```php
trait RobustTrait
{
    protected function processData($data)
    {
        try {
            return $this->doProcessing($data);
        } catch (\Exception $e) {
            Log::error('Trait processing failed: ' . $e->getMessage(), [
                'trait' => static::class,
                'data' => $data
            ]);

            // Return safe fallback
            return $this->getFallbackData();
        }
    }
}
```

---

## Related Documentation

- [Available Traits](overview.md) - Built-in traits reference
- [API Reference](../api/objects.md) - Core system methods
- [Architecture Overview](../architecture.md) - Understanding the trait system
- [Testing](../advanced/testing.md) - Testing custom traits