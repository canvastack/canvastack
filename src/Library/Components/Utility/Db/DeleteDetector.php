<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Db;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Dynamic Delete Detection System
 * Automatically detects controller, model, and delete type
 */
class DeleteDetector
{
    /**
     * Get current controller information from route
     */
    public static function getCurrentControllerInfo(): array
    {
        try {
            $currentRoute = Route::current();
            if (!$currentRoute) {
                return self::getDefaultInfo();
            }

            $action = $currentRoute->getAction();
            $controllerAction = $action['controller'] ?? $action['uses'] ?? null;
            
            if (!$controllerAction) {
                return self::getDefaultInfo();
            }

            // Parse controller@method
            [$controllerClass, $method] = explode('@', $controllerAction);
            
            // Get controller instance
            $controller = app($controllerClass);
            
            // Get model from controller
            $model = self::getModelFromController($controller);
            
            // Get table name
            $tableName = self::getTableNameFromModel($model);
            
            // Detect delete type
            $deleteType = self::getDeleteType($model);
            
            // Get relations
            $relations = self::getModelRelations($model);
            
            return [
                'controller_class' => $controllerClass,
                'controller_name' => class_basename($controllerClass),
                'model_class' => $model ? get_class($model) : null,
                'model_name' => $model ? class_basename($model) : null,
                'table_name' => $tableName,
                'delete_type' => $deleteType,
                'has_soft_deletes' => $deleteType === 'soft',
                'relations' => $relations,
                'route_name' => $currentRoute->getName(),
                'route_uri' => $currentRoute->uri(),
            ];
            
        } catch (\Throwable $e) {
            \Log::warning('DeleteDetector: Failed to get controller info', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::getDefaultInfo();
        }
    }
    
    /**
     * Get model instance from controller
     */
    private static function getModelFromController($controller): ?Model
    {
        // Try common property names
        $modelProperties = ['model', 'model_data', 'modelClass', 'modelName'];
        
        foreach ($modelProperties as $property) {
            if (property_exists($controller, $property)) {
                $value = $controller->{$property};
                
                if ($value instanceof Model) {
                    return $value;
                } elseif (is_string($value) && class_exists($value)) {
                    return new $value;
                }
            }
        }
        
        // Try to instantiate from controller name
        $controllerName = class_basename($controller);
        $modelName = str_replace('Controller', '', $controllerName);
        
        // Common model paths
        $modelPaths = [
            "App\\Models\\{$modelName}",
            "App\\Models\\Admin\\System\\{$modelName}",
            "Canvastack\\Canvastack\\Models\\Admin\\System\\{$modelName}",
            "App\\{$modelName}",
        ];
        
        foreach ($modelPaths as $modelPath) {
            if (class_exists($modelPath)) {
                return new $modelPath;
            }
        }
        
        return null;
    }
    
    /**
     * Get table name from model
     */
    private static function getTableNameFromModel(?Model $model): string
    {
        if (!$model) {
            return 'records';
        }
        
        return $model->getTable();
    }
    
    /**
     * Detect delete type (soft or hard)
     */
    private static function getDeleteType(?Model $model): string
    {
        if (!$model) {
            return 'hard';
        }
        
        // Check if model uses SoftDeletes trait
        $traits = class_uses_recursive($model);
        
        if (in_array(SoftDeletes::class, $traits)) {
            return 'soft';
        }
        
        // Check if table has deleted_at column
        $tableName = $model->getTable();
        if (SchemaTools::hasColumn($tableName, 'deleted_at')) {
            return 'soft';
        }
        
        return 'hard';
    }
    
    /**
     * Get model relations
     */
    private static function getModelRelations(?Model $model): array
    {
        if (!$model) {
            return [];
        }
        
        $relations = [];
        
        try {
            $reflection = new \ReflectionClass($model);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if ($method->class !== get_class($model)) {
                    continue;
                }
                
                $methodName = $method->getName();
                
                // Skip magic methods and common model methods
                if (Str::startsWith($methodName, ['__', 'get', 'set', 'is', 'has', 'can', 'should', 'where', 'find', 'create', 'update', 'delete', 'save', 'fresh', 'refresh', 'replicate', 'toArray', 'toJson'])) {
                    continue;
                }
                
                // Try to call method to see if it returns a relation
                try {
                    $result = $model->{$methodName}();
                    
                    if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        $relations[] = [
                            'name' => $methodName,
                            'type' => class_basename($result),
                            'related_model' => get_class($result->getRelated()),
                            'foreign_key' => method_exists($result, 'getForeignKeyName') ? $result->getForeignKeyName() : null,
                        ];
                    }
                } catch (\Throwable $e) {
                    // Ignore errors when calling methods
                    continue;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('DeleteDetector: Failed to get model relations', [
                'model' => get_class($model),
                'error' => $e->getMessage()
            ]);
        }
        
        return $relations;
    }
    
    /**
     * Get default info when detection fails
     */
    private static function getDefaultInfo(): array
    {
        return [
            'controller_class' => null,
            'controller_name' => 'Unknown',
            'model_class' => null,
            'model_name' => 'Unknown',
            'table_name' => 'records',
            'delete_type' => 'hard',
            'has_soft_deletes' => false,
            'relations' => [],
            'route_name' => null,
            'route_uri' => null,
        ];
    }
    
    /**
     * Get delete confirmation message based on detected info
     */
    public static function getDeleteMessage(array $info, $recordId): string
    {
        $tableName = $info['table_name'];
        $deleteType = $info['delete_type'];
        
        if ($deleteType === 'soft') {
            return "Anda akan menghapus record data dari tabel <strong>'{$tableName}'</strong> dengan ID <strong>{$recordId}</strong>. Data akan dipindahkan ke recycle bin dan dapat dipulihkan kembali. Apakah Anda yakin?";
        } else {
            return "Anda akan menghapus permanen record data dari tabel <strong>'{$tableName}'</strong> dengan ID <strong>{$recordId}</strong>. Tindakan ini tidak dapat dibatalkan. Apakah Anda yakin?";
        }
    }
    
    /**
     * Get restore message for soft deleted items
     */
    public static function getRestoreMessage(array $info, $recordId): string
    {
        $tableName = $info['table_name'];
        return "Anda akan memulihkan record data dari tabel <strong>'{$tableName}'</strong> dengan ID <strong>{$recordId}</strong>. Data akan dikembalikan ke kondisi aktif. Apakah Anda yakin?";
    }
    
    /**
     * Check if record has dependent relations that would be affected
     */
    public static function checkDependentRelations(Model $model, $recordId): array
    {
        $info = self::getCurrentControllerInfo();
        $dependencies = [];
        
        try {
            $record = $model->find($recordId);
            if (!$record) {
                return $dependencies;
            }
            
            foreach ($info['relations'] as $relation) {
                try {
                    $relationData = $record->{$relation['name']};
                    
                    if ($relationData instanceof \Illuminate\Database\Eloquent\Collection) {
                        $count = $relationData->count();
                        if ($count > 0) {
                            $dependencies[] = [
                                'relation' => $relation['name'],
                                'count' => $count,
                                'type' => $relation['type']
                            ];
                        }
                    } elseif ($relationData instanceof Model) {
                        $dependencies[] = [
                            'relation' => $relation['name'],
                            'count' => 1,
                            'type' => $relation['type']
                        ];
                    }
                } catch (\Throwable $e) {
                    // Ignore relation check errors
                    continue;
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('DeleteDetector: Failed to check dependent relations', [
                'model' => get_class($model),
                'record_id' => $recordId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $dependencies;
    }
}