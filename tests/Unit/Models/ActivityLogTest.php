<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\ActivityLog;
use Canvastack\Canvastack\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test for ActivityLog model.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that activity log can be created.
     *
     * @return void
     */
    public function test_activity_log_can_be_created(): void
    {
        $log = ActivityLog::create([
            'user_id' => 1,
            'username' => 'testuser',
            'user_email' => 'test@example.com',
            'action' => 'login',
            'context' => 'admin',
            'status' => 'success',
        ]);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals(1, $log->user_id);
        $this->assertEquals('testuser', $log->username);
        $this->assertEquals('test@example.com', $log->user_email);
        $this->assertEquals('login', $log->action);
        $this->assertEquals('admin', $log->context);
        $this->assertEquals('success', $log->status);
    }

    /**
     * Test that activity log belongs to user.
     *
     * @return void
     */
    public function test_activity_log_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    /**
     * Test scope for user.
     *
     * @return void
     */
    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        ActivityLog::create(['user_id' => $user1->id, 'action' => 'login']);
        ActivityLog::create(['user_id' => $user2->id, 'action' => 'login']);

        $logs = ActivityLog::forUser($user1->id)->get();

        $this->assertCount(1, $logs);
        $this->assertEquals($user1->id, $logs->first()->user_id);
    }

    /**
     * Test scope for context.
     *
     * @return void
     */
    public function test_scope_for_context(): void
    {
        ActivityLog::create(['context' => 'admin', 'action' => 'login']);
        ActivityLog::create(['context' => 'public', 'action' => 'view']);

        $logs = ActivityLog::forContext('admin')->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('admin', $logs->first()->context);
    }

    /**
     * Test scope for action.
     *
     * @return void
     */
    public function test_scope_for_action(): void
    {
        ActivityLog::create(['action' => 'login']);
        ActivityLog::create(['action' => 'logout']);

        $logs = ActivityLog::forAction('login')->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('login', $logs->first()->action);
    }

    /**
     * Test scope for successful logs.
     *
     * @return void
     */
    public function test_scope_successful(): void
    {
        ActivityLog::create(['status' => 'success', 'action' => 'login']);
        ActivityLog::create(['status' => 'failed', 'action' => 'login']);

        $logs = ActivityLog::successful()->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('success', $logs->first()->status);
    }

    /**
     * Test scope for failed logs.
     *
     * @return void
     */
    public function test_scope_failed(): void
    {
        ActivityLog::create(['status' => 'success', 'action' => 'login']);
        ActivityLog::create(['status' => 'failed', 'action' => 'login']);

        $logs = ActivityLog::failed()->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('failed', $logs->first()->status);
    }

    /**
     * Test scope for error logs.
     *
     * @return void
     */
    public function test_scope_error(): void
    {
        ActivityLog::create(['status' => 'success', 'action' => 'login']);
        ActivityLog::create(['status' => 'error', 'action' => 'login']);

        $logs = ActivityLog::error()->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs->first()->status);
    }

    /**
     * Test scope for today.
     *
     * @return void
     */
    public function test_scope_today(): void
    {
        // Use DB insert to bypass Eloquent timestamp handling
        $today = now()->toDateTimeString();
        $yesterday = now()->subDay()->toDateTimeString();

        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);

        $logs = ActivityLog::today()->get();

        $this->assertCount(1, $logs);
    }

    /**
     * Test scope for this month.
     *
     * @return void
     */
    public function test_scope_this_month(): void
    {
        // Use DB insert to bypass Eloquent timestamp handling
        $thisMonth = now()->toDateTimeString();
        $lastMonth = now()->subMonth()->toDateTimeString();

        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => $thisMonth,
            'updated_at' => $thisMonth,
        ]);
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => $lastMonth,
            'updated_at' => $lastMonth,
        ]);

        $logs = ActivityLog::thisMonth()->get();

        $this->assertCount(1, $logs);
    }

    /**
     * Test scope for recent.
     *
     * @return void
     */
    public function test_scope_recent(): void
    {
        $log1 = ActivityLog::create([
            'action' => 'login',
            'created_at' => now()->subHour(),
        ]);
        $log2 = ActivityLog::create([
            'action' => 'login',
            'created_at' => now(),
        ]);

        $logs = ActivityLog::recent()->get();

        $this->assertEquals($log2->id, $logs->first()->id);
        $this->assertEquals($log1->id, $logs->last()->id);
    }

    /**
     * Test formatted duration attribute.
     *
     * @return void
     */
    public function test_formatted_duration_attribute(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'duration_ms' => 500,
        ]);

        $this->assertEquals('500ms', $log->formatted_duration);

        $log->duration_ms = 1500;
        $this->assertEquals('1.5s', $log->formatted_duration);

        $log->duration_ms = null;
        $this->assertEquals('N/A', $log->formatted_duration);
    }

    /**
     * Test formatted memory attribute.
     *
     * @return void
     */
    public function test_formatted_memory_attribute(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'memory_usage' => 1024,
        ]);

        $this->assertEquals('1 KB', $log->formatted_memory);

        $log->memory_usage = 1048576;
        $this->assertEquals('1 MB', $log->formatted_memory);

        $log->memory_usage = null;
        $this->assertEquals('N/A', $log->formatted_memory);
    }

    /**
     * Test is successful method.
     *
     * @return void
     */
    public function test_is_successful(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'status' => 'success',
        ]);

        $this->assertTrue($log->isSuccessful());
        $this->assertFalse($log->isFailed());
        $this->assertFalse($log->isError());
    }

    /**
     * Test is failed method.
     *
     * @return void
     */
    public function test_is_failed(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'status' => 'failed',
        ]);

        $this->assertFalse($log->isSuccessful());
        $this->assertTrue($log->isFailed());
        $this->assertFalse($log->isError());
    }

    /**
     * Test is error method.
     *
     * @return void
     */
    public function test_is_error(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'status' => 'error',
        ]);

        $this->assertFalse($log->isSuccessful());
        $this->assertFalse($log->isFailed());
        $this->assertTrue($log->isError());
    }

    /**
     * Test request data is cast to array.
     *
     * @return void
     */
    public function test_request_data_is_cast_to_array(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'request_data' => ['key' => 'value'],
        ]);

        $this->assertIsArray($log->request_data);
        $this->assertEquals(['key' => 'value'], $log->request_data);
    }

    /**
     * Test response data is cast to array.
     *
     * @return void
     */
    public function test_response_data_is_cast_to_array(): void
    {
        $log = ActivityLog::create([
            'action' => 'login',
            'response_data' => ['status' => 'ok'],
        ]);

        $this->assertIsArray($log->response_data);
        $this->assertEquals(['status' => 'ok'], $log->response_data);
    }

    /**
     * Test date range scope.
     *
     * @return void
     */
    public function test_scope_date_range(): void
    {
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-01-01 10:00:00',
        ]);
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => '2024-01-15 10:00:00',
            'updated_at' => '2024-01-15 10:00:00',
        ]);
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'action' => 'login',
            'created_at' => '2024-02-01 10:00:00',
            'updated_at' => '2024-02-01 10:00:00',
        ]);

        $logs = ActivityLog::dateRange('2024-01-01 00:00:00', '2024-01-31 23:59:59')->get();

        $this->assertCount(2, $logs);
    }

    /**
     * Test user relationship eager loading.
     *
     * @return void
     */
    public function test_user_relationship_eager_loading(): void
    {
        $user = User::factory()->create();
        ActivityLog::create(['user_id' => $user->id, 'action' => 'login']);

        $log = ActivityLog::with('user')->first();

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    /**
     * Test activity log with all fields.
     *
     * @return void
     */
    public function test_activity_log_with_all_fields(): void
    {
        $log = ActivityLog::create([
            'user_id' => 1,
            'username' => 'testuser',
            'user_fullname' => 'Test User',
            'user_email' => 'test@example.com',
            'user_group_id' => 1,
            'user_group_name' => 'admin',
            'user_group_info' => 'Administrator',
            'route_path' => 'admin.users.index',
            'module_name' => 'UserController',
            'page_info' => 'index',
            'url' => 'https://example.com/admin/users',
            'method' => 'GET',
            'context' => 'admin',
            'action' => 'view',
            'description' => 'Viewed users list',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'request_data' => ['page' => 1],
            'response_data' => ['count' => 10],
            'sql_dump' => 'SELECT * FROM users',
            'duration_ms' => 150,
            'memory_usage' => 2048,
            'status' => 'success',
        ]);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals(1, $log->user_id);
        $this->assertEquals('testuser', $log->username);
        $this->assertEquals('Test User', $log->user_fullname);
        $this->assertEquals('test@example.com', $log->user_email);
        $this->assertEquals(1, $log->user_group_id);
        $this->assertEquals('admin', $log->user_group_name);
        $this->assertEquals('Administrator', $log->user_group_info);
        $this->assertEquals('admin.users.index', $log->route_path);
        $this->assertEquals('UserController', $log->module_name);
        $this->assertEquals('index', $log->page_info);
        $this->assertEquals('https://example.com/admin/users', $log->url);
        $this->assertEquals('GET', $log->method);
        $this->assertEquals('admin', $log->context);
        $this->assertEquals('view', $log->action);
        $this->assertEquals('Viewed users list', $log->description);
        $this->assertEquals('127.0.0.1', $log->ip_address);
        $this->assertEquals('Mozilla/5.0', $log->user_agent);
        $this->assertEquals(['page' => 1], $log->request_data);
        $this->assertEquals(['count' => 10], $log->response_data);
        $this->assertEquals('SELECT * FROM users', $log->sql_dump);
        $this->assertEquals(150, $log->duration_ms);
        $this->assertEquals(2048, $log->memory_usage);
        $this->assertEquals('success', $log->status);
    }
}
