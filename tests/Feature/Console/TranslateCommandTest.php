<?php

namespace Canvastack\Canvastack\Tests\Feature\Console;

use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\File;

/**
 * TranslateCommandTest.
 *
 * Feature tests for TranslateCommand.
 */
class TranslateCommandTest extends FeatureTestCase
{
    protected string $testPath;

    protected string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testPath = storage_path('app/test-translations');
        $this->outputPath = storage_path('app/translations/test-keys.json');

        // Create test directory
        if (!File::exists($this->testPath)) {
            File::makeDirectory($this->testPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }

        if (File::exists($this->outputPath)) {
            File::delete($this->outputPath);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_extract_translation_keys_from_blade_files()
    {
        // Create test blade file
        $bladeContent = <<<'BLADE'
        <div>
            {{ __('ui.button.save') }}
            @lang('ui.button.cancel')
            @trans('ui.message.success')
        </div>
        BLADE;

        File::put($this->testPath . '/test.blade.php', $bladeContent);

        // Run command
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Assert output file exists
        $this->assertTrue(File::exists($this->outputPath));

        // Assert keys were extracted
        $data = json_decode(File::get($this->outputPath), true);
        $this->assertArrayHasKey('keys', $data);
        $this->assertCount(3, $data['keys']);

        $keys = array_column($data['keys'], 'key');
        $this->assertContains('ui.button.save', $keys);
        $this->assertContains('ui.button.cancel', $keys);
        $this->assertContains('ui.message.success', $keys);
    }

    /** @test */
    public function it_can_extract_translation_keys_from_php_files()
    {
        // Create test PHP file
        $phpContent = <<<'PHP'
        <?php
        
        $message = __('validation.required');
        $text = trans('errors.not_found');
        $choice = trans_choice('messages.items', 5);
        PHP;

        File::put($this->testPath . '/test.php', $phpContent);

        // Run command
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Assert keys were extracted
        $data = json_decode(File::get($this->outputPath), true);
        $keys = array_column($data['keys'], 'key');

        $this->assertContains('validation.required', $keys);
        $this->assertContains('errors.not_found', $keys);
        $this->assertContains('messages.items', $keys);
    }

    /** @test */
    public function it_can_export_to_csv_format()
    {
        $csvPath = storage_path('app/translations/test-keys.csv');

        // Create test file
        File::put($this->testPath . '/test.blade.php', "{{ __('ui.test') }}");

        // Run command with CSV format
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $csvPath,
            '--format' => 'csv',
        ])->assertExitCode(0);

        // Assert CSV file exists
        $this->assertTrue(File::exists($csvPath));

        // Assert CSV content
        $content = File::get($csvPath);
        $this->assertStringContainsString('Key,Group,Count,Files', $content);
        $this->assertStringContainsString('ui.test', $content);

        // Clean up
        File::delete($csvPath);
    }

    /** @test */
    public function it_can_export_to_php_format()
    {
        $phpPath = storage_path('app/translations/test-keys.php');

        // Create test file
        File::put($this->testPath . '/test.blade.php', "{{ __('ui.test') }}");

        // Run command with PHP format
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $phpPath,
            '--format' => 'php',
        ])->assertExitCode(0);

        // Assert PHP file exists
        $this->assertTrue(File::exists($phpPath));

        // Assert PHP content
        $data = include $phpPath;
        $this->assertIsArray($data);
        $this->assertArrayHasKey('keys', $data);

        // Clean up
        File::delete($phpPath);
    }

    /** @test */
    public function it_groups_keys_by_namespace()
    {
        // Create test file with namespaced keys
        $content = <<<'BLADE'
        {{ __('canvastack::ui.button') }}
        {{ __('canvastack::validation.required') }}
        {{ __('ui.message') }}
        BLADE;

        File::put($this->testPath . '/test.blade.php', $content);

        // Run command
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Assert groups
        $data = json_decode(File::get($this->outputPath), true);
        $groups = array_unique(array_column($data['keys'], 'group'));

        $this->assertContains('canvastack::ui', $groups);
        $this->assertContains('canvastack::validation', $groups);
        $this->assertContains('ui', $groups);
    }

    /** @test */
    public function it_counts_key_occurrences()
    {
        // Create test file with duplicate keys
        $content = <<<'BLADE'
        {{ __('ui.button.save') }}
        {{ __('ui.button.save') }}
        {{ __('ui.button.save') }}
        {{ __('ui.button.cancel') }}
        BLADE;

        File::put($this->testPath . '/test.blade.php', $content);

        // Run command
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Assert counts
        $data = json_decode(File::get($this->outputPath), true);
        $keys = $data['keys'];

        $saveKey = collect($keys)->firstWhere('key', 'ui.button.save');
        $cancelKey = collect($keys)->firstWhere('key', 'ui.button.cancel');

        $this->assertEquals(3, $saveKey['count']);
        $this->assertEquals(1, $cancelKey['count']);
    }

    /** @test */
    public function it_tracks_files_containing_keys()
    {
        // Create multiple test files
        File::put($this->testPath . '/file1.blade.php', "{{ __('ui.test') }}");
        File::put($this->testPath . '/file2.blade.php', "{{ __('ui.test') }}");

        // Run command
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Assert files tracked
        $data = json_decode(File::get($this->outputPath), true);
        $testKey = collect($data['keys'])->firstWhere('key', 'ui.test');

        $this->assertCount(2, $testKey['files']);
        $this->assertStringContainsString('file1.blade.php', $testKey['files'][0]);
        $this->assertStringContainsString('file2.blade.php', $testKey['files'][1]);
    }

    /** @test */
    public function it_handles_custom_patterns()
    {
        // Create test file with custom translation function
        $content = "<?php echo customTrans('custom.key'); ?>";
        File::put($this->testPath . '/test.php', $content);

        // Run command with custom pattern
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
            '--pattern' => "/customTrans\(['\"]([^'\"]+)['\"]\)/",
        ])->assertExitCode(0);

        // Assert custom key extracted
        $data = json_decode(File::get($this->outputPath), true);
        $keys = array_column($data['keys'], 'key');

        $this->assertContains('custom.key', $keys);
    }

    /** @test */
    public function it_fails_when_path_does_not_exist()
    {
        $this->artisan('canvastack:translate', [
            '--path' => '/non/existent/path',
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_displays_statistics()
    {
        // Create test files
        File::put($this->testPath . '/test.blade.php', "{{ __('ui.test') }}");

        // Run command
        $this->artisan('canvastack:translate', [
            '--path' => $this->testPath,
            '--output' => $this->outputPath,
        ])->assertExitCode(0);

        // Assert output file was created (statistics were displayed)
        $this->assertTrue(File::exists($this->outputPath));
    }
}
