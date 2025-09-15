
# Best Practices & Troubleshooting Guide

## 💡 Best Practices

### 1. 🏗️ Form Structure Organization

#### ✅ Good Practices
```php
// Logical grouping dengan tabs
$form->openTab('Personal Information', 'fa-user');
$form->text('first_name', null, ['required'], 'First Name');
$form->text('last_name', null, ['required'], 'Last Name');
$form->email('email', null, ['required'], 'Email');
$form->closeTab();

$form->openTab('Contact Details', 'fa-phone');
$form->text('phone', null, [], 'Phone');
$form->textarea('address', null, [], 'Address');
$form->closeTab();
```

#### ❌ Practices to Avoid
```php
// Mixed unrelated fields tanpa struktur
$form->text('first_name');
$form->selectbox('country');
$form->password('password');
$form->text('phone');
$form->file('avatar');
```

### 2. 🛡️ Security Best Practices

#### Input Validation
```php
// ✅ Comprehensive validation
$form->setValidations([
    'email' => 'required|email|unique:users,email|max:255',
    'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
    'phone' => 'required|regex:/^(\+62|62|0)[0-9]{9,13}$/',
    'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048|dimensions:max_width=2000,max_height=2000'
]);
```

#### File Upload Security
```php
// ✅ Secure file upload configuration
$fileInfo = [
    'avatar' => [
        'file_validation' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'file_type' => 'image',
        'thumb_size' => [200, 200]
    ]
];

// ❌ Unrestricted upload
$form->file('document', [], 'Any File'); // No validation
```

#### Model Binding Security
```php
// ✅ Secure model resolution
$allowedModels = ['User', 'Post', 'Category'];
$modelClass = in_array($model, $allowedModels) ? "App\\Models\\{$model}" : User::class;

// ❌ Direct model instantiation dari user input
$modelClass = "App\\Models\\{$_GET['model']}"; // Dangerous!
```

### 3. 🎯 Performance Optimization

#### Efficient Model Loading
```php
// ✅ Load dengan required relationships saja
$user = User::with(['roles:id,name', 'profile:user_id,bio'])
            ->select(['id', 'name', 'email', 'created_at'])
            ->findOrFail($id);

// ❌ Load semua data dan relationships
$user = User::with('*')->findOrFail($id);
```

#### Form Caching untuk Complex Forms
```php
// ✅ Cache form structure
public function buildCachedForm($cacheKey, $model = null)
{
    return Cache::remember($cacheKey, 3600, function() use ($model) {
        $form = new Objects();
        
        if ($model) {
            $form->model($model);
        } else {
            $form->open();
        }
        
        // Build form elements...
        
        return $form->render($form->elements);
    });
}
```

#### Selective Field Loading
```php
// ✅ Load fields berdasarkan user permissions
public function buildFormByPermissions($user, $targetModel)
{
    $form = new Objects();
    $form->model($targetModel);
    
    // Basic fields untuk semua user
    $form->text('name', null, ['required'], 'Name');
    $form->email('email', null, ['required'], 'Email');
    
    // Admin-only fields
    if ($user->hasRole('admin')) {
        $form->selectbox('role', $roles, null, [], 'Role');
        $form->checkbox('permissions', $permissions, [], [], 'Permissions');
    }
    
    return $form;
}
```

### 4. 📝 Code Organization

#### Controller Best Practices
```php
class UserController extends Controller
{
    use FormBuilderTrait;
    
    protected $validationRules = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed'
        ],
        'update' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,{id}',
            'password' => 'nullable|min:8|confirmed'
        ]
    ];
    
    public function create()
    {
        $form = $this->buildUserForm('create');
        return view('users.create', compact('form'));
    }
    
    public function edit(User $user)
    {
        $form = $this->buildUserForm('update', $user);
        return view('users.edit', compact('user', 'form'));
    }
    
    private function buildUserForm($action, $user = null)
    {
        $form = new Objects();
        
        // Dynamic validation rules
        $rules = $this->validationRules[$action];
        if ($action === 'update' && $user) {
            $rules['email'] = str_replace('{id}', $user->id, $rules['email']);
        }
        
        $form->setValidations($rules);
        
        if ($user) {
            $form->model($user, $user->id);
        } else {
            $form->open();
        }
        
        // Form elements
        $this->addCommonFields($form);
        
        if ($action === 'create') {
            $this->addPasswordFields($form);
        } else {
            $this->addOptionalPasswordFields($form);
        }
        
        $form->close($action === 'create' ? 'Create User' : 'Update User');
        
        return $form->render($form->elements);
    }
}
```

#### Form Builder Trait
```php
trait FormBuilderTrait
{
    protected function addCommonFields($form)
    {
        $form->text('name', null, ['required'], 'Full Name');
        $form->email('email', null, ['required'], 'Email Address');
    }
    
    protected function addPasswordFields($form)
    {
        $form->password('password', ['required'], 'Password');
        $form->password('password_confirmation', ['required'], 'Confirm Password');
    }
    
    protected function addOptionalPasswordFields($form)
    {
        $form->password('password', [], 'New Password (leave blank to keep current)');
        $form->password('password_confirmation', [], 'Confirm New Password');
    }
}
```

---

## 🔧 Troubleshooting Guide

### Common Issues & Solutions

#### 1. Form Not Rendering
**Symptom**: Blank page atau no output
```php
// Debug steps:
dd($form->elements);              // Check elements array
dd($form->params);                // Check parameters
dd($form->render($form->elements)); // Check render output
```

**Common Causes**:
- Missing `render()` call
- Empty elements array
- Tab markers mismatch
- PHP errors dalam element generation

**Solution**:
```php
// ✅ Proper form rendering
$form = new Objects();
$form->open();
$form->text('name', null, ['required'], 'Name');
$form->close('Submit');

$html = $form->render($form->elements); // Don't forget this!
if (empty($html)) {
    throw new Exception('Form rendering failed');
}

return view('form', compact('html'));
```

#### 2. Model Binding Not Working
**Symptom**: Form fields not populated with model data

**Debug Steps**:
```php
// Check model loading
dd($form->model);
dd($form->currentRouteName);
dd($form->getModelValue('field_name', 'text'));
```

**Common Causes**:
- Wrong route name detection
- Model not properly loaded
- Field name mismatch
- Missing model data

**Solution**:
```php
// ✅ Explicit model binding
$user = User::findOrFail($id);
$form = new Objects();

// Debug route detection
echo "Current route: " . $form->currentRouteName;

// Explicit binding
$form->model($user, $user->id, route('users.update', $user->id));

// Field names harus match dengan model attributes
$form->text('first_name', null, [], 'First Name'); // Model has 'first_name' field
```

#### 3. File Upload Issues
**Symptom**: Files not uploading atau thumbnails not generated

**Debug File Upload**:
```php
// Check request
dd($request->hasFile('avatar'));
dd($request->file('avatar'));

// Check file info
dd($fileInfo);

// Check upload results
$form->fileUpload('users', $request, $fileInfo);
dd($form->getFileUploads);
```

**Common Causes**:
- Missing `multipart/form-data` encoding
- Incorrect file validation rules
- Directory permission issues
- Missing Intervention Image package

**Solutions**:
```php
// ✅ Proper file form setup
$form->modelWithFile($user, $user->id); // Enables multipart

// ✅ Proper validation
$fileInfo = [
    'avatar' => [
        'file_validation' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'thumb_size' => [200, 200]
    ]
];

// ✅ Check directory permissions
$uploadPath = public_path('uploads/users');
if (!is_writable($uploadPath)) {
    throw new Exception("Upload directory not writable: {$uploadPath}");
}
```

#### 4. Tab System Not Working
**Symptom**: Tabs not rendering atau content not showing

**Debug Tab Issues**:
```php
// Check raw content dengan markers
$content = implode('', $form->elements);
echo htmlspecialchars($content);

// Count markers
echo "Open markers: " . substr_count($content, '--[openTabHTMLForm]--');
echo "Close markers: " . substr_count($content, '--[closeTabHTMLForm]--');
```

**Common Causes**:
- Missing `closeTab()` calls
- Unmatched tab markers
- Content between tabs without proper structure
- Missing Bootstrap CSS/JS

**Solution**:
```php
// ✅ Proper tab structure
$form->openTab('Tab 1', 'fa-user');
$form->text('field1');
$form->text('field2');
$form->closeTab(); // Important!

$form->openTab('Tab 2', 'fa-cog');
$form->selectbox('field3', $options);
$form->closeTab(); // Important!

// ✅ Include Bootstrap tab functionality
// In layout file:
<script src="/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="/css/bootstrap.min.css">
```

#### 5. Validation Not Showing
**Symptom**: Required fields not marked, validation errors not displayed

**Debug Validation**:
```php
// Check validation setup
dd($form->validations);
dd($form::$validation_attributes);

// Check session errors
dd(session('errors'));
```

**Solution**:
```php
// ✅ Proper validation setup
$form->setValidations([
    'name' => 'required|string|max:255'
]);

// ✅ Display validation errors dalam controller
if ($validator->fails()) {
    return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with([
                        'message' => $validator->errors(),
                        'status' => 'failed'
                    ]);
}
```

### 6. CSRF Token Issues
**Symptom**: 419 Page Expired errors

**Solution**:
```php
// ✅ Ensure CSRF middleware is active
// In routes/web.php atau route group:
Route::middleware(['web'])->group(function () {
    Route::resource('users', UserController::class);
});

// ✅ Add token manually jika needed
$form->token(); // Called automatically in open() dan model()

// ✅ Ajax CSRF setup
// In layout head:
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
```

---

## 🔍 Debugging Techniques

### 1. Element Inspection
```php
class FormDebugger
{
    public static function inspectForm(Objects $form)
    {
        echo "<pre>";
        echo "=== FORM DEBUG INFO ===\n";
        echo "Current Route: " . $form->currentRoute . "\n";
        echo "Route Name: " . $form->currentRouteName . "\n";
        echo "Elements Count: " . count($form->elements) . "\n";
        echo "Parameters Count: " . count($form->params) . "\n";
        echo "Validations: " . count($form->validations) . "\n";
        
        echo "\n=== ELEMENTS ===\n";
        foreach ($form->elements as $index => $element) {
            echo "[$index]: " . substr($element, 0, 100) . "...\n";
        }
        
        echo "\n=== PARAMETERS ===\n";
        print_r($form->params);
        
        echo "\n=== VALIDATIONS ===\n";
        print_r($form->validations);
        echo "</pre>";
    }
}

// Usage
FormDebugger::inspectForm($form);
```

### 2. Step-by-Step Debugging
```php
public function debugFormStep($step = 1)
{
    $form = new Objects();
    
    switch ($step) {
        case 1: // Test basic form creation
            $form->open();
            echo "✓ Form opened successfully\n";
            break;
            
        case 2: // Test element addition
            $form->open();
            $form->text('test', null, [], 'Test');
            echo "✓ Element added successfully\n";
            echo "Elements count: " . count($form->elements) . "\n";
            break;
            
        case 3: // Test model binding
            $user = User::first();
            $form->model($user, $user->id);
            echo "✓ Model binding successful\n";
            echo "Model: " . get_class($user) . "\n";
            break;
            
        case 4: // Test rendering
            $form->open();
            $form->text('test', null, [], 'Test');
            $form->close('Submit');
            
            $html = $form->render($form->elements);
            echo "✓ Rendering successful\n";
            echo "HTML length: " . strlen($html) . "\n";
            break;
    }
}
```

### 3. Performance Profiling
```php
class FormPerformanceProfiler
{
    private static $timings = [];
    
    public static function start($label)
    {
        self::$timings[$label] = microtime(true);
    }
    
    public static function end($label)
    {
        if (isset(self::$timings[$label])) {
            $duration = microtime(true) - self::$timings[$label];
            echo "{$label}: {$duration}s\n";
        }
    }
    
    public static function profileFormRender($form)
    {
        self::start('form_render');
        $html = $form->render($form->elements);
        self::end('form_render');
        
        self::start('model_binding');
        // Test model operations
        self::end('model_binding');
        
        return $html;
    }
}
```

---

## 💻 Development Best Practices

### 1. Environment Setup

#### Required Dependencies
```bash
# Laravel packages
composer require laravelcollective/html
composer require intervention/image

# JavaScript dependencies (package.json)
{
    "devDependencies": {
        "bootstrap": "^4.6.0",
        "jquery": "^3.6.0",
        "chosen-js": "^1.8.7",
        "bootstrap-tagsinput": "^0.8.0"
    }
}
```

#### Configuration
```php
// config/app.php - Add service providers
'providers' => [
    // ...
    Collective\Html\HtmlServiceProvider::class,
    Intervention\Image\ImageServiceProvider::class,
],

'aliases' => [
    // ...
    'Form' => Collective\Html\FormFacade::class,
    'Html' => Collective\Html\HtmlFacade::class,
],
```

### 2. Testing Strategies

#### Unit Testing Forms
```php
class FormTest extends TestCase
{
    public function test_basic_form_creation()
    {
        $form = new Objects();
        $form->open();
        $form->text('name', null, ['required'], 'Name');
        $form->close('Submit');
        
        $html = $form->render($form->elements);
        
        $this->assertStringContains('<form', $html);
        $this->assertStringContains('name="name"', $html);
        $this->assertStringContains('required', $html);
        $this->assertStringContains('Submit', $html);
    }
    
    public function test_model_binding()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        
        $form = new Objects();
        $form->model($user, $user->id);
        $form->text('name', null, [], 'Name');
        $form->close(false);
        
        $html = $form->render($form->elements);
        
        $this->assertStringContains('value="John Doe"', $html);
    }
    
    public function test_file_upload_processing()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);
        $request = Request::create('/upload', 'POST', [], [], ['avatar' => $file]);
        
        $form = new Objects();
        $fileInfo = [
            'avatar' => [
                'file_validation' => 'required|image|max:1024',
                'thumb_size' => [100, 100]
            ]
        ];
        
        $form->fileUpload('test', $request, $fileInfo);
        
        $this->assertArrayHasKey('avatar', $form->getFileUploads);
        $this->assertArrayHasKey('file', $form->getFileUploads['avatar']);
    }
}
```

#### Integration Testing
```php
class FormIntegrationTest extends TestCase
{
    public function test_complete_crud_workflow()
    {
        // Test create
        $response = $this->get('/users/create');
        $response->assertStatus(200);
        $response->assertSee('Create User');
        
        // Test store
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
        
        $response = $this->post('/users', $userData);
        $response->assertRedirect();
        
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        
        // Test edit
        $response = $this->get("/users/{$user->id}/edit");
        $response->assertStatus(200);
        $response->assertSee($user->name);
        
        // Test update
        $response = $this->put("/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => $user->email
        ]);
        
        $response->assertRedirect();
        $this->assertEquals('Updated Name', $user->fresh()->name);
    }
}
```

### 3. Error Handling Strategies

#### Graceful Degradation
```php
class RobustFormBuilder extends Objects
{
    public function safeBuild(callable $builder)
    {
        try {
            return $builder($this);
        } catch (Exception $e) {
            Log::error('Form build error: ' . $e->getMessage());
            
            // Fallback ke basic form
            $this->open();
            $this->addTabContent('<div class="alert alert-warning">Form temporarily unavailable</div>');
            $this->close(false);
            
            return $this->render($this->elements);
        }
    }
}

// Usage
$html = (new RobustFormBuilder())->safeBuild(function($form) {
    $form->model($user);
    $form->text('name');
    $form->email('email');
    $form->close('Save');
    return $form->render($form->elements);
});
```

#### Validation Error Recovery
```php
public function store(Request $request)
{
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users'
        ]);
        
        // Process successful validation
        
    } catch (ValidationException $e) {
        // Enhanced error response
        return redirect()->back()
                        ->withErrors($e->validator)
                        ->withInput()
                        ->with([
                            'message' => 'Please fix the following errors:',
                            'status' => 'failed',
                            'error_count' => $e->validator->errors()->count()
                        ]);
    } catch (Exception $e) {
        Log::error('Form processing error: ' . $e->getMessage());
        
        return redirect()->back()
                        ->with([
                            'message' => 'An unexpected error occurred. Please try again.',
                            'status' => 'failed'
                        ]);
    }
}
```

---

## 📈 Performance Optimization

### 1. Large Form Optimization

#### Lazy Loading
```php
class LazyFormBuilder extends Objects
{
    private $lazyTabs = [];
    
    public function addLazyTab($id, $label, $loadUrl)
    {
        $this->lazyTabs[$id] = [
            'label' => $label,
            'url' => $loadUrl
        ];
        
        $this->openTab($label, 'fa-spinner');
        $this->addTabContent(
            '<div class="lazy-content" data-url="' . $loadUrl . '">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin"></i> Loading...
                </div>
            </div>'
        );
        $this->closeTab();
    }
}

// JavaScript untuk lazy loading
$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function(e) {
    var target = $(e.target).attr('href');
    var lazyContent = $(target).find('.lazy-content');
    
    if (lazyContent.length && !lazyContent.data('loaded')) {
        var url = lazyContent.data('url');
        
        $.get(url, function(data) {
            lazyContent.html(data).data('loaded', true);
        });
    }
});
```

#### Form Caching
```php
class CachedFormBuilder
{
    public function buildWithCache($cacheKey, $ttl = 3600, callable $builder = null)
    {
        return Cache::remember($cacheKey, $ttl, function() use ($builder) {
            $form = new Objects();
            
            if ($builder) {
                return $builder($form);
            }
            
            return $form;
        });
    }
}
```

### 2. Memory Management

#### Element Cleanup
```php
class MemoryEfficientForm extends Objects
{
    public function render($object)
    {
        $html = parent::render($object);
        
        // Cleanup untuk memory
        $this->elements = [];
        $this->params = [];
        $this->paramValue = null;
        $this->paramSelected = null;
        
        return $html;
    }
}
```

#### Selective Loading
```php
public function buildSelectiveForm($user, $fieldsToLoad = [])
{
    $form = new Objects();
    
    // Load hanya fields yang diperlukan
    if (empty($fieldsToLoad)) {
        $fieldsToLoad = $user->getFillable();
    }
    
    $form->model($user, $user->id);
    
    foreach ($fieldsToLoad as $field) {
        $type = $this->determineFieldType($user, $field);
        $form->$type($field, null, [], ucfirst($field));
    }
    
    return $form;
}
```

---

## 🎨 UI/UX Best Practices

### 1. User Experience Guidelines

#### Progressive Disclosure
```php
// ✅ Good: Show advanced options on demand
$form->openTab('Basic Info', 'fa-info');
$form->text('name', null, ['required'], 'Name');
$form->email('email', null, ['required'], 'Email');
$form->closeTab();

$form->openTab('Advanced Settings', 'fa-cog');
$form->addTabContent('<small class="text-muted">Optional advanced configuration</small>');
$form->selectbox('timezone', $timezones, null, [], 'Timezone');
$form->checkbox('notifications', $types, [], [], 'Notifications');
$form->closeTab();
```

#### Clear Field Labels
```php
// ✅ Good: Descriptive labels dengan context
$form->text('username', null, [
    'required',
    'placeholder' => 'Choose a unique username (3-20 characters)',
    'pattern' => '^[a-zA-Z0-9_]{3,20}$'
], 'Username');

// ❌ Bad: Unclear labels
$form->text('usr', null, ['required'], 'Usr');
```

#### Helpful Validation Messages
```php
// ✅ Good: Custom validation messages
$request->validate([
    'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'
], [
    'password.required' => 'Password is required.',
    'password.min' => 'Password must be at least 8 characters.',
    'password.regex' => 'Password must contain uppercase, lowercase, and numbers.'
]);
```

### 2. Mobile Responsiveness

#### Mobile-First Design
```css
/* Mobile-first responsive form styles */
.form-group.row {
    margin-bottom: 1rem;
}

.control-label {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

/* Mobile: Stack labels dan inputs */
@media (max-width: 767px) {
    .col-sm-3,
    .col-sm-9 {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .input-group {
        display: block;
    }
    
    .form-control {
        font-size: 16px; /* Prevent zoom pada iOS */
    }
}

/* Tablet dan desktop */
@media (min-width: 768px) {
    .col-sm-3 {
        text-align: right;
        padding-right: 15px;
    }
}
```

#### Touch-Friendly Inputs
```php
// ✅ Mobile-optimized inputs
$form->email('email', null, [
    'inputmode' => 'email',
    'autocomplete' => 'email',
    'autocapitalize' => 'none'
], 'Email');

$form->text('phone', null, [
    'inputmode' => 'tel',
    'autocomplete' => 'tel',
    'pattern' => '[0-9]*'
], 'Phone');

$form->number('age', null, [
    'inputmode' => 'numeric',
    'min' => '0',
    'max' => '120'
], 'Age');
```

---

## 🔧 Advanced Troubleshooting

### Complex Issues

#### Memory Leaks dalam Large Forms
```php
// Problem: Form dengan banyak elements menggunakan memory berlebihan
// Solution: Chunked rendering

class ChunkedFormRenderer extends Objects
{
    private $chunkSize = 50;
    
    public function renderInChunks()
    {
        $chunks = array_chunk($this->elements, $this->chunkSize);
        $output = '';
        
        foreach ($chunks as $chunk) {
            $chunkHtml = $this->render($chunk);
            $output .= $chunkHtml;
            
            // Free memory setelah setiap chunk
            unset($chunk);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return $output;
    }
}
```

#### Tab Rendering Performance Issues
```php
// Problem: Tab system lambat untuk forms besar
// Solution: Conditional tab loading

public function renderTabsSelectively($activeTabOnly = false)
{
    if (!$activeTabOnly) {
        return $this->render($this->elements);
    }
    
    // Render hanya active tab
    $activeTabContent = $this->extractActiveTabContent();
    return $this->renderSingleTab($activeTabContent);
}

private function extractActiveTabContent()
{
    // Extract dan render hanya tab yang sedang active
    $content = implode('', $this->elements);
    
    // Logic untuk extract active tab...
    
    return $activeContent;
}
```

#### Database Connection Issues
```php
// Problem: Form gagal load data karena database issues
// Solution: Graceful fallback

public function safeModelBinding($model, $id)
{
    try {
        $record = $model::findOrFail($id);
        $this->model($record, $record->id);
        return true;
    } catch (ModelNotFoundException $e) {
        Log::warning("Model not found: {$model}#{$id}");
        $this->open(); // Fallback ke basic form
        $this->addTabContent('<div class="alert alert-warning">Record not found, creating new...</div>');
        return false;
    } catch (QueryException $e) {
        Log::error("Database error: " . $e->getMessage());
        $this->addTabContent('<div class="alert alert-danger">Database temporarily unavailable</div>');
        return false;
    }
}
```

---

## 📚 Code Quality Guidelines

### 1. Documentation Standards
```php
/**
 * Build user registration form dengan comprehensive validation
 *
 * @param array $userTypes Available user types
 * @param bool $enableFileUpload Enable file upload capability
 * @return string Rendered form HTML
 * 
 * @throws ValidationException When validation rules are malformed
 * @throws ModelNotFoundException When referenced models don't exist
 * 
 * @example
 * $form = new Objects();


### 2. Maintenance & Monitoring

#### Form Health Check
```php
class FormHealthChecker
{
    public function runHealthCheck()
    {
        $checks = [
            'dependencies' => $this->checkDependencies(),
            'permissions' => $this->checkPermissions(),
            'configuration' => $this->checkConfiguration(),
            'performance' => $this->checkPerformance()
        ];
        
        return $checks;
    }
    
    private function checkDependencies()
    {
        $issues = [];
        
        // Check Laravel Collective
        if (!class_exists('\Collective\Html\FormFacade')) {
            $issues[] = 'Laravel Collective HTML package not installed';
        }
        
        // Check Intervention Image
        if (!class_exists('\Intervention\Image\ImageManager')) {
            $issues[] = 'Intervention Image package not installed';
        }
        
        // Check Bootstrap
        if (!file_exists(public_path('assets/templates/default/css/bootstrap.min.css'))) {
            $issues[] = 'Bootstrap CSS not found';
        }
        
        return empty($issues) ? 'OK' : $issues;
    }
    
    private function checkPermissions()
    {
        $uploadPath = public_path('uploads');
        
        if (!is_dir($uploadPath)) {
            return ['Upload directory does not exist'];
        }
        
        if (!is_writable($uploadPath)) {
            return ['Upload directory not writable'];
        }
        
        return 'OK';
    }
}
```

#### Performance Monitoring
```php
class FormPerformanceMonitor
{
    public function monitorFormRender($form, $threshold = 1.0)
    {
        $start = microtime(true);
        
        $html = $form->render($form->elements);
        
        $duration = microtime(true) - $start;
        
        if ($duration > $threshold) {
            Log::warning("Slow form render detected", [
                'duration' => $duration,
                'elements_count' => count($form->elements),
                'params_count' => count($form->params),
                'route' => request()->route()->getName()
            ]);
        }
        
        return $html;
    }
}
```

---

## 🚨 Common Errors & Quick Fixes

### Error: "Call to undefined method"
```php
// Problem: Method tidak ditemukan dalam trait
// Solution: Check trait usage dan method name

// ✅ Correct trait usage
class Objects {
    use Text, DateTime, Select, File, Check, Radio, Tab;
}

// ✅ Correct method call
$form->text('name');      // ✓ From Text trait
$form->selectbox('role'); // ✓ From Select trait
$form->openTab('Info');   // ✓ From Tab trait
```

### Error: "Class not found"
```php
// Problem: Missing use statements
// Solution: Add proper imports

use Canvastack\Canvastack\Library\Components\Form\Objects;
use Collective\Html\FormFacade as Form;
use Collective\Html\HtmlFacade as Html;
```

### Error: "Array to string conversion"
```php
// Problem: Array passed ke text input
// Solution: Convert array ke string

// ❌ Problem
$form->text('tags', ['php', 'laravel'], [], 'Tags');

// ✅ Solution
$form->text('tags', implode(',', ['php', 'laravel']), [], 'Tags');
// Or use tags input:
$form->tags('tags', implode(',', ['php', 'laravel']), [], 'Tags');
```

### Error: "File upload not working"
```php
// Problem: Missing multipart encoding
// ❌ Wrong
$form->open();
$form->file('avatar');

// ✅ Correct
$form->open(route('upload'), 'POST', 'route', true); // true = file upload
// Or:
$form->modelWithFile($user);
```

### Error: "Tabs not rendering"
```php
// Problem: Unmatched tab markers
// ❌ Wrong
$form->openTab('Info');
$form->text('name');
// Missing closeTab()!

// ✅ Correct
$form->openTab('Info');
$form->text('name');
$form->closeTab(); // Required!
```

### Error: "Validation not showing"
```php
// Problem: Validation rules not applied
// ❌ Wrong sequence
$form->text('email', null, ['required'], 'Email');
$form->setValidations(['email' => 'required|email']); // Too late!

// ✅ Correct sequence
$form->setValidations(['email' => 'required|email']);
$form->text('email', null, ['required'], 'Email');
```

---

## 🛡️ Security Guidelines

### 1. Input Sanitization
```php
// ✅ Always validate dan sanitize input
$form->setValidations([
    'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
    'email' => 'required|email|max:255',
    'phone' => 'nullable|regex:/^[0-9+\-\s()]+$/',
    'content' => 'required|string|max:5000'
]);

// ✅ Additional sanitization dalam controller
$data = $request->validate($rules);
$data['name'] = strip_tags($data['name']);
$data['content'] = strip_tags($data['content'], '<p><br><strong><em>');
```

### 2. File Upload Security
```php
// ✅ Comprehensive file validation
$fileInfo = [
    'avatar' => [
        'file_validation' => [
            'required',
            'image',
            'mimes:jpeg,png,jpg',
            'max:2048',
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
        ]
    ]
];

// ✅ Additional security checks
public function validateUploadedFile($file)
{
    // Check real file type vs extension
    $mimeType = $file->getMimeType();
    $extension = $file->getClientOriginalExtension();
    
    $allowedTypes = [
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg', 
        'png' => 'image/png'
    ];
    
    if (!isset($allowedTypes[$extension]) || 
        $allowedTypes[$extension] !== $mimeType) {
        throw new ValidationException('File type mismatch');
    }
    
    return true;
}
```

### 3. XSS Prevention
```php
// ✅ Safe output dalam views
{!! $form->render($form->elements) !!} // Form HTML is safe

// ✅ Escape user content
<div>{{ $user->bio }}</div> // Auto-escaped

// ✅ For rich content, use purifier
$clean = Purifier::clean($user->content);
echo $clean;
```

---

## 📊 Monitoring & Analytics

### Form Usage Analytics
```php
class FormAnalytics
{
    public static function trackFormUsage($formType, $action, $duration = null)
    {
        $data = [
            'form_type' => $formType,
            'action' => $action,
            'duration' => $duration,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ];
        
        // Log ke database atau analytics service
        DB::table('form_analytics')->insert($data);
    }
}

// Usage dalam controller
public function store(Request $request)
{
    $start = microtime(true);
    
    // Process form...
    
    $duration = microtime(true) - $start;
    FormAnalytics::trackFormUsage('user_registration', 'create', $duration);
    
    return redirect()->back();
}
```

### Error Tracking
```php
class FormErrorTracker
{
    public static function trackValidationErrors($errors, $formContext)
    {
        foreach ($errors->toArray() as $field => $messages) {
            Log::channel('form_errors')->error('Validation error', [
                'field' => $field,
                'messages' => $messages,
                'form_context' => $formContext,
                'user_id' => auth()->id(),
                'url' => request()->fullUrl()
            ]);
        }
    }
}
```

---

## 🎯 Production Deployment

### Pre-deployment Checklist
- [ ] **Dependencies**: Laravel Collective HTML, Intervention Image installed
- [ ] **Permissions**: Upload directories writable (755)
- [ ] **Assets**: Bootstrap CSS/JS published dan accessible
- [ ] **Configuration**: Form config values set correctly
- [ ] **Security**: File upload validation rules in place
- [ ] **Performance**: Form caching enabled untuk complex forms
- [ ] **Monitoring**: Error tracking dan analytics configured
- [ ] **Testing**: All form functionalities tested
- [ ] **Documentation**: Team trained pada form system usage

### Environment Configuration
```php
// .env production settings
FORM_UPLOAD_MAX_SIZE=2048
FORM_THUMBNAIL_QUALITY=85
FORM_CACHE_TTL=3600
FORM_DEBUG_ENABLED=false

// config/form.php
return [
    'upload' => [
        'max_size' => env('FORM_UPLOAD_MAX_SIZE', 2048),
        'allowed_types' => ['jpeg', 'png', 'jpg', 'pdf', 'doc', 'docx'],
        'thumbnail_quality' => env('FORM_THUMBNAIL_QUALITY', 85),
    ],
    'cache' => [
        'enabled' => env('APP_ENV') === 'production',
        'ttl' => env('FORM_CACHE_TTL', 3600),
    ],
    'debug' => [
        'enabled' => env('FORM_DEBUG_ENABLED', false),
        'log_performance' => env('APP_ENV') !== 'production',
    ]
];
```

---

## 📖 Quick Reference Cards

### Basic Form Checklist
```
□ Import Objects class
□ Create form instance
□ Set validation rules (setValidations)
□ Open form (open/model)
□ Add elements (text/email/etc)
□ Close form (close)
□ Render output (render)
□ Display dalam view
```

### Model Form Checklist
```
□ Load model dengan relationships
□ Call model() method
□ Add form elements
□ Handle file uploads jika ada
□ Close dengan action button
□ Process dalam controller
□ Handle validation errors
□ Redirect dengan messages
```

### Tab Form Checklist
```
□ openTab() dengan label dan icon
□ Add tab elements
□ closeTab() untuk setiap tab
□ Include Bootstrap tab CSS/JS
□ Test tab switching functionality
□ Validate across all tabs
□ Handle tab-specific errors
```

### File Upload Checklist
```
□ Use modelWithFile() atau open() dengan file=true
□ Add file input elements
□ Set file validation rules
□ Configure fileInfo array
□ Call fileUpload() dalam controller
□ Check getFileUploads results
□ Update model dengan file paths
□ Handle upload errors gracefully
```

---

## 🎓 Learning Resources

### Additional Reading
- [Laravel Forms & HTML Documentation](https://laravelcollective.com/docs/html)
- [Bootstrap 4 Form Components](https://getbootstrap.com/docs/4.6/components/forms/)
- [Intervention Image Documentation](http://image.intervention.io/)
- [Laravel Validation Documentation](https://laravel.com/docs/validation)

### Community Resources
- **GitHub Issues**: Report bugs atau feature requests
- **Stack Overflow**: Tag `canvastack-form` untuk community support
- **Laravel Forums**: General Laravel form discussions

---

**Selesai**: Dokumentasi Form System CanvaStack lengkap sudah siap! 🎉
