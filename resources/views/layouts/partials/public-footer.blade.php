{{-- Public Footer Block --}}
<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            {{-- Brand --}}
            <div class="col-span-1 md:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 gradient-bg rounded-lg flex items-center justify-center">
                        <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="text-xl font-bold gradient-text">{{ config('app.name', 'CanvaStack') }}</span>
                </a>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 max-w-md">
                    A modern CanvaStack CMS package with powerful components, theme system, and i18n support.
                </p>
                <div class="flex items-center gap-3">
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition">
                        <i data-lucide="github" class="w-4 h-4"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition">
                        <i data-lucide="twitter" class="w-4 h-4"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition">
                        <i data-lucide="linkedin" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
            
            {{-- Product --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Product</h3>
                <ul class="space-y-3">
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Features</a></li>
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Pricing</a></li>
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Documentation</a></li>
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Changelog</a></li>
                </ul>
            </div>
            
            {{-- Company --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Company</h3>
                <ul class="space-y-3">
                    <li><a href="{{ route('about') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">About</a></li>
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Blog</a></li>
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Contact</a></li>
                    <li><a href="#" class="text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">Careers</a></li>
                </ul>
            </div>
        </div>
        
        {{-- Bottom --}}
        <div class="pt-8 border-t border-gray-200 dark:border-gray-800">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'CanvaStack') }}. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <a href="#" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Privacy Policy</a>
                    <a href="#" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Terms of Service</a>
                </div>
            </div>
        </div>
    </div>
</footer>
