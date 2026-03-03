# Dropdown Component

The Dropdown component provides a context menu or select dropdown with Alpine.js-powered interactivity.

## 📦 Location

- **Blade Component**: `resources/views/components/ui/dropdown.blade.php`
- **Link Component**: `resources/views/components/ui/dropdown-link.blade.php`

## 🎯 Features

- Click to open/close
- Click outside to close
- Keyboard accessible (Escape key)
- Smooth transitions
- Dark mode support
- Multiple alignment options
- Customizable width
- Nested content support

## 📖 Basic Usage

```blade
<x-ui.dropdown>
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

## 🔧 Props

| Prop | Type | Default | Options | Description |
|------|------|---------|---------|-------------|
| `align` | string | `'right'` | `left`, `right`, `top` | Dropdown alignment |
| `width` | string | `'48'` | `48`, `56`, `64`, `72` | Dropdown width (in Tailwind units) |
| `contentClasses` | string | `''` | Any CSS classes | Additional classes for dropdown content |

## 📝 Examples

### Left Aligned Dropdown

```blade
<x-ui.dropdown align="left">
    <x-slot name="trigger">
        <button>Left Aligned</button>
    </x-slot>

    <x-ui.dropdown-link href="#">Option 1</x-ui.dropdown-link>
    <x-ui.dropdown-link href="#">Option 2</x-ui.dropdown-link>
</x-ui.dropdown>
```

### Wide Dropdown

```blade
<x-ui.dropdown width="72">
    <x-slot name="trigger">
        <button>Wide Dropdown</button>
    </x-slot>

    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
        <p class="text-sm font-semibold">Account Settings</p>
        <p class="text-xs text-gray-500">Manage your account</p>
    </div>
    <x-ui.dropdown-link href="#">Profile Settings</x-ui.dropdown-link>
    <x-ui.dropdown-link href="#">Privacy Settings</x-ui.dropdown-link>
</x-ui.dropdown>
```

### Custom Content

```blade
<x-ui.dropdown>
    <x-slot name="trigger">
        <button>Custom Content</button>
    </x-slot>

    <div class="p-4">
        <h3 class="font-semibold mb-2">Custom Header</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            You can put any content here.
        </p>
        <button class="mt-3 w-full px-4 py-2 bg-indigo-600 text-white rounded-lg">
            Action Button
        </button>
    </div>
</x-ui.dropdown>
```

### With Icons

```blade
<x-ui.dropdown>
    <x-slot name="trigger">
        <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
            <i data-lucide="more-vertical" class="w-5 h-5"></i>
        </button>
    </x-slot>

    <x-ui.dropdown-link href="#">
        <i data-lucide="edit" class="w-4 h-4 mr-2"></i>
        Edit
    </x-ui.dropdown-link>
    <x-ui.dropdown-link href="#">
        <i data-lucide="copy" class="w-4 h-4 mr-2"></i>
        Duplicate
    </x-ui.dropdown-link>
    <x-ui.dropdown-link href="#" class="text-red-600">
        <i data-lucide="trash" class="w-4 h-4 mr-2"></i>
        Delete
    </x-ui.dropdown-link>
</x-ui.dropdown>
```

## 🎨 Dropdown Link Component

The `dropdown-link` component is a helper for creating dropdown menu items.

### Usage

```blade
<x-ui.dropdown-link href="/profile">
    Profile
</x-ui.dropdown-link>

<x-ui.dropdown-link href="/settings" class="text-red-600">
    Logout
</x-ui.dropdown-link>
```

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `href` | string | `#` | Link URL |
| `class` | string | `''` | Additional CSS classes |

## 🔍 Alpine.js Implementation

The dropdown uses Alpine.js for state management:

```html
<div x-data="{ open: false }" @click.away="open = false">
    <div @click="open = !open">
        <!-- Trigger -->
    </div>
    
    <div x-show="open" x-transition>
        <!-- Content -->
    </div>
</div>
```

### Key Features

- `x-data="{ open: false }"` - Reactive state
- `@click.away="open = false"` - Close on outside click
- `x-show="open"` - Toggle visibility
- `x-transition` - Smooth animations

## 🎯 Accessibility

The dropdown component includes:

- Keyboard support (Escape key to close)
- Click outside to close
- Proper ARIA attributes
- Focus management

## 🎨 Styling

### Default Styles

```css
/* Dropdown container */
.relative

/* Dropdown content */
.absolute mt-2 bg-white dark:bg-gray-900 
border border-gray-200 dark:border-gray-800 
rounded-xl shadow-lg py-1 z-50
```

### Customization

Override styles using the `contentClasses` prop:

```blade
<x-ui.dropdown contentClasses="!bg-blue-50 !border-blue-200">
    <!-- Content -->
</x-ui.dropdown>
```

## 🧪 Testing

### Blade Component Test

```php
public function test_dropdown_renders()
{
    $view = $this->blade(
        '<x-ui.dropdown>
            <x-slot name="trigger"><button>Test</button></x-slot>
            <x-ui.dropdown-link href="/test">Link</x-ui.dropdown-link>
        </x-ui.dropdown>'
    );

    $view->assertSee('x-data');
    $view->assertSee('open: false');
    $view->assertSee('@click.away');
    $view->assertSee('Test');
    $view->assertSee('Link');
}
```

### Browser Test

```php
$browser->click('@dropdown-trigger')
        ->waitFor('@dropdown-menu')
        ->assertVisible('@dropdown-menu')
        ->click('@dropdown-option')
        ->assertPathIs('/profile');
```

## 💡 Tips

1. **Use semantic trigger elements** - Buttons for actions, links for navigation
2. **Keep dropdown content focused** - Don't overload with too many options
3. **Consider mobile UX** - Ensure touch targets are large enough
4. **Use icons for clarity** - Icons help users quickly identify actions
5. **Group related items** - Use dividers to separate groups

## 🔗 Related Components

- [Modal](modal.md) - For more complex interactions
- [Button](button.md) - For trigger elements
- [Sidebar](sidebar.md) - For navigation menus

## 📚 Resources

- [Alpine.js Click Away](https://alpinejs.dev/directives/on#away)
- [Alpine.js Transitions](https://alpinejs.dev/directives/transition)
- [Tailwind CSS Positioning](https://tailwindcss.com/docs/position)

---

**Last Updated**: 2026-02-26  
**Component Version**: 1.0.0
