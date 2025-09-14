<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Utils;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use ReflectionClass;
use ReflectionMethod;

/**
 * RelationshipIntrospector
 * 
 * Automatically detects and analyzes Eloquent model relationships
 * to build foreign key mappings and table schemas.
 */
class RelationshipIntrospector
{
    /**
     * Introspect model relationships and build foreign key mappings
     *
     * @param Model|Builder $model
     * @param string $relationMethod
     * @return array
     */
    public static function analyzeRelationship($model, string $relationMethod): array
    {
        try {
            // Convert Builder to Model instance if needed
            if ($model instanceof Builder) {
                $model = $model->getModel();
            }
            
            // Check if relation method exists
            if (!method_exists($model, $relationMethod)) {
                return [
                    'success' => false,
                    'error' => "Relation method '{$relationMethod}' not found in model " . get_class($model)
                ];
            }

            // Get the relationship instance
            $relation = $model->{$relationMethod}();
            
            // Analyze based on relationship type
            $analysis = self::analyzeRelationshipType($relation, $model);
            
            return [
                'success' => true,
                'relation_type' => class_basename($relation),
                'foreign_keys' => $analysis['foreign_keys'],
                'tables' => $analysis['tables'],
                'pivot_table' => $analysis['pivot_table'] ?? null,
                'related_model' => get_class($relation->getRelated()),
                'related_table' => $relation->getRelated()->getTable()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to analyze relationship: " . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze specific relationship type and extract foreign keys
     *
     * @param mixed $relation
     * @param Model $model
     * @return array
     */
    private static function analyzeRelationshipType($relation, Model $model): array
    {
        $baseTable = $model->getTable();
        $relatedTable = $relation->getRelated()->getTable();
        
        if ($relation instanceof BelongsToMany) {
            return self::analyzeBelongsToMany($relation, $baseTable, $relatedTable);
        }
        
        if ($relation instanceof HasMany) {
            return self::analyzeHasMany($relation, $baseTable, $relatedTable);
        }
        
        if ($relation instanceof BelongsTo) {
            return self::analyzeBelongsTo($relation, $baseTable, $relatedTable);
        }
        
        if ($relation instanceof HasOne) {
            return self::analyzeHasOne($relation, $baseTable, $relatedTable);
        }
        
        return [
            'foreign_keys' => [],
            'tables' => [$baseTable, $relatedTable]
        ];
    }

    /**
     * Analyze BelongsToMany relationship (many-to-many with pivot table)
     *
     * @param BelongsToMany $relation
     * @param string $baseTable
     * @param string $relatedTable
     * @return array
     */
    private static function analyzeBelongsToMany(BelongsToMany $relation, string $baseTable, string $relatedTable): array
    {
        $pivotTable = $relation->getTable();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();
        $parentKey = $relation->getParentKeyName();
        $relatedKey = $relation->getRelatedKeyName();
        
        return [
            'foreign_keys' => [
                "{$pivotTable}.{$foreignPivotKey}" => "{$baseTable}.{$parentKey}",
                "{$relatedTable}.{$relatedKey}" => "{$pivotTable}.{$relatedPivotKey}"
            ],
            'tables' => [$baseTable, $pivotTable, $relatedTable],
            'pivot_table' => $pivotTable
        ];
    }

    /**
     * Analyze HasMany relationship (one-to-many)
     *
     * @param HasMany $relation
     * @param string $baseTable
     * @param string $relatedTable
     * @return array
     */
    private static function analyzeHasMany(HasMany $relation, string $baseTable, string $relatedTable): array
    {
        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();
        
        return [
            'foreign_keys' => [
                "{$relatedTable}.{$foreignKey}" => "{$baseTable}.{$localKey}"
            ],
            'tables' => [$baseTable, $relatedTable]
        ];
    }

    /**
     * Analyze BelongsTo relationship (many-to-one)
     *
     * @param BelongsTo $relation
     * @param string $baseTable
     * @param string $relatedTable
     * @return array
     */
    private static function analyzeBelongsTo(BelongsTo $relation, string $baseTable, string $relatedTable): array
    {
        $foreignKey = $relation->getForeignKeyName();
        $ownerKey = $relation->getOwnerKeyName();
        
        return [
            'foreign_keys' => [
                "{$baseTable}.{$foreignKey}" => "{$relatedTable}.{$ownerKey}"
            ],
            'tables' => [$baseTable, $relatedTable]
        ];
    }

    /**
     * Analyze HasOne relationship (one-to-one)
     *
     * @param HasOne $relation
     * @param string $baseTable
     * @param string $relatedTable
     * @return array
     */
    private static function analyzeHasOne(HasOne $relation, string $baseTable, string $relatedTable): array
    {
        $foreignKey = $relation->getForeignKeyName();
        $localKey = $relation->getLocalKeyName();
        
        return [
            'foreign_keys' => [
                "{$relatedTable}.{$foreignKey}" => "{$baseTable}.{$localKey}"
            ],
            'tables' => [$baseTable, $relatedTable]
        ];
    }

    /**
     * Get all relationship methods from a model
     *
     * @param Model $model
     * @return array
     */
    public static function getAllRelationships(Model $model): array
    {
        $relationships = [];
        $reflection = new ReflectionClass($model);
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip magic methods and non-relation methods
            if ($method->isStatic() || 
                $method->getNumberOfParameters() > 0 || 
                strpos($method->getName(), '__') === 0) {
                continue;
            }
            
            try {
                $return = $method->invoke($model);
                
                // Check if it returns an Eloquent relationship
                if (is_object($return) && 
                    (strpos(get_class($return), 'Illuminate\\Database\\Eloquent\\Relations\\') === 0)) {
                    
                    $analysis = self::analyzeRelationship($model, $method->getName());
                    if ($analysis['success']) {
                        $relationships[$method->getName()] = $analysis;
                    }
                }
            } catch (\Exception $e) {
                // Skip methods that throw exceptions
                continue;
            }
        }
        
        return $relationships;
    }

    /**
     * Build comprehensive foreign key mapping for multiple relationships
     *
     * @param Model $model
     * @param array $relationMethods
     * @return array
     */
    public static function buildForeignKeyMap(Model $model, array $relationMethods = []): array
    {
        $foreignKeyMap = [];
        $allTables = [];
        
        // If no specific relations provided, get all
        if (empty($relationMethods)) {
            $relationships = self::getAllRelationships($model);
        } else {
            $relationships = [];
            foreach ($relationMethods as $method) {
                $analysis = self::analyzeRelationship($model, $method);
                if ($analysis['success']) {
                    $relationships[$method] = $analysis;
                }
            }
        }
        
        // Merge all foreign keys
        foreach ($relationships as $relationName => $analysis) {
            $foreignKeyMap = array_merge($foreignKeyMap, $analysis['foreign_keys']);
            $allTables = array_merge($allTables, $analysis['tables']);
        }
        
        return [
            'foreign_keys' => $foreignKeyMap,
            'tables' => array_unique($allTables),
            'relationships' => $relationships
        ];
    }
}