# Alpine.js Components

This directory contains Alpine.js component implementations for CanvaStack.

## Core Alpine.js Integration

Alpine.js is initialized in `canvastack.js` and provides reactive components throughout the application.

### Available Components

1. **Dropdown** - Context menus and select dropdowns
2. **Modal** - Dialog boxes and overlays
3. **Sidebar Toggle** - Collapsible sidebar management
4. **Dark Mode Toggle** - Theme switching

## Component Managers

### Dark Mode Manager

Handles dark mode state with localStorage persistence.

```javascript
// Toggle dark mode
window.toggleDark();

// Check if dark mode is enabled
window.darkMode.isEnabled();

// Enable/disable programmatically
window.darkMode.enable();
window.darkMode.disable();

// Listen to dark mode events
window.addEventListener('darkmode:enabled', (e) => {
    console.log('Dark mode enabled', e.detail.isDark);
});
```

### Sidebar Manager

Manages sidebar collapse/expand and mobile menu.

```javascript
// Toggle sidebar (desktop)
window.toggleSidebar();

// Open mobile sidebar
window.openSidebarMobile();

// Close mobile sidebar
window.closeSidebarMobile();

// Check if collapsed
window.sidebar.isCollapsed();
```

### Modal Manager

Event-driven modal system.

```javascript
// Open modal
window.openModal('modal-name');

// Close modal
window.closeModal('modal-name');

// Or dispatch events directly
window.dispatchEvent(new CustomEvent('open-modal', { detail: 'modal-name' }));
```

## Blade Component Usage

### Dropdown Component

```blade
<x-ui.dropdown align="right" width="48">
    <x-slot name="trigger">
        <button class="flex items-center gap-2">
            Options
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </button>
    </x-slot>

    <x-ui.dropdown-link href="/profile">Profile</x-ui.dropdown-link>
    <x-ui.dropdown-link href="/settings">Settings</x-ui.dropdown-link>
    <x-ui.dropdown-link href="/logout">Logout</x-ui.dropdown-link>
</x-ui.dropdown>
```

### Modal Component

```blade
<x-ui.modal name="confirm-delete" max-width="md">
    <x-slot name="header">
        <h3 class="text-lg font-bold">Confirm Delete</h3>
    </x-slot>

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Are you sure you want to delete this item?
    </p>

    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'confirm-delete')">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary">
            Delete
        </x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Trigger -->
<x-ui.button @click="$dispatch('open-modal', 'confirm-delete')">
    Delete Item
</x-ui.button>
```

### Sidebar Toggle

```blade
<!-- Desktop toggle -->
<x-ui.sidebar-toggle />

<!-- Mobile toggle -->
<x-ui.sidebar-toggle mobile />
```

### Dark Mode Toggle

```blade
<!-- Icon button (default) -->
<x-ui.dark-mode-toggle />

<!-- Full button with text -->
<x-ui.dark-mode-toggle variant="button" />

<!-- Toggle switch -->
<x-ui.dark-mode-toggle variant="switch" />

<!-- Custom size -->
<x-ui.dark-mode-toggle size="lg" />
```

## Custom Alpine.js Components

You can create custom Alpine.js components using `x-data`:

```blade
<div x-data="{ count: 0 }">
    <button @click="count++">Increment</button>
    <span x-text="count"></span>
</div>
```

### Reusable Component Pattern

```blade
<div x-data="dropdown()">
    <button @click="toggle()">Toggle</button>
    <div x-show="open" @click.away="close()">
        Content
    </div>
</div>

<script>
function dropdown() {
    return {
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        }
    }
}
</script>
```

## Animation with Alpine.js

Alpine.js provides built-in transition directives:

```blade
<div 
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
>
    Content
</div>
```

## Best Practices

1. **Use `@click.away`** for closing dropdowns and modals
2. **Use `@keydown.escape`** for keyboard accessibility
3. **Use `x-cloak`** to prevent flash of unstyled content
4. **Store state in localStorage** for persistence
5. **Dispatch custom events** for component communication
6. **Use `x-ref`** for DOM element references
7. **Keep components small** and focused

## Performance Tips

1. Use `x-show` for frequently toggled elements (keeps in DOM)
2. Use `x-if` for conditionally rendered elements (removes from DOM)
3. Avoid complex expressions in templates
4. Use `x-init` for initialization logic
5. Debounce expensive operations

## Debugging

Enable Alpine.js devtools:

```javascript
// In development
window.Alpine.devtools = true;
```

## Resources

- [Alpine.js Documentation](https://alpinejs.dev)
- [Alpine.js Examples](https://alpinejs.dev/examples)
- [Alpine.js Plugins](https://alpinejs.dev/plugins)
