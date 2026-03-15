<?php

namespace Canvastack\Canvastack\Http\Controllers;

use App\Models\User;
use Canvastack\Canvastack\Http\Controllers\BaseController;
#use Canvastack\Canvastack\Components\Table\TableBuilder;

class DemoController extends BaseController
{
    /**
     * Display dashboard with all components.
     */
    public function dashboard()
    {
        $this->setPage('CanvaStack Test Dashboard');
        
        // Setup table
        $this->table->setContext('admin');
        $this->table->setModel(new User());
        $this->table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'created_at:Created At'
        ]);
        $this->table->addAction('edit', route('page.form-edit', ':id'), 'edit', 'Edit');
        $this->table->addAction('delete', '#', 'trash', 'Delete', 'DELETE');
        
        // Setup chart - User growth
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $userData = [5, 10, 15, 20, 25, 30];
        
        $this->chart->setContext('admin');
        $this->chart->line([
            ['name' => 'Users', 'data' => $userData]
        ], $months);
        
        return $this->render();
    }
    
    /**
     * Display form builder page.
     */
    public function formCreate()
    {
        $this->setPage('Form Builder Test');
        
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
        
        // Checkbox (single checkbox)
        $this->form->checkbox('agree', 'I agree to terms and conditions', [], false);
        
        // Date field
        $this->form->date('birthdate', 'Birth Date');
        
        // Number field
        $this->form->number('age', 'Age')->placeholder('Enter your age');
        
        return $this->render();
    }
    
    /**
     * Display form edit page.
     */
    public function formEdit(User $user)
    {
        $this->setPage('Edit User - Form Builder Test');
        
        $this->form->setContext('admin');
        $this->form->setModel($user);
        
        $this->form->text('name', 'Full Name')->required();
        $this->form->email('email', 'Email Address')->required();
        
        $this->form->select('role', 'Role', [
            'admin' => 'Administrator',
            'user' => 'User',
            'guest' => 'Guest'
        ]);
        
        return $this->render(['user' => $user]);
    }
    
    /**
     * Display table builder test - Method 1 (Manual Configuration).
     */
    public function tableMethod1()
    {
        $this->setPage('Table Builder - Method 1 (Manual)');
        
        $this->table->setContext('admin');
        $this->table->setModel(new User());
        $this->table->setName('users');
        $this->table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'created_at:Created'
        ]);
        $this->table->addAction('view', '#', 'eye', 'View');
        $this->table->addAction('edit', route('page.form-edit', ':id'), 'edit', 'Edit');
        $this->table->addAction('delete', '#', 'trash', 'Delete', 'DELETE');
        
        // Add filter configuration for testing with cascading
        // Name filter cascades to Email and Created At
        $this->table->filterGroups('name', 'selectbox', ['email', 'created_at']);
        $this->table->filterGroups('email', 'selectbox', 'created_at');
        $this->table->filterGroups('created_at', 'datebox');
        
        // Enable server-side processing for scalability
        $this->table->setServerSide(true);
        $this->table->cache(300);
        $this->table->orderBy('created_at', 'desc');
        
        // IMPORTANT: Call format() to prepare the table for rendering
        $this->table->format();
        
        return $this->render();
    }
    
    /**
     * Display multi-table test - Method 1 (Manual Configuration).
     */
    public function multiTableMethod1()
    {
        $this->setPage('Multi-Table - Method 1 (Manual)');
        
        // Table 1: Basic Info
        $table1 = $this->table; //app(TableBuilder::class);
        $table1->setContext('admin');
        $table1->setModel(new User());
        $table1->setName('users');
        $table1->setFields([
            'id:ID',
            'name:Name',
            'email:Email'
        ]);
        $table1->setServerSide(true);
        $table1->orderBy('name', 'asc');
        
        // Table 2: Verification Status
        $table2 = $this->table; //app(TableBuilder::class);
        $table2->setContext('admin');
        $table2->setModel(new User());
        $table2->setName('users');
        $table2->setFields([
            'id:ID',
            'name:Name',
            'email_verified_at:Verified At'
        ]);
        $table2->setServerSide(true);
        $table2->orderBy('email_verified_at', 'desc');
        
        // Table 3: Timestamps
        $table3 = $this->table; //app(TableBuilder::class);
        $table3->setContext('admin');
        $table3->setModel(new User());
        $table3->setName('users');
        $table3->setFields([
            'id:ID',
            'name:Name',
            'created_at:Created',
            'updated_at:Updated'
        ]);
        $table3->setServerSide(true);
        $table3->orderBy('created_at', 'desc');
        
        // Add all tables to content_page
        $this->data['content_page'] = [
            '<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 1: Basic Information</h2>' . $table1->render() . '</div></div>',
            '<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 2: Verification Status</h2>' . $table2->render() . '</div></div>',
            '<div class="card bg-white dark:bg-gray-900 shadow-lg"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 3: Timestamps</h2>' . $table3->render() . '</div></div>',
        ];
        
        return $this->render();
    }
    
    /**
     * Display table builder test - Method 2 (lists() Enhancement).
     */
    public function tableMethod2()
    {
        $this->setPage('Table Builder - Method 2 (lists)');
        
        $this->table->setContext('admin');
        $this->table->setModel(new User());
        
        // Use lists() method - auto-detects columns and handles everything
        $this->table->lists(
            'users',                    // table name
            ['id:ID', 'name:Name', 'email:Email', 'created_at:Created'], // fields
            [                           // actions
                'view' => [
                    'label' => 'View',
                    'icon' => 'eye',
                    'url' => fn($row) => '#',
                    'class' => 'btn-sm btn-info',
                ],
                'edit' => [
                    'label' => 'Edit',
                    'icon' => 'edit',
                    'url' => fn($row) => route('page.form-edit', $row->id),
                    'class' => 'btn-sm btn-warning',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'trash',
                    'url' => fn($row) => '#',
                    'method' => 'DELETE',
                    'class' => 'btn-sm btn-error',
                ],
            ],
            true,                       // server-side processing
            true                        // numbering
        );
        
        return $this->render();
    }
    
    /**
     * Display multi-table test - Method 2 (lists() Enhancement).
     */
    public function multiTableMethod2()
    {
        $this->setPage('Multi-Table - Method 2 (lists)');
        
        // Create 3 separate TableBuilder instances
        $table1 = $this->table; //app(TableBuilder::class);
        $table2 = $this->table; //app(TableBuilder::class);
        $table3 = $this->table; //app(TableBuilder::class);
        
        // Table 1: Basic Info - use lists() for auto-detection
        $table1->setContext('admin');
        $table1->setModel(new User());
        $table1Html = $table1->lists(
            'users',
            ['id:ID', 'name:Name', 'email:Email'],
            false,  // no actions
            true,   // server-side
            true    // numbering
        );
        
        // Table 2: Verification Status - use lists() for auto-detection
        $table2->setContext('admin');
        $table2->setModel(new User());
        $table2Html = $table2->lists(
            'users',
            ['id:ID', 'name:Name', 'email_verified_at:Verified At'],
            false,  // no actions
            true,   // server-side
            true    // numbering
        );
        
        // Table 3: Timestamps - use lists() for auto-detection
        $table3->setContext('admin');
        $table3->setModel(new User());
        $table3Html = $table3->lists(
            'users',
            ['id:ID', 'name:Name', 'created_at:Created', 'updated_at:Updated'],
            false,  // no actions
            true,   // server-side
            true    // numbering
        );
        
        // Add all tables to content_page
        $this->data['content_page'] = [
            '<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 1: Basic Information</h2>' . $table1Html . '</div></div>',
            '<div class="card bg-white dark:bg-gray-900 shadow-lg mb-6"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 2: Verification Status</h2>' . $table2Html . '</div></div>',
            '<div class="card bg-white dark:bg-gray-900 shadow-lg"><div class="card-body"><h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Table 3: Timestamps</h2>' . $table3Html . '</div></div>',
        ];
        
        return $this->render();
    }
    
    /**
     * Display table builder test (legacy - redirects to method 1).
     */
    public function table()
    {
        $this->setPage('Table Builder Test - With Export Buttons & Bi-Directional Cascade');
        
        $this->table->setContext('admin');
        $this->table->setModel(new User());
        $this->table->setName('users');
        $this->table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'created_at:Created'
        ]);
        $this->table->addAction('view', '#', 'eye', 'View');
        $this->table->addAction('edit', route('page.form-edit', ':id'), 'edit', 'Edit');
        $this->table->addAction('delete', '#', 'trash', 'Delete', 'DELETE');
        
        // ✨ NEW: Enable export buttons (Phase 8: P2 Features)
        $this->table->setButtons(['excel', 'csv', 'pdf', 'print', 'copy']);
        
        // ✨ NEW: Enable bi-directional filter cascade
        // Users can now select filters in ANY order!
        $this->table->setBidirectionalCascade(true);
        
        // Add filter configuration for testing with bi-directional cascading
        // With bidirectional enabled, all filters update each other
        $this->table->filterGroups('name', 'selectbox', true);
        $this->table->filterGroups('email', 'selectbox', true);
        $this->table->filterGroups('created_at', 'datebox', true);
        
        // Enable server-side processing for scalability
        $this->table->setServerSide(true);
        $this->table->cache(300);
        $this->table->orderBy('created_at', 'desc');
        
        // IMPORTANT: Call format() to prepare the table for rendering
        $this->table->format();
        
        return $this->render();
    }
    
    /**
     * Display multi-table test (legacy - redirects to method 2).
     */
    public function multiTable()
    {
        return $this->multiTableMethod2();
    }
    
    /**
     * Display TanStack Table test - Same features as table() but using TanStack engine.
     * 
     * Features:
     * - TanStack Table v8 engine
     * - Export buttons (excel, csv, pdf, print, copy)
     * - Bi-directional filter cascade
     * - CLIENT-SIDE processing (for now - server-side will be added later)
     * - Caching
     * - Actions (view, edit, delete)
     */
    public function tanstackTable()
    {
        $this->setPage('TanStack Table Test - With Export Buttons & Bi-Directional Cascade');
        
        // CRITICAL: Set table_engine in REQUEST so middleware can access it
        request()->merge(['_table_engine' => 'tanstack']);
        
        // CRITICAL: Set TanStack engine FIRST before any other configuration
        $this->table->setEngine('tanstack');
        $this->table->setContext('admin');
        $this->table->setModel(new User());
        $this->table->setName('users');  // Same table name as DataTables version
        $this->table->setFields([
            'id:ID',
            'name:Name',
            'email:Email',
            'created_at:Created'
        ]);
        $this->table->addAction('view', '#', 'eye', 'View');
        $this->table->addAction('edit', route('page.form-edit', ':id'), 'edit', 'Edit');
        $this->table->addAction('delete', '#', 'trash', 'Delete', 'DELETE');
        
        // ✨ Enable export buttons (Phase 8: P2 Features)
        $this->table->setButtons(['excel', 'csv', 'pdf', 'print', 'copy', 'fullscreen']);
        
        // ✨ Enable bi-directional filter cascade
        // Users can now select filters in ANY order!
        $this->table->setBidirectionalCascade(true);
        
        // Add filter configuration for testing with bi-directional cascading
        // With bidirectional enabled, all filters update each other
        $this->table->filterGroups('name', 'selectbox', true);
        $this->table->filterGroups('email', 'selectbox', true);
        $this->table->filterGroups('created_at', 'datebox', true);
        
        // ENABLE server-side processing
        $this->table->setServerSide(true);
        $this->table->cache(300);
        $this->table->orderBy('created_at', 'desc');
        
        // ✨ Enable session persistence for filters
        $this->table->sessionFilters();
        
        // IMPORTANT: Call format() to prepare the table for rendering
        // $this->table->format();
        
        // Debug: Verify table is configured
        \Log::info('TanStack Table Debug', [
            'engine' => $this->table->getEngine(),
            'columns' => $this->table->getColumns(),
            'model' => get_class($this->table->getModel()),
            'server_side' => $this->table->getConfiguration()->serverSide ?? false,
            'has_filters' => $this->table->hasFilters(),
            'filter_groups' => $this->table->getFilterManager()->getFilters(),
        ]);
        
        return $this->render();
    }
    
    /**
     * Display chart builder page.
     */
    public function chart()
    {
        $this->setPage('Chart Builder Test');
        
        $this->chart->setContext('admin');
        
        // Sample data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $salesData = [120, 150, 180, 200, 250, 300, 280, 320, 350, 380, 400, 450];
        $revenueData = [1200, 1500, 1800, 2000, 2500, 3000, 2800, 3200, 3500, 3800, 4000, 4500];
        
        // Line chart
        $this->chart->line([
            ['name' => 'Sales', 'data' => $salesData],
            ['name' => 'Revenue', 'data' => $revenueData]
        ], $months);
        
        return $this->render();
    }
    
    /**
     * Display theme page.
     */
    public function theme()
    {
        // Delegate to ThemeController
        $controller = app(\Canvastack\Canvastack\Http\Controllers\Admin\ThemeController::class);
        return $controller->index($this->table, $this->meta);
    }
    
    /**
     * Display i18n page.
     */
    public function i18n()
    {
        // Delegate to LocaleController
        $controller = app(\Canvastack\Canvastack\Http\Controllers\Admin\LocaleController::class);
        return $controller->index($this->table, $this->meta);
    }

    /**
     * Display TanStack Multi-Table & Tab System - Complete Feature Demo.
     *
     * This method demonstrates ALL features from the TanStack Multi-Table & Tab System:
     * - Multiple tabs with lazy loading
     * - Multiple tables per tab
     * - Automatic connection detection
     * - Connection override warnings
     * - Unique ID generation
     * - Custom tab content
     * - Filter groups with cascading
     * - Fixed columns
     * - Number formatting
     * - Column alignment (center, right)
     * - Searchable columns
     * - Sortable tables
     * - Server-side processing
     *
     * Uses same data structure as IncentiveController from Mantra application.
     */
    public function tanstackMultiTableTabs()
    {
        // Log IMMEDIATELY at method entry
        \Log::info('TestCanvastackController::tanstackMultiTableTabs CALLED');
        
        $this->setPage('TanStack Multi-Table & Tab System - Complete Demo');

        // Set TanStack engine
        request()->merge(['_table_engine' => 'tanstack']);
        $this->table->setEngine('tanstack');
        $this->table->setContext('admin');

        // Connection configuration - will auto-detect from model
        $connection = 'mysql_mantra_etl';

        // Helper function for date info - replaces canvas_date_info()
        $getLastUpdateDate = function($table) use ($connection) {
            try {
                $result = \DB::connection($connection)->select(
                    "SELECT MAX(running_date) as last_update FROM {$table} WHERE period IS NOT NULL LIMIT 1"
                );
                
                if (!empty($result) && isset($result[0]->last_update)) {
                    return date('d M Y', strtotime($result[0]->last_update));
                }
                
                return 'N/A';
            } catch (\Exception $e) {
                \Log::error("Failed to get last update date for table {$table}: " . $e->getMessage());
                return 'N/A';
            }
        };

        // ========================================
        // FIX #72: DISABLE TAB SYSTEM FOR TESTING
        // Testing if problem is in TAB system or TABLE rendering
        // ========================================
        
        // COMMENT OUT: Tab opening
        $this->table->openTab('Summary ASM');

        // COMMENT OUT: Custom tab content
        // $this->table->addTabContent(
        //     '<div class="alert alert-info mb-4">' .
        //     '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
        //     '<strong>Tanggal Update Terakhir:</strong> ' .
        //     $getLastUpdateDate('report_data_summary_incentive_asm') .
        //     '</div>'
        // );

        // Configure table for ASM data (KEEP THIS - just render table without tabs)

        $fieldsetAsm = [
            'period_string:Period',
            'running_date_string:Running Date',
            'cor:COR',
            'region:Region',
            'cluster:Cluster',
            'nik:NIK',
            'nama:Nama',
            'po:PO',
            'target_po:Target PO',
            'ach_po:ACH PO (%)',
            'bobot:Bobot (%)',
            'score_po:Score PO (%)',
            'bts_revenue:BTS Revenue',
            'target_bts:Target BTS',
            'ach_bts_rev:ACH BTS Rev (%)',
            'bobot_bts_rev:Bobot BTS Rev (%)',
            'score_bts_rev:Score BTS Rev',
            'netadd:Nett Add',
            'target_netadd:Target Nett Add',
            'ach_netadd:ACH Nett Add (%)',
            'bobot_netadd:Bobot Nett Add (%)',
            'score_netadd:Score Nett Add (%)',
            'totalscore:Total Score',
            'incentive:Incentive',
            'totalsite:Total Site',
            'averagebtsrevenue:AVG BTS Revenue',
            'incentivebtsrev:Incentive BTS Revenue',
            'substhismonth:Subs Bulan Ini',
            'max_subs:Max Subs',
            'max_subs_date_string:Max Subs Bulanan',
            'growthnetadd:Growth Nett Add',
            'netaddgrowthincentive:Incentive Growth Nett Add',
            'totalincentive:Total Incentive'
        ];

        $this->table->connection($connection);
        $this->table->setName('report_data_summary_incentive_asm');
        
        // Log to verify connection is set
        \Log::info('TestCanvastackController: After setting connection', [
            'connection_set' => $connection,
            'connection_get' => $this->table->getConnection(),
        ]);
        
        // Set query to fetch data from the table
        $this->table->query("SELECT * FROM report_data_summary_incentive_asm");

        $this->table->setFields($fieldsetAsm);

        // Column alignment
        $this->table->setCenterColumns(['cor']);
        $this->table->setRightColumns([
            'po', 'target_po', 'ach_po', 'bobot', 'score_po',
            'bts_revenue', 'target_bts', 'ach_bts_rev', 'bobot_bts_rev', 'score_bts_rev',
            'netadd', 'target_netadd', 'ach_netadd', 'bobot_netadd', 'score_netadd',
            'totalscore', 'incentive', 'totalsite', 'averagebtsrevenue', 'incentivebtsrev',
            'substhismonth', 'max_subs', 'growthnetadd', 'netaddgrowthincentive', 'totalincentive'
        ], true, true);

        // Number formatting - use setNumberColumns for new CanvaStack
        $this->table->setNumberColumns(['target_po'], 'decimal', 0);
        $this->table->setNumberColumns(['ach_po', 'score_po', 'bts_revenue', 'ach_bts_rev', 'score_bts_rev', 
            'target_netadd', 'ach_netadd', 'score_netadd', 'totalscore', 'averagebtsrevenue'], 'decimal', 2);
        $this->table->setNumberColumns(['target_bts', 'incentive', 'substhismonth', 'max_subs', 'growthnetadd', 
            'netaddgrowthincentive', 'totalsite', 'incentivebtsrev', 'totalincentive'], 'decimal', 0);

        // Filter groups - FIX #78: Enable bidirectional cascade
        $this->table->filterGroups('period_string', 'selectbox', true, true); // relate=true, bidirectional=true
        $this->table->filterGroups('cor', 'selectbox', true, true);           // relate=true, bidirectional=true
        $this->table->filterGroups('region', 'selectbox', true, true);        // relate=true, bidirectional=true
        $this->table->filterGroups('cluster', 'selectbox', false, false);     // No cascade for last filter

        // Table configuration
        $this->table->fixedColumns(2,2);
        $this->table->clickable(false);
        $this->table->sortable();
        $this->table->searchable(['period_string', 'cor', 'region', 'cluster', 'nik', 'nama']);
        $this->table->displayRowsLimitOnLoad('*');
        // ✨ Enable export buttons (Phase 8: P2 Features)
        $this->table->setButtons(['excel', 'csv', 'pdf', 'print', 'copy', 'fullscreen']);

        // Server-side processing
        $this->table->setServerSide(true);
        $this->table->cache(300);

        // COMMENT OUT: Close tab
        $this->table->closeTab();

        // ========================================
        // FIX #72: COMMENT OUT ALL OTHER TABS
        // ========================================
        
        // ========================================
        // TAB 2: Summary ASC
        // ========================================
        $this->table->openTab('Summary ASC');

        // Add custom content with date info
        $this->table->addTabContent(
            '<div class="alert alert-info mb-4">' .
            '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
            '<strong>Tanggal Update Terakhir:</strong> ' .
            $getLastUpdateDate('report_data_summary_incentive_asc') .
            '</div>'
        );

        // Configure table for ASC data
        $this->table->connection($connection);
        $this->table->setName('report_data_summary_incentive_asc');
        
        // Set query to fetch data from the table
        $this->table->query("SELECT * FROM report_data_summary_incentive_asc");

        $fieldsetAsc = [
            'period_string:Period',
            'running_date_string:Running Date',
            'cor:COR',
            'region:Region',
            'cluster:Cluster',
            'sub_cluster:Sub Cluster',
            'nik:NIK',
            'nama:Nama',
            'jabatan:Jabatan',
            'po:PO',
            'target_po:Target PO',
            'ach_po:ACH PO (%)',
            'bobot_po:Bobot PO (%)',
            'score_po:Skor PO (%)',
            'bts_revenue:BTS Rev',
            'target_bt_rev:Target BTS Rev',
            'ach_bts_rev:ACH BTS Rev (%)',
            'bobot_btsrev:Bobot BTS Rev (%)',
            'score_bts_rev:Skor BTS Rev (%)',
            'score_total:Skor Total',
            'incentive:Incentive',
            'totalsite:Total Site',
            'averagebtsrevenue:AVG BTS Rev',
            'icentiveavgbtsrevenue:Incentive Rev AVG BTS',
            'totalincentive:Total Incentive',
            'rguact:RGU ACT',
            'target:Target',
            'bobot_rguact:Bobot RGU ACT',
            'score_rguact:Skor RGU ACT',
            'icentiveaddrguact:Incentive RGU ACT'
        ];

        $this->table->setFields($fieldsetAsc);

        // Column alignment
        $this->table->setCenterColumns(['cor']);
        $this->table->setRightColumns([
            'target_po', 'ach_po', 'bobot_po', 'score_po',
            'bts_revenue', 'target_bt_rev', 'ach_bts_rev', 'bobot_btsrev', 'score_bts_rev',
            'score_total', 'incentive', 'totalsite', 'averagebtsrevenue', 'icentiveavgbtsrevenue',
            'totalincentive', 'rguact', 'target', 'bobot_rguact', 'score_rguact', 'icentiveaddrguact'
        ], true, true);

        // Number formatting - use setNumberColumns for new CanvaStack
        $this->table->setNumberColumns(['target_po', 'target_bt_rev'], 'decimal', 0);
        $this->table->setNumberColumns(['ach_po', 'score_po', 'bts_revenue', 'ach_bts_rev', 'score_bts_rev', 
            'score_total', 'averagebtsrevenue', 'incentive', 'icentiveavgbtsrevenue'], 'decimal', 2);
        $this->table->setNumberColumns(['icentiveaddrguact', 'totalincentive'], 'decimal', 0);

        // Filter groups
        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('cor', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox');

        // Table configuration
        $this->table->fixedColumns(5);
        $this->table->clickable(false);
        $this->table->sortable();
        $this->table->searchable(['period_string', 'cor', 'region', 'cluster', 'nik', 'nama']);
        $this->table->displayRowsLimitOnLoad('*');

        // Server-side processing
        $this->table->setServerSide(true);
        $this->table->cache(300);

        $this->table->closeTab();

        /*
        // ========================================
        // TAB 3: Summary PIC Cluster
        // ========================================
        $this->table->openTab('Summary PIC Cluster');

        // Add custom content with date info
        $this->table->addTabContent(
            '<div class="alert alert-info mb-4">' .
            '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
            '<strong>Tanggal Update Terakhir:</strong> ' .
            $getLastUpdateDate('report_data_summary_incentive_pic_cluster') .
            '</div>'
        );

        // Configure table for PIC Cluster data
        $this->table->connection($connection);
        $this->table->setName('report_data_summary_incentive_pic_cluster');
        
        // Set query to fetch data from the table
        $this->table->query("SELECT * FROM report_data_summary_incentive_pic_cluster");

        $fieldsetPicCluster = [
            'period_string:Period',
            'running_date_string:Running Date',
            'cor:COR',
            'region:Region',
            'cluster:Cluster',
            'nik:NIK',
            'nama:Nama',
            'selltrhuactive:Sellthru Active',
            'target_sellthruactive:Target Sellthru Active',
            'ach_selltrhuactive:ACH Sellthru Active (%)',
            'bobot:Bobot (%)',
            'score_selltrhuactive:Score Sellthru Active (%)',
            'bts_revenue:BTS Revenue',
            'target_bts:Target BTS',
            'ach_bts_rev:ACH BTS Rev (%)',
            'bobot_bts_rev:Bobot BTS Rev (%)',
            'score_bts_rev:Score BTS Rev (%)',
            'netadd:Nett Add',
            'target_netadd:Target Nett Add',
            'ach_netadd:ACH Nett Add (%)',
            'bobot_netadd:Bobot Nett Add (%)',
            'score_netadd:Score Nett Add (%)',
            'totalscore:Total Score',
            'incentive:Incentive'
        ];

        $this->table->setFields($fieldsetPicCluster);

        // Column alignment
        $this->table->setCenterColumns(['cor']);
        $this->table->setRightColumns([
            'selltrhuactive', 'target_sellthruactive', 'ach_selltrhuactive', 'bobot', 'score_selltrhuactive',
            'bts_revenue', 'target_bts', 'ach_bts_rev', 'bobot_bts_rev', 'score_bts_rev',
            'netadd', 'target_netadd', 'ach_netadd', 'bobot_netadd', 'score_netadd',
            'totalscore', 'incentive'
        ], true, true);

        // Number formatting - use setNumberColumns for new CanvaStack
        $this->table->setNumberColumns(['selltrhuactive'], 'decimal', 0);
        $this->table->setNumberColumns(['target_sellthruactive', 'ach_selltrhuactive', 'score_selltrhuactive'], 'decimal', 2);

        // Column conditions
        $this->table->columnCondition('ach_selltrhuactive', 'cell', '>=', 1, 'suffix', ' %');
        $this->table->columnCondition('score_selltrhuactive', 'cell', '>=', 1, 'suffix', ' %');

        // Filter groups
        $this->table->filterGroups('period_string', 'selectbox', true);
        $this->table->filterGroups('cor', 'selectbox', true);
        $this->table->filterGroups('region', 'selectbox', true);
        $this->table->filterGroups('cluster', 'selectbox');

        // Table configuration
        $this->table->fixedColumns(5);
        $this->table->clickable(false);
        $this->table->sortable();
        $this->table->searchable(['period_string', 'cor', 'region', 'cluster', 'nik', 'nama']);
        $this->table->displayRowsLimitOnLoad('*');

        // Server-side processing
        $this->table->setServerSide(true);
        $this->table->cache(300);

        $this->table->clearFixedColumns();
        $this->table->closeTab();

        // ========================================
        // TAB 4: Summary PIC Sub Cluster
        // ========================================
        $this->table->openTab('Summary PIC Sub Cluster');

        // Add custom content with date info
        $this->table->addTabContent(
            '<div class="alert alert-info mb-4">' .
            '<i data-lucide="info" class="w-5 h-5 inline-block mr-2"></i>' .
            '<strong>Tanggal Update Terakhir:</strong> ' .
            $getLastUpdateDate('report_data_summary_incentive_pic_sub_cluster') .
            '</div>'
        );

        // Configure table for PIC Sub Cluster data
        $this->table->connection($connection);
        $this->table->setName('report_data_summary_incentive_pic_sub_cluster');
        
        // Set query to fetch data from the table
        $this->table->query("SELECT * FROM report_data_summary_incentive_pic_sub_cluster");

        $fieldsetPicSubCluster = [
            'period_string:Period',
            'running_date_string:Running Date',
            'cor:COR',
            'region:Region',
            'cluster:Cluster',
            'nik:NIK',
            'nama:Nama',
            'jabatan:Jabatan',
            'selltrhuactive:Sellthru Active',
            'target_selltrhuactive:Target Sellthru Active',
            'ach_selltrhuactive:ACH Sellthru Active (%)',
            'bobot_selltrhuactive:Bobot Sellthru Active (%)',
            'score_selltrhuactive:Score Sellthru Active (%)',
            'bts_revenue:BTS Revenue',
            'target_bt_rev:Target BT Rev',
            'ach_bts_rev:ACH BTS Rev (%)',
            'bobot_btsrev:Bobot BTS Rev (%)',
            'score_bts_rev:Score BTS Rev (%)',
            'substhismonth:Subs Bulan Ini',
            'subslastmonth:Subs Bulan Lalu',
            'netadd:Nett Add',
            'target_netadd:Target Nett Add',
            'ach_netadd:ACH Net Add (%)',
            'bobot_netadd:Bobot Net Add (%)',
            'score_netadd:Score Net Add (%)',
            'score_total:Score Total',
            'incentive:Incentive'
        ];

        $this->table->setFields($fieldsetPicSubCluster);

        // ... (rest of Tab 2, 3, 4 code - ALL COMMENTED OUT)
        */

        // ========================================
        // FIX #72: END OF COMMENTED TAB CODE
        // Now just render single table without tabs
        // ========================================

        // Debug: Log tabs configuration before rendering
        \Log::debug('TanStack Tabs Debug (Fix #72 - Tabs Disabled)', [
            'has_tabs' => $this->table->hasTabNavigation(),
            'tabs_count' => count($this->table->getTabs()),
            'tabs_data' => $this->table->getTabs(),
        ]);

        // CRITICAL FIX #60: Call format() to prepare table for rendering
        // Without format(), Alpine.js component is not registered properly
        // This is why /test/tanstacktable works but /test/tanstack-tabs doesn't
        $this->table->format();

        return $this->render();
    }

}
