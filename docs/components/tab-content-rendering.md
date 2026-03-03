# Tab Content Rendering

Complete guide to rendering tab content in TableBuilder with responsive design and accessibility features.

## 📦 Location

- **Renderer Class**: `src/Components/Table/Tab/TabContentRenderer.php`
- **Tab Container View**: `resources/views/components/table/tab-container.blade.php`
- **Tab Content View**: `resources/views/components/table/tab-content.blade.php`
- **Tab Navigation View**: `resources/views/components/table/tab-navigation.blade.php`
- **Tests**: `tests/Unit/Components/Table/Tab/TabContentRendererTest.php`

## 🎯 Features

- Responsive tab content rendering
- Custom HTML content blocks
- Multiple table instances per tab
- Empty state handling
- Loading placeholders
- XSS protection with content sanitization
- Dark mode support
- Accessibility compliant (ARIA attributes)
- Smooth transitions between tabs
- Mobile-responsive design

## 📖 Basic Usage

### Rendering Tab Content

```php
use Canvastack\Canvastack\Components\Table\Tab\TabContentRenderer;
use Canvastack\Canvastack\Components\Table\Tab\Tab;
use Canvastack\Canvastack\Components\Table\Tab\TableInstance;

// Create renderer
$renderer = new TabContentRenderer('my-table', 'admin');

// Create a tab with content
$tab = new Tab('Summary', 'summary');
$tab->addContent('<p>Last updated: 2025-04-01</p>');
$tab->addTable(new TableInstance('users', ['name', 'email'], []));

// Render the tab content
$html = $renderer->renderTabContent($tab, true);
```

### Rendering Complete Tab Container

```php
use Canvastack\Canvastack\Components\Table\Tab\TabManager;

// Create tab manager with multiple tabs
$tabManager = new TabManager();

$tabManager->openTab('Summary');
$tabManager->addContent('<p>Summary information</p>');
$tabManager->addTableToCurrentTab(new TableInstance('summary_table', ['id', 'name'], []));
$tabManager->closeTab();

$tabManager->openTab('Details');
$tabManager->addContent('<p>Detailed information</p>');
$tabManager->addTableToCurrentTab(new TableInstance('details_table', ['id', 'description'], []));
$tabManager->closeTab();

// Render complete tab container with navigation
$html = $renderer->renderTabContainer($tabManager);
```

## 🔧 Configuration

### Setting Context

```php
// Admin context (default)
$renderer->setContext('admin');

// Public context
$renderer->setContext('public');
```

### Responsive Design

```php
// Enable responsive design (default)
$renderer->setResponsive(true);

// Disable responsive design
$renderer->setResponsive(false);
```

### Table ID

```php
// Set unique table ID
$renderer->setTableId('my-custom-table');

// Get table ID
$tableId = $renderer->getTableId();
```

## 📝 Rendering Methods

### 1. Render Tab Content

Renders a single tab's content including custom HTML and tables.

```php
$tab = new Tab('My Tab', 'my-tab');
$tab->addContent('<div class="alert alert-info">Important notice</div>');
$tab->addTable(new TableInstance('users', ['name', 'email'], []));

$html = $renderer->renderTabContent($tab, true);
```

**Output Structure**:
```html
<div id="tabpanel-my-tab" class="tab-content-panel active" role="tabpanel">
    <!-- Custom Content -->
    <div class="tab-custom-content mb-6">
        <div class="content-block mb-4">
            <div class="alert alert-info">Important notice</div>
        </div>
    </div>
    
    <!-- Tables -->
    <div class="tab-tables-container space-y-6">
        <div class="table-wrapper">
            <!-- Table content -->
        </div>
    </div>
</div>
```

### 2. Render Tab Container

Renders complete tab interface with navigation and content.

```php
$html = $renderer->renderTabContainer($tabManager);
```

**Output Structure**:
```html
<div class="table-tab-container responsive">
    <!-- Tab Navigation -->
    <div class="tab-navigation-wrapper">
        <!-- Navigation buttons -->
    </div>
    
    <!-- Tab Content Area -->
    <div class="tab-content-wrapper mt-6">
        <!-- Tab panels -->
    </div>
</div>
```

### 3. Render Table Instance

Renders a single table instance.

```php
$table = new TableInstance('users', ['name', 'email'], []);
$html = $renderer->renderTableInstance($table);
```

### 4. Render Content Block

Renders a custom HTML content block.

```php
// Basic content block
$html = $renderer->renderContentBlock('<p>Custom content</p>');

// With custom CSS class
$html = $renderer->renderContentBlock(
    '<p>Custom content</p>',
    ['class' => 'my-custom-class']
);

// With XSS sanitization
$html = $renderer->renderContentBlock(
    '<script>alert("XSS")</script>',
    ['sanitize' => true]
);
```

### 5. Render Multiple Content Blocks

Renders multiple content blocks with proper spacing.

```php
$contentBlocks = [
    '<p>Block 1</p>',
    '<p>Block 2</p>',
    '<p>Block 3</p>',
];

$html = $renderer->renderContentBlocks($contentBlocks);
```

### 6. Render Multiple Table Instances

Renders multiple tables with proper spacing.

```php
$tables = [
    new TableInstance('users', ['name', 'email'], []),
    new TableInstance('posts', ['title', 'content'], []),
];

$html = $renderer->renderTableInstances($tables);
```

### 7. Render Empty State

Renders an empty state message when tab has no content.

```php
$html = $renderer->renderEmptyState('No data available');
```

**Output**:
```html
<div class="empty-state py-12 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
        <svg><!-- Icon --></svg>
    </div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Content</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">No data available</p>
</div>
```

### 8. Render Loading Placeholder

Renders a loading spinner with message.

```php
$html = $renderer->renderLoadingPlaceholder('Loading data...');
```

**Output**:
```html
<div class="table-loading flex items-center justify-center py-12">
    <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 dark:border-primary-400 mb-4"></div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Loading data...</p>
    </div>
</div>
```

### 9. Render Tab Navigation Only

Renders just the tab navigation without content.

```php
$html = $renderer->renderTabNavigation($tabManager);
```

## 🎨 Styling & Customization

### CSS Classes

The renderer generates semantic CSS classes for easy customization:

```css
/* Tab container */
.table-tab-container { }
.table-tab-container.responsive { }

/* Navigation */
.tab-navigation-wrapper { }

/* Content area */
.tab-content-wrapper { }
.tab-content-panel { }
.tab-content-panel.active { }

/* Custom content */
.tab-custom-content { }
.content-block { }

/* Tables */
.tab-tables-container { }
.table-wrapper { }
.table-instance-container { }
.table-content { }

/* States */
.empty-state { }
.table-loading { }
.table-placeholder { }
```

### Responsive Classes

```php
// Get responsive CSS classes
$classes = $renderer->getResponsiveClasses();
// Returns: "responsive w-full overflow-x-auto"

// Get context-specific classes
$classes = $renderer->getContextClasses();
// Returns: "admin-context" or "public-context"
```

### Wrapping Content

```php
// Wrap content in responsive container
$wrappedHtml = $renderer->wrapInResponsiveContainer($content);
```

## 🔍 Advanced Features

### Data Attributes

Generate data attributes for tab content:

```php
$tab = new Tab('Test Tab', 'test-tab');
$tab->addContent('<p>Content</p>');
$tab->addTable(new TableInstance('users', ['name'], []));

$attributes = $renderer->generateDataAttributes($tab);
// Returns: data-tab-id="test-tab" data-tab-name="Test Tab" data-table-count="1" data-content-count="1"
```

### Tab Validation

Validate tab data before rendering:

```php
try {
    $renderer->validateTab($tab);
    // Tab is valid
} catch (\InvalidArgumentException $e) {
    // Tab validation failed
    echo $e->getMessage();
}
```

### Fluent Interface

Chain method calls for cleaner code:

```php
$html = $renderer
    ->setContext('admin')
    ->setResponsive(true)
    ->setTableId('my-table')
    ->renderTabContainer($tabManager);
```

## 📱 Responsive Design

### Mobile (< 640px)

- Reduced padding (1rem)
- Smaller spacing between elements
- Full-width tables
- Horizontal scroll for tab navigation

### Tablet (640px - 1024px)

- Medium padding (1rem)
- Standard spacing
- Responsive table containers

### Desktop (> 1024px)

- Full padding (2rem)
- Maximum spacing
- Optimal table layout

### CSS Media Queries

```css
/* Mobile */
@media (max-width: 640px) {
    .table-content {
        padding: 1rem !important;
    }
}

/* Tablet and up */
@media (min-width: 769px) {
    .table-tab-container {
        padding: 0 1rem;
    }
}

/* Desktop and up */
@media (min-width: 1024px) {
    .table-tab-container {
        padding: 0 2rem;
    }
}
```

## 🎯 Accessibility

### ARIA Attributes

The renderer automatically adds proper ARIA attributes:

```html
<div role="tabpanel" 
     id="tabpanel-summary"
     aria-labelledby="tab-summary"
     aria-hidden="false"
     tabindex="0">
    <!-- Content -->
</div>
```

### Keyboard Navigation

- **Tab**: Focus on tab navigation
- **Arrow Left/Up**: Previous tab
- **Arrow Right/Down**: Next tab
- **Home**: First tab
- **End**: Last tab

### Focus Management

```javascript
// Focus is automatically managed when switching tabs
updateFocus(tabId) {
    this.$nextTick(() => {
        const tabButton = document.getElementById(`tab-${tabId}`);
        if (tabButton) {
            tabButton.focus();
        }
    });
}
```

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
    .tab-content-item {
        transition: none !important;
    }
}
```

## 🧪 Testing

### Unit Tests

```php
use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Components\Table\Tab\TabContentRenderer;
use Canvastack\Canvastack\Components\Table\Tab\Tab;
use Canvastack\Canvastack\Components\Table\Tab\TableInstance;

class TabContentRendererTest extends TestCase
{
    public function test_render_tab_content(): void
    {
        $renderer = new TabContentRenderer('test-table', 'admin');
        $tab = new Tab('Test Tab', 'test-tab');
        $tab->addContent('<p>Test content</p>');

        $html = $renderer->renderTabContent($tab, true);

        $this->assertStringContainsString('tabpanel-test-tab', $html);
        $this->assertStringContainsString('Test content', $html);
    }

    public function test_render_content_block_with_sanitization(): void
    {
        $renderer = new TabContentRenderer('test-table', 'admin');
        $content = '<script>alert("XSS")</script>';

        $html = $renderer->renderContentBlock($content, ['sanitize' => true]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_render_empty_state(): void
    {
        $renderer = new TabContentRenderer('test-table', 'admin');
        $html = $renderer->renderEmptyState('No data available');

        $this->assertStringContainsString('empty-state', $html);
        $this->assertStringContainsString('No data available', $html);
    }
}
```

### Integration Tests

```php
public function test_complete_tab_rendering_workflow(): void
{
    $renderer = new TabContentRenderer('test-table', 'admin');
    $tabManager = new TabManager();

    // Create tabs
    $tabManager->openTab('Tab 1');
    $tabManager->addContent('<p>Content 1</p>');
    $tabManager->addTableToCurrentTab(new TableInstance('table1', ['col1'], []));
    $tabManager->closeTab();

    $tabManager->openTab('Tab 2');
    $tabManager->addContent('<p>Content 2</p>');
    $tabManager->closeTab();

    // Render
    $html = $renderer->renderTabContainer($tabManager);

    // Assertions
    $this->assertStringContainsString('Tab 1', $html);
    $this->assertStringContainsString('Tab 2', $html);
    $this->assertStringContainsString('Content 1', $html);
    $this->assertStringContainsString('Content 2', $html);
}
```

## 💡 Best Practices

### 1. Always Set Context

```php
// ✅ Good
$renderer->setContext('admin');

// ❌ Bad - relies on default
$renderer = new TabContentRenderer();
```

### 2. Sanitize User-Generated Content

```php
// ✅ Good - sanitize untrusted content
$html = $renderer->renderContentBlock($userContent, ['sanitize' => true]);

// ❌ Bad - XSS vulnerability
$html = $renderer->renderContentBlock($userContent);
```

### 3. Use Unique Table IDs

```php
// ✅ Good - unique ID per table
$renderer->setTableId('users-table-' . $userId);

// ❌ Bad - generic ID may cause conflicts
$renderer->setTableId('table');
```

### 4. Validate Tabs Before Rendering

```php
// ✅ Good - validate first
try {
    $renderer->validateTab($tab);
    $html = $renderer->renderTabContent($tab);
} catch (\InvalidArgumentException $e) {
    // Handle error
}

// ❌ Bad - no validation
$html = $renderer->renderTabContent($tab);
```

### 5. Handle Empty States

```php
// ✅ Good - check for empty content
if (empty($tab->getContent()) && empty($tab->getTables())) {
    $html = $renderer->renderEmptyState('No data available');
} else {
    $html = $renderer->renderTabContent($tab);
}

// ❌ Bad - render without checking
$html = $renderer->renderTabContent($tab);
```

## 🎭 Common Patterns

### Pattern 1: Multi-Tab Report

```php
$renderer = new TabContentRenderer('report-table', 'admin');
$tabManager = new TabManager();

// Summary tab
$tabManager->openTab('Summary');
$tabManager->addContent('<p>Last updated: ' . date('Y-m-d') . '</p>');
$tabManager->addTableToCurrentTab(new TableInstance('summary', ['metric', 'value'], []));
$tabManager->closeTab();

// Details tab
$tabManager->openTab('Details');
$tabManager->addTableToCurrentTab(new TableInstance('details', ['id', 'name', 'status'], []));
$tabManager->closeTab();

// Render
$html = $renderer->renderTabContainer($tabManager);
```

### Pattern 2: Tab with Multiple Tables

```php
$renderer = new TabContentRenderer('multi-table', 'admin');
$tab = new Tab('Dashboard', 'dashboard');

// Add custom content
$tab->addContent('<h3>Overview</h3>');

// Add multiple tables
$tab->addTable(new TableInstance('users', ['name', 'email'], []));
$tab->addTable(new TableInstance('posts', ['title', 'author'], []));
$tab->addTable(new TableInstance('comments', ['content', 'user'], []));

$html = $renderer->renderTabContent($tab, true);
```

### Pattern 3: Loading State

```php
$renderer = new TabContentRenderer('async-table', 'admin');

// Show loading placeholder initially
$html = $renderer->renderLoadingPlaceholder('Loading data...');

// Later, replace with actual content via AJAX
// JavaScript will replace the loading placeholder with real content
```

### Pattern 4: Conditional Content

```php
$renderer = new TabContentRenderer('conditional-table', 'admin');
$tab = new Tab('Data', 'data');

if ($hasData) {
    $tab->addTable(new TableInstance('data', ['col1', 'col2'], []));
} else {
    // Will automatically show empty state
}

$html = $renderer->renderTabContent($tab, true);
```

## 🔗 Related Components

- [TabManager](./tab-manager.md) - Manages multiple tabs
- [Tab](./tab.md) - Individual tab data structure
- [TableInstance](./table-instance.md) - Table data structure
- [Tab Navigation](./tab-navigation.md) - Tab navigation UI

## 📚 Resources

- [TableBuilder Documentation](../table-builder.md)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Tailwind CSS Documentation](https://tailwindcss.com)
- [ARIA Authoring Practices - Tabs](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/)

---

**Last Updated**: 2026-03-02  
**Version**: 1.0.0  
**Status**: Complete
