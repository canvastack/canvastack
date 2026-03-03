# Translation Contribution Guide

Guide for contributing translations to CanvaStack and maintaining translation quality.

## 📋 Overview

This guide helps contributors:
- Add new language translations
- Update existing translations
- Maintain translation quality
- Follow contribution workflow
- Use translation tools effectively

## 🚀 Getting Started

### Prerequisites

1. **Fork the Repository**
   ```bash
   git clone https://github.com/your-username/canvastack.git
   cd canvastack
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Create a Branch**
   ```bash
   git checkout -b translation/add-french-locale
   ```

## 🌍 Adding a New Language

### Step 1: Check if Language Exists

Check if the language already exists:

```bash
ls resources/lang/
```

### Step 2: Create Language Directory

Create a new directory for your language:

```bash
mkdir resources/lang/fr  # For French
mkdir resources/lang/de  # For German
mkdir resources/lang/ja  # For Japanese
```

### Step 3: Copy English Files

Copy English translation files as a template:

```bash
cp -r resources/lang/en/* resources/lang/fr/
```

### Step 4: Translate Files

Translate each file in the new language directory:

**Example: `resources/lang/fr/ui.php`**

```php
<?php

return [
    // Common UI elements
    'name' => 'Nom',
    'email' => 'Email',
    'password' => 'Mot de passe',
    'submit' => 'Soumettre',
    'cancel' => 'Annuler',
    'save' => 'Enregistrer',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'view' => 'Voir',
    'search' => 'Rechercher',
    'filter' => 'Filtrer',
    
    // Navigation
    'dashboard' => 'Tableau de bord',
    'users' => 'Utilisateurs',
    'settings' => 'Paramètres',
    'logout' => 'Déconnexion',
    
    // Messages
    'success' => 'Succès!',
    'error' => 'Erreur!',
    'warning' => 'Avertissement!',
    'info' => 'Information',
    
    // Actions
    'create' => 'Créer',
    'update' => 'Mettre à jour',
    'delete_confirm' => 'Êtes-vous sûr de vouloir supprimer cet élément?',
];
```

### Step 5: Add Locale Configuration

Add the new locale to `config/canvastack.php`:

```php
'localization' => [
    'available_locales' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇺🇸',
        ],
        'fr' => [
            'name' => 'French',
            'native' => 'Français',
            'flag' => '🇫🇷',
        ],
        // ... other locales
    ],
],
```

### Step 6: Test Translations

Test your translations:

```bash
# Run translation coverage
php artisan canvastack:translate:coverage --locale=fr

# Check for missing keys
php artisan canvastack:translate:missing --locale=fr
```

### Step 7: Submit Pull Request

```bash
git add resources/lang/fr/
git commit -m "Add French translations"
git push origin translation/add-french-locale
```

Then create a pull request on GitHub.

## 🔄 Updating Existing Translations

### Step 1: Find Missing Keys

```bash
# Check coverage for specific locale
php artisan canvastack:translate:coverage --locale=id

# Find missing keys
php artisan canvastack:translate:missing --locale=id
```

### Step 2: Update Translation Files

Add missing translations to the appropriate files:

```php
// resources/lang/id/ui.php

return [
    // ... existing translations
    
    // Add new translations
    'new_feature' => 'Fitur Baru',
    'advanced_settings' => 'Pengaturan Lanjutan',
];
```

### Step 3: Verify Changes

```bash
# Check coverage again
php artisan canvastack:translate:coverage --locale=id

# Run tests
php artisan test --filter=Translation
```

### Step 4: Submit Pull Request

```bash
git add resources/lang/id/
git commit -m "Update Indonesian translations - add missing keys"
git push origin translation/update-indonesian
```

## ✅ Translation Quality Guidelines

### 1. Accuracy

- Translate meaning, not word-for-word
- Maintain context and tone
- Use appropriate formality level

```php
// Good - Natural translation
'delete_confirm' => '¿Está seguro de que desea eliminar este elemento?'

// Bad - Word-for-word translation
'delete_confirm' => '¿Usted está seguro que usted quiere borrar este artículo?'
```

### 2. Consistency

- Use consistent terminology
- Follow existing translation patterns
- Maintain consistent tone

```php
// Good - Consistent
'create' => 'Crear',
'update' => 'Actualizar',
'delete' => 'Eliminar',

// Bad - Inconsistent
'create' => 'Crear',
'update' => 'Editar',  // Should be 'Actualizar'
'delete' => 'Borrar',  // Should be 'Eliminar'
```

### 3. Completeness

- Translate all keys in the file
- Don't leave English text
- Translate parameters appropriately

```php
// Good - Complete translation
'welcome_message' => 'Bienvenido, :name!'

// Bad - Partial translation
'welcome_message' => 'Welcome, :name!'
```

### 4. Cultural Appropriateness

- Use culturally appropriate expressions
- Adapt idioms and metaphors
- Consider local conventions

```php
// Good - Culturally appropriate
'date_format' => 'd/m/Y'  // For European locales
'currency_symbol' => '€'

// Bad - Not adapted
'date_format' => 'm/d/Y'  // US format for European locale
```

### 5. Technical Accuracy

- Preserve technical terms when appropriate
- Use standard technical translations
- Don't translate code-related terms

```php
// Good - Preserve technical terms
'database_error' => 'Erreur de base de données'
'api_key' => 'Clé API'

// Bad - Over-translation
'database_error' => 'Erreur de base de données'  // OK
'api_key' => 'Clé IPA'  // Wrong - API should not be translated
```

## 🛠️ Translation Tools

### Extract Translation Keys

Extract all translation keys from your codebase:

```bash
# Extract from views
php artisan canvastack:translate --path=resources/views

# Extract from specific directory
php artisan canvastack:translate --path=app/Http/Controllers

# Export to CSV
php artisan canvastack:translate --path=resources/views --format=csv --output=translations.csv
```

### Check Translation Coverage

Check how complete your translations are:

```bash
# Check all locales
php artisan canvastack:translate:coverage

# Check specific locale
php artisan canvastack:translate:coverage --locale=fr

# Export to HTML report
php artisan canvastack:translate:coverage --format=html --output=coverage-report.html
```

### Find Missing Translations

Find keys that need translation:

```bash
# Find missing keys for specific locale
php artisan canvastack:translate:missing --locale=fr

# Export missing keys to file
php artisan canvastack:translate:missing --locale=fr --output=missing-fr.json
```

### Export Translations

Export translations for external translation services:

```bash
# Export to JSON
php artisan canvastack:translate:export --locale=fr --format=json

# Export to CSV
php artisan canvastack:translate:export --locale=fr --format=csv

# Export all locales
php artisan canvastack:translate:export --format=json
```

### Import Translations

Import translations from external sources:

```bash
# Import from JSON
php artisan canvastack:translate:import translations-fr.json

# Import from CSV
php artisan canvastack:translate:import translations-fr.csv --locale=fr

# Dry run (preview without importing)
php artisan canvastack:translate:import translations-fr.json --dry-run
```

## 📝 Contribution Workflow

### 1. Choose a Language

Check the [translation status](https://github.com/canvastack/canvastack/wiki/Translation-Status) to see which languages need help.

### 2. Claim the Work

Comment on the relevant issue or create a new one:

```
I would like to contribute French translations.
Estimated completion: 2 weeks
```

### 3. Translate

Follow the guidelines in this document to translate the files.

### 4. Test

Test your translations thoroughly:

```bash
# Run translation tests
php artisan test --filter=Translation

# Check coverage
php artisan canvastack:translate:coverage --locale=fr

# Manual testing
# - Switch to your locale in the application
# - Navigate through all pages
# - Check for missing or incorrect translations
```

### 5. Submit

Create a pull request with:
- Clear title: "Add French translations" or "Update Spanish translations"
- Description of what was translated
- Coverage report results
- Screenshots (if applicable)

### 6. Review

Respond to review comments and make necessary changes.

## 🎯 Translation Priorities

### High Priority

1. **Core UI Elements** (`ui.php`)
   - Navigation
   - Common actions
   - Form labels
   - Messages

2. **Authentication** (`auth.php`)
   - Login/logout
   - Registration
   - Password reset

3. **Validation** (`validation.php`)
   - Field validation messages
   - Error messages

### Medium Priority

4. **Components** (`components.php`)
   - Form components
   - Table components
   - Chart components

5. **Errors** (`errors.php`)
   - HTTP errors
   - Application errors

### Low Priority

6. **Feature-Specific** (`features/*.php`)
   - User management
   - Product management
   - Order management

## 🌟 Best Practices for Contributors

### 1. Start Small

Begin with high-priority files:

```bash
# Start with these files
resources/lang/{locale}/ui.php
resources/lang/{locale}/auth.php
resources/lang/{locale}/validation.php
```

### 2. Use Native Speakers

If possible, have translations reviewed by native speakers.

### 3. Test in Context

Always test translations in the actual application:

```bash
# Set locale and test
php artisan tinker
>>> app()->setLocale('fr');
>>> __('ui.dashboard');
```

### 4. Document Decisions

Add comments for non-obvious translations:

```php
return [
    // "Dashboard" doesn't have a good French equivalent
    // Using "Tableau de bord" which is commonly used
    'dashboard' => 'Tableau de bord',
    
    // Keeping "Email" as is - commonly used in French
    'email' => 'Email',
];
```

### 5. Keep Formatting

Preserve formatting and parameters:

```php
// Good - Preserves parameter
'welcome_message' => 'Bienvenue, :name!'

// Bad - Removes parameter
'welcome_message' => 'Bienvenue!'
```

## 🐛 Common Issues

### Issue: Missing Keys

**Problem**: Some keys are not translated.

**Solution**:
```bash
php artisan canvastack:translate:missing --locale=fr
```

### Issue: Incorrect Pluralization

**Problem**: Plural forms don't work correctly.

**Solution**: Use Laravel's pluralization syntax:
```php
'items_count' => '{0} Aucun élément|{1} Un élément|[2,*] :count éléments',
```

### Issue: Parameter Not Replaced

**Problem**: `:name` appears in output instead of actual value.

**Solution**: Ensure parameter names match:
```php
// Translation file
'welcome' => 'Bienvenue, :name!'

// Usage
__('ui.welcome', ['name' => $user->name])
```

### Issue: RTL Not Working

**Problem**: RTL languages display incorrectly.

**Solution**: Add locale to RTL list:
```php
'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
```

## 📞 Getting Help

### Resources

- [Translation API Documentation](translation-api.md)
- [Implementation Guide](implementation-guide.md)
- [Key Conventions](translation-key-conventions.md)

### Community

- GitHub Issues: Report problems or ask questions
- Discord: Join our translation channel
- Email: translations@canvastack.com

### Translation Team

Contact the translation team for:
- Language-specific questions
- Review requests
- Coordination with other translators

## 🏆 Recognition

Contributors will be:
- Listed in CONTRIBUTORS.md
- Credited in release notes
- Mentioned in documentation
- Given contributor badge

## 📚 Related Documentation

- [Implementation Guide](implementation-guide.md) - Complete i18n implementation
- [Translation Key Conventions](translation-key-conventions.md) - Key naming standards
- [Developer Tools](developer-tools.md) - Translation management tools
- [Translation API](translation-api.md) - Translation API reference

---

**Last Updated**: 2026-02-27  
**Version**: 1.0.0  
**Status**: Published

**Thank you for contributing to CanvaStack translations!** 🌍
