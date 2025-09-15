
# API Reference - Form System CanvaStack

## 🎯 Overview

Dokumentasi lengkap semua public dan protected methods yang tersedia dalam Form System CanvaStack, terorganisir berdasarkan class dan trait.

---

## 📋 Objects Class - Core Methods

### Form Lifecycle

#### `__construct()`
```php
public function __construct()
```
- **Purpose**: Initialize form object dan setup route detection
- **Returns**: `void`
- **Called Automatically**: Yes
- **Dependencies**: `getCurrentRoute()`

#### `open($path, $method, $type, $file)`
```php
public function open($path = false, $method = false, $type = false, $file = false)
```
- **Purpose**: Open form tag dengan configuration
- **Parameters**:
  - `$path` (string|false): Target URL/route/action (auto-detect jika false)
  - `$method` (string|false): HTTP method ('POST', 'GET', 'PUT', 'DELETE')
  - `$type` (string|false): URL type ('url', 'route', 'action') - auto-detect
  - `$file` (bool): Enable multipart/form-data untuk file uploads
- **Returns**: `void` (adds to elements array)
- **Example**: `$form->open(route('users.store'), 'POST', 'route', true);`

#### `model($model, $row_selected, $path, $file, $type)`
```php
public function model($model = null, $row_selected = false, $path = false, $file = false, $type = false)
```
- **Purpose**: Form dengan model binding untuk CRUD operations
- **Parameters**:
  - `$model` (mixed): Eloquent model instance, class name, atau null
  - `$row_selected` (int|false): Record ID untuk edit mode
  - `$path` (string|false): Custom action path
  - `$file` (bool): File upload support
  - `$type` (string|false): URL type
- **Returns**: `void`
- **Features**: Auto-detect edit ID, model encryption, soft delete support
- **Example**: `$form->model(User::class, $user->id, route('users.update', $user));`

#### `modelWithFile($model, $row_selected, $path, $type)`
```php
public function modelWithFile($model = null, $row_selected = false, $path = false, $type = false)
```
- **Purpose**: Shortcut untuk model form dengan file upload enabled
- **Parameters**: Same as `model()` except `$file` automatically set to `true`
- **Returns**: `void`
- **Example**: `$form->modelWithFile($user, $user->id);`

#### `close($action_buttons, $option_buttons, $prefix, $suffix)`
```php
public function close($action_buttons = false, $option_buttons = false, $prefix = false, $suffix = false)
```
- **Purpose**: Close form dan add action buttons
- **Parameters**:
  - `$action_buttons` (string|false): Button label
  - `$option_buttons` (array|false): Button attributes
  - `$prefix` (string|false): HTML before button
  - `$suffix` (string|false): HTML after button
- **Returns**: `void`
- **Default Button**: `btn btn-success btn-slideright pull-right btn_create`
- **Example**: `$form->close('Save Changes', ['class' => 'btn btn-primary']);`

### Configuration Methods

#### `setValidations($data)`
```php
public function setValidations($data = [])
```
- **Purpose**: Set Laravel validation rules untuk form fields
- **Parameters**: `$data` (array): Validation rules `['field' => 'rules']`
- **Returns**: `void`
- **Integration**: Applied automatically during rendering
- **Example**: `$form->setValidations(['email' => 'required|email|unique:users']);`

#### `addAttributes($attributes)`
```php
public function addAttributes($attributes = [])
```
- **Purpose**: Add global attributes untuk element berikutnya
- **Parameters**: `$attributes` (array): HTML attributes
- **Returns**: `void`
- **Scope**: Applied to next element only, then reset
- **Example**: `$form->addAttributes(['data-toggle' => 'tooltip']);`

#### `method($method)`
```php
public function method($method)
```
- **Purpose**: Set HTTP method untuk form
- **Parameters**: `$method` (string): HTTP method
- **Returns**: `void`
- **Default**: `PUT`
- **Example**: `$form->method('PATCH');`

### Utility Methods

#### `draw($data)`
```php
public function draw($data = [])
```
- **Purpose**: Add HTML element ke collection
- **Parameters**: `$data` (string|array): HTML content
- **Returns**: `void`
- **Usage**: Internal method, called by all element methods

#### `render($object)`
```php
public function render($object)
```
- **Purpose**: Final rendering dan HTML assembly
- **Parameters**: `$object` (array|string): Elements to render
- **Returns**: `string|array` (string untuk regular forms, array untuk tabs)
- **Features**: Tab detection, HTML assembly
- **Example**: `echo $form->render($form->elements);`

#### `token()`
```php
public function token()
```
- **Purpose**: Add CSRF token field
- **Returns**: `void`
- **Usage**: Called automatically dalam `open()` dan `model()`

#### `label($name, $value, $attributes)`
```php
public function label($name, $value, $attributes = [])
```
- **Purpose**: Create form label dengan Bootstrap styling
- **Parameters**:
  - `$name` (string): Field name
  - `$value` (string): Label text
  - `$attributes` (array): Label attributes
- **Returns**: `string` (HTML label)
- **CSS Classes**: `col-sm-3 control-label`

#### `sync($source_field, $target_field, $values, $labels, $query, $selected)`
```php
public function sync(string $source_field, string $target_field, string $values, string $labels = null, string $query, $selected = null)
```
- **Purpose**: Ajax relational fields untuk dependent select boxes
- **Parameters**:
  - `$source_field` (string): Source select field name
  - `$target_field` (string): Target field yang akan di-update
  - `$values` (string): Column untuk option values
  - `$labels` (string): Column untuk option labels  
  - `$query` (string): SQL query dengan parameter binding
  - `$selected` (mixed): Default selected value
- **Returns**: `void`
- **Security**: All parameters encrypted before transmission
- **Example**: `$form->sync('province_id', 'city_id', 'id', 'name', 'SELECT id, name FROM cities WHERE province_id = :province_id');`

---

## 📝 Text Trait Methods

#### `text($name, $value, $attributes, $label)`
```php
public function text($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Standard text input field
- **Parameters**:
  - `$name` (string): Field name
  - `$value` (string|null): Default value
  - `$attributes` (array): HTML attributes
  - `$label` (bool|string): Auto-generate label (true) atau custom label
- **CSS Classes**: `form-control` (auto-applied)
- **Example**: `$form->text('username', 'john_doe', ['required', 'maxlength' => 50], 'Username');`

#### `textarea($name, $value, $attributes, $label)`
```php
public function textarea($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Multi-line text input dengan features
- **Special Features**:
  - **Character Limit**: Format `field_name|limit:100`
  - **CKEditor**: Attribute `ckeditor` dalam class
  - **MaxLength**: Bootstrap character counter
- **Example**: 
  ```php
  $form->textarea('bio|limit:500', null, [], 'Biography');
  $form->textarea('content', null, ['class' => 'ckeditor'], 'Content');
  ```

#### `email($name, $value, $attributes, $label)`
```php
public function email($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Email input dengan HTML5 validation
- **HTML Type**: `email`
- **Example**: `$form->email('email_address', null, ['required'], 'Email Address');`

#### `number($name, $value, $attributes, $label)`
```php
public function number($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Numeric input field
- **HTML Type**: `number`
- **Attributes**: Support `min`, `max`, `step`
- **Example**: `$form->number('age', null, ['min' => 0, 'max' => 120], 'Age');`

#### `password($name, $attributes, $label)`
```php
public function password($name, $attributes = [], $label = true)
```
- **Purpose**: Password input (no value parameter untuk security)
- **HTML Type**: `password`
- **Security**: No value pre-population
- **Example**: `$form->password('password', ['required', 'minlength' => 8], 'Password');`

#### `tags($name, $value, $attributes, $label)`
```php
public function tags($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Tags input dengan bootstrap-tagsinput plugin
- **Auto Attributes**: 
  - `data-role="tagsinput"`
  - `placeholder="Type {Field Name}"`
- **Example**: `$form->tags('skills', 'PHP,Laravel,JavaScript', [], 'Skills');`

---

## 📅 DateTime Trait Methods

#### `date($name, $value, $attributes, $label)`
```php
public function date($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Date picker input
- **Auto CSS Class**: `date-picker`
- **Example**: `$form->date('birth_date', '1990-01-15', [], 'Birth Date');`

#### `datetime($name, $value, $attributes, $label)`
```php
public function datetime($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Date and time picker input
- **Auto CSS Class**: `datetime-picker`
- **Example**: `$form->datetime('appointment', now(), [], 'Appointment Time');`

#### `daterange($name, $value, $attributes, $label)`
```php
public function daterange($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Date range picker input
- **Auto CSS Class**: `daterange-picker`
- **Format**: `YYYY-MM-DD - YYYY-MM-DD`
- **Example**: `$form->daterange('report_period', '2024-01-01 - 2024-12-31', [], 'Period');`

#### `time($name, $value, $attributes, $label)`
```php
public function time($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Time picker input
- **Auto CSS Class**: `bootstrap-timepicker`
- **Format**: `HH:MM`
- **Example**: `$form->time('start_time', '09:00', [], 'Start Time');`

---

## 📋 Select Trait Methods

#### `selectbox($name, $values, $selected, $attributes, $label, $set_first_value)`
```php
public function selectbox($name, $values = [], $selected = false, $attributes = [], $label = true, $set_first_value = [null => ''])
```
- **Purpose**: Dropdown select dengan Chosen.js integration
- **Parameters**:
  - `$values` (array): Options `['value' => 'label']`
  - `$selected` (mixed): Selected value(s)
  - `$set_first_value` (array|false): First option atau false untuk disable
- **Auto CSS Classes**: `chosen-select-deselect chosen-selectbox`
- **Example**: 
  ```php
  $form->selectbox('status', ['active' => 'Active', 'inactive' => 'Inactive'], 'active');
  $form->selectbox('roles[]', $roles, [1, 3], ['multiple'], 'Roles');
  ```

#### `month($name, $value, $attributes, $label)`
```php
public function month($name, $value = null, $attributes = [], $label = true)
```
- **Purpose**: Month picker select (1-12)
- **Auto CSS Classes**: `chosen-select-deselect chosen-selectbox`
- **Example**: `$form->month('birth_month', 6, [], 'Birth Month');`

---

## 📁 File Trait Methods

#### `file($name, $attributes, $label)`
```php
public function file($name, $attributes = [], $label = true)
```
- **Purpose**: File input dengan Bootstrap styling
- **Special Attributes**:
  - `imagepreview`: Enable image preview
  - `value`: Existing file path untuk preview
- **Example**: `$form->file('avatar', ['imagepreview'], 'Profile Picture');`

#### `fileUpload($upload_path, $request, $fileInfo)`
```php
public function fileUpload($upload_path, $request, $fileInfo)
```
- **Purpose**: Process file upload dengan validation dan thumbnails
- **Parameters**:
  - `$upload_path` (string): Base upload directory
  - `$request` (Request): Laravel Request object
  - `$fileInfo` (array): File configuration per field
- **Returns**: `void` (populates `$this->getFileUploads`)
- **Features**: Thumbnail generation, validation, directory creation
- **Example**:
  ```php
  $fileInfo = [
      'avatar' => [
          'file_validation' => 'required|image|max:2048',
          'thumb_size' => [200, 200]
      ]
  ];
  $form->fileUpload('users', $request, $fileInfo);
  ```

---

## ☑️ Check Trait Methods

#### `checkbox($name, $values, $selected, $attributes, $label)`
```php
public function checkbox($name, $values = [], $selected = [], $attributes = [], $label = true)
```
- **Purpose**: Checkbox atau switch elements
- **Parameters**:
  - `$values` (array): Options `['value' => 'label']`
  - `$selected` (array): Selected values
  - `$attributes` (array): HTML attributes + special `check_type`
- **Special Attributes**:
  - `check_type => 'switch'`: Render as toggle switch
  - `check_type => 'primary|success|danger|warning'`: Color variants
- **Example**:
  ```php
  $form->checkbox('interests', [
      'programming' => 'Programming',
      'design' => 'Design'
  ], ['programming'], ['check_type' => 'primary'], 'Interests');
  ```

---

## 🔘 Radio Trait Methods

#### `radiobox($name, $values, $selected, $attributes, $label)`
```php
public function radiobox($name, $values = [], $selected = false, $attributes = [], $label = true)
```
- **Purpose**: Radio button group
- **Parameters**:
  - `$values` (array): Options `['value' => 'label']`
  - `$selected` (mixed): Single selected value
  - `$attributes` (array): HTML attributes + `radio_type`
- **Special Attributes**: `radio_type => 'primary|success|danger|warning'`
- **Example**:
  ```php
  $form->radiobox('gender', [
      'male' => 'Male',
      'female' => 'Female'
  ], 'male', ['radio_type' => 'primary'], 'Gender');
  ```

---

## 📑 Tab Trait Methods

#### `openTab($label, $class)`
```php
public function openTab($label, $class = false)
```
- **Purpose**: Start new tab section
- **Parameters**:
  - `$label` (string): Tab title
  - `$class` (string|false): FontAwesome icon class
- **Returns**: `void`
- **Example**: `$form->openTab('Personal Info', 'fa-user');`

#### `closeTab()`
```php
public function closeTab()
```
- **Purpose**: Close current tab section
- **Returns**: `void`
- **Usage**: Must be called setelah `openTab()`

#### `addTabContent($content)`
```php
public function addTabContent($content)
```
- **Purpose**: Add custom HTML content ke current tab
- **Parameters**: `$content` (string): HTML content
- **Returns**: `void`
- **Example**: `$form->addTabContent('<div class="alert alert-info">Note</div>');`

#### `renderTab($object)`
```php
public function renderTab($object)
```
- **Purpose**: Convert tab markers menjadi Bootstrap tab structure
- **Parameters**: `$object` (string|array): Form content dengan tab markers
- **Returns**: `array` (Bootstrap tab HTML structure)
- **Internal Method**: Called automatically by `render()`

---

## 🛠️ Helper Functions API

### Attribute Helpers

#### `canvastack_form_check_str_attr($attributes, $string)`
```php
function canvastack_form_check_str_attr($attributes, $string)
```
- **Purpose**: Check jika string ada dalam class atau id attribute
- **Parameters**:
  - `$attributes` (array): HTML attributes
  - `$string` (string): String to search
- **Returns**: `bool`
- **Example**: `canvastack_form_check_str_attr(['class' => 'ckeditor'], 'ckeditor')`

#### `canvastack_form_change_input_attribute($attribute, $key, $value)`
```php
function canvastack_form_change_input_attribute($attribute, $key = false, $value = false)
```
- **Purpose**: Add atau merge attributes dengan class handling
- **Parameters**:
  - `$attribute` (array): Existing attributes
  - `$key` (string): Attribute name
  - `$value` (string): Attribute value
- **Returns**: `array` (merged attributes)
- **Special**: Class values di-merge dengan spaces

#### `canvastack_form_set_icon_attributes($string, $attributes, $pos)`
```php
function canvastack_form_set_icon_attributes($string, $attributes = [], $pos = 'left')
```
- **Purpose**: Parse icon configuration dari field name
- **Format**: `field_name|icon_name|position`
- **Returns**: `array` dengan name dan icon config
- **Example**: `canvastack_form_set_icon_attributes('username|user|left')`

### UI Component Helpers

#### `canvastack_form_button($name, $label, $action, $tag, $link, $color, $border, $size, $disabled, $icon_name, $icon_color)`
```php
function canvastack_form_button($name, $label = false, ...)
```
- **Purpose**: Generate custom buttons dengan styling
- **Parameters**:
  - `$name` (string): Button name/class suffix
  - `$label` (string): Button text
  - `$action` (array): Additional attributes
  - `$tag` (string): HTML tag ('button', 'a', 'input')
  - `$link` (string): URL untuk anchor tags
  - `$color` (string): Bootstrap color
  - `$icon_name` (string): FontAwesome icon
- **Returns**: `string` (HTML button)

#### `canvastack_form_checkList($name, $value, $label, $checked, $class, $id, $inputNode)`
```php
function canvastack_form_checkList($name, $value = false, ...)
```
- **Purpose**: Generate single checkbox dengan Bootstrap styling
- **Returns**: `string` (HTML checkbox)
- **Use Case**: Quick checkbox creation

#### `canvastack_form_selectbox($name, $values, $selected, $attributes, $label, $set_first_value)`
```php
function canvastack_form_selectbox($name, $values = [], ...)
```
- **Purpose**: Generate select box dengan Chosen.js
- **Returns**: `string` (HTML select)
- **Features**: Fallback manual generation jika Laravel Collective unavailable

#### `canvastack_form_alert_message($message, $type, $title, $prefix, $extra)`
```php
function canvastack_form_alert_message($message = 'Success', ...)
```
- **Purpose**: Generate Bootstrap alert messages
- **Parameters**:
  - `$message` (string|array): Message content atau validation errors
  - `$type` (string): Alert type ('success', 'warning', 'danger', 'info')
  - `$title` (string): Alert title
  - `$prefix` (string): FontAwesome icon class
- **Returns**: `string` (HTML alert)
- **Special**: Array messages untuk validation errors

### Tab System Helpers

#### `canvastack_form_create_header_tab($data, $pointer, $active, $class)`
```php
function canvastack_form_create_header_tab($data, $pointer, $active = false, $class = false)
```
- **Purpose**: Generate tab navigation header
- **Parameters**:
  - `$data` (string): Tab title
  - `$pointer` (string): Tab ID/href target
  - `$active` (string|false): Active CSS class
  - `$class` (string|false): Icon class
- **Returns**: `string` (HTML tab header)

#### `canvastack_form_create_content_tab($data, $pointer, $active)`
```php
function canvastack_form_create_content_tab($data, $pointer, $active = false)
```
- **Purpose**: Generate tab content pane
- **Parameters**:
  - `$data` (string): Tab content HTML
  - `$pointer` (string): Tab ID
  - `$active` (string|false): Active state classes
- **Returns**: `string` (HTML tab pane)

### Status & Configuration Helpers

#### `canvastack_form_active_box($en)`
```php
function canvastack_form_active_box($en = true)
```
- **Purpose**: Generate active/inactive options
- **Parameters**: `$en` (bool): Language (true = English, false = Indonesian)
- **Returns**: `array` - `[0 => 'No', 1 => 'Yes']` atau `[0 => 'Tidak', 1 => 'Ya']`

#### `canvastack_form_request_status($en, $num)`
```php
function canvastack_form_request_status($en = true, $num = false)
```
- **Purpose**: Generate request status options
- **Returns**: `array` - Status options (Pending, Accept, Blocked, Banned)

#### `canvastack_form_set_active_value($value)`
```php
function canvastack_form_set_active_value($value)
```
- **Purpose**: Convert numeric value ke active status text
- **Example**: `canvastack_form_set_active_value(1)` returns "Active"

#### `canvastack_form_get_client_ip()`
```php
function canvastack_form_get_client_ip()
```
- **Purpose**: Get real client IP dengan proxy detection
- **Returns**: `string` (IP address)

### Data Conversion Helpers

#### `canvastack_selectbox($object, $key_value, $key_label, $set_null_array)`
```php
function canvastack_selectbox($object, $key_value, $key_label, $set_null_array = true)
```
- **Purpose**: Convert object/array collection ke select options
- **Parameters**:
  - `$object` (Collection|array): Data source
  - `$key_value` (string): Field untuk option values
  - `$key_label` (string): Field untuk option labels
  - `$set_null_array` (bool): Include null option
- **Returns**: `array` (select options)
- **Example**: 
  ```php
  $users = User::all();
  $options = canvastack_selectbox($users, 'id', 'name');
  // Result: [1 => 'John Doe', 2 => 'Jane Smith']
  ```

---

## 🏛️ FormUi Static Methods

### Modern Utility Methods

#### `FormUi::button($name, $label, $action, ...)`
```php
public static function button($name, $label = false, $action = [], ...)
```
- **Purpose**: Modern button builder dengan legacy parity
- **Returns**: `string` (HTML button)
- **Features**: Icon support, custom attributes, flexible styling

#### `FormUi::checkList($name, $value, $label, $checked, $class, $id, $inputNode)`
```php
public static function checkList($name, $value = false, ...)
```
- **Purpose**: Static checkbox builder
- **Returns**: `string` (HTML checkbox)

#### `FormUi::selectbox($name, $values, $selected, $attributes, $label, $set_first_value)`
```php
public static function selectbox($name, $values = [], ...)
```
- **Purpose**: Static select builder dengan Laravel Collective fallback
- **Returns**: `string` (HTML select)

#### `FormUi::alertMessage($message, $type, $title, $prefix, $extra)`
```php
public static function alertMessage($message = 'Success', ...)
```
- **Purpose**: Static alert message builder
- **Returns**: `string` (HTML alert)
- **Features**: Array message support untuk validation

#### `FormUi::createHeaderTab($data, $pointer, $active, $class)`
```php
public static function createHeaderTab($data, $pointer, $active = false, $class = false)
```
- **Purpose**: Static tab header builder
- **Returns**: `string` (HTML tab header)

#### `FormUi::createContentTab($data, $pointer, $active)`
```php
public static function createContentTab($data, $pointer, $active = false)
```
- **Purpose**: Static tab content builder
- **Returns**: `string` (HTML tab pane)

#### `FormUi::changeInputAttribute($attribute, $key, $value)`
```php
public static function changeInputAttribute($attribute, $key = false, $value = false)
```
- **Purpose**: Static attribute merger
- **Returns**: `array` (merged attributes)
- **Features**: Array value handling untuk classes

---

## 🔧 Internal Methods Reference

### Route & Path Management

#### `getCurrentRoute()`
```php
protected function getCurrentRoute()
```
- **Access**: Protected
- **Purpose**: Parse current route information
- **Sets**: `$currentRoute`, `$currentRouteArray`, `$currentRouteName`

#### `setActionRoutePath()`
```php
private function setActionRoutePath()
```
- **Access**: Private
- **Purpose**: Auto-generate form action URL
- **Logic**: create→store, edit→update

### Parameter Processing

#### `setParams($function_name, $name, $value, $attributes, $label, $selected)`
```php
private function setParams($function_name, $name, $value, $attributes, $label, $selected = false)
```
- **Access**: Private
- **Purpose**: Process dan store element parameters
- **Side Effects**: Populates `$this->params`, calls model binding

#### `setModelValueAndSelectedToParams($function_name, $name, $value, $selected)`
```php
private function setModelValueAndSelectedToParams($function_name, $name, $value, $selected)
```
- **Access**: Private
- **Purpose**: Bind model values berdasarkan element type
- **Features**: Context-aware binding, special handling per element type

#### `getModelValue($field_name, $function_name)`
```php
private function getModelValue($field_name, $function_name)
```
- **Access**: Private
- **Purpose**: Extract field value dari bound model
- **Features**: Soft delete support, route-based record detection

### HTML Generation

#### `inputDraw($function_name, $name)`
```php
private function inputDraw($function_name, $name)
```
- **Access**: Private
- **Purpose**: Generate complete form group HTML
- **Features**: Bootstrap wrapper, label generation, validation indicators

#### `inputTag($function_name, $name, $attributes, $value)`
```php
private function inputTag($function_name, $name, $attributes, $value)
```
- **Access**: Private
- **Purpose**: Generate actual input HTML berdasarkan type
- **Returns**: `string` (wrapped input element)

#### `checkValidationAttributes($field_name, $current_attributes)`
```php
protected static function checkValidationAttributes($field_name, $current_attributes = [])
```
- **Access**: Protected Static
- **Purpose**: Apply validation attributes ke elements
- **Returns**: `array` (merged attributes dengan validation)

#### `alert_message($data)`
```php
private function alert_message($data = [])
```
- **Access**: Private
- **Purpose**: Process dan display session messages/errors
- **Features**: Validation error formatting, session integration

### File Processing

#### `getFileType($request, $input_name)`
```php
private function getFileType($request, $input_name)
```
- **Access**: Private
- **Purpose**: Detect MIME type dan extract file category
- **Returns**: `string` ('image', 'document', 'video', etc.)

#### `validationFile($request, $input_name, $validation)`
```php
private function validationFile($request, $input_name, $validation)
```
- **Access**: Private
- **Purpose**: Apply file-specific validation rules
- **Integration**: Laravel validation system

#### `setUploadPath($folder_name)`
```php
private function setUploadPath($folder_name)
```
- **Access**: Private
- **Purpose**: Generate absolute upload path
- **Returns**: `string` (full upload path)

#### `setAssetPath($path, $folder)`
```php
private function setAssetPath($path, $folder)
```
- **Access**: Private
- **Purpose**: Convert absolute path ke relative URL
- **Returns**: `string` (relative asset URL)

#### `fileUploadProcessor($request, $upload_path, $fileInfo, $use_time)`
```php
private function fileUploadProcessor($request, $upload_path, $fileInfo, $use_time = true)
```
- **Access**: Private
- **Purpose**: Core file upload processing
- **Features**: Directory creation, naming, validation, thumbnails

#### `createThumbImage($request, $inputname, $dataInfo, $upload_path)`
```php
private function createThumbImage($request, $inputname, $dataInfo, $upload_path)
```
- **Access**: Private
- **Purpose**: Generate image thumbnails
- **Features**: Aspect ratio preservation, quality optimization
- **Library**: Intervention Image v2/v3 support

### Element-Specific Renderers

#### `drawCheckBox($name, $value, $selected, $attributes)`
```php
private function drawCheckBox($name, $value, $selected, $attributes = [])
```
- **Access**: Private (Check Trait)
- **Purpose**: Render checkbox HTML dengan styling
- **Returns**: `string` (checkbox HTML)

#### `drawRadioBox($name, $value, $selected, $attributes)`
```php
private function drawRadioBox($name, $value, $selected, $attributes = [])
```
- **Access**: Private (Radio Trait)
- **Purpose**: Render radio button HTML dengan styling
- **Returns**: `string` (radio HTML)

#### `inputFile($name, $attributes)`
```php
private function inputFile($name, $attributes)
```
- **Access**: Private (File Trait)
- **Purpose**: Render file input dengan Bootstrap File Input
- **Returns**: `string` (file input HTML)
- **Features**: Image preview, existing file display

---



## 📊 Properties Reference

### Public Properties

#### `$model`
```php
public $model;
```
- **Type**: `mixed` (Eloquent model instance atau class name)
- **Purpose**: Model untuk form binding
- **Set By**: `model()` method atau external assignment

#### `$elements`
```php
public $elements = [];
```
- **Type**: `array`
- **Purpose**: Collection semua HTML elements
- **Structure**: `[0 => '<form...>', 1 => '<div...>', ...]`

#### `$element_name`
```php
public $element_name = [];
```
- **Type**: `array`
- **Purpose**: Mapping field names ke element types
- **Structure**: `['username' => 'text', 'status' => 'select']`

#### `$element_plugins`
```php
public $element_plugins = [];
```
- **Type**: `array`
- **Purpose**: Plugin configuration per field
- **Structure**: `['content' => 'ckeditor', 'tags' => 'tagsinput']`

#### `$params`
```php
public $params = [];
```
- **Type**: `array`
- **Purpose**: Parameter storage untuk semua elements
- **Structure**: `[$element_type][$field_name] = ['label', 'value', 'selected', 'attributes']`

#### `$validations`
```php
public $validations = [];
```
- **Type**: `array`
- **Purpose**: Laravel validation rules
- **Structure**: `['field_name' => 'validation_rules']`

#### `$identity`
```php
public $identity = null;
```
- **Type**: `string|null`
- **Purpose**: Encrypted form identity untuk security
- **Set By**: `model()` method

#### `$modelToView`
```php
public $modelToView = false;
```
- **Type**: `bool`
- **Purpose**: Flag untuk view-only mode
- **Usage**: Disable action buttons, make inputs readonly

#### `$currentRouteName`
```php
public $currentRouteName;
```
- **Type**: `string`
- **Purpose**: Current route name (create/edit/show)
- **Set By**: `getCurrentRoute()` method

### Private Properties

#### `$method`
```php
private $method = 'PUT';
```
- **Type**: `string`
- **Purpose**: Default HTTP method untuk model forms
- **Modifiable**: Via `method()` method

#### `$currentRoute`
```php
private $currentRoute;
```
- **Type**: `string`
- **Purpose**: Full current route name
- **Example**: `'users.edit'`

#### `$currentRouteArray`
```php
private $currentRouteArray;
```
- **Type**: `array`
- **Purpose**: Route segments
- **Example**: `['users', 'edit']`

### Trait-Specific Properties

#### File Trait Properties
```php
public $inputFiles = [];           // File input configurations
public $getFileUploads = [];       // Upload results
public $isFileType = false;        // File type detection flag
private $filePath = null;          // Current upload path
private $fileNameInfo = null;      // Current filename
private $thumbFolder = 'thumb';    // Thumbnail directory name
```

#### Tab Trait Properties
```php
private $opentabHTML = '--[openTabHTMLForm]--';      // Tab system marker
private $openNewTab = '--[openNewTab]--';            // Tab separator
private $openNewTabClass = '--[openNewTabClass]--';  // Icon separator
private $closedtabHTML = '--[closeTabHTMLForm]--';   // Tab closure marker
private $contentTab = null;                          // Tab content buffer
```

#### Parameter Properties
```php
private $paramValue = null;        // Processed values per element
private $paramSelected = null;     // Selected values per element
private $added_attributes = [];    // Global attributes buffer
```

---

## 🔍 Method Chaining Patterns

### Basic Chaining
```php
$form = new Objects();
$html = $form->open()
             ->text('name', null, ['required'], 'Name')
             ->email('email', null, ['required'], 'Email')
             ->close('Submit')
             ->render($form->elements);
```

### Extended Chaining
```php
$form = new Objects();
$form->setValidations(['name' => 'required|max:255', 'email' => 'required|email'])
     ->model(User::class)
     ->text('name', null, ['required'], 'Full Name')
     ->email('email', null, ['required'], 'Email Address')
     ->selectbox('role', $roles, null, ['required'], 'Role')
     ->checkbox('permissions', $permissions, [], [], 'Permissions')
     ->close('Create User');

return $form->render($form->elements);
```

---

## 🎯 Return Types & Error Handling

### Method Return Types

| Method | Return Type | Description |
|--------|-------------|-------------|
| `__construct()` | `void` | Constructor |
| `open()` | `void` | Adds to elements array |
| `model()` | `void` | Adds to elements array |
| `close()` | `void` | Adds to elements array |
| `render()` | `string\|array` | Final HTML output |
| `setValidations()` | `void` | Sets validation rules |
| `draw()` | `void` | Adds to elements array |
| `text()` | `void` | Adds to elements array |
| `selectbox()` | `void` | Adds to elements array |
| `file()` | `void` | Adds to elements array |
| `fileUpload()` | `void` | Populates getFileUploads |
| `sync()` | `void` | Adds Ajax script |

### Error Handling

#### Validation Errors
```php
// Laravel validation akan throw ValidationException
// Form system akan catch dan display via alert_message()

try {
    $request->validate($form->validations);
} catch (ValidationException $e) {
    return redirect()->back()
                    ->withErrors($e->validator)
                    ->withInput();
}
```

#### File Upload Errors
```php
// File validation dalam fileUploadProcessor
private function validationFile($request, $input_name, $validation)
{
    if (!empty($validation)) {
        $this->validations[$input_name] = $validation;
        $request->validate($this->validations); // Throws ValidationException
    }
}
```

#### Model Binding Errors
```php
// Safe model loading dengan error handling
if (!empty($this->model)) {
    try {
        if (true === canvastack_is_softdeletes($this->model)) {
            $model = $this->model::withTrashed()->get();
        } else {
            $model = $this->model->get();
        }
    } catch (Exception $e) {
        Log::error('Model binding error: ' . $e->getMessage());
        return false;
    }
}
```

---

## 🔧 Configuration Constants

### Default Values
```php
// Form defaults
const DEFAULT_METHOD = 'PUT';
const DEFAULT_TYPE = 'route';
const DEFAULT_BUTTON_CLASS = 'btn btn-success btn-slideright pull-right btn_create';

// Upload defaults
const DEFAULT_THUMB_FOLDER = 'thumb';
const DEFAULT_THUMB_PREFIX = 'tnail_';

// Bootstrap classes
const FORM_GROUP_CLASS = 'form-group row';
const LABEL_CLASS = 'col-sm-3 control-label';
const INPUT_WRAPPER_CLASS = 'input-group col-sm-9';
const FORM_CONTROL_CLASS = 'form-control';
```

### Auto-Applied CSS Classes

| Element Type | Auto Classes |
|--------------|--------------|
| Text inputs | `form-control` |
| Select boxes | `chosen-select-deselect chosen-selectbox form-control` |
| Date inputs | `date-picker form-control` |
| DateTime inputs | `datetime-picker form-control` |
| Time inputs | `bootstrap-timepicker form-control` |
| Tags inputs | `form-control` + `data-role="tagsinput"` |
| Checkboxes | `ckbox ckbox-primary` |
| Radio buttons | `rdio rdio-primary circle` |
| File inputs | Bootstrap File Input classes |

---

## 🔒 Security Features

### CSRF Protection
- **Automatic**: Token added dalam `open()` dan `model()` methods
- **Method**: `Form::token()`
- **Laravel Integration**: Built-in CSRF middleware support

### Model Identity Encryption
```php
// Model URI encryption untuk security
$model_uri = canvastack_random_strings() . '___' . str_replace('\\', '.', $model_path) . '___' . canvastack_random_strings();
$model_enc = encrypt($model_uri);
```

### File Upload Security
- **MIME Type Validation**: `getFileType()` method
- **File Size Limits**: Laravel validation integration
- **Upload Path Sanitization**: Prevent directory traversal
- **Extension Validation**: MIME type vs extension verification

### Input Sanitization
- **Laravel HTML**: Built-in XSS protection via Collective HTML
- **Attribute Escaping**: Automatic HTML attribute escaping
- **SQL Injection Protection**: Parameterized queries dalam sync()

---

## 📚 Usage Examples by Method

### Complete API Usage Example
```php
use Canvastack\Canvastack\Library\Components\Form\Objects;

class ComprehensiveFormExample
{
    public function buildCompleteForm()
    {
        $form = new Objects();
        
        // 1. Set validation rules
        $form->setValidations([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'avatar' => 'nullable|image|max:2048'
        ]);
        
        // 2. Initialize form dengan model binding
        $form->modelWithFile(User::class);
        
        // 3. Add global attributes
        $form->addAttributes(['data-form-type' => 'user-registration']);
        
        // 4. Build tabs
        $form->openTab('Basic Information', 'fa-user');
        $form->text('name', null, ['required'], 'Full Name');
        $form->email('email', null, ['required'], 'Email');
        $form->closeTab();
        
        $form->openTab('Profile', 'fa-camera');
        $form->file('avatar', ['imagepreview'], 'Profile Picture');
        $form->textarea('bio', null, [], 'Biography');
        $form->closeTab();
        
        // 5. Close form dengan custom button
        $form->close('Create User', ['class' => 'btn btn-primary btn-lg']);
        
        // 6. Render final output
        return $form->render($form->elements);
    }
}
```

---

**Next**: [Best Practices & Troubleshooting](./BEST_PRACTICES.md)
