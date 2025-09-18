<?php

namespace Canvastack\Canvastack\Tests\Unit\Core\Services;

use Canvastack\Canvastack\Core\Contracts\FileUploadInterface;
use Canvastack\Canvastack\Core\Contracts\PrivilegeInterface;
use Canvastack\Canvastack\Core\Contracts\RouteInfoInterface;
use Canvastack\Canvastack\Core\Services\FileUploadService;
use Canvastack\Canvastack\Core\Services\PrivilegeService;
use Canvastack\Canvastack\Core\Services\RouteInfoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Phase 4 Services Test
 * 
 * Test untuk FileUpload, Privilege, dan RouteInfo services
 * 
 * @author dev@canvastack.com
 * @created 2024-12-19
 * @version 1.0
 */
class Phase4ServicesTest extends TestCase
{
    protected FileUploadInterface $fileUploadService;
    protected PrivilegeInterface $privilegeService;
    protected RouteInfoInterface $routeInfoService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fileUploadService = new FileUploadService();
        $this->privilegeService = new PrivilegeService();
        $this->routeInfoService = new RouteInfoService();
    }

    /** @test */
    public function test_file_upload_service_implements_interface()
    {
        $this->assertInstanceOf(FileUploadInterface::class, $this->fileUploadService);
    }

    /** @test */
    public function test_privilege_service_implements_interface()
    {
        $this->assertInstanceOf(PrivilegeInterface::class, $this->privilegeService);
    }

    /** @test */
    public function test_route_info_service_implements_interface()
    {
        $this->assertInstanceOf(RouteInfoInterface::class, $this->routeInfoService);
    }

    /** @test */
    public function test_file_upload_service_can_set_image_validation()
    {
        $this->fileUploadService->setImageValidation('test_image', 2);
        
        $attributes = $this->fileUploadService->getFileAttributes('test_image');
        
        $this->assertArrayHasKey('file_type', $attributes);
        $this->assertEquals('image', $attributes['file_type']);
        $this->assertArrayHasKey('file_validation', $attributes);
    }

    /** @test */
    public function test_file_upload_service_can_set_file_validation()
    {
        $this->fileUploadService->setFileValidation('test_file', 'document', false, 5);
        
        $attributes = $this->fileUploadService->getFileAttributes('test_file');
        
        $this->assertArrayHasKey('file_type', $attributes);
        $this->assertEquals('document', $attributes['file_type']);
        $this->assertArrayHasKey('file_validation', $attributes);
    }

    /** @test */
    public function test_file_upload_service_can_set_image_elements()
    {
        $this->fileUploadService->setImageElements('profile_image', 2, true, [150, 150]);
        
        $attributes = $this->fileUploadService->getFileAttributes('profile_image');
        
        $this->assertArrayHasKey('file_type', $attributes);
        $this->assertEquals('image', $attributes['file_type']);
        $this->assertArrayHasKey('thumb_name', $attributes);
        $this->assertArrayHasKey('thumb_size', $attributes);
        $this->assertEquals([150, 150], $attributes['thumb_size']);
    }

    /** @test */
    public function test_file_upload_service_can_set_file_elements()
    {
        $this->fileUploadService->setFileElements('document', 'pdf', true, 10);
        
        $attributes = $this->fileUploadService->getFileAttributes('document');
        
        $this->assertArrayHasKey('file_type', $attributes);
        $this->assertEquals('pdf', $attributes['file_type']);
        $this->assertArrayHasKey('file_validation', $attributes);
    }

    /** @test */
    public function test_file_upload_service_can_generate_unique_filename()
    {
        $originalName = 'test-file.jpg';
        $uniqueName = $this->fileUploadService->generateUniqueFilename($originalName);
        
        $this->assertNotEquals($originalName, $uniqueName);
        $this->assertStringContains('.jpg', $uniqueName);
    }

    /** @test */
    public function test_file_upload_service_can_get_allowed_extensions()
    {
        $imageExtensions = $this->fileUploadService->getAllowedExtensions('image');
        $documentExtensions = $this->fileUploadService->getAllowedExtensions('document');
        
        $this->assertIsArray($imageExtensions);
        $this->assertContains('jpg', $imageExtensions);
        $this->assertContains('png', $imageExtensions);
        
        $this->assertIsArray($documentExtensions);
        $this->assertContains('pdf', $documentExtensions);
        $this->assertContains('doc', $documentExtensions);
    }

    /** @test */
    public function test_file_upload_service_can_check_file_type_allowed()
    {
        $this->assertTrue($this->fileUploadService->isFileTypeAllowed('test.jpg', 'image'));
        $this->assertTrue($this->fileUploadService->isFileTypeAllowed('test.pdf', 'document'));
        $this->assertFalse($this->fileUploadService->isFileTypeAllowed('test.exe', 'image'));
    }

    /** @test */
    public function test_file_upload_service_can_clear_file_attributes()
    {
        $this->fileUploadService->setImageValidation('test_image', 2);
        $this->assertNotEmpty($this->fileUploadService->getFileAttributes('test_image'));
        
        $this->fileUploadService->clearFileAttributes('test_image');
        $this->assertEmpty($this->fileUploadService->getFileAttributes('test_image'));
    }

    /** @test */
    public function test_privilege_service_can_initialize()
    {
        $this->privilegeService->initialize();
        
        // Should not throw any exceptions
        $this->assertTrue(true);
    }

    /** @test */
    public function test_privilege_service_can_set_role_group()
    {
        $this->privilegeService->setRoleGroup(2);
        
        $this->assertEquals(2, $this->privilegeService->getRoleGroup());
    }

    /** @test */
    public function test_privilege_service_can_get_module_privileges()
    {
        $privileges = $this->privilegeService->getModulePrivileges(1);
        
        $this->assertIsArray($privileges);
        $this->assertArrayHasKey('menu', $privileges);
        $this->assertArrayHasKey('privilege', $privileges);
        $this->assertArrayHasKey('granted', $privileges);
    }

    /** @test */
    public function test_privilege_service_can_set_module_privileges()
    {
        $result = $this->privilegeService->setModulePrivileges(1);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('role_group', $result);
        $this->assertArrayHasKey('role', $result);
    }

    /** @test */
    public function test_privilege_service_can_check_module_granted()
    {
        $this->privilegeService->setRoleGroup(1);
        $isGranted = $this->privilegeService->isModuleGranted();
        
        $this->assertIsBool($isGranted);
    }

    /** @test */
    public function test_privilege_service_can_get_user_permissions()
    {
        $permissions = $this->privilegeService->getUserPermissions();
        
        $this->assertIsArray($permissions);
    }

    /** @test */
    public function test_privilege_service_can_check_privilege()
    {
        $hasPrivilege = $this->privilegeService->hasPrivilege('create', 1);
        
        $this->assertIsBool($hasPrivilege);
    }

    /** @test */
    public function test_privilege_service_can_check_action_permission()
    {
        $canCreate = $this->privilegeService->canPerformAction('create');
        $canRead = $this->privilegeService->canPerformAction('read');
        $canUpdate = $this->privilegeService->canPerformAction('update');
        $canDelete = $this->privilegeService->canPerformAction('delete');
        
        $this->assertIsBool($canCreate);
        $this->assertIsBool($canRead);
        $this->assertIsBool($canUpdate);
        $this->assertIsBool($canDelete);
    }

    /** @test */
    public function test_privilege_service_can_get_available_actions()
    {
        $actions = $this->privilegeService->getAvailableActions();
        
        $this->assertIsArray($actions);
    }

    /** @test */
    public function test_privilege_service_can_remove_action_buttons()
    {
        $buttons = ['add', 'edit', 'delete'];
        $this->privilegeService->removeActionButtons($buttons);
        
        $removedButtons = $this->privilegeService->getRemovedButtons();
        $this->assertEquals($buttons, $removedButtons);
    }

    /** @test */
    public function test_privilege_service_can_check_root_user()
    {
        $isRoot = $this->privilegeService->isRootUser();
        
        $this->assertIsBool($isRoot);
    }

    /** @test */
    public function test_route_info_service_can_initialize()
    {
        $this->routeInfoService->initialize();
        
        // Should not throw any exceptions
        $this->assertTrue(true);
    }

    /** @test */
    public function test_route_info_service_can_get_page_info()
    {
        $pageInfo = $this->routeInfoService->getPageInfo();
        
        $this->assertIsArray($pageInfo);
        $this->assertArrayHasKey('page_info', $pageInfo);
        $this->assertArrayHasKey('controller_name', $pageInfo);
        $this->assertArrayHasKey('controller_path', $pageInfo);
        $this->assertArrayHasKey('current_route', $pageInfo);
    }

    /** @test */
    public function test_route_info_service_can_get_route_info()
    {
        $routeInfo = $this->routeInfoService->getRouteInfo();
        
        $this->assertIsObject($routeInfo);
    }

    /** @test */
    public function test_route_info_service_can_set_route_page()
    {
        $this->routeInfoService->setRoutePage('test.route');
        
        // Should not throw any exceptions
        $this->assertTrue(true);
    }

    /** @test */
    public function test_route_info_service_can_hide_action_button()
    {
        $this->routeInfoService->hideActionButton();
        
        $actionButtons = $this->routeInfoService->getActionButtons();
        $this->assertEmpty($actionButtons);
    }

    /** @test */
    public function test_route_info_service_can_set_action_buttons()
    {
        $buttons = ['create', 'edit', 'delete'];
        $this->routeInfoService->setActionButtons($buttons);
        
        $actionButtons = $this->routeInfoService->getActionButtons();
        $this->assertEquals($buttons, $actionButtons);
    }

    /** @test */
    public function test_route_info_service_can_get_current_route()
    {
        $currentRoute = $this->routeInfoService->getCurrentRoute();
        
        // In testing environment, this might be null
        $this->assertTrue(is_null($currentRoute) || is_string($currentRoute));
    }

    /** @test */
    public function test_route_info_service_can_check_route_exists()
    {
        // Test with a route that likely doesn't exist
        $exists = $this->routeInfoService->routeExists('non.existent.route');
        
        $this->assertIsBool($exists);
        $this->assertFalse($exists);
    }

    /** @test */
    public function test_route_info_service_can_get_available_actions()
    {
        $actions = $this->routeInfoService->getAvailableActions();
        
        $this->assertIsArray($actions);
    }

    /** @test */
    public function test_route_info_service_can_set_page_name()
    {
        $pageName = 'Test Page';
        $this->routeInfoService->setPageName($pageName);
        
        $this->assertEquals($pageName, $this->routeInfoService->getPageName());
    }

    /** @test */
    public function test_route_info_service_can_set_soft_deleted()
    {
        $this->routeInfoService->setSoftDeleted(true);
        
        $this->assertTrue($this->routeInfoService->isSoftDeleted());
        
        $this->routeInfoService->setSoftDeleted(false);
        
        $this->assertFalse($this->routeInfoService->isSoftDeleted());
    }

    /** @test */
    public function test_route_info_service_can_get_route_lists_info()
    {
        $routeInfo = $this->routeInfoService->getRouteListsInfo('test.route.name');
        
        $this->assertIsArray($routeInfo);
        $this->assertArrayHasKey('base_info', $routeInfo);
        $this->assertArrayHasKey('last_info', $routeInfo);
    }

    /** @test */
    public function test_route_info_service_can_build_action_page()
    {
        $actionRole = [
            'show' => true,
            'create' => true,
            'edit' => true,
            'delete' => true
        ];
        
        $actionPage = $this->routeInfoService->buildActionPage($actionRole, 'Test Item');
        
        $this->assertIsArray($actionPage);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up any test files or data if needed
        $this->fileUploadService->clearFileAttributes();
    }
}