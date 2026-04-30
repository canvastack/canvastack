<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Unit tests for ThemeAdapterResolver registration and reset behaviour.
 *
 * Covers:
 * - register() with a valid adapter class
 * - register() throws InvalidArgumentException for an invalid class
 * - register() invalidates the cached instance for the given template
 * - reset() clears all cached instances
 *
 * Requirements: 3.6
 */
class ThemeAdapterResolverUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ThemeAdapterResolver::resetAll();
    }

    protected function tearDown(): void
    {
        // resetAll() restores both instances cache AND registry to defaults,
        // preventing registry mutations in one test from bleeding into the next.
        ThemeAdapterResolver::resetAll();
        parent::tearDown();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Set the active template via Laravel config so canvastack_current_template()
     * returns the desired value.
     */
    private function setTemplate(string $template): void
    {
        config(['canvastack.settings.template' => $template]);
        ThemeAdapterResolver::reset();
    }

    // ── register() with a valid adapter class ────────────────────────────

    /**
     * @test
     * Requirement 3.6: register() accepts a valid adapter class and the next
     *                  resolve() call returns an instance of that class.
     */
    public function test_register_with_valid_adapter_class_is_used_on_next_resolve(): void
    {
        // Create an anonymous adapter class that implements the interface
        $customAdapterClass = new class implements ThemeAdapterInterface {
            public function renderTabHeader(string $data, string $pointer, string|false $active, string|false $class): string { return ''; }
            public function renderTabContent(string $data, string $pointer, bool $active): string { return ''; }
            public function renderAlertMessage(string|array $message, string $type, string $title, string $prefix, string|false $extra): string { return ''; }
            public function renderCheckList(mixed $name, string|false $value, string|false $label, bool $checked, string $class, string|false $id, ?string $inputNode): string { return ''; }
            public function renderSelectBox(string $name, array $values, mixed $selected, array $attributes, bool $label, array|bool $set_first_value): string { return ''; }
            public function renderModalWrapper(string $name, string $title, array $elements): string { return ''; }
            public function getSelectBoxClass(): string { return 'custom-select'; }
            public function getDataToggleAttribute(): string { return 'data-toggle'; }
            public function renderFilterModal(string $name, string $title, array $elements): string { return ''; }
            public function getTableClass(): string { return 'custom-table'; }
            public function renderActionButtons(object $rowData, string $fieldTarget, string $currentUrl, mixed $action, ?array $removedButtons): string { return ''; }
            public function getDismissAttribute(): string { return 'data-dismiss'; }
            public function getHideClass(): string { return 'hidden'; }
            public function getFloatRightClass(): string { return 'ml-auto'; }
            public function renderCheckboxWrapper(string $checkboxType, string $inputHtml, string $labelHtml): string { return $inputHtml . $labelHtml; }
            public function renderTabWrapper(string $headersHtml, string $contentsHtml): string { return $headersHtml . $contentsHtml; }
            public function getContainerClass(): string { return 'custom-container'; }
            public function getRowClass(): string { return 'custom-row'; }
            public function getColumnClass(int $columns): string { return "custom-col-{$columns}"; }
        };

        $customClass = get_class($customAdapterClass);

        // Register the custom adapter for a new template name
        ThemeAdapterResolver::register('mytheme', $customClass);

        // Resolve with the new template
        $this->setTemplate('mytheme');
        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            $customClass,
            $adapter,
            'register() must make the custom adapter available via resolve()'
        );

        $this->assertInstanceOf(
            ThemeAdapterInterface::class,
            $adapter,
            'Registered adapter must implement ThemeAdapterInterface'
        );
    }

    /**
     * @test
     * Requirement 3.6: register() can override an existing registered template.
     */
    public function test_register_can_override_existing_template_mapping(): void
    {
        // Override 'default' template to use Bootstrap5Adapter
        ThemeAdapterResolver::register('default', Bootstrap5Adapter::class);

        $this->setTemplate('default');
        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            Bootstrap5Adapter::class,
            $adapter,
            'register() must allow overriding an existing template mapping'
        );
    }

    // ── register() throws for invalid class ──────────────────────────────

    /**
     * @test
     * Requirement 3.6: register() throws InvalidArgumentException when the given
     *                  class does not implement ThemeAdapterInterface.
     */
    public function test_register_throws_for_class_not_implementing_interface(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ThemeAdapterResolver::register('badtheme', \stdClass::class);
    }

    /**
     * @test
     * Requirement 3.6: register() throws InvalidArgumentException for a
     *                  non-existent class name.
     */
    public function test_register_throws_for_nonexistent_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ThemeAdapterResolver::register('badtheme', 'NonExistent\\Class\\That\\DoesNotExist');
    }

    /**
     * @test
     * Requirement 3.6: The exception message from register() mentions the class name.
     */
    public function test_register_exception_message_contains_class_name(): void
    {
        $invalidClass = \stdClass::class;

        try {
            ThemeAdapterResolver::register('badtheme', $invalidClass);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString(
                $invalidClass,
                $e->getMessage(),
                'Exception message must mention the invalid class name'
            );
        }
    }

    // ── register() invalidates cached instance ────────────────────────────

    /**
     * @test
     * Requirement 3.6: register() invalidates the cached instance for the given
     *                  template so the next resolve() creates a fresh instance.
     */
    public function test_register_invalidates_cached_instance_for_template(): void
    {
        // Resolve 'canvasign' to cache a Bootstrap5Adapter instance
        $this->setTemplate('canvasign');
        $originalAdapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(Bootstrap5Adapter::class, $originalAdapter);

        // Re-register 'canvasign' with DefaultAdapter (override)
        ThemeAdapterResolver::register('canvasign', DefaultAdapter::class);

        // The cached instance should be gone; next resolve() returns DefaultAdapter
        config(['canvastack.settings.template' => 'canvasign']);
        $newAdapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            DefaultAdapter::class,
            $newAdapter,
            'register() must invalidate the cached instance so the new class is used'
        );

        $this->assertNotSame(
            $originalAdapter,
            $newAdapter,
            'After register(), resolve() must return a new instance, not the cached one'
        );
    }

    /**
     * @test
     * Requirement 3.6: register() only invalidates the cache for the registered
     *                  template, not for other templates.
     */
    public function test_register_does_not_invalidate_cache_for_other_templates(): void
    {
        // Cache instances for 'default' and 'canvas'
        $this->setTemplate('default');
        $defaultAdapter = ThemeAdapterResolver::resolve();

        config(['canvastack.settings.template' => 'canvas']);
        $canvasAdapter = ThemeAdapterResolver::resolve();

        // Register a change for 'canvasign' only
        ThemeAdapterResolver::register('canvasign', DefaultAdapter::class);

        // 'default' and 'canvas' caches should be unaffected
        config(['canvastack.settings.template' => 'default']);
        $defaultAdapterAfter = ThemeAdapterResolver::resolve();

        config(['canvastack.settings.template' => 'canvas']);
        $canvasAdapterAfter = ThemeAdapterResolver::resolve();

        $this->assertSame(
            $defaultAdapter,
            $defaultAdapterAfter,
            "register() for 'canvasign' must not invalidate the 'default' cache"
        );

        $this->assertSame(
            $canvasAdapter,
            $canvasAdapterAfter,
            "register() for 'canvasign' must not invalidate the 'canvas' cache"
        );
    }

    // ── reset() clears all cached instances ──────────────────────────────

    /**
     * @test
     * Requirement 3.7: reset() clears all cached adapter instances so the next
     *                  resolve() creates fresh instances.
     */
    public function test_reset_clears_all_cached_instances(): void
    {
        // Cache instances for all registered templates
        $this->setTemplate('default');
        $defaultBefore = ThemeAdapterResolver::resolve();

        config(['canvastack.settings.template' => 'canvasign']);
        $canvasignBefore = ThemeAdapterResolver::resolve();

        config(['canvastack.settings.template' => 'canvas']);
        $canvasBefore = ThemeAdapterResolver::resolve();

        // Reset clears all caches
        ThemeAdapterResolver::reset();

        // New instances must be created after reset
        config(['canvastack.settings.template' => 'default']);
        $defaultAfter = ThemeAdapterResolver::resolve();

        config(['canvastack.settings.template' => 'canvasign']);
        $canvasignAfter = ThemeAdapterResolver::resolve();

        config(['canvastack.settings.template' => 'canvas']);
        $canvasAfter = ThemeAdapterResolver::resolve();

        $this->assertNotSame(
            $defaultBefore,
            $defaultAfter,
            "reset() must clear the 'default' cached instance"
        );

        $this->assertNotSame(
            $canvasignBefore,
            $canvasignAfter,
            "reset() must clear the 'canvasign' cached instance"
        );

        $this->assertNotSame(
            $canvasBefore,
            $canvasAfter,
            "reset() must clear the 'canvas' cached instance"
        );
    }

    /**
     * @test
     * Requirement 3.7: After reset(), resolve() still returns the correct adapter
     *                  types (registry is preserved, only instances are cleared).
     */
    public function test_reset_preserves_registry_mappings(): void
    {
        // Cache and then reset
        $this->setTemplate('canvasign');
        ThemeAdapterResolver::resolve();
        ThemeAdapterResolver::reset();

        // Registry should still map 'canvasign' → Bootstrap5Adapter
        config(['canvastack.settings.template' => 'canvasign']);
        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            Bootstrap5Adapter::class,
            $adapter,
            'reset() must preserve registry mappings — only instances are cleared'
        );
    }

    /**
     * @test
     * Requirement 3.7: reset() is idempotent — calling it multiple times has no
     *                  adverse effect.
     */
    public function test_reset_is_idempotent(): void
    {
        ThemeAdapterResolver::reset();
        ThemeAdapterResolver::reset();
        ThemeAdapterResolver::reset();

        $this->setTemplate('default');
        $adapter = ThemeAdapterResolver::resolve();

        $this->assertInstanceOf(
            DefaultAdapter::class,
            $adapter,
            'reset() must be idempotent — multiple calls must not break resolve()'
        );
    }

    // ── Correct adapter per template (unit-level spot checks) ────────────

    /**
     * @test
     * Requirement 3.2: resolve() returns DefaultAdapter for template 'default'.
     */
    public function test_resolve_returns_default_adapter_for_default_template(): void
    {
        $this->setTemplate('default');
        $this->assertInstanceOf(DefaultAdapter::class, ThemeAdapterResolver::resolve());
    }

    /**
     * @test
     * Requirement 3.3: resolve() returns Bootstrap5Adapter for template 'canvasign'.
     */
    public function test_resolve_returns_bootstrap5_adapter_for_canvasign_template(): void
    {
        $this->setTemplate('canvasign');
        $this->assertInstanceOf(Bootstrap5Adapter::class, ThemeAdapterResolver::resolve());
    }

    /**
     * @test
     * Requirement 3.4: resolve() returns TailwindAdapter for template 'canvas'.
     */
    public function test_resolve_returns_tailwind_adapter_for_canvas_template(): void
    {
        $this->setTemplate('canvas');
        $this->assertInstanceOf(TailwindAdapter::class, ThemeAdapterResolver::resolve());
    }

    /**
     * @test
     * Requirement 3.5: resolve() returns DefaultAdapter for an unregistered template.
     */
    public function test_resolve_returns_default_adapter_for_unregistered_template(): void
    {
        $this->setTemplate('completely_unknown_template_xyz');
        $this->assertInstanceOf(DefaultAdapter::class, ThemeAdapterResolver::resolve());
    }
}
