<?php

namespace Canvastack\Canvastack\Library\Components\Utility;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/**
 * Dynamic Route Registrar
 * Automatically registers restore routes for controllers with soft delete models
 */
class DynamicRouteRegistrar
{
    /**
     * Auto-register restore routes for all controllers with soft delete models
     */
    public static function registerRestoreRoutes(): void
    {
        try {
            // Get all registered routes
            $routes = Route::getRoutes();
            $registeredRestoreRoutes = [];
            
            foreach ($routes as $route) {
                $action = $route->getAction();
                
                // Skip if no controller action
                if (!isset($action['controller'])) {
                    continue;
                }
                
                // Parse controller and method
                $controllerAction = explode('@', $action['controller']);
                
                // Skip if action format is invalid
                if (count($controllerAction) !== 2) {
                    continue;
                }
                
                [$controllerClass, $method] = $controllerAction;
                
                // Only process destroy routes
                if ($method !== 'destroy') {
                    continue;
                }
                
                // Check if controller has soft delete model
                if (!self::controllerHasSoftDeleteModel($controllerClass)) {
                    continue;
                }
                
                // Generate restore route
                $routeName = $route->getName();
                if (!$routeName) {
                    continue;
                }
                
                // Create restore route name
                $restoreRouteName = str_replace('.destroy', '.restore', $routeName);
                
                // Skip if restore route already exists
                if (Route::has($restoreRouteName) || in_array($restoreRouteName, $registeredRestoreRoutes)) {
                    continue;
                }
                
                // Get route URI pattern for restore
                $uri = $route->uri();
                $restoreUri = str_replace('{id}', '{id}/restore', $uri);
                
                // Register restore route
                Route::post($restoreUri, $controllerClass . '@restore')
                     ->name($restoreRouteName)
                     ->middleware($route->middleware());
                
                $registeredRestoreRoutes[] = $restoreRouteName;
                
                Log::info("Auto-registered restore route: {$restoreRouteName} -> {$restoreUri}");
            }
            
            Log::info("Dynamic route registration completed. Registered " . count($registeredRestoreRoutes) . " restore routes.");
            
        } catch (\Throwable $e) {
            Log::error("Failed to register dynamic restore routes: " . $e->getMessage());
        }
    }
    
    /**
     * Check if controller has a model that supports soft deletes
     */
    protected static function controllerHasSoftDeleteModel(string $controllerClass): bool
    {
        try {
            if (!class_exists($controllerClass)) {
                return false;
            }
            
            // Try to create controller instance to check model
            // Skip if constructor requires parameters
            $reflection = new \ReflectionClass($controllerClass);
            $constructor = $reflection->getConstructor();
            
            if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
                // Cannot instantiate controller with required parameters
                // Try to analyze class properties statically
                return self::analyzeControllerStatically($controllerClass);
            }
            
            $controller = new $controllerClass();
            
            // Try to get model from various properties
            $model = null;
            $modelProperties = ['model', 'model_data', 'model_table', 'model_path'];
            
            foreach ($modelProperties as $property) {
                if (property_exists($controller, $property)) {
                    $value = $controller->{$property};
                    
                    if (is_object($value)) {
                        $model = $value;
                        break;
                    } elseif (is_string($value) && class_exists($value)) {
                        $model = new $value;
                        break;
                    }
                }
            }
            
            if (!$model) {
                return false;
            }
            
            // Check if model uses SoftDeletes trait
            return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model), true);
            
        } catch (\Throwable $e) {
            Log::warning("Failed to check soft delete model for {$controllerClass}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Analyze controller statically without instantiation
     */
    protected static function analyzeControllerStatically(string $controllerClass): bool
    {
        try {
            $reflection = new \ReflectionClass($controllerClass);
            
            // Check for model-related properties in class definition
            $properties = $reflection->getProperties();
            
            foreach ($properties as $property) {
                $propertyName = $property->getName();
                
                // Look for common model property names
                if (in_array($propertyName, ['model', 'model_data', 'model_table', 'model_path'])) {
                    // Try to get default value or analyze docblock
                    if ($property->hasDefaultValue()) {
                        $defaultValue = $property->getDefaultValue();
                        
                        if (is_string($defaultValue) && class_exists($defaultValue)) {
                            // Check if the model class uses SoftDeletes
                            $modelTraits = class_uses($defaultValue);
                            return in_array('Illuminate\Database\Eloquent\SoftDeletes', $modelTraits, true);
                        }
                    }
                }
            }
            
            // Check parent classes for model properties
            $parentClass = $reflection->getParentClass();
            if ($parentClass && $parentClass->getName() !== 'Illuminate\Routing\Controller') {
                return self::analyzeControllerStatically($parentClass->getName());
            }
            
            return false;
            
        } catch (\Throwable $e) {
            Log::warning("Failed to analyze controller statically {$controllerClass}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Register restore route for specific controller
     */
    public static function registerRestoreRoute(string $controllerClass, string $routePrefix = ''): bool
    {
        try {
            if (!self::controllerHasSoftDeleteModel($controllerClass)) {
                return false;
            }
            
            // Generate route name and URI
            $routeName = $routePrefix ? "{$routePrefix}.restore" : 'restore';
            $routeUri = $routePrefix ? "{$routePrefix}/{id}/restore" : '{id}/restore';
            
            // Skip if route already exists
            if (Route::has($routeName)) {
                return false;
            }
            
            // Register restore route
            Route::post($routeUri, $controllerClass . '@restore')->name($routeName);
            
            Log::info("Registered restore route: {$routeName} -> {$routeUri}");
            return true;
            
        } catch (\Throwable $e) {
            Log::error("Failed to register restore route for {$controllerClass}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all controllers that support soft delete
     */
    public static function getSoftDeleteControllers(): array
    {
        $controllers = [];
        
        try {
            $routes = Route::getRoutes();
            
            foreach ($routes as $route) {
                $action = $route->getAction();
                
                if (!isset($action['controller'])) {
                    continue;
                }
                
                [$controllerClass, $method] = explode('@', $action['controller']);
                
                if (self::controllerHasSoftDeleteModel($controllerClass)) {
                    $controllers[] = $controllerClass;
                }
            }
            
            return array_unique($controllers);
            
        } catch (\Throwable $e) {
            Log::error("Failed to get soft delete controllers: " . $e->getMessage());
            return [];
        }
    }
}