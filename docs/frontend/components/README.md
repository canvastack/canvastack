# Frontend Components

This directory contains documentation for all Alpine.js-powered frontend components in CanvaStack.

## 📚 Available Components

### Interactive Components
- [Dropdown](dropdown.md) - Context menus and select dropdowns
- [Modal](modal.md) - Dialog boxes and overlays
- [Sidebar Toggle](sidebar-toggle.md) - Collapsible sidebar management
- [Dark Mode Toggle](dark-mode-toggle.md) - Theme switching component

### Form Components
- [Input](input.md) - Text input with icons
- [Select](select.md) - Dropdown select with search
- [Textarea](textarea.md) - Multi-line text input
- [Checkbox](checkbox.md) - Checkbox and radio buttons
- [File Upload](file-upload.md) - File upload with preview

### UI Components
- [Button](button.md) - Action buttons
- [Card](card.md) - Content containers
- [Badge](badge.md) - Status indicators
- [Alert](alert.md) - Notification messages
- [Breadcrumbs](breadcrumbs.md) - Navigation breadcrumbs

### Layout Components
- [Navbar](navbar.md) - Top navigation bar
- [Sidebar](sidebar.md) - Side navigation
- [Footer](footer.md) - Page footer

---

## 🎯 Component Architecture

All components follow these principles:

### 1. Alpine.js Powered
Components use Alpine.js for reactivity:
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

### 2. Blade Component Syntax
Components are Blade components:
```blade
<x-ui.dropdown>
    <x-slot name="trigger">Button</x-slot>
    Content
</x-ui.dropdown>
```

### 3. Tailwind CSS Styling
Components use Tailwind utility classes:
```html
<button class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
```

### 4. Dark Mode Support
All components support dark mode:
```html
<div class="bg-white dark:bg-gray-900">
```

---

## 🚀 Quick Examples

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

### Dark Mode Toggle
```blade
<!-- Icon button (default) -->
<x-ui.dark-mode-toggle />

<!-- Full button with text -->
<x-ui.dark-mode-toggle variant="button" />

<!-- Toggle switch -->
<x-ui.dark-mode-toggle variant="switch" />
```

### Sidebar Toggle
```blade
<!-- Desktop toggle -->
<x-ui.sidebar-toggle />

<!-- Mobile toggle -->
<x-ui.sidebar-toggle mobile />
```

---

## 🎨 Component Props

### Common Props

Most components support these common props:

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `class` | string | - | Additional CSS classes |
| `id` | string | - | Element ID |
| `attributes` | array | - | Additional HTML attributes |

### Component-Specific Props

Each component has its own specific props. See individual component documentation for details.

---

## 🔧 Customization

### Extending Components

You can extend components by creating your own:

```blade
{{-- resources/views/components/custom/my-dropdown.blade.php --}}
<x-ui.dropdown {{ $attributes }}>
    <x-slot name="trigger">
        {{ $trigger }}
    </x-slot>
    
    <div class="p-4">
        {{ $slot }}
    </div>
</x-ui.dropdown>
```

### Overriding Styles

Override component styles using Tailwind classes:

```blade
<x-ui.button class="!bg-red-600 !text-white">
    Custom Styled Button
</x-ui.button>
```

---

## 🧪 Testing Components

### Blade Component Testing

```php
public function test_dropdown_component()
{
    $view = $this->blade(
        '<x-ui.dropdown>
            <x-slot name="trigger"><button>Test</button></x-slot>
        </x-ui.dropdown>'
    );

    $view->assertSee('x-data');
    $view->assertSee('open: false');
}
```

### Browser Testing

```php
$browser->click('@dropdown-trigger')
        ->waitFor('@dropdown-menu')
        ->assertVisible('@dropdown-menu');
```

---

## 📖 Component Patterns

### 1. Trigger + Content Pattern

Used in dropdowns, modals, tooltips:

```blade
<x-component>
    <x-slot name="trigger">Trigger Element</x-slot>
    Content
</x-component>
```

### 2. Header + Body + Footer Pattern

Used in modals, cards:

```blade
<x-component>
    <x-slot name="header">Header</x-slot>
    Body Content
    <x-slot name="footer">Footer</x-slot>
</x-component>
```

### 3. Variant Pattern

Components with multiple styles:

```blade
<x-component variant="primary" />
<x-component variant="secondary" />
```

### 4. Size Pattern

Components with multiple sizes:

```blade
<x-component size="sm" />
<x-component size="md" />
<x-component size="lg" />
```

---

## 🎯 Best Practices

### 1. Use Semantic Slots

```blade
<!-- Good -->
<x-ui.modal>
    <x-slot name="header">Title</x-slot>
    <x-slot name="footer">Actions</x-slot>
</x-ui.modal>

<!-- Avoid -->
<x-ui.modal>
    <div slot="top">Title</div>
</x-ui.modal>
```

### 2. Keep Components Focused

Each component should do one thing well.

### 3. Support Dark Mode

Always include dark mode variants:

```html
<div class="bg-white dark:bg-gray-900">
```

### 4. Maintain Accessibility

Include ARIA labels and keyboard support:

```html
<button aria-label="Close" @keydown.escape="close()">
```

---

## 📚 Resources

- [Alpine.js Documentation](https://alpinejs.dev)
- [Blade Components](https://laravel.com/docs/blade#components)
- [Tailwind CSS](https://tailwindcss.com)
- [DaisyUI Components](https://daisyui.com)

---

## 🤝 Contributing

When creating new components:

1. Follow existing component patterns
2. Include dark mode support
3. Add comprehensive documentation
4. Write tests
5. Provide usage examples

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0
