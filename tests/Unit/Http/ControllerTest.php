<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Http;

use Canvastack\Canvastack\Http\Controller;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\JsonResponse;

class ControllerTest extends TestCase
{
    private TestController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestController();
    }

    public function test_success_response_returns_json_with_correct_structure(): void
    {
        $response = $this->controller->testSuccess(['id' => 1], 'Operation successful');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Operation successful', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
    }

    public function test_error_response_returns_json_with_correct_structure(): void
    {
        $response = $this->controller->testError('Something went wrong', ['field' => 'error'], 400);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Something went wrong', $data['message']);
        $this->assertEquals(['field' => 'error'], $data['errors']);
    }

    public function test_json_response_helper_works(): void
    {
        $response = $this->controller->testJsonResponse(['test' => 'data'], 201);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['test' => 'data'], $response->getData(true));
    }

    public function test_created_response_returns_201_status(): void
    {
        $response = $this->controller->testCreatedResponse(['id' => 1]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_not_found_response_returns_404_status(): void
    {
        $response = $this->controller->testNotFoundResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_unauthorized_response_returns_401_status(): void
    {
        $response = $this->controller->testUnauthorizedResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_forbidden_response_returns_403_status(): void
    {
        $response = $this->controller->testForbiddenResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_flash_success_message_works(): void
    {
        $this->controller->testFlashSuccess('Success message');

        $this->assertEquals('Success message', session('success'));
    }

    public function test_flash_error_message_works(): void
    {
        $this->controller->testFlashError('Error message');

        $this->assertEquals('Error message', session('error'));
    }

    public function test_flash_warning_message_works(): void
    {
        $this->controller->testFlashWarning('Warning message');

        $this->assertEquals('Warning message', session('warning'));
    }

    public function test_flash_info_message_works(): void
    {
        $this->controller->testFlashInfo('Info message');

        $this->assertEquals('Info message', session('info'));
    }
}

/**
 * Test Controller for testing base controller functionality.
 */
class TestController extends Controller
{
    public function testSuccess($data, $message)
    {
        return $this->success($data, $message);
    }

    public function testError($message, $errors, $status)
    {
        return $this->error($message, $errors, $status);
    }

    public function testJsonResponse($data, $status)
    {
        return $this->jsonResponse($data, $status);
    }

    public function testCreatedResponse($data)
    {
        return $this->createdResponse($data);
    }

    public function testNotFoundResponse()
    {
        return $this->notFoundResponse();
    }

    public function testUnauthorizedResponse()
    {
        return $this->unauthorizedResponse();
    }

    public function testForbiddenResponse()
    {
        return $this->forbiddenResponse();
    }

    public function testFlashSuccess($message)
    {
        $this->flashSuccess($message);
    }

    public function testFlashError($message)
    {
        $this->flashError($message);
    }

    public function testFlashWarning($message)
    {
        $this->flashWarning($message);
    }

    public function testFlashInfo($message)
    {
        $this->flashInfo($message);
    }
}
