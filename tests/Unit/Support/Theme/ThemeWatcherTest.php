<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Support\Theme\ThemeRepository;
use Canvastack\Canvastack\Support\Theme\ThemeWatcher;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ThemeWatcherTest extends TestCase
{
    protected Filesystem $files;

    protected ThemeManager $manager;

    protected ThemeWatcher $watcher;

    protected string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->tempPath = sys_get_temp_dir() . '/canvastack-test-themes-' . uniqid();
        $this->files->makeDirectory($this->tempPath);

        $repository = new ThemeRepository();
        $loader = new ThemeLoader($this->tempPath, $this->files);
        $this->manager = new ThemeManager($repository, $loader);
        $this->watcher = new ThemeWatcher($this->files, $this->manager, $this->tempPath, false);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->tempPath)) {
            $this->files->deleteDirectory($this->tempPath);
        }

        parent::tearDown();
    }

    public function test_can_enable_and_disable_watcher(): void
    {
        $this->watcher->disable();
        $this->assertFalse($this->watcher->isEnabled());

        $this->watcher->enable();
        $this->assertTrue($this->watcher->isEnabled());
    }

    public function test_can_set_and_get_base_path(): void
    {
        $newPath = '/new/path';
        $this->watcher->setBasePath($newPath);

        $this->assertEquals($newPath, $this->watcher->getBasePath());
    }

    public function test_returns_false_when_disabled(): void
    {
        $this->watcher->disable();

        $this->assertFalse($this->watcher->hasChanges());
    }

    public function test_detects_no_changes_initially(): void
    {
        $this->watcher->enable();
        $this->createThemeFile('test-theme', ['name' => 'test']);

        // First check initializes modification times
        $this->assertFalse($this->watcher->hasChanges());
    }

    public function test_detects_file_modifications(): void
    {
        $this->watcher->enable();
        $themePath = $this->createThemeFile('test-theme', ['name' => 'test']);

        // Initialize modification times
        $this->watcher->hasChanges();

        // Wait a moment and modify file
        sleep(1);
        $this->files->put($themePath, json_encode(['name' => 'modified']));

        // Should detect change
        $this->assertTrue($this->watcher->hasChanges());
    }

    public function test_reload_if_changed_returns_false_when_no_changes(): void
    {
        $this->watcher->enable();
        $this->createThemeFile('test-theme', ['name' => 'test']);

        // Initialize
        $this->watcher->hasChanges();

        $this->assertFalse($this->watcher->reloadIfChanged());
    }

    public function test_can_clear_modification_times(): void
    {
        $this->watcher->enable();
        $this->createThemeFile('test-theme', ['name' => 'test']);

        $this->watcher->hasChanges(); // Initialize times

        $times = $this->watcher->getModificationTimes();
        $this->assertNotEmpty($times);

        $this->watcher->clearModificationTimes();
        $this->assertEmpty($this->watcher->getModificationTimes());
    }

    public function test_handles_non_existent_directory(): void
    {
        $watcher = new ThemeWatcher(
            $this->files,
            $this->manager,
            '/non/existent/path',
            false
        );
        $watcher->enable();

        $this->assertFalse($watcher->hasChanges());
    }

    public function test_watches_both_json_and_php_files(): void
    {
        $this->watcher->enable();

        // Create both JSON and PHP theme files
        $this->createThemeFile('theme1', ['name' => 'theme1'], 'json');
        $this->createThemeFile('theme2', ['name' => 'theme2'], 'php');

        // Initialize
        $this->watcher->hasChanges();

        $times = $this->watcher->getModificationTimes();
        $this->assertCount(2, $times);
    }

    protected function createThemeFile(string $name, array $config, string $type = 'json'): string
    {
        $themePath = "{$this->tempPath}/{$name}";
        $this->files->makeDirectory($themePath, 0755, true, true);

        $filePath = "{$themePath}/theme.{$type}";

        if ($type === 'json') {
            $this->files->put($filePath, json_encode($config));
        } else {
            $this->files->put($filePath, '<?php return ' . var_export($config, true) . ';');
        }

        return $filePath;
    }
}
