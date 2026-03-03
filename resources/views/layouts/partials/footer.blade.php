{{-- Footer Block --}}
<footer class="mt-auto border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
    <div class="px-6 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <span>&copy; {{ date('Y') }}</span>
                <a href="mailto:{{ config('mail.from.address', 'admin@canvastack.com') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    {{ config('app.name', 'CanvaStack') }}
                </a>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Documentation</a>
                <a href="#" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">Support</a>
                <a href="#" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition">About</a>
            </div>
        </div>
    </div>
</footer>
