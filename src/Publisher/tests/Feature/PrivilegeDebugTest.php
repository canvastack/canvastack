<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Canvastack\Canvastack\Models\Admin\System\Group;
use Canvastack\Canvastack\Models\Admin\System\User;

class PrivilegeDebugTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        config(['canvastack.controller.security.csrf_protection' => false]);
        config(['canvastack.settings.log_activity.run_status' => false]);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        DB::table('base_preference')->insert([
            'title' => 'Test App',
            'meta_title' => 'Test App',
            'meta_keywords' => 'test',
            'meta_description' => 'Test Description',
            'meta_author' => 'Test Author',
            'email_person' => 'Test Person',
            'email_address' => 'test@example.com',
            'template' => 'default'
        ]);
        
        // Create REAL module that exists in production
        DB::table('base_module')->insertOrIgnore([
            'id' => 1,
            'module_name' => 'Dashboard',
            'route_path' => 'dashboard',
            'parent_name' => null,
            'icon' => 'fa-dashboard',
            'active' => 1
        ]);
        
        $rootGroup = Group::firstOrCreate(
            ['group_name' => 'root'],
            ['group_alias' => 'Root', 'group_info' => 'Root Group', 'active' => 1]
        );
        
        $this->user = User::create([
            'username' => 'testuser',
            'password' => bcrypt('password'),
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'created_by' => 1,
            'active' => 1
        ]);
        
        DB::table('base_user_group')->insert([
            'user_id' => $this->user->id,
            'group_id' => $rootGroup->id
        ]);
        
        $this->actingAs($this->user);
        
        session([
            'id' => $this->user->id,
            'user_id' => $this->user->id,
            'username' => $this->user->username,
            'fullname' => $this->user->fullname,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'user_group' => 'root',
            'group_id' => $rootGroup->id,
            'group_info' => $rootGroup->group_info,
        ]);
    }
    
    /** @test */
    public function debug_privilege_format()
    {
        $module = DB::table('base_module')->where('id', 1)->first();
        
        echo "\n\n=== MODULE DATA ===\n";
        print_r($module);
        
        // Try different formats
        $formats = [
            'Format 1: modules[admin_privilege][route][permission] = id (NO ARRAY)' => [
                'group_name' => 'test_format1_' . uniqid(),
                'group_alias' => 'Test Format 1',
                'group_info' => 'Testing Format 1',
                'active' => 1,
                'modules' => [
                    'admin_privilege' => [
                        $module->route_path => [
                            8 => $module->id,  // NOT an array!
                            4 => $module->id,  // NOT an array!
                        ]
                    ]
                ]
            ],
        ];
        
        foreach ($formats as $formatName => $groupData) {
            echo "\n\n=== TESTING: $formatName ===\n";
            echo "Request Data:\n";
            print_r($groupData);
            
            $response = $this->post(route('system.config.group.store'), $groupData);
            
            echo "\nResponse Status: " . $response->status() . "\n";
            
            if ($response->status() !== 302) {
                echo "Response Content (first 1000 chars):\n";
                echo substr($response->getContent(), 0, 1000) . "\n";
            }
            
            $group = Group::where('group_name', $groupData['group_name'])->first();
            
            if ($group) {
                echo "\nGroup Created: ID = {$group->id}\n";
                
                $privileges = DB::table('base_group_privilege')
                    ->where('group_id', $group->id)
                    ->get();
                
                echo "\nPrivileges Found: " . $privileges->count() . "\n";
                foreach ($privileges as $priv) {
                    echo "  - Module ID: {$priv->module_id}, Admin: {$priv->admin_privilege}, Index: {$priv->index_privilege}\n";
                }
            } else {
                echo "\nGroup NOT created!\n";
            }
        }
        
        $this->assertTrue(true);
    }
}
