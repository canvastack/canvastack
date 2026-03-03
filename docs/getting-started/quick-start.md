# Quick Start Guide

Get started with CanvaStack in 5 minutes! This guide walks you through creating your first form and table.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Your First Form](#your-first-form)
3. [Your First Table](#your-first-table)
4. [Combining Form and Table](#combining-form-and-table)
5. [Next Steps](#next-steps)

---

## Prerequisites

Before starting, ensure you have:

- ✅ Laravel 12.x installed
- ✅ CanvaStack package installed ([Installation Guide](installation.md))
- ✅ Database configured and migrated
- ✅ Basic understanding of Laravel

---

## Your First Form

Let's create a simple contact form in just a few steps.

### Step 1: Create Controller

Generate a controller:

```bash
php artisan make:controller ContactController
```

### Step 2: Add Form Logic

Edit `app/Http/Controllers/ContactController.php`:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create(FormBuilder $form)
    {
        // Create form fields
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
            ->rows(6)
            ->maxLength(1000)
            ->placeholder('Your message here...');
        
        return view('contact.create', ['form' => $form]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:1000',
        ]);
        
        // Process the contact form (send email, save to database, etc.)
        
        return redirect()->back()->with('success', 'Message sent successfully!');
    }
}
```

### Step 3: Create View

Create `resources/views/contact/create.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                Contact Us
            </h1>
            
            @if(session('success'))
                <div class="alert alert-success mb-4">
                    {{ session('success') }}
                </div>
            @endif
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <form method="POST" action="{{ route('contact.store') }}">
                    @csrf
                    {!! $form->render() !!}
                    
                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
```

### Step 4: Add Routes

Add to `routes/web.php`:

```php
use App\Http\Controllers\ContactController;

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
```

### Step 5: Test Your Form

Visit `http://your-app.test/contact` and you'll see a beautiful, functional contact form!

**Features you get automatically:**
- ✅ Icons on input fields
- ✅ Character counter on textarea
- ✅ Client-side validation
- ✅ Dark mode support
- ✅ Responsive design
- ✅ Error display

---

## Your First Table

Now let's create a data table to display users.

### Step 1: Create Controller

```bash
php artisan make:controller UserController
```

### Step 2: Add Table Logic

Edit `app/Http/Controllers/UserController.php`:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;

class UserController extends Controller
{
    public function index(TableBuilder $table)
    {
        // Set the model
        $table->setModel(User::class);
        
        // Define columns
        $table->column('id', 'ID')
            ->sortable()
            ->searchable();
        
        $table->column('name', 'Name')
            ->sortable()
            ->searchable();
        
        $table->column('email', 'Email')
            ->sortable()
            ->searchable();
        
        $table->column('created_at', 'Joined')
            ->sortable()
            ->format('date', 'M d, Y');
        
        // Add actions
        $table->action('edit', 'Edit', 'users.edit')
            ->icon('edit')
            ->color('primary');
        
        $table->action('delete', 'Delete', 'users.destroy')
            ->icon('trash')
            ->color('danger')
            ->confirm('Are you sure?');
        
        // Format the table
        $table->format();
        
        return view('users.index', ['table' => $table]);
    }
}
```

### Step 3: Create View

Create `resources/views/users/index.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Users
            </h1>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                Add User
            </a>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md">
            {!! $table->render() !!}
        </div>
    </div>
</body>
</html>
```

### Step 4: Add Route

Add to `routes/web.php`:

```php
use App\Http\Controllers\UserController;

Route::get('/users', [UserController::class, 'index'])->name('users.index');
```

### Step 5: Test Your Table

Visit `http://your-app.test/users` and you'll see a feature-rich data table!

**Features you get automatically:**
- ✅ Sorting on columns
- ✅ Search functionality
- ✅ Pagination
- ✅ Action buttons
- ✅ Responsive design
- ✅ Dark mode support
- ✅ Performance optimized

---

## Combining Form and Table

Let's create a complete CRUD interface for managing users.

### Step 1: Complete Controller

Update `app/Http/Controllers/UserController.php`:

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(TableBuilder $table)
    {
        $table->setModel(User::class);
        
        $table->column('id', 'ID')->sortable()->searchable();
        $table->column('name', 'Name')->sortable()->searchable();
        $table->column('email', 'Email')->sortable()->searchable();
        $table->column('created_at', 'Joined')->sortable()->format('date', 'M d, Y');
        
        $table->action('edit', 'Edit', 'users.edit')->icon('edit')->color('primary');
        $table->action('delete', 'Delete', 'users.destroy')->icon('trash')->color('danger')->confirm('Are you sure?');
        
        $table->format();
        
        return view('users.index', ['table' => $table]);
    }
    
    public function create(FormBuilder $form)
    {
        $form->text('name', 'Full Name')
            ->required()
            ->icon('user')
            ->maxLength(255);
        
        $form->email('email', 'Email Address')
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
        
        return view('users.create', ['form' => $form]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);
        
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        
        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }
    
    public function edit(User $user, FormBuilder $form)
    {
        $form->setModel($user);
        
        $form->text('name', 'Full Name')
            ->required()
            ->icon('user')
            ->maxLength(255);
        
        $form->email('email', 'Email Address')
            ->required()
            ->icon('mail');
        
        $form->password('password', 'New Password')
            ->minLength(8)
            ->icon('lock')
            ->help('Leave blank to keep current password');
        
        return view('users.edit', ['form' => $form, 'user' => $user]);
    }
    
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
        ]);
        
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();
        
        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }
    
    public function destroy(User $user)
    {
        $user->delete();
        
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }
}
```

### Step 2: Create Views

Create `resources/views/users/create.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Add New User</h1>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                {!! $form->render() !!}
                
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Create User
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

Create `resources/views/users/edit.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit User</h1>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                {!! $form->render() !!}
                
                <div class="mt-6 flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Update User
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
```

### Step 3: Add Routes

Update `routes/web.php`:

```php
use App\Http\Controllers\UserController;

Route::resource('users', UserController::class);
```

### Step 4: Test Complete CRUD

Now you have a complete CRUD interface:

- **List**: `http://your-app.test/users`
- **Create**: `http://your-app.test/users/create`
- **Edit**: `http://your-app.test/users/{id}/edit`
- **Delete**: Click delete button in table

---

## What You've Learned

In just 5 minutes, you've learned how to:

✅ Create forms with validation  
✅ Build data tables with sorting and search  
✅ Combine forms and tables for CRUD operations  
✅ Use icons and styling  
✅ Handle form submissions  
✅ Display success messages

---

## Next Steps

### Explore More Features

1. **Advanced Forms**
   - Tabbed forms
   - Cascading dropdowns
   - File uploads
   - Rich text editing
   - [Form Documentation](../components/form/README.md)

2. **Advanced Tables**
   - Relations and eager loading
   - Custom formatting
   - Bulk actions
   - Export functionality
   - [Table Documentation](../components/table/README.md)

3. **Customization**
   - Theme configuration
   - Custom styling
   - Dark mode
   - [Configuration Guide](configuration.md)

### Learn More

- [Form API Reference](../components/form/api-reference.md)
- [Table API Reference](../components/table/api-reference.md)
- [Form Examples](../components/form/examples.md)
- [Table Examples](../components/table/examples.md)

### Best Practices

- Use validation on both client and server side
- Enable caching for better performance
- Use eager loading to prevent N+1 queries
- Follow Laravel conventions
- Test your forms and tables

---

## Common Patterns

### Pattern 1: Search Form

```php
$form->text('search', 'Search')
    ->placeholder('Search...')
    ->icon('search');

$form->select('status', 'Status', [
    '' => 'All',
    'active' => 'Active',
    'inactive' => 'Inactive'
]);
```

### Pattern 2: Filter Table

```php
$table->filter('status', 'Status', [
    'active' => 'Active',
    'inactive' => 'Inactive'
]);

$table->dateRangeFilter('created_at', 'Date Range');
```

### Pattern 3: Bulk Actions

```php
$table->bulkAction('delete', 'Delete Selected')
    ->confirm('Delete selected items?');

$table->bulkAction('export', 'Export Selected')
    ->icon('download');
```

---

## Tips and Tricks

### Tip 1: Use Method Chaining

```php
$form->text('name', 'Name')
    ->required()
    ->icon('user')
    ->placeholder('Enter name')
    ->maxLength(100)
    ->help('Your full name');
```

### Tip 2: Cache Tables for Performance

```php
$table->cache(300); // Cache for 5 minutes
```

### Tip 3: Use Eager Loading

```php
$table->setModel(User::class);
$table->eager(['profile', 'roles']);
```

### Tip 4: Custom Validation Messages

```php
$request->validate($form->getValidations(), [
    'email.required' => 'Please provide your email address.',
    'email.unique' => 'This email is already registered.'
]);
```

---

## Getting Help

If you encounter any issues:

1. Check the [Troubleshooting Guide](installation.md#troubleshooting)
2. Review the [Documentation](../README.md)
3. Search [GitHub Issues](https://github.com/canvastack/canvastack/issues)
4. Ask in [Discussions](https://github.com/canvastack/canvastack/discussions)

---

**Congratulations!** You've completed the Quick Start guide. You're now ready to build amazing applications with CanvaStack!

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-26  
**Status**: Production Ready
