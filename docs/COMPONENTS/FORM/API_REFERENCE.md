# Form Builder API Reference

## Table of Contents

1. [Introduction](#introduction)
2. [Objects Class](#objects-class)
3. [Element Traits](#element-traits)
4. [FormConstants](#formconstants)
5. [SafeHtml System](#safehtml-system)
6. [Security Guidelines](#security-guidelines)
7. [Accessibility Guidelines](#accessibility-guidelines)

## Introduction

The Canvastack Origin Form Builder provides a comprehensive, secure, and accessible way to generate HTML forms in Laravel applications. This API reference documents all public methods, security features, and best practices.

### Key Features

- **XSS Protection**: All user input is automatically escaped
- **WCAG 2.1 Level A Compliance**: Full accessibility support with ARIA attributes
- **Type Safety**: Complete type hints for IDE autocomplete
- **Validation Integration**: Server-side validation rules propagate to client-side
- **File Upload Security**: Multi-layer validation for secure file uploads
- **SafeHtml System**: Prevents double-encoding while maintaining security

### Quick Start

```php
use Canvastack\Canvastack\Library\Components\Form\Objects;

$form = new Objects();

// Open form
$form->open('/users/store', 'POST');

// Add text input
$form->text('username', null, [], true);

// Add email input
$form->email('email', null, [], true);

// Close form with submit button
$form->close();
```

---

*For detailed method documentation, see the sections below.*
*For migration guide from older versions, see [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)*
