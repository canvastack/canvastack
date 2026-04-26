بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ

وَٱعْتَصِمُوا۟ بِحَبْلِ ٱللَّهِ

# CanvaStack

![Canvastack Logo](https://avatars.githubusercontent.com/u/86165096?s=256&v=4)

**Alhamdulillah** - In the name of Allah SWT, this library has been developed piece by piece since March 29, 2017.

A comprehensive Laravel library for building secure, accessible, and maintainable web applications. Inspired by Muntilan-CMS developed by [.::bit](https://www.limabit.com).

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20%7C%209.x%20%7C%2010.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![Version](https://img.shields.io/badge/version-0.1.0--alpha-orange.svg)](CHANGELOG.md)
[![Total Downloads](https://img.shields.io/packagist/dt/canvastack/canvastack)](https://packagist.org/packages/canvastack/canvastack)


## 🌟 Features

### 🔒 Security First
- **XSS Protection**: Automatic HTML escaping for all user input
- **File Upload Security**: Multi-layer validation (extension, MIME type, size, path traversal)
- **SQL Injection Prevention**: Encrypted queries with integrity checking
- **CSRF Protection**: Automatic token generation and validation
- **Path Traversal Protection**: Secure file path validation
- **Input Validation**: Dangerous attribute blocking (onclick, onerror, etc.)

### ♿ Accessibility (WCAG 2.1 Level A)
- **ARIA Attributes**: Full support for screen readers
- **Keyboard Navigation**: Complete keyboard accessibility
- **Label Associations**: Proper label-input relationships
- **Required Field Indicators**: Both visual and semantic markers
- **Error Announcements**: Accessible validation messages

### 🎨 Form Builder
- **Intuitive API**: Fluent interface for form generation
- **Model Binding**: Automatic value population from Eloquent models
- **Validation Integration**: Server-side rules propagate to client-side
- **File Uploads**: Secure file handling with thumbnail generation
- **AJAX Relational Fields**: Dynamic dependent dropdowns
- **Tab Navigation**: Accessible tab components

### 📊 DataTables Integration
- **Server-Side Processing**: Efficient handling of large datasets
- **Search & Filter**: Advanced search capabilities
- **Export Functions**: Export to Excel, PDF, CSV
- **Responsive Design**: Mobile-friendly tables
- **Custom Actions**: Flexible action buttons

### 📈 Chart Components
- **Multiple Chart Types**: Line, bar, pie, doughnut, radar
- **Interactive**: Hover tooltips and click events
- **Responsive**: Auto-resize on window changes
- **Customizable**: Full control over colors, labels, and options

### 🛠️ Developer Experience
- **Type Hints**: Complete PHP 8.0+ type declarations
- **PHPDoc**: Comprehensive documentation
- **IDE Support**: Full autocomplete support
- **Constants**: No magic strings
- **Error Handling**: Descriptive exceptions with context

## 📋 Requirements

- PHP 8.0 or higher
- Laravel 8.x, 9.x, or 10.x
- MySQL 5.7+ or PostgreSQL 9.6+
- PHP Extensions:
  - fileinfo (for MIME type detection)
  - gd or imagick (for image processing)
  - openssl (for encryption)

## 🚀 Installation

### Step 1: Install Laravel

```bash
composer create-project --prefer-dist laravel/laravel:^10.0 myapp
cd myapp
```

### Step 2: Install CanvaStack

**Option A: Via Composer (Recommended)**

```bash
composer require canvastack/canvastack
```

**Option B: Via composer.json**

Add to your `composer.json`:

```json
{
    "require": {
        "canvastack/canvastack": "^0.1"
    },
    "repositories": [{
        "type": "vcs",
        "url": "git@github.com:canvastack/canvastack.git"
    }]
}
```

Then run:

```bash
composer update
```

### Step 3: Publish Assets

```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider" --force
```

This will publish:
- Configuration files to `config/`
- Migrations to `database/migrations/`
- Assets to `public/vendor/canvastack/`
- Views to `resources/views/vendor/canvastack/`

### Step 4: Configure Database

1. Update `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

2. Create database:

```bash
mysql -u root -p
CREATE DATABASE your_database_name;
exit;
```

### Step 5: Run Migrations

```bash
php artisan migrate:fresh --seed
```

### Step 6: Configure Base URL

Edit `config/canvastack.php` and set your base URL:

```php
'base_url' => env('APP_URL', 'http://localhost'),
```

### Step 7: Access Application

**Default Credentials:**
- URL: `http://localhost/myapp/public`
- Email: `admin@gmail.com`
- Password: `@admin`

**Demo Site:** [demo.canvastack.com](https://demo.canvastack.com/login)

## 📖 Quick Start

### Basic Form Example

```php
use Canvastack\Canvastack\Library\Components\Form\Objects;

$form = new Objects();

// Open form
$form->open('/users/store', 'POST');

// Add fields
$form->text('username', null, [], true);
$form->email('email', null, [], true);
$form->password('password', [], true);

// Close with submit button
$form->close();
```

### Form with Validation

```php
$form->setValidations([
    'username' => 'required|min:3|max:50',
    'email' => 'required|email|unique:users',
    'age' => 'required|numeric|min:18'
]);

$form->open('/users/store', 'POST');
$form->text('username');
$form->email('email');
$form->number('age');
$form->close();
```

### Edit Form with Model Binding

```php
$user = User::find($id);

$form->model($user, false, "/users/{$id}");
$form->text('name');
$form->email('email');
$form->textarea('bio');
$form->close();
```

### File Upload Form

```php
$form->open('/profile/update', 'POST', 'horizontal', true);
$form->file('avatar', ['imagepreview'], true);
$form->text('bio');
$form->close();
```

### DataTable Example

```php
use Canvastack\Canvastack\Library\Components\Table\DataTables;

$table = new DataTables();
$table->setModel(User::class);
$table->setColumns(['id', 'name', 'email', 'created_at']);
$table->setSearchable(['name', 'email']);
$table->render();
```

## 📚 Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Form Builder API Reference](docs/COMPONENTS/FORM/API_REFERENCE.md)** - Complete API documentation
- **[Objects Class](docs/COMPONENTS/FORM/OBJECTS_CLASS.md)** - Main form class methods
- **[Element Traits](docs/COMPONENTS/FORM/ELEMENT_TRAITS.md)** - Form element components
- **[Security Guidelines](docs/COMPONENTS/FORM/SECURITY.md)** - Security best practices
- **[Accessibility Guidelines](docs/COMPONENTS/FORM/ACCESSIBILITY.md)** - WCAG compliance guide
- **[FormConstants](docs/COMPONENTS/FORM/FORMCONSTANTS.md)** - Available constants
- **[Migration Guide](docs/COMPONENTS/FORM/MIGRATION_GUIDE.md)** - Upgrade instructions

## 🔧 Configuration

### File Upload Configuration

```php
// config/canvastack.php
'file_upload' => [
    'max_size' => 10485760, // 10MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
    'upload_path' => 'uploads/',
    'generate_thumbnails' => true,
    'thumbnail_size' => [150, 150]
],
```

### Security Configuration

```php
'security' => [
    'enable_xss_protection' => true,
    'enable_csrf_protection' => true,
    'enable_file_validation' => true,
    'enable_path_validation' => true,
    'log_security_events' => true
],
```

## 🧪 Testing

Run the test suite:

```bash
# Unit tests
./vendor/bin/phpunit

# Property-based tests
./vendor/bin/phpunit --testsuite=PropertyBased

# Security tests
./vendor/bin/phpunit --testsuite=Security

# Accessibility tests
./vendor/bin/phpunit --testsuite=Accessibility
```

## 🔐 Security

### Reporting Security Issues

If you discover a security vulnerability, please email:
**security@canvastack.com**

Do not create public GitHub issues for security vulnerabilities.

### Security Features

- ✅ XSS Protection (automatic HTML escaping)
- ✅ SQL Injection Prevention (parameterized queries)
- ✅ CSRF Protection (automatic token generation)
- ✅ File Upload Security (multi-layer validation)
- ✅ Path Traversal Protection (secure path validation)
- ✅ Input Validation (dangerous attribute blocking)
- ✅ Encryption Security (integrity checking)
- ✅ Security Logging (audit trail)

## 📊 Package Components

### Core Components

| Component | Description | Documentation |
|-----------|-------------|---------------|
| **Form Builder** | Secure form generation with validation | [docs/COMPONENTS/FORM/](docs/COMPONENTS/FORM/) |
| **DataTables** | Server-side table processing | [docs/COMPONENTS/TABLE/](docs/COMPONENTS/TABLE/) |
| **Charts** | Interactive chart components | Coming soon |
| **Template Engine** | Layout and theme management | Coming soon |
| **Meta Tags** | SEO optimization helpers | Coming soon |
| **Scripts Manager** | JavaScript asset management | Coming soon |

### Helper Functions

| Helper | Purpose | Example |
|--------|---------|---------|
| `canvastack_form_escape_html()` | Escape HTML for XSS protection | `canvastack_form_escape_html($input)` |
| `canvastack_form_validate_file_extension()` | Validate file extensions | `canvastack_form_validate_file_extension($file, ['jpg', 'png'])` |
| `canvastack_form_validate_path()` | Validate file paths | `canvastack_form_validate_path($path, $baseDir)` |
| `canvastack_form_validate_attributes()` | Validate HTML attributes | `canvastack_form_validate_attributes($attrs)` |
| `canvastack_mail_config_service()` | Get MailConfigService instance | `canvastack_mail_config_service()` |
| `canvastack_mail_test_smtp()` | Test SMTP connection | `canvastack_mail_test_smtp()` |

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details on:
- Code of Conduct
- Development setup
- Coding standards
- Pull request process
- Testing requirements

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

> Previously released as `canvastack/origin`. Legacy history available in [docs/CHANGELOG/CHANGELOG-legacy-origin.md](docs/CHANGELOG/CHANGELOG-legacy-origin.md).

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Credits

### Author
- **wisnuwidi** - [wisnuwidi@gmail.com](mailto:wisnuwidi@gmail.com)
- **CanvaStack** - [canvastacks@gmail.com](mailto:canvastacks@gmail.com)

### Inspiration
- **Muntilan-CMS** by [.::bit](https://www.limabit.com)

### Contributors
- All contributors who have helped improve this library

## 🙏 Acknowledgments

**Alhamdulillah** - All praise is due to Allah SWT for making this project possible.

Special thanks to:
- The Laravel community
- All open-source contributors
- Everyone who has provided feedback and suggestions

## 📞 Support

- **Documentation**: [docs/](docs/)
- **Issues**: [GitHub Issues](https://github.com/canvastack/canvastack/issues)
- **Email**: [support@canvastack.com](mailto:support@canvastack.com)
- **Demo**: [demo.canvastack.com](https://demo.canvastack.com)

---

**Made with ❤️ by the Canvastack Team**

*"Simplifying Laravel development, one component at a time."*
