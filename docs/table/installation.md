# Installation & Setup

This guide will walk you through installing and setting up CanvaStack Table in your Laravel application.

## Requirements

- PHP 8.0 or higher
- Laravel 9.0 or higher
- MySQL 5.7+ or PostgreSQL 10+
- Node.js 16+ (for asset compilation)

## Installation

### 1. Install via Composer

```bash
composer require canvastack/canvastack
```

### 2. Publish Configuration Files

```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"
```

This will publish:
- `config/canvastack.php` - Main configuration
- `config/canvastack-security.php` - Security configuration
- Database migrations
- Asset files

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Publish Assets

```bash
php artisan vendor:publish --tag=canvastack-assets --force
```

## Configuration

### Basic Configuration

Edit `config/canvastack.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Table Configuration
    |--------------------------------------------------------------------------
    */
    'table' => [
        'default_method' => 'GET', // GET or POST
        'server_side' => true,
        'pagination' => true,
        'searching' => true,
        'ordering' => true,
        'responsive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'canvastack',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    */
    'assets' => [
        'css_path' => 'vendor/canvastack/css',
        'js_path' => 'vendor/canvastack/js',
        'image_path' => 'vendor/canvastack/images',
    ],
];
```

### Security Configuration

Edit `config/canvastack-security.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Mode
    |--------------------------------------------------------------------------
    | Options: 'full', 'hardened', 'basic', 'custom', 'disabled'
    */
    'mode' => env('CANVASTACK_SECURITY_MODE', 'hardened'),

    /*
    |--------------------------------------------------------------------------
    | Core Security Settings
    |--------------------------------------------------------------------------
    */
    'core' => [
        'input_validation' => [
            'enabled' => true,
            'sql_injection_protection' => true,
            'xss_protection' => true,
        ],
        'parameter_binding' => [
            'enabled' => true,
            'force_prepared_statements' => true,
        ],
    ],
];
```

## Environment Variables

Add these to your `.env` file:

```env
# CanvaStack Configuration
CANVASTACK_SECURITY_MODE=hardened
CANVASTACK_ADVANCED_SECURITY=false
CANVASTACK_DEBUG_MODE=false

# Database Configuration (if using separate connection)
CANVASTACK_DB_CONNECTION=mysql
CANVASTACK_DB_HOST=127.0.0.1
CANVASTACK_DB_PORT=3306
CANVASTACK_DB_DATABASE=your_database
CANVASTACK_DB_USERNAME=your_username
CANVASTACK_DB_PASSWORD=your_password
```

## Controller Setup

### 1. Extend CanvaStack Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Controllers\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        // Initialize with your model and route prefix
        parent::__construct(User::class, 'users');
    }

    public function index()
    {
        $this->setPage(); // Set page metadata

        // Configure your table
        $this->table->searchable()
                    ->clickable()
                    ->sortable()
                    ->lists('users', ['name', 'email', 'created_at']);

        return $this->render();
    }
}
```

### 2. Alternative: Use Trait in Existing Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Controller;
use Canvastack\Canvastack\Traits\TableTrait;

class UserController extends Controller
{
    use TableTrait;

    public function index()
    {
        $this->initializeTable(User::class);

        $this->table->searchable()
                    ->clickable()
                    ->lists('users', ['name', 'email', 'created_at']);

        return view('users.index', [
            'table' => $this->table->render()
        ]);
    }
}
```

## View Setup

### 1. Basic Blade Template

Create `resources/views/users/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Users Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Users</h3>
                    <div class="card-tools">
                        <a href="{{ route('users.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add User
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {!! $table !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <!-- CanvaStack CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack-table.css') }}">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/datatables/datatables.min.css') }}">
@endpush

@push('scripts')
    <!-- DataTables JS -->
    <script src="{{ asset('vendor/datatables/datatables.min.js') }}"></script>
    <!-- CanvaStack JS -->
    <script src="{{ asset('vendor/canvastack/js/canvastack-table.js') }}"></script>
@endpush
```

### 2. Include Required Assets

In your main layout (`resources/views/layouts/app.blade.php`):

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'CanvaStack App')</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
```

## Routes Setup

Add routes to `routes/web.php`:

```php
<?php

use App\Http\Controllers\UserController;

Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);
    
    // CanvaStack AJAX routes (automatically registered)
    Route::prefix('canvastack')->group(function () {
        Route::post('ajax/post', [UserController::class, 'ajaxPost'])
             ->name('canvastack.ajax.post');
        Route::get('ajax/get', [UserController::class, 'ajaxGet'])
             ->name('canvastack.ajax.get');
    });
});
```

## Database Setup

### 1. Basic User Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('fullname');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('photo')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->date('expire_date')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
```

### 2. User Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'fullname', 
        'email',
        'password',
        'photo',
        'address',
        'phone',
        'active',
        'expire_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
        'expire_date' => 'date',
    ];

    // Define relationships for CanvaStack
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
```

## Verification

### 1. Test Basic Setup

Visit your application at `/users` and you should see:
- A DataTable with your users
- Search functionality
- Sorting capabilities
- Pagination

### 2. Check Console for Errors

Open browser developer tools and check for:
- JavaScript errors
- Failed AJAX requests
- CSS loading issues

### 3. Verify Security

Check that security features are working:
- CSRF tokens are included in AJAX requests
- SQL injection attempts are blocked
- XSS attempts are sanitized

## Troubleshooting

### Common Issues

#### 1. Assets Not Loading
```bash
# Re-publish assets
php artisan vendor:publish --tag=canvastack-assets --force

# Clear cache
php artisan cache:clear
php artisan view:clear
```

#### 2. AJAX Requests Failing
Check your routes and middleware configuration:
```php
// In routes/web.php
Route::middleware(['web', 'auth'])->group(function () {
    // Your routes here
});
```

#### 3. Database Connection Issues
Verify your database configuration in `.env` and test connection:
```bash
php artisan migrate:status
```

#### 4. Permission Issues
Ensure proper file permissions:
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## Next Steps

Now that you have CanvaStack Table installed and configured:

1. Read the [Quick Start Guide](quick-start.md) for basic usage
2. Explore [Basic Usage](basic-usage.md) for common patterns
3. Check out [Configuration](configuration.md) for advanced options
4. Review [Security Features](advanced/security.md) for production deployment

---

## Need Help?

- [Quick Start Guide](quick-start.md) - Get up and running quickly
- [Configuration](configuration.md) - Detailed configuration options
- [Troubleshooting](advanced/troubleshooting.md) - Common issues and solutions
- [API Reference](api/objects.md) - Complete method documentation