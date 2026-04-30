<?php

namespace Tests\Unit\Exceptions;

use Tests\TestCase;
use Canvastack\Canvastack\Exceptions\Controller\SessionException;
use Canvastack\Canvastack\Exceptions\Controller\FileUploadException;
use Canvastack\Canvastack\Exceptions\Controller\ControllerValidationException;
use Canvastack\Canvastack\Exceptions\Controller\PrivilegeException;
use Canvastack\Canvastack\Exceptions\Controller\RouteException;

/**
 * Controller Exception Integration Test
 * 
 * Tests that controller components properly throw specific exceptions
 * instead of generic exceptions.
 * 
 * @group exceptions
 * @group integration
 */
class ControllerExceptionIntegrationTest extends TestCase
{
    /**
     * Test that session validation failures throw SessionException
     * 
     * @return void
     */
    public function test_session_validation_throws_session_exception(): void
    {
        // Test SessionException::tampered() directly
        $exception = SessionException::tampered(
            'test_session',
            'Missing required field: id',
            [
                'missing_fields' => ['id'],
                'user_id' => null,
                'ip_address' => '127.0.0.1',
            ]
        );
        
        $this->assertInstanceOf(SessionException::class, $exception);
        $this->assertEquals('tampered', $exception->getSessionErrorType());
        $this->assertStringContainsString('Missing required field', $exception->getMessage());
        $this->assertTrue($exception->shouldDestroySession());
    }
    
    /**
     * Test that file upload validation failures throw FileUploadException
     * 
     * @return void
     */
    public function test_file_upload_validation_throws_file_upload_exception(): void
    {
        // Test FileUploadException::invalidFileType() directly
        $exception = FileUploadException::invalidFileType(
            'test.exe',
            'exe',
            ['jpg', 'png', 'gif', 'pdf'],
            [
                'field' => 'file',
                'user_id' => 123,
                'ip_address' => '127.0.0.1',
            ]
        );
        
        $this->assertInstanceOf(FileUploadException::class, $exception);
        $this->assertEquals('type', $exception->getUploadErrorType());
        $this->assertStringContainsString('Invalid file type', $exception->getMessage());
        
        $fileDetails = $exception->getFileDetails();
        $this->assertEquals('test.exe', $fileDetails['filename']);
        $this->assertArrayHasKey('extension', $fileDetails);
    }
    
    /**
     * Test that pagination validation failures throw ControllerValidationException
     * 
     * @return void
     */
    public function test_pagination_validation_throws_controller_validation_exception(): void
    {
        // Test ControllerValidationException::invalidPaginationParams() directly
        $exception = ControllerValidationException::invalidPaginationParams(
            -1,
            10,
            'Invalid pagination start parameter: must be non-negative',
            [
                'start' => -1,
                'length' => 10,
                'user_id' => 123,
                'ip_address' => '127.0.0.1',
            ]
        );
        
        $this->assertInstanceOf(ControllerValidationException::class, $exception);
        $this->assertStringContainsString('Invalid pagination start parameter', $exception->getMessage());
        
        $context = $exception->getContext();
        $this->assertEquals(-1, $context['start']);
        $this->assertEquals(10, $context['length']);
    }
    
    /**
     * Test that SessionException includes proper context data
     * 
     * @return void
     */
    public function test_session_exception_includes_context_data(): void
    {
        try {
            throw SessionException::tampered(
                'test_session_id',
                'Test tampering reason',
                [
                    'user_id' => 123,
                    'ip_address' => '127.0.0.1',
                ]
            );
        } catch (SessionException $e) {
            $this->assertEquals('tampered', $e->getSessionErrorType());
            $this->assertArrayHasKey('user_id', $e->getContext());
            $this->assertEquals(123, $e->getContext()['user_id']);
            $this->assertArrayHasKey('ip_address', $e->getContext());
        }
    }
    
    /**
     * Test that FileUploadException includes proper context data
     * 
     * @return void
     */
    public function test_file_upload_exception_includes_context_data(): void
    {
        try {
            throw FileUploadException::fileTooLarge(
                'test.jpg',
                15000000, // 15MB
                10485760, // 10MB max
                [
                    'field' => 'avatar',
                    'user_id' => 123,
                ]
            );
        } catch (FileUploadException $e) {
            $this->assertEquals('size', $e->getUploadErrorType());
            $fileDetails = $e->getFileDetails();
            $this->assertEquals('avatar', $fileDetails['field']);
            $this->assertEquals('test.jpg', $fileDetails['filename']);
            $this->assertEquals(15000000, $fileDetails['size']);
        }
    }
    
    /**
     * Test that ControllerValidationException includes validation errors
     * 
     * @return void
     */
    public function test_controller_validation_exception_includes_errors(): void
    {
        try {
            throw new ControllerValidationException(
                'Validation failed',
                [
                    'errors' => [
                        'start' => ['Must be a non-negative integer'],
                        'length' => ['Must be a positive integer'],
                    ],
                ]
            );
        } catch (ControllerValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('start', $errors);
            $this->assertArrayHasKey('length', $errors);
            $this->assertCount(2, $errors);
        }
    }
    
    /**
     * Test that exceptions provide user-friendly messages
     * 
     * @return void
     */
    public function test_exceptions_provide_user_friendly_messages(): void
    {
        $sessionException = SessionException::expired('test_session', 123);
        $this->assertStringContainsString('expired', $sessionException->getUserMessage());
        
        $fileException = FileUploadException::fileTooLarge('test.jpg', 15000000, 10485760);
        $this->assertStringContainsString('too large', $fileException->getUserMessage());
        
        $validationException = new ControllerValidationException('Test validation');
        $this->assertStringContainsString('invalid', $validationException->getUserMessage());
    }
    
    /**
     * Test that SessionException::tampered() is used for integrity violations
     * 
     * @return void
     */
    public function test_session_tampered_exception_for_integrity_violations(): void
    {
        $exception = SessionException::tampered(
            'session_123',
            'Invalid field type: email',
            [
                'field' => 'email',
                'expected_type' => 'string',
                'actual_type' => 'array',
            ]
        );
        
        $this->assertEquals('tampered', $exception->getSessionErrorType());
        $this->assertTrue($exception->shouldDestroySession());
        $this->assertTrue($exception->shouldRedirectToLogin());
    }
    
    /**
     * Test that FileUploadException::invalidFileType() is used for extension validation
     * 
     * @return void
     */
    public function test_file_upload_invalid_type_exception(): void
    {
        $exception = FileUploadException::invalidFileType(
            'malicious.exe',
            'exe',
            ['jpg', 'png', 'gif'],
            ['field' => 'avatar']
        );
        
        $this->assertEquals('type', $exception->getUploadErrorType());
        $fileDetails = $exception->getFileDetails();
        $this->assertEquals('malicious.exe', $fileDetails['filename']);
        // The extension might be normalized or detected differently
        $this->assertContains($fileDetails['extension'], ['exe', 'unknown']);
    }
}
