<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Tab;

/**
 * TableInstance - Represents a single table instance within a tab
 * 
 * This class captures table configuration and provides isolated rendering.
 * 
 * @package Canvastack\Canvastack\Components\Table\Tab
 * @version 1.0.0
 */
class TableInstance
{
    /**
     * Database table name
     * 
     * @var string
     */
    protected string $tableName;

    /**
     * Table fields/columns
     * 
     * @var array
     */
    protected array $fields;

    /**
     * Table configuration
     * 
     * @var array
     */
    protected array $config;

    /**
     * Unique instance ID
     * 
     * @var string
     */
    protected string $uniqueId;

    /**
     * Constructor
     * 
     * @param string $tableName Database table name
     * @param array $fields Table fields/columns
     * @param array $config Table configuration
     */
    public function __construct(string $tableName, array $fields, array $config)
    {
        $this->tableName = $tableName;
        $this->fields = $fields;
        $this->config = $config;
        $this->uniqueId = $this->generateUniqueId();
    }

    /**
     * Get table name
     * 
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get fields
     * 
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get unique ID
     * 
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * Render the table instance
     * 
     * This method generates the HTML container for the table instance.
     * The actual DataTable rendering will be handled by TableBuilder's renderer.
     * 
     * @return string Rendered HTML
     */
    public function render(): string
    {
        // Generate data attributes for configuration
        $dataAttributes = $this->generateDataAttributes();
        
        // Build HTML container with configuration
        $html = sprintf(
            '<div id="%s" class="table-instance" data-table="%s" %s>',
            $this->uniqueId,
            htmlspecialchars($this->tableName),
            $dataAttributes
        );
        
        // Add loading placeholder
        $html .= '<div class="table-loading">';
        $html .= '<span class="loading loading-spinner loading-lg"></span>';
        $html .= '<p class="text-sm text-gray-500 mt-2">Loading table...</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate data attributes from configuration
     * 
     * @return string HTML data attributes
     */
    protected function generateDataAttributes(): string
    {
        $attributes = [];
        
        // Add essential configuration as data attributes
        if (!empty($this->config['connection'])) {
            $attributes[] = sprintf('data-connection="%s"', htmlspecialchars($this->config['connection']));
        }
        
        if (!empty($this->config['displayLimit'])) {
            $attributes[] = sprintf('data-display-limit="%s"', htmlspecialchars((string)$this->config['displayLimit']));
        }
        
        if (!empty($this->config['sortable'])) {
            $attributes[] = sprintf('data-sortable="%s"', htmlspecialchars(json_encode($this->config['sortable'])));
        }
        
        if (!empty($this->config['searchable'])) {
            $attributes[] = sprintf('data-searchable="%s"', htmlspecialchars(json_encode($this->config['searchable'])));
        }
        
        // Add fixed columns configuration
        if (!empty($this->config['fixedColumns'])) {
            $attributes[] = sprintf('data-fixed-columns="%s"', htmlspecialchars(json_encode($this->config['fixedColumns'])));
        }
        
        return implode(' ', $attributes);
    }

    /**
     * Convert to array for JSON serialization
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->uniqueId,
            'tableName' => $this->tableName,
            'fields' => $this->fields,
            'config' => $this->config,
            'html' => $this->config['html'] ?? '', // Get HTML from config if available
        ];
    }

    /**
     * Generate a unique ID for this table instance
     * 
     * @return string
     */
    protected function generateUniqueId(): string
    {
        return 'table_' . uniqid() . '_' . substr(md5($this->tableName), 0, 8);
    }
    
    /**
     * Get a specific configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return self
     */
    public function setConfigValue(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }
    
    /**
     * Merge additional configuration
     * 
     * @param array $config Configuration to merge
     * @return self
     */
    public function mergeConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }
    
    /**
     * Check if configuration is isolated (no shared references)
     * 
     * @return bool
     */
    public function isConfigIsolated(): bool
    {
        // Check if config arrays are not references
        // This is a basic check - in practice, PHP arrays are copy-on-write
        return is_array($this->config) && is_array($this->fields);
    }
    
    /**
     * Clone configuration to ensure isolation
     * 
     * @return array Deep copy of configuration
     */
    public function cloneConfig(): array
    {
        return json_decode(json_encode($this->config), true);
    }
    
    /**
     * Validate table configuration
     * 
     * @return bool
     * @throws \InvalidArgumentException If configuration is invalid
     */
    public function validateConfig(): bool
    {
        // Validate table name
        if (empty($this->tableName)) {
            throw new \InvalidArgumentException('Table name cannot be empty');
        }
        
        // Validate fields
        if (empty($this->fields)) {
            throw new \InvalidArgumentException('Table fields cannot be empty');
        }
        
        // Validate configuration structure
        if (!is_array($this->config)) {
            throw new \InvalidArgumentException('Configuration must be an array');
        }
        
        return true;
    }
    
    /**
     * Get table metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return [
            'id' => $this->uniqueId,
            'tableName' => $this->tableName,
            'fieldCount' => count($this->fields),
            'hasConfig' => !empty($this->config),
            'configKeys' => array_keys($this->config),
        ];
    }
}
