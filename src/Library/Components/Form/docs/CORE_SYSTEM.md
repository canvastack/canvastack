# Core System - Objects.php

## 🏛️ Overview

Class `Objects` adalah jantung dari Form System CanvaStack yang bertindak sebagai orchestrator untuk semua operasi form. Class ini menggunakan semua trait elements dan mengelola lifecycle form dari initialization hingga rendering.

## 🎯 Responsibilities

- **Form Lifecycle Management**: Open, model binding, close
- **Element Collection**: Menyimpan dan mengelola semua form elements
- **Parameter Management**: Mengatur konfigurasi setiap element
- **Model Binding**: Automatic data population dari Eloquent models
- **Route Detection**: Smart URL generation berdasarkan current route
- **Validation Integration**: Laravel validation dengan visual indicators
- **Rendering Pipeline**: Final HTML assembly dan output

## 🔧 Class Structure

```php
class Objects
{
    use Text, DateTime, Select, File, Check, Radio, Tab;
    
    // Core Properties
    public $model;                    // Model binding object
    public $elements = [];            // HTML elements collection
    public $element_name = [];        // Element name mapping
    public $element_plugins = [];     // Plugin configurations
    public $params = [];              // Element parameters
    public $validations = [];         // Validation rules
    
    // Internal Properties
    private $currentRoute;            // Current route info
    private $currentRouteArray;       // Route segments
    public $currentRouteName;         // Route name (create/edit/show)
    private $method = 'PUT';          // Default HTTP method
    public $identity = null;          // Form identity
    public $modelToView = false;      // View-only mode flag
}
```

## 📋 Public Methods

### Form Lifecycle

#### `__construct()`
```php
public function __construct()
```
**Purpose**: Initialize form object dan detect current route  
**Actions**:
- Call `getCurrentRoute()` untuk route detection
- Setup internal properties

#### `open($path, $method, $type, $file)`
```php
public function open($path = false, $method = false, $type = false, $file = false)
```
**Purpose**: Membuka form tag dengan konfigurasi  
**Parameters**:
- `$path`: Target URL/route/action (auto-detect jika false)
- `$method`: HTTP method (POST default)
- `$type`: URL type (url/route/action - auto-detect)
- `$file`: Multipart file support (false default)

**Example**:
```php
// Basic form
$form->open();

// Custom route
$form->open('user.store', 'POST', 'route');

// With file upload
$form->open('user.store', 'POST', 'route', true);
```

#### `model($model, $row_selected, $path, $file, $type)`
```php
public function model($model = null, $row_selected = false, $path = false, $file = false, $type = false)
```
**Purpose**: Form dengan model binding untuk CRUD operations  
**Parameters**:
- `$model`: Eloquent model instance atau class name
- `$row_selected`: ID untuk edit mode (auto-detect dari URL)
- `$path`: Custom action path
- `$file`: File upload support
- `$type`: URL type

**Example**:
```php
// Create form
$form->model(User::class);

// Edit form
$form->model($user, $user->id, 'user.update');

// View mode
$form->model($user, $user->id, false); // false = view mode
```

#### `modelWithFile($model, $row_selected, $path, $type)`
```php
public function modelWithFile($model = null, $row_selected = false, $path = false, $type = false)
```
**Purpose**: Shortcut untuk model form dengan file upload enabled  
**Note**: Equivalent to `model()` dengan `$file = true`

#### `close($action_buttons, $option_buttons, $prefix, $suffix)`
```php
public function close($action_buttons = false, $option_buttons = false, $prefix = false, $suffix = false)
```
**Purpose**: Menutup form dan menambahkan action buttons  
**Parameters**:
- `$action_buttons`: Button label (false = no button)
- `$option_buttons`: Custom button attributes
- `$prefix`: HTML before button
- `$suffix`: HTML after button

**Example**:
```php
// Default submit button
$form->close('Save');

// Custom button
$form->close('Update', ['class' => 'btn btn-primary']);

// No button (view mode)
$form->close(false);
```

### Element Management

#### `draw($data)`
```php
public function draw($data = [])
```
**Purpose**: Menambahkan HTML element ke collection  
**Usage**: Internal method yang dipanggil oleh semua element methods

#### `render($object)`
```php
public function render($object)
```
**Purpose**: Final rendering dan assembly HTML output  
**Returns**: String HTML atau array untuk tab system  
**Logic**:
- Detect tab system dengan marker `--[openTabHTMLForm]--`
- Call `renderTab()` jika ada tabs
- Return raw HTML jika tidak ada tabs

### Validation & Configuration

#### `setValidations($data)`
```php
public function setValidations($data = [])
```
**Purpose**: Set validation rules untuk form fields  
**Parameter**: `$data` - Array dengan format `['field' => 'rules']`

**Example**:
```php
$form->setValidations([
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
    'name' => 'required|string|max:255'
]);
```

#### `addAttributes($attributes)`
```php
public function addAttributes($attributes = [])
```
**Purpose**: Menambahkan attributes global untuk element berikutnya  
**Parameter**: `$attributes` - Array attributes yang akan ditambahkan

**Example**:
```php
$form->addAttributes(['data-toggle' => 'tooltip']);
$form->text('username'); // Will have tooltip attribute
```

#### `sync($source_field, $target_field, $values, $labels, $query, $selected)`
```php
public function sync(string $source_field, string $target_field, string $values, string $labels = null, string $query, $selected = null)
```
**Purpose**: Ajax relational fields untuk dependent select boxes  
**Parameters**:
- `$source_field`: Source select field name
- `$target_field`: Target field yang akan di-update
- `$values`: Column untuk option values
- `$labels`: Column untuk option labels
- `$query`: SQL query untuk data source
- `$selected`: Default selected value

**Example**:
```php
$form->selectbox('province_id', $provinces);
$form->sync(
    'province_id', 
    'city_id', 
    'id', 
    'name', 
    'SELECT id, name FROM cities WHERE province_id = :province_id'
);
$form->selectbox('city_id', []); // Will be populated via Ajax
```

### Utility Methods

#### `method($method)`
```php
public function method($method)
```
**Purpose**: Set HTTP method untuk form  
**Example**:
```php
$form->method('PATCH');
$form->model($user, $user->id);
```

#### `token()`
```php
public function token()
```
**Purpose**: Add CSRF token field  
**Usage**: Automatic dalam `open()` dan `model()`

#### `label($name, $value, $attributes)`
```php
public function label($name, $value, $attributes = [])
```
**Purpose**: Create form label dengan styling  
**Returns**: HTML label dengan Bootstrap classes

## 🔄 Internal Methods (Protected/Private)

### Route Management

#### `getCurrentRoute()`
```php
protected function getCurrentRoute()
```
**Purpose**: Detect dan parse current route information  
**Actions**:
- Get current route name
- Split route segments
- Determine route type (create/edit/show)

#### `setActionRoutePath()`
```php
private function setActionRoutePath()
```
**Purpose**: Auto-generate form action URL  
**Logic**:
- `create` route → `store` route
- `edit` route → `update` route
- Default → current route

### Parameter Processing

#### `setParams($function_name, $name, $value, $attributes, $label, $selected)`
```php
private function setParams($function_name, $name, $value, $attributes, $label, $selected = false)
```
**Purpose**: Process dan store element parameters  
**Actions**:
- Generate label dari field name jika `$label = true`
- Merge dengan global attributes
- Apply validation attributes
- Store dalam `$this->params` array

#### `setModelValueAndSelectedToParams($function_name, $name, $value, $selected)`
```php
private function setModelValueAndSelectedToParams($function_name, $name, $value, $selected)
```
**Purpose**: Bind model values ke form elements  
**Logic**:
- **Create mode**: Use provided values
- **Edit mode**: Load dari model data
- **View mode**: Display model values
- **Special handling**: Checkbox arrays, radio selections

### HTML Generation

#### `inputDraw($function_name, $name)`
```php
private function inputDraw($function_name, $name)
```
**Purpose**: Generate complete form group HTML  
**Structure**:
```html
<div class="form-group row">
    <label class="col-sm-3 control-label">Field Label *</label>
    <div class="input-group col-sm-9">
        <!-- Input element -->
    </div>
</div>
```

#### `inputTag($function_name, $name, $attributes, $value)`
```php
private function inputTag($function_name, $name, $attributes, $value)
```
**Purpose**: Generate actual input HTML berdasarkan element type  
**Returns**: Wrapped input element dengan Bootstrap styling

### Model Integration

#### `getModelValue($field_name, $function_name)`
```php
private function getModelValue($field_name, $function_name)
```
**Purpose**: Extract field value dari bound model  
**Features**:
- Soft delete support dengan `withTrashed()`
- Route-based record detection
- Safe null handling

#### `alert_message($data)`
```php
private function alert_message($data = [])
```
**Purpose**: Display session messages dan validation errors  
**Features**:
- Success/error message styling
- Validation attribute processing
- Session data integration

## 🗃️ Data Structures

### Elements Array
```php
$this->elements = [
    0 => '<form method="POST" action="...">',
    1 => '<div class="form-group">...</div>',
    2 => '<div class="form-group">...</div>',
    3 => '</form>'
];
```

### Parameters Array
```php
$this->params = [
    'text' => [
        'username' => [
            'label' => 'Username',
            'value' => 'john_doe',
            'selected' => null,
            'attributes' => ['class' => 'form-control', 'required' => true]
        ]
    ],
    'select' => [
        'status' => [
            'label' => 'Status',
            'value' => ['active' => 'Active', 'inactive' => 'Inactive'],
            'selected' => 'active',
            'attributes' => ['class' => 'form-control chosen-select']
        ]
    ]
];
```

### Element Names Mapping
```php
$this->element_name = [
    'username' => 'text',
    'email' => 'email',
    'status' => 'select',
    'avatar' => 'file'
];
```

## ⚡ Usage Patterns

### Basic Form
```php
$form = new Objects();
$form->open();
$form->text('name', null, ['required'], true);
$form->email('email', null, ['required'], true);
$form->close('Submit');

echo $form->render($form->elements);
```

### CRUD Form
```php
// Create
$form = new Objects();
$form->model(User::class);
$form->text('name', null, ['required'], true);
$form->close('Create User');

// Edit (auto-detect ID dari URL)
$form = new Objects();
$form->model(User::class);
$form->text('name', null, ['required'], true);  // Auto-filled dengan data existing
$form->close('Update User');
```

### Advanced Form dengan File Upload
```php
$form = new Objects();
$form->setValidations([
    'name' => 'required|string|max:255',
    'avatar' => 'required|image|max:2048'
]);

$form->modelWithFile($user, $user->id);
$form->text('name', null, ['required'], true);
$form->file('avatar', ['imagepreview'], true);
$form->close('Update Profile');

echo $form->render($form->elements);
```

## 🔧 Integration Points

### Laravel Collective HTML
- Menggunakan `Form::` dan `Html::` facades
- Bootstrap styling integration
- CSRF protection otomatis

### Eloquent Models
- Model binding dengan auto-detection
- Soft delete support
- Relationship handling

### Validation System
- Laravel validation rules
- Visual required indicators
- Error message display

### File Upload
- Integration dengan `File` trait
- Thumbnail generation
- Upload path management

---

**Next**: [Form Elements Documentation](./FORM_ELEMENTS.md)