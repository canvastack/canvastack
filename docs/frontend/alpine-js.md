# Alpine.js Integration Guide

## Overview

CanvaStack uses Alpine.js 3.x as its primary JavaScript framework for building reactive, interactive components. Alpine.js provides a declarative syntax similar to Vue.js but with a much smaller footprint (~15kb minified).

## Installation

Alpine.js is already installed and configured in CanvaStack. It's initialized in `resources/js/canvastack.js`:

```javascript
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

## Core Concepts

### 1. Reactive Data with `x-data`

Define reactive component state:

```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

### 2. Event Handling with `@click` / `x-on`

Handle user interactions:

```html
<button @click="count++">Increment</button>
<button x-on:click="count++">Increment (verbose)</button>
```

### 3. Conditional Rendering

**`x-show`** - Toggles CSS display (keeps element in DOM):
```html
<div x-show="isVisible">Visible content</div>
```

**`x-if`** - Conditionally renders (removes from DOM):
```html
<template x-if="isVisible">
    <div>Visible content</div>
</template>
```

### 4. Binding Attributes with `x-bind` / `:`

Dynamically bind attributes:

```html
<div :class="{ 'active': isActive }">Content</div>
<img :src="imageUrl" :alt="imageAlt">
```

### 5. Two-Way Binding with `x-model`

Bind form inputs:

```html
<input type="text" x-model="name">
<p x-text="name"></p>
```

## Built-in Components

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

**Props:**
- `align` - Alignment: `left`, `right`, `top` (default: `right`)
- `width` - Width: `48`, `56`, `64`, `72` (default: `48`)
- `contentClasses` - Additional CSS classes for dropdown content

### Modal Component

```blade
<x-ui.modal name="confirm-delete" max-width="md">
    <x-slot name="header">
        <h3 class="text-lg font-bold">Confirm Delete</h3>
    </x-slot>

    <p>Are you sure you want to delete this item?</p>

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

**Props:**
- `name` - Unique modal identifier (required)
- `show` - Initial visibility state (default: `false`)
- `maxWidth` - Maximum width: `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `5xl`, `6xl`, `full` (default: `md`)

**Programmatic Control:**
```javascript
// Open modal
window.openModal('modal-name');

// Close modal
window.closeModal('modal-name');
```

### Sidebar Toggle

```blade
<!-- Desktop toggle -->
<x-ui.sidebar-toggle />

<!-- Mobile toggle -->
<x-ui.sidebar-toggle mobile />
```

**Props:**
- `mobile` - Enable mobile mode (default: `false`)

**Programmatic Control:**
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

**Props:**
- `variant` - Style variant: `icon`, `button`, `switch` (default: `icon`)
- `size` - Size: `sm`, `md`, `lg` (default: `md`)

**Programmatic Control:**
```javascript
// Toggle dark mode
window.toggleDark();

// Enable dark mode
window.darkMode.enable();

// Disable dark mode
window.darkMode.disable();

// Check if enabled
window.darkMode.isEnabled();

// Listen to events
window.addEventListener('darkmode:enabled', (e) => {
    console.log('Dark mode enabled', e.detail.isDark);
});

window.addEventListener('darkmode:disabled', (e) => {
    console.log('Dark mode disabled', e.detail.isDark);
});
```

## Common Patterns

### Tabs Component

```html
<div x-data="{ tab: 'tab1' }">
    <div class="flex gap-2 border-b">
        <button 
            @click="tab = 'tab1'"
            :class="tab === 'tab1' ? 'border-indigo-500' : 'border-transparent'"
            class="px-4 py-2 border-b-2"
        >
            Tab 1
        </button>
        <button 
            @click="tab = 'tab2'"
            :class="tab === 'tab2' ? 'border-indigo-500' : 'border-transparent'"
            class="px-4 py-2 border-b-2"
        >
            Tab 2
        </button>
    </div>
    
    <div x-show="tab === 'tab1'" class="p-4">Content 1</div>
    <div x-show="tab === 'tab2'" class="p-4">Content 2</div>
</div>
```

### Accordion Component

```html
<div x-data="{ open: false }">
    <button 
        @click="open = !open"
        class="w-full flex items-center justify-between p-4"
    >
        <span>Section Title</span>
        <i data-lucide="chevron-down" :class="{ 'rotate-180': open }"></i>
    </button>
    <div x-show="open" x-collapse class="p-4">
        Section content
    </div>
</div>
```

### Toast Notification

```html
<div x-data="{ show: false, message: '' }">
    <button 
        @click="show = true; message = 'Success!'; setTimeout(() => show = false, 3000)"
    >
        Show Toast
    </button>
    
    <div 
        x-show="show"
        x-transition
        class="fixed bottom-4 right-4 bg-emerald-500 text-white px-6 py-3 rounded-lg"
    >
        <span x-text="message"></span>
    </div>
</div>
```

### Search with Debounce

```html
<div x-data="{ search: '', results: [] }">
    <input 
        type="text" 
        x-model="search"
        @input.debounce.500ms="fetchResults()"
        placeholder="Search..."
    >
    
    <div x-show="results.length > 0">
        <template x-for="result in results" :key="result.id">
            <div x-text="result.name"></div>
        </template>
    </div>
</div>
```

## Transitions

Alpine.js provides built-in transition directives:

```html
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

**Shorthand:**
```html
<div x-show="open" x-transition>
    Content with default transition
</div>
```

## Event Modifiers

Alpine.js supports various event modifiers:

```html
<!-- Prevent default -->
<form @submit.prevent="handleSubmit()">

<!-- Stop propagation -->
<button @click.stop="handleClick()">

<!-- Click outside -->
<div @click.away="close()">

<!-- Keyboard events -->
<input @keydown.escape="close()">
<input @keydown.enter="submit()">

<!-- Debounce -->
<input @input.debounce.500ms="search()">

<!-- Throttle -->
<button @click.throttle.1s="save()">
```

## Magic Properties

Alpine.js provides magic properties prefixed with `$`:

```html
<!-- $el - Reference to current element -->
<div x-init="console.log($el)">

<!-- $refs - Reference to elements with x-ref -->
<input x-ref="email" type="email">
<button @click="$refs.email.focus()">Focus Email</button>

<!-- $event - Access event object -->
<button @click="console.log($event)">Click</button>

<!-- $dispatch - Dispatch custom events -->
<button @click="$dispatch('custom-event', { data: 'value' })">

<!-- $watch - Watch for changes -->
<div x-data="{ count: 0 }" x-init="$watch('count', value => console.log(value))">

<!-- $nextTick - Wait for DOM update -->
<button @click="count++; $nextTick(() => console.log('Updated'))">
```

## Component Communication

### Parent to Child (Props)

```html
<div x-data="{ message: 'Hello' }">
    <child-component :message="message"></child-component>
</div>
```

### Child to Parent (Events)

```html
<!-- Child -->
<button @click="$dispatch('custom-event', { data: 'value' })">
    Trigger Event
</button>

<!-- Parent -->
<div @custom-event="handleEvent($event.detail)">
    <child-component></child-component>
</div>
```

### Global Events

```javascript
// Dispatch
window.dispatchEvent(new CustomEvent('global-event', { 
    detail: { data: 'value' } 
}));

// Listen
window.addEventListener('global-event', (e) => {
    console.log(e.detail.data);
});
```

## Best Practices

### 1. Keep Components Small
Break down complex components into smaller, reusable pieces.

### 2. Use `x-cloak` for Flash Prevention
```html
<style>
    [x-cloak] { display: none !important; }
</style>

<div x-data="{ loaded: false }" x-cloak>
    Content
</div>
```

### 3. Leverage `x-init` for Initialization
```html
<div x-data="{ data: [] }" x-init="data = await fetchData()">
```

### 4. Use `x-show` for Frequent Toggles
Use `x-show` instead of `x-if` for elements that toggle frequently.

### 5. Debounce Expensive Operations
```html
<input @input.debounce.500ms="expensiveOperation()">
```

### 6. Store State in localStorage
```html
<div 
    x-data="{ 
        theme: localStorage.getItem('theme') || 'light' 
    }"
    x-init="$watch('theme', value => localStorage.setItem('theme', value))"
>
```

## Debugging

### Enable Alpine Devtools

```javascript
// In development
window.Alpine.devtools = true;
```

### Log Component State

```html
<div x-data="{ count: 0 }" x-init="console.log($data)">
```

### Use Browser DevTools

Alpine.js components are accessible via `__x` property:

```javascript
// In browser console
$0.__x.$data // Access component data
```

## Performance Tips

1. **Use `x-show` for frequently toggled elements** - Keeps element in DOM
2. **Use `x-if` for conditionally rendered elements** - Removes from DOM
3. **Avoid complex expressions in templates** - Move logic to methods
4. **Use `x-init` for initialization** - Runs once on component mount
5. **Debounce expensive operations** - Use `.debounce` modifier
6. **Lazy load components** - Use `x-if` with lazy loading

## Resources

- [Alpine.js Official Documentation](https://alpinejs.dev)
- [Alpine.js Examples](https://alpinejs.dev/examples)
- [Alpine.js Plugins](https://alpinejs.dev/plugins)
- [Alpine.js GitHub](https://github.com/alpinejs/alpine)

## Examples

See `resources/views/components/ui/alpine-examples.blade.php` for comprehensive examples of all Alpine.js patterns used in CanvaStack.

## Testing

Alpine.js components can be tested using Laravel's Blade testing utilities:

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

See `tests/Feature/AlpineComponentsTest.php` for complete test examples.
