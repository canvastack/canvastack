# Sidebar Toggle Component

The Sidebar Toggle component provides controls for collapsing/expanding the sidebar on desktop and opening/closing the mobile menu.

## 📦 Location

- **Blade Component**: `resources/views/components/ui/sidebar-toggle.blade.php`
- **JavaScript Manager**: `resources/js/canvastack.js` (SidebarManager class)

## 🎯 Features

- Desktop sidebar collapse/expand
- Mobile sidebar open/close
- LocalStorage persistence (desktop)
- Smooth transitions
- Responsive behavior
- Keyboard accessible
- Overlay for mobile

## 📖 Basic Usage

```blade
<!-- Desktop toggle -->
<x-ui.sidebar-toggle />

<!-- Mobile toggle -->
<x-ui.sidebar-toggle mobile />
```

## 🔧 Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `mobile` | boolean | `false` | Enable mobile mode |

## 📝 Examples

### Desktop Sidebar Toggle

```blade
<header class="flex items-center justify-between p-4">
    <div class="flex items-center gap-4">
        <x-ui.sidebar-toggle />
        <h1>Dashboard</h1>
    </div>
</header>
```

### Mobile Menu Toggle

```blade
<header class="flex items-center justify-between p-4">
    <div class="flex items-center gap-4">
        <x-ui.sidebar-toggle mobile />
        <h1>Dashboard</h1>
    </div>
</header>
```

### Combined (Responsive)

```blade
<header class="flex items-center justify-between p-4">
    <div class="flex items-center gap-4">
        <!-- Mobile toggle (visible on mobile) -->
        <x-ui.sidebar-toggle mobile />
        
        <!-- Desktop toggle (visible on desktop) -->
        <x-ui.sidebar-toggle />
        
        <h1>Dashboard</h1>
    </div>
</header>
```

### In Navbar

```blade
<nav class="sticky top-0 bg-white dark:bg-gray-950 border-b">
    <div class="flex items-center justify-between h-16 px-6">
        <!-- Left side -->
        <div class="flex items-center gap-4">
            <x-ui.sidebar-toggle mobile />
            <x-ui.sidebar-toggle />
            <h1 class="text-xl font-bold">CanvaStack</h1>
        </div>
        
        <!-- Right side -->
        <div class="flex items-center gap-3">
            <button class="p-2 rounded-lg hover:bg-gray-100">
                <i data-lucide="bell" class="w-5 h-5"></i>
            </button>
            <x-ui.dark-mode-toggle />
        </div>
    </div>
</nav>
```

## 🎮 Programmatic Control

### JavaScript API

```javascript
// Desktop sidebar
window.toggleSidebar();      // Toggle collapse/expand
window.sidebar.collapse();   // Collapse sidebar
window.sidebar.expand();     // Expand sidebar
window.sidebar.isCollapsed(); // Check if collapsed

// Mobile sidebar
window.openSidebarMobile();  // Open mobile menu
window.closeSidebarMobile(); // Close mobile menu
```

### Alpine.js Usage

```blade
<button @click="window.toggleSidebar()">
    Toggle Sidebar
</button>

<button @click="window.openSidebarMobile()">
    Open Mobile Menu
</button>
```

## 🔍 Implementation Details

### SidebarManager Class

The sidebar system is managed by the `SidebarManager` class in `canvastack.js`:

```javascript
class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('main-content');
        this.overlay = document.getElementById('sidebar-overlay');
        this.storageKey = 'sidebarCollapsed';
        
        if (this.sidebar) {
            this.init();
        }
    }

    init() {
        // Restore sidebar state on desktop
        if (window.innerWidth >= 1024) {
            const isCollapsed = localStorage.getItem(this.storageKey) === 'true';
            if (isCollapsed) {
                this.collapse();
            }
        }
    }

    toggle() {
        this.isCollapsed() ? this.expand() : this.collapse();
    }

    collapse() {
        this.sidebar.classList.remove('w-64');
        this.sidebar.classList.add('w-16');
        this.mainContent.classList.remove('ml-64');
        this.mainContent.classList.add('ml-16');
        
        // Hide labels
        document.querySelectorAll('.sidebar-label').forEach(el => {
            el.classList.add('hidden');
        });
        
        localStorage.setItem(this.storageKey, 'true');
    }

    expand() {
        this.sidebar.classList.remove('w-16');
        this.sidebar.classList.add('w-64');
        this.mainContent.classList.remove('ml-16');
        this.mainContent.classList.add('ml-64');
        
        // Show labels
        document.querySelectorAll('.sidebar-label').forEach(el => {
            el.classList.remove('hidden');
        });
        
        localStorage.setItem(this.storageKey, 'false');
    }

    openMobile() {
        this.sidebar.classList.remove('-translate-x-full');
        this.overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeMobile() {
        this.sidebar.classList.add('-translate-x-full');
        this.overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}
```

## 🎯 Features Explained

### 1. Desktop Sidebar Collapse

On desktop (≥1024px), the sidebar can be collapsed to a narrow icon-only view:

- **Expanded**: 256px wide (w-64)
- **Collapsed**: 64px wide (w-16)
- **Persistence**: State saved to localStorage

### 2. Mobile Sidebar

On mobile (<1024px), the sidebar slides in from the left:

- **Closed**: Translated off-screen (-translate-x-full)
- **Open**: Visible with overlay
- **Body scroll**: Prevented when open

### 3. Responsive Behavior

The component automatically adapts:

```blade
<!-- Desktop only -->
<x-ui.sidebar-toggle class="hidden lg:block" />

<!-- Mobile only -->
<x-ui.sidebar-toggle mobile class="lg:hidden" />
```

### 4. LocalStorage Persistence

Desktop sidebar state persists across page loads:

```javascript
localStorage.setItem('sidebarCollapsed', 'true');
```

## 🎨 Styling

### Desktop Toggle

```html
<button class="hidden lg:block p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
    <i data-lucide="panel-left" class="w-5 h-5"></i>
</button>
```

### Mobile Toggle

```html
<button class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
    <i data-lucide="menu" class="w-5 h-5"></i>
</button>
```

## 🏗️ Sidebar Structure

### Required HTML Structure

```html
<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-64 lg:-translate-x-0 -translate-x-full">
    <!-- Sidebar content -->
    <nav>
        <a href="#" class="flex items-center gap-3 px-3 py-2">
            <i data-lucide="home" class="w-5 h-5"></i>
            <span class="sidebar-label">Dashboard</span>
        </a>
    </nav>
</aside>

<!-- Main content -->
<main id="main-content" class="lg:ml-64">
    <!-- Page content -->
</main>

<!-- Mobile overlay -->
<div 
    id="sidebar-overlay" 
    class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden"
    onclick="window.closeSidebarMobile()"
></div>
```

### Sidebar Item Structure

```html
<a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl">
    <i data-lucide="home" class="w-5 h-5 flex-shrink-0"></i>
    <span class="sidebar-label">Dashboard</span>
</a>
```

The `.sidebar-label` class is important for the collapse functionality.

## 🧪 Testing

### Blade Component Test

```php
public function test_sidebar_toggle_desktop()
{
    $view = $this->blade('<x-ui.sidebar-toggle />');

    $view->assertSee('window.toggleSidebar()');
    $view->assertSee('Toggle sidebar');
    $view->assertSee('hidden lg:block');
}

public function test_sidebar_toggle_mobile()
{
    $view = $this->blade('<x-ui.sidebar-toggle mobile />');

    $view->assertSee('window.openSidebarMobile()');
    $view->assertSee('Open sidebar');
    $view->assertSee('lg:hidden');
}
```

### Browser Test

```php
// Desktop
$browser->assertVisible('#sidebar')
        ->assertSee('Dashboard')
        ->click('@sidebar-toggle')
        ->assertDontSee('Dashboard') // Labels hidden
        ->refresh()
        ->assertDontSee('Dashboard'); // State persisted

// Mobile
$browser->resize(375, 667)
        ->assertMissing('#sidebar')
        ->click('@mobile-menu-toggle')
        ->waitFor('#sidebar')
        ->assertVisible('#sidebar')
        ->click('#sidebar-overlay')
        ->assertMissing('#sidebar');
```

### JavaScript Test

```javascript
// Test toggle
window.toggleSidebar();
expect(window.sidebar.isCollapsed()).toBe(true);

// Test collapse
window.sidebar.collapse();
expect(document.getElementById('sidebar').classList.contains('w-16')).toBe(true);

// Test expand
window.sidebar.expand();
expect(document.getElementById('sidebar').classList.contains('w-64')).toBe(true);

// Test persistence
window.sidebar.collapse();
expect(localStorage.getItem('sidebarCollapsed')).toBe('true');
```

## 💡 Tips

1. **Use both toggles** - Include both desktop and mobile toggles for responsive design
2. **Test on mobile** - Ensure mobile menu works on actual devices
3. **Provide visual feedback** - Use transitions for smooth animations
4. **Consider touch targets** - Ensure buttons are large enough on mobile
5. **Handle window resize** - Close mobile menu when resizing to desktop

## 🎭 Common Patterns

### Admin Layout

```blade
<div class="min-h-screen bg-gray-50 dark:bg-gray-950">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 border-r lg:-translate-x-0 -translate-x-full transition-transform">
        <!-- Sidebar content -->
    </aside>

    <!-- Main content -->
    <main id="main-content" class="lg:ml-64 transition-all">
        <!-- Navbar -->
        <nav class="sticky top-0 bg-white dark:bg-gray-950 border-b">
            <div class="flex items-center justify-between h-16 px-6">
                <div class="flex items-center gap-4">
                    <x-ui.sidebar-toggle mobile />
                    <x-ui.sidebar-toggle />
                    <h1>Dashboard</h1>
                </div>
            </div>
        </nav>

        <!-- Page content -->
        <div class="p-6">
            <!-- Content -->
        </div>
    </main>

    <!-- Mobile overlay -->
    <div 
        id="sidebar-overlay" 
        class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden"
        onclick="window.closeSidebarMobile()"
    ></div>
</div>
```

## 🔗 Related Components

- [Sidebar](sidebar.md) - Full sidebar component
- [Navbar](navbar.md) - Navigation bar
- [Dark Mode Toggle](dark-mode-toggle.md) - Theme switcher

## 📚 Resources

- [Responsive Design](https://tailwindcss.com/docs/responsive-design)
- [Transitions](https://tailwindcss.com/docs/transition-property)
- [LocalStorage API](https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage)

---

**Last Updated**: 2026-02-26  
**Component Version**: 1.0.0
