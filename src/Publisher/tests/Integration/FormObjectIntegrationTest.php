<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Integration tests for FormObject helper delegation to ThemeAdapterResolver.
 *
 * Verifies that each FormObject helper function:
 *   1. Delegates to the correct adapter method.
 *   2. With template `default` produces output identical to DefaultAdapter directly.
 *   3. With template `canvasign` produces Bootstrap 5 output.
 *   4. With template `canvas` produces Tailwind output (no Bootstrap-specific classes).
 *
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7
 *
 * @group integration
 * @group theme-adapter
 */
class FormObjectIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::resetAll();
    }

    protected function tearDown(): void
    {
        ThemeAdapterResolver::resetAll();
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Set the active template via Laravel config so canvastack_current_template()
     * returns the desired value, then reset the resolver cache.
     */
    private function setTemplate(string $template): void
    {
        config(['canvastack.settings.template' => $template]);
        ThemeAdapterResolver::reset();
    }

    // ── 1. Delegation: each helper calls the correct adapter method ───────

    /**
     * @test
     * Requirement 7.1: canvastack_form_create_header_tab() delegates to
     *                  ThemeAdapterResolver::resolve()->renderTabHeader().
     *
     * We verify delegation by comparing the helper output to the adapter output
     * directly — they must be identical for the same inputs.
     */
    public function test_form_create_header_tab_delegates_to_adapter_render_tab_header(): void
    {
        $this->setTemplate('default');

        $data    = 'profile';
        $pointer = 'tab-profile';
        $active  = 'active';
        $class   = 'fa fa-user';

        $adapterOutput = ThemeAdapterResolver::resolve()->renderTabHeader($data, $pointer, $active, $class);
        $helperOutput  = canvastack_form_create_header_tab($data, $pointer, $active, $class);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_form_create_header_tab() must delegate to renderTabHeader()'
        );
    }

    /**
     * @test
     * Requirement 7.2: canvastack_form_create_content_tab() delegates to
     *                  ThemeAdapterResolver::resolve()->renderTabContent().
     */
    public function test_form_create_content_tab_delegates_to_adapter_render_tab_content(): void
    {
        $this->setTemplate('default');

        $data    = '<p>Tab content here</p>';
        $pointer = 'tab-profile';
        $active  = true;

        $adapterOutput = ThemeAdapterResolver::resolve()->renderTabContent($data, $pointer, $active);
        $helperOutput  = canvastack_form_create_content_tab($data, $pointer, $active);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_form_create_content_tab() must delegate to renderTabContent()'
        );
    }

    /**
     * @test
     * Requirement 7.3: canvastack_form_alert_message() delegates to
     *                  ThemeAdapterResolver::resolve()->renderAlertMessage().
     */
    public function test_form_alert_message_delegates_to_adapter_render_alert_message(): void
    {
        $this->setTemplate('default');

        $message = 'Data saved successfully.';
        $type    = 'success';
        $title   = 'Success';
        $prefix  = 'fa-check';
        $extra   = false;

        $adapterOutput = ThemeAdapterResolver::resolve()->renderAlertMessage($message, $type, $title, $prefix, $extra);
        $helperOutput  = canvastack_form_alert_message($message, $type, $title, $prefix, $extra);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_form_alert_message() must delegate to renderAlertMessage()'
        );
    }

    /**
     * @test
     * Requirement 7.4: canvastack_form_checkList() delegates to
     *                  ThemeAdapterResolver::resolve()->renderCheckList().
     */
    public function test_form_check_list_delegates_to_adapter_render_check_list(): void
    {
        $this->setTemplate('default');

        $name      = 'agree';
        $value     = '1';
        $label     = 'I agree to the terms';
        $checked   = true;
        $class     = 'success';
        $id        = 'agree_checkbox';
        $inputNode = null;

        $adapterOutput = ThemeAdapterResolver::resolve()->renderCheckList($name, $value, $label, $checked, $class, $id, $inputNode);
        $helperOutput  = canvastack_form_checkList($name, $value, $label, $checked, $class, $id, $inputNode);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_form_checkList() must delegate to renderCheckList()'
        );
    }

    /**
     * @test
     * Requirement 7.5: canvastack_form_selectbox() delegates to
     *                  ThemeAdapterResolver::resolve()->renderSelectBox().
     */
    public function test_form_selectbox_delegates_to_adapter_render_select_box(): void
    {
        $this->setTemplate('default');

        $name           = 'country';
        $values         = ['us' => 'United States', 'uk' => 'United Kingdom', 'id' => 'Indonesia'];
        $selected       = 'id';
        $attributes     = [];
        $label          = true;
        $setFirstValue  = [null => 'Select Country'];

        $adapterOutput = ThemeAdapterResolver::resolve()->renderSelectBox($name, $values, $selected, $attributes, $label, $setFirstValue);
        $helperOutput  = canvastack_form_selectbox($name, $values, $selected, $attributes, $label, $setFirstValue);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_form_selectbox() must delegate to renderSelectBox()'
        );
    }

    // ── 2. Template `default` — output identical to DefaultAdapter directly ──

    /**
     * @test
     * Requirement 7.7: With template `default`, canvastack_form_create_header_tab()
     *                  produces output identical to DefaultAdapter::renderTabHeader() directly.
     */
    public function test_default_template_tab_header_matches_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $data    = 'settings';
        $pointer = 'tab-settings';
        $active  = false;
        $class   = false;

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderTabHeader($data, $pointer, $active, $class);
        $helperOutput  = canvastack_form_create_header_tab($data, $pointer, $active, $class);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, helper output must be identical to DefaultAdapter output'
        );
    }

    /**
     * @test
     * Requirement 7.7: With template `default`, canvastack_form_create_content_tab()
     *                  produces output identical to DefaultAdapter::renderTabContent() directly.
     */
    public function test_default_template_tab_content_matches_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $data    = '<p>Content here</p>';
        $pointer = 'tab-settings';
        $active  = false;

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderTabContent($data, $pointer, $active);
        $helperOutput  = canvastack_form_create_content_tab($data, $pointer, $active);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, helper output must be identical to DefaultAdapter output'
        );
    }

    /**
     * @test
     * Requirement 7.7: With template `default`, canvastack_form_alert_message()
     *                  produces output identical to DefaultAdapter::renderAlertMessage() directly.
     */
    public function test_default_template_alert_message_matches_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $message = 'An error occurred.';
        $type    = 'danger';
        $title   = 'Error';
        $prefix  = 'fa-warning';
        $extra   = false;

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderAlertMessage($message, $type, $title, $prefix, $extra);
        $helperOutput  = canvastack_form_alert_message($message, $type, $title, $prefix, $extra);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, helper output must be identical to DefaultAdapter output'
        );
    }

    /**
     * @test
     * Requirement 7.7: With template `default`, canvastack_form_checkList()
     *                  produces output identical to DefaultAdapter::renderCheckList() directly.
     */
    public function test_default_template_check_list_matches_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $name    = 'newsletter';
        $value   = '1';
        $label   = 'Subscribe to newsletter';
        $checked = false;
        $class   = 'info';
        $id      = false;

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderCheckList($name, $value, $label, $checked, $class, $id, null);
        $helperOutput  = canvastack_form_checkList($name, $value, $label, $checked, $class, $id, null);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, helper output must be identical to DefaultAdapter output'
        );
    }

    /**
     * @test
     * Requirement 7.7: With template `default`, canvastack_form_selectbox()
     *                  produces output identical to DefaultAdapter::renderSelectBox() directly.
     */
    public function test_default_template_selectbox_matches_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $name          = 'status';
        $values        = ['active' => 'Active', 'inactive' => 'Inactive'];
        $selected      = 'active';
        $attributes    = [];
        $label         = true;
        $setFirstValue = [null => 'Select'];

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderSelectBox($name, $values, $selected, $attributes, $label, $setFirstValue);
        $helperOutput  = canvastack_form_selectbox($name, $values, $selected, $attributes, $label, $setFirstValue);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, helper output must be identical to DefaultAdapter output'
        );
    }

    // ── 3. Template `canvasign` — Bootstrap 5 output ─────────────────────

    /**
     * @test
     * Requirement 7.3 (canvasign): canvastack_form_create_header_tab() with template
     *                              `canvasign` produces Bootstrap 5 output (data-bs-toggle).
     */
    public function test_canvasign_template_tab_header_contains_bootstrap5_markers(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', false);

        $this->assertStringContainsString(
            'data-bs-toggle',
            $output,
            'canvasign template tab header must contain Bootstrap 5 data-bs-toggle'
        );

        $this->assertStringNotContainsString(
            'data-toggle="tab"',
            $output,
            'canvasign template tab header must NOT contain Bootstrap 4 data-toggle="tab"'
        );
    }

    /**
     * @test
     * Requirement 7.3 (canvasign): canvastack_form_alert_message() with template
     *                              `canvasign` produces Bootstrap 5 output (data-bs-dismiss).
     */
    public function test_canvasign_template_alert_message_contains_bootstrap5_markers(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_form_alert_message('Saved!', 'success', 'Success', 'fa-check', false);

        $this->assertStringContainsString(
            'data-bs-dismiss',
            $output,
            'canvasign template alert must contain Bootstrap 5 data-bs-dismiss'
        );

        $this->assertStringNotContainsString(
            'data-dismiss="alert"',
            $output,
            'canvasign template alert must NOT contain Bootstrap 4 data-dismiss="alert"'
        );

        $this->assertStringNotContainsString(
            'alert-block',
            $output,
            'canvasign template alert must NOT contain Bootstrap 4 alert-block class'
        );
    }

    /**
     * @test
     * Requirement 7.4 (canvasign): canvastack_form_checkList() with template
     *                              `canvasign` produces Bootstrap 5 form-check output.
     */
    public function test_canvasign_template_check_list_contains_bootstrap5_markers(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_form_checkList('agree', '1', 'I agree', false, 'success', false, null);

        $this->assertStringContainsString(
            'form-check',
            $output,
            'canvasign template checkbox must contain Bootstrap 5 form-check class'
        );

        $this->assertStringNotContainsString(
            'class="ckbox',
            $output,
            'canvasign template checkbox must NOT contain Bootstrap 4 ckbox class'
        );
    }

    /**
     * @test
     * Requirement 7.5 (canvasign): canvastack_form_selectbox() with template
     *                              `canvasign` produces Bootstrap 5 form-select output.
     */
    public function test_canvasign_template_selectbox_contains_bootstrap5_markers(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_form_selectbox('status', ['active' => 'Active', 'inactive' => 'Inactive'], false, [], true, [null => 'Select']);

        $this->assertStringContainsString(
            'form-select',
            $output,
            'canvasign template selectbox must contain Bootstrap 5 form-select class'
        );

        $this->assertStringNotContainsString(
            'chosen-select',
            $output,
            'canvasign template selectbox must NOT contain Bootstrap 4 chosen-select class'
        );
    }

    /**
     * @test
     * Requirement 7.3 (canvasign): canvastack_form_create_content_tab() with template
     *                              `canvasign` produces valid tab-pane output.
     */
    public function test_canvasign_template_tab_content_produces_valid_output(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_form_create_content_tab('<p>Content</p>', 'tab-profile', true);

        $this->assertStringContainsString(
            'tab-pane',
            $output,
            'canvasign template tab content must contain tab-pane class'
        );

        $this->assertStringContainsString(
            'tab-profile',
            $output,
            'canvasign template tab content must contain the pointer as id'
        );
    }

    // ── 4. Template `canvas` — Tailwind output ────────────────────────────

    /**
     * @test
     * Requirement 7.3 (canvas): canvastack_form_create_header_tab() with template
     *                           `canvas` produces Tailwind output (no Bootstrap nav-item class).
     *
     * Note: TailwindAdapter uses `data-toggle` (custom JS) per Requirement 6.5 —
     * Tailwind has no framework-specific toggle attribute, so it reuses the same
     * attribute name with custom JavaScript. The key difference is the absence of
     * Bootstrap structural classes like `nav-item`.
     */
    public function test_canvas_template_tab_header_contains_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_form_create_header_tab('profile', 'tab-profile', 'active', false);

        // Must NOT contain Bootstrap-specific structural classes
        $this->assertStringNotContainsString(
            'nav-item',
            $output,
            'canvas template tab header must NOT contain Bootstrap nav-item class'
        );

        // Must contain Tailwind utility classes
        $this->assertStringContainsString(
            'cursor-pointer',
            $output,
            'canvas template tab header must contain Tailwind cursor-pointer class'
        );
    }

    /**
     * @test
     * Requirement 7.3 (canvas): canvastack_form_alert_message() with template
     *                           `canvas` produces Tailwind output (no Bootstrap-specific classes).
     *
     * Note: TailwindAdapter uses `data-dismiss` (custom JS) per Requirement 6.5 —
     * Tailwind has no framework-specific dismiss attribute, so it reuses the same
     * attribute name with custom JavaScript. The key difference is the absence of
     * Bootstrap structural classes like `alert-block` and Bootstrap 5 `data-bs-dismiss`.
     */
    public function test_canvas_template_alert_message_contains_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_form_alert_message('Saved!', 'success', 'Success', 'fa-check', false);

        // Must NOT contain Bootstrap-specific structural classes
        $this->assertStringNotContainsString(
            'alert-block',
            $output,
            'canvas template alert must NOT contain Bootstrap 4 alert-block class'
        );

        $this->assertStringNotContainsString(
            'data-bs-dismiss',
            $output,
            'canvas template alert must NOT contain Bootstrap 5 data-bs-dismiss'
        );

        // Must contain Tailwind utility classes
        $this->assertStringContainsString(
            'flex',
            $output,
            'canvas template alert must contain Tailwind flex class'
        );
    }

    /**
     * @test
     * Requirement 7.4 (canvas): canvastack_form_checkList() with template
     *                           `canvas` produces Tailwind output (no Bootstrap classes).
     */
    public function test_canvas_template_check_list_contains_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_form_checkList('agree', '1', 'I agree', false, 'success', false, null);

        // Must NOT contain Bootstrap-specific classes
        $this->assertStringNotContainsString(
            'class="ckbox',
            $output,
            'canvas template checkbox must NOT contain Bootstrap 4 ckbox class'
        );

        $this->assertStringNotContainsString(
            'form-check-input',
            $output,
            'canvas template checkbox must NOT contain Bootstrap 5 form-check-input class'
        );

        // Must contain Tailwind utility classes
        $this->assertStringContainsString(
            'flex',
            $output,
            'canvas template checkbox must contain Tailwind flex class'
        );
    }

    /**
     * @test
     * Requirement 7.5 (canvas): canvastack_form_selectbox() with template
     *                           `canvas` produces Tailwind output (no Bootstrap classes).
     */
    public function test_canvas_template_selectbox_contains_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_form_selectbox('status', ['active' => 'Active', 'inactive' => 'Inactive'], false, [], true, [null => 'Select']);

        // Must NOT contain Bootstrap-specific classes
        $this->assertStringNotContainsString(
            'chosen-select',
            $output,
            'canvas template selectbox must NOT contain Bootstrap 4 chosen-select class'
        );

        $this->assertStringNotContainsString(
            'form-select',
            $output,
            'canvas template selectbox must NOT contain Bootstrap 5 form-select class'
        );

        // Must contain Tailwind class (form-input)
        $this->assertStringContainsString(
            'form-input',
            $output,
            'canvas template selectbox must contain Tailwind form-input class'
        );
    }

    /**
     * @test
     * Requirement 7.3 (canvas): canvastack_form_create_content_tab() with template
     *                           `canvas` produces valid tab-pane output without Bootstrap classes.
     */
    public function test_canvas_template_tab_content_produces_valid_output(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_form_create_content_tab('<p>Content</p>', 'tab-profile', true);

        $this->assertStringContainsString(
            'tab-pane',
            $output,
            'canvas template tab content must contain tab-pane class'
        );

        $this->assertStringContainsString(
            'tab-profile',
            $output,
            'canvas template tab content must contain the pointer as id'
        );

        $this->assertStringNotContainsString(
            'd-none',
            $output,
            'canvas template tab content must NOT contain Bootstrap 5 d-none class'
        );
    }

    // ── 5. Resolver returns correct adapter type per template ─────────────

    /**
     * @test
     * Requirement 7.1–7.5: With template `default`, resolver returns DefaultAdapter.
     */
    public function test_default_template_resolver_returns_default_adapter(): void
    {
        $this->setTemplate('default');

        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            DefaultAdapter::class,
            $adapter,
            'Template default must resolve to DefaultAdapter'
        );
    }

    /**
     * @test
     * Requirement 7.1–7.5: With template `canvasign`, resolver returns Bootstrap5Adapter.
     */
    public function test_canvasign_template_resolver_returns_bootstrap5_adapter(): void
    {
        $this->setTemplate('canvasign');

        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            Bootstrap5Adapter::class,
            $adapter,
            'Template canvasign must resolve to Bootstrap5Adapter'
        );
    }

    /**
     * @test
     * Requirement 7.1–7.5: With template `canvas`, resolver returns TailwindAdapter.
     */
    public function test_canvas_template_resolver_returns_tailwind_adapter(): void
    {
        $this->setTemplate('canvas');

        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            TailwindAdapter::class,
            $adapter,
            'Template canvas must resolve to TailwindAdapter'
        );
    }

    // ── 6. Public API signatures unchanged (Requirement 7.6) ─────────────

    /**
     * @test
     * Requirement 7.6: canvastack_form_create_header_tab() signature is unchanged.
     */
    public function test_form_create_header_tab_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_form_create_header_tab');
        $params     = $reflection->getParameters();

        $this->assertCount(4, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('pointer', $params[1]->getName());
        $this->assertSame('active', $params[2]->getName());
        $this->assertSame('class', $params[3]->getName());

        // Check defaults
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
        $this->assertTrue($params[3]->isDefaultValueAvailable());
        $this->assertFalse($params[3]->getDefaultValue());
    }

    /**
     * @test
     * Requirement 7.6: canvastack_form_create_content_tab() signature is unchanged.
     */
    public function test_form_create_content_tab_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_form_create_content_tab');
        $params     = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('pointer', $params[1]->getName());
        $this->assertSame('active', $params[2]->getName());

        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * @test
     * Requirement 7.6: canvastack_form_alert_message() signature is unchanged.
     */
    public function test_form_alert_message_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_form_alert_message');
        $params     = $reflection->getParameters();

        $this->assertCount(5, $params);
        $this->assertSame('message', $params[0]->getName());
        $this->assertSame('type', $params[1]->getName());
        $this->assertSame('title', $params[2]->getName());
        $this->assertSame('prefix', $params[3]->getName());
        $this->assertSame('extra', $params[4]->getName());

        // Check defaults
        $this->assertSame('Success', $params[0]->getDefaultValue());
        $this->assertSame('success', $params[1]->getDefaultValue());
        $this->assertSame('Success', $params[2]->getDefaultValue());
        $this->assertSame('fa-check', $params[3]->getDefaultValue());
        $this->assertFalse($params[4]->getDefaultValue());
    }

    /**
     * @test
     * Requirement 7.6: canvastack_form_checkList() signature is unchanged.
     */
    public function test_form_check_list_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_form_checkList');
        $params     = $reflection->getParameters();

        $this->assertCount(7, $params);
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('value', $params[1]->getName());
        $this->assertSame('label', $params[2]->getName());
        $this->assertSame('checked', $params[3]->getName());
        $this->assertSame('class', $params[4]->getName());
        $this->assertSame('id', $params[5]->getName());
        $this->assertSame('inputNode', $params[6]->getName());

        // Check defaults
        $this->assertFalse($params[1]->getDefaultValue());
        $this->assertFalse($params[2]->getDefaultValue());
        $this->assertFalse($params[3]->getDefaultValue());
        $this->assertSame('success', $params[4]->getDefaultValue());
        $this->assertFalse($params[5]->getDefaultValue());
        $this->assertNull($params[6]->getDefaultValue());
    }

    /**
     * @test
     * Requirement 7.6: canvastack_form_selectbox() signature is unchanged.
     */
    public function test_form_selectbox_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_form_selectbox');
        $params     = $reflection->getParameters();

        $this->assertCount(6, $params);
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('values', $params[1]->getName());
        $this->assertSame('selected', $params[2]->getName());
        $this->assertSame('attributes', $params[3]->getName());
        $this->assertSame('label', $params[4]->getName());
        $this->assertSame('set_first_value', $params[5]->getName());

        // Check defaults
        $this->assertSame([], $params[1]->getDefaultValue());
        $this->assertFalse($params[2]->getDefaultValue());
        $this->assertSame([], $params[3]->getDefaultValue());
        $this->assertTrue($params[4]->getDefaultValue());
        $this->assertSame([null => 'Select'], $params[5]->getDefaultValue());
    }
}
