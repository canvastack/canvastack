# Panduan Penggunaan Form System CanvaStack

## 🚀 Quick Start

### Installation & Setup
```php
// Import class Objects
use Canvastack\Canvastack\Library\Components\Form\Objects;

// Basic usage
$form = new Objects();
```

### Minimal Form Example
```php
<?php
use Canvastack\Canvastack\Library\Components\Form\Objects;

// Controller method
public function create()
{
    $form = new Objects();
    
    // Basic form
    $form->open();
    $form->text('name', null, ['required'], 'Full Name');
    $form->email('email', null, ['required'], 'Email Address');
    $form->password('password', ['required'], 'Password');
    $form->close('Create Account');
    
    $html = $form->render($form->elements);
    
    return view('users.create', compact('html'));
}
```

**Blade Template:**
```blade
{{-- resources/views/users/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Create New User</div>
                <div class="panel-body">
                    {!! $html !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

## 📝 Basic Form Patterns

### Simple Contact Form
```php
public function contactForm()
{
    $form = new Objects();
    
    $form->open(route('contact.send'), 'POST');
    
    // Personal information
    $form->text('full_name', null, ['required'], 'Full Name');
    $form->email('email', null, ['required'], 'Email Address');
    $form->text('phone', null, [], 'Phone Number');
    
    // Message details
    $form->selectbox('subject', [
        'general' => 'General Inquiry',
        'support' => 'Technical Support',
        'sales' => 'Sales Question',
        'feedback' => 'Feedback'
    ], null, ['required'], 'Subject');
    
    $form->textarea('message', null, ['required'], 'Message');
    
    // Agreement
    $form->checkbox('newsletter', [1 => 'Subscribe to newsletter'], [], [], '');
    
    $form->close('Send Message');
    
    return $form->render($form->elements);
}
```

### Login Form
```php
public function loginForm()
{
    $form = new Objects();
    
    $form->open(route('auth.login'), 'POST', 'route');
    
    $form->email('email', null, ['required', 'autofocus'], 'Email Address');
    $form->password('password', ['required'], 'Password');
    
    $form->checkbox('remember', [1 => 'Remember Me'], [], [], '');
    
    $form->close('Sign In');
    
    return $form->render($form->elements);
}
```

## 🗄️ CRUD Operations

### Create Form (User Registration)
```php
class UserController extends Controller
{
    public function create()
    {
        $form = new Objects();
        
        // Set validation rules
        $form->setValidations([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'birth_date' => 'required|date|before:today',
            'phone' => 'nullable|regex:/^[0-9+\-\s()]+$/'
        ]);
        
        $form->open(route('users.store'), 'POST');
        
        // Basic information
        $form->text('first_name', null, ['required'], 'First Name');
        $form->text('last_name', null, ['required'], 'Last Name');
        $form->email('email', null, ['required'], 'Email Address');
        
        // Security
        $form->password('password', ['required'], 'Password');
        $form->password('password_confirmation', ['required'], 'Confirm Password');
        
        // Additional info
        $form->date('birth_date', null, ['required'], 'Date of Birth');
        $form->text('phone', null, [], 'Phone Number');
        
        // Status
        $statusOptions = canvastack_form_active_box(true);
        $form->selectbox('is_active', $statusOptions, 1, [], 'Account Status');
        
        $form->close('Create User');
        
        $html = $form->render($form->elements);
        
        return view('users.create', compact('html'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'birth_date' => 'required|date|before:today',
        ]);
        
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'birth_date' => $request->birth_date,
            'phone' => $request->phone,
            'is_active' => $request->is_active ?? 1,
        ]);
        
        return redirect()->route('users.index')
                        ->with('message', 'User created successfully!');
    }
}
```

### Edit Form (User Profile Update)
```php
public function edit($id)
{
    $user = User::findOrFail($id);
    
    $form = new Objects();
    
    // Dynamic validation berdasarkan user context
    $validationRules = [
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'phone' => 'nullable|regex:/^[0-9+\-\s()]+$/',
        'bio' => 'nullable|string|max:1000'
    ];
    
    // Add password validation hanya jika diisi
    if (request('password')) {
        $validationRules['password'] = 'nullable|min:8|confirmed';
    }
    
    $form->setValidations($validationRules);
    
    // Model binding untuk auto-fill values
    $form->model($user, $user->id, route('users.update', $user->id));
    
    $form->text('first_name', null, ['required'], 'First Name');
    $form->text('last_name', null, ['required'], 'Last Name');
    $form->email('email', null, ['required'], 'Email Address');
    
    // Optional password change
    $form->password('password', [], 'New Password (leave blank to keep current)');
    $form->password('password_confirmation', [], 'Confirm New Password');
    
    $form->text('phone', null, [], 'Phone Number');
    $form->textarea('bio', null, [], 'Biography');
    
    // Status (hanya admin yang bisa ubah)
    if (auth()->user()->hasRole('admin')) {
        $statusOptions = canvastack_form_active_box(true);
        $form->selectbox('is_active', $statusOptions, $user->is_active, [], 'Account Status');
    }
    
    $form->close('Update Profile');
    
    $html = $form->render($form->elements);
    
    return view('users.edit', compact('user', 'html'));
}
```

### View Mode (Read-only Form)
```php
public function show($id)
{
    $user = User::findOrFail($id);
    
    $form = new Objects();
    
    // Model binding dengan path = false untuk view mode
    $form->model($user, $user->id, false); // false = view mode
    
    $form->text('first_name', null, [], 'First Name');
    $form->text('last_name', null, [], 'Last Name');
    $form->email('email', null, [], 'Email Address');
    $form->date('birth_date', null, [], 'Date of Birth');
    $form->text('phone', null, [], 'Phone Number');
    $form->textarea('bio', null, [], 'Biography');
    
    // Status display
    $statusText = $user->is_active ? 'Active' : 'Inactive';
    $form->text('status_display', $statusText, ['readonly'], 'Status');
    
    $form->close(false); // No action buttons untuk view mode
    
    $html = $form->render($form->elements);
    
    return view('users.show', compact('user', 'html'));
}
```

## 📑 Advanced Features

### Tab-based Form
```php
public function createUserProfile()
{
    $form = new Objects();
    
    // Comprehensive validation rules
    $form->setValidations([
        // Personal
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'email' => 'required|email|unique:users,email',
        'birth_date' => 'required|date|before:today',
        
        // Contact
        'phone' => 'nullable|regex:/^[0-9+\-\s()]+$/',
        'address' => 'nullable|string|max:500',
        'city' => 'nullable|string|max:100',
        'postal_code' => 'nullable|regex:/^[0-9]{5}$/',
        
        // Files
        'avatar' => 'nullable|image|max:2048',
        'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        
        // Settings  
        'timezone' => 'required|string',
        'language' => 'required|string',
        'password' => 'required|min:8|confirmed'
    ]);
    
    $form->modelWithFile(new User()); // File upload enabled
    
    // Tab 1: Personal Information
    $form->openTab('Personal Information', 'fa-user');
    $form->text('first_name', null, ['required'], 'First Name');
    $form->text('last_name', null, ['required'], 'Last Name');
    $form->email('email', null, ['required'], 'Email Address');
    $form->date('birth_date', null, ['required'], 'Date of Birth');
    $form->radiobox('gender', [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other'
    ], null, [], 'Gender');
    $form->closeTab();
    
    // Tab 2: Contact Details
    $form->openTab('Contact Details', 'fa-map-marker');
    $form->text('phone', null, [], 'Phone Number');
    $form->textarea('address', null, [], 'Street Address');
    $form->text('city', null, [], 'City');
    $form->text('postal_code', null, [], 'Postal Code');
    
    // Ajax dependent select untuk provinces
    $form->sync(
        'country', 
        'province', 
        'id', 
        'name',
        'SELECT id, name FROM provinces WHERE country_code = :country'
    );
    $form->selectbox('province', [], null, [], 'Province');
    $form->closeTab();
    
    // Tab 3: Files & Documents
    $form->openTab('Files & Documents', 'fa-file');
    $form->file('avatar', ['imagepreview'], 'Profile Picture');
    $form->file('resume', [], 'Resume/CV');
    $form->closeTab();
    
    // Tab 4: Account Settings
    $form->openTab('Account Settings', 'fa-cog');
    
    $form->password('password', ['required'], 'Password');
    $form->password('password_confirmation', ['required'], 'Confirm Password');
    $form->closeTab();
    
    $form->close('Create Complete Profile');
    
    $html = $form->render($form->elements);
    
    return view('users.create-profile', compact('html'));
}
```

### File Upload Form dengan Processing
```php
class ProfileController extends Controller
{
    public function editProfile()
    {
        $user = auth()->user();
        
        $form = new Objects();
        
        $form->setValidations([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
        ]);
        
        $form->modelWithFile($user, $user->id);
        
        $form->text('name', null, ['required'], 'Full Name');
        $form->textarea('bio', null, [], 'Biography');
        
        // Avatar dengan preview
        $form->file('avatar', [
            'imagepreview', 
            'value' => $user->avatar
        ], 'Profile Picture');
        
        // Cover photo
        $form->file('cover_photo', [
            'imagepreview',
            'value' => $user->cover_photo
        ], 'Cover Photo');
        
        $form->close('Update Profile');
        
        $html = $form->render($form->elements);
        
        return view('profile.edit', compact('html'));
    }
    
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120'
        ]);
        
        // Update basic info
        $user->update($request->only(['name', 'bio']));
        
        // Process file uploads
        if ($request->hasFile('avatar') || $request->hasFile('cover_photo')) {
            $form = new Objects();
            
            $fileInfo = [
                'avatar' => [
                    'file_type' => 'image',
                    'file_validation' => 'image|max:2048',
                    'thumb_name' => 'avatar_thumb',
                    'thumb_size' => [200, 200]
                ]
            ];
            
            $form->fileUpload('users/profile', $request, $fileInfo);
            $uploadedFiles = $form->getFileUploads;
            
            // Update file paths
            if (isset($uploadedFiles['avatar']['file'])) {
                $user->avatar = $uploadedFiles['avatar']['file'];
                $user->avatar_thumbnail = $uploadedFiles['avatar']['thumbnail'];
            }
            
            $user->save();
        }
        
        return redirect()->back()->with('message', 'Profile updated successfully!');
    }
}
```

## 🔄 Dynamic Forms

### Form dengan Conditional Fields
```php
public function dynamicForm()
{
    $form = new Objects();
    
    $userTypes = [
        'individual' => 'Individual',
        'business' => 'Business',
        'organization' => 'Organization'
    ];
    
    $form->open();
    
    // Base fields
    $form->selectbox('user_type', $userTypes, null, [
        'required',
        'onchange' => 'toggleFormFields(this.value)'
    ], 'User Type');
    
    $form->text('email', null, ['required'], 'Email Address');
    
    // Individual fields
    $form->addAttributes(['class' => 'individual-field', 'style' => 'display:none;']);
    $form->text('first_name', null, [], 'First Name');
    $form->text('last_name', null, [], 'Last Name');
    $form->date('birth_date', null, [], 'Date of Birth');
    
    // Business fields  
    $form->addAttributes(['class' => 'business-field', 'style' => 'display:none;']);
    $form->text('company_name', null, [], 'Company Name');
    $form->text('tax_id', null, [], 'Tax ID');
    
    $form->close('Submit Registration');
    
    $html = $form->render($form->elements);
    
    // JavaScript untuk dynamic fields
    $javascript = "
    <script>
    function toggleFormFields(userType) {
        // Hide all conditional fields
        $('.individual-field, .business-field, .organization-field').closest('.form-group').hide();
        
        // Show relevant fields
        if (userType) {
            $('.' + userType + '-field').closest('.form-group').show();
        }
    }
    
    $(document).ready(function() {
        // Initialize form state
        var selectedType = $('select[name=\"user_type\"]').val();
        if (selectedType) {
            toggleFormFields(selectedType);
        }
    });
    </script>
    ";
    
    return view('dynamic-form', compact('html', 'javascript'));
}
```

## 📊 Complex Real-World Examples

### E-Commerce Product Form
```php
class ProductFormBuilder
{
    public function buildProductForm(Product $product = null)
    {
        $form = new Objects();
        $isEdit = !is_null($product);
        
        // Comprehensive validation
        $form->setValidations([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku' . ($isEdit ? ',' . $product->id : ''),
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:active,inactive,draft'
        ]);
        
        if ($isEdit) {
            $form->modelWithFile($product, $product->id);
        } else {
            $form->open(route('products.store'), 'POST', 'route', true);
        }
        
        // Tab 1: Basic Information
        $form->openTab('Basic Information', 'fa-info-circle');
        $form->text('name', null, ['required'], 'Product Name');
        $form->textarea('description', null, ['required', 'class' => 'ckeditor'], 'Description');
        $form->text('sku', null, ['required'], 'SKU');
        $form->number('price', null, ['required', 'step' => '0.01'], 'Price');
        $form->closeTab();
        
        // Tab 2: Categorization
        $form->openTab('Categorization', 'fa-folder');
        $categories = Category::pluck('name', 'id');
        $form->selectbox('category_id', $categories, null, ['required'], 'Category');
        
        $statusOptions = [
            'draft' => 'Draft',
            'active' => 'Active', 
            'inactive' => 'Inactive'
        ];
        $form->selectbox('status', $statusOptions, 'draft', ['required'], 'Status');
        $form->closeTab();
        
        $form->close($isEdit ? 'Update Product' : 'Create Product');
        
        return $form->render($form->elements);
    }
}
```

## 🧩 Integration Patterns

### Form Builder Service Class
```php
class FormBuilderService
{
    private $form;
    
    public function __construct()
    {
        $this->form = new Objects();
    }
    
    public function userRegistrationForm($userTypes = [])
    {
        $this->form->setValidations([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'user_type' => 'required|in:' . implode(',', array_keys($userTypes))
        ]);
        
        $this->form->open(route('register.store'));
        
        $this->form->text('name', null, ['required'], 'Full Name');
        $this->form->email('email', null, ['required'], 'Email Address');
        $this->form->selectbox('user_type', $userTypes, null, ['required'], 'Account Type');
        $this->form->password('password', ['required'], 'Password');
        $this->form->password('password_confirmation', ['required'], 'Confirm Password');
        
        $this->form->checkbox('terms', [1 => 'I agree to the Terms of Service'], [], [], '');
        
        $this->form->close('Create Account');
        
        return $this->form->render($this->form->elements);
    }
}
```

## 🔌 JavaScript Integration

### Real-time Form Enhancement
```php
public function enhancedForm()
{
    $form = new Objects();
    
    $form->open();
    
    // Username dengan availability check
    $form->text('username', null, [
        'required',
        'data-validate-url' => route('validate.username'),
        'data-validate-delay' => '500'
    ], 'Username');
    
    // Password dengan strength meter
    $form->password('password', [
        'required',
        'data-strength-meter' => 'true'
    ], 'Password');
    
    $form->close('Create Account');
    
    $html = $form->render($form->elements);
    
    // Enhanced JavaScript
    $javascript = '
    <script>
    $(document).ready(function() {
        // Real-time username validation
        let usernameTimeout;
        $(\'input[name="username"]\').on(\'input\', function() {
            const username = $(this).val();
            const url = $(this).data(\'validate-url\');
            const delay = $(this).data(\'validate-delay\');
            
            clearTimeout(usernameTimeout);
            
            if (username.length >= 3) {
                usernameTimeout = setTimeout(() => {
                    $.post(url, {username: username})
                        .done(function(response) {
                            const feedback = $(\'#username-feedback\');
                            if (response.available) {
                                feedback.html(\'<span class="text-success"><i class="fa fa-check"></i> Available</span>\');
                            } else {
                                feedback.html(\'<span class="text-danger"><i class="fa fa-times"></i> Already taken</span>\');
                            }
                        });
                }, delay);
            }
        });
        
        // Password strength meter
        $(\'input[name="password"]\').on(\'input\', function() {
            const password = $(this).val();
            const strength = calculatePasswordStrength(password);
            
            $(\'#password-strength\').html(`
                <div class="strength-bar">
                    <div class="strength-fill ${strength.level}" style="width: ${strength.percentage}%"></div>
                </div>
                <span class="strength-text">${strength.text}</span>
            `);
        });
    });
    
    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score += 25;
        if (/[a-z]/.test(password)) score += 25;
        if (/[A-Z]/.test(password)) score += 25;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^a-zA-Z0-9]/.test(password)) score += 10;
        
        if (score < 30) return {level: \'weak\', text: \'Weak\', percentage: score};
        if (score < 60) return {level: \'medium\', text: \'Medium\', percentage: score};
        return {level: \'strong\', text: \'Strong\', percentage: Math.min(score, 100)};
    }
    </script>';
    
    return view('enhanced-form', compact('html', 'javascript'));
}
```

## 💡 Tips & Best Practices

### 1. Form Organization
```php
// ✅ Good: Logical grouping
$form->openTab('Contact Info', 'fa-envelope');
$form->email('email');
$form->text('phone');
$form->closeTab();

// ❌ Bad: Mixed unrelated fields
$form->email('email');
$form->selectbox('country');
$form->password('password');
```

### 2. Validation Strategy
```php
// ✅ Good: Comprehensive validation
$form->setValidations([
    'email' => 'required|email|unique:users,email',
    'phone' => 'required|regex:/^[0-9+\-\s()]+$/',
    'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'
]);

// ❌ Bad: Minimal validation
$form->setValidations([
    'email' => 'required',
    'phone' => 'required'
]);
```

### 3. User Experience
```php
// ✅ Good: Clear labels dan helpful hints
$form->text('username', null, [
    'required',
    'placeholder' => 'Choose a unique username',
    'data-help' => 'Username must be 3-20 characters'
], 'Username');

// ❌ Bad: Unclear labels
$form->text('usr', null, ['required'], 'Usr');
```

### 4. Error Handling
```php
// ✅ Good: Graceful error handling
try {
    $html = $form->render($form->elements);
} catch (Exception $e) {
    Log::error('Form render error: ' . $e->getMessage());
    return view('errors.form-error', ['message' => 'Unable to load form']);
}
```

### 5. Security Considerations
```php
// ✅ Good: Secure file upload
$form->setValidations([
    'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048|dimensions:max_width=2000,max_height=2000'
]);

// ❌ Bad: Unrestricted upload
$form->file('avatar', [], 'Avatar');
```

## 📱 Mobile & Responsive

### Mobile-Optimized Forms
```php
class MobileFormBuilder
{
    public function buildMobileForm()
    {
        $form = new Objects();
        
        $form->open();
        
        // Mobile-friendly input types
        $form->email('email', null, [
            'required',
            'inputmode' => 'email',
            'autocomplete' => 'email'
        ], 'Email');
        
        $form->text('phone', null, [
            'inputmode' => 'tel',
            'autocomplete' => 'tel',
            'pattern' => '[0-9]*'
        ], 'Phone');
        
        $form->close('Submit');
        
        return $form->render($form->elements);
    }
}
```

## 🎨 Custom Styling

### Custom CSS Integration
```css
/* Custom Form Styles */
.form-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group.row {
    margin-bottom: 1.5rem;
}

.control-label {
    font-weight: 600;
    color: #495057;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.required {
    color: #dc3545;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .form-container {
        padding: 1rem;
    }
    
    .col-sm-3,
    .col-sm-9 {
        width: 100%;
    }
}
```

---

**Next**: [Model Binding & Data Flow](./MODEL_BINDING.md)