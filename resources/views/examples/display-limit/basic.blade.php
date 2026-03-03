@extends('canvastack::layouts.admin')

@section('title', 'Display Limit UI - Basic Example')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Display Limit UI - Basic Example</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            Basic usage of the display limit UI component with default options.
        </p>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        <!-- Table Header with Display Limit UI -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Users</h2>
            
            <!-- Display Limit UI -->
            <div class="flex items-center gap-4">
                {!! $displayLimitUI !!}
            </div>
        </div>

        <!-- Table -->
        {!! $table->render() !!}
    </div>

    <!-- Code Example -->
    <div class="mt-8 bg-gray-50 dark:bg-gray-800 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Code Example</h3>
        <pre class="text-sm text-gray-700 dark:text-gray-300 overflow-x-auto"><code>// Controller
public function index(TableBuilder $table): View
{
    $table->setContext('admin');
    $table->setModel(new User());
    $table->setFields(['name:Name', 'email:Email', 'created_at:Created']);
    
    // Set initial display limit
    $table->displayRowsLimitOnLoad(25);
    
    // Enable session persistence
    $table->sessionFilters();
    
    $table->format();
    
    return view('users.index', [
        'table' => $table,
        'displayLimitUI' => $table->renderDisplayLimitUI(),
    ]);
}

// Blade Template
&lt;div class="flex items-center justify-between mb-6"&gt;
    &lt;h2&gt;Users&lt;/h2&gt;
    {!! $displayLimitUI !!}
&lt;/div&gt;

{!! $table->render() !!}</code></pre>
    </div>
</div>
@endsection