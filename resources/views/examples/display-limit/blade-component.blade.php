@extends('canvastack::layouts.admin')

@section('title', 'Display Limit UI - Blade Component Example')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Display Limit UI - Blade Component</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            Using the display limit component directly in Blade templates.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Example 1: Default Component -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Default Component</h2>
            
            <x-canvastack::table.display-limit 
                table-name="users_table"
                :current-limit="25" />
            
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <code class="text-sm text-gray-700 dark:text-gray-300">
                    &lt;x-canvastack::table.display-limit 
                        table-name="users_table"
                        :current-limit="25" /&gt;
                </code>
            </div>
        </div>

        <!-- Example 2: Custom Options -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Custom Options</h2>
            
            <x-canvastack::table.display-limit 
                table-name="products_table"
                :current-limit="50"
                :options="[
                    ['value' => '5', 'label' => '5'],
                    ['value' => '20', 'label' => '20'],
                    ['value' => '50', 'label' => '50'],
                    ['value' => '100', 'label' => '100'],
                    ['value' => 'all', 'label' => 'Show All']
                ]" />
            
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <code class="text-sm text-gray-700 dark:text-gray-300">
                    &lt;x-canvastack::table.display-limit 
                        table-name="products_table"
                        :current-limit="50"
                        :options="[
                            ['value' => '5', 'label' => '5'],
                            ['value' => '20', 'label' => '20'],
                            ['value' => '50', 'label' => '50'],
                            ['value' => '100', 'label' => '100'],
                            ['value' => 'all', 'label' => 'Show All']
                        ]" /&gt;
                </code>
            </div>
        </div>

        <!-- Example 3: Compact Version -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Compact Version</h2>
            
            <x-canvastack::table.display-limit 
                table-name="orders_table"
                :current-limit="10"
                :show-label="false"
                size="xs" />
            
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <code class="text-sm text-gray-700 dark:text-gray-300">
                    &lt;x-canvastack::table.display-limit 
                        table-name="orders_table"
                        :current-limit="10"
                        :show-label="false"
                        size="xs" /&gt;
                </code>
            </div>
        </div>

        <!-- Example 4: Large Size -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Large Size</h2>
            
            <x-canvastack::table.display-limit 
                table-name="reports_table"
                :current-limit="'all'"
                size="lg" />
            
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <code class="text-sm text-gray-700 dark:text-gray-300">
                    &lt;x-canvastack::table.display-limit 
                        table-name="reports_table"
                        :current-limit="'all'"
                        size="lg" /&gt;
                </code>
            </div>
        </div>
    </div>

    <!-- Component Properties -->
    <div class="mt-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Component Properties</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Default</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">table-name</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">string</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">'default'</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Unique identifier for session storage</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">current-limit</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">int|string</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">10</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Current display limit (integer or 'all')</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">options</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">array</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">[10, 25, 50, 100, all]</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Array of limit options</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">show-label</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">bool</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">true</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Whether to show "Show:" and "entries" labels</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">size</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">string</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">'sm'</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">Size of the dropdown: 'xs', 'sm', 'md', 'lg'</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection