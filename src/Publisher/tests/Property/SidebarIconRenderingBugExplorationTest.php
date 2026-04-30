<?php

namespace Tests\Property;

use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test for Sidebar Icon Rendering
 * 
 * **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * **DO NOT attempt to fix the test or the code when it fails**
 * **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 * 
 * Uses Eris property-based testing to surface counterexamples that demonstrate
 * the bug where canvastack_sidebar_menu() strips icon HTML, resulting in empty
 * <span class="icon"></span> tags instead of preserving Font Awesome markup.
 * 
 * **Validates: Requirements 2.1, 2.2, 2.3**
 * 
 * @group property
 * @group bugfix
 * @group sidebar-icons
 */
class SidebarIconRenderingBugExplorationTest extends TestCase
{
    use TestTrait;
    
    /**
     * Property 1: Fault Condition - Icon HTML Preservation
     * 
     * **Validates: Requirements 2.1, 2.2, 2.3**
     * 
     * For any icon data where the icon contains HTML markup (specifically <i> tags
     * with Font Awesome classes) from the database, the canvastack_sidebar_menu
     * function SHALL preserve the icon HTML without stripping tags, rendering it
     * as <span class="icon"><i class="fa fa-home"></i></span> with the icon markup intact.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * - Icon HTML is stripped by strip_tags() at lines 349 and 383
     * - Results in empty <span class="icon"></span> tags
     * - Counterexamples will show icons are missing from rendered output
     * 
     * This property uses a scoped PBT approach with concrete failing cases:
     * - Home icon: <i class="fa fa-home"></i>
     * - Dashboard icon: <i class="fa fa-dashboard"></i>
     * - Users icon: <i class="fa fa-users"></i>
     * 
     * @test
     */
    #[ErisRepeat(repeat: 50)]
    public function test_property_1_fault_condition_icon_html_preservation()
    {
        $this->forAll(
            // Generate Font Awesome icon HTML from a set of common icons
            Generators::elements([
                '<i class="fa fa-home"></i>',
                '<i class="fa fa-dashboard"></i>',
                '<i class="fa fa-users"></i>',
                '<i class="fa fa-cog"></i>',
                '<i class="fa fa-chart-bar"></i>',
                '<i class="fa fa-file"></i>',
                '<i class="fa fa-folder"></i>',
                '<i class="fa fa-envelope"></i>',
            ])
        )
        ->then(function ($iconHtml) {
            // Arrange: Create menu data with icon HTML
            $label = 'Test Menu';
            $url = '/test-url';
            $icon = ['icon' => $iconHtml];
            
            // Act: Render sidebar menu using the function under test
            $output = canvastack_sidebar_menu($label, $url, $icon);
            
            // Assert: Icon HTML should be preserved in the output
            // The output should contain the full icon markup inside the span
            $this->assertStringContainsString(
                '<span class="icon">' . $iconHtml . '</span>',
                $output,
                "Expected icon HTML to be preserved in output. " .
                "Bug: strip_tags() at lines 349/383 removes <i> tags, " .
                "resulting in empty <span class=\"icon\"></span> tags. " .
                "Icon HTML: {$iconHtml}"
            );
            
            // Assert: Output should NOT contain empty icon spans
            $this->assertStringNotContainsString(
                '<span class="icon"></span>',
                $output,
                "Found empty icon span - icon HTML was stripped. " .
                "This confirms the bug exists. Icon HTML: {$iconHtml}"
            );
            
            // Assert: Output should contain the Font Awesome class
            $this->assertStringContainsString(
                'class="fa fa-',
                $output,
                "Expected Font Awesome icon class to be present in output. " .
                "Bug: Icon HTML is being stripped. Icon HTML: {$iconHtml}"
            );
        });
    }
    
    /**
     * Property 1 - Additional Test Case: Menu with Submenu
     * 
     * **Validates: Requirements 2.1, 2.2, 2.3**
     * 
     * Tests icon preservation for menu items that have submenus (line 349 code path).
     * This tests the first occurrence of the bug where strip_tags() is applied
     * to icons for parent menu items with children.
     * 
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS
     * 
     * @test
     */
    #[ErisRepeat(repeat: 50)]
    public function test_property_1_icon_preservation_with_submenu()
    {
        $this->forAll(
            // Generate Font Awesome icon HTML
            Generators::elements([
                '<i class="fa fa-home"></i>',
                '<i class="fa fa-dashboard"></i>',
                '<i class="fa fa-users"></i>',
            ])
        )
        ->then(function ($iconHtml) {
            // Arrange: Create menu data with submenu and icon HTML
            $label = 'Parent Menu';
            $links = [
                'Child 1' => '/child-1',
                'Child 2' => '/child-2',
            ];
            $icon = ['icon' => $iconHtml];
            
            // Act: Render sidebar menu with submenu
            $output = canvastack_sidebar_menu($label, $links, $icon);
            
            // Assert: Icon HTML should be preserved for parent menu with submenu
            $this->assertStringContainsString(
                '<span class="icon">' . $iconHtml . '</span>',
                $output,
                "Expected icon HTML to be preserved for parent menu with submenu. " .
                "Bug at line 349: strip_tags() removes <i> tags. Icon HTML: {$iconHtml}"
            );
            
            // Assert: Output should NOT contain empty icon spans
            $this->assertStringNotContainsString(
                '<span class="icon"></span>',
                $output,
                "Found empty icon span in parent menu with submenu. " .
                "This confirms the bug at line 349. Icon HTML: {$iconHtml}"
            );
        });
    }
}
