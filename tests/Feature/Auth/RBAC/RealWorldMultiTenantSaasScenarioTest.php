<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\Gate;
use Canvastack\Canvastack\Auth\RBAC\Traits\HasPermissionScopes;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\Role;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;

/**
 * Real-world multi-tenant SaaS scenario E2E test.
 *
 * Simulates a multi-tenant SaaS application with organizations, teams, and projects.
 * Tests tenant isolation, team-based access, and subscription-based features.
 */
class RealWorldMultiTenantSaasScenarioTest extends TestCase
{
    protected $organizationModel;

    protected $projectModel;

    protected $documentModel;

    protected static $authGuard = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard
        if (self::$authGuard === null) {
            self::$authGuard = new class () {
                protected $user = null;

                public function user()
                {
                    return $this->user;
                }

                public function id()
                {
                    return $this->user ? $this->user->id : null;
                }

                public function check()
                {
                    return $this->user !== null;
                }

                public function setUser($user)
                {
                    $this->user = $user;
                }
            };
        }

        $app = \Illuminate\Container\Container::getInstance();
        $app->singleton('auth', function () {
            return new class (self::$authGuard) implements \Illuminate\Contracts\Auth\Factory {
                private $guard;

                public function __construct($guard)
                {
                    $this->guard = $guard;
                }

                public function guard($name = null)
                {
                    return $this->guard;
                }

                public function user()
                {
                    return $this->guard->user();
                }

                public function id()
                {
                    return $this->guard->id();
                }

                public function check(): bool
                {
                    return $this->guard->check();
                }

                public function shouldUse($name)
                {
                    // Not implemented
                }

                public function setDefaultDriver($name)
                {
                    // Not implemented
                }

                public function userResolver()
                {
                    return fn () => $this->guard->user();
                }

                public function resolveUsersUsing(\Closure $userResolver)
                {
                    // Not implemented
                }

                public function extend($driver, \Closure $callback)
                {
                    // Not implemented
                }

                public function provider($name, \Closure $callback)
                {
                    // Not implemented
                }

                public function hasResolvedGuards()
                {
                    return true;
                }

                public function forgetGuards()
                {
                    // Not implemented
                }
            };
        });

        // Add organization_id and team_id to users table if not exists
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();
        
        if (!$schema->hasColumn('users', 'organization_id')) {
            $schema->table('users', function ($table) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('password');
            });
        }
        
        if (!$schema->hasColumn('users', 'team_id')) {
            $schema->table('users', function ($table) {
                $table->unsignedBigInteger('team_id')->nullable()->after('organization_id');
            });
        }

        // Create organizations table
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->create('organizations', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('plan')->default('free'); // free, pro, enterprise
            $table->integer('max_users')->default(5);
            $table->integer('max_projects')->default(3);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        // Create projects table
        $capsule->getSchemaBuilder()->create('projects', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('owner_id');
            $table->string('visibility')->default('private'); // private, team, organization
            $table->string('status')->default('active'); // active, archived
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Create documents table
        $capsule->getSchemaBuilder()->create('documents', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('author_id');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Define models
        $this->organizationModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'organizations';

            protected $fillable = ['name', 'plan', 'max_users', 'max_projects', 'features'];

            protected $casts = ['features' => 'array'];
        };

        $this->projectModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'projects';

            protected $fillable = [
                'name', 'description', 'organization_id', 'team_id',
                'owner_id', 'visibility', 'status', 'settings',
            ];

            protected $casts = ['settings' => 'array'];
        };

        $this->documentModel = new class () extends Model {
            use HasPermissionScopes;

            protected $table = 'documents';

            protected $fillable = [
                'title', 'content', 'project_id', 'author_id', 'status', 'metadata',
            ];

            protected $casts = ['metadata' => 'array'];
        };
    }

    protected function actingAs($user): void
    {
        self::$authGuard->setUser($user);
    }

    protected function tearDown(): void
    {
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->dropIfExists('documents');
        $capsule->getSchemaBuilder()->dropIfExists('projects');
        $capsule->getSchemaBuilder()->dropIfExists('organizations');

        parent::tearDown();
    }

    /**
     * Test tenant isolation in multi-tenant SaaS.
     *
     * @return void
     */
    public function test_tenant_isolation_workflow(): void
    {
        // Arrange - Create roles
        $memberRole = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'description' => 'Organization member',
        ]);

        // Create organizations
        $org1 = $this->organizationModel::create([
            'name' => 'Organization 1',
            'plan' => 'pro',
            'max_users' => 10,
            'max_projects' => 10,
        ]);

        $org2 = $this->organizationModel::create([
            'name' => 'Organization 2',
            'plan' => 'free',
            'max_users' => 5,
            'max_projects' => 3,
        ]);

        // Create users
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@org1.com',
            'password' => 'password',
            'organization_id' => $org1->id,
        ]);
        $user1->roles()->attach($memberRole->id);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@org2.com',
            'password' => 'password',
            'organization_id' => $org2->id,
        ]);
        $user2->roles()->attach($memberRole->id);

        // Create projects
        $project1 = $this->projectModel::create([
            'name' => 'Project 1',
            'organization_id' => $org1->id,
            'owner_id' => $user1->id,
            'visibility' => 'organization',
        ]);

        $project2 = $this->projectModel::create([
            'name' => 'Project 2',
            'organization_id' => $org2->id,
            'owner_id' => $user2->id,
            'visibility' => 'organization',
        ]);

        // Create permission
        $viewProjectsPermission = Permission::create([
            'name' => 'projects.view',
            'display_name' => 'View Projects',
            'module' => 'saas',
        ]);

        // Attach permission to role
        $memberRole->permissions()->attach($viewProjectsPermission->id);

        // Add row-level rule: Users can only view projects in their organization
        PermissionRule::create([
            'permission_id' => $viewProjectsPermission->id,
            'rule_type' => 'row',
            'rule_config' => [
                'model' => get_class($this->projectModel),
                'conditions' => ['organization_id' => '{{auth.organization}}'],
                'operator' => 'AND',
            ],
            'priority' => 0,
        ]);

        $gate = app(Gate::class);

        // Act & Assert - User 1 can only access org 1 projects
        $this->actingAs($user1);

        $this->assertTrue(
            $gate->canAccessRow($user1, 'projects.view', $project1),
            'User 1 should access org 1 project'
        );

        $this->assertFalse(
            $gate->canAccessRow($user1, 'projects.view', $project2),
            'User 1 should not access org 2 project'
        );

        // User 2 can only access org 2 projects
        $this->actingAs($user2);

        $this->assertTrue(
            $gate->canAccessRow($user2, 'projects.view', $project2),
            'User 2 should access org 2 project'
        );

        $this->assertFalse(
            $gate->canAccessRow($user2, 'projects.view', $project1),
            'User 2 should not access org 1 project'
        );

        // Query scope enforces tenant isolation
        $this->actingAs($user1);
        $user1Projects = $this->projectModel::byPermission($user1->id, 'projects.view')->get();
        $this->assertCount(1, $user1Projects);
        $this->assertEquals($org1->id, $user1Projects->first()->organization_id);

        $this->actingAs($user2);
        $user2Projects = $this->projectModel::byPermission($user2->id, 'projects.view')->get();
        $this->assertCount(1, $user2Projects);
        $this->assertEquals($org2->id, $user2Projects->first()->organization_id);
    }

    /**
     * Test subscription-based feature access.
     *
     * @return void
     */
    public function test_subscription_based_feature_access(): void
    {
        // Arrange - Create roles
        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Project manager',
        ]);

        $freeOrg = $this->organizationModel::create([
            'name' => 'Free Org',
            'plan' => 'free',
            'features' => ['basic_projects', 'basic_documents'],
        ]);

        $proOrg = $this->organizationModel::create([
            'name' => 'Pro Org',
            'plan' => 'pro',
            'features' => ['basic_projects', 'basic_documents', 'advanced_analytics', 'api_access'],
        ]);

        $enterpriseOrg = $this->organizationModel::create([
            'name' => 'Enterprise Org',
            'plan' => 'enterprise',
            'features' => ['basic_projects', 'basic_documents', 'advanced_analytics', 'api_access', 'custom_branding', 'sso'],
        ]);

        $freeUser = User::create([
            'name' => 'Free User',
            'email' => 'free@example.com',
            'password' => 'password',
            'organization_id' => $freeOrg->id,
        ]);
        $freeUser->roles()->attach($managerRole->id);

        $proUser = User::create([
            'name' => 'Pro User',
            'email' => 'pro@example.com',
            'password' => 'password',
            'organization_id' => $proOrg->id,
        ]);
        $proUser->roles()->attach($managerRole->id);

        $enterpriseUser = User::create([
            'name' => 'Enterprise User',
            'email' => 'enterprise@example.com',
            'password' => 'password',
            'organization_id' => $enterpriseOrg->id,
        ]);
        $enterpriseUser->roles()->attach($managerRole->id);

        // Create projects
        $freeProject = $this->projectModel::create([
            'name' => 'Free Project',
            'organization_id' => $freeOrg->id,
            'owner_id' => $freeUser->id,
            'settings' => ['analytics' => false, 'api_enabled' => false],
        ]);

        $proProject = $this->projectModel::create([
            'name' => 'Pro Project',
            'organization_id' => $proOrg->id,
            'owner_id' => $proUser->id,
            'settings' => ['analytics' => true, 'api_enabled' => true],
        ]);

        // Create permission
        $manageProjectsPermission = Permission::create([
            'name' => 'projects.manage',
            'display_name' => 'Manage Projects',
            'module' => 'saas',
        ]);

        // Attach permission to role
        $managerRole->permissions()->attach($manageProjectsPermission->id);

        // Add JSON attribute rule: Free users cannot access advanced settings
        PermissionRule::create([
            'permission_id' => $manageProjectsPermission->id,
            'rule_type' => 'json_attribute',
            'rule_config' => [
                'model' => get_class($this->projectModel),
                'json_column' => 'settings',
                'allowed_paths' => ['basic.*', 'notifications.*'],
                'denied_paths' => ['analytics.*', 'api_enabled', 'custom_domain'],
                'path_separator' => '.',
            ],
            'priority' => 0,
        ]);

        $gate = app(Gate::class);

        // Act & Assert - Free user cannot access advanced settings
        $this->actingAs($freeUser);

        $this->assertFalse(
            $gate->canAccessJsonAttribute($freeUser, 'projects.manage', $freeProject, 'settings', 'analytics'),
            'Free user should not access analytics setting'
        );

        $this->assertFalse(
            $gate->canAccessJsonAttribute($freeUser, 'projects.manage', $freeProject, 'settings', 'api_enabled'),
            'Free user should not access API setting'
        );

        // Pro user can access advanced settings with override
        UserPermissionOverride::create([
            'user_id' => $proUser->id,
            'permission_id' => $manageProjectsPermission->id,
            'model_type' => get_class($this->projectModel),
            'field_name' => 'settings.analytics',
            'allowed' => true,
        ]);

        UserPermissionOverride::create([
            'user_id' => $proUser->id,
            'permission_id' => $manageProjectsPermission->id,
            'model_type' => get_class($this->projectModel),
            'field_name' => 'settings.api_enabled',
            'allowed' => true,
        ]);

        $this->actingAs($proUser);

        $this->assertTrue(
            $gate->canAccessJsonAttribute($proUser, 'projects.manage', $proProject, 'settings', 'analytics'),
            'Pro user should access analytics setting with override'
        );

        $this->assertTrue(
            $gate->canAccessJsonAttribute($proUser, 'projects.manage', $proProject, 'settings', 'api_enabled'),
            'Pro user should access API setting with override'
        );
    }

    /**
     * Test team-based project access.
     *
     * @return void
     */
    public function test_team_based_project_access(): void
    {
        // Arrange - Create roles
        $memberRole = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'description' => 'Team member',
        ]);

        $org = $this->organizationModel::create([
            'name' => 'Organization',
            'plan' => 'pro',
        ]);

        $teamMember = User::create([
            'name' => 'Team Member',
            'email' => 'member@example.com',
            'password' => 'password',
            'organization_id' => $org->id,
            'team_id' => 1,
        ]);
        $teamMember->roles()->attach($memberRole->id);

        $nonTeamMember = User::create([
            'name' => 'Non Team Member',
            'email' => 'nonmember@example.com',
            'password' => 'password',
            'organization_id' => $org->id,
            'team_id' => 2,
        ]);
        $nonTeamMember->roles()->attach($memberRole->id);

        // Create projects with different visibility
        $privateProject = $this->projectModel::create([
            'name' => 'Private Project',
            'organization_id' => $org->id,
            'team_id' => 1,
            'owner_id' => $teamMember->id,
            'visibility' => 'private',
        ]);

        $teamProject = $this->projectModel::create([
            'name' => 'Team Project',
            'organization_id' => $org->id,
            'team_id' => 1,
            'owner_id' => $teamMember->id,
            'visibility' => 'team',
        ]);

        $orgProject = $this->projectModel::create([
            'name' => 'Organization Project',
            'organization_id' => $org->id,
            'team_id' => 1,
            'owner_id' => $teamMember->id,
            'visibility' => 'organization',
        ]);

        // Create permission
        $viewProjectsPermission = Permission::create([
            'name' => 'projects.view',
            'display_name' => 'View Projects',
            'module' => 'saas',
        ]);

        // Attach permission to role
        $memberRole->permissions()->attach($viewProjectsPermission->id);

        // Add conditional rule for team visibility
        PermissionRule::create([
            'permission_id' => $viewProjectsPermission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->projectModel),
                'condition' => "(visibility === 'organization' AND organization_id === {{auth.organization}}) OR (visibility === 'team' AND team_id === {{auth.team}}) OR (visibility === 'private' AND owner_id === {{auth.id}})",
                'allowed_operators' => ['===', 'AND', 'OR'],
            ],
            'priority' => 0,
        ]);

        $gate = app(Gate::class);

        // Act & Assert - Team member access
        $this->actingAs($teamMember);

        $this->assertTrue(
            $gate->canAccessRow($teamMember, 'projects.view', $privateProject),
            'Team member should access own private project'
        );

        $this->assertTrue(
            $gate->canAccessRow($teamMember, 'projects.view', $teamProject),
            'Team member should access team project'
        );

        $this->assertTrue(
            $gate->canAccessRow($teamMember, 'projects.view', $orgProject),
            'Team member should access organization project'
        );

        // Non-team member access
        $this->actingAs($nonTeamMember);

        $this->assertFalse(
            $gate->canAccessRow($nonTeamMember, 'projects.view', $privateProject),
            'Non-team member should not access private project'
        );

        $this->assertFalse(
            $gate->canAccessRow($nonTeamMember, 'projects.view', $teamProject),
            'Non-team member should not access team project'
        );

        $this->assertTrue(
            $gate->canAccessRow($nonTeamMember, 'projects.view', $orgProject),
            'Non-team member should access organization project'
        );

        // Query scope filters correctly
        $this->actingAs($teamMember);
        $teamMemberProjects = $this->projectModel::byPermission($teamMember->id, 'projects.view')->get();
        $this->assertCount(3, $teamMemberProjects);

        $this->actingAs($nonTeamMember);
        $nonTeamMemberProjects = $this->projectModel::byPermission($nonTeamMember->id, 'projects.view')->get();
        $this->assertCount(1, $nonTeamMemberProjects);
        $this->assertEquals('organization', $nonTeamMemberProjects->first()->visibility);
    }

    /**
     * Test document access within projects.
     *
     * @return void
     */
    public function test_document_access_within_projects(): void
    {
        // Arrange - Create roles
        $ownerRole = Role::create([
            'name' => 'owner',
            'display_name' => 'Owner',
            'description' => 'Project owner',
        ]);

        $contributorRole = Role::create([
            'name' => 'contributor',
            'display_name' => 'Contributor',
            'description' => 'Project contributor',
        ]);

        $org = $this->organizationModel::create([
            'name' => 'Organization',
            'plan' => 'pro',
        ]);

        $projectOwner = User::create([
            'name' => 'Project Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'organization_id' => $org->id,
        ]);
        $projectOwner->roles()->attach($ownerRole->id);

        $contributor = User::create([
            'name' => 'Contributor',
            'email' => 'contributor@example.com',
            'password' => 'password',
            'organization_id' => $org->id,
        ]);
        $contributor->roles()->attach($contributorRole->id);

        $project = $this->projectModel::create([
            'name' => 'Project',
            'organization_id' => $org->id,
            'owner_id' => $projectOwner->id,
            'visibility' => 'organization',
        ]);

        $draftDoc = $this->documentModel::create([
            'title' => 'Draft Document',
            'content' => 'Draft content',
            'project_id' => $project->id,
            'author_id' => $projectOwner->id,
            'status' => 'draft',
            'metadata' => ['version' => 1, 'confidential' => false],
        ]);

        $publishedDoc = $this->documentModel::create([
            'title' => 'Published Document',
            'content' => 'Published content',
            'project_id' => $project->id,
            'author_id' => $projectOwner->id,
            'status' => 'published',
            'metadata' => ['version' => 2, 'confidential' => false],
        ]);

        $confidentialDoc = $this->documentModel::create([
            'title' => 'Confidential Document',
            'content' => 'Confidential content',
            'project_id' => $project->id,
            'author_id' => $projectOwner->id,
            'status' => 'published',
            'metadata' => ['version' => 1, 'confidential' => true],
        ]);

        // Create permissions
        $viewDocsPermission = Permission::create([
            'name' => 'documents.view',
            'display_name' => 'View Documents',
            'module' => 'saas',
        ]);

        $editDocsPermission = Permission::create([
            'name' => 'documents.edit',
            'display_name' => 'Edit Documents',
            'module' => 'saas',
        ]);

        // Attach permissions to roles
        $ownerRole->permissions()->attach([$viewDocsPermission->id, $editDocsPermission->id]);
        $contributorRole->permissions()->attach($viewDocsPermission->id);

        // Add conditional rule: Can only view published or own draft documents
        PermissionRule::create([
            'permission_id' => $viewDocsPermission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->documentModel),
                'condition' => "status === 'published' OR (status === 'draft' AND author_id === {{auth.id}})",
                'allowed_operators' => ['===', 'AND', 'OR'],
            ],
            'priority' => 0,
        ]);

        // Add conditional rule: Can only edit own draft documents
        PermissionRule::create([
            'permission_id' => $editDocsPermission->id,
            'rule_type' => 'conditional',
            'rule_config' => [
                'model' => get_class($this->documentModel),
                'condition' => "status === 'draft' AND author_id === {{auth.id}}",
                'allowed_operators' => ['===', 'AND'],
            ],
            'priority' => 0,
        ]);

        // Add column-level rule: Cannot edit metadata
        PermissionRule::create([
            'permission_id' => $editDocsPermission->id,
            'rule_type' => 'column',
            'rule_config' => [
                'model' => get_class($this->documentModel),
                'allowed_columns' => ['title', 'content', 'status'],
                'denied_columns' => ['metadata', 'author_id', 'project_id'],
                'mode' => 'whitelist',
            ],
            'priority' => 0,
        ]);

        $gate = app(Gate::class);

        // Act & Assert - Project owner access
        $this->actingAs($projectOwner);

        $this->assertTrue(
            $gate->canAccessRow($projectOwner, 'documents.view', $draftDoc),
            'Owner should view own draft'
        );

        $this->assertTrue(
            $gate->canAccessRow($projectOwner, 'documents.view', $publishedDoc),
            'Owner should view published doc'
        );

        $this->assertTrue(
            $gate->canAccessRow($projectOwner, 'documents.edit', $draftDoc),
            'Owner should edit own draft'
        );

        $this->assertFalse(
            $gate->canAccessRow($projectOwner, 'documents.edit', $publishedDoc),
            'Owner should not edit published doc'
        );

        // Contributor access
        $this->actingAs($contributor);

        $this->assertFalse(
            $gate->canAccessRow($contributor, 'documents.view', $draftDoc),
            'Contributor should not view other draft'
        );

        $this->assertTrue(
            $gate->canAccessRow($contributor, 'documents.view', $publishedDoc),
            'Contributor should view published doc'
        );

        $this->assertFalse(
            $gate->canAccessRow($contributor, 'documents.edit', $draftDoc),
            'Contributor should not edit other draft'
        );

        // Column access
        $this->actingAs($projectOwner);

        $this->assertTrue(
            $gate->canAccessColumn($projectOwner, 'documents.edit', $draftDoc, 'title'),
            'Owner should edit title'
        );

        $this->assertFalse(
            $gate->canAccessColumn($projectOwner, 'documents.edit', $draftDoc, 'metadata'),
            'Owner should not edit metadata'
        );
    }
}
