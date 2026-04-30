<?php

namespace Canvastack\Canvastack\Library\Theme;

use Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter;
use Canvastack\Canvastack\Library\Theme\Adapters\Bootstrap5Adapter;
use Canvastack\Canvastack\Library\Theme\Adapters\TailwindAdapter;

/**
 * ThemeAdapterResolver — Resolves the correct ThemeAdapter for the active template.
 *
 * Maintains a static registry mapping template names to adapter classes, and
 * caches resolved instances per template (singleton per request).
 *
 * Default registry:
 *   'default'   → DefaultAdapter   (Bootstrap 4)
 *   'canvasign' → Bootstrap5Adapter (Bootstrap 5)
 *   'canvas'    → TailwindAdapter   (TailwindCSS)
 *
 * Usage:
 *   $adapter = ThemeAdapterResolver::resolve();
 *   $html    = $adapter->renderTabHeader($data, $pointer, $active, $class);
 *
 * @package    Canvastack\Canvastack\Library\Theme
 * @author     wisnuwidi@canvastack.com
 * @copyright  Canvastack
 * @see        ThemeAdapterInterface
 */
class ThemeAdapterResolver
{
    /**
     * Map of template name → fully-qualified adapter class name.
     *
     * @var array<string, string>
     */
    private static array $registry = [
        'default'   => DefaultAdapter::class,
        'canvasign' => Bootstrap5Adapter::class,
        'canvas'    => TailwindAdapter::class,
    ];

    /**
     * Cached adapter instances, keyed by template name.
     *
     * @var array<string, ThemeAdapterInterface>
     */
    private static array $instances = [];

    /**
     * Resolve the adapter for the currently active template.
     *
     * Calls `canvastack_current_template()` to determine the active template,
     * returns a cached instance if one already exists, otherwise instantiates
     * the registered adapter class. Falls back to DefaultAdapter when the
     * active template is not registered.
     *
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.7, 15.2, 15.3
     *
     * @return ThemeAdapterInterface
     */
    public static function resolve(): ThemeAdapterInterface
    {
        $template = canvastack_current_template() ?: 'default';

        if (!isset(self::$instances[$template])) {
            $adapterClass = self::$registry[$template] ?? self::$registry['default'];
            self::$instances[$template] = new $adapterClass();
        }

        return self::$instances[$template];
    }

    /**
     * Register a new adapter class for a given template name.
     *
     * Allows third-party packages to extend the ThemeAdapter system with
     * custom adapters without modifying framework code.
     *
     * If an instance for the given template is already cached, it is
     * invalidated so the next call to `resolve()` creates a fresh instance
     * using the newly registered class.
     *
     * Requirements: 3.6
     *
     * @param string $templateName  The template identifier (e.g. 'mytheme').
     * @param string $adapterClass  Fully-qualified class name that implements ThemeAdapterInterface.
     *
     * @throws \InvalidArgumentException If $adapterClass does not implement ThemeAdapterInterface.
     */
    public static function register(string $templateName, string $adapterClass): void
    {
        if (!is_a($adapterClass, ThemeAdapterInterface::class, true)) {
            throw new \InvalidArgumentException(
                "{$adapterClass} must implement ThemeAdapterInterface"
            );
        }

        self::$registry[$templateName] = $adapterClass;

        // Invalidate cached instance so the new class is used on next resolve()
        unset(self::$instances[$templateName]);
    }

    /**
     * Reset all cached adapter instances.
     *
     * Intended for use in tests to ensure a clean state between test cases.
     * In normal production usage this method should not be called, as PHP
     * discards static state between requests automatically.
     *
     * Requirements: 3.7 (testing support)
     */
    public static function reset(): void
    {
        self::$instances = [];
    }

    /**
     * Reset both cached instances AND the registry to the default mappings.
     *
     * Use this in tests that call register() to mutate the registry, so that
     * subsequent tests start from a clean state.
     *
     * Requirements: 3.7 (testing support)
     */
    public static function resetAll(): void
    {
        self::$instances = [];
        self::$registry  = [
            'default'   => DefaultAdapter::class,
            'canvasign' => Bootstrap5Adapter::class,
            'canvas'    => TailwindAdapter::class,
        ];
    }
}
