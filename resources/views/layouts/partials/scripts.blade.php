{{-- Scripts Block --}}

{{-- Vite JS --}}
@vite(['resources/js/app.js'])

{{-- Additional Scripts --}}
@stack('scripts')

{{-- Core Scripts --}}
<script>
    // Dark mode toggle
    function toggleDark() {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        document.cookie = `darkMode=${isDark}; path=/; max-age=31536000`;
        localStorage.setItem('darkMode', isDark);
    }
    
    // Initialize dark mode from localStorage
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
    }
    
    // Sidebar toggle (desktop)
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const isCollapsed = sidebar.classList.contains('w-16');
        
        if (isCollapsed) {
            sidebar.classList.remove('w-16');
            sidebar.classList.add('w-64');
            mainContent.classList.remove('ml-16');
            mainContent.classList.add('ml-64');
        } else {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-16');
            mainContent.classList.remove('ml-64');
            mainContent.classList.add('ml-16');
        }
        
        // Toggle labels
        document.querySelectorAll('.sidebar-label').forEach(el => {
            el.classList.toggle('hidden');
        });
    }
    
    // Mobile sidebar
    function openSidebarMobile() {
        document.getElementById('sidebar').classList.remove('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.remove('hidden');
    }
    
    function closeSidebarMobile() {
        document.getElementById('sidebar').classList.add('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.add('hidden');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('user-dropdown');
        const button = event.target.closest('[onclick*="user-dropdown"]');
        if (!button && dropdown && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
