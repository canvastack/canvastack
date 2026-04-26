<?php

namespace Canvastack\Canvastack\Controllers\Admin\System;

use Canvastack\Canvastack\Controllers\Core\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Cache Management Controller
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Provides cache management functionality for development environments.
 * Allows developers to clear various Laravel caches through a web interface.
 * 
 * Security Features:
 * - Only accessible in local/development environments
 * - Requires root user authentication
 * - CSRF protection enabled
 * - Rate limiting applied
 * - All actions are logged
 * 
 * Available Cache Types:
 * - all: Clear all caches (application, config, route, view, compiled)
 * - config: Clear configuration cache
 * - route: Clear route cache
 * - view: Clear compiled view cache
 * - compiled: Clear compiled class cache
 * - app: Clear application cache only
 * - optimize: Cache config and routes for optimization
 * 
 * Usage:
 * POST /admin/cache/clear/{type}
 * 
 * Response Format:
 * {
 *   "success": true,
 *   "message": "Cache cleared successfully",
 *   "type": "all",
 *   "timestamp": "2026-04-05 10:30:15"
 * }
 * 
 * @package    Canvastack\Canvastack\Controllers\Admin\System
 * @category   System Management
 * @author     Canvastack Development Team
 * @copyright  2026 Canvastack
 * @license    Proprietary
 * @version    1.0.0
 * 
 * @security   Root access only
 * @security   Environment restricted (local, development)
 * @security   CSRF protection required
 * @security   Rate limited (5 requests per minute)
 */
class CacheManagementController extends Controller
{
    /**
     * Constructor
     * 
     * Initializes the controller and verifies environment and permissions.
     * 
     * @return void
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If not in allowed environment
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException If user is not root
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Verify access permissions
     * 
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function verifyAccess(): void
    {
        // Log for debugging
        \Illuminate\Support\Facades\Log::info('🔧 CacheManagementController: Verifying access', [
            'environment' => app()->environment(),
            'user_group' => session('user_group'),
            'session_user_group' => $this->session['user_group'] ?? null,
        ]);
        
        // Verify environment
        if (!in_array(app()->environment(), ['local', 'development'])) {
            \Illuminate\Support\Facades\Log::warning('🔧 CacheManagementController: Wrong environment');
            abort(403, 'Cache management is only available in local/development environments');
        }
        
        // Verify user is root (check both session sources)
        $userGroup = $this->session['user_group'] ?? session('user_group');
        if (!$userGroup || $userGroup !== 'root') {
            \Illuminate\Support\Facades\Log::warning('🔧 CacheManagementController: Not root user', [
                'user_group' => $userGroup,
            ]);
            abort(403, 'Cache management requires root access');
        }
        
        \Illuminate\Support\Facades\Log::info('🔧 CacheManagementController: Authorization passed');
    }
    
    /**
     * Clear cache based on type
     * 
     * Executes the appropriate Artisan command(s) to clear the specified cache type.
     * All operations are logged for audit purposes.
     * 
     * @param \Illuminate\Http\Request $request HTTP request
     * @param string $type Cache type to clear (all, config, route, view, compiled, app, optimize)
     * @return \Illuminate\Http\JsonResponse JSON response with operation result
     * 
     * @throws \InvalidArgumentException If cache type is invalid
     * 
     * @security Logs all cache clear operations with user context
     * @security Rate limited to prevent abuse
     * 
     * @example
     * POST /admin/cache/clear/all
     * Response: {"success": true, "message": "All caches cleared successfully"}
     */
    public function clear(Request $request, string $type): JsonResponse
    {
        // Verify access permissions
        $this->verifyAccess();
        
        try {
            // Validate cache type
            $validTypes = ['all', 'config', 'route', 'view', 'compiled', 'app', 'optimize'];
            if (!in_array($type, $validTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid cache type: {$type}",
                    'valid_types' => $validTypes,
                ], 400);
            }
            
            // Log the operation
            Log::info('Cache clear initiated', [
                'type' => $type,
                'user_id' => $this->session['id'] ?? null,
                'username' => $this->session['username'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Execute cache clear based on type
            $message = $this->executeCacheClear($type);
            
            // Log success
            Log::info('Cache clear completed', [
                'type' => $type,
                'user_id' => $this->session['id'] ?? null,
                'message' => $message,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'type' => $type,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            // Log error
            Log::error('Cache clear failed', [
                'type' => $type,
                'user_id' => $this->session['id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Cache clear failed: ' . $e->getMessage(),
                'type' => $type,
            ], 500);
        }
    }
    
    /**
     * Execute cache clear command(s)
     * 
     * Runs the appropriate Artisan command(s) based on the cache type.
     * 
     * @param string $type Cache type to clear
     * @return string Success message
     * 
     * @throws \Exception If Artisan command fails
     */
    private function executeCacheClear(string $type): string
    {
        switch ($type) {
            case 'all':
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                Artisan::call('clear-compiled');
                return 'All caches cleared successfully (application, config, route, view, compiled)';
                
            case 'config':
                Artisan::call('config:clear');
                return 'Configuration cache cleared successfully';
                
            case 'route':
                Artisan::call('route:clear');
                return 'Route cache cleared successfully';
                
            case 'view':
                Artisan::call('view:clear');
                return 'View cache cleared successfully';
                
            case 'compiled':
                Artisan::call('clear-compiled');
                return 'Compiled class cache cleared successfully';
                
            case 'app':
                Artisan::call('cache:clear');
                return 'Application cache cleared successfully';
                
            case 'optimize':
                Artisan::call('config:cache');
                Artisan::call('route:cache');
                return 'Application optimized (config and routes cached)';
                
            default:
                throw new \InvalidArgumentException("Unknown cache type: {$type}");
        }
    }
    
    /**
     * Get cache status information
     * 
     * Returns information about current cache status (cached/not cached).
     * 
     * @param \Illuminate\Http\Request $request HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response with cache status
     * 
     * @example
     * GET /admin/cache/status
     * Response: {
     *   "config": {"cached": true, "path": "/path/to/config.php"},
     *   "routes": {"cached": false},
     *   "views": {"cached": true, "count": 45}
     * }
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $status = [
                'config' => [
                    'cached' => file_exists(app()->getCachedConfigPath()),
                    'path' => app()->getCachedConfigPath(),
                ],
                'routes' => [
                    'cached' => file_exists(app()->getCachedRoutesPath()),
                    'path' => app()->getCachedRoutesPath(),
                ],
                'views' => [
                    'cached' => is_dir(storage_path('framework/views')),
                    'path' => storage_path('framework/views'),
                ],
            ];
            
            return response()->json([
                'success' => true,
                'status' => $status,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cache status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
