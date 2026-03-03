# Modal Component

The Modal component provides dialog boxes and overlays with Alpine.js-powered interactivity and event-driven control.

## 📦 Location

- **Blade Component**: `resources/views/components/ui/modal.blade.php`

## 🎯 Features

- Event-driven open/close
- Click outside to close
- Keyboard accessible (Escape key)
- Smooth transitions
- Dark mode support
- Multiple size options
- Header, body, footer slots
- Backdrop overlay
- Prevent body scroll when open

## 📖 Basic Usage

```blade
<x-ui.modal name="example-modal">
    <x-slot name="header">
        <h3 class="text-lg font-bold">Modal Title</h3>
    </x-slot>

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Modal content goes here.
    </p>

    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'example-modal')">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary">
            Confirm
        </x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Trigger -->
<x-ui.button @click="$dispatch('open-modal', 'example-modal')">
    Open Modal
</x-ui.button>
```

## 🔧 Props

| Prop | Type | Default | Options | Description |
|------|------|---------|---------|-------------|
| `name` | string | `'modal'` | Any unique string | Modal identifier (required) |
| `show` | boolean | `false` | `true`, `false` | Initial visibility state |
| `maxWidth` | string | `'md'` | `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `5xl`, `6xl`, `full` | Maximum modal width |

## 📝 Examples

### Confirmation Modal

```blade
<x-ui.modal name="confirm-delete" max-width="sm">
    <x-slot name="header">
        <h3 class="text-lg font-bold text-red-600 dark:text-red-400">
            Confirm Delete
        </h3>
    </x-slot>

    <p class="text-sm text-gray-600 dark:text-gray-400">
        Are you sure you want to delete this item? This action cannot be undone.
    </p>

    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'confirm-delete')">
            Cancel
        </x-ui.button>
        <x-ui.button variant="danger">
            Delete
        </x-ui.button>
    </x-slot>
</x-ui.modal>
```

### Form Modal

```blade
<x-ui.modal name="create-user" max-width="lg">
    <x-slot name="header">
        <h3 class="text-lg font-bold">Create New User</h3>
    </x-slot>

    <form @submit.prevent="submitForm()">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Name</label>
                <input type="text" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
        </div>
    </form>

    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'create-user')">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary" type="submit">
            Create User
        </x-ui.button>
    </x-slot>
</x-ui.modal>
```

### Large Content Modal

```blade
<x-ui.modal name="details" max-width="2xl">
    <x-slot name="header">
        <h3 class="text-lg font-bold">Item Details</h3>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <h4 class="font-semibold mb-2">Information</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Detailed information here...
                </p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <h4 class="font-semibold mb-2">Statistics</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Statistics here...
                </p>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'details')">
            Close
        </x-ui.button>
    </x-slot>
</x-ui.modal>
```

### Modal Without Header/Footer

```blade
<x-ui.modal name="simple" max-width="md">
    <div class="text-center py-6">
        <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="check" class="w-8 h-8 text-emerald-600 dark:text-emerald-400"></i>
        </div>
        <h3 class="text-xl font-bold mb-2">Success!</h3>
        <p class="text-gray-600 dark:text-gray-400">
            Your changes have been saved successfully.
        </p>
        <button 
            @click="$dispatch('close-modal', 'simple')"
            class="mt-6 px-6 py-2 gradient-bg text-white rounded-lg"
        >
            Continue
        </button>
    </div>
</x-ui.modal>
```

## 🎮 Programmatic Control

### JavaScript API

```javascript
// Open modal
window.openModal('modal-name');

// Close modal
window.closeModal('modal-name');

// Or dispatch events directly
window.dispatchEvent(new CustomEvent('open-modal', { 
    detail: 'modal-name' 
}));

window.dispatchEvent(new CustomEvent('close-modal', { 
    detail: 'modal-name' 
}));
```

### Alpine.js Dispatch

```blade
<!-- Open modal -->
<button @click="$dispatch('open-modal', 'modal-name')">
    Open
</button>

<!-- Close modal -->
<button @click="$dispatch('close-modal', 'modal-name')">
    Close
</button>
```

## 🔍 Alpine.js Implementation

The modal uses Alpine.js for state management:

```html
<div 
    x-data="{ show: false }"
    x-on:open-modal.window="$event.detail === 'modal-name' ? show = true : null"
    x-on:close-modal.window="$event.detail === 'modal-name' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    @click.self="show = false"
>
    <!-- Modal content -->
</div>
```

### Key Features

- `x-data="{ show: false }"` - Reactive state
- `x-on:open-modal.window` - Listen for open event
- `x-on:close-modal.window` - Listen for close event
- `x-on:keydown.escape.window` - Close on Escape key
- `@click.self="show = false"` - Close on backdrop click
- `x-show="show"` - Toggle visibility
- `x-transition` - Smooth animations

## 🎯 Accessibility

The modal component includes:

- Keyboard support (Escape key to close)
- Click outside to close
- Focus trap (keeps focus within modal)
- Proper ARIA attributes
- Prevent body scroll when open

## 🎨 Styling

### Default Styles

```css
/* Backdrop */
.fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50

/* Modal container */
.bg-white dark:bg-gray-900 rounded-2xl 
border border-gray-200 dark:border-gray-800 
shadow-2xl
```

### Size Classes

| Size | Max Width | Use Case |
|------|-----------|----------|
| `sm` | 384px | Confirmations, alerts |
| `md` | 448px | Forms, simple content |
| `lg` | 512px | Detailed forms |
| `xl` | 576px | Rich content |
| `2xl` | 672px | Complex layouts |
| `3xl` | 768px | Large content |
| `4xl` | 896px | Very large content |
| `5xl` | 1024px | Full-width content |
| `6xl` | 1152px | Maximum width |
| `full` | 100% | Full screen |

## 🧪 Testing

### Blade Component Test

```php
public function test_modal_renders()
{
    $view = $this->blade(
        '<x-ui.modal name="test">
            <x-slot name="header">Title</x-slot>
            Content
        </x-ui.modal>'
    );

    $view->assertSee('x-data');
    $view->assertSee('test');
    $view->assertSee('Title');
    $view->assertSee('Content');
    $view->assertSee('open-modal');
    $view->assertSee('close-modal');
}
```

### Browser Test

```php
$browser->click('@open-modal-button')
        ->waitFor('@modal')
        ->assertVisible('@modal')
        ->press('Escape')
        ->assertMissing('@modal');
```

## 💡 Tips

1. **Use unique names** - Each modal needs a unique identifier
2. **Keep content focused** - Modals should have a single purpose
3. **Provide clear actions** - Always include cancel/close options
4. **Consider mobile** - Test on small screens
5. **Avoid nested modals** - Don't open modals from within modals
6. **Use appropriate sizes** - Match size to content needs

## 🎭 Common Patterns

### Confirmation Pattern

```blade
<x-ui.modal name="confirm" max-width="sm">
    <x-slot name="header">Confirm Action</x-slot>
    Are you sure?
    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'confirm')">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary">Confirm</x-ui.button>
    </x-slot>
</x-ui.modal>
```

### Form Pattern

```blade
<x-ui.modal name="form" max-width="lg">
    <x-slot name="header">Form Title</x-slot>
    <form><!-- Form fields --></form>
    <x-slot name="footer">
        <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'form')">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary" type="submit">Submit</x-ui.button>
    </x-slot>
</x-ui.modal>
```

### Success Pattern

```blade
<x-ui.modal name="success" max-width="sm">
    <div class="text-center py-6">
        <div class="success-icon mb-4"></div>
        <h3 class="text-xl font-bold mb-2">Success!</h3>
        <p>Operation completed successfully.</p>
    </div>
</x-ui.modal>
```

## 🔗 Related Components

- [Dropdown](dropdown.md) - For simpler menus
- [Button](button.md) - For trigger elements
- [Alert](alert.md) - For inline notifications

## 📚 Resources

- [Alpine.js Events](https://alpinejs.dev/directives/on)
- [Alpine.js Transitions](https://alpinejs.dev/directives/transition)
- [Modal UX Best Practices](https://www.nngroup.com/articles/modal-nonmodal-dialog/)

---

**Last Updated**: 2026-02-26  
**Component Version**: 1.0.0
