# CanvaStack Blade Components

Complete Blade component library for CanvaStack package with dark mode support, Alpine.js integration, and modern UI design.

## Table of Contents

- [Layouts](#layouts)
- [UI Components](#ui-components)
- [Form Components](#form-components)
- [Table Components](#table-components)
- [Dark Mode](#dark-mode)

---

## Layouts

### Admin Layout

Full admin panel layout with sidebar, navbar, and content area.

```blade
<x-canvastack::layouts.admin title="Dashboard">
    <x-slot:header>
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Welcome back!</p>
    </x-slot:header>
    
    <!-- Your content here -->
</x-canvastack::layouts.admin>
```

**Props:**
- `title` - Page title (optional)
- `navigation` - Array of navigation items (optional)
- `user` - Current user object (optional)
- `breadcrumbs` - Array of breadcrumb items (optional)

### Public Layout

Public-facing website layout with navbar and footer.

```blade
<x-canvastack::layouts.public title="Home" description="Welcome to our site">
    <!-- Your content here -->
</x-canvastack::layouts.public>
```

**Props:**
- `title` - Page title (optional)
- `description` - Meta description (optional)
- `navigation` - Array of navigation items (optional)
- `footerLinks` - Array of footer link columns (optional)

### Auth Layout

Authentication pages layout (login, register, forgot password).

```blade
<x-canvastack::layouts.auth title="Login">
    <x-slot:header>
        <h2 class="text-2xl font-bold text-center">Welcome Back</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mt-2">
            Sign in to your account
        </p>
    </x-slot:header>
    
    <!-- Login form here -->
    
    <x-slot:footer>
        Don't have an account? 
        <a href="/register" class="text-indigo-600 dark:text-indigo-400 hover:underline">Sign up</a>
    </x-slot:footer>
</x-canvastack::layouts.auth>
```

**Props:**
- `title` - Page title (optional)
- `logo` - Custom logo slot (optional)

---

## UI Components

### Button

Versatile button component with multiple variants and sizes.

```blade
<!-- Primary Button -->
<x-canvastack::ui.button variant="primary">
    Save Changes
</x-canvastack::ui.button>

<!-- With Icon -->
<x-canvastack::ui.button variant="primary" icon="plus" iconPosition="left">
    Add User
</x-canvastack::ui.button>

<!-- Loading State -->
<x-canvastack::ui.button variant="primary" :loading="true">
    Processing...
</x-canvastack::ui.button>

<!-- As Link -->
<x-canvastack::ui.button href="/dashboard" variant="secondary">
    Go to Dashboard
</x-canvastack::ui.button>
```

**Props:**
- `variant` - Button style: `primary`, `secondary`, `outline`, `ghost`, `danger`, `success`, `warning` (default: `primary`)
- `size` - Button size: `xs`, `sm`, `md`, `lg`, `xl` (default: `md`)
- `icon` - Lucide icon name (optional)
- `iconPosition` - Icon position: `left`, `right` (default: `left`)
- `href` - URL for link button (optional)
- `type` - Button type: `button`, `submit`, `reset` (default: `button`)
- `disabled` - Disable button (default: `false`)
- `loading` - Show loading spinner (default: `false`)

### Card

Container component with optional header and footer.

```blade
<x-canvastack::ui.card>
    <x-slot:header>
        <h3 class="font-semibold">Card Title</h3>
    </x-slot:header>
    
    <p>Card content goes here.</p>
    
    <x-slot:footer>
        <x-canvastack::ui.button size="sm">Action</x-canvastack::ui.button>
    </x-slot:footer>
</x-canvastack::ui.card>

<!-- With Hover Effect -->
<x-canvastack::ui.card :hover="true">
    Hoverable card
</x-canvastack::ui.card>
```

**Props:**
- `hover` - Enable hover effect (default: `false`)
- `padding` - Add padding (default: `true`)

### Badge

Status indicator component.

```blade
<x-canvastack::ui.badge type="success">Active</x-canvastack::ui.badge>
<x-canvastack::ui.badge type="warning">Pending</x-canvastack::ui.badge>
<x-canvastack::ui.badge type="danger">Failed</x-canvastack::ui.badge>
<x-canvastack::ui.badge type="info" icon="clock">In Progress</x-canvastack::ui.badge>
```

**Props:**
- `type` - Badge style: `default`, `primary`, `success`, `warning`, `danger`, `info` (default: `default`)
- `size` - Badge size: `xs`, `sm`, `md`, `lg` (default: `md`)
- `icon` - Lucide icon name (optional)

### Modal

Alpine.js powered modal dialog.

```blade
<!-- Trigger -->
<x-canvastack::ui.button @click="$dispatch('open-modal', 'confirm-delete')">
    Delete
</x-canvastack::ui.button>

<!-- Modal -->
<x-canvastack::ui.modal name="confirm-delete" maxWidth="md">
    <x-slot:header>
        <h3 class="text-lg font-bold">Confirm Deletion</h3>
    </x-slot:header>
    
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Are you sure you want to delete this item?
    </p>
    
    <x-slot:footer>
        <x-canvastack::ui.button variant="outline" @click="show = false">
            Cancel
        </x-canvastack::ui.button>
        <x-canvastack::ui.button variant="danger">
            Delete
        </x-canvastack::ui.button>
    </x-slot:footer>
</x-canvastack::ui.modal>
```

**Props:**
- `name` - Unique modal identifier (required)
- `show` - Initial visibility state (default: `false`)
- `maxWidth` - Modal width: `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `4xl`, `5xl`, `6xl`, `full` (default: `md`)

### Alert

Notification message component.

```blade
<x-canvastack::ui.alert type="success" message="Changes saved successfully!" />

<x-canvastack::ui.alert type="error" :dismissible="false">
    <strong>Error:</strong> Something went wrong.
</x-canvastack::ui.alert>
```

**Props:**
- `type` - Alert style: `success`, `error`, `warning`, `info` (default: `info`)
- `message` - Alert message (optional, can use slot instead)
- `dismissible` - Show dismiss button (default: `true`)
- `icon` - Custom Lucide icon name (optional)

### Breadcrumbs

Navigation breadcrumb trail.

```blade
<x-canvastack::ui.breadcrumbs :items="[
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Users', 'url' => '/users'],
    ['label' => 'John Doe'],
]" />
```

**Props:**
- `items` - Array of breadcrumb items with `label` and optional `url`

### Dropdown

Alpine.js powered dropdown menu.

```blade
<x-canvastack::ui.dropdown align="right" width="48">
    <x-slot:trigger>
        <x-canvastack::ui.button variant="outline" icon="more-vertical" />
    </x-slot:trigger>
    
    <x-canvastack::ui.dropdown-link href="/edit" icon="edit">
        Edit
    </x-canvastack::ui.dropdown-link>
    <x-canvastack::ui.dropdown-link href="/delete" icon="trash">
        Delete
    </x-canvastack::ui.dropdown-link>
</x-canvastack::ui.dropdown>
```

**Props:**
- `align` - Dropdown alignment: `left`, `right`, `top` (default: `right`)
- `width` - Dropdown width: `48`, `56`, `64`, `72` (default: `48`)

---

## Form Components

### Input

Text input field with icon support.

```blade
<x-canvastack::form.input 
    name="email" 
    label="Email Address"
    type="email"
    icon="mail"
    placeholder="you@example.com"
    :required="true"
    hint="We'll never share your email."
/>
```

**Props:**
- `name` - Input name (required)
- `type` - Input type (default: `text`)
- `label` - Field label (optional)
- `value` - Input value (optional)
- `placeholder` - Placeholder text (optional)
- `icon` - Lucide icon name (optional)
- `iconPosition` - Icon position: `left`, `right` (default: `left`)
- `required` - Mark as required (default: `false`)
- `disabled` - Disable input (default: `false`)
- `readonly` - Make readonly (default: `false`)
- `error` - Error message (optional)
- `hint` - Help text (optional)

### Textarea

Multi-line text input.

```blade
<x-canvastack::form.textarea 
    name="description" 
    label="Description"
    rows="4"
    :maxlength="500"
    :showCount="true"
    hint="Provide a detailed description."
/>
```

**Props:**
- `name` - Textarea name (required)
- `label` - Field label (optional)
- `value` - Textarea value (optional)
- `placeholder` - Placeholder text (optional)
- `rows` - Number of rows (default: `4`)
- `maxlength` - Maximum character length (optional)
- `showCount` - Show character counter (default: `false`)
- `required`, `disabled`, `readonly`, `error`, `hint` - Same as input

### Select

Dropdown select field.

```blade
<x-canvastack::form.select 
    name="status" 
    label="Status"
    :options="[
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
    ]"
    placeholder="Select status"
    icon="check-circle"
/>

<!-- With Option Groups -->
<x-canvastack::form.select 
    name="category" 
    label="Category"
    :options="[
        'Fruits' => [
            'apple' => 'Apple',
            'banana' => 'Banana',
        ],
        'Vegetables' => [
            'carrot' => 'Carrot',
            'lettuce' => 'Lettuce',
        ],
    ]"
/>
```

**Props:**
- `name` - Select name (required)
- `label` - Field label (optional)
- `value` - Selected value (optional)
- `options` - Array of options (required)
- `placeholder` - Placeholder option (optional)
- `icon` - Lucide icon name (optional)
- `required`, `disabled`, `error`, `hint` - Same as input

### Checkbox

Checkbox input field.

```blade
<x-canvastack::form.checkbox 
    name="terms" 
    label="I agree to the terms and conditions"
    :required="true"
/>

<x-canvastack::form.checkbox name="newsletter" value="1">
    Subscribe to newsletter
    <x-slot:hint>
        Receive updates about new features and promotions.
    </x-slot:hint>
</x-canvastack::form.checkbox>
```

**Props:**
- `name` - Checkbox name (required)
- `label` - Checkbox label (optional, can use slot)
- `value` - Checkbox value (default: `1`)
- `checked` - Initial checked state (default: `false`)
- `disabled`, `error`, `hint` - Same as input

### Radio

Radio button input field.

```blade
<x-canvastack::form.radio 
    name="plan" 
    value="basic" 
    label="Basic Plan"
    hint="$9/month"
/>

<x-canvastack::form.radio 
    name="plan" 
    value="pro" 
    label="Pro Plan"
    hint="$29/month"
    :checked="true"
/>
```

**Props:**
- `name` - Radio name (required)
- `value` - Radio value (required)
- `label` - Radio label (optional, can use slot)
- `checked` - Initial checked state (default: `false`)
- `disabled`, `error`, `hint` - Same as input

### File

File upload input with preview.

```blade
<x-canvastack::form.file 
    name="avatar" 
    label="Profile Picture"
    accept="image/*"
    :preview="true"
    hint="Max file size: 2MB"
/>

<!-- Multiple Files -->
<x-canvastack::form.file 
    name="documents" 
    label="Documents"
    :multiple="true"
    accept=".pdf,.doc,.docx"
/>
```

**Props:**
- `name` - File input name (required)
- `label` - Field label (optional)
- `accept` - Accepted file types (optional)
- `multiple` - Allow multiple files (default: `false`)
- `preview` - Show file preview (default: `false`)
- `required`, `disabled`, `error`, `hint` - Same as input

---

## Table Components

### DataTable

Responsive data table component.

```blade
<x-canvastack::table.datatable 
    :headers="[
        ['label' => 'Name', 'sortable' => true],
        ['label' => 'Email'],
        ['label' => 'Status'],
        ['label' => 'Actions', 'class' => 'text-right'],
    ]"
    :rows="[
        ['John Doe', 'john@example.com', '<x-canvastack::ui.badge type=\"success\">Active</x-canvastack::ui.badge>', '...'],
        ['Jane Smith', 'jane@example.com', '<x-canvastack::ui.badge type=\"warning\">Pending</x-canvastack::ui.badge>', '...'],
    ]"
    :striped="true"
    :hoverable="true"
/>
```

**Props:**
- `headers` - Array of table headers
- `rows` - Array of table rows
- `striped` - Alternate row colors (default: `true`)
- `hoverable` - Highlight row on hover (default: `true`)
- `responsive` - Enable horizontal scroll on mobile (default: `true`)

### Pagination

Laravel paginator component.

```blade
<!-- Full Pagination -->
<x-canvastack::table.pagination :paginator="$users" />

<!-- Simple Pagination -->
<x-canvastack::table.pagination :paginator="$users" :simple="true" />
```

**Props:**
- `paginator` - Laravel paginator instance (required)
- `simple` - Use simple pagination (default: `false`)

---

## Dark Mode

### JavaScript API

```javascript
// Toggle dark mode
window.toggleDark();

// Or use the dark mode manager
window.darkMode.enable();
window.darkMode.disable();
window.darkMode.toggle();
window.darkMode.isEnabled(); // Returns boolean

// Listen to dark mode changes
window.addEventListener('darkmode:enabled', (e) => {
    console.log('Dark mode enabled', e.detail.isDark);
});

window.addEventListener('darkmode:disabled', (e) => {
    console.log('Dark mode disabled', e.detail.isDark);
});
```

### Features

- **LocalStorage Persistence**: Dark mode preference is saved and restored
- **System Preference Detection**: Automatically detects OS dark mode preference
- **Smooth Transitions**: All color changes are animated
- **Icon Updates**: Lucide icons are re-initialized after mode change

---

## Usage Examples

### Complete Form Example

```blade
<x-canvastack::layouts.admin title="Create User">
    <x-canvastack::ui.card>
        <x-slot:header>
            <h3 class="font-semibold">User Information</h3>
        </x-slot:header>
        
        <form method="POST" action="/users">
            @csrf
            
            <div class="space-y-5">
                <x-canvastack::form.input 
                    name="name" 
                    label="Full Name"
                    icon="user"
                    :required="true"
                />
                
                <x-canvastack::form.input 
                    name="email" 
                    label="Email Address"
                    type="email"
                    icon="mail"
                    :required="true"
                />
                
                <x-canvastack::form.select 
                    name="role" 
                    label="Role"
                    :options="['admin' => 'Administrator', 'user' => 'User']"
                    icon="shield"
                />
                
                <x-canvastack::form.textarea 
                    name="bio" 
                    label="Biography"
                    rows="4"
                    :maxlength="500"
                    :showCount="true"
                />
                
                <x-canvastack::form.checkbox 
                    name="active" 
                    label="Active User"
                />
            </div>
            
            <div class="flex items-center gap-3 mt-6">
                <x-canvastack::ui.button type="submit" variant="primary">
                    Create User
                </x-canvastack::ui.button>
                <x-canvastack::ui.button href="/users" variant="outline">
                    Cancel
                </x-canvastack::ui.button>
            </div>
        </form>
    </x-canvastack::ui.card>
</x-canvastack::layouts.admin>
```

---

## Accessibility

All components follow accessibility best practices:

- Proper ARIA labels and roles
- Keyboard navigation support
- Focus states for interactive elements
- Screen reader friendly
- Color contrast meets WCAG AA standards

---

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## License

Part of the CanvaStack package. See main package LICENSE for details.
