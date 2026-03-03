<?php

declare(strict_types=1);

/**
 * Display Limit DataTables Integration Example
 * 
 * Task 3.1.3: Integrate with DataTables
 * 
 * This example demonstrates how the display limit functionality
 * integrates with DataTables for dynamic pagination updates.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Canvastack\Canvastack\Components\Table\TableBuilder;

// Create sample data
$sampleData = [];
for ($i = 1; $i <= 100; $i++) {
    $sampleData[] = [
        'id' => $i,
        'name' => "User {$i}",
        'email' => "user{$i}@example.com",
        'status' => $i % 2 === 0 ? 'Active' : 'Inactive',
        'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
    ];
}

// Create table builder instance
$table = app(TableBuilder::class);

// Configure table with display limit
$table->setContext('admin')
      ->setData($sampleData)
      ->setFields([
          'id:ID',
          'name:Name',
          'email:Email Address',
          'status:Status',
          'created_at:Created At'
      ])
      ->displayRowsLimitOnLoad(25) // Set initial display limit to 25
      ->sessionFilters() // Enable session persistence
      ->format();

// Render the table
$tableHtml = $table->render();

// Render the display limit UI component
$displayLimitHtml = $table->renderDisplayLimitUI([
    ['value' => '10', 'label' => '10'],
    ['value' => '25', 'label' => '25'],
    ['value' => '50', 'label' => '50'],
    ['value' => '100', 'label' => '100'],
    ['value' => 'all', 'label' => 'All'],
], true, 'sm');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Display Limit DataTables Integration Example</title>
    
    <!-- Tailwind CSS + DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery and DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    Display Limit DataTables Integration
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Task 3.1.3: Demonstrates how display limit changes automatically update DataTables pagination.
                </p>
            </div>

            <!-- Features Card -->
            <div class="card bg-white dark:bg-gray-800 shadow-xl mb-8">
                <div class="card-body">
                    <h2 class="card-title text-indigo-600 dark:text-indigo-400">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        Integration Features
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="flex items-start gap-3">
                            <div class="badge badge-success badge-sm mt-1"></div>
                            <div>
                                <h3 class="font-semibold">Dynamic Page Length</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    DataTables page length updates automatically when display limit changes
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="badge badge-success badge-sm mt-1"></div>
                            <div>
                                <h3 class="font-semibold">Event-Driven Updates</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Uses custom events to communicate between components
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="badge badge-success badge-sm mt-1"></div>
                            <div>
                                <h3 class="font-semibold">Session Persistence</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Display limit preference saved to session via AJAX
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="badge badge-success badge-sm mt-1"></div>
                            <div>
                                <h3 class="font-semibold">Performance Optimized</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Efficient pagination without full page reloads
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Limit Control -->
            <div class="card bg-white dark:bg-gray-800 shadow-xl mb-8">
                <div class="card-body">
                    <h2 class="card-title text-indigo-600 dark:text-indigo-400 mb-4">
                        <i data-lucide="list" class="w-5 h-5"></i>
                        Display Limit Control
                    </h2>
                    <div class="flex items-center gap-4">
                        <?php echo $displayLimitHtml; ?>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Change the limit to see DataTables pagination update automatically
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card bg-white dark:bg-gray-800 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-indigo-600 dark:text-indigo-400 mb-4">
                        <i data-lucide="table" class="w-5 h-5"></i>
                        Sample Data Table (100 records)
                    </h2>
                    <?php echo $tableHtml; ?>
                </div>
            </div>

            <!-- Technical Details -->
            <div class="card bg-white dark:bg-gray-800 shadow-xl mt-8">
                <div class="card-body">
                    <h2 class="card-title text-indigo-600 dark:text-indigo-400 mb-4">
                        <i data-lucide="code" class="w-5 h-5"></i>
                        Technical Implementation
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold mb-2">1. Display Limit Configuration</h3>
                            <div class="mockup-code">
                                <pre><code>$table->displayRowsLimitOnLoad(25)  // Set initial limit
      ->sessionFilters()            // Enable session persistence</code></pre>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">2. DataTables Integration</h3>
                            <div class="mockup-code">
                                <pre><code>// DataTables config includes dynamic pageLength
pageLength: <?php echo $table->getDisplayLimit(); ?>,

// Event listener for display limit changes
document.addEventListener('display-limit-changed', function(event) {
    const pageLength = (event.detail.limit === 'all') ? -1 : parseInt(event.detail.limit);
    table.page.len(pageLength).draw();
});</code></pre>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">3. AJAX Session Persistence</h3>
                            <div class="mockup-code">
                                <pre><code>// Save to session via AJAX
fetch('/datatable/save-display-limit', {
    method: 'POST',
    body: JSON.stringify({ table: 'users', limit: limit })
});</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Add some debugging to see the integration in action
        document.addEventListener('display-limit-changed', function(event) {
            console.log('Display limit changed to:', event.detail.limit);
            
            // Show a toast notification
            const toast = document.createElement('div');
            toast.className = 'toast toast-top toast-end';
            toast.innerHTML = `
                <div class="alert alert-success">
                    <span>Display limit updated to: ${event.detail.limit}</span>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    </script>
</body>
</html>