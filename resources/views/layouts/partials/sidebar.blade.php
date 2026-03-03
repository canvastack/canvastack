{{-- Sidebar Block --}}
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-40 sidebar-transition flex flex-col lg:translate-x-0 -translate-x-full">
    {{-- Logo --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-800">
        <a href="{{ route('test.dashboard') }}" class="flex items-center gap-2" id="sidebar-logo">
            <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center shrink-0">
                <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
            </div>
            <span class="text-lg font-bold gradient-text sidebar-label">{{ config('app.name', 'CanvaStack') }}</span>
        </a>
        <button onclick="toggleSidebar()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <i data-lucide="panel-left" class="w-4 h-4"></i>
        </button>
    </div>
    
    {{-- Navigation --}}
    <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
        <a href="{{ route('test.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.dashboard') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="home" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">Dashboard</span>
        </a>
        
        <a href="{{ route('test.table') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.table') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="table" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">Table Builder</span>
        </a>
        
        <a href="{{ route('test.form-create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.form-create') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="blocks" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">Form Builder</span>
        </a>
        
        <a href="{{ route('test.chart') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.chart') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="bar-chart-3" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">Chart Builder</span>
        </a>
        
        <a href="{{ route('test.multi-table') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.multi-table') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="layout-grid" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">Multi Table</span>
        </a>
        
        <div class="border-t border-gray-200 dark:border-gray-800 my-2"></div>
        
        <a href="{{ route('test.theme') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.theme') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="palette" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">Theme System</span>
        </a>
        
        <a href="{{ route('test.i18n') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('test.i18n') ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }} text-sm transition">
            <i data-lucide="languages" class="w-5 h-5 shrink-0"></i>
            <span class="sidebar-label">i18n System</span>
        </a>
    </nav>
    
    {{-- User Info --}}
    <div class="p-3 border-t border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3 px-3 py-2">
            <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0">
                {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
            </div>
            <div class="sidebar-label min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name ?? 'Admin User' }}</p>
                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email ?? 'admin@panel.com' }}</p>
            </div>
        </div>
    </div>
</aside>

{{-- Mobile Overlay --}}
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="closeSidebarMobile()"></div>
