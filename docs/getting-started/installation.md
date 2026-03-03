# Installation Guide

Complete guide for installing CanvaStack in your Laravel application.

## 📦 Requirements

### System Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 12.x
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 7.x (recommended) or File cache
- **Node.js**: 18.x or higher (for asset compilation)
- **Composer**: 2.x

### PHP Extensions

Required PHP extensions:
- `ext-json`
- `ext-mbstring`
- `ext-pdo`
- `ext-tokenizer`
- `ext-xml`
- `ext-ctype`
- `ext-fileinfo`
- `ext-openssl`

Recommended PHP extensions:
- `ext-redis` (for Redis cache)
- `ext-gd` or `ext-imagick` (for image processing)
- `ext-zip` (for export features)

---

## 🚀 Installation Methods

### Method 1: Composer (Recommended)

#### Step 1: Install Package

```bash
composer require canvastack/canvastack
```

#### Step 2: Publish Assets

```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider"
```

This will publish:
- Configuration files to `config/`
- Views to `resources/views/vendor/canvastack/`
- Assets to `public/vendor/canvastack/`
- Migrations to `database/migrations/`

#### Step 3: Run Migrations

```bash
php artisan migrate
```

#### Step 4: Install Frontend Dependencies

```bash
npm install
npm run build
```

#### Step 5: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

### Method 2: Manual Installation (Development)

#### Step 1: Clone Repository

```bash
cd packages
mkdir -p canvastack
cd canvastack
git clone https://github.com/canvastack/canvastack.git
```

#### Step 2: Update composer.json

Add to your Laravel application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/canvastack/canvastack"
        }
    ],
    "require": {
        "canvastack/canvastack": "@dev"
    }
}
```

#### Step 3: Install Package

```bash
composer update canvastack/canvastack
```

#### Step 4: Follow Steps 2-5 from Method 1

---

## ⚙️ Configuration

### Environment Variables

Add to your `.env` file:

```env
# CanvaStack Configuration
CANVASTACK_CACHE_ENABLED=true
CANVASTACK_CACHE_DRIVER=redis
CANVASTACK_CACHE_TTL=3600

# Theme Configuration
CANVASTACK_THEME=default
CANVASTACK_DARK_MODE=true

# Locale Configuration
CANVASTACK_LOCALE=en
CANVASTACK_FALLBACK_LOCALE=en
```

### Configuration Files

#### 1. Main Configuration (`config/canvastack.php`)

```php
return [
    'app' => [
        'name' => env('APP_NAME', 'CanvaStack'),
        'version' => '1.0.0',
    ],
    
    'cache' => [
        'enabled' => env('CANVASTACK_CACHE_ENABLED', true),
        'driver' => env('CANVASTACK_CACHE_DRIVER', 'redis'),
        'ttl' => [
            'forms' => 3600,
            'tables' => 300,
            'permissions' => 3600,
        ],
    ],
    
    'performance' => [
        'chunk_size' => 100,
        'eager_load' => true,
        'query_cache' => true,
    ],
];
```

#### 2. UI Configuration (`config/canvastack-ui.php`)

```php
return [
    'theme' => [
        'default' => env('CANVASTACK_THEME', 'default'),
        'registry' => [
            // Theme definitions
        ],
    ],
    
    'dark_mode' => [
        'enabled' => env('CANVASTACK_DARK_MODE', true),
        'default' => 'light',
    ],
];
```

#### 3. RBAC Configuration (`config/canvastack-rbac.php`)

```php
return [
    'roles' => [
        'admin' => [
            'name' => 'Administrator',
            'level' => 1,
        ],
    ],
    
    'permissions' => [
        'users.create',
        'users.edit',
        'users.delete',
    ],
];
```

---

## 🗄️ Database Setup

### Redis Configuration

#### Install Redis

**Ubuntu/Debian:**
```bash
sudo apt-get install redis-server
sudo systemctl start redis
sudo systemctl enable redis
```

**macOS:**
```bash
brew install redis
brew services start redis
```

**Windows:**
Download from [Redis Windows](https://github.com/microsoftarchive/redis/releases)

#### Configure Laravel

Update `.env`:
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### MySQL Configuration

Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## 🎨 Frontend Setup

### Install Dependencies

```bash
npm install
```

This installs:
- Tailwind CSS 3.x
- DaisyUI 4.x
- Alpine.js 3.x
- GSAP 3.x
- Lucide Icons
- ApexCharts

### Build Assets

**Development:**
```bash
npm run dev
```

**Production:**
```bash
npm run build
```

### Vite Configuration

The package includes a pre-configured `vite.config.js`:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/canvastack.css',
                'resources/js/canvastack.js',
            ],
            refresh: true,
        }),
    ],
});
```

---

## 🔐 Security Setup

### Generate Application Key

```bash
php artisan key:generate
```

### Set Permissions

**Linux/macOS:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Windows:**
Ensure the web server has write access to `storage/` and `bootstrap/cache/`

### Configure CORS (if using API)

Install Laravel Sanctum:
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## ✅ Verification

### Check Installation

Run the verification command:
```bash
php artisan canvastack:check
```

This checks:
- PHP version and extensions
- Database connection
- Redis connection
- File permissions
- Configuration files
- Asset compilation

### Test Components

Create a test route in `routes/web.php`:

```php
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Table\TableBuilder;

Route::get('/test-canvastack', function (FormBuilder $form, TableBuilder $table) {
    $form->setContext('admin');
    $form->text('name', 'Name')->required();
    
    $table->setContext('admin');
    $table->setData([
        ['name' => 'Test', 'value' => '123'],
    ]);
    $table->setFields(['name:Name', 'value:Value']);
    $table->format();
    
    return view('canvastack::test', compact('form', 'table'));
});
```

Visit `http://your-app.test/test-canvastack` to verify.

---

## 🔧 Troubleshooting

### Common Issues

#### Issue 1: Class Not Found

**Error:**
```
Class 'Canvastack\Canvastack\CanvastackServiceProvider' not found
```

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

#### Issue 2: Assets Not Loading

**Error:**
Assets return 404

**Solution:**
```bash
php artisan vendor:publish --provider="Canvastack\Canvastack\CanvastackServiceProvider" --force
npm run build
php artisan view:clear
```

#### Issue 3: Redis Connection Failed

**Error:**
```
Connection refused [tcp://127.0.0.1:6379]
```

**Solution:**
```bash
# Check Redis is running
redis-cli ping

# If not running, start it
sudo systemctl start redis

# Or use file cache instead
# Update .env:
CACHE_DRIVER=file
```

#### Issue 4: Migration Failed

**Error:**
```
SQLSTATE[42S01]: Base table or view already exists
```

**Solution:**
```bash
# Check existing tables
php artisan migrate:status

# Rollback if needed
php artisan migrate:rollback

# Re-run migrations
php artisan migrate
```

#### Issue 5: Permission Denied

**Error:**
```
The stream or file "storage/logs/laravel.log" could not be opened
```

**Solution:**
```bash
# Linux/macOS
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Or use current user
sudo chown -R $USER:www-data storage bootstrap/cache
```

---

## 🚀 Next Steps

After successful installation:

1. **Read Quick Start Guide**: [quick-start.md](quick-start.md)
2. **Configure Theme**: [../features/theming.md](../features/theming.md)
3. **Setup RBAC**: [../features/rbac.md](../features/rbac.md)
4. **Learn Components**: [../components/](../components/)
5. **Explore Examples**: [../guides/](../guides/)

---

## 📚 Additional Resources

### Documentation

- [Configuration Guide](configuration.md)
- [Project Structure](project-structure.md)
- [Component Reference](../components/)
- [API Reference](../api/)

### External Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Tailwind CSS](https://tailwindcss.com)
- [Alpine.js](https://alpinejs.dev)
- [DaisyUI](https://daisyui.com)

### Support

- **GitHub Issues**: [github.com/canvastack/canvastack/issues](https://github.com/canvastack/canvastack/issues)
- **Documentation**: [docs.canvastack.com](https://docs.canvastack.com)
- **Community**: [community.canvastack.com](https://community.canvastack.com)

---

**Last Updated**: 2026-03-01  
**Version**: 1.0.0  
**Status**: Published
