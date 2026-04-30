<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Form\Objects;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Integration tests for Objects class delegation to ThemeAdapterResolver.
 *
 * Verifies that the Objects class (Select, Check, Tab elements) correctly
 * delegates CSS class and wrapper rendering to the active theme adapter.
 *
 * Requirements: 4.4, 5.4, 5.7, 6.3, 6.4, 7.4, 7.5, 7.7
 *
 * @group integration
 * @group theme-adapter
 */
class ObjectsDelegationIntegrationTest extends TestCase
{
    protected Objects $form;

    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::resetAll();
        $this->form = new Objects();
    }

    protected function tearDown(): void
    {
        ThemeAdapterResolver::resetAll();
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Set the active template and reset the resolver cache.
     */
    private function setTemplate(string $template): void
    {
        config(['canvastack.settings.template' => $template]);
        ThemeAdapterResolver::reset();
    }

    /**
     * Get the rendered HTML from the Objects form elements array.
     * For tab-containing forms, calls render() to process tab markers.
     */
    private function getRenderedHtml(): string
    {
        $reflection = new \ReflectionClass($this->form);
        $property   = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $elements = $property->getValue($this->form);

        return implode('', $elements);
    }

    /**
     * Get the fully rendered HTML including tab processing.
     * Calls render() to process tab markers into final HTML.
     */
    private function getFullyRenderedHtml(): string
    {
        $reflection = new \ReflectionClass($this->form);
        $property   = $reflection->getProperty('elements');
        $property->setAccessible(true);
        $elements = $property->getValue($this->form);

        $rendered = $this->form->render($elements);

        if (is_array($rendered)) {
            return implode('', $rendered);
        }

        return (string) $rendered;
    }

    // ── selectbox() tests ─────────────────────────────────────────────────

    /**
     * @test
     * Requirement 4.5, 7.5: $form->selectbox() with `default` template uses
     *                        `chosen-select-deselect chosen-selectbox` class.
     */
    public function test_selectbox_with_default_template_uses_chosen_select_class(): void
    {
        $this->setTemplate('default');

        $this->form->selectbox(
            'status',
            ['active' => 'Active', 'inactive' => 'Inactive'],
            false,
            [],
            true,
            [null => 'Select']
        );

        $html = $this->getRenderedHtml();

        $this->assertStringContainsString(
            'chosen-select-deselect chosen-selectbox',
            $html,
            'default template selectbox must use chosen-select-deselect chosen-selectbox class'
        );
    }

    /**
     * @test
     * Requirement 5.4, 5.6: $form->selectbox() with `canvasign` template uses `form-select` class.
     */
    public function test_selectbox_with_canvasign_template_uses_form_select_class(): void
    {
        $this->setTemplate('canvasign');

        $this->form->selectbox(
            'status',
            ['active' => 'Active', 'inactive' => 'Inactive'],
            false,
            [],
            true,
            [null => 'Select']
        );

        $html = $this->getRenderedHtml();

        $this->assertStringContainsString(
            'form-select',
            $html,
            'canvasign template selectbox must use form-select class'
        );

        $this->assertStringNotContainsString(
            'chosen-select',
            $html,
            'canvasign template selectbox must NOT use chosen-select class'
        );
    }

    /**
     * @test
     * Requirement 6.3, 6.6: $form->selectbox() with `canvas` template uses `form-input` class.
     */
    public function test_selectbox_with_canvas_template_uses_form_input_class(): void
    {
        $this->setTemplate('canvas');

        $this->form->selectbox(
            'status',
            ['active' => 'Active', 'inactive' => 'Inactive'],
            false,
            [],
            true,
            [null => 'Select']
        );

        $html = $this->getRenderedHtml();

        $this->assertStringContainsString(
            'form-input',
            $html,
            'canvas template selectbox must use form-input class'
        );

        $this->assertStringNotContainsString(
            'chosen-select',
            $html,
            'canvas template selectbox must NOT use chosen-select class'
        );

        $this->assertStringNotContainsString(
            'form-select',
            $html,
            'canvas template selectbox must NOT use Bootstrap 5 form-select class'
        );
    }

    // ── checkbox() tests ──────────────────────────────────────────────────

    /**
     * @test
     * Requirement 4.4: $form->checkbox() with `default` template contains `ckbox` class.
     */
    public function test_checkbox_with_default_template_contains_ckbox_class(): void
    {
        $this->setTemplate('default');

        $this->form->checkbox(
            'agree',
            [1 => 'I agree to the terms'],
            [],
            [],
            true
        );

        $html = $this->getRenderedHtml();

        $this->assertStringContainsString(
            'ckbox',
            $html,
            'default template checkbox must contain ckbox class'
        );
    }

    /**
     * @test
     * Requirement 5.7: $form->checkbox() with `canvasign` template contains `form-check` class
     *                   and does NOT contain `ckbox`.
     */
    public function test_checkbox_with_canvasign_template_contains_form_check_and_no_ckbox(): void
    {
        $this->setTemplate('canvasign');

        $this->form->checkbox(
            'agree',
            [1 => 'I agree to the terms'],
            [],
            [],
            true
        );

        $html = $this->getRenderedHtml();

        $this->assertStringContainsString(
            'form-check',
            $html,
            'canvasign template checkbox must contain form-check class'
        );

        $this->assertStringNotContainsString(
            'class="col-sm-3 ckbox',
            $html,
            'canvasign template checkbox must NOT contain Bootstrap 4 ckbox wrapper'
        );
    }

    /**
     * @test
     * Requirement 6.4: $form->checkbox() with `canvas` template contains `flex items-center`
     *                   and does NOT contain `ckbox`.
     */
    public function test_checkbox_with_canvas_template_contains_flex_items_center_and_no_ckbox(): void
    {
        $this->setTemplate('canvas');

        $this->form->checkbox(
            'agree',
            [1 => 'I agree to the terms'],
            [],
            [],
            true
        );

        $html = $this->getRenderedHtml();

        $this->assertStringContainsString(
            'flex items-center',
            $html,
            'canvas template checkbox must contain flex items-center Tailwind classes'
        );

        $this->assertStringNotContainsString(
            'class="col-sm-3 ckbox',
            $html,
            'canvas template checkbox must NOT contain Bootstrap 4 ckbox wrapper'
        );

        $this->assertStringNotContainsString(
            'form-check',
            $html,
            'canvas template checkbox must NOT contain Bootstrap 5 form-check class'
        );
    }

    // ── openTab() / closeTab() tests ──────────────────────────────────────

    /**
     * @test
     * Requirement 6.1, 7.5: $form->openTab() / $form->closeTab() with `canvas` template
     *                        produces Tailwind tab wrapper (w-full, flex border-b).
     */
    public function test_tab_with_canvas_template_produces_tailwind_wrapper(): void
    {
        $this->setTemplate('canvas');

        $this->form->open('/test-form', 'POST', 'horizontal', false);
        $this->form->openTab('General', false);
        $this->form->text('name', '', [], 'Name');
        $this->form->openTab('Details', false);
        $this->form->text('description', '', [], 'Description');
        $this->form->closeTab();
        $this->form->close('Submit', false, '', '');

        $html = $this->getFullyRenderedHtml();

        $this->assertStringContainsString(
            'w-full',
            $html,
            'canvas template tab wrapper must contain Tailwind w-full class'
        );

        $this->assertStringContainsString(
            'flex border-b border-gray-200',
            $html,
            'canvas template tab wrapper must contain Tailwind flex border-b border-gray-200 classes'
        );

        $this->assertStringNotContainsString(
            'class="tabbable"',
            $html,
            'canvas template tab wrapper must NOT contain Bootstrap tabbable class'
        );

        $this->assertStringNotContainsString(
            'class="nav nav-tabs"',
            $html,
            'canvas template tab wrapper must NOT contain Bootstrap nav nav-tabs class'
        );
    }

    /**
     * @test
     * Requirement 4.2, 7.7: $form->openTab() / $form->closeTab() with `default` template
     *                        produces identical output to pre-integration (tabbable + nav-tabs).
     */
    public function test_tab_with_default_template_produces_bootstrap4_wrapper(): void
    {
        $this->setTemplate('default');

        $this->form->open('/test-form', 'POST', 'horizontal', false);
        $this->form->openTab('General', false);
        $this->form->text('name', '', [], 'Name');
        $this->form->openTab('Details', false);
        $this->form->text('description', '', [], 'Description');
        $this->form->closeTab();
        $this->form->close('Submit', false, '', '');

        $html = $this->getFullyRenderedHtml();

        $this->assertStringContainsString(
            'class="tabbable"',
            $html,
            'default template tab wrapper must contain tabbable class'
        );

        $this->assertStringContainsString(
            'class="nav nav-tabs"',
            $html,
            'default template tab wrapper must contain nav nav-tabs class'
        );

        $this->assertStringContainsString(
            'class="tab-content"',
            $html,
            'default template tab wrapper must contain tab-content class'
        );

        $this->assertStringNotContainsString(
            'w-full',
            $html,
            'default template tab wrapper must NOT contain Tailwind w-full class'
        );
    }

    // ── Adapter resolution verification ──────────────────────────────────

    /**
     * @test
     * Verify that the resolver returns the correct adapter for each template.
     */
    public function test_resolver_returns_correct_adapter_for_each_template(): void
    {
        $this->setTemplate('default');
        $this->assertInstanceOf(DefaultAdapter::class, ThemeAdapterResolver::resolve());

        $this->setTemplate('canvasign');
        $this->assertInstanceOf(Bootstrap5Adapter::class, ThemeAdapterResolver::resolve());

        $this->setTemplate('canvas');
        $this->assertInstanceOf(TailwindAdapter::class, ThemeAdapterResolver::resolve());
    }

    /**
     * @test
     * Requirement 7.7: getSelectBoxClass() returns correct value per adapter.
     */
    public function test_get_select_box_class_returns_correct_value_per_adapter(): void
    {
        $this->setTemplate('default');
        $this->assertSame(
            'chosen-select-deselect chosen-selectbox',
            ThemeAdapterResolver::resolve()->getSelectBoxClass(),
            'default adapter must return chosen-select-deselect chosen-selectbox'
        );

        $this->setTemplate('canvasign');
        $this->assertSame(
            'form-select',
            ThemeAdapterResolver::resolve()->getSelectBoxClass(),
            'canvasign adapter must return form-select'
        );

        $this->setTemplate('canvas');
        $this->assertSame(
            'form-input',
            ThemeAdapterResolver::resolve()->getSelectBoxClass(),
            'canvas adapter must return form-input'
        );
    }

    /**
     * @test
     * Requirement 4.4, 5.7, 6.4: renderCheckboxWrapper() returns correct HTML per adapter.
     */
    public function test_render_checkbox_wrapper_returns_correct_html_per_adapter(): void
    {
        $inputHtml = '<input type="checkbox" name="test">';
        $labelHtml = '<label for="test">Test</label>';

        // DefaultAdapter
        $defaultAdapter = new DefaultAdapter();
        $defaultOutput  = $defaultAdapter->renderCheckboxWrapper(' ckbox-primary', $inputHtml, $labelHtml);
        $this->assertStringContainsString('col-sm-3', $defaultOutput);
        $this->assertStringContainsString('ckbox', $defaultOutput);

        // Bootstrap5Adapter
        $bs5Adapter = new Bootstrap5Adapter();
        $bs5Output  = $bs5Adapter->renderCheckboxWrapper(' ckbox-primary', $inputHtml, $labelHtml);
        $this->assertStringContainsString('form-check', $bs5Output);
        $this->assertStringNotContainsString('col-sm-3', $bs5Output);
        // BS5 adapter uses form-check, not the Bootstrap 4 ckbox wrapper
        $this->assertStringNotContainsString('class="col-sm-3 ckbox', $bs5Output);

        // TailwindAdapter
        $twAdapter = new TailwindAdapter();
        $twOutput  = $twAdapter->renderCheckboxWrapper(' ckbox-primary', $inputHtml, $labelHtml);
        $this->assertStringContainsString('flex items-center gap-2', $twOutput);
        $this->assertStringNotContainsString('class="col-sm-3 ckbox', $twOutput);
        $this->assertStringNotContainsString('form-check', $twOutput);
    }

    /**
     * @test
     * Requirement 4.2, 5.1, 6.1: renderTabWrapper() returns correct HTML per adapter.
     */
    public function test_render_tab_wrapper_returns_correct_html_per_adapter(): void
    {
        $headersHtml  = '<li>Tab 1</li><li>Tab 2</li>';
        $contentsHtml = '<div>Content 1</div><div>Content 2</div>';

        // DefaultAdapter
        $defaultAdapter = new DefaultAdapter();
        $defaultOutput  = $defaultAdapter->renderTabWrapper($headersHtml, $contentsHtml);
        $this->assertStringContainsString('class="tabbable"', $defaultOutput);
        $this->assertStringContainsString('class="nav nav-tabs"', $defaultOutput);
        $this->assertStringContainsString('class="tab-content"', $defaultOutput);
        $this->assertStringContainsString('<br />', $defaultOutput);

        // Bootstrap5Adapter — same structure as DefaultAdapter
        $bs5Adapter = new Bootstrap5Adapter();
        $bs5Output  = $bs5Adapter->renderTabWrapper($headersHtml, $contentsHtml);
        $this->assertStringContainsString('class="tabbable"', $bs5Output);
        $this->assertStringContainsString('class="nav nav-tabs"', $bs5Output);
        $this->assertStringContainsString('class="tab-content"', $bs5Output);

        // TailwindAdapter
        $twAdapter = new TailwindAdapter();
        $twOutput  = $twAdapter->renderTabWrapper($headersHtml, $contentsHtml);
        $this->assertStringContainsString('class="w-full"', $twOutput);
        $this->assertStringContainsString('flex border-b border-gray-200', $twOutput);
        $this->assertStringContainsString('tab-content pt-4', $twOutput);
        $this->assertStringNotContainsString('tabbable', $twOutput);
        $this->assertStringNotContainsString('nav-tabs', $twOutput);
    }
}
