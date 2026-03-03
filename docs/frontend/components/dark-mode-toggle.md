# Dark Mode Toggle Component

The Dark Mode Toggle component provides theme switching functionality with multiple variants and persistent state management.

## 📦 Location

- **Blade Component**: `resources/views/components/ui/dark-mode-toggle.blade.php`
- **JavaScript Manager**: `resources/js/canvastack.js` (DarkModeManager class)

## 🎯 Features

- Three variants (icon, button, switch)
- Multiple sizes
- LocalStorage persistence
- System preference detection
- Smooth transitions
- Event-driven architecture
- Automatic icon updates
- Keyboard accessible

## 📖 Basic Usage

```blade
<!-- Icon button (default) -->
<x-ui.dark-mode-toggle />

<!-- Full button with text -->
<x-ui.dark-mode-toggle variant="button" />

<!-- Toggle switch -->
<x-ui.dark-mode-toggle variant="switch" />
```

## 🔧 Props

| Prop | Type | Default | Options | Description |
|------|------|---------|---------|-------------|
| `variant` | string | `'icon'` | `icon`, `button`, `switch` | Toggle style variant |
| `size` | string | `'md'` | `sm`, `md`, `lg` | Component size |

## 📝 Examples

### Icon Button Variant

```blade
<!-- Small -->
<x-ui.dark-mode-toggle variant="icon" size="sm" />

<!-- Medium (default) -->
<x-ui.dark-mode-toggle variant="icon" size="md" />

<!-- Large -->
<x-ui.dark-mode-toggle variant="icon" size="lg" />
```

### Button Variant

```blade
<x-ui.dark-mode-toggle variant="button" />
```

This renders a full button with text that changes based on the current theme:
- Light mode: Shows "Dark Mode" with moon icon
- Dark mode: Shows "Light Mode" with sun icon

### Switch Variant

```blade
<x-ui.dark-mode-toggle variant="switch" />
```

This renders a toggle switch with label:
- Light mode: Shows "Light" label
- Dark mode: Shows "Dark" label

### In Navbar

```blade
<nav class="flex items-center justify-between p-4">
    <div class="logo">Logo</div>
    
    <div class="flex items-center gap-4">
        <a href="/profile">Profile</a>
        <x-ui.dark-mode-toggle />
    </div>
</nav>
```

### In Dropdown Menu

```blade
<x-ui.dropdown>
    <x-slot name="trigger">
        <button>Settings</button>
    </x-slot>

    <x-ui.dropdown-link href="/profile">Profile</x-ui.dropdown-link>
    <x-ui.dropdown-link href="/settings">Settings</x-ui.dropdown-link>
    
    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-800">
        <x-ui.dark-mode-toggle variant="switch" />
    </div>
</x-ui.dropdown>
```

## 🎮 Programmatic Control

### JavaScript API

```javascript
// Toggle dark mode
window.toggleDark();

// Enable dark mode
window.darkMode.enable();

// Disable dark mode
window.darkMode.disable();

// Check if dark mode is enabled
const isDark = window.darkMode.isEnabled();

// Listen to dark mode events
window.addEventListener('darkmode:enabled', (e) => {
    console.log('Dark mode enabled', e.detail.isDark);
});

window.addEventListener('darkmode:disabled', (e) => {
    console.log('Dark mode disabled', e.detail.isDark);
});
```

### Alpine.js Usage

```blade
<div x-data="{ isDark: window.darkMode.isEnabled() }">
    <button @click="window.toggleDark(); isDark = !isDark">
        <span x-show="!isDark">🌙 Dark</span>
        <span x-show="isDark">☀️ Light</span>
    </button>
</div>
```

## 🔍 Implementation Details

### DarkModeManager Class

The dark mode system is managed by the `DarkModeManager` class in `canvastack.js`:

```javascript
class DarkModeManager {
    constructor() {
        this.storageKey = 'darkMode';
        this.init();
    }

    init() {
        // Initialize from localStorage or system preference
        const savedMode = localStorage.getItem(this.storageKey);
        
        if (savedMode === 'true') {
            this.enable();
        } else if (savedMode === 'false') {
            this.disable();
        } else {
            // Use system preference
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.enable();
            }
        }
    }

    enable() {
        document.documentElement.classList.add('dark');
        localStorage.setItem(this.storageKey, 'true');
        this.dispatchEvent('enabled');
    }

    disable() {
        document.documentElement.classList.remove('dark');
        localStorage.setItem(this.storageKey, 'false');
        this.dispatchEvent('disabled');
    }

    toggle() {
        this.isEnabled() ? this.disable() : this.enable();
    }

    isEnabled() {
        return document.documentElement.classList.contains('dark');
    }
}
```

### Alpine.js Component

```html
<button 
    @click="window.toggleDark()"
    x-data="{ isDark: document.documentElement.classList.contains('dark') }"
    x-on:darkmode:enabled.window="isDark = true"
    x-on:darkmode:disabled.window="isDark = false"
>
    <i x-show="!isDark" data-lucide="moon"></i>
    <i x-show="isDark" data-lucide="sun"></i>
</button>
```

## 🎯 Features Explained

### 1. LocalStorage Persistence

Dark mode preference is saved to localStorage:
```javascript
localStorage.setItem('darkMode', 'true');
```

### 2. System Preference Detection

Automatically detects system dark mode preference:
```javascript
window.matchMedia('(prefers-color-scheme: dark)').matches
```

### 3. Event System

Dispatches custom events when theme changes:
```javascript
window.addEventListener('darkmode:enabled', (e) => {
    // Handle dark mode enabled
});

window.addEventListener('darkmode:disabled', (e) => {
    // Handle dark mode disabled
});
```

### 4. Icon Updates

Automatically updates Lucide icons after theme change:
```javascript
setTimeout(() => {
    createIcons({ icons });
}, 50);
```

## 🎨 Styling

### Icon Button Sizes

| Size | Padding | Icon Size |
|------|---------|-----------|
| `sm` | `p-1.5` | `w-4 h-4` |
| `md` | `p-2` | `w-5 h-5` |
| `lg` | `p-2.5` | `w-6 h-6` |

### Customization

```blade
<!-- Custom classes -->
<x-ui.dark-mode-toggle class="!bg-blue-500 !text-white" />

<!-- Custom size -->
<x-ui.dark-mode-toggle size="lg" />
```

## 🧪 Testing

### Blade Component Test

```php
public function test_dark_mode_toggle_icon_variant()
{
    $view = $this->blade('<x-ui.dark-mode-toggle />');

    $view->assertSee('window.toggleDark()');
    $view->assertSee('Toggle dark mode');
    $view->assertSee('data-lucide="moon"');
    $view->assertSee('data-lucide="sun"');
}

public function test_dark_mode_toggle_button_variant()
{
    $view = $this->blade('<x-ui.dark-mode-toggle variant="button" />');

    $view->assertSee('Dark Mode');
    $view->assertSee('Light Mode');
}

public function test_dark_mode_toggle_switch_variant()
{
    $view = $this->blade('<x-ui.dark-mode-toggle variant="switch" />');

    $view->assertSee('type="checkbox"');
    $view->assertSee('x-model="isDark"');
}
```

### Browser Test

```php
$browser->assertMissing('.dark')
        ->click('@dark-mode-toggle')
        ->assertPresent('.dark')
        ->refresh()
        ->assertPresent('.dark'); // Persisted
```

### JavaScript Test

```javascript
// Test toggle
window.toggleDark();
expect(document.documentElement.classList.contains('dark')).toBe(true);

// Test enable
window.darkMode.enable();
expect(window.darkMode.isEnabled()).toBe(true);

// Test disable
window.darkMode.disable();
expect(window.darkMode.isEnabled()).toBe(false);

// Test persistence
window.darkMode.enable();
expect(localStorage.getItem('darkMode')).toBe('true');
```

## 💡 Tips

1. **Place in navbar** - Most common location for theme toggles
2. **Use icon variant** - Most compact and recognizable
3. **Test both themes** - Ensure all components work in both modes
4. **Provide feedback** - Use transitions for smooth theme changes
5. **Consider accessibility** - Ensure sufficient contrast in both modes

## 🎭 Common Patterns

### Navbar Integration

```blade
<nav class="bg-white dark:bg-gray-900 border-b">
    <div class="flex items-center justify-between p-4">
        <div>Logo</div>
        <div class="flex items-center gap-4">
            <a href="/profile">Profile</a>
            <x-ui.dark-mode-toggle />
        </div>
    </div>
</nav>
```

### Settings Page

```blade
<div class="p-6">
    <h3 class="text-lg font-semibold mb-4">Appearance</h3>
    
    <div class="flex items-center justify-between">
        <div>
            <p class="font-medium">Dark Mode</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Toggle dark mode theme
            </p>
        </div>
        <x-ui.dark-mode-toggle variant="switch" />
    </div>
</div>
```

### Dropdown Menu

```blade
<x-ui.dropdown>
    <x-slot name="trigger">
        <button>Settings</button>
    </x-slot>

    <div class="p-4 border-b">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium">Theme</span>
            <x-ui.dark-mode-toggle variant="switch" />
        </div>
    </div>
    
    <x-ui.dropdown-link href="/settings">More Settings</x-ui.dropdown-link>
</x-ui.dropdown>
```

## 🔗 Related Components

- [Dropdown](dropdown.md) - For settings menus
- [Sidebar](sidebar.md) - For navigation with theme toggle
- [Button](button.md) - For custom toggle buttons

## 📚 Resources

- [Alpine.js Events](https://alpinejs.dev/directives/on)
- [Tailwind Dark Mode](https://tailwindcss.com/docs/dark-mode)
- [prefers-color-scheme](https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-color-scheme)
- [Dark Mode Best Practices](https://web.dev/prefers-color-scheme/)

---

**Last Updated**: 2026-02-26  
**Component Version**: 1.0.0
