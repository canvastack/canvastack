<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Http\Middleware;

use Canvastack\Canvastack\Http\Middleware\LogActivity;
use Canvastack\Canvastack\Models\ActivityLog;
use Canvastack\Canvastack\Models\User;
use Canvastack\Canvastack\Support\ActivityLogger;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Test for LogActivity middleware.
 */
class LogActivityTest extends TestCase
{
    use RefreshDatabase;

    protected LogActivity $middleware;
    protected ActivityLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new ActivityLogger();
        $this->middleware = new LogActivity($this->logger);
    }

    /**
     * Test that middleware logs activity.
     *
     * @return void
     */
    public function test_middleware_logs_activity(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'method' => 'GET',
            'status' => 'success',
        ]);
    }

    /**
     * Test that middleware logs successful response.
     *
     * @return void
     */
    public function test_middleware_logs_successful_response(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('success', $log->status);
    }

    /**
     * Test that middleware logs failed response.
     *
     * @return void
     */
    public function test_middleware_logs_failed_response(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            return new Response('Not Found', 404);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('failed', $log->status);
    }

    /**
     * Test that middleware logs error response.
     *
     * @return void
     */
    public function test_middleware_logs_error_response(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            return new Response('Internal Server Error', 500);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('error', $log->status);
    }

    /**
     * Test that middleware returns response.
     *
     * @return void
     */
    public function test_middleware_returns_response(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('Test Response', 200);
        });

        $this->assertEquals('Test Response', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that middleware logs POST request.
     *
     * @return void
     */
    public function test_middleware_logs_post_request(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'POST', ['name' => 'Test']);

        $this->middleware->handle($request, function ($req) {
            return new Response('Created', 201);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('POST', $log->method);
        $this->assertEquals('success', $log->status);
    }

    /**
     * Test that middleware logs PUT request.
     *
     * @return void
     */
    public function test_middleware_logs_put_request(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'PUT', ['name' => 'Updated']);

        $this->middleware->handle($request, function ($req) {
            return new Response('Updated', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('PUT', $log->method);
    }

    /**
     * Test that middleware logs DELETE request.
     *
     * @return void
     */
    public function test_middleware_logs_delete_request(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'DELETE');

        $this->middleware->handle($request, function ($req) {
            return new Response('Deleted', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('DELETE', $log->method);
    }

    /**
     * Test that middleware logs without authenticated user.
     *
     * @return void
     */
    public function test_middleware_logs_without_authenticated_user(): void
    {
        $request = Request::create('/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertNull($log->user_id);
    }

    /**
     * Test that middleware calculates duration.
     *
     * @return void
     */
    public function test_middleware_calculates_duration(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET');

        $this->middleware->handle($request, function ($req) {
            usleep(10000); // Sleep 10ms
            return new Response('OK', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertNotNull($log->duration_ms);
        $this->assertGreaterThanOrEqual(10, $log->duration_ms);
    }

    /**
     * Test that middleware logs IP address.
     *
     * @return void
     */
    public function test_middleware_logs_ip_address(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('192.168.1.1', $log->ip_address);
    }

    /**
     * Test that middleware logs user agent.
     *
     * @return void
     */
    public function test_middleware_logs_user_agent(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ]);

        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $log = ActivityLog::latest()->first();
        $this->assertEquals('Mozilla/5.0', $log->user_agent);
    }

    /**
     * Test that middleware handles different status codes.
     *
     * @return void
     */
    public function test_middleware_handles_different_status_codes(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $statusCodes = [
            200 => 'success',
            201 => 'success',
            204 => 'success',
            400 => 'failed',
            401 => 'failed',
            403 => 'failed',
            404 => 'failed',
            500 => 'error',
            502 => 'error',
            503 => 'error',
        ];

        foreach ($statusCodes as $code => $expectedStatus) {
            $request = Request::create('/test', 'GET');

            $this->middleware->handle($request, function ($req) use ($code) {
                return new Response('Response', $code);
            });

            $log = ActivityLog::latest()->first();
            $this->assertEquals($expectedStatus, $log->status, "Status code {$code} should result in {$expectedStatus}");
        }
    }
}
