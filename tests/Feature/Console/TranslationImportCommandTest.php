<?php

namespace Canvastack\Canvastack\Tests\Feature\Console;

use Canvastack\Canvastack\Console\Commands\TranslationImportCommand;
use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Support\Facades\File;

/**
 * TranslationImportCommandTest.
 *
 * Unit tests for TranslationImportCommand.
 */
class TranslationImportCommandTest extends FeatureTestCase
{
    protected string $importPath;

    protected TranslationLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPath = storage_path('app/translations/import');
        $this->loader = app(TranslationLoader::class);

        // Create import directory
        if (!File::exists($this->importPath)) {
            File::makeDirectory($this->importPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up import files
        if (File::exists($this->importPath)) {
            File::deleteDirectory($this->importPath);
        }

        // Clean up imported translations
        $this->cleanupImportedTranslations();

        parent::tearDown();
    }

    protected function cleanupImportedTranslations(): void
    {
        $testFile = lang_path('id/imported.php');
        if (File::exists($testFile)) {
            File::delete($testFile);
        }

        // Also clean up nested test file
        $nestedFile = lang_path('id/nested.php');
        if (File::exists($nestedFile)) {
            File::delete($nestedFile);
        }
    }

    /** @test */
    public function it_can_import_from_json()
    {
        // Create JSON import file
        $jsonData = [
            'id' => [
                'imported' => [
                    'key1' => 'Nilai 1',
                    'key2' => 'Nilai 2',
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run import
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert translations imported
        $importedFile = lang_path('id/imported.php');
        $this->assertTrue(File::exists($importedFile));

        $translations = include $importedFile;
        $this->assertEquals('Nilai 1', $translations['key1']);
        $this->assertEquals('Nilai 2', $translations['key2']);
    }

    /** @test */
    public function it_can_import_from_csv()
    {
        // Create CSV import file
        $csvPath = $this->importPath . '/import.csv';
        $handle = fopen($csvPath, 'w');
        fputcsv($handle, ['Key', 'Group', 'id']);
        fputcsv($handle, ['key1', 'imported', 'Nilai 1']);
        fputcsv($handle, ['key2', 'imported', 'Nilai 2']);
        fclose($handle);

        // Run import
        $this->artisan('canvastack:translate:import', [
            'file' => $csvPath,
            '--locale' => 'id',
            '--group' => 'imported',
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert translations imported
        $importedFile = lang_path('id/imported.php');
        $this->assertTrue(File::exists($importedFile));
    }

    /** @test */
    public function it_can_import_from_php()
    {
        // Create PHP import file
        $phpData = [
            'id' => [
                'imported' => [
                    'key1' => 'Nilai 1',
                    'key2' => 'Nilai 2',
                ],
            ],
        ];

        $phpPath = $this->importPath . '/import.php';
        File::put($phpPath, "<?php\n\nreturn " . var_export($phpData, true) . ';');

        // Run import
        $this->artisan('canvastack:translate:import', [
            'file' => $phpPath,
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert translations imported
        $importedFile = lang_path('id/imported.php');
        $this->assertTrue(File::exists($importedFile));
    }

    /** @test */
    public function it_can_merge_with_existing_translations()
    {
        // Create existing translation
        $idPath = lang_path('id');
        if (!File::exists($idPath)) {
            File::makeDirectory($idPath, 0755, true);
        }

        File::put($idPath . '/imported.php', "<?php\n\nreturn [\n    'existing' => 'Existing Value',\n];");

        // Create import file
        $jsonData = [
            'id' => [
                'imported' => [
                    'new' => 'New Value',
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run import with merge
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
            '--merge' => true,
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert both keys exist
        $translations = include lang_path('id/imported.php');
        $this->assertArrayHasKey('existing', $translations);
        $this->assertArrayHasKey('new', $translations);
    }

    /** @test */
    public function it_can_create_backup_before_import()
    {
        // Create existing translation
        $idPath = lang_path('id');
        if (!File::exists($idPath)) {
            File::makeDirectory($idPath, 0755, true);
        }

        File::put($idPath . '/imported.php', "<?php\n\nreturn ['test' => 'value'];");

        // Create import file
        $jsonData = [
            'id' => [
                'imported' => [
                    'key' => 'Value',
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run import with backup
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
            '--backup' => true,
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert backup created
        $backupPath = storage_path('app/translations/backups');
        $this->assertTrue(File::exists($backupPath));

        // Clean up backup
        File::deleteDirectory($backupPath);
    }

    /** @test */
    public function it_supports_dry_run_mode()
    {
        // Create import file
        $jsonData = [
            'id' => [
                'imported' => [
                    'key' => 'Value',
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run import with dry-run
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
            '--dry-run' => true,
        ])->assertExitCode(0);

        // Assert no files created
        $importedFile = lang_path('id/imported.php');
        $this->assertFalse(File::exists($importedFile));
    }

    /** @test */
    public function it_fails_when_file_not_found()
    {
        $this->artisan('canvastack:translate:import', [
            'file' => '/non/existent/file.json',
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_fails_when_csv_missing_required_options()
    {
        $csvPath = $this->importPath . '/import.csv';
        File::put($csvPath, 'Key,Value');

        $this->artisan('canvastack:translate:import', [
            'file' => $csvPath,
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_auto_detects_file_format()
    {
        // Create JSON file
        $jsonData = [
            'id' => [
                'imported' => [
                    'key' => 'Value',
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run without format option
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert imported
        $this->assertTrue(File::exists(lang_path('id/imported.php')));
    }

    /** @test */
    public function it_displays_preview_before_import()
    {
        // Create import file
        $jsonData = [
            'id' => [
                'imported' => [
                    'key1' => 'Value 1',
                    'key2' => 'Value 2',
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run import
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
        ])
            ->expectsOutput('Importing translations...')
            ->expectsOutput('Preview:')
            ->expectsConfirmation('Do you want to import these translations?', 'no')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_nested_keys()
    {
        // Create import with nested keys
        $jsonData = [
            'id' => [
                'imported' => [
                    'level1' => [
                        'level2' => [
                            'level3' => 'Deep Value',
                        ],
                    ],
                ],
            ],
        ];

        $jsonPath = $this->importPath . '/import.json';
        File::put($jsonPath, json_encode($jsonData));

        // Run import
        $this->artisan('canvastack:translate:import', [
            'file' => $jsonPath,
        ])
            ->expectsConfirmation('Do you want to import these translations?', 'yes')
            ->assertExitCode(0);

        // Assert nested structure preserved
        $translations = include lang_path('id/imported.php');
        $this->assertEquals('Deep Value', $translations['level1']['level2']['level3']);
    }
}
