# Form Elements - Traits Documentation

## 🎯 Overview

Form System CanvaStack menggunakan 7 traits yang menyediakan berbagai jenis input elements. Setiap trait memiliki spesialisasi tertentu dan dapat dikombinasikan untuk membuat form yang kompleks.

## 📝 Text Trait

**File**: `Elements/Text.php`  
**Purpose**: Menyediakan text-based input elements

### Methods

#### `text($name, $value, $attributes, $label)`
```php
public function text($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Standard text input field  
**Parameters**:
- `$name`: Field name
- `$value`: Default value
- `$attributes`: HTML attributes array
- `$label`: Auto-generate label (true) atau custom label string

**Example**:
```php
$form->text('username', 'john_doe', ['required', 'maxlength' => 50], 'Username');
$form->text('first_name', null, ['class' => 'custom-class'], true);
```

#### `textarea($name, $value, $attributes, $label)`
```php
public function textarea($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Multi-line text input dengan features tambahan  

**Special Features**:
- **Character Limit**: Format `field_name|limit:100`
- **CKEditor Integration**: Attribute `ckeditor` dalam class
- **Bootstrap MaxLength**: Auto-applied untuk character limit

**Examples**:
```php
// Basic textarea
$form->textarea('description', null, [], 'Description');

// With character limit
$form->textarea('bio|limit:500', null, [], 'Biography');

// With CKEditor
$form->textarea('content', null, ['class' => 'ckeditor'], 'Content');
```

**Generated HTML Structure**:
```html
<div class="form-group row">
    <label class="col-sm-3 control-label">Description</label>
    <div class="input-group col-sm-9">
        <textarea name="description" class="form-control"></textarea>
    </div>
</div>
```

#### `email($name, $value, $attributes, $label)`
```php
public function email($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Email input dengan HTML5 validation  

**Example**:
```php
$form->email('email_address', null, ['required'], 'Email Address');
```

#### `number($name, $value, $attributes, $label)`
```php
public function number($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Numeric input field  

**Example**:
```php
$form->number('age', null, ['min' => 0, 'max' => 120], 'Age');
$form->number('price', null, ['step' => '0.01'], 'Price');
```

#### `password($name, $attributes, $label)`
```php
public function password($name, $attributes = [], $label = true)
```
**Purpose**: Password input (no value parameter untuk security)  

**Example**:
```php
$form->password('password', ['required', 'minlength' => 8], 'Password');
$form->password('password_confirmation', ['required'], 'Confirm Password');
```

#### `tags($name, $value, $attributes, $label)`
```php
public function tags($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Tags input dengan bootstrap-tagsinput plugin  

**Auto-Applied Attributes**:
- `data-role="tagsinput"`
- `placeholder="Type {Field Name}"`

**Example**:
```php
$form->tags('skills', 'PHP,Laravel,JavaScript', [], 'Skills');
```

---

## 📅 DateTime Trait

**File**: `Elements/DateTime.php`  
**Purpose**: Date dan time input elements

### Methods

#### `date($name, $value, $attributes, $label)`
```php
public function date($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Date picker input  
**Auto-Applied Class**: `date-picker`

**Example**:
```php
$form->date('birth_date', '1990-01-15', [], 'Birth Date');
$form->date('start_date', null, ['required'], 'Start Date');
```

#### `datetime($name, $value, $attributes, $label)`
```php
public function datetime($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Date and time picker input  
**Auto-Applied Class**: `datetime-picker`

**Example**:
```php
$form->datetime('created_at', now(), [], 'Created At');
$form->datetime('meeting_time', null, ['required'], 'Meeting Time');
```

#### `daterange($name, $value, $attributes, $label)`
```php
public function daterange($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Date range picker input  
**Auto-Applied Class**: `daterange-picker`

**Example**:
```php
$form->daterange('report_period', '2024-01-01 - 2024-12-31', [], 'Report Period');
```

#### `time($name, $value, $attributes, $label)`
```php
public function time($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Time picker input  
**Auto-Applied Class**: `bootstrap-timepicker`

**Example**:
```php
$form->time('start_time', '09:00', [], 'Start Time');
$form->time('end_time', null, ['required'], 'End Time');
```

---

## 📋 Select Trait

**File**: `Elements/Select.php`  
**Purpose**: Select box dan dropdown elements

### Methods

#### `selectbox($name, $values, $selected, $attributes, $label, $set_first_value)`
```php
public function selectbox($name, $values = [], $selected = false, $attributes = [], $label = true, $set_first_value = [null => ''])
```
**Purpose**: Dropdown select dengan Chosen.js integration  

**Parameters**:
- `$values`: Options array `['value' => 'label']`
- `$selected`: Selected value(s)
- `$set_first_value`: First option (`[null => 'Select All']`)

**Auto-Applied Classes**: `chosen-select-deselect chosen-selectbox`

**Examples**:
```php
// Basic select
$form->selectbox('status', [
    'active' => 'Active',
    'inactive' => 'Inactive'
], 'active', [], 'Status');

// Multiple select
$form->selectbox('roles[]', $roles, [1, 3], ['multiple'], 'Roles');

// Custom first option
$form->selectbox('category', $categories, null, [], 'Category', ['' => 'Choose Category']);

// No first option
$form->selectbox('required_field', $options, null, [], 'Field', false);
```

#### `month($name, $value, $attributes, $label)`
```php
public function month($name, $value = null, $attributes = [], $label = true)
```
**Purpose**: Month picker select  
**Auto-Applied Classes**: `chosen-select-deselect chosen-selectbox`

**Example**:
```php
$form->month('birth_month', 6, [], 'Birth Month'); // June
```

---

## 📁 File Trait

**File**: `Elements/File.php`  
**Purpose**: File upload system dengan advanced features

### Public Methods

#### `file($name, $attributes, $label)`
```php
public function file($name, $attributes = [], $label = true)
```
**Purpose**: File input dengan Bootstrap File Input styling  

**Attributes**:
- `imagepreview`: Enable image preview
- `value`: Existing file path for preview

**Example**:
```php
// Basic file upload
$form->file('document', [], 'Document');

// Image with preview
$form->file('avatar', ['imagepreview'], 'Profile Picture');

// With existing file
$form->file('photo', ['imagepreview', 'value' => '/uploads/photo.jpg'], 'Photo');
```

#### `fileUpload($upload_path, $request, $fileInfo)`
```php
public function fileUpload($upload_path, $request, $fileInfo)
```
**Purpose**: Process file upload dengan thumbnail generation  

**Parameters**:
- `$upload_path`: Upload directory
- `$request`: Laravel Request object
- `$fileInfo`: Configuration array

**Example**:
```php
$fileInfo = [
    'avatar' => [
        'file_type' => 'image',
        'file_validation' => 'required|image|max:2048',
        'thumb_name' => 'avatar_thumb',
        'thumb_size' => [150, 150]
    ]
];

$form->fileUpload('users/avatars', $request, $fileInfo);
$uploadedFiles = $form->getFileUploads;
```

### File Upload Features

**Automatic Features**:
- **Time-based naming**: `{timestamp}_{original_name}`
- **Directory structure**: `uploads/{path}/YYYY/MM/DD/`
- **MIME type detection**: Auto-detect file type
- **Thumbnail generation**: For image files
- **Validation**: File type dan size validation

**Thumbnail System**:
- **Location**: `uploads/{path}/YYYY/MM/DD/thumb/`
- **Naming**: `tnail_{timestamp}_{original_name}`
- **Size options**: Width/height with aspect ratio preservation
- **Format**: Same as original file

**Upload Path Structure**:
```
uploads/
└── users/
    └── avatars/
        └── 2024/
            └── 03/
                └── 15/
                    ├── 1710494400_avatar.jpg
                    └── thumb/
                        └── tnail_1710494400_avatar.jpg
```

---

## ☑️ Check Trait

**File**: `Elements/Check.php`  
**Purpose**: Checkbox dan switch elements

### Methods

#### `checkbox($name, $values, $selected, $attributes, $label)`
```php
public function checkbox($name, $values = [], $selected = [], $attributes = [], $label = true)
```
**Purpose**: Checkbox atau switch elements  

**Parameters**:
- `$values`: Options `['value' => 'label']`
- `$selected`: Selected values array
- `$attributes`: Includes special `check_type` attribute

**Special Attributes**:
- `check_type => 'switch'`: Render as toggle switch
- `check_type => 'primary|success|danger|warning'`: Color variants

**Examples**:
```php
// Basic checkboxes
$form->checkbox('interests', [
    'programming' => 'Programming',
    'design' => 'Design',
    'marketing' => 'Marketing'
], ['programming', 'design'], [], 'Interests');

// Switch toggles
$form->checkbox('notifications', [
    'email' => 'Email Notifications',
    'sms' => 'SMS Notifications'
], ['email'], ['check_type' => 'switch'], 'Notifications');

// Colored checkboxes
$form->checkbox('permissions', $permissions, $selected, [
    'check_type' => 'success'
], 'Permissions');
```

**Generated HTML**:
```html
<!-- Checkbox -->
<div class="col-sm-3 ckbox ckbox-primary">
    <input type="checkbox" name="interests[programming]" value="programming" id="diy123">
    <label for="diy123">Programming</label>
</div>

<!-- Switch -->
<div class="switch-box">
    <div class="s-swtich col-sm-5">
        <input type="checkbox" name="notifications[email]" value="email" class="switch" id="diy456">
        <label for="diy456">Toggle</label>
    </div>
    <label>Email Notifications</label>
</div>
```

---

## 🔘 Radio Trait

**File**: `Elements/Radio.php`  
**Purpose**: Radio button elements

### Methods

#### `radiobox($name, $values, $selected, $attributes, $label)`
```php
public function radiobox($name, $values = [], $selected = false, $attributes = [], $label = true)
```
**Purpose**: Radio button group  

**Parameters**:
- `$values`: Options `['value' => 'label']`
- `$selected`: Single selected value
- `$attributes`: Includes `radio_type` for styling

**Special Attributes**:
- `radio_type => 'primary|success|danger|warning'`: Color variants

**Example**:
```php
$form->radiobox('gender', [
    'male' => 'Male',
    'female' => 'Female',
    'other' => 'Other'
], 'male', ['radio_type' => 'primary'], 'Gender');

$form->radiobox('status', [
    1 => 'Active',
    0 => 'Inactive'
], 1, [], 'Status');
```

**Generated HTML**:
```html
<div class="rdio rdio-primary circle">
    <input type="radio" name="gender" value="male" id="diy123" checked>
    <label for="diy123">Male</label>
</div>
<div class="rdio rdio-primary circle">
    <input type="radio" name="gender" value="female" id="diy456">
    <label for="diy456">Female</label>
</div>
```

---

## 📑 Tab Trait

**File**: `Elements/Tab.php`  
**Purpose**: Dynamic tab system rendering

### Methods

#### `openTab($label, $class)`
```php
public function openTab($label, $class = false)
```
**Purpose**: Start new tab dengan label  

**Parameters**:
- `$label`: Tab title
- `$class`: Optional icon class

#### `closeTab()`
```php
public function closeTab()
```
**Purpose**: Close current tab section

#### `addTabContent($content)`
```php
public function addTabContent($content)
```
**Purpose**: Add custom content to tab

### Tab System Usage

**Basic Tab Structure**:
```php
$form->open();

$form->openTab('Personal Info', 'fa-user');
$form->text('first_name', null, ['required'], true);
$form->text('last_name', null, ['required'], true);
$form->email('email', null, ['required'], true);
$form->closeTab();

$form->openTab('Address', 'fa-map-marker');
$form->text('street', null, [], 'Street Address');
$form->text('city', null, [], 'City');
$form->selectbox('country', $countries, null, [], 'Country');
$form->closeTab();

$form->openTab('Settings', 'fa-cog');
$form->checkbox('notifications', $notificationTypes, [], [], 'Notifications');
$form->radiobox('theme', ['light' => 'Light', 'dark' => 'Dark'], 'light', [], 'Theme');
$form->closeTab();

$form->close('Save');
echo $form->render($form->elements);
```

**Generated Tab HTML**:
```html
<div class="tabbable">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#personalinfo">
                <i class="fa-user"></i>Personal Info
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#address">
                <i class="fa-map-marker"></i>Address
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#settings">
                <i class="fa-cog"></i>Settings
            </a>
        </li>
    </ul>
    <div class="tab-content">
        <div id="personalinfo" class="tab-pane fade in active">
            <!-- Personal Info fields -->
        </div>
        <div id="address" class="tab-pane fade">
            <!-- Address fields -->
        </div>
        <div id="settings" class="tab-pane fade">
            <!-- Settings fields -->
        </div>
    </div>
</div>
```

### Tab System Internals

**Markers Used**:
- `--[openTabHTMLForm]--`: Tab system detector
- `--[openNewTab]--`: Individual tab separator
- `--[openNewTabClass]--`: Icon class separator
- `--[closeTabHTMLForm]--`: Tab closure marker

**Rendering Process**:
1. Collect all content antara tab markers
2. Parse labels dan icons
3. Generate tab navigation
4. Generate tab content panes
5. Apply Bootstrap classes dan JavaScript

## 🎛️ Common Patterns

### Field Dependencies
```php
// Ajax dependent selects
$form->selectbox('province_id', $provinces);
$form->sync('province_id', 'city_id', 'id', 'name', 
    'SELECT id, name FROM cities WHERE province_id = :province_id');
$form->selectbox('city_id', []);
```

### Conditional Fields
```php
// Hide fields based on conditions
if ($user->role === 'admin') {
    $form->selectbox('permissions', $allPermissions);
} else {
    $form->addAttributes(['class' => 'hide']);
    $form->selectbox('permissions', $limitedPermissions);
}
```

### Plugin Integration
```php
// CKEditor
$form->textarea('content', null, ['class' => 'ckeditor'], 'Content');

// Chosen.js (auto-applied to selectbox)
$form->selectbox('tags[]', $tags, null, ['multiple'], 'Tags');

// Date/Time pickers (auto-applied classes)
$form->datetime('appointment', null, [], 'Appointment Time');
```

---

**Next**: [Rendering System Documentation](./RENDERING_SYSTEM.md)