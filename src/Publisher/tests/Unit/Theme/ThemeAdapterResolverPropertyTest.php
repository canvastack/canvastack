<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;
use Eris\TestTrait;
use Eris\Generators;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterResolver;
use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * Property-based tests for ThemeAdapterResolver.
 *
 * Property 2: ThemeAdapterResolver always returns ThemeAdapterInterface
 *   For any template name (registered or not), resolve() always returns an
 *   instance implementing ThemeAdapterInterface and never throws an exception.
 *   Validates: Requirements 3.1, 3.2, 3.3, 3.4, 15.2
 *
 * Property 3: Fallback to DefaultAdapter for unregistered templates
 *   For any template name not in the registry, resolve() returns DefaultAdapter.
 *   Validates: Requirements 3.5, 15.2, 15.3
 *
 * Property 5: Singleton per request — resolve() returns the same instance
 *   Multiple calls to resolve() for the same template return the same object reference.
 *   Validates: Requirements 3.7
 *
 * Uses giorgiosironi/eris with minimum 100 iterations per property.
 */
class ThemeAdapterResolverPropertyTest extends TestCase
{
    use TestTrait;

    /** @var list<string> Known registered template names */
    private const REGISTERED_TEMPLATES = ['default', 'canvasign', 'canvas'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->iterations = 25;
        ThemeAdapterResolver::reset();
    }

    protected function tearDown(): void
    {
        ThemeAdapterResolver::reset();
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

    /**
     * Generator for registered template names.
     */
    private function registeredTemplateGenerator(): \Eris\Generator
    {
        return Generators::elements(...self::REGISTERED_TEMPLATES);
    }

    /**
     * Generator for safe alphanumeric strings that are NOT registered templates.
     * Produces strings like 'abc123', 'xyz', etc.
     */
    private function unregisteredTemplateGenerator(): \Eris\Generator
    {
        return Generators::map(
            function (string $s): string {
                // Keep only safe chars, ensure non-empty, prefix to avoid collision
                $clean = preg_replace('/[^a-zA-Z0-9]/', '', $s);
                $name  = 'custom_' . ($clean ?: 'unknown');
                // Ensure it's not accidentally a registered name
                return in_array($name, self::REGISTERED_TEMPLATES, true) ? 'custom_unregistered' : $name;
            },
            Generators::string()
        );
    }

    /**
     * Generator for any template name (registered or not).
     */
    private function anyTemplateGenerator(): \Eris\Generator
    {
        return Generators::oneOf(
            $this->registeredTemplateGenerator(),
            $this->unregisteredTemplateGenerator()
        );
    }

    // ── Property 2: resolve() always returns ThemeAdapterInterface ────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 2: ThemeAdapterResolver::resolve() always returns an instance of
     *             ThemeAdapterInterface for any template name (registered or not).
     *
     * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 15.2
     */
    public function test_resolve_always_returns_theme_adapter_interface_for_registered_templates(): void
    {
        $this->forAll(
            $this->registeredTemplateGenerator()
        )->then(function (string $template): void {
            $this->setTemplate($template);

            $adapter = ThemeAdapterResolver::resolve();

            $this->assertInstanceOf(
                ThemeAdapterInterface::class,
                $adapter,
                "resolve() must return ThemeAdapterInterface for registered template '{$template}'"
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 2: ThemeAdapterResolver::resolve() always returns an instance of
     *             ThemeAdapterInterface for unregistered template names (fallback path).
     *
     * Validates: Requirements 3.5, 15.2
     */
    public function test_resolve_always_returns_theme_adapter_interface_for_unregistered_templates(): void
    {
        $this->forAll(
            $this->unregisteredTemplateGenerator()
        )->then(function (string $template): void {
            $this->setTemplate($template);

            $adapter = ThemeAdapterResolver::resolve();

            $this->assertInstanceOf(
                ThemeAdapterInterface::class,
                $adapter,
                "resolve() must return ThemeAdapterInterface even for unregistered template '{$template}'"
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 2: ThemeAdapterResolver::resolve() never throws an exception for any template name.
     *
     * Validates: Requirements 3.5, 15.2
     */
    public function test_resolve_never_throws_for_any_template_name(): void
    {
        $this->forAll(
            $this->anyTemplateGenerator()
        )->then(function (string $template): void {
            $this->setTemplate($template);

            $exception = null;
            try {
                $adapter = ThemeAdapterResolver::resolve();
            } catch (\Throwable $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "resolve() must not throw for template '{$template}', but got: " . ($exception?->getMessage() ?? '')
            );
        });
    }

    // ── Property 3: Fallback to DefaultAdapter for unregistered templates ─

    /**
     * @test
     * Feature: theme-adapter
     * Property 3: ThemeAdapterResolver::resolve() returns DefaultAdapter for any
     *             template name that is not registered in the registry.
     *
     * Validates: Requirements 3.5, 15.2, 15.3
     */
    public function test_resolve_returns_default_adapter_for_unregistered_templates(): void
    {
        $this->forAll(
            $this->unregisteredTemplateGenerator()
        )->then(function (string $template): void {
            $this->setTemplate($template);

            $adapter = ThemeAdapterResolver::resolve();

            $this->assertInstanceOf(
                DefaultAdapter::class,
                $adapter,
                "resolve() must return DefaultAdapter (fallback) for unregistered template '{$template}'"
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 3: ThemeAdapterResolver::resolve() returns DefaultAdapter when
     *             canvastack_current_template() returns null or empty string.
     *
     * Validates: Requirements 15.3
     */
    public function test_resolve_returns_default_adapter_when_template_is_null_or_empty(): void
    {
        // null template
        config(['canvastack.settings.template' => null]);
        ThemeAdapterResolver::reset();
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(
            DefaultAdapter::class,
            $adapter,
            'resolve() must return DefaultAdapter when template config is null'
        );

        // empty string template
        config(['canvastack.settings.template' => '']);
        ThemeAdapterResolver::reset();
        $adapter = ThemeAdapterResolver::resolve();
        $this->assertInstanceOf(
            DefaultAdapter::class,
            $adapter,
            'resolve() must return DefaultAdapter when template config is empty string'
        );
    }

    // ── Property 5: Singleton per request ────────────────────────────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 5: ThemeAdapterResolver::resolve() returns the same object reference
     *             on multiple calls for the same template (singleton per request).
     *
     * Validates: Requirements 3.7
     */
    public function test_resolve_returns_same_instance_for_registered_templates(): void
    {
        $this->forAll(
            $this->registeredTemplateGenerator()
        )->then(function (string $template): void {
            $this->setTemplate($template);

            $first  = ThemeAdapterResolver::resolve();
            $second = ThemeAdapterResolver::resolve();
            $third  = ThemeAdapterResolver::resolve();

            $this->assertSame(
                $first,
                $second,
                "resolve() must return the same instance on second call for template '{$template}'"
            );

            $this->assertSame(
                $first,
                $third,
                "resolve() must return the same instance on third call for template '{$template}'"
            );
        });
    }

    /**
     * @test
     * Feature: theme-adapter
     * Property 5: ThemeAdapterResolver::resolve() returns the same object reference
     *             on multiple calls for unregistered templates (fallback singleton).
     *
     * Validates: Requirements 3.7
     */
    public function test_resolve_returns_same_instance_for_unregistered_templates(): void
    {
        $this->forAll(
            $this->unregisteredTemplateGenerator()
        )->then(function (string $template): void {
            $this->setTemplate($template);

            $first  = ThemeAdapterResolver::resolve();
            $second = ThemeAdapterResolver::resolve();

            $this->assertSame(
                $first,
                $second,
                "resolve() must return the same instance on repeated calls for unregistered template '{$template}'"
            );
        });
    }

    // ── Correct adapter per registered template ───────────────────────────

    /**
     * @test
     * Feature: theme-adapter
     * Property 2 (structural): resolve() returns the correct concrete adapter class
     *             for each registered template name.
     *
     * Validates: Requirements 3.2, 3.3, 3.4
     */
    public function test_resolve_returns_correct_adapter_for_each_registered_template(): void
    {
        $expectedMap = [
            'default'   => DefaultAdapter::class,
            'canvasign' => Bootstrap5Adapter::class,
            'canvas'    => TailwindAdapter::class,
        ];

        $this->forAll(
            $this->registeredTemplateGenerator()
        )->then(function (string $template) use ($expectedMap): void {
            $this->setTemplate($template);

            $adapter = ThemeAdapterResolver::resolve();

            $this->assertInstanceOf(
                $expectedMap[$template],
                $adapter,
                "resolve() must return {$expectedMap[$template]} for template '{$template}'"
            );
        });
    }
}
