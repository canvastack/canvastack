<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Mockery;

/**
 * Test for FormBuilder column filtering based on permissions.
 *
 * Requirements tested:
 * - Requirement 7: FormBuilder Integration
 *   - Get accessible columns from PermissionRuleManager
 *   - Filter fields before rendering
 *   - Only render form fields for accessible columns
 */
class FormBuilderColumnFilteringTest extends TestCase
{
    protected FormBuilder $form;

    protected $ruleManagerMock;

    protected $userMock;

    protected $modelMock;

    protected FieldFactory $fieldFactory;

    protected ValidationCache $validationCache;

    protected function setUp(): void
    {
        parent::setUp();

        // Add encrypter binding for QueryEncryption dependency
        $encrypter = new \Illuminate\Encryption\Encrypter(str_repeat('a', 32), 'AES-256-CBC');
        Container::getInstance()->instance('encrypter', $encrypter);
        Container::getInstance()->instance(\Illuminate\Contracts\Encryption\Encrypter::class, $encrypter);

        // Mock auth manager and guard
        $authManager = Mockery::mock(\Illuminate\Auth\AuthManager::class);
        $guard = Mockery::mock(\Illuminate\Contracts\Auth\Guard::class);

        // Mock user
        $this->userMock = Mockery::mock();
        $this->userMock->id = 1;
        $this->userMock->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $guard->shouldReceive('user')->andReturn($this->userMock);
        $guard->shouldReceive('check')->andReturn(true);
        $guard->shouldReceive('id')->andReturn(1);

        $authManager->shouldReceive('guard')->andReturn($guard);
        $authManager->shouldReceive('user')->andReturn($this->userMock);
        $authManager->shouldReceive('check')->andReturn(true);
        $authManager->shouldReceive('id')->andReturn(1);

        Container::getInstance()->instance('auth', $authManager);
        Container::getInstance()->instance(\Illuminate\Contracts\Auth\Factory::class, $authManager);

        $this->fieldFactory = new FieldFactory();
        $this->validationCache = new ValidationCache();
        $this->form = new FormBuilder($this->fieldFactory, $this->validationCache);

        // Mock PermissionRuleManager
        $this->ruleManagerMock = Mockery::mock(PermissionRuleManager::class);
        Container::getInstance()->instance('canvastack.rbac.rule.manager', $this->ruleManagerMock);

        // Mock model
        $this->modelMock = Mockery::mock();
        $this->modelMock->shouldReceive('getAttribute')->andReturn(null);
    }

    /**
     * Set up auth to return no user (unauthenticated).
     */
    protected function setUpUnauthenticated(): void
    {
        $authManager = Mockery::mock(\Illuminate\Auth\AuthManager::class);
        $guard = Mockery::mock(\Illuminate\Contracts\Auth\Guard::class);

        $guard->shouldReceive('user')->andReturn(null);
        $guard->shouldReceive('check')->andReturn(false);
        $guard->shouldReceive('id')->andReturn(null);

        $authManager->shouldReceive('guard')->andReturn($guard);
        $authManager->shouldReceive('user')->andReturn(null);
        $authManager->shouldReceive('check')->andReturn(false);
        $authManager->shouldReceive('id')->andReturn(null);

        Container::getInstance()->instance('auth', $authManager);
        Container::getInstance()->instance(\Illuminate\Contracts\Auth\Factory::class, $authManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that fields are not filtered when no permission is set.
     *
     * @return void
     */
    public function test_fields_not_filtered_when_no_permission_set(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // No permission set
        // $this->form->setPermission('posts.edit');

        // Act
        $html = $this->form->render();

        // Assert - All fields should be present
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="status"', $html);
    }

    /**
     * Test that fields are not filtered when user is not authenticated.
     *
     * @return void
     */
    public function test_fields_not_filtered_when_user_not_authenticated(): void
    {
        // Arrange
        $this->setUpUnauthenticated();

        $this->form->setContext('admin');
        $this->form->setPermission('posts.edit');
        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Act
        $html = $this->form->render();

        // Assert - All fields should be present
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="status"', $html);
    }

    /**
     * Test that fields are not filtered when no model is set.
     *
     * @return void
     */
    public function test_fields_not_filtered_when_no_model_set(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setPermission('posts.edit');
        // No model set
        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Act
        $html = $this->form->render();

        // Assert - All fields should be present
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="status"', $html);
    }

    /**
     * Test whitelist mode: only accessible columns are rendered.
     *
     * @return void
     */
    public function test_whitelist_mode_only_accessible_columns_rendered(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return whitelist
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn(['name', 'email']); // Only name and email accessible

        // Act
        $html = $this->form->render();

        // Assert - Only name and email should be present
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringNotContainsString('name="status"', $html);
    }

    /**
     * Test blacklist mode: denied columns are not rendered.
     *
     * @return void
     */
    public function test_blacklist_mode_denied_columns_not_rendered(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return blacklist (negative list)
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn(['!status']); // Status is denied

        // Act
        $html = $this->form->render();

        // Assert - Name and email should be present, status should not
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringNotContainsString('name="status"', $html);
    }

    /**
     * Test default deny behavior: all fields removed when no columns returned.
     *
     * @return void
     */
    public function test_default_deny_all_fields_removed(): void
    {
        // Arrange
        // Set config to default deny
        config(['canvastack-rbac.fine_grained.column_level.default_deny' => true]);

        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return empty array (no rules)
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn([]);

        // Act
        $html = $this->form->render();

        // Assert - No fields should be present
        $this->assertStringNotContainsString('name="name"', $html);
        $this->assertStringNotContainsString('name="email"', $html);
        $this->assertStringNotContainsString('name="status"', $html);
    }

    /**
     * Test default allow behavior: all fields kept when no columns returned.
     *
     * @return void
     */
    public function test_default_allow_all_fields_kept(): void
    {
        // Arrange
        // Set config to default allow
        config(['canvastack-rbac.fine_grained.column_level.default_deny' => false]);

        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return empty array (no rules)
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn([]);

        // Act
        $html = $this->form->render();

        // Assert - All fields should be present
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="status"', $html);
    }

    /**
     * Test filtering works in view mode.
     *
     * @return void
     */
    public function test_filtering_works_in_view_mode(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');
        $this->form->viewMode(true);

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return whitelist
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn(['name', 'email']); // Only name and email accessible

        // Act
        $html = $this->form->render();

        // Assert - Only name and email should be present in view mode
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Email', $html);
        // Status label should not be present
        $this->assertStringNotContainsString('Status', $html);
    }

    /**
     * Test multiple denied columns in blacklist mode.
     *
     * @return void
     */
    public function test_multiple_denied_columns_blacklist_mode(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');
        $this->form->text('featured', 'Featured');

        // Mock PermissionRuleManager to return blacklist with multiple denied columns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn(['!status', '!featured']); // Status and featured are denied

        // Act
        $html = $this->form->render();

        // Assert - Name and email should be present, status and featured should not
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringNotContainsString('name="status"', $html);
        $this->assertStringNotContainsString('name="featured"', $html);
    }

    /**
     * Test that getField returns null for filtered fields.
     *
     * @return void
     */
    public function test_get_field_returns_null_for_filtered_fields(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return whitelist
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn(['name', 'email']); // Only name and email accessible

        // Act
        $this->form->render(); // This triggers filtering

        // Assert
        $this->assertNotNull($this->form->getField('name'));
        $this->assertNotNull($this->form->getField('email'));
        $this->assertNull($this->form->getField('status')); // Filtered out
    }

    /**
     * Test that getFields returns only accessible fields after filtering.
     *
     * @return void
     */
    public function test_get_fields_returns_only_accessible_fields(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('name', 'Name');
        $this->form->email('email', 'Email');
        $this->form->text('status', 'Status');

        // Mock PermissionRuleManager to return whitelist
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock))
            ->andReturn(['name', 'email']); // Only name and email accessible

        // Act
        $this->form->render(); // This triggers filtering
        $fields = $this->form->getFields();

        // Assert
        $this->assertCount(2, $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayNotHasKey('status', $fields);
    }
}
