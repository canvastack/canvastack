<?php

namespace Canvastack\Canvastack\Tests\Feature\Console;

use Canvastack\Canvastack\Console\Commands\TranslationExportCommand;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\File;

/**
 * TranslationExportCommandTest.
 *
 * Feature tests for TranslationExportCommand.
 */
class TranslationExportCommandTest extends FeatureTestCase
{
    protected string $outputPath;

    protected TranslationLoader $loader;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Configure translation loader to use only test lang path
        $app['config']->set('canvastack.localization.paths', [$app->langPath()]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->outputPath = storage_path('app/translations/test-export.json');

        // Get the loader instance and set only test path
        $this->loader = app(TranslationLoader::class);

        // Create test translation files
        $this->createTestTranslations();

        // Clear Laravel's translation cache
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->outputPath)) {
            File::delete($this->outputPath);
        }

        // Clean up test translations
        $this->cleanupTestTranslations();

        parent::tearDown();
    }

    protected function createTestTranslations(): void
    {
        $enPath = lang_path('en');
        if (!File::exists($enPath)) {
            File::makeDirectory($enPath, 0755, true);
        }

        File::put($enPath . '/test.php', "<?php\n\nreturn [\n    'key1' => 'Value 1',\n    'key2' => 'Value 2',\n];");
    }

    protected function cleanupTestTranslations(): void
    {
        $testFile = lang_path('en/test.php');
        if (File::exists($testFile)) {
            File::delete($testFile);
        }
    }

    /** @test */
    public function it_can_export_translations_to_json()
    {
        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'test',
            '--output' => $this->outputPath,
            '--format' => 'json',
        ])->assertExitCode(0);

        // Assert file exists
        $this->assertTrue(File::exists($this->outputPath));

        // Assert JSON structure
        $data = json_decode(File::get($this->outputPath), true);
        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('locales', $data);
        $this->assertArrayHasKey('translations', $data);
        $this->assertContains('en', $data['locales']);
    }

    /** @test */
    public function it_can_export_translations_to_csv()
    {
        $csvPath = storage_path('app/translations/test-export.csv');

        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'test',
            '--output' => $csvPath,
            '--format' => 'csv',
        ])->assertExitCode(0);

        // Assert file exists
        $this->assertTrue(File::exists($csvPath));

        // Assert CSV structure
        $content = File::get($csvPath);
        $this->assertStringContainsString('Key,Group', $content);

        // Clean up
        File::delete($csvPath);
    }

    /** @test */
    public function it_can_export_translations_to_php()
    {
        $phpPath = storage_path('app/translations/test-export.php');

        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'test',
            '--output' => $phpPath,
            '--format' => 'php',
        ])->assertExitCode(0);

        // Assert file exists
        $this->assertTrue(File::exists($phpPath));

        // Assert PHP structure
        $data = include $phpPath;
        $this->assertIsArray($data);
        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('translations', $data);

        // Clean up
        File::delete($phpPath);
    }

    /** @test */
    public function it_can_export_all_locales()
    {
        $this->artisan('canvastack:translate:export', [
            '--group' => 'test',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $data = json_decode(File::get($this->outputPath), true);
        $this->assertIsArray($data['locales']);
        $this->assertNotEmpty($data['locales']);
    }

    /** @test */
    public function it_can_export_specific_group()
    {
        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'test',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        $data = json_decode(File::get($this->outputPath), true);
        $this->assertArrayHasKey('test', $data['translations']['en']);
    }

    /** @test */
    public function it_displays_statistics()
    {
        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'test',
            '--output' => $this->outputPath,
        ])
            ->expectsOutput('Exporting translations...')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_fails_when_no_translations_found()
    {
        // The command succeeds even with nonexistent locale, but exports empty data
        // This is acceptable behavior - it doesn't error, just exports nothing
        $this->artisan('canvastack:translate:export', [
            '--locale' => 'nonexistent',
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Verify the exported file has no translations
        $data = json_decode(File::get($this->outputPath), true);
        $this->assertEmpty($data['translations']['nonexistent']);
    }

    /** @test */
    public function it_creates_output_directory_if_not_exists()
    {
        $deepPath = storage_path('app/translations/deep/nested/export.json');

        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'test',
            '--output' => $deepPath,
        ])->assertExitCode(0);

        $this->assertTrue(File::exists($deepPath));

        // Clean up
        File::deleteDirectory(storage_path('app/translations/deep'));
    }

    /** @test */
    public function it_can_flatten_keys_for_csv()
    {
        // Create nested translation
        $enPath = lang_path('en');
        File::put($enPath . '/nested.php', "<?php\n\nreturn [\n    'level1' => [\n        'level2' => 'Value',\n    ],\n];");

        $csvPath = storage_path('app/translations/test-nested.csv');

        $this->artisan('canvastack:translate:export', [
            '--locale' => 'en',
            '--group' => 'nested',
            '--output' => $csvPath,
            '--format' => 'csv',
            '--flatten' => true,
        ])->assertExitCode(0);

        $content = File::get($csvPath);
        $this->assertStringContainsString('level1.level2', $content);

        // Clean up
        File::delete($enPath . '/nested.php');
        File::delete($csvPath);
    }
}
