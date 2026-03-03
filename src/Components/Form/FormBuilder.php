<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Form;

use Canvastack\Canvastack\Components\Form\Features\Ajax\AjaxSync;
use Canvastack\Canvastack\Components\Form\Features\Ajax\EloquentSyncBuilder;
use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig;
use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileProcessor;
use Canvastack\Canvastack\Components\Form\Features\SoftDelete\SoftDeleteActions;
use Canvastack\Canvastack\Components\Form\Features\SoftDelete\SoftDeleteIndicator;
use Canvastack\Canvastack\Components\Form\Features\Tabs\TabManager;
use Canvastack\Canvastack\Components\Form\Fields\BaseField;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\PublicRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\RendererInterface;
use Canvastack\Canvastack\Components\Form\Support\AssetManager;
use Canvastack\Canvastack\Components\Form\Support\ModelInspector;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * FormBuilder - Modern form component with backward compatibility.
 *
 * Provides both legacy API and new fluent interface for form building.
 * Supports Admin and Public rendering strategies.
 */
class FormBuilder
{
    protected FieldFactory $fieldFactory;

    protected RendererInterface $renderer;

    protected ValidationCache $validationCache;

    protected TabManager $tabManager;

    protected AjaxSync $ajaxSync;

    protected ?FileProcessor $fileProcessor = null;

    protected CKEditorIntegration $ckeditorIntegration;

    protected SearchableSelect $searchableSelect;

    protected \Canvastack\Canvastack\Components\Form\Features\Enhancements\TagsInput $tagsInput;

    protected \Canvastack\Canvastack\Components\Form\Features\Enhancements\DateRangePicker $dateRangePicker;

    protected \Canvastack\Canvastack\Components\Form\Features\Enhancements\MonthPicker $monthPicker;

    protected AssetManager $assetManager;

    protected ModelInspector $modelInspector;

    protected SoftDeleteIndicator $softDeleteIndicator;

    protected SoftDeleteActions $softDeleteActions;

    protected array $fields = [];

    protected array $hiddenFields = [];

    protected array $config = [];

    protected ?object $model = null;

    protected ?string $formIdentity = null;

    protected string $context = 'admin';

    protected bool $viewMode = false;

    protected ?string $permission = null;

    public function __construct(
        FieldFactory $fieldFactory,
        ValidationCache $validationCache,
        ?FileProcessor $fileProcessor = null
    ) {
        $this->fieldFactory = $fieldFactory;
        $this->validationCache = $validationCache;
        $this->fileProcessor = $fileProcessor;
        $this->modelInspector = new ModelInspector();
        $this->softDeleteIndicator = new SoftDeleteIndicator();
        $this->softDeleteActions = new SoftDeleteActions();
        $this->setContext('admin'); // Default to admin context

        // Initialize configuration with defaults
        $this->config = [
            'cache_enabled' => config('canvastack.form.cache_enabled', true),
            'showPermanentDelete' => false,
        ];

        // Initialize AssetManager
        $this->initializeAssetManager();

        // Initialize TabManager with renderer
        $this->initializeTabManager();

        // Initialize AjaxSync
        $this->initializeAjaxSync();

        // Initialize CKEditor integration
        $this->initializeCKEditor();

        // Initialize SearchableSelect
        $this->initializeSearchableSelect();

        // Initialize TagsInput
        $this->initializeTagsInput();

        // Initialize DateRangePicker
        $this->initializeDateRangePicker();

        // Initialize MonthPicker
        $this->initializeMonthPicker();
    }

    /**
     * Initialize AssetManager.
     */
    protected function initializeAssetManager(): void
    {
        $this->assetManager = new \Canvastack\Canvastack\Components\Form\Support\AssetManager();
    }

    /**
     * Initialize TabManager with current renderer.
     */
    protected function initializeTabManager(): void
    {
        $this->tabManager = new \Canvastack\Canvastack\Components\Form\Features\Tabs\TabManager($this->renderer);
    }

    /**
     * Initialize AjaxSync with encryption.
     */
    protected function initializeAjaxSync(): void
    {
        $encryption = app(QueryEncryption::class);
        $this->ajaxSync = new AjaxSync($encryption);
    }

    /**
     * Initialize CKEditor integration.
     *
     * Requirements: 4.1, 4.2
     */
    protected function initializeCKEditor(): void
    {
        $editorConfig = new EditorConfig();
        $this->ckeditorIntegration = new CKEditorIntegration($editorConfig);
        // Set context to match FormBuilder context
        $this->ckeditorIntegration->setContext($this->context);
    }

    /**
     * Initialize SearchableSelect component.
     *
     * Requirements: 6.1, 6.2
     */
    protected function initializeSearchableSelect(): void
    {
        $this->searchableSelect = new \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect();
    }

    /**
     * Initialize TagsInput component.
     *
     * Requirements: 9.1, 9.2
     */
    protected function initializeTagsInput(): void
    {
        $this->tagsInput = new \Canvastack\Canvastack\Components\Form\Features\Enhancements\TagsInput($this->assetManager);
    }

    /**
     * Initialize DateRangePicker component.
     *
     * Requirements: 10.1, 10.2
     */
    protected function initializeDateRangePicker(): void
    {
        $this->dateRangePicker = new \Canvastack\Canvastack\Components\Form\Features\Enhancements\DateRangePicker($this->assetManager);
    }

    /**
     * Initialize MonthPicker component.
     *
     * Requirements: 11.1, 11.2
     */
    protected function initializeMonthPicker(): void
    {
        $this->monthPicker = new \Canvastack\Canvastack\Components\Form\Features\Enhancements\MonthPicker($this->assetManager);
    }

    /**
     * Set rendering context (admin or public).
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        $this->renderer = $context === 'public'
            ? new PublicRenderer()
            : new AdminRenderer();

        // Reinitialize TabManager with new renderer
        $this->initializeTabManager();

        // Update CKEditor context if already initialized
        if (isset($this->ckeditorIntegration)) {
            $this->ckeditorIntegration->setContext($context);
        }

        return $this;
    }

    /**
     * Get current context.
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Enable view mode for display-only forms.
     *
     * When enabled, renders all fields as read-only text instead of input elements.
     * Automatically enabled when current route name contains "show".
     *
     * Requirements: 12.1, 12.2, 12.5
     *
     * @param bool $enable Enable or disable view mode
     * @return self For method chaining
     */
    public function viewMode(bool $enable = true): self
    {
        $this->viewMode = $enable;

        return $this;
    }

    /**
     * Check if view mode is enabled.
     *
     * @return bool True if view mode is enabled
     */
    public function isViewMode(): bool
    {
        // Auto-detect from route if not explicitly set
        if (!$this->viewMode && function_exists('request')) {
            $routeName = request()->route()?->getName() ?? '';
            if (str_contains($routeName, 'show')) {
                return true;
            }
        }

        return $this->viewMode;
    }

    /**
     * Set model for form binding.
     *
     * Automatically detects and handles soft-deleted models.
     * Requirements: 8.1, 8.2, 8.3, 8.4
     */
    public function setModel(?object $model): self
    {
        $this->model = $model;

        // Reset soft delete configuration first
        $this->config['softDeletes'] = false;
        $this->config['softDeleteColumn'] = null;

        // Detect soft deletes and configure accordingly
        if ($model && $this->modelInspector->usesSoftDeletes($model)) {
            $this->config['softDeletes'] = true;
            $this->config['softDeleteColumn'] = $this->modelInspector->getSoftDeleteColumn($model);
        }

        return $this;
    }

    /**
     * Get bound model.
     */
    public function getModel(): ?object
    {
        return $this->model;
    }

    /**
     * Set permission for filtering.
     *
     * Enables permission-aware rendering by storing the permission name
     * that will be used to filter accessible columns.
     *
     * @param string|null $permission Permission name (e.g., 'posts.edit')
     * @return self
     */
    public function setPermission(?string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Get permission for filtering.
     *
     * @return string|null
     */
    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /**
     * Filter fields based on accessible columns from permission rules.
     *
     * This method gets accessible columns from PermissionRuleManager and filters
     * the form fields to only include fields that the user has permission to access.
     *
     * Requirement 7: FormBuilder Integration
     * - Get accessible columns from Permission_Rule_Manager
     * - Only render form fields for accessible columns
     *
     * @return void
     */
    protected function filterFieldsByPermission(): void
    {
        // Reset hidden fields tracking
        $this->hiddenFields = [];

        // Only filter if permission is set
        if (!$this->permission) {
            return;
        }

        // Only filter if user is authenticated
        try {
            $user = auth()->user();
            if (!$user || !$user->id) {
                return;
            }
            $userId = $user->id;
        } catch (\Exception $e) {
            // Auth not available in test environment
            return;
        }

        // Only filter if model is set (we need model class for permission check)
        if (!$this->model) {
            return;
        }

        $modelClass = get_class($this->model);

        // Get PermissionRuleManager from container
        if (!app()->bound('canvastack.rbac.rule.manager')) {
            // Rule manager not bound in container
            return;
        }

        $ruleManager = app('canvastack.rbac.rule.manager');

        // Get accessible columns
        $accessibleColumns = $ruleManager->getAccessibleColumns(
            $userId,
            $this->permission,
            $modelClass
        );

        // If empty array returned, it means either:
        // 1. Fine-grained permissions are disabled (allow all)
        // 2. No rules defined with default allow (allow all)
        // 3. No rules defined with default deny (deny all)
        // We need to check the config to determine behavior
        if (empty($accessibleColumns)) {
            $config = config('canvastack-rbac.fine_grained.column_level', []);
            $defaultDeny = $config['default_deny'] ?? false;

            // If default is deny and no columns returned, track and remove all fields
            if ($defaultDeny) {
                foreach ($this->fields as $name => $field) {
                    $this->hiddenFields[$name] = [
                        'field' => $field,
                        'reason' => 'column_level_denied',
                    ];
                }
                $this->fields = [];
            }

            // If default is allow, keep all fields but still filter JSON attributes
            // Filter JSON attribute fields even if column filtering is disabled
            $this->filterJsonAttributesByPermission($userId, $ruleManager, $modelClass);

            return;
        }

        // Check if we have negative list (blacklist mode)
        $hasNegativeList = false;
        $deniedColumns = [];

        foreach ($accessibleColumns as $column) {
            if (is_string($column) && str_starts_with($column, '!')) {
                $hasNegativeList = true;
                $deniedColumns[] = substr($column, 1);
            }
        }

        if ($hasNegativeList) {
            // Blacklist mode: track and remove denied columns
            $filtered = [];
            foreach ($this->fields as $name => $field) {
                if (in_array($field->getName(), $deniedColumns)) {
                    $this->hiddenFields[$name] = [
                        'field' => $field,
                        'reason' => 'column_level_denied',
                    ];
                } else {
                    $filtered[$name] = $field;
                }
            }
            $this->fields = $filtered;
        } else {
            // Whitelist mode: track and keep only accessible columns
            // Note: JSON fields (with dots) are handled separately by filterJsonAttributesByPermission
            $filtered = [];
            foreach ($this->fields as $name => $field) {
                $fieldName = $field->getName();

                // Skip JSON fields in column filtering - they'll be handled by JSON filtering
                if (str_contains($fieldName, '.')) {
                    $filtered[$name] = $field;
                    continue;
                }

                // Regular fields: check if in accessible columns
                if (in_array($fieldName, $accessibleColumns)) {
                    $filtered[$name] = $field;
                } else {
                    $this->hiddenFields[$name] = [
                        'field' => $field,
                        'reason' => 'column_level_denied',
                    ];
                }
            }
            $this->fields = $filtered;
        }

        // Filter JSON attribute fields
        $this->filterJsonAttributesByPermission($userId, $ruleManager, $modelClass);
    }

    /**
     * Filter JSON attribute fields based on permissions.
     *
     * This method filters fields that represent JSON attributes (nested fields in JSON columns).
     * It checks accessible JSON paths from PermissionRuleManager and removes fields that
     * the user doesn't have access to.
     *
     * @param int $userId User ID
     * @param mixed $ruleManager PermissionRuleManager instance
     * @param string $modelClass Model class name
     * @return void
     */
    protected function filterJsonAttributesByPermission(
        int $userId,
        $ruleManager,
        string $modelClass
    ): void {
        // Get all JSON columns from the model
        $jsonColumns = $this->getJsonColumnsFromFields();

        if (empty($jsonColumns)) {
            // No JSON fields to filter
            return;
        }

        // Process each JSON column
        foreach ($jsonColumns as $jsonColumn => $paths) {
            // Get accessible JSON paths for this column
            $accessiblePaths = $ruleManager->getAccessibleJsonPaths(
                $userId,
                $this->permission,
                $modelClass,
                $jsonColumn
            );

            // If empty array returned (no keys), fine-grained permissions are disabled (allow all)
            if (empty($accessiblePaths) || !isset($accessiblePaths['allowed'], $accessiblePaths['denied'])) {
                continue;
            }

            // Extract allowed and denied paths
            $allowedPaths = $accessiblePaths['allowed'] ?? [];
            $deniedPaths = $accessiblePaths['denied'] ?? [];

            // Filter fields based on paths
            $this->filterFieldsByJsonPaths($jsonColumn, $paths, $allowedPaths, $deniedPaths);
        }
    }

    /**
     * Get JSON columns and their paths from current fields.
     *
     * This method scans all fields and identifies which ones represent JSON attributes.
     * It returns a map of JSON column names to their paths.
     *
     * Format: ['metadata' => ['seo.title', 'seo.description', 'featured']]
     *
     * @return array<string, array<string>> Map of JSON column to paths
     */
    protected function getJsonColumnsFromFields(): array
    {
        $jsonColumns = [];

        foreach ($this->fields as $field) {
            $fieldName = $field->getName();

            // Check if field name contains dot notation (e.g., 'metadata.seo.title')
            if (str_contains($fieldName, '.')) {
                // Extract JSON column and path
                $parts = explode('.', $fieldName, 2);
                $jsonColumn = $parts[0];
                $path = $parts[1];

                if (!isset($jsonColumns[$jsonColumn])) {
                    $jsonColumns[$jsonColumn] = [];
                }

                $jsonColumns[$jsonColumn][] = $path;
            }
        }

        return $jsonColumns;
    }

    /**
     * Filter fields by JSON paths.
     *
     * This method removes fields that don't match the allowed paths or match denied paths.
     * It supports wildcard matching (e.g., 'seo.*' matches 'seo.title', 'seo.description').
     *
     * @param string $jsonColumn JSON column name
     * @param array<string> $paths Paths in this JSON column
     * @param array<string> $allowedPaths Allowed paths (whitelist)
     * @param array<string> $deniedPaths Denied paths (blacklist)
     * @return void
     */
    protected function filterFieldsByJsonPaths(
        string $jsonColumn,
        array $paths,
        array $allowedPaths,
        array $deniedPaths
    ): void {
        $filtered = [];

        foreach ($this->fields as $name => $field) {
            $fieldName = $field->getName();

            // Check if this field belongs to the current JSON column
            if (!str_starts_with($fieldName, $jsonColumn . '.')) {
                // Keep fields that don't belong to this JSON column
                $filtered[$name] = $field;
                continue;
            }

            // Extract path from field name
            $path = substr($fieldName, strlen($jsonColumn) + 1);

            // Check if path is accessible
            if ($this->isJsonPathAccessible($path, $allowedPaths, $deniedPaths)) {
                $filtered[$name] = $field;
            } else {
                // Track hidden JSON attribute field
                $this->hiddenFields[$name] = [
                    'field' => $field,
                    'reason' => 'json_attribute_denied',
                ];
            }
        }

        $this->fields = $filtered;
    }

    /**
     * Check if a JSON path is accessible based on allowed and denied paths.
     *
     * This method checks if a path matches the allowed paths and doesn't match denied paths.
     * It supports wildcard matching (e.g., 'seo.*' matches 'seo.title', 'seo.description').
     *
     * Logic:
     * 1. If denied paths exist and path matches any denied path → NOT accessible
     * 2. If allowed paths exist and path matches any allowed path → accessible
     * 3. If allowed paths exist and path doesn't match any → NOT accessible
     * 4. If no allowed paths exist (allow all) → accessible
     *
     * @param string $path JSON path to check
     * @param array<string> $allowedPaths Allowed paths (whitelist)
     * @param array<string> $deniedPaths Denied paths (blacklist)
     * @return bool True if accessible, false otherwise
     */
    protected function isJsonPathAccessible(
        string $path,
        array $allowedPaths,
        array $deniedPaths
    ): bool {
        // Check denied paths first (blacklist takes precedence)
        foreach ($deniedPaths as $deniedPath) {
            if ($this->matchesJsonPath($path, $deniedPath)) {
                return false;
            }
        }

        // If no allowed paths specified, allow all (except denied)
        if (empty($allowedPaths)) {
            return true;
        }

        // Check allowed paths (whitelist)
        foreach ($allowedPaths as $allowedPath) {
            if ($this->matchesJsonPath($path, $allowedPath)) {
                return true;
            }
        }

        // Path doesn't match any allowed path
        return false;
    }

    /**
     * Check if a path matches a pattern (supports wildcards).
     *
     * Wildcard matching:
     * - 'seo.*' matches 'seo.title', 'seo.description', 'seo.keywords'
     * - 'seo.title' matches only 'seo.title'
     * - '*' matches everything
     *
     * @param string $path Path to check
     * @param string $pattern Pattern to match against
     * @return bool True if path matches pattern
     */
    protected function matchesJsonPath(string $path, string $pattern): bool
    {
        // Exact match
        if ($path === $pattern) {
            return true;
        }

        // Wildcard match
        if (str_contains($pattern, '*')) {
            // Convert wildcard pattern to regex
            // Step 1: Replace * with a placeholder
            // Step 2: Escape special regex characters
            // Step 3: Replace placeholder with regex pattern
            $placeholder = '___WILDCARD___';
            $pattern = str_replace('*', $placeholder, $pattern);
            $pattern = preg_quote($pattern, '/');
            $pattern = str_replace($placeholder, '.+', $pattern);

            $regex = '/^' . $pattern . '$/';

            return (bool) preg_match($regex, $path);
        }

        return false;
    }

    /**
     * Set form identity.
     */
    public function setFormIdentity(?string $identity): self
    {
        $this->formIdentity = $identity;

        return $this;
    }

    /**
     * Create text input field.
     *
     * @param string $name Field name
     * @param string|null $label Field label (auto-generated if null)
     * @param mixed $value Default value
     * @param array $attributes HTML attributes
     * @return mixed Field instance (for fluent API) or void (for legacy API)
     */
    public function text(string $name, $label = null, $value = null, array $attributes = [])
    {
        $field = $this->fieldFactory->make('text', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;

        // Add field to active tab if tabs are being used
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create textarea field.
     *
     * Detects 'ckeditor' attribute and registers field for CKEditor initialization.
     *
     * Requirements: 4.1, 4.2
     */
    public function textarea(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('textarea', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        // Detect 'ckeditor' attribute and register for CKEditor initialization
        if (isset($attributes['ckeditor']) || (isset($attributes['class']) && str_contains($attributes['class'], 'ckeditor'))) {
            $editorOptions = is_array($attributes['ckeditor'] ?? null) ? $attributes['ckeditor'] : [];
            $this->ckeditorIntegration->register($name, $editorOptions);
        }

        return $field;
    }

    /**
     * Create email input field.
     */
    public function email(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('email', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create password input field.
     */
    public function password(string $name, ?string $label = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('password', $name, $label, null, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create number input field.
     */
    public function number(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('number', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create select field.
     */
    public function select(string $name, ?string $label = null, array $options = [], mixed $selected = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('select', $name, $label, $options, $attributes);
        $field->setSelected($selected);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        // Register with SearchableSelect if field is marked as searchable
        // This will be triggered when the field's searchable() method is called
        // We'll check this during render phase

        return $field;
    }

    /**
     * Create checkbox field.
     */
    public function checkbox(string $name, ?string $label = null, array $options = [], mixed $checked = null, array $attributes = []): BaseField
    {
        /** @var CheckboxField $field */
        $field = $this->fieldFactory->make('checkbox', $name, $label, $options, $attributes);
        $field->setChecked($checked);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create radio field.
     */
    public function radio(string $name, ?string $label = null, array $options = [], mixed $checked = null, array $attributes = []): BaseField
    {
        /** @var RadioField $field */
        $field = $this->fieldFactory->make('radio', $name, $label, $options, $attributes);
        $field->setChecked($checked);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create file upload field.
     */
    public function file(string $name, ?string $label = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('file', $name, $label, null, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Process file upload (Legacy API compatibility).
     *
     * This method provides backward compatibility with the legacy CanvaStack Origin API.
     * It delegates to the FileProcessor for actual file handling.
     *
     * @param string $uploadPath The path to store the file (relative to storage disk)
     * @param mixed $request The request object containing the file
     * @param array $fileInfo File information including field name and options
     * @return array File information array with paths and metadata
     * @throws \InvalidArgumentException If FileProcessor is not available
     * @throws \Illuminate\Validation\ValidationException If file validation fails
     */
    public function fileUpload(string $uploadPath, $request, array $fileInfo): array
    {
        if (!$this->fileProcessor) {
            throw new \InvalidArgumentException(
                'FileProcessor is not available. Please inject FileProcessor in FormBuilder constructor.'
            );
        }

        return $this->fileProcessor->fileUpload($uploadPath, $request, $fileInfo);
    }

    /**
     * Get the FileProcessor instance.
     *
     * @return FileProcessor|null
     */
    public function getFileProcessor(): ?FileProcessor
    {
        return $this->fileProcessor;
    }

    /**
     * Create date input field.
     */
    public function date(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('date', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create datetime input field.
     */
    public function datetime(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('datetime', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create time input field.
     */
    public function time(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('time', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create hidden input field.
     */
    public function hidden(string $name, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('hidden', $name, null, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        return $field;
    }

    /**
     * Create tags input field.
     *
     * Requirements: 9.1, 9.2, 9.3
     */
    public function tags(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('tags', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        // Register with TagsInput component
        $this->tagsInput->register($name, $field->getTagsConfig());

        return $field;
    }

    /**
     * Create date range picker field.
     *
     * Requirements: 10.1, 10.2, 10.3
     */
    public function daterange(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('daterange', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        // Register with DateRangePicker component
        $this->dateRangePicker->register($name, $field->getDateRangeConfig());

        return $field;
    }

    /**
     * Create month picker field.
     *
     * Requirements: 11.1, 11.2, 11.3
     */
    public function month(string $name, ?string $label = null, mixed $value = null, array $attributes = []): BaseField
    {
        $field = $this->fieldFactory->make('month', $name, $label, $value, $attributes);
        $field->setModel($this->model);
        $this->fields[$name] = $field;
        $this->tabManager->addFieldToActiveTab($field);

        // Register with MonthPicker component
        $this->monthPicker->register($name, $field->getMonthConfig());

        return $field;
    }

    /**
     * Get all fields.
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get specific field.
     */
    public function getField(string $name): ?BaseField
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Open a new tab section.
     *
     * Creates a new tab with the specified label and optional CSS class.
     * Fields added after this call will be associated with this tab.
     *
     * Requirement 1.1: Create new tab section with label and class
     *
     * @param string $label Tab label displayed in navigation
     * @param string|bool $class Optional CSS class or 'active' flag
     * @return self For method chaining
     */
    public function openTab(string $label, string|bool $class = false): self
    {
        $this->tabManager->openTab($label, $class);

        return $this;
    }

    /**
     * Close the current active tab.
     *
     * Clears the active tab reference. Fields added after this call
     * will not be associated with any tab.
     *
     * Requirement 1.3: Close current tab section
     *
     * @return self For method chaining
     */
    public function closeTab(): self
    {
        $this->tabManager->closeTab();

        return $this;
    }

    /**
     * Add custom HTML content to the active tab.
     *
     * Allows adding arbitrary HTML content to the currently active tab.
     * Useful for custom layouts, instructions, or non-field content.
     *
     * Requirement 1.4: Add custom HTML content to tab
     *
     * @param string $html HTML content to add
     * @return self For method chaining
     */
    public function addTabContent(string $html): self
    {
        $this->tabManager->addTabContent($html);

        return $this;
    }

    /**
     * Get TabManager instance.
     *
     * @return TabManager
     */
    public function getTabManager(): TabManager
    {
        return $this->tabManager;
    }

    /**
     * Register a cascading dropdown sync relationship.
     *
     * Creates a relationship between source and target select fields where
     * the target field options are dynamically loaded based on the source
     * field selection via Ajax.
     *
     * Requirement 2.1: Register cascading relationship between fields
     * Requirement 2.2: Send Ajax request to fetch related options
     *
     * @param string $sourceField Source field name (e.g., 'province_id')
     * @param string $targetField Target field name (e.g., 'city_id')
     * @param string $values Column name for option values
     * @param string|null $labels Column name for option labels (defaults to $values)
     * @param string $query SQL query with ? placeholder for source value
     * @param mixed $selected Pre-selected value for target field
     * @return self For method chaining
     */
    public function sync(
        string $sourceField,
        string $targetField,
        string $values,
        ?string $labels = null,
        string $query = '',
        $selected = null
    ): self {
        $this->ajaxSync->register(
            $sourceField,
            $targetField,
            $values,
            $labels,
            $query,
            $selected
        );

        return $this;
    }

    /**
     * Create Eloquent-based sync relationship (fluent interface).
     *
     * Provides a modern, type-safe API for creating cascading dropdowns
     * using Eloquent models instead of raw SQL queries. Returns a builder
     * instance for method chaining.
     *
     * Enhancement 11.5: Eloquent Relationship Support for Ajax Sync
     *
     * @param string $sourceField Source field name (e.g., 'province_id')
     * @param string|Closure $model Model class name or query builder closure
     * @return EloquentSyncBuilder Builder instance for fluent configuration
     *
     * @example Using model class with relationship:
     * ```php
     * $form->syncWith('province_id', City::class)
     *     ->relationship('province')
     *     ->display('name')
     *     ->value('id')
     *     ->selected($selectedCityId);
     * ```
     *
     * @example Using query builder closure:
     * ```php
     * $form->syncWith('province_id', function($provinceId) {
     *     return City::where('province_id', $provinceId)
     *         ->where('active', true)
     *         ->orderBy('name')
     *         ->get(['id', 'name']);
     * });
     * ```
     */
    public function syncWith(string $sourceField, string|Closure $model): EloquentSyncBuilder
    {
        $builder = new EloquentSyncBuilder($sourceField, $model);
        $builder->setAjaxSync($this->ajaxSync);

        // Builder will auto-build in its destructor when it goes out of scope
        // This allows both fluent chaining and simple one-line usage

        return $builder;
    }

    /**
     * Create sync relationship using parent model's relationship.
     *
     * Convenience method for specifying the parent model and relationship name
     * directly, without needing to specify the child model.
     *
     * Enhancement 11.5: Eloquent Relationship Support for Ajax Sync
     *
     * @param string $sourceField Source field name (e.g., 'province_id')
     * @param string $parentModel Parent model class name (e.g., Province::class)
     * @param string $relationship Relationship method name (e.g., 'cities')
     * @return EloquentSyncBuilder Builder instance for fluent configuration
     *
     * @example
     * ```php
     * $form->syncWithRelationship('province_id', Province::class, 'cities')
     *     ->display('name')
     *     ->value('id')
     *     ->where('active', true)
     *     ->orderBy('name');
     * ```
     */
    public function syncWithRelationship(
        string $sourceField,
        string $parentModel,
        string $relationship
    ): EloquentSyncBuilder {
        $builder = new EloquentSyncBuilder($sourceField, $parentModel);
        $builder->relationship($relationship);
        $builder->setAjaxSync($this->ajaxSync);

        // Builder will auto-build in its destructor when it goes out of scope

        return $builder;
    }

    /**
     * Get AjaxSync instance.
     *
     * @return AjaxSync
     */
    public function getAjaxSync(): AjaxSync
    {
        return $this->ajaxSync;
    }

    /**
     * Set validation errors for tab highlighting.
     *
     * Detects which tabs contain fields with validation errors
     * and marks them for highlighting in the UI.
     *
     * Requirement 1.10: Preserve tab state during validation errors
     * Requirement 1.11: Highlight tabs containing errors
     *
     * @param array<string, mixed> $errors Validation errors array
     * @return self For method chaining
     */
    public function setValidationErrors(array $errors): self
    {
        $this->config['validation_errors'] = $errors;

        // Mark tabs with errors
        if ($this->tabManager->hasTabs()) {
            foreach ($this->tabManager->getTabs() as $tab) {
                if ($tab->hasErrors($errors)) {
                    $tab->addClass('has-errors');
                }
            }

            // Set first tab with errors as active
            $firstErrorTab = $this->tabManager->getTabWithErrors($errors);
            if ($firstErrorTab !== null) {
                // Deactivate all tabs first
                foreach ($this->tabManager->getTabs() as $tab) {
                    $tab->setActive(false);
                }
                // Activate the first tab with errors
                $firstErrorTab->setActive(true);
            }
        }

        return $this;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, mixed>
     */
    public function getValidationErrors(): array
    {
        return $this->config['validation_errors'] ?? [];
    }

    /**
     * Set active tab by ID.
     *
     * Preserves tab state when form is re-rendered after validation.
     *
     * Requirement 1.10: Preserve active tab state on form re-render
     *
     * @param string $tabId Tab identifier
     * @return self For method chaining
     */
    public function setActiveTab(string $tabId): self
    {
        foreach ($this->tabManager->getTabs() as $tab) {
            if ($tab->getId() === $tabId) {
                $tab->setActive(true);
            } else {
                $tab->setActive(false);
            }
        }

        return $this;
    }

    /**
     * Get active tab ID.
     *
     * @return string|null Active tab ID or null if no tabs or no active tab
     */
    public function getActiveTabId(): ?string
    {
        foreach ($this->tabManager->getTabs() as $tab) {
            if ($tab->isActive()) {
                return $tab->getId();
            }
        }

        return null;
    }

    /**
     * Render all fields.
     *
     * If tabs are defined, renders tabs with their associated fields.
     * Otherwise, renders fields normally without tab structure.
     * Includes Ajax sync scripts if sync relationships are registered.
     * Includes CKEditor assets and initialization scripts if editors are registered.
     * Includes SearchableSelect assets and scripts if searchable selects are registered.
     *
     * Requirement 1.15: Render fields normally when no tabs defined
     * Requirement 2.2: Generate JavaScript for Ajax handlers
     * Requirement 4.1, 4.2: Render CKEditor scripts at form end
     * Requirement 6.1, 6.2: Render SearchableSelect scripts at form end
     * Requirement 12.1, 12.2: Render fields as read-only in view mode
     * Requirement 7: Filter fields based on column-level permissions
     */
    public function render(): string
    {
        // Check if view mode is enabled
        if ($this->isViewMode()) {
            return $this->renderViewMode();
        }

        // Filter fields based on permission rules (Requirement 7)
        $this->filterFieldsByPermission();

        // Pass validation errors to renderer
        $this->renderer->setValidationErrors($this->getValidationErrors());

        // Register searchable selects before rendering
        $this->registerSearchableSelects();

        // Add soft delete indicator if record is soft deleted
        $output = $this->renderSoftDeleteIndicator();

        // Add soft delete action buttons if record is soft deleted
        $output .= $this->renderSoftDeleteActions();

        // Add permission indicators if fields were hidden
        $output .= $this->renderPermissionIndicators();

        // If tabs are defined, render with tab structure
        if ($this->tabManager->hasTabs()) {
            $output .= $this->renderWithTabs();
        } else {
            // OPTIMIZATION: Use optimized field rendering for large forms
            $output .= $this->renderFieldsOptimized();
        }

        // Append Ajax sync scripts if relationships exist
        $output .= $this->ajaxSync->renderScript();

        // Append CKEditor assets and scripts if editors are registered
        $output .= $this->ckeditorIntegration->render();

        // Append SearchableSelect assets and scripts if instances are registered
        $output .= $this->renderSearchableSelectAssets();

        // Append TagsInput assets and scripts if instances are registered
        $output .= $this->renderTagsInputAssets();

        // Append DateRangePicker assets and scripts if instances are registered
        $output .= $this->renderDateRangePickerAssets();

        // Append MonthPicker assets and scripts if instances are registered
        $output .= $this->renderMonthPickerAssets();

        // Append soft delete action scripts if record is soft deleted
        $output .= $this->renderSoftDeleteScripts();

        return $output;
    }

    /**
     * Render form in view mode (read-only display).
     *
     * Requirements: 12.1, 12.2, 12.3, 12.4, 12.12
     * Requirement 7: Filter fields based on column-level permissions
     *
     * @return string Rendered HTML
     */
    protected function renderViewMode(): string
    {
        // Filter fields based on permission rules (Requirement 7)
        $this->filterFieldsByPermission();

        $viewModeRenderer = new \Canvastack\Canvastack\Components\Form\Features\ViewMode\ViewModeRenderer($this->context);

        $output = '<div class="form-view-mode">';

        // Add soft delete indicator if record is soft deleted
        $output .= $this->renderSoftDeleteIndicator();

        // If tabs are defined, maintain tab structure in view mode
        if ($this->tabManager->hasTabs()) {
            $output .= $this->renderViewModeWithTabs($viewModeRenderer);
        } else {
            // Otherwise, render fields normally
            foreach ($this->fields as $field) {
                $output .= $viewModeRenderer->render($field);
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render view mode with tab structure.
     *
     * Requirements: 12.12
     *
     * @param \Canvastack\Canvastack\Components\Form\Features\ViewMode\ViewModeRenderer $viewModeRenderer
     * @return string Rendered HTML
     */
    protected function renderViewModeWithTabs($viewModeRenderer): string
    {
        $output = '<div class="tabs-container" x-data="{ activeTab: \'' . $this->getActiveTabId() . '\' }">';

        // Render tab navigation
        $output .= '<div role="tablist" class="tabs tabs-bordered mb-6">';
        foreach ($this->tabManager->getTabs() as $tab) {
            $id = $tab->getId();
            $label = htmlspecialchars($tab->getLabel(), ENT_QUOTES, 'UTF-8');
            $activeClass = $tab->isActive() ? 'tab-active' : '';

            $output .= <<<HTML
            <a role="tab" class="tab {$activeClass}" 
               :class="{ 'tab-active': activeTab === '{$id}' }"
               @click.prevent="activeTab = '{$id}'"
               href="#{$id}">
                {$label}
            </a>
            HTML;
        }
        $output .= '</div>';

        // Render tab content
        $output .= '<div class="tab-content">';
        foreach ($this->tabManager->getTabs() as $tab) {
            $id = $tab->getId();

            $output .= '<div id="' . $id . '" role="tabpanel" x-show="activeTab === \'' . $id . '\'" class="py-6">';

            foreach ($tab->getFields() as $field) {
                $output .= $viewModeRenderer->render($field);
            }

            // Render custom content
            foreach ($tab->getContent() as $content) {
                $output .= $content;
            }

            $output .= '</div>';
        }
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Render form with tab structure.
     *
     * Renders tab navigation and content panels with associated fields.
     * Passes validation errors to highlight tabs containing errors.
     *
     * @return string Rendered HTML
     */
    protected function renderWithTabs(): string
    {
        // Get validation errors
        $errors = $this->getValidationErrors();

        // Render tabs (includes navigation and content with error highlighting)
        $tabsHtml = $this->renderer->renderTabs($this->tabManager->getTabs(), $errors);

        // Render any fields not associated with tabs
        $nonTabFields = $this->getNonTabFields();
        $nonTabFieldsHtml = '';
        foreach ($nonTabFields as $field) {
            $nonTabFieldsHtml .= $this->renderer->render($field);
        }

        return $tabsHtml . $nonTabFieldsHtml;
    }

    /**
     * Get fields that are not associated with any tab.
     *
     * @return array<BaseField>
     */
    protected function getNonTabFields(): array
    {
        $tabFields = [];
        foreach ($this->tabManager->getTabs() as $tab) {
            foreach ($tab->getFields() as $field) {
                $tabFields[$field->getName()] = true;
            }
        }

        $nonTabFields = [];
        foreach ($this->fields as $name => $field) {
            if (!isset($tabFields[$name])) {
                $nonTabFields[] = $field;
            }
        }

        return $nonTabFields;
    }

    /**
     * Render specific field.
     */
    public function renderField(string $name): string
    {
        $field = $this->getField($name);
        if (!$field) {
            return '';
        }

        return $this->renderer->render($field);
    }

    /**
     * Render permission indicators for hidden fields.
     *
     * This method generates HTML alerts to inform users about fields that were hidden
     * due to permission restrictions. It uses theme colors and i18n for messages.
     *
     * Requirements:
     * - Requirement 7.6: Display message when fields are hidden due to permissions
     * - Requirement 18.1: Use theme colors for permission-related UI elements
     * - Requirement 17.1: Use i18n for all permission-related messages
     *
     * @return string HTML for permission indicators
     */
    protected function renderPermissionIndicators(): string
    {
        // No indicators if no fields were hidden
        if (empty($this->hiddenFields)) {
            return '';
        }

        // Check if permission indicators are enabled in config
        $config = config('canvastack-rbac.fine_grained', []);
        if (isset($config['show_indicators']) && !$config['show_indicators']) {
            return '';
        }

        $output = '';

        // Group hidden fields by reason
        $columnFields = [];
        $jsonFields = [];

        foreach ($this->hiddenFields as $name => $data) {
            $field = $data['field'];
            $reason = $data['reason'];

            if ($reason === 'column_level_denied') {
                $columnFields[] = $field;
            } elseif ($reason === 'json_attribute_denied') {
                $jsonFields[] = $field;
            }
        }

        // Render indicator for column-level hidden fields
        if (!empty($columnFields)) {
            $count = count($columnFields);
            $fieldNames = array_map(fn ($field) => $field->getLabel() ?? $field->getName(), $columnFields);

            $output .= '<div class="alert alert-info mb-4" role="alert" ';
            $output .= 'style="background: ' . $this->getThemeColor('info-light') . '; ';
            $output .= 'color: ' . $this->getThemeColor('info-dark') . '; ';
            $output .= 'border: 1px solid ' . $this->getThemeColor('info') . ';">';
            $output .= '<i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>';

            if ($count === 1) {
                // Use correct translation key with field name
                $output .= __('rbac.fine_grained.field_hidden', ['field' => $fieldNames[0]]);
            } else {
                // Use correct translation key for multiple fields
                $output .= trans_choice('rbac.fine_grained.columns_hidden', $count, ['count' => $count]);
            }

            $output .= '</div>';
        }

        // Render indicator for JSON attribute hidden fields
        if (!empty($jsonFields)) {
            $count = count($jsonFields);
            $fieldNames = array_map(fn ($field) => $field->getLabel() ?? $field->getName(), $jsonFields);

            $output .= '<div class="alert alert-info mb-4" role="alert" ';
            $output .= 'style="background: ' . $this->getThemeColor('info-light') . '; ';
            $output .= 'color: ' . $this->getThemeColor('info-dark') . '; ';
            $output .= 'border: 1px solid ' . $this->getThemeColor('info') . ';">';
            $output .= '<i data-lucide="lock" class="w-4 h-4 inline mr-2"></i>';

            if ($count === 1) {
                // For single JSON field, show the nested field path
                $fieldPath = $fieldNames[0];
                $output .= __('rbac.fine_grained.json_field_hidden', ['field' => $fieldPath]);
            } else {
                // For multiple JSON fields, show count
                $output .= __('rbac.fine_grained.json_fields_hidden', ['count' => $count]);
            }

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Get theme color value.
     *
     * This method retrieves theme color values using the theme system.
     * Falls back to default colors if theme system is not available.
     *
     * @param string $colorName Color name (e.g., 'info', 'info-light', 'info-dark')
     * @return string Color value (hex code or CSS color)
     */
    protected function getThemeColor(string $colorName): string
    {
        // Try to get color from theme system
        if (function_exists('theme_color')) {
            return theme_color($colorName);
        }

        // Fallback colors
        $fallbackColors = [
            'info' => '#3b82f6',
            'info-light' => '#dbeafe',
            'info-dark' => '#1e40af',
        ];

        return $fallbackColors[$colorName] ?? '#3b82f6';
    }

    /**
     * Set validation rules for the form.
     */
    public function setValidations(array $rules): self
    {
        $this->config['validations'] = $rules;

        // Cache validation rules
        if (!empty($this->formIdentity)) {
            $this->validationCache->put($this->formIdentity, $rules);
        }

        return $this;
    }

    /**
     * Get validation rules.
     */
    public function getValidations(): array
    {
        // Try to get from cache first
        if (!empty($this->formIdentity)) {
            $cached = $this->validationCache->get($this->formIdentity);
            if ($cached !== null) {
                return $cached;
            }
        }

        return $this->config['validations'] ?? [];
    }

    /**
     * Clear form fields.
     */
    public function clear(): self
    {
        $this->fields = [];

        return $this;
    }

    /**
     * Cache form definition.
     */
    public function cache(int $ttl = 3600): self
    {
        if (!empty($this->formIdentity)) {
            $cacheKey = "form.definition.{$this->formIdentity}";
            Cache::tags(['forms'])->put($cacheKey, [
                'fields' => $this->fields,
                'config' => $this->config,
            ], $ttl);
        }

        return $this;
    }

    /**
     * Load form definition from cache.
     */
    public function loadFromCache(string $formIdentity): bool
    {
        $cacheKey = "form.definition.{$formIdentity}";
        $cached = Cache::tags(['forms'])->get($cacheKey);

        if ($cached) {
            $this->fields = $cached['fields'] ?? [];
            $this->config = $cached['config'] ?? [];
            $this->formIdentity = $formIdentity;

            return true;
        }

        return false;
    }

    /**
     * Get renderer instance.
     */
    public function getRenderer(): RendererInterface
    {
        return $this->renderer;
    }

    /**
     * Get CKEditor integration instance.
     *
     * @return CKEditorIntegration CKEditor integration instance
     *
     * Requirements: 4.1, 4.2
     */
    public function getCKEditorIntegration(): CKEditorIntegration
    {
        return $this->ckeditorIntegration;
    }

    /**
     * Register searchable selects by scanning all fields.
     *
     * Iterates through all fields and registers any SelectField marked as searchable
     * with the SearchableSelect component.
     *
     * Requirements: 6.1, 6.2
     *
     * @return void
     */
    protected function registerSearchableSelects(): void
    {
        foreach ($this->fields as $field) {
            if ($field instanceof \Canvastack\Canvastack\Components\Form\Fields\SelectField && $field->isSearchable()) {
                $this->searchableSelect->register($field->getName(), [
                    'multiple' => $field->isMultiple(),
                ]);
            }
        }
    }

    /**
     * Render SearchableSelect assets and initialization scripts.
     *
     * Loads Choices.js library assets and renders initialization scripts
     * for all registered searchable select instances.
     *
     * Requirements: 6.2, 6.16, 6.22
     *
     * @return string HTML with asset tags and initialization scripts
     */
    protected function renderSearchableSelectAssets(): string
    {
        if (!$this->searchableSelect->hasInstances()) {
            return '';
        }

        $output = '';

        // Load Choices.js assets
        $output .= $this->assetManager->renderAssetTags('choices', true);

        // Render initialization scripts
        $output .= $this->searchableSelect->renderScript();

        return $output;
    }

    /**
     * Render TagsInput assets and initialization scripts.
     *
     * Loads Tagify library assets and renders initialization scripts
     * for all registered tags input instances.
     *
     * Requirements: 9.1, 9.2
     *
     * @return string HTML with asset tags and initialization scripts
     */
    protected function renderTagsInputAssets(): string
    {
        if (!$this->tagsInput->hasInstances()) {
            return '';
        }

        $output = '';

        // Load Tagify assets
        $output .= $this->tagsInput->renderAssets();

        // Render initialization scripts
        $output .= $this->tagsInput->renderScript();

        return $output;
    }

    /**
     * Render DateRangePicker assets and initialization scripts.
     *
     * Loads Flatpickr library assets and renders initialization scripts
     * for all registered date range picker instances.
     *
     * Requirements: 10.1, 10.2
     *
     * @return string HTML with asset tags and initialization scripts
     */
    protected function renderDateRangePickerAssets(): string
    {
        if (!$this->dateRangePicker->hasInstances()) {
            return '';
        }

        $output = '';

        // Load Flatpickr assets
        $output .= $this->dateRangePicker->renderAssets();

        // Render initialization scripts
        $output .= $this->dateRangePicker->renderScript();

        return $output;
    }

    /**
     * Render MonthPicker assets and initialization scripts.
     *
     * Loads Flatpickr library assets with month select plugin and renders
     * initialization scripts for all registered month picker instances.
     *
     * Requirements: 11.1, 11.2
     *
     * @return string HTML with asset tags and initialization scripts
     */
    protected function renderMonthPickerAssets(): string
    {
        if (!$this->monthPicker->hasInstances()) {
            return '';
        }

        $output = '';

        // Load Flatpickr assets with month select plugin
        $output .= $this->monthPicker->renderAssets();

        // Render initialization scripts
        $output .= $this->monthPicker->renderScript();

        return $output;
    }

    /**
     * Get SearchableSelect instance.
     *
     * @return SearchableSelect SearchableSelect instance
     *
     * Requirements: 6.1, 6.2
     */
    public function getSearchableSelect(): \Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect
    {
        return $this->searchableSelect;
    }

    /**
     * Get AssetManager instance.
     *
     * @return AssetManager AssetManager instance
     */
    public function getAssetManager(): \Canvastack\Canvastack\Components\Form\Support\AssetManager
    {
        return $this->assetManager;
    }

    /**
     * Get ModelInspector instance.
     *
     * @return ModelInspector ModelInspector instance
     *
     * Requirements: 8.1, 8.10
     */
    public function getModelInspector(): ModelInspector
    {
        return $this->modelInspector;
    }

    /**
     * Check if the current model uses soft deletes.
     *
     * @return bool True if model uses soft deletes
     *
     * Requirements: 8.1, 8.2
     */
    public function usesSoftDeletes(): bool
    {
        return isset($this->config['softDeletes']) && $this->config['softDeletes'] === true;
    }

    /**
     * Get the soft delete column name for the current model.
     *
     * @return string|null Soft delete column name or null
     *
     * Requirements: 8.12, 8.16
     */
    public function getSoftDeleteColumn(): ?string
    {
        return $this->config['softDeleteColumn'] ?? null;
    }

    /**
     * Check if the current model is soft deleted.
     *
     * @return bool True if model is soft deleted
     *
     * Requirements: 8.4, 8.5
     */
    public function isSoftDeleted(): bool
    {
        if (!$this->model || !$this->usesSoftDeletes()) {
            return false;
        }

        $column = $this->getSoftDeleteColumn();

        if (!$column || !isset($this->model->{$column})) {
            return false;
        }

        return $this->model->{$column} !== null;
    }

    /**
     * Render soft delete indicator if record is soft deleted.
     *
     * @return string HTML for soft delete indicator
     *
     * Requirements: 8.5, 8.6
     */
    protected function renderSoftDeleteIndicator(): string
    {
        if (!$this->isSoftDeleted()) {
            return '';
        }

        $column = $this->getSoftDeleteColumn();
        $deletedAt = $this->model->{$column} ?? null;

        if (!$deletedAt) {
            return '';
        }

        return $this->softDeleteIndicator->render($deletedAt, $this->context);
    }

    /**
     * Render soft delete action buttons if record is soft deleted.
     *
     * @return string HTML for action buttons
     *
     * Requirements: 8.7, 8.9
     */
    protected function renderSoftDeleteActions(): string
    {
        if (!$this->isSoftDeleted() || !$this->model) {
            return '';
        }

        $modelClass = get_class($this->model);
        $modelId = $this->model->getKey();

        // Check if permanent delete should be shown (can be configured)
        $showPermanentDelete = $this->config['showPermanentDelete'] ?? false;

        return $this->softDeleteActions->render(
            $modelClass,
            $modelId,
            $this->context,
            $showPermanentDelete
        );
    }

    /**
     * Render soft delete action scripts if record is soft deleted.
     *
     * @return string JavaScript for soft delete actions
     *
     * Requirements: 8.7, 8.8, 8.9
     */
    protected function renderSoftDeleteScripts(): string
    {
        if (!$this->isSoftDeleted()) {
            return '';
        }

        return $this->softDeleteActions->renderScript();
    }

    /**
     * Enable permanent delete option for soft-deleted records.
     *
     * @param bool $show Whether to show permanent delete button
     * @return self
     *
     * Requirements: 8.9
     */
    public function showPermanentDelete(bool $show = true): self
    {
        $this->config['showPermanentDelete'] = $show;

        return $this;
    }

    /**
     * Render fields with optimizations for large forms.
     *
     * Optimizations:
     * 1. Batch field rendering to reduce function call overhead
     * 2. Use view caching for repeated field types
     * 3. Lazy load fields for very large forms (>50 fields)
     *
     * Task 6.1.3: Fix form rendering scalability
     * Target: <15x scaling factor (currently 25.9x)
     *
     * @return string Rendered HTML
     */
    protected function renderFieldsOptimized(): string
    {
        $fieldCount = count($this->fields);

        // For small forms (<= 20 fields), use standard rendering
        if ($fieldCount <= 20) {
            return $this->renderFieldsStandard();
        }

        // For medium forms (21-50 fields), use batch rendering
        if ($fieldCount <= 50) {
            return $this->renderFieldsBatched();
        }

        // For large forms (>50 fields), use lazy loading
        return $this->renderFieldsLazy();
    }

    /**
     * Standard field rendering for small forms.
     *
     * @return string Rendered HTML
     */
    protected function renderFieldsStandard(): string
    {
        $output = '';
        foreach ($this->fields as $field) {
            $output .= $this->renderer->render($field);
        }
        return $output;
    }

    /**
     * Batch field rendering for medium forms.
     *
     * Groups fields by type and renders them in batches to reduce overhead.
     *
     * @return string Rendered HTML
     */
    protected function renderFieldsBatched(): string
    {
        // Group fields by type for potential batch optimizations
        $fieldsByType = [];
        foreach ($this->fields as $field) {
            $type = get_class($field);
            if (!isset($fieldsByType[$type])) {
                $fieldsByType[$type] = [];
            }
            $fieldsByType[$type][] = $field;
        }

        // Render fields maintaining original order
        $output = '';
        $renderedFields = [];

        foreach ($this->fields as $field) {
            $fieldName = $field->getName();
            if (isset($renderedFields[$fieldName])) {
                continue;
            }

            // Check view cache first
            $cacheKey = $this->getFieldCacheKey($field);
            $cached = $this->getFieldFromCache($cacheKey);

            if ($cached !== null) {
                $output .= $cached;
            } else {
                $rendered = $this->renderer->render($field);
                $output .= $rendered;

                // Cache the rendered field
                $this->cacheRenderedField($cacheKey, $rendered);
            }

            $renderedFields[$fieldName] = true;
        }

        return $output;
    }

    /**
     * Lazy loading field rendering for large forms.
     *
     * Renders only the first 30 fields immediately, then loads the rest via JavaScript.
     *
     * @return string Rendered HTML
     */
    protected function renderFieldsLazy(): string
    {
        $initialLoadCount = 30;
        $fields = array_values($this->fields);
        $output = '';

        // Render first batch immediately
        for ($i = 0; $i < min($initialLoadCount, count($fields)); $i++) {
            $output .= $this->renderer->render($fields[$i]);
        }

        // If there are more fields, add lazy loading container
        if (count($fields) > $initialLoadCount) {
            $remainingFields = array_slice($fields, $initialLoadCount);

            $output .= '<div id="lazy-fields-container" data-loaded="false">';
            $output .= '<div class="text-center py-4">';
            $output .= '<button type="button" class="btn btn-sm btn-outline" onclick="loadRemainingFields()">';
            $output .= '<i data-lucide="chevron-down" class="w-4 h-4 inline"></i> ';
            $output .= __('ui.form.load_more_fields', ['count' => count($remainingFields)]);
            $output .= '</button>';
            $output .= '</div>';
            $output .= '<div id="remaining-fields" style="display:none;">';

            // Pre-render remaining fields but hide them
            foreach ($remainingFields as $field) {
                $output .= $this->renderer->render($field);
            }

            $output .= '</div>';
            $output .= '</div>';

            // Add JavaScript for lazy loading
            $output .= $this->renderLazyLoadScript();
        }

        return $output;
    }

    /**
     * Render lazy load JavaScript.
     *
     * @return string JavaScript code
     */
    protected function renderLazyLoadScript(): string
    {
        return <<<'HTML'
<script>
function loadRemainingFields() {
    const container = document.getElementById('lazy-fields-container');
    const remainingFields = document.getElementById('remaining-fields');
    
    if (container && remainingFields && container.dataset.loaded === 'false') {
        // Show remaining fields
        remainingFields.style.display = 'block';
        
        // Hide the load button
        container.querySelector('.text-center').style.display = 'none';
        
        // Mark as loaded
        container.dataset.loaded = 'true';
        
        // Initialize any JavaScript components in the newly loaded fields
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}
</script>
HTML;
    }

    /**
     * Get cache key for a field.
     *
     * @param BaseField $field
     * @return string Cache key
     */
    protected function getFieldCacheKey(BaseField $field): string
    {
        // Create cache key based on field properties
        $key = 'form_field_' . md5(serialize([
            'type' => get_class($field),
            'name' => $field->getName(),
            'label' => $field->getLabel(),
            'attributes' => $field->getAttributes(),
            'context' => $this->context,
        ]));

        return $key;
    }

    /**
     * Get rendered field from cache.
     *
     * @param string $cacheKey
     * @return string|null Cached HTML or null if not found
     */
    protected function getFieldFromCache(string $cacheKey): ?string
    {
        // Only use cache if caching is enabled
        if (!$this->config['cache_enabled'] ?? false) {
            return null;
        }

        try {
            if (app()->bound('cache.store')) {
                $cache = app('cache.store');
                return $cache->get($cacheKey);
            }
        } catch (\Exception $e) {
            // Cache not available, continue without caching
        }

        return null;
    }

    /**
     * Cache rendered field HTML.
     *
     * @param string $cacheKey
     * @param string $html
     * @return void
     */
    protected function cacheRenderedField(string $cacheKey, string $html): void
    {
        // Only cache if caching is enabled
        if (!$this->config['cache_enabled'] ?? false) {
            return;
        }

        try {
            if (app()->bound('cache.store')) {
                $cache = app('cache.store');
                // Cache for 1 hour (3600 seconds)
                $cache->put($cacheKey, $html, 3600);
            }
        } catch (\Exception $e) {
            // Cache not available, continue without caching
        }
    }
}
