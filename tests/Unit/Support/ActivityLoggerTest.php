<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support;

use Canvastack\Canvastack\Models\ActivityLog;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Models\User;
use Canvastack\Canvastack\Support\ActivityLogger;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/**
 * Test for ActivityLogger service.
 */
class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new ActivityLogger();
    }

    /**
     * Test that activity can be logged.
     *
     * @return void
     */
    public function test_activity_can_be_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
            'description' => 'Test description',
        ]);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('test_action', $log->action);
        $this->assertEquals('Test description', $log->description);
    }

    /**
     * Test that request can be logged.
     *
     * @return void
     */
    public function test_request_can_be_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $log = $this->logger->logRequest($request, 'view', 'Test page view');

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('view', $log->action);
        $this->assertEquals('Test page view', $log->description);
        $this->assertEquals('GET', $log->method);
    }

    /**
     * Test that login can be logged.
     *
     * @return void
     */
    public function test_login_can_be_logged(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        $log = $this->logger->logLogin($user, $request, true);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('login', $log->action);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('login_processor', $log->page_info);
    }

    /**
     * Test that failed login can be logged.
     *
     * @return void
     */
    public function test_failed_login_can_be_logged(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST');

        $log = $this->logger->logLogin($user, $request, false);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('login', $log->action);
        $this->assertEquals('failed', $log->status);
    }

    /**
     * Test that logout can be logged.
     *
     * @return void
     */
    public function test_logout_can_be_logged(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/logout', 'POST');

        $log = $this->logger->logLogout($user, $request);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('logout', $log->action);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('logout', $log->page_info);
    }

    /**
     * Test that CRUD operations can be logged.
     *
     * @return void
     */
    public function test_crud_operations_can_be_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->logCrud('create', User::class, 1, 'Created user');

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('create', $log->action);
        $this->assertEquals('Created user', $log->description);
        $this->assertEquals('User', $log->module_name);
    }

    /**
     * Test that permission check can be logged.
     *
     * @return void
     */
    public function test_permission_check_can_be_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->logPermissionCheck('users.create', true, 'admin');

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('permission_check', $log->action);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('admin', $log->context);
    }

    /**
     * Test that failed permission check can be logged.
     *
     * @return void
     */
    public function test_failed_permission_check_can_be_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->logPermissionCheck('users.delete', false, 'admin');

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('permission_check', $log->action);
        $this->assertEquals('failed', $log->status);
    }

    /**
     * Test that logging can be disabled.
     *
     * @return void
     */
    public function test_logging_can_be_disabled(): void
    {
        Config::set('canvastack.log_activity.enabled', false);

        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
        ]);

        $this->assertNull($log);
    }

    /**
     * Test that logging respects run status none.
     *
     * @return void
     */
    public function test_logging_respects_run_status_none(): void
    {
        Config::set('canvastack.log_activity.run_status', 'none');

        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
        ]);

        $this->assertNull($log);
    }

    /**
     * Test that logging respects run status all.
     *
     * @return void
     */
    public function test_logging_respects_run_status_all(): void
    {
        Config::set('canvastack.log_activity.run_status', 'all');

        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
        ]);

        $this->assertInstanceOf(ActivityLog::class, $log);
    }

    /**
     * Test that logging respects group exceptions.
     *
     * @return void
     */
    public function test_logging_respects_group_exceptions(): void
    {
        Config::set('canvastack.log_activity.run_status', 'unexceptions');
        Config::set('canvastack.log_activity.exceptions.groups', ['admin']);

        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
            'user_group_name' => 'admin',
        ]);

        $this->assertNull($log);
    }

    /**
     * Test that duration is calculated.
     *
     * @return void
     */
    public function test_duration_is_calculated(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
        ]);

        $this->assertNotNull($log->duration_ms);
        $this->assertIsInt($log->duration_ms);
        $this->assertGreaterThanOrEqual(0, $log->duration_ms);
    }

    /**
     * Test that memory usage is calculated.
     *
     * @return void
     */
    public function test_memory_usage_is_calculated(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->logger->log([
            'action' => 'test_action',
        ]);

        $this->assertNotNull($log->memory_usage);
        $this->assertIsInt($log->memory_usage);
    }

    /**
     * Test that request data is sanitized.
     *
     * @return void
     */
    public function test_request_data_is_sanitized(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'POST', [
            'username' => 'testuser',
            'password' => 'secret123',
            'token' => 'abc123',
        ]);

        $log = $this->logger->logRequest($request);

        $this->assertArrayHasKey('username', $log->request_data);
        $this->assertArrayNotHasKey('password', $log->request_data);
        $this->assertArrayNotHasKey('token', $log->request_data);
    }

    /**
     * Test that user group information is logged.
     *
     * @return void
     */
    public function test_user_group_information_is_logged(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Administrator',
        ]);
        $user->assignRole($role);

        Auth::login($user);

        $request = Request::create('/test', 'GET');
        $log = $this->logger->logRequest($request);

        $this->assertEquals($role->id, $log->user_group_id);
        $this->assertEquals($role->name, $log->user_group_name);
        $this->assertEquals($role->description, $log->user_group_info);
    }

    /**
     * Test that timer can be reset.
     *
     * @return void
     */
    public function test_timer_can_be_reset(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log1 = $this->logger->log(['action' => 'test1']);
        $duration1 = $log1->duration_ms;

        usleep(10000); // Sleep 10ms

        $this->logger->resetTimer();

        $log2 = $this->logger->log(['action' => 'test2']);
        $duration2 = $log2->duration_ms;

        $this->assertLessThan($duration1 + 10, $duration2);
    }

    /**
     * Test that action is extracted from request method.
     *
     * @return void
     */
    public function test_action_is_extracted_from_request_method(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $getRequest = Request::create('/test', 'GET');
        $postRequest = Request::create('/test', 'POST');
        $putRequest = Request::create('/test', 'PUT');
        $deleteRequest = Request::create('/test', 'DELETE');

        $getLog = $this->logger->logRequest($getRequest);
        $postLog = $this->logger->logRequest($postRequest);
        $putLog = $this->logger->logRequest($putRequest);
        $deleteLog = $this->logger->logRequest($deleteRequest);

        $this->assertEquals('view', $getLog->action);
        $this->assertEquals('create', $postLog->action);
        $this->assertEquals('update', $putLog->action);
        $this->assertEquals('delete', $deleteLog->action);
    }

    /**
     * Test that IP address is logged.
     *
     * @return void
     */
    public function test_ip_address_is_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $log = $this->logger->logRequest($request);

        $this->assertEquals('192.168.1.1', $log->ip_address);
    }

    /**
     * Test that user agent is logged.
     *
     * @return void
     */
    public function test_user_agent_is_logged(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $log = $this->logger->logRequest($request);

        $this->assertEquals('Mozilla/5.0', $log->user_agent);
    }
}
