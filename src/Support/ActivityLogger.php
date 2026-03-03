<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support;

use Canvastack\Canvastack\Models\ActivityLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/**
 * Activity Logger Service.
 *
 * Handles logging of user activities for auditing and monitoring.
 */
class ActivityLogger
{
    /**
     * Start time for duration calculation.
     *
     * @var float
     */
    protected float $startTime;

    /**
     * Start memory for memory usage calculation.
     *
     * @var int
     */
    protected int $startMemory;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    /**
     * Log an activity.
     *
     * @param array<string, mixed> $data Activity data
     * @return ActivityLog|null
     */
    public function log(array $data): ?ActivityLog
    {
        if (! $this->shouldLog($data)) {
            return null;
        }

        // Calculate duration and memory usage
        $duration = (int) ((microtime(true) - $this->startTime) * 1000);
        $memoryUsage = memory_get_usage() - $this->startMemory;

        // Merge with default data
        $logData = array_merge([
            'user_id' => Auth::id(),
            'username' => Auth::user()?->name,
            'user_fullname' => Auth::user()?->name,
            'user_email' => Auth::user()?->email,
            'context' => 'admin',
            'status' => 'success',
            'duration_ms' => $duration,
            'memory_usage' => $memoryUsage,
        ], $data);

        return ActivityLog::create($logData);
    }

    /**
     * Log from HTTP request.
     *
     * @param Request $request
     * @param string|null $action
     * @param string|null $description
     * @param string $status
     * @return ActivityLog|null
     */
    public function logRequest(
        Request $request,
        ?string $action = null,
        ?string $description = null,
        string $status = 'success'
    ): ?ActivityLog {
        $user = Auth::user();

        $data = [
            'route_path' => $request->route()?->getName(),
            'module_name' => $this->extractModuleName($request),
            'page_info' => $this->extractPageInfo($request),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'action' => $action ?? $this->extractAction($request),
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => $this->sanitizeRequestData($request),
            'status' => $status,
        ];

        // Add user group information if available
        if ($user && method_exists($user, 'roles')) {
            $role = $user->roles()->first();
            if ($role) {
                $data['user_group_id'] = $role->id;
                $data['user_group_name'] = $role->name;
                $data['user_group_info'] = $role->description;
            }
        }

        return $this->log($data);
    }

    /**
     * Log login activity.
     *
     * @param Authenticatable $user
     * @param Request $request
     * @param bool $success
     * @return ActivityLog|null
     */
    public function logLogin(Authenticatable $user, Request $request, bool $success = true): ?ActivityLog
    {
        return $this->log([
            'user_id' => $user->getAuthIdentifier(),
            'username' => $user->name ?? $user->email ?? 'Unknown',
            'user_fullname' => $user->name ?? 'Unknown',
            'user_email' => $user->email ?? 'Unknown',
            'action' => 'login',
            'description' => $success ? 'User logged in successfully' : 'Failed login attempt',
            'page_info' => 'login_processor',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $success ? 'success' : 'failed',
        ]);
    }

    /**
     * Log logout activity.
     *
     * @param Authenticatable $user
     * @param Request $request
     * @return ActivityLog|null
     */
    public function logLogout(Authenticatable $user, Request $request): ?ActivityLog
    {
        return $this->log([
            'user_id' => $user->getAuthIdentifier(),
            'username' => $user->name ?? $user->email ?? 'Unknown',
            'user_fullname' => $user->name ?? 'Unknown',
            'user_email' => $user->email ?? 'Unknown',
            'action' => 'logout',
            'description' => 'User logged out',
            'page_info' => 'logout',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);
    }

    /**
     * Log CRUD operation.
     *
     * @param string $action create, update, delete, view
     * @param string $model Model class name
     * @param mixed $modelId Model ID
     * @param string|null $description
     * @return ActivityLog|null
     */
    public function logCrud(
        string $action,
        string $model,
        mixed $modelId,
        ?string $description = null
    ): ?ActivityLog {
        return $this->log([
            'action' => $action,
            'description' => $description ?? "User {$action}d {$model} #{$modelId}",
            'module_name' => class_basename($model),
            'request_data' => [
                'model' => $model,
                'model_id' => $modelId,
            ],
        ]);
    }

    /**
     * Log permission check.
     *
     * @param string $permission
     * @param bool $granted
     * @param string|null $context
     * @return ActivityLog|null
     */
    public function logPermissionCheck(
        string $permission,
        bool $granted,
        ?string $context = null
    ): ?ActivityLog {
        if (! Config::get('canvastack-rbac.activity_log.log_failed_attempts', true) && $granted) {
            return null;
        }

        return $this->log([
            'action' => 'permission_check',
            'description' => $granted
                ? "Permission '{$permission}' granted"
                : "Permission '{$permission}' denied",
            'context' => $context ?? 'admin',
            'status' => $granted ? 'success' : 'failed',
            'request_data' => [
                'permission' => $permission,
                'granted' => $granted,
            ],
        ]);
    }

    /**
     * Check if activity should be logged.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    protected function shouldLog(array $data): bool
    {
        // Check if logging is enabled
        if (! Config::get('canvastack.log_activity.enabled', true)) {
            return false;
        }

        // Check run status
        $runStatus = Config::get('canvastack.log_activity.run_status', 'unexceptions');

        if ($runStatus === 'none') {
            return false;
        }

        if ($runStatus === 'all') {
            return true;
        }

        // Check exceptions
        $exceptions = Config::get('canvastack.log_activity.exceptions', []);

        // Check if user group is in exceptions
        if (isset($data['user_group_name'])) {
            $excludedGroups = $exceptions['groups'] ?? [];
            if (in_array($data['user_group_name'], $excludedGroups)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract module name from request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractModuleName(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $action = $route->getActionName();
        if (str_contains($action, '@')) {
            [$controller] = explode('@', $action);
            $parts = explode('\\', $controller);

            return $parts[count($parts) - 1] ?? null;
        }

        return null;
    }

    /**
     * Extract page info from request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractPageInfo(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $name = $route->getName();
        if ($name) {
            return $name;
        }

        $action = $route->getActionName();
        if (str_contains($action, '@')) {
            [, $method] = explode('@', $action);

            return $method;
        }

        return null;
    }

    /**
     * Extract action from request.
     *
     * @param Request $request
     * @return string
     */
    protected function extractAction(Request $request): string
    {
        $method = strtolower($request->method());

        return match ($method) {
            'post' => 'create',
            'put', 'patch' => 'update',
            'delete' => 'delete',
            default => 'view',
        };
    }

    /**
     * Sanitize request data for logging.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function sanitizeRequestData(Request $request): array
    {
        $data = $request->except([
            'password',
            'password_confirmation',
            'token',
            '_token',
            'api_token',
            'secret',
            'credit_card',
            'cvv',
        ]);

        // Limit data size
        $json = json_encode($data);
        if (strlen($json) > 10000) {
            return ['_truncated' => true, '_size' => strlen($json)];
        }

        return $data;
    }

    /**
     * Reset timer for new request.
     *
     * @return void
     */
    public function resetTimer(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }
}
