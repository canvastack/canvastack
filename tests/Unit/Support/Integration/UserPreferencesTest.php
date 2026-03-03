<?php

namespace Canvastack\Canvastack\Tests\Unit\Support\Integration;

use Canvastack\Canvastack\Support\Integration\UserPreferences;
use Canvastack\Canvastack\Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    protected UserPreferences $preferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preferences = new UserPreferences('session');
    }

    public function test_get_all_preferences()
    {
        $all = $this->preferences->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('theme', $all);
        $this->assertArrayHasKey('locale', $all);
        $this->assertArrayHasKey('dark_mode', $all);
    }

    public function test_get_preference()
    {
        $theme = $this->preferences->get('theme');

        $this->assertNotNull($theme);
        $this->assertIsString($theme);
    }

    public function test_get_preference_with_default()
    {
        $value = $this->preferences->get('nonexistent', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function test_set_preference()
    {
        $this->preferences->set('theme', 'custom');

        $this->assertEquals('custom', $this->preferences->get('theme'));
    }

    public function test_set_many_preferences()
    {
        $this->preferences->setMany([
            'theme' => 'custom',
            'locale' => 'id',
        ]);

        $this->assertEquals('custom', $this->preferences->get('theme'));
        $this->assertEquals('id', $this->preferences->get('locale'));
    }

    public function test_has_preference()
    {
        $this->assertTrue($this->preferences->has('theme'));
        $this->assertFalse($this->preferences->has('nonexistent'));
    }

    public function test_forget_preference()
    {
        $this->preferences->set('custom_key', 'value');
        $this->assertTrue($this->preferences->has('custom_key'));

        $this->preferences->forget('custom_key');
        $this->assertFalse($this->preferences->has('custom_key'));
    }

    public function test_clear_preferences()
    {
        $this->preferences->set('custom_key', 'value');

        $this->preferences->clear();

        $all = $this->preferences->all();
        $this->assertArrayHasKey('theme', $all); // Defaults are still there
        $this->assertFalse($this->preferences->has('custom_key'));
    }

    public function test_reset_preferences()
    {
        $this->preferences->set('theme', 'custom');

        $this->preferences->reset();

        $theme = $this->preferences->get('theme');
        $this->assertNotEquals('custom', $theme);
    }

    public function test_get_theme()
    {
        $theme = $this->preferences->getTheme();

        $this->assertNotNull($theme);
        $this->assertIsString($theme);
    }

    public function test_set_theme()
    {
        $this->preferences->setTheme('custom');

        $this->assertEquals('custom', $this->preferences->getTheme());
    }

    public function test_get_locale()
    {
        $locale = $this->preferences->getLocale();

        $this->assertNotNull($locale);
        $this->assertIsString($locale);
    }

    public function test_set_locale()
    {
        $this->preferences->setLocale('id');

        $this->assertEquals('id', $this->preferences->getLocale());
    }

    public function test_get_dark_mode()
    {
        $darkMode = $this->preferences->getDarkMode();

        $this->assertIsBool($darkMode);
    }

    public function test_set_dark_mode()
    {
        $this->preferences->setDarkMode(true);

        $this->assertTrue($this->preferences->getDarkMode());

        $this->preferences->setDarkMode(false);

        $this->assertFalse($this->preferences->getDarkMode());
    }

    public function test_export_preferences()
    {
        $exported = $this->preferences->export();

        $this->assertIsArray($exported);
        $this->assertArrayHasKey('preferences', $exported);
        $this->assertArrayHasKey('exported_at', $exported);

        $this->assertIsArray($exported['preferences']);
        $this->assertNotEmpty($exported['exported_at']);
    }

    public function test_import_preferences()
    {
        $data = [
            'preferences' => [
                'theme' => 'imported',
                'locale' => 'ar',
            ],
        ];

        $this->preferences->import($data);

        $this->assertEquals('imported', $this->preferences->getTheme());
        $this->assertEquals('ar', $this->preferences->getLocale());
    }

    public function test_get_driver()
    {
        $driver = $this->preferences->getDriver();

        $this->assertEquals('session', $driver);
    }

    public function test_set_driver()
    {
        $this->preferences->setDriver('cookie');

        $this->assertEquals('cookie', $this->preferences->getDriver());
    }

    public function test_get_cookie_name()
    {
        $name = $this->preferences->getCookieName();

        $this->assertEquals('canvastack_preferences', $name);
    }

    public function test_set_cookie_name()
    {
        $this->preferences->setCookieName('custom_preferences');

        $this->assertEquals('custom_preferences', $this->preferences->getCookieName());
    }

    public function test_get_session_key()
    {
        $key = $this->preferences->getSessionKey();

        $this->assertEquals('canvastack_preferences', $key);
    }

    public function test_set_session_key()
    {
        $this->preferences->setSessionKey('custom_preferences');

        $this->assertEquals('custom_preferences', $this->preferences->getSessionKey());
    }

    public function test_preferences_persist_across_instances()
    {
        $this->preferences->setTheme('persistent');

        $newInstance = new UserPreferences('session');

        $this->assertEquals('persistent', $newInstance->getTheme());
    }

    public function test_nested_preferences()
    {
        $this->preferences->set('ui.sidebar.collapsed', true);

        $this->assertTrue($this->preferences->get('ui.sidebar.collapsed'));
        $this->assertTrue($this->preferences->has('ui.sidebar.collapsed'));
    }
}
