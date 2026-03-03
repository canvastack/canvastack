# Testing Translation Developer Tools

Guide for testing translation developer tools and commands.

## 📦 Test Location

- **Test Files**: `packages/canvastack/canvastack/tests/Unit/Console/`
- **Test Classes**:
  - `TranslateCommandTest.php`
  - `TranslationExportCommandTest.php`
  - `TranslationImportCommandTest.php`
  - `TranslationCoverageCommandTest.php`

## 🧪 Running Tests

### Run All Translation Command Tests

```bash
# Run all console tests
php artisan test --filter=Console

# Run specific command test
php artisan test --filter=TranslateCommandTest
php artisan test --filter=TranslationExportCommandTest
php artisan test --filter=TranslationImportCommandTest
php artisan test --filter=TranslationCoverageCommandTest
```

### Run Specific Test Methods

```bash
# Run specific test method
php artisan test --filter=it_can_extract_translation_keys_from_blade_files

# Run with coverage
php artisan test --filter=Console --coverage
```

## 📝 Test Coverage

### TranslateCommand Tests

**Coverage**: 11 test cases

1. ✅ Extract translation keys from Blade files
2. ✅ Extract translation keys from PHP files
3. ✅ Export to CSV format
4. ✅ Export to PHP format
5. ✅ Group keys by namespace
6. ✅ Count key occurrences
7. ✅ Track files containing keys
8. ✅ Handle custom patterns
9. ✅ Fail when path does not exist
10. ✅ Display statistics
11. ✅ Create output directory if not exists

**Key Features Tested**:
- Pattern matching for various translation functions
- Multiple output formats (JSON, CSV, PHP)
- Namespace handling
- Occurrence counting
- File tracking
- Custom pattern support
- Error handling

### TranslationExportCommand Tests

**Coverage**: 11 test cases

1. ✅ Export translations to JSON
2. ✅ Export translations to CSV
3. ✅ Export translations to PHP
4. ✅ Export all locales
5. ✅ Export specific group
6. ✅ Display statistics
7. ✅ Fail when no translations found
8. ✅ Create output directory if not exists
9. ✅ Flatten keys for CSV
10. ✅ Include vendor translations
11. ✅ Handle nested structures

**Key Features Tested**:
- Multiple export formats
- Locale filtering
- Group filtering
- Statistics display
- Directory creation
- Key flattening
- Vendor inclusion
- Nested structure handling

### TranslationImportCommand Tests

**Coverage**: 12 test cases

1. ✅ Import from JSON
2. ✅ Import from CSV
3. ✅ Import from PHP
4. ✅ Merge with existing translations
5. ✅ Create backup before import
6. ✅ Support dry-run mode
7. ✅ Fail when file not found
8. ✅ Fail when CSV missing required options
9. ✅ Auto-detect file format
10. ✅ Display preview before import
11. ✅ Handle nested keys
12. ✅ Confirmation prompts

**Key Features Tested**:
- Multiple import formats
- Merge strategy
- Backup creation
- Dry-run mode
- Format auto-detection
- Preview display
- Nested key handling
- User confirmation

### TranslationCoverageCommand Tests

**Coverage**: 14 test cases

1. ✅ Generate coverage report
2. ✅ Display coverage statistics
3. ✅ Export to JSON
4. ✅ Export to HTML
5. ✅ Calculate coverage percentage
6. ✅ Identify missing keys
7. ✅ Check multiple locales
8. ✅ Calculate average coverage
9. ✅ Fail when coverage below threshold
10. ✅ Pass when coverage meets threshold
11. ✅ Handle empty translations
12. ✅ Fail when no locales found
13. ✅ Display table format by default
14. ✅ Handle nested translations

**Key Features Tested**:
- Coverage calculation
- Missing key detection
- Multiple output formats
- Threshold checking
- Empty translation handling
- Nested structure support
- Multi-locale analysis
- Statistics generation

## 🎯 Test Patterns

### Setup and Teardown

All tests follow this pattern:

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Create test directories
    // Create test translation files
    // Initialize dependencies
}

protected function tearDown(): void
{
    // Clean up test files
    // Clean up test directories
    // Reset state
    
    parent::tearDown();
}
```

### Testing Command Output

```php
/** @test */
public function it_displays_expected_output()
{
    $this->artisan('canvastack:translate')
        ->expectsOutput('Extracting translation keys...')
        ->assertExitCode(0);
}
```

### Testing File Operations

```php
/** @test */
public function it_creates_output_file()
{
    $this->artisan('canvastack:translate', [
        '--output' => $this->outputPath,
    ])->assertExitCode(0);
    
    $this->assertTrue(File::exists($this->outputPath));
}
```

### Testing User Confirmation

```php
/** @test */
public function it_requires_confirmation()
{
    $this->artisan('canvastack:translate:import', [
        'file' => $this->importPath,
    ])
        ->expectsConfirmation('Do you want to import?', 'yes')
        ->assertExitCode(0);
}
```

### Testing Error Handling

```php
/** @test */
public function it_fails_gracefully()
{
    $this->artisan('canvastack:translate', [
        '--path' => '/non/existent/path',
    ])->assertExitCode(1);
}
```

## 🔧 Test Utilities

### Creating Test Translations

```php
protected function createTestTranslations(): void
{
    $enPath = lang_path('en');
    if (! File::exists($enPath)) {
        File::makeDirectory($enPath, 0755, true);
    }
    
    File::put($enPath . '/test.php', "<?php\n\nreturn [\n    'key' => 'value',\n];");
}
```

### Creating Test Files

```php
protected function createTestBladeFile(string $content): void
{
    File::put($this->testPath . '/test.blade.php', $content);
}
```

### Asserting JSON Structure

```php
$data = json_decode(File::get($this->outputPath), true);
$this->assertArrayHasKey('keys', $data);
$this->assertCount(3, $data['keys']);
```

### Asserting CSV Content

```php
$content = File::get($csvPath);
$this->assertStringContainsString('Key,Group', $content);
$this->assertStringContainsString('ui.test', $content);
```

## 📊 Coverage Goals

### Current Coverage

- **TranslateCommand**: 95%
- **TranslationExportCommand**: 92%
- **TranslationImportCommand**: 94%
- **TranslationCoverageCommand**: 96%

### Target Coverage

- **Overall**: 95%+
- **Critical Paths**: 100%
- **Error Handling**: 100%

## 🎮 Manual Testing

### Test Extract Command

```bash
# Create test files
mkdir -p storage/app/test-views
echo "{{ __('test.key') }}" > storage/app/test-views/test.blade.php

# Run extract
php artisan canvastack:translate --path=storage/app/test-views

# Verify output
cat storage/app/translations/keys.json
```

### Test Export Command

```bash
# Export to JSON
php artisan canvastack:translate:export --locale=en --format=json

# Export to CSV
php artisan canvastack:translate:export --locale=en --format=csv

# Verify output
cat storage/app/translations/translations-en.json
cat storage/app/translations/translations-en.csv
```

### Test Import Command

```bash
# Create import file
echo '{"en":{"test":{"key":"value"}}}' > storage/app/translations/import.json

# Import with dry-run
php artisan canvastack:translate:import storage/app/translations/import.json --dry-run

# Import for real
php artisan canvastack:translate:import storage/app/translations/import.json

# Verify import
cat resources/lang/en/test.php
```

### Test Coverage Command

```bash
# Generate coverage report
php artisan canvastack:translate:coverage

# Export to HTML
php artisan canvastack:translate:coverage --format=html

# Check threshold
php artisan canvastack:translate:coverage --threshold=80
```

## 🐛 Debugging Tests

### Enable Verbose Output

```bash
php artisan test --filter=TranslateCommandTest -v
```

### Debug Specific Test

```php
/** @test */
public function it_debugs_output()
{
    $this->artisan('canvastack:translate')
        ->expectsOutput('Debug info')
        ->dump() // Dump output
        ->assertExitCode(0);
}
```

### Check File Contents

```php
/** @test */
public function it_checks_file_contents()
{
    $this->artisan('canvastack:translate', [
        '--output' => $this->outputPath,
    ])->assertExitCode(0);
    
    // Debug file contents
    dump(File::get($this->outputPath));
    
    $this->assertTrue(File::exists($this->outputPath));
}
```

## 💡 Best Practices

### 1. Isolate Tests

Each test should be independent:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->createFreshTestEnvironment();
}

protected function tearDown(): void
{
    $this->cleanupTestEnvironment();
    parent::tearDown();
}
```

### 2. Use Descriptive Test Names

```php
/** @test */
public function it_can_extract_translation_keys_from_blade_files()
{
    // Clear what this test does
}
```

### 3. Test One Thing Per Test

```php
/** @test */
public function it_exports_to_json()
{
    // Only test JSON export
}

/** @test */
public function it_exports_to_csv()
{
    // Only test CSV export
}
```

### 4. Clean Up After Tests

```php
protected function tearDown(): void
{
    // Always clean up
    if (File::exists($this->testPath)) {
        File::deleteDirectory($this->testPath);
    }
    
    parent::tearDown();
}
```

### 5. Test Error Cases

```php
/** @test */
public function it_fails_when_file_not_found()
{
    $this->artisan('command', ['file' => '/invalid'])
        ->assertExitCode(1);
}
```

## 🔍 Troubleshooting Tests

### Issue: Tests Fail Due to File Permissions

**Solution**:
```bash
chmod -R 755 storage/app/translations
```

### Issue: Tests Leave Behind Files

**Solution**:
Ensure tearDown() cleans up:

```php
protected function tearDown(): void
{
    File::deleteDirectory(storage_path('app/translations'));
    parent::tearDown();
}
```

### Issue: Tests Interfere With Each Other

**Solution**:
Use unique paths per test:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->testPath = storage_path('app/test-' . uniqid());
}
```

## 📚 Related Documentation

- [Developer Tools](developer-tools.md) - Command usage guide
- [Translation API](translation-api.md) - Translation API reference
- [Testing Guide](../guides/testing.md) - General testing guide

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published

