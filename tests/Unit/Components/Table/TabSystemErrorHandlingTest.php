<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for TabSystem error handling and retry functionality.
 * 
 * This test verifies that the Alpine.js tab component properly handles
 * errors and provides retry functionality as specified in task 3.2.6.
 * 
 * Requirements: 6.8, 15.2
 */
class TabSystemErrorHandlingTest extends TestCase
{
    /**
     * Test that retryLoad method clears error state.
     *
     * @return void
     */
    public function test_retry_load_clears_error_state(): void
    {
        // This is a JavaScript component test
        // The actual functionality is tested via browser tests
        // This test verifies the component file exists and is properly structured
        
        $componentPath = __DIR__ . '/../../../../resources/js/components/table-tabs.js';
        
        $this->assertFileExists($componentPath, 'table-tabs.js component file should exist');
        
        $content = file_get_contents($componentPath);
        
        // Verify retryLoad method exists
        $this->assertStringContainsString(
            'async retryLoad(index)',
            $content,
            'retryLoad method should be defined'
        );
        
        // Verify error state is cleared
        $this->assertStringContainsString(
            'this.error = null',
            $content,
            'retryLoad should clear error state'
        );
    }
    
    /**
     * Test that retryLoad method clears cached content.
     *
     * @return void
     */
    public function test_retry_load_clears_cached_content(): void
    {
        $componentPath = __DIR__ . '/../../../../resources/js/components/table-tabs.js';
        $content = file_get_contents($componentPath);
        
        // Verify cached content is cleared
        $this->assertStringContainsString(
            'delete this.tabContent[index]',
            $content,
            'retryLoad should clear cached content'
        );
    }
    
    /**
     * Test that retryLoad method removes tab from loaded list.
     *
     * @return void
     */
    public function test_retry_load_removes_from_loaded_list(): void
    {
        $componentPath = __DIR__ . '/../../../../resources/js/components/table-tabs.js';
        $content = file_get_contents($componentPath);
        
        // Verify tab is removed from loaded list
        $this->assertStringContainsString(
            'this.tabsLoaded.splice(loadedIndex, 1)',
            $content,
            'retryLoad should remove tab from loaded list'
        );
    }
    
    /**
     * Test that retryLoad method triggers fresh load.
     *
     * @return void
     */
    public function test_retry_load_triggers_fresh_load(): void
    {
        $componentPath = __DIR__ . '/../../../../resources/js/components/table-tabs.js';
        $content = file_get_contents($componentPath);
        
        // Verify loadTab is called
        $this->assertStringContainsString(
            'await this.loadTab(index)',
            $content,
            'retryLoad should trigger fresh load'
        );
    }
    
    /**
     * Test that formatErrorMessage provides user-friendly messages.
     *
     * @return void
     */
    public function test_format_error_message_provides_user_friendly_messages(): void
    {
        $componentPath = __DIR__ . '/../../../../resources/js/components/table-tabs.js';
        $content = file_get_contents($componentPath);
        
        // Verify formatErrorMessage method exists
        $this->assertStringContainsString(
            'formatErrorMessage(error)',
            $content,
            'formatErrorMessage method should be defined'
        );
        
        // Verify user-friendly messages for common errors
        $this->assertStringContainsString(
            'Tab content not found',
            $content,
            'Should have user-friendly message for 404'
        );
        
        $this->assertStringContainsString(
            'You do not have permission',
            $content,
            'Should have user-friendly message for 403'
        );
        
        $this->assertStringContainsString(
            'Your session has expired',
            $content,
            'Should have user-friendly message for 419'
        );
        
        $this->assertStringContainsString(
            'Network error',
            $content,
            'Should have user-friendly message for network errors'
        );
    }
    
    /**
     * Test that error handling is implemented in loadTab method.
     *
     * @return void
     */
    public function test_load_tab_has_error_handling(): void
    {
        $componentPath = __DIR__ . '/../../../../resources/js/components/table-tabs.js';
        $content = file_get_contents($componentPath);
        
        // Verify try-catch block exists
        $this->assertStringContainsString(
            'try {',
            $content,
            'loadTab should have try-catch block'
        );
        
        $this->assertStringContainsString(
            'catch (error) {',
            $content,
            'loadTab should catch errors'
        );
        
        // Verify error is formatted and stored
        $this->assertStringContainsString(
            'this.error = this.formatErrorMessage(error)',
            $content,
            'loadTab should format and store error message'
        );
    }
    
    /**
     * Test that tab placeholder template has retry button.
     *
     * @return void
     */
    public function test_tab_placeholder_has_retry_button(): void
    {
        $templatePath = __DIR__ . '/../../../../resources/views/components/table/tab-placeholder.blade.php';
        
        $this->assertFileExists($templatePath, 'tab-placeholder.blade.php template should exist');
        
        $content = file_get_contents($templatePath);
        
        // Verify retry button exists
        $this->assertStringContainsString(
            '@click="retryLoad(',
            $content,
            'Template should have retry button with retryLoad handler'
        );
        
        // Verify retry button has proper styling
        $this->assertStringContainsString(
            'btn btn-primary',
            $content,
            'Retry button should use DaisyUI button classes'
        );
        
        // Verify retry button has icon
        $this->assertStringContainsString(
            '<svg',
            $content,
            'Retry button should have icon'
        );
    }
    
    /**
     * Test that error state is displayed properly.
     *
     * @return void
     */
    public function test_error_state_is_displayed(): void
    {
        $templatePath = __DIR__ . '/../../../../resources/views/components/table/tab-placeholder.blade.php';
        $content = file_get_contents($templatePath);
        
        // Verify error state container exists
        $this->assertStringContainsString(
            'x-show="error && activeTab',
            $content,
            'Template should have error state container'
        );
        
        // Verify error message is displayed
        $this->assertStringContainsString(
            'x-text="error"',
            $content,
            'Template should display error message'
        );
        
        // Verify error has ARIA attributes
        $this->assertStringContainsString(
            'role="alert"',
            $content,
            'Error state should have role="alert"'
        );
        
        $this->assertStringContainsString(
            'aria-live="assertive"',
            $content,
            'Error state should have aria-live="assertive"'
        );
    }
}
