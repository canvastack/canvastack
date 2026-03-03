{{-- Header/Navbar Block --}}
<header class="sticky top-0 z-20 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800">
    <div class="flex items-center justify-between h-16 px-6">
        {{-- Left Side --}}
        <div class="flex items-center gap-4">
            {{-- Mobile Menu Toggle --}}
            <button onclick="openSidebarMobile()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
            
            {{-- Breadcrumbs --}}
            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500">
                <span>Admin</span>
                <i data-lucide="chevron-right" class="w-3 h-3"></i>
                <span class="text-gray-900 dark:text-gray-100 font-medium">@yield('page-title', 'Dashboard')</span>
            </div>
        </div>
        
        {{-- Right Side --}}
        <div class="flex items-center gap-3">
            {{-- Search Box --}}
            <div class="hidden md:flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-xl">
                <i data-lucide="search" class="w-4 h-4 text-gray-400"></i>
                <input type="text" placeholder="Search..." class="bg-transparent outline-none text-sm w-40">
            </div>
            
            {{-- Notifications --}}
            <button class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            
            {{-- Dark Mode Toggle --}}
            <button onclick="toggleDark()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
                <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
            </button>
            
            {{-- User Dropdown --}}
            <div class="relative">
                <button onclick="document.getElementById('user-dropdown').classList.toggle('hidden')" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                    </div>
                    <i data-lucide="chevron-down" class="w-3 h-3 hidden sm:block"></i>
                </button>
                
                <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-lg py-1 z-50">
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="user" class="w-4 h-4"></i> Profile
                    </a>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="settings" class="w-4 h-4"></i> Settings
                    </a>
                    <div class="border-t border-gray-200 dark:border-gray-800 my-1"></div>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
