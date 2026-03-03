<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Filter Modal Test</title>
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    
    {{-- DaisyUI --}}
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    
    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    {{-- Lucide Icons --}}
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-8">Filter Actions Test</h1>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Filter Modal Component</h2>
                
                {{-- Filter Modal Component --}}
                <x-canvastack::table.filter-modal
                    :filters="$filters"
                    :activeFilters="$activeFilters"
                    :tableName="$tableName"
                    :activeFilterCount="$activeFilterCount"
                />
                
                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Instructions:</h3>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                        <li>Click the "Filter" button to open the modal</li>
                        <li>Select values from the dropdowns or enter text</li>
                        <li>Click "Apply Filter" to apply filters</li>
                        <li>Click "Clear" to clear all filters</li>
                        <li>Notice the badge count updates based on active filters</li>
                        <li>Check browser console for API calls</li>
                    </ul>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Mock DataTable</h2>
                <div id="mock-datatable" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <p class="text-gray-600 dark:text-gray-400">This is a mock DataTable. When filters are applied, you should see API calls in the browser console.</p>
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">Current Session Filters:</h4>
                        <pre id="session-filters" class="mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded text-sm">{{ json_encode(session('test_filters', []), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mock DataTable object for testing
        window.dataTable = {
            ajax: {
                reload: function() {
                    console.log('DataTable.ajax.reload() called');
                    // Update the session filters display
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                }
            }
        };

        // Mock notification system
        window.showNotification = function(type, message) {
            console.log(`Notification [${type}]: ${message}`);
            
            // Create a simple toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        };

        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>