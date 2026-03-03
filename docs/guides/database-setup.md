# Development Database Setup Guide

## Overview

This guide covers setting up the development database for CanvaStack package development and testing.

---

## 1. Database Requirements

### Minimum Requirements
- **MySQL**: 8.0+ (recommended) or MariaDB 10.3+
- **PHP**: 8.2+ with PDO MySQL extension
- **Storage**: 500MB minimum for development

### Recommended Setup
- MySQL 8.0+ for better performance and JSON support
- InnoDB storage engine
- UTF8MB4 character set
- Separate databases for development and testing

---

## 2. Database Creation

### 2.1 Create Development Database

```sql
-- Connect to MySQL
mysql -u root -p

-- Create development database
CREATE DATABASE canvastack_dev
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Create testing database
CREATE DATABASE canvastack_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Create dedicated user (optional but recommended)
CREATE USER 'canvastack_user'@'localhost' IDENTIFIED BY 'secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON canvastack_dev.* TO 'canvastack_user'@'localhost';
GRANT ALL PRIVILEGES ON canvastack_test.* TO 'canvastack_user'@'localhost';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verify databases
SHOW DATABASES LIKE 'canvastack%';

-- Exit
EXIT;
```

### 2.2 Using Command Line

```bash
# Create development database
mysql -u root -p -e "CREATE DATABASE canvastack_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create testing database
mysql -u root -p -e "CREATE DATABASE canvastack_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Verify
mysql -u root -p -e "SHOW DATABASES LIKE 'canvastack%';"
```

---

## 3. Environment Configuration

### 3.1 Update .env File

Create `.env` file in package root (copy from `.env.example`):

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=canvastack_dev
DB_USERNAME=root
DB_PASSWORD=

# Testing Database
DB_TEST_DATABASE=canvastack_test
```

### 3.2 Laravel Database Configuration

If integrating with Laravel application, update `config/database.php`:

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'canvastack_dev'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
    
    'mysql_test' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_TEST_DATABASE', 'canvastack_test'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
    ],
],
```

---

## 4. Database Schema

### 4.1 Core Tables

Create initial migration structure:

```bash
cd packages/canvastack/canvastack
php artisan make:migration create_canvastack_core_tables
```

### 4.2 Users & Authentication Tables

```sql
-- Users table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personal access tokens (Sanctum)
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.3 RBAC Tables

```sql
-- Roles table
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    level INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions table
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    module VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_name (name),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-User pivot table
CREATE TABLE role_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_user (role_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permission-Role pivot table
CREATE TABLE permission_role (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission_role (permission_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.4 Activity Log Table

```sql
-- User activity log
CREATE TABLE user_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(255) NOT NULL,
    model_type VARCHAR(255) NULL,
    model_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    properties JSON NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_model (model_type, model_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.5 Cache Table (Fallback)

```sql
-- Cache table (for database cache driver fallback)
CREATE TABLE cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL,
    INDEX idx_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache locks table
CREATE TABLE cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL,
    INDEX idx_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 5. Seed Data

### 5.1 Create Seeder

Create `database/seeders/CanvastackDevelopmentSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CanvastackDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $adminRole = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full system access',
            'level' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userRole = DB::table('roles')->insertGetId([
            'name' => 'user',
            'display_name' => 'User',
            'description' => 'Basic user access',
            'level' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create permissions
        $permissions = [
            ['name' => 'users.view', 'display_name' => 'View Users', 'module' => 'users'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'module' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'module' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'module' => 'users'],
            ['name' => 'roles.manage', 'display_name' => 'Manage Roles', 'module' => 'rbac'],
            ['name' => 'permissions.manage', 'display_name' => 'Manage Permissions', 'module' => 'rbac'],
        ];

        foreach ($permissions as $permission) {
            $permissionId = DB::table('permissions')->insertGetId(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Assign all permissions to admin role
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminRole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create admin user
        $adminUser = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'email' => 'admin@canvastack.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign admin role to admin user
        DB::table('role_user')->insert([
            'role_id' => $adminRole,
            'user_id' => $adminUser,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create regular user
        $regularUser = DB::table('users')->insertGetId([
            'name' => 'Regular User',
            'email' => 'user@canvastack.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign user role to regular user
        DB::table('role_user')->insert([
            'role_id' => $userRole,
            'user_id' => $regularUser,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

### 5.2 Run Seeder

```bash
php artisan db:seed --class=CanvastackDevelopmentSeeder
```

---

## 6. Database Testing

### 6.1 Connection Test

Create `tests/Feature/DatabaseConnectionTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_database_connection(): void
    {
        $this->assertTrue(DB::connection()->getDatabaseName() === 'canvastack_dev');
    }

    public function test_can_query_database(): void
    {
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    public function test_tables_exist(): void
    {
        $tables = [
            'users',
            'roles',
            'permissions',
            'role_user',
            'permission_role',
        ];

        foreach ($tables as $table) {
            $exists = DB::select("SHOW TABLES LIKE '{$table}'");
            $this->assertNotEmpty($exists, "Table {$table} does not exist");
        }
    }
}
```

Run test:
```bash
php artisan test --filter=DatabaseConnectionTest
```

---

## 7. Migration Commands

### 7.1 Run Migrations

```bash
# Run all migrations
php artisan migrate

# Run migrations with seeding
php artisan migrate --seed

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Refresh database (rollback + migrate)
php artisan migrate:refresh

# Refresh with seeding
php artisan migrate:refresh --seed

# Fresh migration (drop all tables + migrate)
php artisan migrate:fresh

# Fresh with seeding
php artisan migrate:fresh --seed
```

### 7.2 Create New Migration

```bash
# Create migration
php artisan make:migration create_table_name

# Create migration with table
php artisan make:migration create_users_table --create=users

# Create migration for table modification
php artisan make:migration add_column_to_users_table --table=users
```

---

## 8. Database Backup & Restore

### 8.1 Backup Database

```bash
# Backup development database
mysqldump -u root -p canvastack_dev > backup_dev_$(date +%Y%m%d_%H%M%S).sql

# Backup with compression
mysqldump -u root -p canvastack_dev | gzip > backup_dev_$(date +%Y%m%d_%H%M%S).sql.gz

# Backup specific tables
mysqldump -u root -p canvastack_dev users roles permissions > backup_rbac.sql
```

### 8.2 Restore Database

```bash
# Restore from backup
mysql -u root -p canvastack_dev < backup_dev_20260224_120000.sql

# Restore from compressed backup
gunzip < backup_dev_20260224_120000.sql.gz | mysql -u root -p canvastack_dev
```

---

## 9. Performance Optimization

### 9.1 Indexing Strategy

```sql
-- Add indexes for frequently queried columns
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_created_at (created_at);

-- Add composite indexes
ALTER TABLE role_user ADD INDEX idx_role_user (role_id, user_id);
ALTER TABLE permission_role ADD INDEX idx_permission_role (permission_id, role_id);

-- Add full-text indexes (for search)
ALTER TABLE users ADD FULLTEXT INDEX ft_name (name);
```

### 9.2 Query Optimization

```sql
-- Analyze tables
ANALYZE TABLE users, roles, permissions;

-- Optimize tables
OPTIMIZE TABLE users, roles, permissions;

-- Check table status
SHOW TABLE STATUS LIKE 'users';
```

### 9.3 MySQL Configuration

Add to `my.cnf` or `my.ini`:

```ini
[mysqld]
# InnoDB settings
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2

# Query cache (MySQL 5.7 and below)
query_cache_type = 1
query_cache_size = 64M

# Connection settings
max_connections = 200
```

---

## 10. Troubleshooting

### Issue: Access Denied

```bash
# Reset MySQL root password
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'new_password';
FLUSH PRIVILEGES;
EXIT;
```

### Issue: Database Already Exists

```bash
# Drop and recreate
mysql -u root -p -e "DROP DATABASE IF EXISTS canvastack_dev;"
mysql -u root -p -e "CREATE DATABASE canvastack_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Issue: Migration Failed

```bash
# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Issue: Connection Timeout

```sql
-- Increase timeout settings
SET GLOBAL connect_timeout = 60;
SET GLOBAL wait_timeout = 28800;
SET GLOBAL interactive_timeout = 28800;
```

---

## 11. Development Workflow

### Daily Development

```bash
# 1. Pull latest changes
git pull

# 2. Run migrations
php artisan migrate

# 3. Clear cache
php artisan cache:clear

# 4. Run tests
php artisan test
```

### Fresh Start

```bash
# Complete reset
php artisan migrate:fresh --seed
php artisan cache:clear
php artisan config:clear
```

---

## 12. Testing Database

### 12.1 Configure Testing Database

Update `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="mysql_test"/>
    <env name="DB_DATABASE" value="canvastack_test"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
</php>
```

### 12.2 Database Transactions in Tests

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        // Test code here
        // Database will be rolled back after test
    }
}
```

---

## Summary

**Development Database**: `canvastack_dev`  
**Testing Database**: `canvastack_test`  
**Character Set**: UTF8MB4  
**Engine**: InnoDB  
**Default User**: admin@canvastack.local / password

**Status**: ✅ Ready for development

---

**Last Updated**: 2026-02-24  
**Version**: 1.0.0
