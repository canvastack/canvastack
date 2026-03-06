<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\File;

/**
 * Dark Mode Compliance Test
 * 
 * Validates Requirements:
 * - 51.6: Dark mode support via Tailwind dark: prefix
 * - 51.14: Proper contrast in both light and dark modes
 * - 15.1: Dark mode styling support
 * - 15.2: Tailwind dark: prefix usage
 * - 15.3: Proper contrast in dark mode
 * - 15.5: Dark mode consistency across engines
 * - 15.6: Smooth transitions
 * - 15.7: Dark mode detection and sync
 * 
 * @package CanvaStack
 * @subpackage Tests\Unit\Components\Table
 */
class DarkModeComplianceTest extends TestCase
{
    /**
     * Get the CSS file path.
     * 
     * @return string
     */
    protected function getCssPath(): string
    {
        return __DIR__ . '/../../../../resources/css/components/tanstack-table.css';
    }
    
    /**
     * Get the CSS file content.
     * 
     * @return string
     */
    protected function getCssContent(): string
    {
        $cssPath = $this->getCssPath();
        
        if (!File::exists($cssPath)) {
            $this->markTestSkipped('CSS file not found: ' . $cssPath);
        }
        
        return File::get($cssPath);
    }
    
    /**
     * Test that all table Blade templates use Tailwind dark: prefix.
     * 
     * Validates: Requirements 51.6, 15.2
     * 
     * @return void
     */
    public function test_all_table_templates_use_dark_prefix(): void
    {
        $templatePaths = [
            __DIR__ . '/../../../../resources/views/canvastack/components/table/partials/filter-modal.blade.php',
        ];
        
        foreach ($templatePaths as $fullPath) {
            if (!File::exists($fullPath)) {
                $this->markTestSkipped("Template not found: {$fullPath}");
                continue;
            }
            
            $content = File::get($fullPath);
            
            // Check for dark: prefix usage
            $this->assertMatchesRegularExpression(
                '/dark:/',
                $content,
                "Template should use Tailwind dark: prefix for dark mode support"
            );
            
            // Count dark: occurrences
            $darkCount = substr_count($content, 'dark:');
            $this->assertGreaterThan(
                10,
                $darkCount,
                "Template should have multiple dark mode classes (found {$darkCount})"
            );
        }
    }
    
    /**
     * Test that CSS file uses proper dark mode classes.
     * 
     * Validates: Requirements 51.6, 15.2
     * 
     * @return void
     */
    public function test_css_file_uses_dark_mode_classes(): void
    {
        $content = $this->getCssContent();
        
        // Check for dark: prefix in CSS
        $this->assertStringContainsString(
            'dark:',
            $content,
            'CSS file should use Tailwind dark: prefix'
        );
        
        // Verify key dark mode classes exist
        $requiredDarkClasses = [
            'dark:bg-gray-900',
            'dark:bg-gray-800',
            'dark:text-gray-100',
            'dark:text-gray-300',
            'dark:border-gray-800',
            'dark:border-gray-700',
        ];
        
        foreach ($requiredDarkClasses as $class) {
            $this->assertStringContainsString(
                $class,
                $content,
                "CSS file should contain {$class} for dark mode support"
            );
        }
    }
    
    /**
     * Test proper contrast ratios in dark mode.
     * 
     * Validates: Requirements 51.14, 15.3
     * 
     * @return void
     */
    public function test_proper_contrast_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Verify high contrast text colors are used
        $highContrastClasses = [
            // Text on dark backgrounds should be light
            'dark:bg-gray-900',
            'dark:text-gray-100',
            'dark:bg-gray-800',
            'dark:text-gray-300',
            'dark:text-gray-50',
            'dark:text-gray-200',
        ];
        
        foreach ($highContrastClasses as $class) {
            $this->assertStringContainsString(
                $class,
                $content,
                "CSS should contain {$class} for proper contrast in dark mode"
            );
        }
        
        // Verify contrast enhancement classes exist
        $this->assertStringContainsString(
            'tanstack-table-high-contrast',
            $content,
            'CSS should have high contrast class for accessibility'
        );
        
        $this->assertStringContainsString(
            'tanstack-table-medium-contrast',
            $content,
            'CSS should have medium contrast class for accessibility'
        );
        
        $this->assertStringContainsString(
            'tanstack-table-low-contrast',
            $content,
            'CSS should have low contrast class for accessibility'
        );
    }
    
    /**
     * Test smooth transitions for dark mode switching.
     * 
     * Validates: Requirements 15.6, 51.13
     * 
     * @return void
     */
    public function test_smooth_transitions_for_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Verify transition classes are used
        $this->assertStringContainsString(
            'transition-colors',
            $content,
            'CSS should use transition-colors for smooth dark mode switching'
        );
        
        $this->assertStringContainsString(
            'duration-200',
            $content,
            'CSS should use appropriate transition duration (200ms)'
        );
        
        $this->assertStringContainsString(
            'ease-in-out',
            $content,
            'CSS should use ease-in-out timing function for smooth transitions'
        );
        
        // Verify transitions are applied to key elements
        $transitionElements = [
            'tanstack-table-container',
            'tanstack-table-header',
            'tanstack-table-body',
            'tanstack-table-row',
            'tanstack-table-cell',
        ];
        
        foreach ($transitionElements as $element) {
            $this->assertMatchesRegularExpression(
                "/\\.{$element}.*transition/s",
                $content,
                "Element .{$element} should have transition for smooth dark mode switching"
            );
        }
    }
    
    /**
     * Test that all interactive elements have dark mode variants.
     * 
     * Validates: Requirements 51.6, 15.2
     * 
     * @return void
     */
    public function test_interactive_elements_have_dark_mode_variants(): void
    {
        $content = $this->getCssContent();
        
        // Interactive elements that must have dark mode variants
        $interactiveElements = [
            'tanstack-table-search-input' => [
                'dark:bg-gray-800',
                'dark:border-gray-700',
                'dark:text-gray-100',
            ],
            'tanstack-table-filter-button' => [
                'dark:bg-gray-800',
                'dark:border-gray-700',
                'dark:text-gray-300',
            ],
            'tanstack-table-pagination-button' => [
                'dark:bg-gray-700',
                'dark:border-gray-600',
                'dark:text-gray-300',
            ],
            'tanstack-table-action-button' => [
                'dark:focus:ring-offset-gray-900',
            ],
        ];
        
        foreach ($interactiveElements as $element => $darkClasses) {
            foreach ($darkClasses as $darkClass) {
                $this->assertMatchesRegularExpression(
                    "/\\.{$element}.*{$darkClass}/s",
                    $content,
                    "Interactive element .{$element} should have {$darkClass} for dark mode"
                );
            }
        }
    }
    
    /**
     * Test that hover states work in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2, 24.1-24.7
     * 
     * @return void
     */
    public function test_hover_states_work_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Elements with hover states - check that dark mode hover classes exist
        $hoverClasses = [
            'dark:hover:bg-gray-700',
            'dark:hover:bg-gray-600',
            'dark:hover:bg-gray-500',
            'dark:hover:bg-indigo-600',
            'dark:hover:bg-red-600',
            'dark:hover:bg-green-600',
        ];
        
        foreach ($hoverClasses as $hoverClass) {
            $this->assertStringContainsString(
                $hoverClass,
                $content,
                "CSS should contain {$hoverClass} for dark mode hover state"
            );
        }
    }
    
    /**
     * Test that focus states are visible in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2, 26.6
     * 
     * @return void
     */
    public function test_focus_states_visible_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Verify focus ring colors for dark mode
        $this->assertStringContainsString(
            'dark:focus:ring-indigo-400',
            $content,
            'Focus rings should be visible in dark mode with appropriate color'
        );
        
        $this->assertStringContainsString(
            'dark:focus:ring-offset-gray-900',
            $content,
            'Focus ring offset should work with dark backgrounds'
        );
        
        $this->assertStringContainsString(
            'dark:ring-indigo-400',
            $content,
            'Ring colors should be adjusted for dark mode'
        );
    }
    
    /**
     * Test that loading states work in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2, 19.1-19.7
     * 
     * @return void
     */
    public function test_loading_states_work_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Loading overlay
        $this->assertMatchesRegularExpression(
            '/tanstack-table-loading-overlay.*dark:bg-gray-900/s',
            $content,
            'Loading overlay should have dark mode background'
        );
        
        // Loading spinner
        $this->assertMatchesRegularExpression(
            '/tanstack-table-loading-spinner.*dark:border-gray-700/s',
            $content,
            'Loading spinner should have dark mode border color'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-loading-spinner.*dark:border-t-indigo-400/s',
            $content,
            'Loading spinner accent should be visible in dark mode'
        );
        
        // Skeleton loading
        $this->assertMatchesRegularExpression(
            '/tanstack-table-skeleton.*dark:bg-gray-800/s',
            $content,
            'Skeleton loading should have dark mode background'
        );
    }
    
    /**
     * Test that empty and error states work in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2, 20.1-20.7
     * 
     * @return void
     */
    public function test_empty_and_error_states_work_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Empty state
        $this->assertMatchesRegularExpression(
            '/tanstack-table-empty-icon.*dark:text-gray-600/s',
            $content,
            'Empty state icon should have dark mode color'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-empty-title.*dark:text-gray-100/s',
            $content,
            'Empty state title should be readable in dark mode'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-empty-description.*dark:text-gray-400/s',
            $content,
            'Empty state description should have appropriate contrast in dark mode'
        );
        
        // Error state
        $this->assertMatchesRegularExpression(
            '/tanstack-table-error-icon.*dark:text-red-400/s',
            $content,
            'Error state icon should be visible in dark mode'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-error-button.*dark:bg-red-500/s',
            $content,
            'Error state button should have dark mode background'
        );
    }
    
    /**
     * Test that badges and tags work in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2
     * 
     * @return void
     */
    public function test_badges_and_tags_work_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Badge variants
        $badgeVariants = [
            'tanstack-table-badge-success' => ['dark:bg-green-900', 'dark:text-green-300'],
            'tanstack-table-badge-warning' => ['dark:bg-yellow-900', 'dark:text-yellow-300'],
            'tanstack-table-badge-error' => ['dark:bg-red-900', 'dark:text-red-300'],
            'tanstack-table-badge-info' => ['dark:bg-blue-900', 'dark:text-blue-300'],
        ];
        
        foreach ($badgeVariants as $badge => $darkClasses) {
            foreach ($darkClasses as $darkClass) {
                $this->assertStringContainsString(
                    $darkClass,
                    $content,
                    "Badge .{$badge} should have {$darkClass} for dark mode"
                );
            }
        }
    }
    
    /**
     * Test that column pinning works in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2, 12.1-12.7
     * 
     * @return void
     */
    public function test_column_pinning_works_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Pinned columns
        $this->assertMatchesRegularExpression(
            '/tanstack-table-cell-pinned-left.*dark:bg-gray-900/s',
            $content,
            'Left pinned columns should have dark mode background'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-cell-pinned-left.*dark:border-gray-800/s',
            $content,
            'Left pinned columns should have dark mode border'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-cell-pinned-right.*dark:bg-gray-900/s',
            $content,
            'Right pinned columns should have dark mode background'
        );
        
        $this->assertMatchesRegularExpression(
            '/tanstack-table-cell-pinned-right.*dark:border-gray-800/s',
            $content,
            'Right pinned columns should have dark mode border'
        );
    }
    
    /**
     * Test that scrollbar works in dark mode.
     * 
     * Validates: Requirements 51.6, 15.2
     * 
     * @return void
     */
    public function test_scrollbar_works_in_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Scrollbar track
        $this->assertMatchesRegularExpression(
            '/::-webkit-scrollbar-track.*dark:bg-gray-800/s',
            $content,
            'Scrollbar track should have dark mode background'
        );
        
        // Scrollbar thumb
        $this->assertMatchesRegularExpression(
            '/::-webkit-scrollbar-thumb.*dark:bg-gray-600/s',
            $content,
            'Scrollbar thumb should have dark mode background'
        );
        
        $this->assertMatchesRegularExpression(
            '/::-webkit-scrollbar-thumb.*dark:hover:bg-gray-500/s',
            $content,
            'Scrollbar thumb hover should work in dark mode'
        );
    }
    
    /**
     * Test that no hardcoded colors exist without dark mode variants.
     * 
     * Validates: Requirements 51.1, 51.3, 51.5
     * 
     * @return void
     */
    public function test_no_hardcoded_colors_without_dark_variants(): void
    {
        $content = $this->getCssContent();
        
        // Common light mode colors that should have dark variants
        $lightColors = [
            'bg-white',
            'bg-gray-50',
            'bg-gray-100',
            'text-gray-900',
            'text-gray-700',
            'border-gray-200',
            'border-gray-300',
        ];
        
        foreach ($lightColors as $lightColor) {
            // Find all occurrences of light color
            preg_match_all("/@apply[^;]*{$lightColor}[^;]*;/", $content, $matches);
            
            foreach ($matches[0] as $match) {
                // Verify it has a dark: variant
                $this->assertMatchesRegularExpression(
                    '/dark:/',
                    $match,
                    "Light color {$lightColor} should have a dark mode variant in: {$match}"
                );
            }
        }
    }
    
    /**
     * Test that theme integration works with dark mode.
     * 
     * Validates: Requirements 51.7, 51.8, 51.12
     * 
     * @return void
     */
    public function test_theme_integration_works_with_dark_mode(): void
    {
        $content = $this->getCssContent();
        
        // Verify CSS variables are used
        $this->assertStringContainsString(
            'var(--cs-color-primary)',
            $content,
            'CSS should use theme CSS variables'
        );
        
        $this->assertStringContainsString(
            'var(--cs-color-secondary)',
            $content,
            'CSS should use theme CSS variables'
        );
        
        $this->assertStringContainsString(
            'var(--cs-color-accent)',
            $content,
            'CSS should use theme CSS variables'
        );
        
        // Theme classes should exist
        $themeClasses = [
            'tanstack-table-theme-primary',
            'tanstack-table-theme-secondary',
            'tanstack-table-theme-accent',
        ];
        
        foreach ($themeClasses as $themeClass) {
            $this->assertStringContainsString(
                ".{$themeClass}",
                $content,
                "Theme class .{$themeClass} should exist"
            );
        }
    }
}

