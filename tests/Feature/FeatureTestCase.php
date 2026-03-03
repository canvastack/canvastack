<?php

namespace Canvastack\Canvastack\Tests\Feature;

use Canvastack\Canvastack\CanvastackServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * FeatureTestCase.
 *
 * Base test case for feature tests using Orchestra Testbench.
 * Provides full Laravel application context for integration testing.
 */
abstract class FeatureTestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CanvastackServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Set encryption key for testing
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // Setup default configuration
        $app['config']->set('canvastack.i18n', [
            'default_locale' => 'en',
            'fallback_locale' => 'en',
            'available_locales' => ['en', 'id', 'ar', 'he'],
            'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
        ]);

        $app['config']->set('canvastack.localization', [
            'default_locale' => 'en',
            'fallback_locale' => 'en',
            'available_locales' => [
                'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
                'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
            ],
            'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
            'storage' => 'session',
            'detect_browser' => false,
        ]);
    }

    /**
     * Get base path for the package.
     *
     * @return string
     */
    protected function getBasePath(): string
    {
        return __DIR__ . '/../..';
    }
}
