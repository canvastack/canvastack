<?php

namespace Canvastack\Canvastack\Tests\Feature\Console;

use Canvastack\Canvastack\Console\Commands\TranslationCoverageCommand;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\File;

/**
 * TranslationCoverageCommandTest.
 *
 * Unit tests for TranslationCoverageCommand.
 */
class TranslationCoverageCommandTest extends FeatureTestCase
{
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
        // This must be set BEFORE the service provider registers the loader
        $app['config']->set('canvastack.localization.paths', [$app->langPath()]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create test translations first
        $this->createTestTranslations();

        // Ensure storage directory exists
        $storageDir = storage_path('app/translations');
        if (!File::exists($storageDir)) {
            File::makeDirectory($storageDir, 0755, true);
        }

        // Get the loader instance
        $this->loader = app(TranslationLoader::class);

        // Clear Laravel's translation cache
        \Illuminate\Support\Facades\Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clean up test translations
        $this->cleanupTestTranslations();

        parent::tearDown();
    }

    protected function createTestTranslations(): void
    {
        // Create English (base) translations
        $enPath = lang_path('en');
        if (!File::exists($enPath)) {
            File::makeDirectory($enPath, 0755, true);
        }

        File::put($enPath . '/coverage.php', "<?php\n\nreturn [\n    'key1' => 'Value 1',\n    'key2' => 'Value 2',\n    'key3' => 'Value 3',\n];");

        // Create Indonesian translations (incomplete - missing key3)
        $idPath = lang_path('id');
        if (!File::exists($idPath)) {
            File::makeDirectory($idPath, 0755, true);
        }

        File::put($idPath . '/coverage.php', "<?php\n\nreturn [\n    'key1' => 'Nilai 1',\n    'key2' => 'Nilai 2',\n];");
    }

    protected function cleanupTestTranslations(): void
    {
        $files = [
            lang_path('en/coverage.php'),
            lang_path('en/nested.php'),
            lang_path('id/coverage.php'),
            lang_path('id/nested.php'),
            lang_path('es/coverage.php'),
        ];

        foreach ($files as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up empty directories
        $dirs = [lang_path('es')];
        foreach ($dirs as $dir) {
            if (File::isDirectory($dir) && count(File::files($dir)) === 0) {
                File::deleteDirectory($dir);
            }
        }
    }

    /** @test */
    public function it_can_generate_coverage_report()
    {
        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--base' => 'en',
            '--threshold' => 60, // ID has 66.67% coverage (2/3 keys)
        ])->assertExitCode(0);
    }

    /** @test */
    public function it_displays_coverage_statistics()
    {
        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--threshold' => 60,
        ])
            ->expectsOutput('Generating translation coverage report...')
            ->expectsOutput('Translation Coverage Report')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_export_to_json()
    {
        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 60,
        ])->assertExitCode(0);

        // Assert file exists
        $this->assertTrue(File::exists($jsonPath));

        // Assert JSON structure
        $data = json_decode(File::get($jsonPath), true);
        $this->assertArrayHasKey('generated_at', $data);
        $this->assertArrayHasKey('base_locale', $data);
        $this->assertArrayHasKey('locales', $data);
        $this->assertArrayHasKey('summary', $data);

        // Clean up
        File::delete($jsonPath);
    }

    /** @test */
    public function it_can_export_to_html()
    {
        $htmlPath = storage_path('app/translations/coverage.html');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--format' => 'html',
            '--output' => $htmlPath,
            '--threshold' => 60,
        ])->assertExitCode(0);

        // Assert file exists
        $this->assertTrue(File::exists($htmlPath));

        // Assert HTML content
        $content = File::get($htmlPath);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('Translation Coverage Report', $content);

        // Clean up
        File::delete($htmlPath);
    }

    /** @test */
    public function it_calculates_coverage_percentage()
    {
        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 60,
        ])->assertExitCode(0);

        $data = json_decode(File::get($jsonPath), true);

        // Debug: dump the data
        if (!isset($data['locales']['id'])) {
            $this->fail('ID locale not found in report. Data: ' . json_encode($data));
        }

        $localeData = $data['locales']['id'];
        $coverage = $localeData['coverage'];

        // Debug: show what we got
        $debugInfo = [
            'total' => $localeData['total'],
            'translated' => $localeData['translated'],
            'missing' => $localeData['missing'],
            'coverage' => $coverage,
            'missing_keys' => $localeData['missing_keys'] ?? [],
        ];

        if ($coverage >= 70) {
            $this->fail("Coverage should be ~66.67%, got {$coverage}%. Debug: " . json_encode($debugInfo));
        }

        // 2 out of 3 keys = 66.67%
        $this->assertGreaterThan(60, $coverage);
        $this->assertLessThan(70, $coverage);

        // Clean up
        File::delete($jsonPath);
    }

    /** @test */
    public function it_identifies_missing_keys()
    {
        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 60,
        ])->assertExitCode(0);

        $data = json_decode(File::get($jsonPath), true);
        $missingKeys = $data['locales']['id']['missing_keys'];

        $this->assertContains('coverage.key3', $missingKeys);

        // Clean up
        File::delete($jsonPath);
    }

    /** @test */
    public function it_can_check_multiple_locales()
    {
        // Create Spanish translations
        $esPath = lang_path('es');
        if (!File::exists($esPath)) {
            File::makeDirectory($esPath, 0755, true);
        }

        File::put($esPath . '/coverage.php', "<?php\n\nreturn [\n    'key1' => 'Valor 1',\n];");

        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 0, // ES has only 33% coverage
        ])->assertExitCode(0);

        $data = json_decode(File::get($jsonPath), true);
        $this->assertArrayHasKey('id', $data['locales']);
        $this->assertArrayHasKey('es', $data['locales']);

        // Clean up
        File::delete($esPath . '/coverage.php');
        File::delete($jsonPath);
    }

    /** @test */
    public function it_calculates_average_coverage()
    {
        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 60,
        ])->assertExitCode(0);

        $data = json_decode(File::get($jsonPath), true);
        $averageCoverage = $data['summary']['average_coverage'];

        $this->assertIsNumeric($averageCoverage);
        $this->assertGreaterThanOrEqual(0, $averageCoverage);
        $this->assertLessThanOrEqual(100, $averageCoverage);

        // Clean up
        File::delete($jsonPath);
    }

    /** @test */
    public function it_fails_when_coverage_below_threshold()
    {
        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--threshold' => 90, // Indonesian has ~66% coverage
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_passes_when_coverage_meets_threshold()
    {
        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--threshold' => 60, // Indonesian has ~66% coverage
        ])->assertExitCode(0);
    }

    /** @test */
    public function it_handles_empty_translations()
    {
        // Create locale with empty translation
        $esPath = lang_path('es');
        if (!File::exists($esPath)) {
            File::makeDirectory($esPath, 0755, true);
        }

        File::put($esPath . '/coverage.php', "<?php\n\nreturn [\n    'key1' => '',\n    'key2' => 'Value',\n];");

        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'es',
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 0, // ES has only 33% coverage
        ])->assertExitCode(0);

        $data = json_decode(File::get($jsonPath), true);
        $emptyKeys = $data['locales']['es']['empty_keys'];

        $this->assertContains('coverage.key1', $emptyKeys);

        // Clean up
        File::delete($esPath . '/coverage.php');
        File::delete($jsonPath);
    }

    /** @test */
    public function it_fails_when_no_locales_found()
    {
        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'nonexistent',
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_displays_table_format_by_default()
    {
        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--threshold' => 60,
        ])
            ->expectsOutput('Translation Coverage Report')
            ->expectsOutput('Summary:')
            ->expectsOutput('Coverage by Locale:')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_nested_translations()
    {
        // Create nested translations
        $enPath = lang_path('en');
        File::put($enPath . '/nested.php', "<?php\n\nreturn [\n    'level1' => [\n        'level2' => 'Value',\n    ],\n];");

        $idPath = lang_path('id');
        File::put($idPath . '/nested.php', "<?php\n\nreturn [\n    'level1' => [\n        // level2 missing\n    ],\n];");

        $jsonPath = storage_path('app/translations/coverage.json');

        $this->artisan('canvastack:translate:coverage', [
            '--locale' => 'id',
            '--format' => 'json',
            '--output' => $jsonPath,
            '--threshold' => 0, // Allow low coverage for nested test
        ])->assertExitCode(0);

        $data = json_decode(File::get($jsonPath), true);
        $missingKeys = $data['locales']['id']['missing_keys'];

        $this->assertContains('nested.level1.level2', $missingKeys);

        // Clean up
        File::delete($enPath . '/nested.php');
        File::delete($idPath . '/nested.php');
        File::delete($jsonPath);
    }
}
