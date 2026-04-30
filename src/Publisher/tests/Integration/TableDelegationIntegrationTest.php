<?php

namespace Tests\Integration;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Integration tests for Table helper delegation to ThemeAdapterResolver.
 *
 * Verifies that each Table helper function:
 *   1. Delegates to the correct adapter method.
 *   2. With template `default` produces output identical to DefaultAdapter directly.
 *   3. With template `canvasign` produces Bootstrap 5 output.
 *   4. With template `canvas` produces Tailwind output (no Bootstrap-specific classes).
 *
 * Requirements: 8.6, 9.6, 10.6
 *
 * @group integration
 * @group theme-adapter
 * @group table-delegation
 */
class TableDelegationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::resetAll();
        
        // Disable privilege checking for action buttons in tests
        config(['canvastack.datatables.actions.check_privileges' => false]);
        
        // Enable actions
        config(['canvastack.datatables.actions.enabled' => true]);
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

    /**
     * Build a minimal row data object for action button tests.
     */
    private function makeRowData(int $id = 1): object
    {
        return (object) ['id' => $id, 'name' => 'Test User'];
    }

    // ── 1. Modal HTML — canvastack_modal_content_html() ──────────────────

    /**
     * @test
     * Requirement 8.6: With template `default`, canvastack_modal_content_html()
     *                  produces output identical to pre-integration behavior
     *                  (i.e. identical to DefaultAdapter::renderFilterModal() directly).
     */
    public function test_default_template_modal_html_matches_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $name     = 'users_CanvaStackFILTERmodalBOX';
        $title    = 'Users';
        $elements = ['<input type="text" name="search" />', '<input type="text" name="status" />'];

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderFilterModal($name, $title, $elements);
        $helperOutput  = canvastack_modal_content_html($name, $title, $elements);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, canvastack_modal_content_html() must be identical to DefaultAdapter::renderFilterModal()'
        );
    }

    /**
     * @test
     * Requirement 8.2: With template `default`, modal HTML contains Bootstrap 4
     *                  attributes: data-dismiss="modal", pull-right, hide.
     */
    public function test_default_template_modal_html_contains_bootstrap4_markers(): void
    {
        $this->setTemplate('default');

        $output = canvastack_modal_content_html(
            'test_CanvaStackFILTERmodalBOX',
            'Test Filter',
            ['<input type="text" name="q" />']
        );

        $this->assertStringContainsString(
            'data-dismiss="modal"',
            $output,
            'default template modal must contain Bootstrap 4 data-dismiss="modal"'
        );

        $this->assertStringContainsString(
            'pull-right',
            $output,
            'default template modal must contain Bootstrap 4 pull-right class'
        );

        $this->assertStringContainsString(
            'hide',
            $output,
            'default template modal must contain Bootstrap 4 hide class'
        );
    }

    /**
     * @test
     * Requirement 8.3: With template `canvasign`, modal HTML contains Bootstrap 5
     *                  attributes: data-bs-dismiss="modal", float-end, d-none.
     */
    public function test_canvasign_template_modal_html_contains_bootstrap5_markers(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_modal_content_html(
            'test_CanvaStackFILTERmodalBOX',
            'Test Filter',
            ['<input type="text" name="q" />']
        );

        $this->assertStringContainsString(
            'data-bs-dismiss="modal"',
            $output,
            'canvasign template modal must contain Bootstrap 5 data-bs-dismiss="modal"'
        );

        $this->assertStringContainsString(
            'float-end',
            $output,
            'canvasign template modal must contain Bootstrap 5 float-end class'
        );

        $this->assertStringContainsString(
            'd-none',
            $output,
            'canvasign template modal must contain Bootstrap 5 d-none class'
        );

        // Must NOT contain Bootstrap 4 attributes
        $this->assertStringNotContainsString(
            'data-dismiss="modal"',
            str_replace('data-bs-dismiss="modal"', '', $output),
            'canvasign template modal must NOT contain Bootstrap 4 data-dismiss="modal"'
        );

        $this->assertStringNotContainsString(
            'pull-right',
            $output,
            'canvasign template modal must NOT contain Bootstrap 4 pull-right class'
        );

        $this->assertStringNotContainsString(
            '"hide"',
            $output,
            'canvasign template modal must NOT contain Bootstrap 4 hide class'
        );
    }

    /**
     * @test
     * Requirement 8.4: With template `canvas`, modal HTML contains Tailwind classes
     *                  and no Bootstrap-specific attributes.
     */
    public function test_canvas_template_modal_html_contains_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_modal_content_html(
            'test_CanvaStackFILTERmodalBOX',
            'Test Filter',
            ['<input type="text" name="q" />']
        );

        // Must contain Tailwind utility classes
        $this->assertStringContainsString(
            'px-6',
            $output,
            'canvas template modal must contain Tailwind padding classes'
        );

        $this->assertStringContainsString(
            'rounded-lg',
            $output,
            'canvas template modal must contain Tailwind rounded-lg class'
        );

        // Must NOT contain Bootstrap-specific attributes
        $this->assertStringNotContainsString(
            'data-bs-dismiss="modal"',
            $output,
            'canvas template modal must NOT contain Bootstrap 5 data-bs-dismiss="modal"'
        );

        $this->assertStringNotContainsString(
            'pull-right',
            $output,
            'canvas template modal must NOT contain Bootstrap 4 pull-right class'
        );

        $this->assertStringNotContainsString(
            'float-end',
            $output,
            'canvas template modal must NOT contain Bootstrap 5 float-end class'
        );

        $this->assertStringNotContainsString(
            'd-none',
            $output,
            'canvas template modal must NOT contain Bootstrap 5 d-none class'
        );

        $this->assertStringNotContainsString(
            '"hide"',
            $output,
            'canvas template modal must NOT contain Bootstrap 4 hide class'
        );
    }

    /**
     * @test
     * Requirement 8.1: canvastack_modal_content_html() delegates to
     *                  ThemeAdapterResolver::resolve()->renderFilterModal().
     *
     * Verified by comparing helper output to adapter output directly.
     */
    public function test_modal_html_delegates_to_adapter_render_filter_modal(): void
    {
        $this->setTemplate('canvasign');

        $name     = 'orders_CanvaStackFILTERmodalBOX';
        $title    = 'Orders';
        $elements = ['<input type="text" name="status" />'];

        $adapterOutput = ThemeAdapterResolver::resolve()->renderFilterModal($name, $title, $elements);
        $helperOutput  = canvastack_modal_content_html($name, $title, $elements);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_modal_content_html() must delegate to renderFilterModal()'
        );
    }

    // ── 2. Table class — canvastack_table_setup_attributes() / getTableClass() ──

    /**
     * @test
     * Requirement 9.6: With template `default`, getTableClass() returns the full
     *                  Bootstrap 4 class string including `animated fadeIn`.
     */
    public function test_default_template_table_class_contains_bootstrap4_markers(): void
    {
        $this->setTemplate('default');

        $tableClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->assertStringContainsString(
            'animated',
            $tableClass,
            'default template table class must contain animated'
        );

        $this->assertStringContainsString(
            'fadeIn',
            $tableClass,
            'default template table class must contain fadeIn'
        );

        $this->assertStringContainsString(
            'CanvaStack-table',
            $tableClass,
            'default template table class must contain CanvaStack-table'
        );

        $this->assertStringContainsString(
            'table-striped',
            $tableClass,
            'default template table class must contain table-striped'
        );
    }

    /**
     * @test
     * Requirement 9.3: With template `default`, getTableClass() returns the value
     *                  identical to the CANVASTACK_DEFAULT_TABLE_CLASS constant.
     */
    public function test_default_template_table_class_matches_constant(): void
    {
        $this->setTemplate('default');

        $tableClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->assertSame(
            CANVASTACK_DEFAULT_TABLE_CLASS,
            $tableClass,
            'default template getTableClass() must return the same value as CANVASTACK_DEFAULT_TABLE_CLASS'
        );
    }

    /**
     * @test
     * Requirement 9.4: With template `canvasign`, getTableClass() returns Bootstrap 5
     *                  classes without `animated` and `fadeIn`.
     */
    public function test_canvasign_template_table_class_omits_animation_classes(): void
    {
        $this->setTemplate('canvasign');

        $tableClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->assertStringNotContainsString(
            'animated',
            $tableClass,
            'canvasign template table class must NOT contain animated'
        );

        $this->assertStringNotContainsString(
            'fadeIn',
            $tableClass,
            'canvasign template table class must NOT contain fadeIn'
        );

        $this->assertStringContainsString(
            'CanvaStack-table',
            $tableClass,
            'canvasign template table class must still contain CanvaStack-table'
        );

        $this->assertStringContainsString(
            'table-striped',
            $tableClass,
            'canvasign template table class must contain table-striped'
        );
    }

    /**
     * @test
     * Requirement 9.5: With template `canvas`, getTableClass() returns Tailwind
     *                  utility classes like `w-full text-sm text-left`.
     */
    public function test_canvas_template_table_class_contains_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $tableClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->assertStringContainsString(
            'w-full',
            $tableClass,
            'canvas template table class must contain Tailwind w-full'
        );

        $this->assertStringContainsString(
            'text-sm',
            $tableClass,
            'canvas template table class must contain Tailwind text-sm'
        );

        $this->assertStringContainsString(
            'text-left',
            $tableClass,
            'canvas template table class must contain Tailwind text-left'
        );

        $this->assertStringNotContainsString(
            'animated',
            $tableClass,
            'canvas template table class must NOT contain Bootstrap animated class'
        );

        $this->assertStringNotContainsString(
            'table-striped',
            $tableClass,
            'canvas template table class must NOT contain Bootstrap table-striped class'
        );
    }

    /**
     * @test
     * Requirement 9.2: canvastack_table_setup_attributes() uses getTableClass()
     *                  from the resolver instead of the hardcoded constant.
     *
     * Verified by checking that the attributes string contains the adapter's
     * table class for each template.
     */
    public function test_table_setup_attributes_uses_adapter_table_class(): void
    {
        $this->setTemplate('canvasign');

        $attributes = canvastack_table_setup_attributes('test_table', false);

        // The Bootstrap5Adapter table class does NOT contain 'animated' or 'fadeIn'
        $this->assertStringNotContainsString(
            'animated',
            $attributes,
            'canvasign template table attributes must NOT contain animated class'
        );

        $this->assertStringNotContainsString(
            'fadeIn',
            $attributes,
            'canvasign template table attributes must NOT contain fadeIn class'
        );

        // Must still contain the CanvaStack-table identifier
        $this->assertStringContainsString(
            'CanvaStack-table',
            $attributes,
            'canvasign template table attributes must contain CanvaStack-table'
        );
    }

    /**
     * @test
     * Requirement 9.6: With template `default`, canvastack_table_setup_attributes()
     *                  produces output identical to pre-integration behavior
     *                  (uses CANVASTACK_DEFAULT_TABLE_CLASS constant value).
     */
    public function test_default_template_table_setup_attributes_uses_constant_value(): void
    {
        $this->setTemplate('default');

        $attributes = canvastack_table_setup_attributes('test_table', false);

        $this->assertStringContainsString(
            CANVASTACK_DEFAULT_TABLE_CLASS,
            $attributes,
            'default template table attributes must contain the CANVASTACK_DEFAULT_TABLE_CLASS value'
        );
    }

    // ── 3. Action buttons — canvastack_table_action_button() ─────────────

    /**
     * @test
     * Requirement 10.6: With template `default`, canvastack_table_action_button()
     *                   produces output identical to DefaultAdapter::renderActionButtons() directly.
     */
    public function test_default_template_action_buttons_match_default_adapter_directly(): void
    {
        $this->setTemplate('default');

        $rowData    = $this->makeRowData(42);
        $fieldTarget = 'id';
        $currentUrl  = '/admin/users';
        $action      = ['view', 'edit', 'delete'];

        $directAdapter = new DefaultAdapter();
        $directOutput  = $directAdapter->renderActionButtons($rowData, $fieldTarget, $currentUrl, $action, null);
        $helperOutput  = canvastack_table_action_button($rowData, $fieldTarget, $currentUrl, $action, null);

        $this->assertSame(
            $directOutput,
            $helperOutput,
            'With default template, canvastack_table_action_button() must be identical to DefaultAdapter::renderActionButtons()'
        );
    }

    /**
     * @test
     * Requirement 10.2: With template `default`, action buttons contain `btn-xs`.
     */
    public function test_default_template_action_buttons_contain_btn_xs(): void
    {
        $this->setTemplate('default');

        $output = canvastack_table_action_button(
            $this->makeRowData(1),
            'id',
            '/admin/users',
            ['view', 'edit', 'delete'],
            null
        );

        $this->assertStringContainsString(
            'btn-xs',
            $output,
            'default template action buttons must contain Bootstrap 4 btn-xs class'
        );
    }

    /**
     * @test
     * Requirement 10.3: With template `canvasign`, action buttons contain `btn-sm`
     *                   instead of `btn-xs`.
     */
    public function test_canvasign_template_action_buttons_contain_btn_sm(): void
    {
        $this->setTemplate('canvasign');

        $output = canvastack_table_action_button(
            $this->makeRowData(1),
            'id',
            '/admin/users',
            ['view', 'edit', 'delete'],
            null
        );

        $this->assertStringContainsString(
            'btn-sm',
            $output,
            'canvasign template action buttons must contain Bootstrap 5 btn-sm class'
        );

        $this->assertStringNotContainsString(
            'btn-xs',
            $output,
            'canvasign template action buttons must NOT contain Bootstrap 4 btn-xs class'
        );
    }

    /**
     * @test
     * Requirement 10.4: With template `canvas`, action buttons contain Tailwind
     *                   button classes and no `btn-xs`.
     */
    public function test_canvas_template_action_buttons_contain_tailwind_markers(): void
    {
        $this->setTemplate('canvas');

        $output = canvastack_table_action_button(
            $this->makeRowData(1),
            'id',
            '/admin/users',
            ['view', 'edit', 'delete'],
            null
        );

        // Must NOT contain Bootstrap-specific button size classes
        $this->assertStringNotContainsString(
            'btn-xs',
            $output,
            'canvas template action buttons must NOT contain Bootstrap 4 btn-xs class'
        );

        $this->assertStringNotContainsString(
            'btn-sm',
            $output,
            'canvas template action buttons must NOT contain Bootstrap 5 btn-sm class'
        );

        // Must contain Tailwind utility classes
        $this->assertStringContainsString(
            'inline-flex',
            $output,
            'canvas template action buttons must contain Tailwind inline-flex class'
        );
    }

    /**
     * @test
     * Requirement 10.1: canvastack_table_action_button() delegates to
     *                   ThemeAdapterResolver::resolve()->renderActionButtons().
     *
     * Verified by comparing helper output to adapter output directly.
     */
    public function test_action_button_delegates_to_adapter_render_action_buttons(): void
    {
        $this->setTemplate('default');

        $rowData     = $this->makeRowData(5);
        $fieldTarget = 'id';
        $currentUrl  = '/admin/orders';
        $action      = ['view', 'edit'];

        $adapterOutput = ThemeAdapterResolver::resolve()->renderActionButtons($rowData, $fieldTarget, $currentUrl, $action, null);
        $helperOutput  = canvastack_table_action_button($rowData, $fieldTarget, $currentUrl, $action, null);

        $this->assertSame(
            $adapterOutput,
            $helperOutput,
            'canvastack_table_action_button() must delegate to renderActionButtons()'
        );
    }

    // ── 4. Resolver returns correct adapter type per template ─────────────

    /**
     * @test
     * With template `default`, resolver returns DefaultAdapter.
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
     * With template `canvasign`, resolver returns Bootstrap5Adapter.
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
     * With template `canvas`, resolver returns TailwindAdapter.
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

    // ── 5. Public API signatures unchanged ───────────────────────────────

    /**
     * @test
     * Requirement 8.5: canvastack_modal_content_html() signature is unchanged.
     */
    public function test_modal_content_html_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_modal_content_html');
        $params     = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertSame('name', $params[0]->getName());
        $this->assertSame('title', $params[1]->getName());
        $this->assertSame('elements', $params[2]->getName());
    }

    /**
     * @test
     * Requirement 10.5: canvastack_table_action_button() signature is unchanged.
     */
    public function test_table_action_button_signature_unchanged(): void
    {
        $reflection = new \ReflectionFunction('canvastack_table_action_button');
        $params     = $reflection->getParameters();

        $this->assertCount(5, $params);
        $this->assertSame('row_data', $params[0]->getName());
        $this->assertSame('field_target', $params[1]->getName());
        $this->assertSame('current_url', $params[2]->getName());
        $this->assertSame('action', $params[3]->getName());
        $this->assertSame('removed_button', $params[4]->getName());

        // Check default for removed_button
        $this->assertTrue($params[4]->isDefaultValueAvailable());
        $this->assertNull($params[4]->getDefaultValue());
    }

    // ── 6. Cross-template isolation ───────────────────────────────────────

    /**
     * @test
     * Switching templates between calls produces different output.
     * Verifies that ThemeAdapterResolver::reset() properly clears the cache.
     */
    public function test_switching_templates_produces_different_modal_output(): void
    {
        $name     = 'filter_CanvaStackFILTERmodalBOX';
        $title    = 'Filter';
        $elements = ['<input type="text" name="q" />'];

        $this->setTemplate('default');
        $defaultOutput = canvastack_modal_content_html($name, $title, $elements);

        $this->setTemplate('canvasign');
        $canvasignOutput = canvastack_modal_content_html($name, $title, $elements);

        $this->setTemplate('canvas');
        $canvasOutput = canvastack_modal_content_html($name, $title, $elements);

        $this->assertNotSame(
            $defaultOutput,
            $canvasignOutput,
            'default and canvasign templates must produce different modal HTML'
        );

        $this->assertNotSame(
            $defaultOutput,
            $canvasOutput,
            'default and canvas templates must produce different modal HTML'
        );

        $this->assertNotSame(
            $canvasignOutput,
            $canvasOutput,
            'canvasign and canvas templates must produce different modal HTML'
        );
    }

    /**
     * @test
     * Switching templates between calls produces different table class output.
     */
    public function test_switching_templates_produces_different_table_class(): void
    {
        $this->setTemplate('default');
        $defaultClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->setTemplate('canvasign');
        $canvasignClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->setTemplate('canvas');
        $canvasClass = ThemeAdapterResolver::resolve()->getTableClass();

        $this->assertNotSame(
            $defaultClass,
            $canvasignClass,
            'default and canvasign templates must produce different table classes'
        );

        $this->assertNotSame(
            $defaultClass,
            $canvasClass,
            'default and canvas templates must produce different table classes'
        );

        $this->assertNotSame(
            $canvasignClass,
            $canvasClass,
            'canvasign and canvas templates must produce different table classes'
        );
    }

    /**
     * @test
     * Switching templates between calls produces different action button output.
     */
    public function test_switching_templates_produces_different_action_button_output(): void
    {
        $rowData     = $this->makeRowData(1);
        $fieldTarget = 'id';
        $currentUrl  = '/admin/users';
        $action      = ['view', 'edit', 'delete'];

        $this->setTemplate('default');
        $defaultOutput = canvastack_table_action_button($rowData, $fieldTarget, $currentUrl, $action, null);

        $this->setTemplate('canvasign');
        $canvasignOutput = canvastack_table_action_button($rowData, $fieldTarget, $currentUrl, $action, null);

        $this->setTemplate('canvas');
        $canvasOutput = canvastack_table_action_button($rowData, $fieldTarget, $currentUrl, $action, null);

        $this->assertNotSame(
            $defaultOutput,
            $canvasignOutput,
            'default and canvasign templates must produce different action button HTML'
        );

        $this->assertNotSame(
            $defaultOutput,
            $canvasOutput,
            'default and canvas templates must produce different action button HTML'
        );
    }
}
