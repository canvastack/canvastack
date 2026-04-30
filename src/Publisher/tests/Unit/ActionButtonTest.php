<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test action button functionality
 * 
 * Tests the enhanced action button rendering with:
 * - Improved HTML structure
 * - Custom action support
 * - Privilege checking
 * - Tooltips
 * - Confirmation dialogs
 */
class ActionButtonTest extends TestCase
{
    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock session data for privilege checking
        session([
            'privileges' => [
                'role' => ['admin', 'user'],
                'role_group' => 1
            ]
        ]);
    }
    /**
     * Test basic action button rendering
     */
    public function test_basic_action_buttons()
    {
        // Create mock row data
        $row_data = (object) [
            'id' => 1,
            'name' => 'Test User',
            'deleted_at' => null
        ];
        
        // Test with basic actions
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['view', 'edit', 'delete']
        );
        
        // Verify HTML contains action buttons
        $this->assertStringContainsString('btn_view', $html);
        $this->assertStringContainsString('btn_edit', $html);
        $this->assertStringContainsString('btn_delete', $html);
        
        // Verify accessibility attributes
        $this->assertStringContainsString('aria-label', $html);
        $this->assertStringContainsString('role="button"', $html);
        $this->assertStringContainsString('sr-only', $html);
    }
    
    /**
     * Test custom action with string format
     */
    public function test_custom_action_string_format()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['view', 'approve|success|check']
        );
        
        // Verify custom action is rendered
        $this->assertStringContainsString('approve', $html);
        $this->assertStringContainsString('fa-check', $html);
    }
    
    /**
     * Test custom action with array format
     */
    public function test_custom_action_array_format()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            [
                'view',
                'approve' => [
                    'url' => '/admin/users/1/approve',
                    'color' => 'success',
                    'icon' => 'check',
                    'label' => 'Approve User',
                    'tooltip' => 'Approve this user',
                    'confirm' => 'Are you sure?'
                ]
            ]
        );
        
        // Verify custom action with full configuration
        $this->assertStringContainsString('approve', $html);
        $this->assertStringContainsString('Approve User', $html);
        $this->assertStringContainsString('data-confirm', $html);
    }
    
    /**
     * Test XSS protection in action buttons
     */
    public function test_xss_protection()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            [
                'xss' => [
                    'url' => '/test',
                    'label' => '<script>alert("XSS")</script>',
                    'tooltip' => '<img src=x onerror=alert(1)>',
                    'icon' => 'test"><script>alert(2)</script>'
                ]
            ]
        );
        
        // Verify XSS attempts are escaped - no executable script tags
        $this->assertStringNotContainsString('<script>', $html);
        
        // Verify proper escaping is applied
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);
        
        // Verify the dangerous content is in escaped form (inside attribute values)
        // The content appears as: title="&lt;img src=x onerror=alert(1)&gt;"
        // This is safe because the HTML entities prevent execution
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        
        // Verify no unescaped script injection
        $this->assertStringNotContainsString('><script>', $html);
    }
    
    /**
     * Test disabled state for deleted items
     */
    public function test_disabled_state_for_deleted_items()
    {
        $row_data = (object) [
            'id' => 1,
            'deleted_at' => '2024-01-01 00:00:00'
        ];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['view', 'edit', 'delete']
        );
        
        // Verify buttons are disabled
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        
        // Verify restore button is shown
        $this->assertStringContainsString('fa-recycle', $html);
    }
    
    /**
     * Test removed buttons
     */
    public function test_removed_buttons()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['view', 'edit', 'delete'],
            ['delete'] // Remove delete button
        );
        
        // Verify view and edit are present
        $this->assertStringContainsString('btn_view', $html);
        $this->assertStringContainsString('btn_edit', $html);
        
        // Verify delete is not present
        $this->assertStringNotContainsString('btn_delete', $html);
    }
    
    /**
     * Test accessibility attributes
     */
    public function test_accessibility_attributes()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['view', 'edit']
        );
        
        // Verify ARIA attributes
        $this->assertStringContainsString('role="group"', $html);
        $this->assertStringContainsString('aria-label="Row actions"', $html);
        $this->assertStringContainsString('role="toolbar"', $html);
        $this->assertStringContainsString('aria-haspopup="true"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        
        // Verify screen reader text
        $this->assertStringContainsString('sr-only', $html);
    }
    
    /**
     * Test confirmation dialog attributes
     */
    public function test_confirmation_dialog()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['delete']
        );
        
        // Verify confirmation is present
        $this->assertStringContainsString('data-confirm', $html);
        $this->assertStringContainsString('Are you sure', $html);
        $this->assertStringContainsString('onsubmit', $html);
    }
    
    /**
     * Test tooltip attributes
     */
    public function test_tooltip_attributes()
    {
        $row_data = (object) ['id' => 1];
        
        $html = canvastack_table_action_button(
            $row_data,
            'id',
            '/admin/users',
            ['view', 'edit']
        );
        
        // Verify tooltip attributes
        $this->assertStringContainsString('data-toggle="tooltip"', $html);
        $this->assertStringContainsString('title=', $html);
    }
}
