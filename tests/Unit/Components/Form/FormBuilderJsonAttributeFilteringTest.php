<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Container\Container;
use Mockery;

/**
 * Test for FormBuilder JSON attribute filtering based on permissions.
 *
 * Requirements tested:
 * - Requirement 7: FormBuilder Integration
 *   - Get accessible JSON paths from PermissionRuleManager
 *   - Filter JSON fields before rendering
 *   - Support wildcard path matching
 *   - Handle allowed and denied paths
 */
class FormBuilderJsonAttributeFilteringTest extends TestCase
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that JSON fields are not filtered when no permission is set.
     *
     * @return void
     */
    public function test_json_fields_not_filtered_when_no_permission_set(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->text('title', 'Title');
        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');
        $this->form->text('metadata.featured', 'Featured');

        // No permission set

        // Act
        $html = $this->form->render();

        // Assert - All fields should be present
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringContainsString('name="metadata.seo.description"', $html);
        $this->assertStringContainsString('name="metadata.featured"', $html);
    }

    /**
     * Test that JSON fields are filtered with allowed paths (whitelist).
     *
     * @return void
     */
    public function test_json_fields_filtered_with_allowed_paths(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('title', 'Title');
        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns to return all columns (no column filtering)
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths to return allowed paths
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.title', 'seo.description'],
                'denied' => [],
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Only allowed JSON paths should be present
        $this->assertStringContainsString('name="title"', $html); // Regular field
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringContainsString('name="metadata.seo.description"', $html);
        $this->assertStringNotContainsString('name="metadata.featured"', $html); // Not in allowed list
    }

    /**
     * Test that JSON fields are filtered with denied paths (blacklist).
     *
     * @return void
     */
    public function test_json_fields_filtered_with_denied_paths(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('title', 'Title');
        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths to return denied paths
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => [],
                'denied' => ['featured'],
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Denied paths should not be present
        $this->assertStringContainsString('name="title"', $html); // Regular field
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringContainsString('name="metadata.seo.description"', $html);
        $this->assertStringNotContainsString('name="metadata.featured"', $html); // Denied
    }

    /**
     * Test wildcard matching for allowed paths.
     *
     * @return void
     */
    public function test_wildcard_matching_for_allowed_paths(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');
        $this->form->text('metadata.seo.keywords', 'SEO Keywords');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths with wildcard
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.*'], // Wildcard matches all seo.* paths
                'denied' => [],
            ]);

        // Act
        $html = $this->form->render();

        // Assert - All seo.* paths should be present
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringContainsString('name="metadata.seo.description"', $html);
        $this->assertStringContainsString('name="metadata.seo.keywords"', $html);
        $this->assertStringNotContainsString('name="metadata.featured"', $html); // Not matching wildcard
    }

    /**
     * Test wildcard matching for denied paths.
     *
     * @return void
     */
    public function test_wildcard_matching_for_denied_paths(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');
        $this->form->text('metadata.social.title', 'Social Title');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths with wildcard deny
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => [],
                'denied' => ['seo.*'], // Deny all seo.* paths
            ]);

        // Act
        $html = $this->form->render();

        // Assert - All seo.* paths should be denied
        $this->assertStringNotContainsString('name="metadata.seo.title"', $html);
        $this->assertStringNotContainsString('name="metadata.seo.description"', $html);
        $this->assertStringContainsString('name="metadata.social.title"', $html); // Not matching wildcard
        $this->assertStringContainsString('name="metadata.featured"', $html); // Not matching wildcard
    }

    /**
     * Test that denied paths take precedence over allowed paths.
     *
     * @return void
     */
    public function test_denied_paths_take_precedence_over_allowed(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths with both allowed and denied
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.*'], // Allow all seo.*
                'denied' => ['seo.description'], // But deny seo.description
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Denied should take precedence
        $this->assertStringContainsString('name="metadata.seo.title"', $html); // Allowed
        $this->assertStringNotContainsString('name="metadata.seo.description"', $html); // Denied (precedence)
        $this->assertStringNotContainsString('name="metadata.featured"', $html); // Not in allowed list
    }

    /**
     * Test multiple JSON columns filtering.
     *
     * @return void
     */
    public function test_multiple_json_columns_filtering(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.featured', 'Featured');
        $this->form->text('settings.theme', 'Theme');
        $this->form->text('settings.layout', 'Layout');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths for metadata column
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.title'],
                'denied' => [],
            ]);

        // Mock getAccessibleJsonPaths for settings column
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'settings')
            ->andReturn([
                'allowed' => ['theme'],
                'denied' => [],
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Only allowed paths from each column
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringNotContainsString('name="metadata.featured"', $html);
        $this->assertStringContainsString('name="settings.theme"', $html);
        $this->assertStringNotContainsString('name="settings.layout"', $html);
    }

    /**
     * Test that regular fields are not affected by JSON filtering.
     *
     * @return void
     */
    public function test_regular_fields_not_affected_by_json_filtering(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('title', 'Title');
        $this->form->text('content', 'Content');
        $this->form->text('metadata.seo.title', 'SEO Title');

        // Mock getAccessibleColumns to allow all regular columns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths to deny all JSON paths
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => [],
                'denied' => ['*'], // Deny all
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Regular fields should be present, JSON fields should not
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="content"', $html);
        $this->assertStringNotContainsString('name="metadata.seo.title"', $html);
    }

    /**
     * Test that JSON filtering works when fine-grained permissions are disabled.
     *
     * @return void
     */
    public function test_json_filtering_disabled_when_permissions_disabled(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths to return empty (permissions disabled)
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([]); // Empty means disabled

        // Act
        $html = $this->form->render();

        // Assert - All JSON fields should be present (no filtering)
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringContainsString('name="metadata.featured"', $html);
    }

    /**
     * Test exact path matching (no wildcards).
     *
     * @return void
     */
    public function test_exact_path_matching(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.seo.description', 'SEO Description');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths with exact path
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.title'], // Exact match only
                'denied' => [],
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Only exact match should be present
        $this->assertStringContainsString('name="metadata.seo.title"', $html);
        $this->assertStringNotContainsString('name="metadata.seo.description"', $html);
    }

    /**
     * Test that getField returns null for filtered JSON fields.
     *
     * @return void
     */
    public function test_get_field_returns_null_for_filtered_json_fields(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn([]);

        // Mock getAccessibleJsonPaths
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.title'],
                'denied' => [],
            ]);

        // Act
        $this->form->render(); // Triggers filtering

        // Assert
        $this->assertNotNull($this->form->getField('metadata.seo.title'));
        $this->assertNull($this->form->getField('metadata.featured')); // Filtered out
    }

    /**
     * Test combined column and JSON attribute filtering.
     *
     * @return void
     */
    public function test_combined_column_and_json_filtering(): void
    {
        // Arrange
        $this->form->setContext('admin');
        $this->form->setModel($this->modelMock);
        $this->form->setPermission('posts.edit');

        $this->form->text('title', 'Title');
        $this->form->text('status', 'Status');
        $this->form->text('metadata.seo.title', 'SEO Title');
        $this->form->text('metadata.featured', 'Featured');

        // Mock getAccessibleColumns to filter regular columns
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleColumns')
            ->once()
            ->andReturn(['title']); // Only title allowed

        // Mock getAccessibleJsonPaths to filter JSON paths
        $this->ruleManagerMock
            ->shouldReceive('getAccessibleJsonPaths')
            ->once()
            ->with(1, 'posts.edit', get_class($this->modelMock), 'metadata')
            ->andReturn([
                'allowed' => ['seo.title'],
                'denied' => [],
            ]);

        // Act
        $html = $this->form->render();

        // Assert - Both filters should apply
        $this->assertStringContainsString('name="title"', $html); // Column allowed
        $this->assertStringNotContainsString('name="status"', $html); // Column filtered
        $this->assertStringContainsString('name="metadata.seo.title"', $html); // JSON path allowed
        $this->assertStringNotContainsString('name="metadata.featured"', $html); // JSON path filtered
    }
}
