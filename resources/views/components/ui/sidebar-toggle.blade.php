@props([
    'mobile' => false,
])

@if($mobile)
    {{-- Mobile Menu Toggle --}}
    <button 
        @click="window.openSidebarMobile()"
        class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
        aria-label="{{ __('canvastack::ui.navigation.open_menu') }}"
        {{ $attributes }}
    >
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>
@else
    {{-- Desktop Sidebar Toggle --}}
    <button 
        @click="window.toggleSidebar()"
        class="hidden lg:block p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
        aria-label="{{ __('canvastack::ui.navigation.toggle_sidebar') }}"
        {{ $attributes }}
    >
        <i data-lucide="panel-left" class="w-5 h-5"></i>
    </button>
@endif
