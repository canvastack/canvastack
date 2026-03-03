# Form Component Field Types

Complete reference for all field types available in the CanvaStack Enhanced Form Component.

## Table of Contents

1. [Text-Based Fields](#text-based-fields)
2. [Selection Fields](#selection-fields)
3. [Date/Time Fields](#datetime-fields)
4. [File Upload](#file-upload)
5. [Advanced Fields](#advanced-fields)
6. [Hidden Field](#hidden-field)

---

## Text-Based Fields

### TextField

Standard text input field.

**Usage:**
```php
$form->text('username', 'Username')
    ->required()
    ->maxLength(50)
    ->placeholder('Enter username')
    ->icon('user');
```

**Methods:**
- `maxLength(int)` - Maximum character length
- `minLength(int)` - Minimum character length
- `placeholder(string)` - Placeholder text
- `icon(string, string)` - Icon with position

---

### EmailField

Email input with validation.

**Usage:**
```php
$form->email('email', 'Email Address')
    ->required()
    ->placeholder('you@example.com')
    ->icon('mail');
```

**Features:**
- Automatic email validation
- Browser email keyboard on mobile
- Email format checking

---

### PasswordField

Password input with masking.

**Usage:**
```php
$form->password('password', 'Password')
    ->required()
    ->minLength(8)
    ->icon('lock')
    ->help('Minimum 8 characters');
```

**Features:**
- Automatic password masking
- Strength indicator (optional)
- Confirmation field support

---

### NumberField

Numeric input with constraints.

**Usage:**
```php
$form->number('quantity', 'Quantity')
    ->min(1)
    ->max(100)
    ->step(1)
    ->required();
```

**Methods:**
- `min(float)` - Minimum value
- `max(float)` - Maximum value
- `step(float)` - Step increment

---

### TextareaField

Multi-line text input.

**Usage:**
```php
$form->textarea('description', 'Description')
    ->rows(5)
    ->maxLength(500)
    ->placeholder('Enter description...');
```

**Methods:**
- `rows(int)` - Number of visible rows
- `maxLength(int)` - Character limit with counter
- `ckeditor(array)` - Enable WYSIWYG editor

**CKEditor Integration:**
```php
$form->textarea('content', 'Content')
    ->ckeditor([
        'toolbar' => 'full', // minimal, default, full
        'height' => 500,
        'imageUpload' => true
    ]);
```

---

## Selection Fields

### SelectField

Dropdown selection.

**Usage:**
```php
$form->select('status', 'Status', [
    'active' => 'Active',
    'inactive' => 'Inactive'
])
->searchable()
->required();
```

**Methods:**
- `searchable(array)` - Enable search
- `multiple(bool)` - Multiple selection
- `placeholder(string)` - Placeholder text

**Searchable Select:**
```php
$form->select('country', 'Country', $countries)
    ->searchable()
    ->placeholder('Search for a country...');
```

**Multiple Selection:**
```php
$form->select('skills', 'Skills', $skills)
    ->searchable()
    ->multiple();
```

---

### CheckboxField

Checkbox input (single or multiple).

**Single Checkbox:**
```php
$form->checkbox('terms', 'Terms', ['1' => 'I agree'])
    ->required();
```

**Multiple Checkboxes:**
```php
$form->checkbox('interests', 'Interests', [
    'sports' => 'Sports',
    'music' => 'Music',
    'reading' => 'Reading'
])
->inline();
```

**Switch Toggle:**
```php
$form->checkbox('is_active', 'Active')
    ->switch('md', 'success');
```

**Methods:**
- `switch(string, string)` - Render as toggle (size, color)
- `inline(bool)` - Display inline
- `checked(mixed)` - Pre-checked value

---

### RadioField

Radio button selection.

**Usage:**
```php
$form->radio('gender', 'Gender', [
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other'
])
->inline()
->required();
```

**Methods:**
- `inline(bool)` - Display inline
- `checked(mixed)` - Pre-checked value

---

## Date/Time Fields

### DateField

Date picker.

**Usage:**
```php
$form->date('birth_date', 'Birth Date')
    ->format('Y-m-d')
    ->minDate('1900-01-01')
    ->maxDate(date('Y-m-d'))
    ->required();
```

**Methods:**
- `format(string)` - Date format
- `minDate(string)` - Minimum date
- `maxDate(string)` - Maximum date

---

### DateTimeField

Date and time picker.

**Usage:**
```php
$form->datetime('appointment', 'Appointment')
    ->format('Y-m-d H:i:s')
    ->minDate(date('Y-m-d'))
    ->required();
```

---

### TimeField

Time picker.

**Usage:**
```php
$form->time('start_time', 'Start Time')
    ->format('H:i:s')
    ->required();
```

---

### DateRangeField

Date range picker (NEW).

**Usage:**
```php
$form->daterange('period', 'Period')
    ->ranges([
        'Last 7 Days' => [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')],
        'Last 30 Days' => [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')]
    ])
    ->format('Y-m-d')
    ->required();
```

**Methods:**
- `ranges(array)` - Predefined ranges
- `enableTime(bool)` - Enable time selection
- `minDate(string)` - Minimum date
- `maxDate(string)` - Maximum date

---

### MonthField

Month picker (NEW).

**Usage:**
```php
$form->month('billing_month', 'Billing Month')
    ->format('F Y')
    ->minMonth('2020-01')
    ->maxMonth(date('Y-m'))
    ->required();
```

**Methods:**
- `format(string)` - Month format
- `minMonth(string)` - Minimum month (Y-m)
- `maxMonth(string)` - Maximum month (Y-m)

---

## File Upload

### FileField

File upload with validation.

**Basic Upload:**
```php
$form->file('document', 'Document')
    ->accept('.pdf,.doc,.docx')
    ->maxSize(5120)
    ->required();
```

**Image Upload with Preview:**
```php
$form->file('avatar', 'Profile Picture')
    ->accept('image/*')
    ->imagepreview()
    ->maxSize(2048);
```

**Multiple Files:**
```php
$form->file('attachments', 'Attachments')
    ->accept('.pdf,.jpg,.png')
    ->multiple()
    ->maxSize(10240);
```

**Methods:**
- `accept(string)` - Accepted file types
- `maxSize(int)` - Maximum size in KB
- `imagepreview(bool)` - Enable image preview
- `multiple(bool)` - Multiple file selection

---

## Advanced Fields

### TagsField

Tags input with autocomplete (NEW).

**Basic Tags:**
```php
$form->tags('keywords', 'Keywords')
    ->maxTags(10)
    ->placeholder('Add keywords...');
```

**With Suggestions:**
```php
$form->tags('skills', 'Skills')
    ->suggestions(['PHP', 'Laravel', 'JavaScript'])
    ->maxTags(15);
```

**Ajax Suggestions:**
```php
$form->tags('products', 'Products')
    ->ajaxSuggestions(route('api.products.suggest'), 2)
    ->maxTags(20);
```

**Methods:**
- `maxTags(int)` - Maximum tag count
- `suggestions(array)` - Predefined suggestions
- `ajaxSuggestions(string, int)` - Ajax endpoint
- `pattern(string)` - Validation pattern
- `delimiter(string)` - Tag delimiter

---

## Hidden Field

### HiddenField

Hidden input field.

**Usage:**
```php
$form->hidden('user_id', auth()->id());
$form->hidden('token', csrf_token());
```

---

## Field Comparison

| Field Type | Input Type | Validation | Special Features |
|------------|-----------|------------|------------------|
| TextField | text | length, pattern | Character counter, icons |
| EmailField | email | email format | Email validation |
| PasswordField | password | length | Password masking |
| NumberField | number | min, max, step | Numeric constraints |
| TextareaField | textarea | length | CKEditor, rows |
| SelectField | select | required | Searchable, multiple |
| CheckboxField | checkbox | required | Switch toggle, inline |
| RadioField | radio | required | Inline display |
| FileField | file | type, size | Preview, multiple |
| DateField | date | date range | Date picker |
| DateTimeField | datetime | date range | Date/time picker |
| TimeField | time | time format | Time picker |
| DateRangeField | text | date range | Range selection |
| MonthField | text | month range | Month picker |
| TagsField | text | count, pattern | Autocomplete, Ajax |
| HiddenField | hidden | - | Hidden value |

---

## Browser Support

All field types support modern browsers:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

Mobile support:
- iOS Safari 14+
- Chrome Mobile 90+
- Samsung Internet 14+

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-25  
**Status**: Production Ready
