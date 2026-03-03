<?php

namespace Canvastack\Canvastack\Components\Form\Features\Ajax;

/**
 * AjaxSync - Manages cascading dropdown relationships and Ajax requests.
 *
 * This class handles the registration and management of cascading dropdown
 * relationships, encrypts query parameters for security, and generates
 * JavaScript for Ajax handlers.
 */
class AjaxSync
{
    protected QueryEncryption $encryption;

    protected array $relationships = [];

    public function __construct(QueryEncryption $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Register a sync relationship between source and target fields.
     *
     * @param string $sourceField Source field name (e.g., 'province_id')
     * @param string $targetField Target field name (e.g., 'city_id')
     * @param string $values Column name for option values
     * @param string|null $labels Column name for option labels (defaults to $values)
     * @param string $query SQL query with ? placeholder for source value
     * @param mixed $selected Pre-selected value for target field
     * @return void
     */
    public function register(
        string $sourceField,
        string $targetField,
        string $values,
        ?string $labels,
        string $query,
        $selected = null
    ): void {
        // Normalize query (remove extra whitespace)
        $normalizedQuery = $this->normalizeQuery($query);

        // Encrypt sensitive parameters
        $relationship = [
            'source' => $sourceField,
            'target' => $targetField,
            'values' => $this->encryption->encrypt($values),
            'labels' => $this->encryption->encrypt($labels ?? $values),
            'query' => $this->encryption->encrypt($normalizedQuery),
            'selected' => $this->encryption->encrypt($selected),
        ];

        $this->relationships[] = $relationship;
    }

    /**
     * Get all registered relationships.
     *
     * @return array
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Check if a relationship exists between source and target fields.
     *
     * @param string $source Source field name
     * @param string $target Target field name
     * @return bool
     */
    public function hasRelationship(string $source, string $target): bool
    {
        foreach ($this->relationships as $rel) {
            if ($rel['source'] === $source && $rel['target'] === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render JavaScript for Ajax sync functionality.
     *
     * Generates JavaScript code that:
     * - Listens for changes on source fields
     * - Sends Ajax requests to fetch options
     * - Populates target fields with results
     * - Handles loading states and errors
     * - Supports multi-level cascading (e.g., Country → Province → City)
     *
     * Requirement 2.11: Support multiple cascading levels
     *
     * @return string JavaScript code
     */
    public function renderScript(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        $ajaxUrl = route('canvastack.ajax.sync');
        $relationships = json_encode($this->relationships);
        $csrfToken = csrf_token();

        return <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const relationships = {$relationships};
    const ajaxUrl = '{$ajaxUrl}';
    const csrfToken = '{$csrfToken}';
    
    // Build dependency map for multi-level cascading
    const dependencyMap = {};
    relationships.forEach(function(rel) {
        if (!dependencyMap[rel.source]) {
            dependencyMap[rel.source] = [];
        }
        dependencyMap[rel.source].push(rel);
    });
    
    relationships.forEach(function(rel) {
        const sourceField = document.querySelector('[name="' + rel.source + '"]');
        const targetField = document.querySelector('[name="' + rel.target + '"]');
        
        if (sourceField && targetField) {
            // Disable target initially
            targetField.disabled = true;
            
            // Listen for source changes
            sourceField.addEventListener('change', function() {
                fetchOptions(rel, sourceField.value, targetField);
                
                // Reset all dependent fields in the cascade chain
                resetDependentFields(rel.target);
            });
            
            // Trigger initial load if source has value (for pre-selection support)
            if (sourceField.value) {
                fetchOptions(rel, sourceField.value, targetField);
            }
        }
    });
    
    function resetDependentFields(fieldName) {
        // Find all fields that depend on this field
        const dependents = dependencyMap[fieldName] || [];
        
        dependents.forEach(function(depRel) {
            const depField = document.querySelector('[name="' + depRel.target + '"]');
            if (depField) {
                depField.disabled = true;
                depField.innerHTML = '<option value="">Select...</option>';
                depField.value = '';
                
                // Recursively reset fields that depend on this one
                resetDependentFields(depRel.target);
            }
        });
    }
    
    function fetchOptions(rel, sourceValue, targetField) {
        if (!sourceValue) {
            targetField.disabled = true;
            targetField.innerHTML = '<option value="">Select...</option>';
            
            // Reset dependent fields
            resetDependentFields(rel.target);
            return;
        }
        
        // Show loading indicator with dark mode support
        targetField.disabled = true;
        targetField.classList.add('loading', 'loading-spinner');
        targetField.innerHTML = '<option value="">Loading...</option>';
        
        // Fetch options
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                relationship: rel,
                sourceValue: sourceValue
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            targetField.classList.remove('loading', 'loading-spinner');
            if (data.success) {
                populateOptions(targetField, data.options, rel.selected);
                
                // Trigger change event to cascade to next level if value is pre-selected
                if (targetField.value) {
                    targetField.dispatchEvent(new Event('change'));
                }
            } else {
                targetField.innerHTML = '<option value="">Error loading options</option>';
                console.error('Ajax sync error:', data.message || 'Unknown error');
            }
        })
        .catch(error => {
            targetField.classList.remove('loading', 'loading-spinner');
            console.error('Ajax sync error:', error);
            targetField.innerHTML = '<option value="">Error loading options</option>';
        });
    }
    
    function populateOptions(targetField, options, selected) {
        targetField.innerHTML = '<option value="">Select...</option>';
        
        options.forEach(function(option) {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.label;
            
            // Pre-selection support: select the option if it matches
            if (selected && option.value == selected) {
                opt.selected = true;
            }
            
            targetField.appendChild(opt);
        });
        
        targetField.disabled = false;
    }
});
</script>
JS;
    }

    /**
     * Normalize SQL query by removing extra whitespace.
     *
     * @param string $query SQL query
     * @return string Normalized query
     */
    protected function normalizeQuery(string $query): string
    {
        return trim(preg_replace('/\s\s+/', ' ', $query));
    }

    /**
     * Legacy sync() method for backward compatibility.
     *
     * Simplified API that registers a sync relationship with minimal parameters.
     * This method provides backward compatibility with older CanvaStack versions
     * that used a simpler sync API.
     *
     * Validates Requirements 2.18, 18.5 (Backward compatibility)
     *
     * @param string $field Target field name
     * @param string $query SQL query to fetch options
     * @param mixed $selected Pre-selected value
     * @return void
     */
    public function sync(string $field, string $query, $selected = null): void
    {
        // Normalize query
        $normalizedQuery = $this->normalizeQuery($query);

        // Encrypt the query
        $encryptedQuery = $this->encryption->encrypt($normalizedQuery);

        // Store in a simpler format for legacy compatibility
        $this->relationships[$field] = [
            'field' => $field,
            'encrypted_query' => $encryptedQuery,
            'selected' => $selected, // Store unencrypted for easy retrieval
        ];
    }

    /**
     * Get registered relationships in legacy format.
     *
     * Returns relationships registered via the legacy sync() method.
     *
     * @return array
     */
    public function getRegisteredRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Render JavaScript for legacy sync relationships.
     *
     * Generates JavaScript configuration for Ajax sync functionality.
     *
     * @return string JavaScript code
     */
    public function renderJavaScript(): string
    {
        if (empty($this->relationships)) {
            return '';
        }

        $config = [];
        foreach ($this->relationships as $field => $data) {
            $config[$field] = [
                'encrypted_query' => $data['encrypted_query'],
                'selected' => $data['selected'],
            ];
        }

        $jsonConfig = json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP);

        return <<<JS
<script>
var ajaxSyncConfig = {$jsonConfig};
// Additional JavaScript for handling Ajax sync would go here
</script>
JS;
    }
}
