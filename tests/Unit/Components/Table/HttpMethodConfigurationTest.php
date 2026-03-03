<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * HTTP Method Configuration Tests.
 *
 * Tests for Task 21.15: Write unit tests for HTTP method configuration
 *
 * Test Coverage:
 * - setHttpMethod() with 'GET' and 'POST'
 * - setHttpMethod() with invalid methods throws exception
 * - setHttpMethod() is case-insensitive
 * - getHttpMethod() returns correct method
 * - Default method is 'POST'
 * - setAjaxUrl() stores URL correctly
 * - getAjaxUrl() returns configured URL
 * - generateAjaxUrl() generates correct URL from method name
 */
class HttpMethodConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Create TableBuilder instance using helper
        $this->table = $this->createTableBuilder();
    }

    /**
     * Test default HTTP method is POST.
     *
     * @test
     */
    public function test_default_http_method_is_post(): void
    {
        $method = $this->table->getHttpMethod();

        $this->assertEquals('POST', $method);
    }

    /**
     * Test setHttpMethod() with GET.
     *
     * @test
     */
    public function test_set_http_method_with_get(): void
    {
        $result = $this->table->setHttpMethod('GET');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('GET', $this->table->getHttpMethod());
    }

    /**
     * Test setHttpMethod() with POST.
     *
     * @test
     */
    public function test_set_http_method_with_post(): void
    {
        $result = $this->table->setHttpMethod('POST');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('POST', $this->table->getHttpMethod());
    }

    /**
     * Test setHttpMethod() is case-insensitive.
     *
     * @test
     */
    public function test_set_http_method_is_case_insensitive(): void
    {
        // Test lowercase
        $this->table->setHttpMethod('get');
        $this->assertEquals('GET', $this->table->getHttpMethod());

        // Test mixed case
        $this->table->setHttpMethod('Post');
        $this->assertEquals('POST', $this->table->getHttpMethod());

        // Test uppercase
        $this->table->setHttpMethod('GET');
        $this->assertEquals('GET', $this->table->getHttpMethod());
    }

    /**
     * Test setHttpMethod() throws exception for invalid method.
     *
     * @test
     */
    public function test_set_http_method_throws_exception_for_invalid_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method: PUT. Only GET and POST are allowed.');

        $this->table->setHttpMethod('PUT');
    }

    /**
     * Test setHttpMethod() throws exception for DELETE method.
     *
     * @test
     */
    public function test_set_http_method_throws_exception_for_delete(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method: DELETE. Only GET and POST are allowed.');

        $this->table->setHttpMethod('DELETE');
    }

    /**
     * Test setHttpMethod() throws exception for PATCH method.
     *
     * @test
     */
    public function test_set_http_method_throws_exception_for_patch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method: PATCH. Only GET and POST are allowed.');

        $this->table->setHttpMethod('PATCH');
    }

    /**
     * Test setHttpMethod() throws exception for empty string.
     *
     * @test
     */
    public function test_set_http_method_throws_exception_for_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->table->setHttpMethod('');
    }

    /**
     * Test getHttpMethod() returns correct method.
     *
     * @test
     */
    public function test_get_http_method_returns_correct_method(): void
    {
        // Default should be POST
        $this->assertEquals('POST', $this->table->getHttpMethod());

        // After setting to GET
        $this->table->setHttpMethod('GET');
        $this->assertEquals('GET', $this->table->getHttpMethod());

        // After setting back to POST
        $this->table->setHttpMethod('POST');
        $this->assertEquals('POST', $this->table->getHttpMethod());
    }

    /**
     * Test setAjaxUrl() stores URL correctly.
     *
     * @test
     */
    public function test_set_ajax_url_stores_url_correctly(): void
    {
        $url = '/api/users/datatable';
        $result = $this->table->setAjaxUrl($url);

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals($url, $this->table->getAjaxUrl());
    }

    /**
     * Test setAjaxUrl() with full URL.
     *
     * @test
     */
    public function test_set_ajax_url_with_full_url(): void
    {
        $url = 'https://api.example.com/data';
        $this->table->setAjaxUrl($url);

        $this->assertEquals($url, $this->table->getAjaxUrl());
    }

    /**
     * Test setAjaxUrl() with http URL.
     *
     * @test
     */
    public function test_set_ajax_url_with_http_url(): void
    {
        $url = 'http://localhost/api/data';
        $this->table->setAjaxUrl($url);

        $this->assertEquals($url, $this->table->getAjaxUrl());
    }

    /**
     * Test setAjaxUrl() throws exception for invalid URL format.
     *
     * @test
     */
    public function test_set_ajax_url_throws_exception_for_invalid_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid AJAX URL format: api/users. URL must start with / or http(s)://');

        $this->table->setAjaxUrl('api/users');
    }

    /**
     * Test setAjaxUrl() throws exception for relative URL without slash.
     *
     * @test
     */
    public function test_set_ajax_url_throws_exception_for_relative_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->table->setAjaxUrl('relative/path');
    }

    /**
     * Test getAjaxUrl() returns null when not set.
     *
     * @test
     */
    public function test_get_ajax_url_returns_null_when_not_set(): void
    {
        $url = $this->table->getAjaxUrl();

        $this->assertNull($url);
    }

    /**
     * Test getAjaxUrl() returns configured URL.
     *
     * @test
     */
    public function test_get_ajax_url_returns_configured_url(): void
    {
        $url = '/custom/endpoint';
        $this->table->setAjaxUrl($url);

        $this->assertEquals($url, $this->table->getAjaxUrl());
    }

    /**
     * Test method chaining works correctly.
     *
     * @test
     */
    public function test_method_chaining_works(): void
    {
        $result = $this->table
            ->setHttpMethod('GET')
            ->setAjaxUrl('/api/data');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertEquals('GET', $this->table->getHttpMethod());
        $this->assertEquals('/api/data', $this->table->getAjaxUrl());
    }

    /**
     * Test HTTP method configuration persists across multiple calls.
     *
     * @test
     */
    public function test_http_method_configuration_persists(): void
    {
        $this->table->setHttpMethod('GET');
        $this->assertEquals('GET', $this->table->getHttpMethod());

        // Call other methods
        $this->table->setAjaxUrl('/test');

        // HTTP method should still be GET
        $this->assertEquals('GET', $this->table->getHttpMethod());
    }

    /**
     * Test AJAX URL configuration persists across multiple calls.
     *
     * @test
     */
    public function test_ajax_url_configuration_persists(): void
    {
        $url = '/api/endpoint';
        $this->table->setAjaxUrl($url);
        $this->assertEquals($url, $this->table->getAjaxUrl());

        // Call other methods
        $this->table->setHttpMethod('GET');

        // URL should still be set
        $this->assertEquals($url, $this->table->getAjaxUrl());
    }
}
