# Frontend Documentation

This section covers all frontend technologies and implementations in CanvaStack.

## 📚 Contents

### Core Technologies
- [Alpine.js Integration](alpine-js.md) - Reactive JavaScript framework
- [Tailwind CSS](tailwind-css.md) - Utility-first CSS framework
- [DaisyUI Components](daisyui.md) - Pre-built component library
- [Vite Build Tool](vite.md) - Modern build tool

### Features
- [Dark Mode System](dark-mode.md) - Theme switching implementation
- [Animations (GSAP)](animations.md) - Smooth animations
- [Icons (Lucide)](icons.md) - Icon system
- [Responsive Design](responsive.md) - Mobile-first approach

### Components
- [Dropdown Component](components/dropdown.md) - Context menus
- [Modal Component](components/modal.md) - Dialog boxes
- [Sidebar Component](components/sidebar.md) - Navigation sidebar
- [Dark Mode Toggle](components/dark-mode-toggle.md) - Theme switcher

---

## 🎨 Design System

CanvaStack uses a modern design system based on:

### Color Palette
- **Primary**: Indigo (#6366f1)
- **Secondary**: Purple (#8b5cf6)
- **Accent**: Fuchsia (#a855f7)
- **Gradient**: `linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)`

### Typography
- **Font Family**: Inter (Google Fonts)
- **Sizes**: xs, sm, base, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
- **Weights**: 300, 400, 500, 600, 700, 800, 900

### Spacing
- Tailwind default scale (0.25rem increments)
- Container max-width: 7xl (80rem)

### Border Radius
- sm: 0.375rem (6px)
- md: 0.5rem (8px)
- lg: 0.75rem (12px)
- xl: 1rem (16px)
- 2xl: 1.5rem (24px)

---

## 🚀 Quick Start

### Alpine.js Component
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

### Tailwind CSS Classes
```html
<button class="px-4 py-2 gradient-bg text-white rounded-xl hover:opacity-90">
    Primary Button
</button>
```

### DaisyUI Component
```html
<button class="btn btn-primary">
    DaisyUI Button
</button>
```

---

## 📦 Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| Alpine.js | 3.x | Reactive components |
| Tailwind CSS | 3.x | Utility-first CSS |
| DaisyUI | 4.x | Component library |
| GSAP | 3.x | Animations |
| Lucide | Latest | Icons |
| Vite | 5.x | Build tool |

---

## 🎯 Key Features

### 1. Alpine.js Integration
- Reactive components without build step
- Event-driven architecture
- Lightweight (~15kb minified)
- Vue-like syntax

[Learn more →](alpine-js.md)

### 2. Tailwind CSS
- Utility-first approach
- JIT compilation
- Dark mode support
- Custom design tokens

[Learn more →](tailwind-css.md)

### 3. DaisyUI Components
- Pre-built components
- Semantic class names
- Theme customization
- Accessibility built-in

[Learn more →](daisyui.md)

### 4. Dark Mode
- Class-based implementation
- LocalStorage persistence
- Smooth transitions
- System preference detection

[Learn more →](dark-mode.md)

### 5. Animations
- GSAP-powered animations
- Page transitions
- Hover effects
- Loading states

[Learn more →](animations.md)

---

## 🔧 Development Workflow

### 1. Setup
```bash
cd packages/canvastack/canvastack
npm install
```

### 2. Development
```bash
npm run dev
```

### 3. Build for Production
```bash
npm run build
```

### 4. Preview Production Build
```bash
npm run preview
```

---

## 📖 Component Examples

### Dropdown
```blade
<x-ui.dropdown>
    <x-slot name="trigger">
        <button>Options</button>
    </x-slot>
    <x-ui.dropdown-link href="/profile">Profile</x-ui.dropdown-link>
</x-ui.dropdown>
```

### Modal
```blade
<x-ui.modal name="confirm">
    <x-slot name="header">Confirm Action</x-slot>
    Are you sure?
</x-ui.modal>
```

### Dark Mode Toggle
```blade
<x-ui.dark-mode-toggle />
```

---

## 🎨 Customization

### Tailwind Configuration
Edit `tailwind.config.js`:
```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: '#6366f1',
      }
    }
  }
}
```

### DaisyUI Themes
Edit `tailwind.config.js`:
```javascript
daisyui: {
  themes: ['light', 'dark', 'custom']
}
```

---

## 🧪 Testing

### Component Testing
```php
public function test_dropdown_renders()
{
    $view = $this->blade('<x-ui.dropdown>...</x-ui.dropdown>');
    $view->assertSee('x-data');
}
```

### Browser Testing
Use Laravel Dusk for E2E testing:
```php
$browser->click('@dropdown-trigger')
        ->waitFor('@dropdown-menu')
        ->assertVisible('@dropdown-menu');
```

---

## 📚 Documentation

### Frontend Technologies
- [Alpine.js Integration](alpine-js.md) - Complete Alpine.js guide with examples
- [GSAP Animations](animations.md) - Professional-grade animations with GSAP

### Frontend Components
- [Components Overview](components/README.md) - All frontend components
- [Dropdown](components/dropdown.md) - Dropdown menu component
- [Modal](components/modal.md) - Modal dialog component
- [Dark Mode Toggle](components/dark-mode-toggle.md) - Dark mode switcher
- [Sidebar Toggle](components/sidebar-toggle.md) - Sidebar collapse/expand

---

## 📚 Resources

### Official Documentation
- [Alpine.js Docs](https://alpinejs.dev)
- [Tailwind CSS Docs](https://tailwindcss.com)
- [DaisyUI Docs](https://daisyui.com)
- [GSAP Docs](https://greensock.com/docs)
- [Lucide Icons](https://lucide.dev)

### Examples
- [Alpine.js Examples](../resources/views/components/ui/alpine-examples.blade.php)
- [Component Showcase](../resources/views/components/ui/)

---

## 🤝 Contributing

When contributing frontend code:

1. Follow Tailwind CSS best practices
2. Use Alpine.js for interactivity
3. Ensure dark mode compatibility
4. Test on multiple browsers
5. Maintain accessibility standards

---

## 📝 Best Practices

### 1. Use Utility Classes
```html
<!-- Good -->
<div class="flex items-center gap-4 p-6">

<!-- Avoid -->
<div class="custom-container">
```

### 2. Keep Alpine.js Components Small
```html
<!-- Good -->
<div x-data="{ open: false }">

<!-- Avoid complex logic in templates -->
```

### 3. Support Dark Mode
```html
<div class="bg-white dark:bg-gray-900">
```

### 4. Use Semantic HTML
```html
<button type="button" aria-label="Close">
```

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0
