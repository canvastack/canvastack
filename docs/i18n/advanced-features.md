# Advanced i18n Features

This document describes the advanced internationalization features implemented in CanvaStack.

## Features Implemented

### 1. Pluralization Support

**Location**: `src/Support/Localization/Pluralizer.php`

Provides comprehensive pluralization support for multiple locales with complex plural rules.

**Supported Locales**:
- English (en): one, other
- Indonesian (id): other (no plural distinction)
- Arabic (ar): zero, one, two, few, many, other
- Russian (ru): one, few, many, other
- French (fr): one, other
- Polish (pl): one, few, many, other

**Usage**:
```php
use Canvastack\Canvastack\Support\Localization\Pluralizer;

$pluralizer = app(Pluralizer::class);

// Simple pluralization
echo $pluralizer->choice('ui.pluralization.items', 0); // "No items"
echo $pluralizer->choice('ui.pluralization.items', 1); // "One item"
echo $pluralizer->choice('ui.pluralization.items', 5); // "5 items"

// With replacements
echo $pluralizer->choice('ui.pluralization.users', 3, ['name' => 'John']); // "3 users"
```

**Translation Format**:
```php
// resources/lang/en/ui.php
'pluralization' => [
    'items' => '{0} No items|{1} One item|[2,*] :count items',
    'users' => '{0} No users|{1} One user|[2,*] :count users',
],
```

**Helper Function**:
```php
echo trans_choice_canvastack('ui.pluralization.items', 5); // "5 items"
```

---

### 2. Date/Time Localization

**Location**: `src/Support/Localization/DateTimeFormatter.php`

Provides comprehensive date and time formatting for different locales using Carbon.

**Features**:
- Format date, time, and datetime according to locale
- Human-readable dates (e.g., "2 days ago")
- Long and short date formats
- Day and month names in locale language
- Calendar dates (Today, Yesterday, Tomorrow)
- Timezone support
- Date parsing from locale format

**Usage**:
```php
use Canvastack\Canvastack\Support\Localization\DateTimeFormatter;

$formatter = app(DateTimeFormatter::class);

// Format date
echo $formatter->formatDate('2024-01-15'); // "2024-01-15" (en) or "15-01-2024" (id)

// Format with custom format
echo $formatter->formatDate('2024-01-15', 'd/m/Y'); // "15/01/2024"

// Human readable
echo $formatter->diffForHumans('2024-01-13'); // "2 days ago"

// Long format
echo $formatter->formatLongDate('2024-01-15'); // "January 15, 2024"

// Short format
echo $formatter->formatShortDate('2024-01-15'); // "Jan 15, 2024"

// Get day/month names
echo $formatter->getDayName('2024-01-15'); // "Monday"
echo $formatter->getMonthName('2024-01-15'); // "January"

// Calendar
echo $formatter->calendar(Carbon::now()); // "Today at 2:30 PM"

// Check dates
$formatter->isToday($date);
$formatter->isYesterday($date);
$formatter->isTomorrow($date);
```

**Helper Functions**:
```php
echo format_date_locale('2024-01-15'); // Formatted according to locale
echo format_time_locale('14:30:00'); // Formatted according to locale
echo format_datetime_locale('2024-01-15 14:30:00'); // Formatted according to locale
```

**Configuration**:
```php
// config/canvastack.php
'localization' => [
    'date_format' => [
        'en' => 'Y-m-d',
        'id' => 'd-m-Y',
    ],
    'time_format' => [
        'en' => 'H:i:s',
        'id' => 'H:i:s',
    ],
    'datetime_format' => [
        'en' => 'Y-m-d H:i:s',
        'id' => 'd-m-Y H:i:s',
    ],
    'long_date_format' => [
        'en' => 'F j, Y',
        'id' => 'j F Y',
    ],
    'short_date_format' => [
        'en' => 'M j, Y',
        'id' => 'j M Y',
    ],
],
```

---

### 3. Number Formatting

**Location**: `src/Support/Localization/NumberFormatter.php`

Provides comprehensive number formatting for different locales.

**Features**:
- Format numbers with locale-specific separators
- Format integers, decimals, percentages
- File size formatting (bytes to human readable)
- Compact notation (1K, 1M, 1B)
- Ordinal numbers (1st, 2nd, 3rd)
- Number parsing from locale format
- Scientific notation
- Fractions
- Number ranges
- Numbers with units

**Usage**:
```php
use Canvastack\Canvastack\Support\Localization\NumberFormatter;

$formatter = app(NumberFormatter::class);

// Basic formatting
echo $formatter->format(1234.56); // "1,234.56" (en) or "1.234,56" (id)

// Integer
echo $formatter->formatInteger(1234.56); // "1,235"

// Decimal with precision
echo $formatter->formatDecimal(1234.5678, 3); // "1,234.568"

// Percentage
echo $formatter->formatPercentage(45.67); // "45.67%"

// File size
echo $formatter->formatFileSize(1048576); // "1.00 MB"

// Compact
echo $formatter->formatCompact(1500); // "1.5K"
echo $formatter->formatCompact(1500000); // "1.5M"

// Ordinal
echo $formatter->formatOrdinal(1, 'en'); // "1st"
echo $formatter->formatOrdinal(2, 'en'); // "2nd"
echo $formatter->formatOrdinal(1, 'id'); // "ke-1"

// Parse
$number = $formatter->parse('1,234.56', 'en'); // 1234.56

// With sign
echo $formatter->formatWithSign(123.45); // "+123.45"
echo $formatter->formatWithSign(-123.45); // "-123.45"

// Range
echo $formatter->formatRange(10, 20); // "10 - 20"

// With unit
echo $formatter->formatWithUnit(100, 'kg'); // "100 kg"

// Scientific
echo $formatter->formatScientific(1234567, 2); // "1.23E+6"

// Fraction
echo $formatter->formatFraction(2.5); // "2 1/2"
```

**Helper Function**:
```php
echo format_number_locale(1234.56); // Formatted according to locale
echo format_number_locale(1234.56, 3); // With 3 decimal places
```

**Configuration**:
```php
// config/canvastack.php
'localization' => [
    'number_format' => [
        'en' => [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimals' => 2,
        ],
        'id' => [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'decimals' => 2,
        ],
    ],
],
```

---

### 4. Currency Formatting

**Location**: `src/Support/Localization/CurrencyFormatter.php`

Provides comprehensive currency formatting for different locales and currencies.

**Features**:
- Format currency with locale-specific symbols and separators
- Format with currency code (USD, IDR, EUR, etc.)
- Format without symbol (just the number)
- Accounting format (negative in parentheses)
- Currency conversion
- Currency ranges
- Parse currency strings
- Get currency symbol and name

**Usage**:
```php
use Canvastack\Canvastack\Support\Localization\CurrencyFormatter;

$formatter = app(CurrencyFormatter::class);

// Basic formatting
echo $formatter->format(1234.56, 'USD'); // "$1,234.56"
echo $formatter->format(1234, 'IDR'); // "Rp 1.234"

// With code
echo $formatter->formatWithCode(1234.56, 'USD'); // "USD 1,234.56"

// Without symbol
echo $formatter->formatWithoutSymbol(1234.56, 'USD'); // "1,234.56"

// Accounting format
echo $formatter->formatAccounting(1234.56, 'USD'); // "$1,234.56"
echo $formatter->formatAccounting(-1234.56, 'USD'); // "($1,234.56)"

// Parse
$amount = $formatter->parse('$1,234.56', 'USD'); // 1234.56

// Convert
$rates = ['USD' => 1, 'EUR' => 0.85, 'IDR' => 15000];
echo $formatter->convert(100, 'USD', 'EUR', $rates); // "€85.00"

// Range
echo $formatter->formatRange(100, 200, 'USD'); // "$100.00 - $200.00"

// Get symbol
echo $formatter->getSymbol('USD'); // "$"
echo $formatter->getSymbol('IDR'); // "Rp"

// Get name
echo $formatter->getName('USD'); // "US Dollar"
```

**Helper Function**:
```php
echo format_currency_locale(1234.56); // Formatted according to locale
echo format_currency_locale(1234.56, 'id'); // Formatted for Indonesian locale
echo format_currency_locale(1234.56, 'id', false); // Without symbol
```

**Configuration**:
```php
// config/canvastack.php
'localization' => [
    'currency_format' => [
        'en' => [
            'symbol' => '$',
            'position' => 'before',
            'space' => false,
            'decimals' => 2,
        ],
        'id' => [
            'symbol' => 'Rp',
            'position' => 'before',
            'space' => true,
            'decimals' => 0,
        ],
    ],
    'default_currency' => [
        'en' => 'USD',
        'id' => 'IDR',
    ],
    'currencies' => [
        'USD' => [
            'default' => [
                'symbol' => '$',
                'name' => 'US Dollar',
                'position' => 'before',
                'space' => false,
                'decimals' => 2,
            ],
        ],
        'IDR' => [
            'default' => [
                'symbol' => 'Rp',
                'name' => 'Indonesian Rupiah',
                'position' => 'before',
                'space' => true,
                'decimals' => 0,
            ],
        ],
        // ... more currencies
    ],
],
```

---

### 5. RTL (Right-to-Left) Support

**Location**: `src/Support/Localization/RtlSupport.php`

Provides comprehensive RTL support for languages like Arabic, Hebrew, Persian, and Urdu.

**Features**:
- Check if locale is RTL
- Get text direction (ltr/rtl)
- Get start/end positions (left/right)
- Get float direction
- Get margin/padding/border properties
- Convert CSS properties for RTL
- Flip icons for RTL
- Logical properties (start/end instead of left/right)
- Tailwind CSS classes for RTL

**Usage**:
```php
use Canvastack\Canvastack\Support\Localization\RtlSupport;

$rtl = app(RtlSupport::class);

// Check if RTL
$rtl->isRtl('ar'); // true
$rtl->isRtl('en'); // false

// Get direction
$rtl->getDirection('ar'); // "rtl"
$rtl->getDirection('en'); // "ltr"

// Get start/end
$rtl->getStart('ar'); // "right"
$rtl->getEnd('ar'); // "left"

// Get float
$rtl->getFloat('ar'); // "right"

// Get text align
$rtl->getTextAlign('ar'); // "right"

// Get margin properties
$rtl->getMarginStart('ar'); // "margin-right"
$rtl->getMarginEnd('ar'); // "margin-left"

// Convert CSS property
$rtl->convertCssProperty('margin-left', 'ar'); // "margin-right"

// Get dir attribute
$rtl->getDirAttribute('ar'); // "rtl"

// Get RTL class
$rtl->getRtlClass('ar'); // "rtl"

// Get Tailwind classes
$rtl->getTailwindClasses('ar'); // ['rtl', 'text-right', 'dir-rtl']

// Flip icon
$rtl->flipIcon('arrow-left', 'ar'); // "arrow-left flip-rtl"

// Get logical property
$rtl->getLogicalProperty('margin-start', '10px', 'ar'); // ['margin-right' => '10px']
```

**Helper Functions**:
```php
echo is_rtl(); // Check if current locale is RTL
echo text_direction(); // Get text direction for current locale
```

**Blade Directives**:
```blade
{{-- Conditional rendering --}}
@rtl
    <div>RTL content</div>
@endrtl

@ltr
    <div>LTR content</div>
@endltr

{{-- Output direction --}}
<html @dir>
    {{-- Outputs: dir="rtl" or dir="ltr" --}}
</html>

{{-- Output class --}}
<div class="@rtlClass">
    {{-- Outputs: class="rtl" or class="ltr" --}}
</div>

{{-- Inline styles --}}
<div style="@marginStart('10px')">
    {{-- Outputs: margin-left: 10px (LTR) or margin-right: 10px (RTL) --}}
</div>

<div style="@floatStart">
    {{-- Outputs: float: left (LTR) or float: right (RTL) --}}
</div>

<div style="@textStart">
    {{-- Outputs: text-align: left (LTR) or text-align: right (RTL) --}}
</div>
```

**CSS Utilities**:

The package includes comprehensive RTL CSS utilities in `resources/css/rtl.css`:

```css
/* Direction */
[dir="rtl"] { direction: rtl; text-align: right; }
[dir="ltr"] { direction: ltr; text-align: left; }

/* Margin Start/End */
.ms-4 { margin-left: 1rem; }
[dir="rtl"] .ms-4 { margin-left: unset; margin-right: 1rem; }

.me-4 { margin-right: 1rem; }
[dir="rtl"] .me-4 { margin-right: unset; margin-left: 1rem; }

/* Padding Start/End */
.ps-4 { padding-left: 1rem; }
[dir="rtl"] .ps-4 { padding-left: unset; padding-right: 1rem; }

.pe-4 { padding-right: 1rem; }
[dir="rtl"] .pe-4 { padding-right: unset; padding-left: 1rem; }

/* Text Alignment */
.text-start { text-align: left; }
[dir="rtl"] .text-start { text-align: right; }

.text-end { text-align: right; }
[dir="rtl"] .text-end { text-align: left; }

/* Float */
.float-start { float: left; }
[dir="rtl"] .float-start { float: right; }

/* Icon Flip */
.flip-rtl { transform: scaleX(-1); }
[dir="ltr"] .flip-rtl { transform: scaleX(1); }

/* And many more... */
```

**Configuration**:
```php
// config/canvastack.php
'localization' => [
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
    'available_locales' => [
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'flag' => '🇸🇦',
            'direction' => 'rtl',
        ],
        // ... more locales
    ],
],
```

---

## Helper Functions Summary

All helper functions are defined in `src/Support/Helpers/i18n_helpers.php`:

```php
// Pluralization
trans_choice_canvastack($key, $count, $replace = [], $locale = null)

// Locale
locale($locale = null) // Get or set locale
available_locales() // Get all available locales
locale_name($locale = null) // Get locale name
locale_native_name($locale = null) // Get locale native name

// RTL
is_rtl($locale = null) // Check if RTL
text_direction($locale = null) // Get text direction

// Date/Time
format_date_locale($date, $format = null, $locale = null)
format_time_locale($time, $format = null, $locale = null)
format_datetime_locale($datetime, $format = null, $locale = null)

// Number
format_number_locale($number, $decimals = null, $locale = null)

// Currency
format_currency_locale($amount, $locale = null, $includeSymbol = true)
```

---

## Testing

Unit tests are provided for all features:

- `tests/Unit/Support/Localization/PluralizerTest.php`
- `tests/Unit/Support/Localization/DateTimeFormatterTest.php`
- `tests/Unit/Support/Localization/NumberFormatterTest.php`
- `tests/Unit/Support/Localization/CurrencyFormatterTest.php`
- `tests/Unit/Support/Localization/RtlSupportTest.php`

Run tests:
```bash
./vendor/bin/phpunit tests/Unit/Support/Localization/
```

---

## Integration

To use these features in your application:

1. **Register Service Provider** (if not auto-discovered):
```php
// config/app.php
'providers' => [
    Canvastack\Canvastack\CanvastackServiceProvider::class,
],
```

2. **Publish Configuration**:
```bash
php artisan vendor:publish --tag=canvastack-config
```

3. **Include RTL CSS** (in your layout):
```blade
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/rtl.css') }}">
```

4. **Use in Blade Templates**:
```blade
<html lang="{{ locale() }}" dir="{{ text_direction() }}">
<head>
    <title>{{ __('ui.labels.title') }}</title>
</head>
<body class="{{ is_rtl() ? 'rtl' : 'ltr' }}">
    <h1>{{ trans_choice_canvastack('ui.pluralization.items', 5) }}</h1>
    <p>{{ format_date_locale(now()) }}</p>
    <p>{{ format_currency_locale(1234.56) }}</p>
</body>
</html>
```

---

## Best Practices

1. **Always use helper functions** instead of direct class instantiation
2. **Use Blade directives** for conditional RTL/LTR rendering
3. **Use logical properties** (start/end) instead of physical (left/right)
4. **Test with multiple locales** including RTL languages
5. **Use CSS utilities** for RTL-aware styling
6. **Configure formats** in config file, not hardcoded
7. **Use translation keys** for pluralization, not hardcoded strings

---

## Future Enhancements

Potential future improvements:

1. More locale support (Chinese, Japanese, Korean, etc.)
2. ICU MessageFormat support
3. Locale-specific sorting (collation)
4. Locale-specific validation rules
5. Automatic translation management tools
6. Translation memory and suggestions
7. Real-time translation updates
8. Translation versioning

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Implemented
