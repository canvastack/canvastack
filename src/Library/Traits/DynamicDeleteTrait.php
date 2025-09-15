<?php

namespace Canvastack\Canvastack\Library\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Canvastack\Canvastack\Library\Components\Utility\DeleteDetector;

/**
 * Dynamic Delete Trait
 * Provides universal delete and restore functionality for any controller
 */
trait DynamicDeleteTrait
{
    /**
     * Universal destroy method that works with any model
     */
    public function dynamicDestroy(Request $request, $id)
    {
        try {
            // Get dynamic controller info
            $info = DeleteDetector::getCurrentControllerInfo();
            
            // Get model instance
            $model = $this->getModelInstance();
            if (!$model) {
                return $this->deleteErrorResponse($request, 'Model not found or not configured');
            }
            
            // Find the record
            $record = $model->find($id);
            if (!$record) {
                return $this->deleteErrorResponse($request, 'Record not found', 404);
            }
            
            // Check for self-deletion if applicable
            if ($this->preventSelfDeletion($record)) {
                return $this->deleteErrorResponse($request, 'You cannot delete your own account', 403);
            }
            
            // Check if already deleted (for soft delete models)
            if (method_exists($record, 'trashed') && $record->trashed()) {
                return $this->deleteErrorResponse($request, 'Record is already deleted', 400);
            }
            
            // Check dependent relations
            $dependencies = DeleteDetector::checkDependentRelations($model, $id);
            if (!empty($dependencies) && !$request->get('force_delete', false)) {
                $dependencyMessage = $this->buildDependencyMessage($dependencies);
                return $this->deleteErrorResponse($request, $dependencyMessage, 409);
            }
            
            // Store record info for logging
            $recordInfo = $this->getRecordInfo($record);
            
            // Perform deletion based on model type
            $deleteType = $info['delete_type'];
            if ($deleteType === 'soft') {
                $record->delete(); // Soft delete
                $message = "Record '{$recordInfo}' has been moved to trash successfully";
            } else {
                // Handle relations before hard delete
                $this->handleRelationsBeforeDelete($record, $dependencies);
                $record->delete(); // Hard delete
                $message = "Record '{$recordInfo}' has been permanently deleted";
            }
            
            // Log the deletion
            $this->logDeletion($recordInfo, $deleteType);
            
            return $this->deleteSuccessResponse($request, $message);
            
        } catch (\Exception $e) {
            \Log::error('Dynamic delete error: ' . $e->getMessage(), [
                'controller' => get_class($this),
                'record_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->deleteErrorResponse($request, 'Failed to delete record: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Universal restore method for soft deleted records
     */
    public function dynamicRestore(Request $request, $id)
    {
        try {
            // Get model instance
            $model = $this->getModelInstance();
            if (!$model) {
                return $this->deleteErrorResponse($request, 'Model not found or not configured');
            }
            
            // Check if model supports soft deletes
            if (!method_exists($model, 'withTrashed')) {
                return $this->deleteErrorResponse($request, 'Model does not support soft deletes', 400);
            }
            
            // Find the soft deleted record
            $record = $model->withTrashed()->find($id);
            if (!$record) {
                return $this->deleteErrorResponse($request, 'Record not found', 404);
            }
            
            if (!$record->trashed()) {
                return $this->deleteErrorResponse($request, 'Record is not deleted', 400);
            }
            
            // Store record info for logging
            $recordInfo = $this->getRecordInfo($record);
            
            // Restore the record
            $record->restore();
            
            // Log the restoration
            $this->logRestoration($recordInfo);
            
            $message = "Record '{$recordInfo}' has been restored successfully";
            return $this->deleteSuccessResponse($request, $message);
            
        } catch (\Exception $e) {
            \Log::error('Dynamic restore error: ' . $e->getMessage(), [
                'controller' => get_class($this),
                'record_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->deleteErrorResponse($request, 'Failed to restore record: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get model instance from controller
     */
    protected function getModelInstance(): ?Model
    {
        // Try common property names
        $modelProperties = ['model', 'model_data', 'modelClass', 'modelName'];
        
        foreach ($modelProperties as $property) {
            if (property_exists($this, $property)) {
                $value = $this->{$property};
                
                if ($value instanceof Model) {
                    return $value;
                } elseif (is_string($value) && class_exists($value)) {
                    return new $value;
                }
            }
        }
        
        // Try to get from model_table property
        if (property_exists($this, 'model_table') && !empty($this->model_table)) {
            $modelClass = $this->model_table;
            if (class_exists($modelClass)) {
                return new $modelClass;
            }
        }
        
        return null;
    }
    
    /**
     * Check if deletion should be prevented (e.g., self-deletion)
     */
    protected function preventSelfDeletion(Model $record): bool
    {
        // Check for user self-deletion
        if (method_exists($this, 'get_session')) {
            $this->get_session();
            if (isset($this->session['id']) && intval($record->id) === intval($this->session['id'])) {
                return true;
            }
        }
        
        // Check Laravel auth
        if (auth()->check() && intval($record->id) === intval(auth()->id())) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get record info for logging and messages
     */
    protected function getRecordInfo(Model $record): string
    {
        // Try common name fields
        $nameFields = ['name', 'title', 'username', 'fullname', 'email', 'label'];
        
        foreach ($nameFields as $field) {
            if (isset($record->{$field}) && !empty($record->{$field})) {
                return $record->{$field};
            }
        }
        
        return "ID {$record->id}";
    }
    
    /**
     * Handle relations before hard delete
     */
    protected function handleRelationsBeforeDelete(Model $record, array $dependencies): void
    {
        foreach ($dependencies as $dependency) {
            $relationName = $dependency['relation'];
            
            try {
                // For hasMany relations, delete related records
                if ($dependency['type'] === 'HasMany') {
                    $record->{$relationName}()->delete();
                }
                // For belongsToMany, detach relations
                elseif ($dependency['type'] === 'BelongsToMany') {
                    $record->{$relationName}()->detach();
                }
            } catch (\Throwable $e) {
                \Log::warning("Failed to handle relation {$relationName}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Build dependency message
     */
    protected function buildDependencyMessage(array $dependencies): string
    {
        $message = "Cannot delete this record because it has dependent data:\n";
        
        foreach ($dependencies as $dependency) {
            $message .= "- {$dependency['count']} related {$dependency['relation']} record(s)\n";
        }
        
        $message .= "\nPlease remove or reassign the dependent data first, or use force delete.";
        
        return $message;
    }
    
    /**
     * Log deletion
     */
    protected function logDeletion(string $recordInfo, string $deleteType): void
    {
        $user = $this->getCurrentUser();
        $action = $deleteType === 'soft' ? 'soft deleted' : 'permanently deleted';
        
        \Log::info("Record {$action}: {$recordInfo} by {$user}");
    }
    
    /**
     * Log restoration
     */
    protected function logRestoration(string $recordInfo): void
    {
        $user = $this->getCurrentUser();
        \Log::info("Record restored: {$recordInfo} by {$user}");
    }
    
    /**
     * Get current user for logging
     */
    protected function getCurrentUser(): string
    {
        // Try session-based user
        if (method_exists($this, 'get_session') && isset($this->session['fullname'])) {
            return $this->session['fullname'];
        }
        
        // Try Laravel auth
        if (auth()->check()) {
            $user = auth()->user();
            return $user->name ?? $user->username ?? $user->email ?? "User {$user->id}";
        }
        
        return 'System';
    }
    
    /**
     * Success response helper
     */
    protected function deleteSuccessResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        
        $redirectUrl = $this->getRedirectUrl();
        return redirect($redirectUrl)->with('success', $message);
    }
    
    /**
     * Error response helper
     */
    protected function deleteErrorResponse(Request $request, string $message, int $status = 400)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }
        
        $redirectUrl = $this->getRedirectUrl();
        return redirect($redirectUrl)->withErrors(['error' => $message]);
    }
    
    /**
     * Get redirect URL after delete operation
     */
    protected function getRedirectUrl(): string
    {
        // Try to get from current route
        $currentRoute = request()->route();
        if ($currentRoute) {
            $routeName = $currentRoute->getName();
            if ($routeName && str_contains($routeName, '.')) {
                $baseName = substr($routeName, 0, strrpos($routeName, '.')) . '.index';
                if (\Route::has($baseName)) {
                    return route($baseName);
                }
            }
        }
        
        // Fallback to previous URL or home
        return back()->getTargetUrl() ?? url('/');
    }
}