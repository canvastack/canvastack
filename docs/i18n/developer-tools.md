# Translation Developer Tools

Complete guide to CanvaStack translation developer tools and commands.

## 📦 Location

- **Commands Location**: `packages/canvastack/canvastack/src/Console/Commands/`
- **Related Files**: Translation management system

## 🎯 Available Commands

### 1. Extract Translation Keys

Extract translation keys from source files.

```bash
php artisan canvastack:translate [options]
```

**Options:**
- `--path=` - Path to scan for translation keys (default: resources/views)
- `--output=` - Output file path (default: storage/app/translations/keys.json)
- `--format=` - Output format: json, php, csv (default: json)
- `--include-vendor` - Include vendor packages in scan
- `--pattern=` - Additional regex patterns to search for

**Examples:**

```bash
# Extract keys from views
php artisan canvastack:translate

# Extract from specific path
php artisan canvastack:translate --path=app/Http/Controllers

# Export to CSV
php artisan canvastack:translate --format=csv --output=translations.csv

# Include vendor packages
php artisan canvastack:translate --include-vendor

# Add custom pattern
php artisan canvastack:translate --pattern="/@customTrans\([\'"]([^\'"]+)[\'"]\)/"
```

**Output:**

```json
{
    "generated_at": "2026-02-27 10:30:00",
    "total_keys": 150,
    "keys": [
        {
            "key": "ui.button.save",
            "group": "ui",
            "files": [
                "/path/to/file1.blade.php",
                "/path/to/file2.blade.php"
            ],
            "count": 5
        }
    ]
}
```

### 2. Detect Missing Translations

Detect and report missing translations (already exists).

```bash
php artisan canvastack:translation:missing {action} [options]
```

**Actions:**
- `list` - List all missing translations
- `report` - Show detailed report
- `export` - Export report to file
- `clear` - Clear missing translations log

**Options:**
- `--locale=` - Filter by locale
- `--format=` - Export format: json, csv (default: json)
- `--path=` - Export path

**Examples:**

```bash
# List missing translations
php artisan canvastack:translation:missing list

# Show detailed report
php artisan canvastack:translation:missing report

# Export to file
php artisan canvastack:translation:missing export --format=csv

# Clear missing translations
php artisan canvastack:translation:missing clear
```

### 3. Export Translations

Export translations to JSON, CSV, or PHP format.

```bash
php artisan canvastack:translate:export [options]
```

**Options:**
- `--locale=` - Locale to export (default: all)
- `--group=` - Translation group to export (default: all)
- `--format=` - Export format: json, csv, php, xlsx (default: json)
- `--output=` - Output file path
- `--include-vendor` - Include vendor translations
- `--flatten` - Flatten nested arrays (for CSV)

**Examples:**

```bash
# Export all translations to JSON
php artisan canvastack:translate:export

# Export specific locale
php artisan canvastack:translate:export --locale=id

# Export specific group
php artisan canvastack:translate:export --group=ui --locale=en

# Export to CSV
php artisan canvastack:translate:export --format=csv --flatten

# Export to custom path
php artisan canvastack:translate:export --output=/path/to/translations.json

# Include vendor translations
php artisan canvastack:translate:export --include-vendor
```

**JSON Output:**

```json
{
    "exported_at": "2026-02-27 10:30:00",
    "locales": ["en", "id"],
    "translations": {
        "en": {
            "ui": {
                "button": {
                    "save": "Save",
                    "cancel": "Cancel"
                }
            }
        },
        "id": {
            "ui": {
                "button": {
                    "save": "Simpan",
                    "cancel": "Batal"
                }
            }
        }
    }
}
```

**CSV Output:**

```csv
Key,Group,en,id
button.save,ui,Save,Simpan
button.cancel,ui,Cancel,Batal
```

### 4. Import Translations

Import translations from JSON, CSV, or PHP files.

```bash
php artisan canvastack:translate:import {file} [options]
```

**Options:**
- `--format=` - Import format: json, csv, php (auto-detected if not specified)
- `--locale=` - Target locale (required for CSV)
- `--group=` - Target group (required for CSV)
- `--merge` - Merge with existing translations instead of replacing
- `--backup` - Create backup before importing
- `--dry-run` - Preview changes without applying

**Examples:**

```bash
# Import from JSON
php artisan canvastack:translate:import translations.json

# Import from CSV
php artisan canvastack:translate:import translations.csv --locale=id --group=ui

# Merge with existing
php artisan canvastack:translate:import translations.json --merge

# Create backup before import
php artisan canvastack:translate:import translations.json --backup

# Preview changes (dry run)
php artisan canvastack:translate:import translations.json --dry-run
```

**Import Process:**

1. File validation
2. Format detection
3. Preview display
4. Confirmation prompt
5. Backup creation (if requested)
6. Translation application

### 5. Translation Coverage Report

Generate translation coverage report.

```bash
php artisan canvastack:translate:coverage [options]
```

**Options:**
- `--locale=` - Compare specific locale against base locale
- `--base=` - Base locale for comparison (default: en)
- `--format=` - Output format: table, json, html (default: table)
- `--output=` - Output file path
- `--threshold=` - Minimum coverage threshold 0-100 (default: 80)

**Examples:**

```bash
# Generate coverage report
php artisan canvastack:translate:coverage

# Check specific locale
php artisan canvastack:translate:coverage --locale=id

# Export to JSON
php artisan canvastack:translate:coverage --format=json --output=coverage.json

# Export to HTML
php artisan canvastack:translate:coverage --format=html

# Set coverage threshold
php artisan canvastack:translate:coverage --threshold=90
```

**Table Output:**

```
Translation Coverage Report
==========================
Generated: 2026-02-27 10:30:00
Base Locale: en

Summary:
+----------------+-------+
| Metric         | Value |
+----------------+-------+
| Total Locales  | 2     |
| Total Keys     | 150   |
| Total Translated | 140 |
| Total Missing  | 10    |
| Average Coverage | 93.33% |
+----------------+-------+

Coverage by Locale:
+--------+-------+------------+---------+-------+----------+
| Locale | Total | Translated | Missing | Empty | Coverage |
+--------+-------+------------+---------+-------+----------+
| id     | 150   | 140        | 10      | 0     | 93.33%   |
| es     | 150   | 120        | 30      | 0     | 80.00%   |
+--------+-------+------------+---------+-------+----------+
```

**HTML Output:**

Generates a beautiful HTML report with:
- Summary cards
- Coverage table
- Color-coded coverage levels
- Responsive design

## 🔧 Workflow Examples

### Complete Translation Workflow

#### 1. Extract Keys from Source

```bash
# Extract all translation keys
php artisan canvastack:translate --output=keys.json
```

#### 2. Check Coverage

```bash
# Generate coverage report
php artisan canvastack:translate:coverage --format=html
```

#### 3. Export for Translation

```bash
# Export to CSV for translators
php artisan canvastack:translate:export --format=csv --locale=en --output=to-translate.csv
```

#### 4. Import Translated Files

```bash
# Import translated CSV
php artisan canvastack:translate:import translated-id.csv --locale=id --group=ui --backup
```

#### 5. Verify Coverage

```bash
# Check coverage after import
php artisan canvastack:translate:coverage --locale=id
```

### Continuous Integration Workflow

```bash
#!/bin/bash

# Extract keys
php artisan canvastack:translate

# Check coverage
php artisan canvastack:translate:coverage --threshold=80

# Exit with error if coverage below threshold
if [ $? -ne 0 ]; then
    echo "Translation coverage below threshold!"
    exit 1
fi

echo "Translation coverage check passed!"
```

### Translation Team Workflow

#### For Developers:

```bash
# 1. Extract new keys after development
php artisan canvastack:translate

# 2. Export for translation team
php artisan canvastack:translate:export --format=csv --output=translations/to-translate.csv

# 3. Commit to repository
git add translations/to-translate.csv
git commit -m "Export translations for team"
```

#### For Translators:

1. Download `to-translate.csv`
2. Translate in spreadsheet software
3. Save as CSV
4. Send back to developers

#### For Developers (Import):

```bash
# 1. Receive translated file
# 2. Import with backup
php artisan canvastack:translate:import translations/translated-id.csv --locale=id --group=ui --backup --merge

# 3. Verify coverage
php artisan canvastack:translate:coverage --locale=id

# 4. Commit to repository
git add resources/lang/
git commit -m "Import Indonesian translations"
```

## 📊 Output Formats

### JSON Format

**Advantages:**
- Preserves nested structure
- Easy to parse programmatically
- Supports all data types

**Use Cases:**
- API integration
- Automated processing
- Version control

### CSV Format

**Advantages:**
- Easy to edit in spreadsheet software
- Human-readable
- Universal format

**Use Cases:**
- Translation teams
- Manual editing
- Excel/Google Sheets

### PHP Format

**Advantages:**
- Native Laravel format
- Direct import
- Preserves PHP syntax

**Use Cases:**
- Direct file replacement
- Laravel-specific workflows

### HTML Format

**Advantages:**
- Beautiful presentation
- Shareable reports
- No technical knowledge required

**Use Cases:**
- Management reports
- Team presentations
- Documentation

## 🎯 Best Practices

### 1. Regular Key Extraction

Run extraction regularly to catch new keys:

```bash
# Add to CI/CD pipeline
php artisan canvastack:translate
```

### 2. Coverage Monitoring

Set coverage thresholds in CI:

```bash
# Fail build if coverage below 80%
php artisan canvastack:translate:coverage --threshold=80
```

### 3. Backup Before Import

Always create backups:

```bash
php artisan canvastack:translate:import file.csv --backup
```

### 4. Use Dry Run

Preview changes before applying:

```bash
php artisan canvastack:translate:import file.csv --dry-run
```

### 5. Merge Strategy

Use merge for incremental updates:

```bash
php artisan canvastack:translate:import file.csv --merge
```

### 6. Version Control

Track translation files in git:

```bash
git add resources/lang/
git commit -m "Update translations"
```

## 🔍 Troubleshooting

### Issue: Keys Not Detected

**Solution:**
Add custom patterns:

```bash
php artisan canvastack:translate --pattern="/@myTrans\([\'"]([^\'"]+)[\'"]\)/"
```

### Issue: Import Fails

**Solution:**
Check file format and encoding:

```bash
# Verify JSON
cat file.json | jq .

# Check CSV encoding
file -i file.csv
```

### Issue: Coverage Report Empty

**Solution:**
Ensure base locale has translations:

```bash
# Check base locale
php artisan canvastack:translate:export --locale=en
```

### Issue: Missing Keys Not Logged

**Solution:**
Enable detection in config:

```php
// config/canvastack.php
'localization' => [
    'detect_missing' => true,
    'log_missing' => true,
],
```

## 📚 Related Documentation

- [Translation API](translation-api.md) - Translation API reference
- [Locale Management](locale-management.md) - Locale configuration
- [Translation Cache](translation-cache.md) - Caching strategies
- [i18n Implementation](../getting-started/i18n.md) - Implementation guide

## 💡 Tips

1. **Automate Extraction**: Add to pre-commit hooks
2. **Regular Coverage Checks**: Monitor translation completeness
3. **Use CSV for Teams**: Easy for non-technical translators
4. **Backup Strategy**: Always backup before bulk imports
5. **CI Integration**: Fail builds on low coverage
6. **Version Translations**: Track changes in git
7. **Document Patterns**: Keep list of custom patterns
8. **Test Imports**: Use dry-run before applying

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published

