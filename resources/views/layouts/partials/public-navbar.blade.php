{{-- Public Navbar Block --}}
<nav class="fixed top-0 w-full z-50 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
                </div>
                <span class="text-xl font-bold gradient-text">{{ config('app.name', 'CanvaStack') }}</span>
            </a>
            
            {{-- Desktop Menu --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}" class="text-sm font-medium {{ request()->routeIs('home') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }} transition">
                    Home
                </a>
                <a href="{{ route('about') }}" class="text-sm font-medium {{ request()->routeIs('about') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }} transition">
                    About
                </a>
                
                @auth
                    <a href="{{ route('test.dashboard') }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 gradient-bg text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
                        Get Started
                    </a>
                @endauth
                
                {{-- Dark Mode Toggle --}}
                <button onclick="toggleDark()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                    <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
                    <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
                </button>
            </div>
            
            {{-- Mobile Menu Button --}}
            <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="md:hidden p-2 rounded-lg bg-gray-100 dark:bg-gray-800">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>
        </div>
        
        {{-- Mobile Menu --}}
        <div id="mobile-menu" class="hidden md:hidden pb-4 space-y-2">
            <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('home') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                Home
            </a>
            <a href="{{ route('about') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('about') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                About
            </a>
            
            @auth
                <a href="{{ route('test.dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Login
                </a>
                <a href="{{ route('register') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-white gradient-bg text-center">
                    Get Started
                </a>
            @endauth
            
            <button onclick="toggleDark()" class="w-full px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 text-left">
                Toggle Dark Mode
            </button>
        </div>
    </div>
</nav>
