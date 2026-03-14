<?php

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Table\TableBuilder;

class TestController extends BaseController
{
    /**
     * Display test dashboard.
     */
    public function dashboard()
    {
        $this->setPage('CanvaStack Test Dashboard');
        
        // Setup table
        $this->table->setContext('admin');
        
        // Check if User model exists
        if (class_exists('\App\Models\User')) {
            $this->table->setModel(new \App\Models\User());
            $this->table->setFields([
                'id:ID',
                'name:Name',
                'email:Email',
                'created_at:Created At'
            ]);
        } else {
            // Use dummy data if User model doesn't exist
            $this->table->setData([
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now()],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'created_at' => now()],
            ]);
            $this->table->setFields([
                'id:ID',
                'name:Name',
                'email:Email',
                'created_at:Created At'
            ]);
        }
        
        $this->table->format();
        
        // Setup chart - User growth
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $userData = [10, 20, 30, 40, 50, 60];
        
        $this->chart->setContext('admin');
        $this->chart->line([
            ['name' => 'Users', 'data' => $userData]
        ], $months);
        
        return $this->render();
    }
    
    /**
     * Display table builder test.
     */
    public function table()
    {
        $this->setPage('Table Builder Test');
        
        $this->table->setContext('admin');
        
        // Check if User model exists
        if (class_exists('\App\Models\User')) {
            $this->table->setModel(new \App\Models\User());
            $this->table->setFields([
                'id:ID',
                'name:Name',
                'email:Email',
                'email_verified_at:Verified',
                'created_at:Created At'
            ]);
        } else {
            // Use dummy data
            $this->table->setData([
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'email_verified_at' => now(), 'created_at' => now()],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'email_verified_at' => null, 'created_at' => now()],
                ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'email_verified_at' => now(), 'created_at' => now()],
            ]);
            $this->table->setFields([
                'id:ID',
                'name:Name',
                'email:Email',
                'email_verified_at:Verified',
                'created_at:Created At'
            ]);
        }
        
        $this->table->format();
        
        return $this->render();
    }
    
    /**
     * Display form builder test (create).
     */
    public function formCreate()
    {
        $this->setPage('Form Builder Test - Create');
        
        $this->form->setContext('admin');
        
        // Basic fields
        $this->form->text('name', 'Full Name')->required()->placeholder('Enter your name');
        $this->form->email('email', 'Email Address')->required()->placeholder('you@example.com');
        $this->form->password('password', 'Password')->required();
        
        // Select field
        $this->form->select('role', 'Role', [
            'admin' => 'Administrator',
            'user' => 'User',
            'guest' => 'Guest'
        ])->required();
        
        // Textarea
        $this->form->textarea('bio', 'Biography')->placeholder('Tell us about yourself');
        
        // Date field
        $this->form->date('birthdate', 'Birth Date');
        
        return $this->render();
    }
    
    /**
     * Display chart builder test.
     */
    public function chart()
    {
        $this->setPage('Chart Builder Test');
        
        $this->chart->setContext('admin');
        
        // Line chart
        $this->chart->line([
            ['name' => 'Sales', 'data' => [10, 20, 30, 40, 50, 60]],
            ['name' => 'Revenue', 'data' => [15, 25, 35, 45, 55, 65]]
        ], ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']);
        
        return $this->render();
    }
    
    /**
     * Display multi-table test.
     */
    public function multiTable()
    {
        $this->setPage('Multi-Table Test');
        
        // Create 3 separate TableBuilder instances
        $table1 = app(TableBuilder::class);
        $table2 = app(TableBuilder::class);
        $table3 = app(TableBuilder::class);
        
        // Check if User model exists
        if (class_exists('\App\Models\User')) {
            // Table 1: Basic Info
            $table1->setContext('admin');
            $table1->setModel(new \App\Models\User());
            $table1->setName('users');
            $table1->setFields(['id:ID', 'name:Name', 'email:Email']);
            $table1->setServerSide(true);
            $table1->format();
            
            // Table 2: Verification Status
            $table2->setContext('admin');
            $table2->setModel(new \App\Models\User());
            $table2->setName('users');
            $table2->setFields(['id:ID', 'name:Name', 'email_verified_at:Verified At']);
            $table2->setServerSide(true);
            $table2->format();
            
            // Table 3: Timestamps
            $table3->setContext('admin');
            $table3->setModel(new \App\Models\User());
            $table3->setName('users');
            $table3->setFields(['id:ID', 'name:Name', 'created_at:Created', 'updated_at:Updated']);
            $table3->setServerSide(true);
            $table3->format();
            
            // Add all tables to content_page
            $this->data['content_page'] = [
                '<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 1: Basic Information</h2>' . $table1->render() . '</div></div>',
                '<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 2: Verification Status</h2>' . $table2->render() . '</div></div>',
                '<div class="card bg-white dark:bg-gray-900 shadow-lg"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 3: Timestamps</h2>' . $table3->render() . '</div></div>',
            ];
        }
        
        return $this->render();
    }
    
    /**
     * Display TanStack tabs test - EXACT COPY from canvastack/app/Http/Controllers/TestCanvastackController.php
     */
    public function tanstackTabs()
    {
        // Log IMMEDIATELY at method entry
        \Log::info('TestController::tanstackTabs CALLED');
        
        $this->setPage('TanStack Multi-Table & Tab System - Complete Demo');

        // Set TanStack engine
        request()->merge(['_table_engine' => 'tanstack']);
        $this->table->setEngine('tanstack');
        $this->table->setContext('admin');

        // Connection configuration - use default mysql for webapp
        $connection = 'mysql';

        // ========================================
        // TAB 1: Users - Basic Info
        // ========================================
        $this->table->openTab('Users - Basic Info');

        $this->table->addTabContent(
            '<div class="alert alert-info mb-4">' .
            '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
            '<strong>Info:</strong> Basic user information' .
            '</div>'
        );

        // Check if User model exists
        if (class_exists('\App\Models\User')) {
            $this->table->connection($connection);
            $this->table->setModel(new \App\Models\User());
            $this->table->setName('users');
            $this->table->query("SELECT * FROM users");

            $this->table->setFields([
                'id:ID',
                'name:Name',
                'email:Email',
                'created_at:Created At'
            ]);

            // Column alignment
            $this->table->setCenterColumns(['id']);

            // Filter groups - Enable bidirectional cascade
            $this->table->filterGroups('name', 'selectbox', true, true);
            $this->table->filterGroups('email', 'selectbox', false, false);

            // Table configuration
            $this->table->fixedColumns(2);
            $this->table->clickable(false);
            $this->table->sortable();
            $this->table->searchable(['name', 'email']);
            $this->table->displayRowsLimitOnLoad('*');

            // Server-side processing
            $this->table->setServerSide(true);
            $this->table->cache(300);
        } else {
            $this->table->addTabContent(
                '<div class="alert alert-warning">' .
                '<i data-lucide="alert-triangle" class="w-5 h-5 inline-block mr-2"></i>' .
                'User model not found. Please ensure database is configured.' .
                '</div>'
            );
        }

        $this->table->closeTab();

        // ========================================
        // TAB 2: Users - Verification Status
        // ========================================
        $this->table->openTab('Users - Verification');

        $this->table->addTabContent(
            '<div class="alert alert-info mb-4">' .
            '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
            '<strong>Info:</strong> User email verification status' .
            '</div>'
        );

        if (class_exists('\App\Models\User')) {
            $this->table->connection($connection);
            $this->table->setModel(new \App\Models\User());
            $this->table->setName('users');
            $this->table->query("SELECT * FROM users");

            $this->table->setFields([
                'id:ID',
                'name:Name',
                'email:Email',
                'email_verified_at:Verified At'
            ]);

            // Column alignment
            $this->table->setCenterColumns(['id']);

            // Filter groups
            $this->table->filterGroups('name', 'selectbox', true, true);
            $this->table->filterGroups('email_verified_at', 'datebox', false, false);

            // Table configuration
            $this->table->fixedColumns(2);
            $this->table->clickable(false);
            $this->table->sortable();
            $this->table->searchable(['name', 'email']);
            $this->table->displayRowsLimitOnLoad('*');

            // Server-side processing
            $this->table->setServerSide(true);
            $this->table->cache(300);
        }

        $this->table->closeTab();

        // ========================================
        // TAB 3: Users - Timestamps
        // ========================================
        $this->table->openTab('Users - Timestamps');

        $this->table->addTabContent(
            '<div class="alert alert-info mb-4">' .
            '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
            '<strong>Info:</strong> User creation and update timestamps' .
            '</div>'
        );

        if (class_exists('\App\Models\User')) {
            $this->table->connection($connection);
            $this->table->setModel(new \App\Models\User());
            $this->table->setName('users');
            $this->table->query("SELECT * FROM users");

            $this->table->setFields([
                'id:ID',
                'name:Name',
                'created_at:Created At',
                'updated_at:Updated At'
            ]);

            // Column alignment
            $this->table->setCenterColumns(['id']);

            // Filter groups
            $this->table->filterGroups('name', 'selectbox', true, true);
            $this->table->filterGroups('created_at', 'datebox', false, false);

            // Table configuration
            $this->table->fixedColumns(2);
            $this->table->clickable(false);
            $this->table->sortable();
            $this->table->searchable(['name']);
            $this->table->displayRowsLimitOnLoad('*');

            // Server-side processing
            $this->table->setServerSide(true);
            $this->table->cache(300);
        }

        $this->table->closeTab();

        // ========================================
        // TAB 4: Statistics
        // ========================================
        $this->table->openTab('Statistics');

        if (class_exists('\App\Models\User')) {
            $totalUsers = \App\Models\User::count();
            $verifiedUsers = \App\Models\User::whereNotNull('email_verified_at')->count();
            $unverifiedUsers = \App\Models\User::whereNull('email_verified_at')->count();
            $todayUsers = \App\Models\User::whereDate('created_at', today())->count();

            $this->table->addTabContent(
                '<div class="grid grid-cols-1 md:grid-cols-4 gap-6">' .
                '<div class="card-hover bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">' .
                '<div class="flex items-center justify-between">' .
                '<div>' .
                '<p class="text-sm text-gray-600 dark:text-gray-400">Total Users</p>' .
                '<p class="text-3xl font-bold mt-1">' . $totalUsers . '</p>' .
                '</div>' .
                '<div class="w-12 h-12 gradient-bg rounded-xl flex items-center justify-center">' .
                '<i data-lucide="users" class="w-6 h-6 text-white"></i>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '<div class="card-hover bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">' .
                '<div class="flex items-center justify-between">' .
                '<div>' .
                '<p class="text-sm text-gray-600 dark:text-gray-400">Verified</p>' .
                '<p class="text-3xl font-bold mt-1">' . $verifiedUsers . '</p>' .
                '</div>' .
                '<div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center">' .
                '<i data-lucide="check-circle" class="w-6 h-6 text-white"></i>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '<div class="card-hover bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">' .
                '<div class="flex items-center justify-between">' .
                '<div>' .
                '<p class="text-sm text-gray-600 dark:text-gray-400">Unverified</p>' .
                '<p class="text-3xl font-bold mt-1">' . $unverifiedUsers . '</p>' .
                '</div>' .
                '<div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center">' .
                '<i data-lucide="alert-circle" class="w-6 h-6 text-white"></i>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '<div class="card-hover bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">' .
                '<div class="flex items-center justify-between">' .
                '<div>' .
                '<p class="text-sm text-gray-600 dark:text-gray-400">New Today</p>' .
                '<p class="text-3xl font-bold mt-1">' . $todayUsers . '</p>' .
                '</div>' .
                '<div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-500 rounded-xl flex items-center justify-center">' .
                '<i data-lucide="trending-up" class="w-6 h-6 text-white"></i>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '</div>'
            );
        } else {
            $this->table->addTabContent(
                '<div class="alert alert-warning">' .
                '<i data-lucide="alert-triangle" class="w-5 h-5 inline-block mr-2"></i>' .
                'User model not found. Please ensure database is configured.' .
                '</div>'
            );
        }

        $this->table->closeTab();

        // CRITICAL FIX: Use $this->render() like TestCanvastackController
        // This ensures proper variable passing and view rendering
        return $this->render();
    }
    
    /**
     * Display theme test.
     */
    public function theme()
    {
        // Delegate to ThemeController
        $controller = app(\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class);
        return $controller->index($this->table, $this->meta);
    }
    
    /**
     * Display i18n test.
     */
    public function i18n()
    {
        // Delegate to LocaleController
        $controller = app(\Canvastack\Canvastack\Http\Controllers\Admin\LocaleController::class);
        return $controller->index($this->table, $this->meta);
    }
}
