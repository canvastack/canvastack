<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activity Log Model.
 *
 * Represents a user activity log entry for auditing and monitoring.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $username
 * @property string|null $user_fullname
 * @property string|null $user_email
 * @property int|null $user_group_id
 * @property string|null $user_group_name
 * @property string|null $user_group_info
 * @property string|null $route_path
 * @property string|null $module_name
 * @property string|null $page_info
 * @property string|null $url
 * @property string|null $method
 * @property string $context
 * @property string|null $action
 * @property string|null $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $request_data
 * @property array|null $response_data
 * @property string|null $sql_dump
 * @property int|null $duration_ms
 * @property int|null $memory_usage
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ActivityLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'username',
        'user_fullname',
        'user_email',
        'user_group_id',
        'user_group_name',
        'user_group_info',
        'route_path',
        'module_name',
        'page_info',
        'url',
        'method',
        'context',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
        'sql_dump',
        'duration_ms',
        'memory_usage',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'user_group_id' => 'integer',
        'request_data' => 'array',
        'response_data' => 'array',
        'duration_ms' => 'integer',
        'memory_usage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the activity log.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include logs for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs for a specific context.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $context
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    /**
     * Scope a query to only include logs for a specific action.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include successful logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include error logs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeError($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $from
     * @param string $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope a query to filter by today.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to filter by this week.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope a query to filter by this month.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope a query to order by most recent.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get formatted duration.
     *
     * @return string
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms === null) {
            return 'N/A';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }

        return round($this->duration_ms / 1000, 2) . 's';
    }

    /**
     * Get formatted memory usage.
     *
     * @return string
     */
    public function getFormattedMemoryAttribute(): string
    {
        if ($this->memory_usage === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->memory_usage;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the log is successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the log is failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the log is error.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->status === 'error';
    }
}
