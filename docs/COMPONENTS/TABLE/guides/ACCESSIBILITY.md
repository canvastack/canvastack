# Table Components Accessibility Guidelines

## Overview

The Table Components system is designed to be fully accessible and compliant with WCAG 2.1 Level A standards. This guide explains the accessibility features and how to use them effectively.

---

## Table of Contents

- [ARIA Attributes](#aria-attributes)
- [Keyboard Navigation](#keyboard-navigation)
- [Screen Reader Support](#screen-reader-support)
- [Focus Management](#focus-management)
- [Color and Contrast](#color-and-contrast)
- [Responsive Design](#responsive-design)
- [Testing Accessibility](#testing-accessibility)
- [Best Practices](#best-practices)

---

## ARIA Attributes

### Automatic ARIA Attributes

The Table Components system automatically adds appropriate ARIA attributes to all elements. No additional configuration is required.

### Table Element

```html
<table role="table" 
       aria-label="Users table" 
       aria-describedby="users-table-caption"
       aria-rowcount="150">
    <caption id="users-table-caption">
        Users table with 150 records
    </caption>
    ...
</table>
```

**Attributes:**
- `role="table"`: Identifies element as a table
- `aria-label`: Provides accessible name
- `aria-describedby`: Links to caption
- `aria-rowcount`: Total number of rows

---

### Header Cells

```html
<th role="columnheader" 
    aria-sort="ascending"
    aria-label="Name, sortable column, currently sorted ascending"
    tabindex="0">
    Name
    <span class="sort-indicator" aria-hidden="true">▲</span>
</th>
```

**Attributes:**
- `role="columnheader"`: Identifies as column header
- `aria-sort`: Indicates sort state (ascending, descending, none)
- `aria-label`: Descriptive label including sort state
- `tabindex="0"`: Makes header keyboard focusable

**Sort States:**
- `aria-sort="none"`: Not sorted
- `aria-sort="ascending"`: Sorted ascending
- `aria-sort="descending"`: Sorted descending

---

### Body Cells

```html
<tr role="row" aria-rowindex="5">
    <td role="cell" headers="col-name">John Doe</td>
    <td role="cell" headers="col-email">john@example.com</td>
</tr>
```

**Attributes:**
- `role="row"`: Identifies as table row
- `role="cell"`: Identifies as table cell
- `headers`: Associates cell with header
- `aria-rowindex`: Row position in table

---

### Action Buttons

```html
<button role="button"
        aria-label="Edit user John Doe"
        tabindex="0"
        class="btn-edit">
    <i class="fa fa-edit" aria-hidden="true"></i>
    <span class="sr-only">Edit</span>
</button>
```

**Attributes:**
- `role="button"`: Identifies as button
- `aria-label`: Descriptive label with context
- `tabindex="0"`: Keyboard focusable
- `aria-hidden="true"`: Hides decorative icons from screen readers

---

### Loading States

```html
<table role="table" aria-busy="true" aria-live="polite">
    ...
</table>

<div role="status" aria-live="polite" aria-atomic="true" class="sr-only">
    Loading table data...
</div>
```

**Attributes:**
- `aria-busy="true"`: Indicates loading state
- `aria-live="polite"`: Announces updates
- `aria-atomic="true"`: Reads entire region
- `role="status"`: Identifies as status message

---

### Pagination

```html
<nav role="navigation" aria-label="Table pagination">
    <ul class="pagination">
        <li>
            <button aria-label="Go to previous page" aria-disabled="true">
                Previous
            </button>
        </li>
        <li>
            <button aria-label="Go to page 1" aria-current="page">
                1
            </button>
        </li>
        <li>
            <button aria-label="Go to page 2">
                2
            </button>
        </li>
        <li>
            <button aria-label="Go to next page">
                Next
            </button>
        </li>
    </ul>
</nav>
```

**Attributes:**
- `role="navigation"`: Identifies navigation region
- `aria-label`: Describes navigation purpose
- `aria-current="page"`: Indicates current page
- `aria-disabled`: Indicates disabled state

---

## Keyboard Navigation

### Table Navigation

**Tab Key:**
- Tab through sortable column headers
- Tab through action buttons
- Tab through pagination controls
- Tab through filter controls

**Arrow Keys:**
- ↑↓ Navigate between rows (when row selection enabled)
- ←→ Navigate between cells (when cell navigation enabled)

**Enter/Space:**
- Activate sortable column headers
- Activate action buttons
- Submit forms

**Escape:**
- Close modals
- Cancel operations
- Clear focus

---

### Sortable Columns

```javascript
// Keyboard sorting is automatically enabled
// Users can:
// 1. Tab to column header
// 2. Press Enter or Space to sort
// 3. Press again to reverse sort
```

**Visual Feedback:**
- Focus indicator on header
- Sort direction indicator
- Screen reader announcement

---

### Action Buttons

```javascript
// Action buttons are keyboard accessible
// Users can:
// 1. Tab to button
// 2. Press Enter or Space to activate
// 3. Escape to cancel (if confirmation dialog)
```

---

### Filter Modal

```javascript
// Modal keyboard navigation
// Users can:
// 1. Tab through filter fields
// 2. Enter to submit
// 3. Escape to close
// 4. Focus trapped in modal
```

**Focus Management:**
```javascript
// When modal opens:
// - Focus moves to first input
// - Tab cycles through modal only
// - Escape closes modal
// - Focus returns to trigger button
```

---

## Screen Reader Support

### Table Announcements

**Table Load:**
```
"Users table with 150 records. Table has 5 columns: Name, Email, Status, Created Date, Actions."
```

**Sort Change:**
```
"Name column sorted ascending. Table updated with 150 records."
```

**Filter Applied:**
```
"Filters applied. Table updated with 45 records matching your criteria."
```

**Page Change:**
```
"Page 2 of 15. Showing records 11 to 20 of 150."
```

---

### Column Headers

**Sortable Column:**
```
"Name, sortable column, currently sorted ascending. Press Enter to sort descending."
```

**Non-Sortable Column:**
```
"Email address column."
```

---

### Data Cells

**With Context:**
```
"Name: John Doe"
"Email: john@example.com"
"Status: Active"
```

**Action Buttons:**
```
"Edit user John Doe, button"
"Delete user John Doe, button"
```

---

### Live Regions

```html
<!-- Status announcements -->
<div id="table-status" 
     role="status" 
     aria-live="polite" 
     aria-atomic="true" 
     class="sr-only">
    <!-- Dynamically updated with status messages -->
</div>

<!-- Alert announcements -->
<div id="table-alert" 
     role="alert" 
     aria-live="assertive" 
     aria-atomic="true" 
     class="sr-only">
    <!-- Dynamically updated with important alerts -->
</div>
```

**Usage:**
- `role="status"`: Non-critical updates (polite)
- `role="alert"`: Critical updates (assertive)
- `aria-live="polite"`: Waits for user to finish
- `aria-live="assertive"`: Interrupts immediately

---

## Focus Management

### Focus Indicators

**Default Focus Style:**
```css
.table th:focus,
.table button:focus,
.table a:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}
```

**Custom Focus Indicators:**
```php
// Focus indicators are automatically applied
// No configuration needed
```

---

### Focus Order

**Logical Tab Order:**
1. Table caption/title
2. Filter button
3. Column headers (sortable)
4. First row action buttons
5. Second row action buttons
6. ...
7. Pagination controls

---

### Focus Trapping

**Modal Focus Trap:**
```javascript
// When modal opens:
// 1. Save current focus
// 2. Move focus to modal
// 3. Trap focus in modal
// 4. Restore focus on close
```

**Implementation:**
```javascript
// Automatically handled by ModalRenderer
// No additional code needed
```

---

## Color and Contrast

### Contrast Requirements

**WCAG 2.1 Level AA:**
- Normal text: 4.5:1 contrast ratio
- Large text: 3:1 contrast ratio
- UI components: 3:1 contrast ratio

**Default Colors:**
```css
/* Header background: #f8f9fa */
/* Header text: #212529 */
/* Contrast ratio: 8.59:1 ✓ */

/* Row hover: #f5f5f5 */
/* Row text: #212529 */
/* Contrast ratio: 9.74:1 ✓ */

/* Primary button: #007bff */
/* Button text: #ffffff */
/* Contrast ratio: 4.56:1 ✓ */
```

---

### Custom Colors

**Setting Colors with Good Contrast:**
```php
// Good contrast
$table->setBackgroundColor('#f0f0f0', '#333333');

// Poor contrast - avoid
// $table->setBackgroundColor('#ffff00', '#ffffff'); // 1.07:1 ✗
```

**Validation:**
```php
// Color contrast is automatically validated
// Warning logged if contrast ratio < 4.5:1
```

---

### Color Independence

**Don't Rely on Color Alone:**
```php
// Bad - color only
$table->setBackgroundColor('#ff0000', '#ffffff', ['status']);

// Good - color + text/icon
$table->format('status', 0, '.', 'status', [
    'active' => ['color' => '#28a745', 'icon' => '✓', 'text' => 'Active'],
    'inactive' => ['color' => '#dc3545', 'icon' => '✗', 'text' => 'Inactive']
]);
```

---

## Responsive Design

### Mobile Accessibility

**Touch Targets:**
- Minimum 44x44 pixels
- Adequate spacing between targets
- No overlapping interactive elements

**Responsive Tables:**
```html
<div class="table-responsive">
    <table>...</table>
</div>
```

**Mobile Navigation:**
- Swipe to scroll horizontally
- Tap to sort
- Tap to open filters
- Pinch to zoom (not disabled)

---

### Viewport Configuration

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

**Zoom Support:**
- Allow zoom up to 200%
- Don't disable user scaling
- Maintain functionality when zoomed

---

## Testing Accessibility

### Automated Testing

**Tools:**
- axe DevTools
- WAVE
- Lighthouse
- Pa11y

**Example with axe:**
```javascript
// Run axe on table
axe.run('#users-table', function(err, results) {
    if (err) throw err;
    console.log(results.violations);
});
```

---

### Manual Testing

**Keyboard Testing:**
1. Unplug mouse
2. Tab through all interactive elements
3. Verify focus indicators visible
4. Test all keyboard shortcuts
5. Verify no keyboard traps

**Screen Reader Testing:**
- NVDA (Windows)
- JAWS (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)

**Testing Checklist:**
- [ ] All images have alt text
- [ ] All buttons have labels
- [ ] All form inputs have labels
- [ ] Heading hierarchy is logical
- [ ] Color contrast meets standards
- [ ] Keyboard navigation works
- [ ] Focus indicators visible
- [ ] Screen reader announces correctly
- [ ] No keyboard traps
- [ ] Zoom works up to 200%

---

### Browser Testing

**Test in Multiple Browsers:**
- Chrome + ChromeVox
- Firefox + NVDA
- Safari + VoiceOver
- Edge + Narrator

---

## Best Practices

### 1. Provide Descriptive Labels

**Good:**
```php
$table->setFields([
    'name' => 'Full Name',
    'email' => 'Email Address',
    'created_at' => 'Registration Date'
]);
```

**Bad:**
```php
$table->setFields(['name', 'email', 'created_at']); // Generic labels
```

---

### 2. Use Semantic HTML

**Good:**
```html
<table>
    <caption>Users List</caption>
    <thead>
        <tr><th>Name</th></tr>
    </thead>
    <tbody>
        <tr><td>John</td></tr>
    </tbody>
</table>
```

**Bad:**
```html
<div class="table">
    <div class="row">
        <div class="cell">Name</div>
    </div>
</div>
```

---

### 3. Provide Alternative Text

**Good:**
```php
$table->setActions([
    'edit' => [
        'label' => 'Edit',
        'icon' => 'fa-edit',
        'aria-label' => 'Edit user {name}'
    ]
]);
```

**Bad:**
```php
$table->setActions([
    'edit' => [
        'icon' => 'fa-edit' // No text alternative
    ]
]);
```

---

### 4. Maintain Focus Order

**Good:**
```php
// Logical tab order maintained automatically
$table->lists();
```

**Bad:**
```php
// Don't use tabindex > 0
// <button tabindex="5">...</button>
```

---

### 5. Provide Status Updates

**Good:**
```javascript
// Status updates announced automatically
// "Loading table data..."
// "Table updated with 150 records"
```

**Bad:**
```javascript
// Silent updates - screen reader users don't know what happened
```

---

### 6. Support Keyboard Shortcuts

**Good:**
```php
// Keyboard shortcuts automatically enabled
// Enter/Space to sort
// Escape to close modals
```

**Bad:**
```php
// Mouse-only interactions
```

---

### 7. Provide Skip Links

**Good:**
```html
<a href="#main-content" class="skip-link">Skip to main content</a>
<a href="#table-content" class="skip-link">Skip to table</a>
```

---

### 8. Use ARIA Sparingly

**Good:**
```html
<!-- Use semantic HTML first -->
<button>Edit</button>

<!-- Add ARIA only when needed -->
<button aria-label="Edit user John Doe">Edit</button>
```

**Bad:**
```html
<!-- Over-use of ARIA -->
<div role="button" tabindex="0" aria-pressed="false">Edit</div>
```

---

## Common Issues and Solutions

### Issue: Sort Indicator Not Announced

**Problem:**
```html
<th>Name <span class="sort-icon">▲</span></th>
```

**Solution:**
```html
<th aria-sort="ascending">
    Name 
    <span class="sort-icon" aria-hidden="true">▲</span>
</th>
```

---

### Issue: Action Buttons Not Descriptive

**Problem:**
```html
<button><i class="fa fa-edit"></i></button>
```

**Solution:**
```html
<button aria-label="Edit user John Doe">
    <i class="fa fa-edit" aria-hidden="true"></i>
    <span class="sr-only">Edit</span>
</button>
```

---

### Issue: Modal Focus Not Trapped

**Problem:**
```javascript
// Tab escapes modal
```

**Solution:**
```javascript
// Use ModalRenderer - focus trap automatic
$search->render('users-table', 'users', $fields);
```

---

### Issue: Loading State Not Announced

**Problem:**
```javascript
// Silent loading
```

**Solution:**
```html
<div role="status" aria-live="polite" class="sr-only">
    Loading table data...
</div>
```

---

## Resources

### WCAG Guidelines
- [WCAG 2.1 Level A](https://www.w3.org/WAI/WCAG21/quickref/?currentsidebar=%23col_customize&levels=a)
- [WCAG 2.1 Level AA](https://www.w3.org/WAI/WCAG21/quickref/?currentsidebar=%23col_customize&levels=aa)

### ARIA Specifications
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [ARIA Table Pattern](https://www.w3.org/WAI/ARIA/apg/patterns/table/)

### Testing Tools
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE](https://wave.webaim.org/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)

### Screen Readers
- [NVDA](https://www.nvaccess.org/)
- [JAWS](https://www.freedomscientific.com/products/software/jaws/)
- [VoiceOver](https://www.apple.com/accessibility/voiceover/)

---

## See Also

- [Objects API Reference](../api/OBJECTS.md)
- [Builder API Reference](../api/BUILDER.md)
- [Search System](../api/SEARCH.md)
- [Getting Started Guide](GETTING_STARTED.md)
