# Form Component Usage Examples

Complete usage examples for the CanvaStack Enhanced Form Component, demonstrating real-world scenarios and best practices.

## Table of Contents

1. [Basic Forms](#basic-forms)
2. [Tabbed Forms](#tabbed-forms)
3. [Cascading Dropdowns](#cascading-dropdowns)
4. [File Uploads](#file-uploads)
5. [Rich Text Editing](#rich-text-editing)
6. [Advanced Features](#advanced-features)
7. [Complete Applications](#complete-applications)

---

## Basic Forms

### Simple Contact Form

```php
public function contactForm()
{
    $form = app(FormBuilder::class);
    
    $form->text('name', 'Full Name')
        ->required()
        ->icon('user')
        ->placeholder('Enter your name');
    
    $form->email('email', 'Email Address')
        ->required()
        ->icon('mail')
        ->placeholder('you@example.com');
    
    $form->text('subject', 'Subject')
        ->required()
        ->maxLength(200);
    
    $form->textarea('message', 'Message')
        ->required()
        ->maxLength(1000)
        ->rows(6)
        ->placeholder('Your message here...');
    
    return view('contact', ['form' => $form]);
}
```

### User Registration Form

```php
public function register()
{
    $form = app(FormBuilder::class);
    
    $form->text('name', 'Full Name')
        ->required()
        ->icon('user');
    
    $form->email('email', 'Email')
        ->required()
        ->icon('mail');
    
    $form->password('password', 'Password')
        ->required()
        ->minLength(8)
        ->icon('lock')
        ->help('Minimum 8 characters');
    
    $form->password('password_confirmation', 'Confirm Password')
        ->required()
        ->icon('lock');
    
    $form->checkbox('terms', 'Terms', ['1' => 'I agree to the terms and conditions'])
        ->required();
    
    return view('auth.register', ['form' => $form]);
}
```

---

## Tabbed Forms

### User Profile with Tabs

```php
public function editProfile($id)
{
    $user = User::findOrFail($id);
    $form = app(FormBuilder::class);
    $form->setModel($user);
    
    // Personal Information Tab
    $form->openTab('Personal Information', 'active');
    $form->text('first_name', 'First Name')->required()->icon('user');
    $form->text('last_name', 'Last Name')->required()->icon('user');
    $form->email('email', 'Email')->required()->icon('mail');
    $form->date('birth_date', 'Birth Date')->required()->icon('calendar');
    $form->file('avatar', 'Profile Picture')
        ->accept('image/*')
        ->imagepreview()
        ->maxSize(2048);
    $form->closeTab();
    
    // Contact Information Tab
    $form->openTab('Contact Information');
    $form->text('phone', 'Phone')->icon('phone');
    $form->text('mobile', 'Mobile')->icon('smartphone');
    $form->textarea('address', 'Address')->rows(3)->icon('map-pin');
    $form->closeTab();
    
    // Settings Tab
    $form->openTab('Settings');
    $form->checkbox('email_notifications', 'Email Notifications')
        ->switch('md', 'primary');
    $form->checkbox('sms_notifications', 'SMS Notifications')
        ->switch('md', 'primary');
    $form->closeTab();
    
    return view('profile.edit', ['form' => $form, 'user' => $user]);
}
```

---

## Cascading Dropdowns

### Country → Province → City

```php
public function createAddress()
{
    $form = app(FormBuilder::class);
    
    $countries = Country::pluck('name', 'id')->toArray();
    
    $form->select('country_id', 'Country', $countries)
        ->searchable()
        ->required();
    
    $form->select('province_id', 'Province', [])
        ->searchable()
        ->required();
    
    $form->select('city_id', 'City', [])
        ->searchable()
        ->required();
    
    $form->sync(
        'country_id',
        'province_id',
        'id',
        'name',
        "SELECT id, name FROM provinces WHERE country_id = ? ORDER BY name"
    );
    
    $form->sync(
        'province_id',
        'city_id',
        'id',
        'name',
        "SELECT id, name FROM cities WHERE province_id = ? ORDER BY name"
    );
    
    return view('addresses.create', ['form' => $form]);
}
```

### Edit Form with Pre-selection

```php
public function editAddress($id)
{
    $address = Address::findOrFail($id);
    $form = app(FormBuilder::class);
    $form->setModel($address);
    
    $countries = Country::pluck('name', 'id')->toArray();
    $provinces = Province::where('country_id', $address->country_id)
        ->pluck('name', 'id')->toArray();
    $cities = City::where('province_id', $address->province_id)
        ->pluck('name', 'id')->toArray();
    
    $form->select('country_id', 'Country', $countries, $address->country_id)
        ->searchable()
        ->required();
    
    $form->select('province_id', 'Province', $provinces, $address->province_id)
        ->searchable()
        ->required();
    
    $form->select('city_id', 'City', $cities, $address->city_id)
        ->searchable()
        ->required();
    
    // Add pre-selection (6th parameter)
    $form->sync(
        'country_id',
        'province_id',
        'id',
        'name',
        "SELECT id, name FROM provinces WHERE country_id = ? ORDER BY name",
        $address->province_id
    );
    
    $form->sync(
        'province_id',
        'city_id',
        'id',
        'name',
        "SELECT id, name FROM cities WHERE province_id = ? ORDER BY name",
        $address->city_id
    );
    
    return view('addresses.edit', ['form' => $form, 'address' => $address]);
}
```

---

## File Uploads

### Avatar Upload with Preview

```php
public function uploadAvatar()
{
    $form = app(FormBuilder::class);
    
    $form->file('avatar', 'Profile Picture')
        ->accept('image/jpeg,image/png,image/gif')
        ->imagepreview()
        ->maxSize(2048)
        ->required()
        ->help('Maximum 2MB. Accepted: JPG, PNG, GIF');
    
    return view('profile.avatar', ['form' => $form]);
}

// Controller handler
public function storeAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|max:2048'
    ]);
    
    $processor = app(FileProcessor::class);
    
    $result = $processor->process($request->file('avatar'), 'uploads/avatars', [
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'max_size' => 2048,
        'thumbnail' => true,
        'thumbnail_width' => 150,
        'thumbnail_height' => 150
    ]);
    
    auth()->user()->update([
        'avatar' => $result['file_path'],
        'avatar_thumb' => $result['thumbnail_path']
    ]);
    
    return redirect()->back()->with('success', 'Avatar updated');
}
```

### Multiple Document Upload

```php
public function uploadDocuments()
{
    $form = app(FormBuilder::class);
    
    $form->file('resume', 'Resume')
        ->accept('.pdf,.doc,.docx')
        ->maxSize(5120)
        ->required();
    
    $form->file('certificates', 'Certificates')
        ->accept('.pdf,.jpg,.jpeg,.png')
        ->multiple()
        ->maxSize(10240);
    
    return view('applications.documents', ['form' => $form]);
}
```

---

## Rich Text Editing

### Blog Post Editor

```php
public function createPost()
{
    $form = app(FormBuilder::class);
    
    $form->text('title', 'Post Title')
        ->required()
        ->maxLength(200);
    
    $form->textarea('content', 'Content')
        ->ckeditor([
            'toolbar' => 'full',
            'height' => 500,
            'imageUpload' => true
        ])
        ->required();
    
    $form->textarea('excerpt', 'Excerpt')
        ->ckeditor([
            'toolbar' => 'minimal',
            'height' => 150
        ])
        ->maxLength(300);
    
    $form->tags('tags', 'Tags')->maxTags(10);
    
    $form->checkbox('published', 'Status', ['1' => 'Publish'])
        ->switch('md', 'success');
    
    return view('posts.create', ['form' => $form]);
}
```

---

## Advanced Features

### Complete Product Form

```php
public function editProduct($id)
{
    $product = Product::findOrFail($id);
    $form = app(FormBuilder::class);
    $form->setModel($product);
    
    // Basic Info Tab
    $form->openTab('Basic Information', 'active');
    $form->text('name', 'Product Name')->required();
    $form->number('price', 'Price')->min(0)->step(0.01)->required();
    $form->textarea('description', 'Description')
        ->ckeditor(['toolbar' => 'full', 'height' => 400]);
    $form->closeTab();
    
    // Images Tab
    $form->openTab('Images');
    $form->file('main_image', 'Main Image')
        ->accept('image/*')
        ->imagepreview()
        ->maxSize(5120);
    $form->file('gallery', 'Gallery')
        ->accept('image/*')
        ->multiple();
    $form->closeTab();
    
    // Settings Tab
    $form->openTab('Settings');
    $form->checkbox('is_active', 'Active')->switch('lg', 'success');
    $form->checkbox('is_featured', 'Featured')->switch('md', 'primary');
    $form->tags('keywords', 'Keywords')->maxTags(15);
    $form->closeTab();
    
    return view('products.edit', ['form' => $form, 'product' => $product]);
}
```

---

For more examples, see the complete documentation in `form-missing-features-examples.md`.

**Version**: 1.0.0  
**Last Updated**: 2026-02-25
