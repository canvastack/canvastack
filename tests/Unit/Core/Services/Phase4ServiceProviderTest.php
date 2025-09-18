<?php

namespace Canvastack\Canvastack\Tests\Unit\Core\Services;

use Canvastack\Canvastack\Core\Contracts\FileUploadInterface;
use Canvastack\Canvastack\Core\Contracts\PrivilegeInterface;
use Canvastack\Canvastack\Core\Contracts\RouteInfoInterface;
use Canvastack\Canvastack\Core\Services\FileUploadService;
use Canvastack\Canvastack\Core\Services\PrivilegeService;
use Canvastack\Canvastack\Core\Services\RouteInfoService;
use PHPUnit\Framework\TestCase;

/**
 * Phase 4 Service Provider Test
 * 
 * Test untuk memastikan service provider binding bekerja dengan baik
 * 
 * @author dev@canvastack.com
 * @created 2024-12-19
 * @version 1.0
 */
class Phase4ServiceProviderTest extends TestCase
{
    /** @test */
    public function test_file_upload_interface_exists()
    {
        $this->assertTrue(interface_exists(FileUploadInterface::class));
    }

    /** @test */
    public function test_privilege_interface_exists()
    {
        $this->assertTrue(interface_exists(PrivilegeInterface::class));
    }

    /** @test */
    public function test_route_info_interface_exists()
    {
        $this->assertTrue(interface_exists(RouteInfoInterface::class));
    }

    /** @test */
    public function test_file_upload_service_exists()
    {
        $this->assertTrue(class_exists(FileUploadService::class));
    }

    /** @test */
    public function test_privilege_service_exists()
    {
        $this->assertTrue(class_exists(PrivilegeService::class));
    }

    /** @test */
    public function test_route_info_service_exists()
    {
        $this->assertTrue(class_exists(RouteInfoService::class));
    }

    /** @test */
    public function test_file_upload_service_implements_interface()
    {
        $service = new FileUploadService();
        $this->assertInstanceOf(FileUploadInterface::class, $service);
    }

    /** @test */
    public function test_privilege_service_implements_interface()
    {
        $service = new PrivilegeService();
        $this->assertInstanceOf(PrivilegeInterface::class, $service);
    }

    /** @test */
    public function test_route_info_service_implements_interface()
    {
        $service = new RouteInfoService();
        $this->assertInstanceOf(RouteInfoInterface::class, $service);
    }

    /** @test */
    public function test_services_have_required_methods()
    {
        // FileUpload Service Methods
        $fileUploadMethods = [
            'setImageValidation',
            'setFileValidation', 
            'setImageElements',
            'setFileElements',
            'uploadFiles',
            'getFileAttributes',
            'clearFileAttributes',
            'generateUniqueFilename',
            'getAllowedExtensions',
            'isFileTypeAllowed'
        ];

        foreach ($fileUploadMethods as $method) {
            $this->assertTrue(
                method_exists(FileUploadService::class, $method),
                "FileUploadService should have method: {$method}"
            );
        }

        // Privilege Service Methods
        $privilegeMethods = [
            'initialize',
            'setRoleGroup',
            'getRoleGroup',
            'getModulePrivileges',
            'setModulePrivileges',
            'isModuleGranted',
            'getUserPermissions',
            'hasPrivilege',
            'canPerformAction',
            'getAvailableActions',
            'removeActionButtons',
            'isRootUser'
        ];

        foreach ($privilegeMethods as $method) {
            $this->assertTrue(
                method_exists(PrivilegeService::class, $method),
                "PrivilegeService should have method: {$method}"
            );
        }

        // RouteInfo Service Methods
        $routeInfoMethods = [
            'initialize',
            'getPageInfo',
            'getRouteInfo',
            'setRoutePage',
            'hideActionButton',
            'setActionButtons',
            'getActionButtons',
            'getCurrentRoute',
            'routeExists',
            'getAvailableActions',
            'setPageName',
            'getPageName',
            'setSoftDeleted',
            'isSoftDeleted'
        ];

        foreach ($routeInfoMethods as $method) {
            $this->assertTrue(
                method_exists(RouteInfoService::class, $method),
                "RouteInfoService should have method: {$method}"
            );
        }
    }

    /** @test */
    public function test_services_can_be_instantiated_without_errors()
    {
        try {
            $fileUploadService = new FileUploadService();
            $privilegeService = new PrivilegeService();
            $routeInfoService = new RouteInfoService();

            $this->assertNotNull($fileUploadService);
            $this->assertNotNull($privilegeService);
            $this->assertNotNull($routeInfoService);
        } catch (\Throwable $e) {
            $this->fail('Services should be instantiable without errors: ' . $e->getMessage());
        }
    }

    /** @test */
    public function test_services_basic_functionality()
    {
        // Test FileUpload Service
        $fileUploadService = new FileUploadService();
        $fileUploadService->setImageValidation('test_image', 2);
        $attributes = $fileUploadService->getFileAttributes('test_image');
        $this->assertNotEmpty($attributes);

        // Test Privilege Service
        $privilegeService = new PrivilegeService();
        $privilegeService->setRoleGroup(1);
        $this->assertEquals(1, $privilegeService->getRoleGroup());

        // Test RouteInfo Service
        $routeInfoService = new RouteInfoService();
        $routeInfoService->setPageName('Test Page');
        $this->assertEquals('Test Page', $routeInfoService->getPageName());
    }
}