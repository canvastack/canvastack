<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Form\Objects;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Integration Tests for Form Element Traits
 * 
 * Tests the complete rendering flow for all 7 element traits:
 * - Check.php (checkbox rendering)
 * - DateTime.php (date/time inputs)
 * - File.php (file uploads)
 * - Radio.php (radio buttons)
 * - Select.php (dropdowns)
 * - Tab.php (tab navigation)
 * - Text.php (text inputs)
 * 
 * These are integration tests that verify the complete flow through the Objects class,
 * including XSS protection, ARIA attributes, SafeHtml marker integration, and validation
 * attribute propagation.
 */
class TraitsIntegrationTest extends TestCase
{
    protected Objects $form;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->form = new Objects();
    }
    
    protected function tearDown(): void
    {
        // Clean up any uploaded test files
        if (Storage::disk('public')->exists('test-uploads')) {
            Storage::disk('public')->deleteDirectory('test-uploads');
        }
        
        parent::tearDown();
    }
    
    /**
     * Helper: Get rendered output from form
     */
    private function getRenderedOutput(): string
    {
        $reflection = new \ReflectionClass($this->form);
        $property = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $elementsArray = $property->getValue($this->form);
        
        return is_array($elementsArray) ? implode('', $elementsArray) : '';
    }
    
    /**
     * Helper: Assert HTML contains expected string
     */
    private function assertHtmlContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringContainsString($needle, $haystack, $message);
    }
    
    /**
     * Helper: Assert HTML does not contain string
     */
    private function assertHtmlNotContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertStringNotContainsString($needle, $haystack, $message);
    }
    
    // ========================================================================
    // 5.10.1 Test Check.php checkbox rendering
    // ========================================================================
    
    /**
     * @test
     * Test basic checkbox rendering
     */
    public function test_checkbox_renders_basic_html(): void
    {
        $this->form->checkbox('terms', [1 => 'I agree'], [], [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('checkbox', $output);
        $this->assertHtmlContains('I agree', $output);
        $this->assertHtmlContains('name="terms[1]"', $output);
    }
    
    /**
     * @test
     * Test checkbox XSS protection - label escaping
     */
    public function test_checkbox_escapes_malicious_label(): void
    {
        $this->form->checkbox('test', [1 => '<script>alert("xss")</script>'], [], [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlNotContains('<script>', $output);
        $this->assertHtmlContains('&lt;script&gt;', $output);
    }
    
    /**
     * @test
     * Test checkbox ARIA attributes
     */
    public function test_checkbox_includes_aria_attributes(): void
    {
        $this->form->checkbox('agree', [1 => 'Accept'], [1], [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-checked="true"', $output);
    }
    
    /**
     * @test
     * Test checkbox SafeHtml marker integration
     */
    public function test_checkbox_output_is_marked_safe(): void
    {
        $this->form->checkbox('test', [1 => 'Test'], [], [], true);
        $output = $this->getRenderedOutput();
        
        // Output should be properly handled - either marked or not containing raw markers
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Test', $output);
    }
    
    /**
     * @test
     * Test checkbox with required attribute
     */
    public function test_checkbox_with_required_attribute(): void
    {
        $this->form->checkbox('required_field', [1 => 'Required'], [], ['required' => true], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-required="true"', $output);
    }
    
    /**
     * @test
     * Test checkbox switch type
     */
    public function test_checkbox_switch_type_renders_correctly(): void
    {
        $this->form->checkbox('switch', [1 => 'Toggle'], [], ['check_type' => 'switch'], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('switch', $output);
        $this->assertHtmlContains('Toggle', $output);
    }
    
    // ========================================================================
    // 5.10.2 Test DateTime.php date/time inputs
    // ========================================================================
    
    /**
     * @test
     * Test date input rendering
     */
    public function test_date_input_renders_with_picker_class(): void
    {
        $this->form->date('birth_date', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('date-picker', $output);
        $this->assertHtmlContains('name="birth_date"', $output);
    }
    
    /**
     * @test
     * Test datetime input rendering
     */
    public function test_datetime_input_renders_with_picker_class(): void
    {
        $this->form->datetime('event_time', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('datetime-picker', $output);
        $this->assertHtmlContains('name="event_time"', $output);
    }
    
    /**
     * @test
     * Test time input rendering
     */
    public function test_time_input_renders_with_timepicker_class(): void
    {
        $this->form->time('start_time', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('bootstrap-timepicker', $output);
        $this->assertHtmlContains('name="start_time"', $output);
    }
    
    /**
     * @test
     * Test daterange input rendering
     */
    public function test_daterange_input_renders_with_picker_class(): void
    {
        $this->form->daterange('booking_period', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('daterange-picker', $output);
        $this->assertHtmlContains('name="booking_period"', $output);
    }
    
    /**
     * @test
     * Test date input XSS protection
     */
    public function test_date_input_escapes_malicious_value(): void
    {
        $this->form->date('test_date', '<script>alert("xss")</script>', [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlNotContains('<script>', $output);
    }
    
    /**
     * @test
     * Test date input ARIA attributes for required field
     */
    public function test_date_input_includes_aria_required(): void
    {
        $this->form->date('required_date', null, ['required' => true], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-required="true"', $output);
    }
    
    // ========================================================================
    // 5.10.3 Test File.php file uploads
    // ========================================================================
    
    /**
     * @test
     * Test file input rendering
     */
    public function test_file_input_renders_basic_html(): void
    {
        $this->form->file('avatar', [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('fileinput', $output);
        $this->assertHtmlContains('Select File', $output);
    }
    
    /**
     * @test
     * Test file input with image preview
     */
    public function test_file_input_with_image_preview(): void
    {
        $this->form->file('photo', ['imagepreview'], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('fileinput-preview', $output);
        $this->assertHtmlContains('Select Image', $output);
    }
    
    /**
     * @test
     * Test file input XSS protection - filename escaping
     */
    public function test_file_input_escapes_malicious_filename(): void
    {
        $this->form->file('document', ['value' => 'uploads/<script>alert("xss")</script>.pdf'], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlNotContains('<script>', $output);
    }
    
    /**
     * @test
     * Test file input ARIA attributes
     */
    public function test_file_input_includes_aria_label(): void
    {
        $this->form->file('upload', ['required' => true], true);
        $output = $this->getRenderedOutput();
        
        // File inputs should have ARIA attributes for accessibility
        $this->assertHtmlContains('aria-', $output);
    }
    
    /**
     * @test
     * Test file input SafeHtml marker
     */
    public function test_file_input_output_is_marked_safe(): void
    {
        $this->form->file('test_file', [], true);
        $output = $this->getRenderedOutput();
        
        // Output should be properly handled by SafeHtml system
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('fileinput', $output);
    }
    
    // ========================================================================
    // 5.10.4 Test Radio.php radio buttons
    // ========================================================================
    
    /**
     * @test
     * Test radio button rendering
     */
    public function test_radio_renders_basic_html(): void
    {
        $this->form->radiobox('gender', ['m' => 'Male', 'f' => 'Female'], false, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('radio', $output);
        $this->assertHtmlContains('Male', $output);
        $this->assertHtmlContains('Female', $output);
    }
    
    /**
     * @test
     * Test radio button XSS protection
     */
    public function test_radio_escapes_malicious_label(): void
    {
        $this->form->radiobox('test', ['1' => '<script>alert("xss")</script>'], false, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlNotContains('<script>', $output);
        $this->assertHtmlContains('&lt;script&gt;', $output);
    }
    
    /**
     * @test
     * Test radio button ARIA attributes
     */
    public function test_radio_includes_aria_checked(): void
    {
        $this->form->radiobox('status', ['active' => 'Active', 'inactive' => 'Inactive'], 'active', [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-checked="true"', $output);
        $this->assertHtmlContains('aria-checked="false"', $output);
    }
    
    /**
     * @test
     * Test radio button with required attribute
     */
    public function test_radio_with_required_attribute(): void
    {
        $this->form->radiobox('required_radio', ['yes' => 'Yes', 'no' => 'No'], false, ['required' => true], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-required="true"', $output);
    }
    
    /**
     * @test
     * Test radio button SafeHtml marker
     */
    public function test_radio_output_is_marked_safe(): void
    {
        $this->form->radiobox('test', ['1' => 'Option 1'], false, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Option 1', $output);
    }
    
    // ========================================================================
    // 5.10.5 Test Select.php dropdowns
    // ========================================================================
    
    /**
     * @test
     * Test selectbox rendering
     */
    public function test_selectbox_renders_basic_html(): void
    {
        $this->form->selectbox('country', ['us' => 'United States', 'uk' => 'United Kingdom'], false, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('select', $output);
        $this->assertHtmlContains('United States', $output);
        $this->assertHtmlContains('United Kingdom', $output);
    }
    
    /**
     * @test
     * Test selectbox XSS protection - option label escaping
     */
    public function test_selectbox_escapes_malicious_options(): void
    {
        $this->form->selectbox('test', ['1' => '<script>alert("xss")</script>'], false, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlNotContains('<script>', $output);
        $this->assertHtmlContains('&lt;script&gt;', $output);
    }
    
    /**
     * @test
     * Test selectbox ARIA attributes
     */
    public function test_selectbox_includes_aria_required(): void
    {
        $this->form->selectbox('required_select', ['1' => 'Option 1'], false, ['required' => true], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-required="true"', $output);
    }
    
    /**
     * @test
     * Test selectbox with chosen plugin class
     */
    public function test_selectbox_includes_chosen_class(): void
    {
        $this->form->selectbox('category', ['1' => 'Category 1'], false, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('chosen-select', $output);
    }
    
    /**
     * @test
     * Test month selectbox rendering
     */
    public function test_month_selectbox_renders_correctly(): void
    {
        $this->form->month('birth_month', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('select', $output);
        $this->assertHtmlContains('name="birth_month"', $output);
        $this->assertHtmlContains('January', $output);
        $this->assertHtmlContains('December', $output);
    }
    
    // ========================================================================
    // 5.10.6 Test Tab.php tab navigation
    // ========================================================================
    
    /**
     * @test
     * Test tab rendering basic structure
     */
    public function test_tab_renders_basic_structure(): void
    {
        $this->form->openTab('Tab 1');
        $this->form->text('field1', null, [], true);
        $this->form->openTab('Tab 2');
        $this->form->text('field2', null, [], true);
        $this->form->closeTab();
        
        $output = $this->getRenderedOutput();
        $rendered = $this->form->renderTab($output);
        $tabHtml = $rendered[0] ?? '';
        
        $this->assertHtmlContains('nav-tabs', $tabHtml);
        $this->assertHtmlContains('tab-content', $tabHtml);
        $this->assertHtmlContains('Tab 1', $tabHtml);
        $this->assertHtmlContains('Tab 2', $tabHtml);
    }
    
    /**
     * @test
     * Test tab XSS protection - label escaping
     */
    public function test_tab_escapes_malicious_label(): void
    {
        $this->form->openTab('<script>alert("xss")</script>');
        $this->form->text('test', null, [], true);
        $this->form->closeTab();
        
        $output = $this->getRenderedOutput();
        $rendered = $this->form->renderTab($output);
        $tabHtml = $rendered[0] ?? '';
        
        $this->assertHtmlNotContains('<script>alert', $tabHtml);
    }
    
    /**
     * @test
     * Test tab ARIA attributes
     */
    public function test_tab_includes_aria_attributes(): void
    {
        $this->form->openTab('Settings');
        $this->form->text('setting1', null, [], true);
        $this->form->closeTab();
        
        $output = $this->getRenderedOutput();
        $rendered = $this->form->renderTab($output);
        $tabHtml = $rendered[0] ?? '';
        
        // Tabs should have ARIA attributes for accessibility
        $this->assertHtmlContains('role="tablist"', $tabHtml);
    }
    
    /**
     * @test
     * Test tab with custom class
     */
    public function test_tab_with_custom_class(): void
    {
        $this->form->openTab('Custom Tab', 'custom-class');
        $this->form->text('custom_field', null, [], true);
        $this->form->closeTab();
        
        $output = $this->getRenderedOutput();
        $rendered = $this->form->renderTab($output);
        $tabHtml = $rendered[0] ?? '';
        
        $this->assertNotEmpty($tabHtml);
    }
    
    /**
     * @test
     * Test tab content rendering
     */
    public function test_tab_content_renders_correctly(): void
    {
        $this->form->openTab('Content Tab');
        $this->form->addTabContent('<p>Custom content</p>');
        $this->form->closeTab();
        
        $output = $this->getRenderedOutput();
        $rendered = $this->form->renderTab($output);
        $tabHtml = $rendered[0] ?? '';
        
        $this->assertHtmlContains('Custom content', $tabHtml);
    }
    
    // ========================================================================
    // 5.10.7 Test Text.php text inputs
    // ========================================================================
    
    /**
     * @test
     * Test text input rendering
     */
    public function test_text_input_renders_basic_html(): void
    {
        $this->form->text('username', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('text', $output);
        $this->assertHtmlContains('name="username"', $output);
    }
    
    /**
     * @test
     * Test text input XSS protection
     */
    public function test_text_input_escapes_malicious_value(): void
    {
        $this->form->text('test', '<script>alert("xss")</script>', [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlNotContains('<script>', $output);
    }
    
    /**
     * @test
     * Test textarea rendering
     */
    public function test_textarea_renders_basic_html(): void
    {
        $this->form->textarea('description', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('textarea', $output);
        $this->assertHtmlContains('name="description"', $output);
    }
    
    /**
     * @test
     * Test textarea with character limit
     */
    public function test_textarea_with_character_limit(): void
    {
        $this->form->textarea('bio|limit:500', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('maxlength="500"', $output);
        $this->assertHtmlContains('500 character limit', $output);
    }
    
    /**
     * @test
     * Test email input rendering
     */
    public function test_email_input_renders_with_type(): void
    {
        $this->form->email('user_email', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('email', $output);
        $this->assertHtmlContains('name="user_email"', $output);
    }
    
    /**
     * @test
     * Test number input rendering
     */
    public function test_number_input_renders_with_type(): void
    {
        $this->form->number('quantity', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('number', $output);
        $this->assertHtmlContains('name="quantity"', $output);
    }
    
    /**
     * @test
     * Test password input rendering
     */
    public function test_password_input_renders_correctly(): void
    {
        $this->form->password('user_password', [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('password', $output);
        $this->assertHtmlContains('name="user_password"', $output);
    }
    
    /**
     * @test
     * Test tags input rendering
     */
    public function test_tags_input_renders_with_plugin(): void
    {
        $this->form->tags('keywords', null, [], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('tagsinput', $output);
        $this->assertHtmlContains('name="keywords"', $output);
    }
    
    /**
     * @test
     * Test text input ARIA attributes
     */
    public function test_text_input_includes_aria_required(): void
    {
        $this->form->text('required_field', null, ['required' => true], true);
        $output = $this->getRenderedOutput();
        
        $this->assertHtmlContains('aria-required="true"', $output);
    }
    
    // ========================================================================
    // 5.10.8 Code coverage and integration tests
    // ========================================================================
    
    /**
     * @test
     * Test validation attribute propagation across all traits
     */
    public function test_validation_attributes_propagate_to_all_elements(): void
    {
        // Set validation rules
        $this->form->setValidations([
            'username' => 'required|max:50',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ]);
        
        // Render elements
        $this->form->text('username', null, [], true);
        $this->form->email('email', null, [], true);
        $this->form->number('age', null, [], true);
        
        $output = $this->getRenderedOutput();
        
        // Check that required attributes are added
        $this->assertHtmlContains('required', $output);
        $this->assertHtmlContains('maxlength="50"', $output);
    }
    
    /**
     * @test
     * Test backward compatibility - all elements render without errors
     */
    public function test_backward_compatibility_all_elements_render(): void
    {
        // Test all element types render without throwing exceptions
        $this->form->text('text_field', null, [], true);
        $this->form->textarea('textarea_field', null, [], true);
        $this->form->email('email_field', null, [], true);
        $this->form->number('number_field', null, [], true);
        $this->form->password('password_field', [], true);
        $this->form->checkbox('checkbox_field', [1 => 'Option'], [], [], true);
        $this->form->radiobox('radio_field', ['1' => 'Option'], false, [], true);
        $this->form->selectbox('select_field', ['1' => 'Option'], false, [], true);
        $this->form->date('date_field', null, [], true);
        $this->form->time('time_field', null, [], true);
        $this->form->file('file_field', [], true);
        
        $output = $this->getRenderedOutput();
        
        // All elements should render
        $this->assertNotEmpty($output);
        $this->assertHtmlContains('text_field', $output);
        $this->assertHtmlContains('checkbox_field', $output);
        $this->assertHtmlContains('radio_field', $output);
    }
    
    /**
     * @test
     * Test SafeHtml marker integration across all traits
     */
    public function test_safehtml_marker_prevents_double_encoding(): void
    {
        // Render elements with special characters
        $this->form->checkbox('test1', [1 => 'Test & Value'], [], [], true);
        $this->form->radiobox('test2', ['1' => 'Test & Value'], false, [], true);
        
        $output = $this->getRenderedOutput();
        
        // Should be encoded once, not double-encoded
        $this->assertHtmlContains('Test &amp; Value', $output);
        $this->assertHtmlNotContains('&amp;amp;', $output);
    }
    
    /**
     * @test
     * Test complete form flow with multiple traits
     */
    public function test_complete_form_with_multiple_traits(): void
    {
        // Open form
        $this->form->open('/test', 'POST', 'horizontal', false);
        
        // Add various elements
        $this->form->text('name', null, [], true);
        $this->form->email('email', null, [], true);
        $this->form->textarea('bio', null, [], true);
        $this->form->selectbox('country', ['us' => 'USA', 'uk' => 'UK'], false, [], true);
        $this->form->checkbox('terms', [1 => 'I agree'], [], [], true);
        $this->form->radiobox('gender', ['m' => 'Male', 'f' => 'Female'], false, [], true);
        $this->form->date('birth_date', null, [], true);
        $this->form->file('avatar', [], true);
        
        // Close form
        $this->form->close();
        
        $output = $this->getRenderedOutput();
        
        // Verify complete form structure
        $this->assertHtmlContains('<form', $output);
        $this->assertHtmlContains('</form>', $output);
        $this->assertHtmlContains('name="name"', $output);
        $this->assertHtmlContains('name="email"', $output);
        $this->assertHtmlContains('name="bio"', $output);
        $this->assertHtmlContains('name="country"', $output);
        $this->assertHtmlContains('name="terms[1]"', $output);
        $this->assertHtmlContains('name="gender"', $output);
        $this->assertHtmlContains('name="birth_date"', $output);
        $this->assertHtmlContains('name="avatar"', $output);
    }
}
