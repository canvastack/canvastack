# Accessibility Guidelines

## Overview

The CanvaStack Form Builder is designed to be fully accessible and compliant with WCAG 2.1 Level A standards. This document explains the accessibility features and best practices for creating accessible forms.

## Table of Contents

1. [WCAG 2.1 Compliance](#wcag-21-compliance)
2. [ARIA Attributes](#aria-attributes)
3. [Label Associations](#label-associations)
4. [Keyboard Navigation](#keyboard-navigation)
5. [Screen Reader Support](#screen-reader-support)
6. [Color and Contrast](#color-and-contrast)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)

---

## WCAG 2.1 Compliance

### Level A Requirements Met

The Form Builder meets all WCAG 2.1 Level A requirements:

✅ **1.1.1 Non-text Content** - All form controls have text alternatives
✅ **1.3.1 Info and Relationships** - Proper label-input associations
✅ **1.3.2 Meaningful Sequence** - Logical tab order
✅ **1.3.3 Sensory Characteristics** - Instructions don't rely solely on visual cues
✅ **2.1.1 Keyboard** - All functionality available via keyboard
✅ **2.1.2 No Keyboard Trap** - Keyboard focus can move away from components
✅ **2.4.1 Bypass Blocks** - Skip navigation mechanisms
✅ **2.4.2 Page Titled** - Forms have descriptive titles
✅ **2.4.3 Focus Order** - Logical focus order
✅ **2.4.4 Link Purpose** - Link purposes clear from text
✅ **3.1.1 Language of Page** - Language specified
✅ **3.2.1 On Focus** - No context changes on focus
✅ **3.2.2 On Input** - No unexpected context changes
✅ **3.3.1 Error Identification** - Errors identified in text
✅ **3.3.2 Labels or Instructions** - Labels provided for inputs
✅ **4.1.1 Parsing** - Valid HTML markup
✅ **4.1.2 Name, Role, Value** - ARIA attributes for custom controls

---

## ARIA Attributes

### Automatic ARIA Support

The Form Builder automatically adds appropriate ARIA attributes based on field state:

#### Required Fields

```php
$form->text('username', null, ['required' => true], true);
```

**Generated HTML:**
```html
<input type="text" name="username" required aria-required="true" />
```

**ARIA Attributes:**
- `aria-required="true"` - Indicates mandatory field
- Visual asterisk (*) with `aria-label="required field"`

---


#### Validation Errors

```php
// When validation fails
$form->text('email', null, [], true);
```

**Generated HTML (with error):**
```html
<input type="email" name="email" 
       aria-invalid="true" 
       aria-describedby="email-error" />
<span id="email-error" role="alert">Please enter a valid email address</span>
```

**ARIA Attributes:**
- `aria-invalid="true"` - Indicates validation error
- `aria-describedby` - Associates error message with input
- `role="alert"` - Announces error to screen readers

#### Checkboxes and Radio Buttons

```php
$form->checkbox('terms', [1 => 'I agree'], [1], [], true);
```

**Generated HTML:**
```html
<input type="checkbox" name="terms[1]" value="1" checked
       aria-checked="true"
       aria-label="I agree" />
```

**ARIA Attributes:**
- `aria-checked="true|false"` - Indicates selection state
- `aria-label` - Provides accessible name
- `aria-disabled="true"` - For disabled checkboxes

#### Tab Navigation

```php
$form->openTab('Personal Info');
// ... fields ...
$form->closeTab();
```

**Generated HTML:**
```html
<ul class="nav nav-tabs" role="tablist">
    <li role="presentation">
        <a href="#personal-info" role="tab" 
           aria-selected="true" 
           aria-controls="personal-info">
            Personal Info
        </a>
    </li>
</ul>
<div class="tab-content">
    <div id="personal-info" role="tabpanel" 
         aria-labelledby="personal-info-tab" 
         class="tab-pane active">
        <!-- Tab content -->
    </div>
</div>
```

**ARIA Attributes:**
- `role="tablist"` - Tab container
- `role="tab"` - Individual tabs
- `role="tabpanel"` - Tab content panels
- `aria-selected` - Active tab indicator
- `aria-controls` - Tab-panel association
- `aria-labelledby` - Panel-tab association

#### File Inputs

```php
$form->file('avatar', ['imagepreview'], true);
```

**Generated HTML:**
```html
<input type="file" name="avatar" 
       aria-label="Avatar image upload"
       aria-describedby="avatar-requirements" />
<span id="avatar-requirements">
    Allowed types: JPG, PNG, GIF. Maximum size: 2MB.
</span>
```

**ARIA Attributes:**
- `aria-label` - Describes file input purpose
- `aria-describedby` - Associates requirements text
- `aria-invalid` - For validation errors

#### Select Dropdowns

```php
$form->selectbox('country', $countries, false, [], true);
```

**Generated HTML:**
```html
<select name="country" 
        aria-required="true"
        aria-describedby="country-help">
    <option value="">Select a country</option>
    <option value="us">United States</option>
</select>
<span id="country-help">Choose your country of residence</span>
```

**ARIA Attributes:**
- `aria-required` - For required selects
- `aria-invalid` - For validation errors
- `aria-describedby` - Associates help text

---

## Label Associations

### Visible Label Pattern (Default)

```php
$form->text('username', null, [], true);
```

**Generated HTML:**
```html
<label for="username">Username</label>
<input type="text" id="username" name="username" />
```

**Accessibility:**
- Label `for` attribute matches input `id`
- Screen readers announce label when input receives focus
- Clicking label focuses input

### Hidden Label Pattern

```php
$form->text('username', null, [], false);
```

**Generated HTML:**
```html
<input type="text" name="username" 
       aria-label="Username" />
```

**Accessibility:**
- No visible label rendered
- `aria-label` provides accessible name
- Screen readers announce aria-label

### Custom Label Pattern

```php
$form->text('username', null, [], 'Your Username');
```

**Generated HTML:**
```html
<label for="username">Your Username</label>
<input type="text" id="username" name="username" />
```

### Required Field Pattern

```php
$form->text('username', null, ['required' => true], true);
```

**Generated HTML:**
```html
<label for="username">
    Username 
    <span class="required" aria-label="required field">*</span>
</label>
<input type="text" id="username" name="username" 
       required aria-required="true" />
```

**Accessibility:**
- Visual asterisk (*) indicates required
- `aria-label="required field"` on asterisk
- `aria-required="true"` on input
- Screen readers announce "Username, required"

---

## Keyboard Navigation

### Tab Order

All form elements follow logical tab order:

1. Form fields (top to bottom, left to right)
2. Buttons (submit, reset, cancel)
3. Links and other interactive elements

**Example:**
```php
$form->text('name');      // Tab index 1
$form->email('email');    // Tab index 2
$form->password('pass');  // Tab index 3
$form->close();           // Submit button: Tab index 4
```

### Keyboard Shortcuts

**Text Inputs:**
- `Tab` - Move to next field
- `Shift+Tab` - Move to previous field
- `Enter` - Submit form (when focused on input)

**Checkboxes/Radio:**
- `Space` - Toggle selection
- `Tab` - Move to next field
- `Arrow keys` - Navigate between radio buttons in group

**Select Dropdowns:**
- `Space` or `Enter` - Open dropdown
- `Arrow keys` - Navigate options
- `Escape` - Close dropdown
- `Type` - Jump to option starting with letter

**Date Pickers:**
- `Space` or `Enter` - Open calendar
- `Arrow keys` - Navigate dates
- `Page Up/Down` - Change month
- `Home/End` - First/last day of month
- `Escape` - Close calendar

**Tab Navigation:**
- `Tab` - Move between tabs
- `Arrow keys` - Navigate tabs
- `Enter` or `Space` - Activate tab
- `Home` - First tab
- `End` - Last tab

---

## Screen Reader Support

### Tested Screen Readers

✅ **NVDA** (Windows) - Fully supported
✅ **JAWS** (Windows) - Fully supported
✅ **VoiceOver** (macOS/iOS) - Fully supported
✅ **TalkBack** (Android) - Fully supported

### Screen Reader Announcements

#### Form Fields

```
"Username, edit text, required"
"Email, edit text, invalid entry, Please enter a valid email"
"Password, password edit text"
"Country, combo box, United States, 1 of 195"
```

#### Checkboxes

```
"I agree to terms, checkbox, checked"
"Enable notifications, checkbox, not checked"
```

#### Radio Buttons

```
"Gender, radio button group"
"Male, radio button, 1 of 3, checked"
"Female, radio button, 2 of 3, not checked"
```

#### Tabs

```
"Personal Info, tab, 1 of 3, selected"
"Address Details, tab, 2 of 3"
```

#### Validation Errors

```
"Email, edit text, invalid entry"
"Error: Please enter a valid email address"
```

### Live Regions

Alert messages use ARIA live regions:

```php
// Success message
<div role="alert" aria-live="polite">
    Profile updated successfully
</div>

// Error message
<div role="alert" aria-live="assertive">
    Please correct the errors below
</div>
```

**ARIA Live Values:**
- `polite` - Waits for screen reader to finish (success, info)
- `assertive` - Interrupts immediately (errors, warnings)

---

## Color and Contrast

### Contrast Ratios

All form elements meet WCAG AA contrast requirements:

- **Normal text:** 4.5:1 minimum
- **Large text:** 3:1 minimum
- **UI components:** 3:1 minimum

### Color Independence

Form validation doesn't rely solely on color:

```html
<!-- ❌ Bad - color only -->
<input style="border-color: red;" />

<!-- ✅ Good - color + icon + text -->
<input style="border-color: red;" aria-invalid="true" />
<span class="error-icon">⚠️</span>
<span class="error-text">Invalid email</span>
```

### Focus Indicators

All interactive elements have visible focus indicators:

```css
input:focus, select:focus, button:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}
```

---

## Error Handling

### Error Identification

Errors are identified in multiple ways:

1. **Visual indicators** - Red border, error icon
2. **Text messages** - Clear error descriptions
3. **ARIA attributes** - `aria-invalid`, `aria-describedby`
4. **Screen reader announcements** - Live regions

### Error Message Pattern

```php
// When validation fails
$form->text('email', null, [], true);
```

**Generated HTML:**
```html
<div class="form-group has-error">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" 
           class="form-control error"
           aria-invalid="true"
           aria-describedby="email-error" />
    <span id="email-error" class="help-block error-message" role="alert">
        <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
        Please enter a valid email address
    </span>
</div>
```

**Accessibility Features:**
- Visual error styling (red border, icon)
- Error message associated via `aria-describedby`
- `role="alert"` announces error to screen readers
- Icon has `aria-hidden="true"` (decorative)

### Error Summary

For forms with multiple errors:

```html
<div role="alert" aria-live="assertive" class="alert alert-danger">
    <h4>Please correct the following errors:</h4>
    <ul>
        <li><a href="#username">Username is required</a></li>
        <li><a href="#email">Email format is invalid</a></li>
        <li><a href="#password">Password must be at least 8 characters</a></li>
    </ul>
</div>
```

**Accessibility Features:**
- Error summary at top of form
- Links to specific error fields
- `role="alert"` for immediate announcement
- Keyboard accessible

---

## Best Practices

### 1. Always Provide Labels

```php
// ✅ Good
$form->text('username', null, [], true);

// ❌ Bad - no label
$form->text('username', null, [], false);
// Only acceptable if aria-label is provided
```

### 2. Use Descriptive Labels

```php
// ✅ Good
$form->text('email', null, [], 'Email Address');

// ❌ Bad - vague label
$form->text('email', null, [], 'Input');
```

### 3. Group Related Fields

```php
// ✅ Good - use fieldsets
<fieldset>
    <legend>Personal Information</legend>
    <?php $form->text('first_name'); ?>
    <?php $form->text('last_name'); ?>
</fieldset>

// Or use tabs
$form->openTab('Personal Information');
$form->text('first_name');
$form->text('last_name');
$form->closeTab();
```

### 4. Provide Help Text

```php
// ✅ Good
$form->text('username', null, [
    'help' => 'Username must be 3-20 characters, letters and numbers only'
], true);
```

### 5. Use Appropriate Input Types

```php
// ✅ Good
$form->email('email');    // type="email"
$form->number('age');     // type="number"
$form->date('birth_date'); // Date picker

// ❌ Bad
$form->text('email');     // No validation
$form->text('age');       // No numeric keyboard
```

### 6. Indicate Required Fields

```php
// ✅ Good
$form->setValidations(['username' => 'required']);
$form->text('username');

// ❌ Bad - no indication
$form->text('username');
```

### 7. Provide Clear Error Messages

```php
// ✅ Good
'email.required' => 'Please enter your email address'
'email.email' => 'Please enter a valid email address (e.g., user@example.com)'

// ❌ Bad
'email.required' => 'Required'
'email.email' => 'Invalid'
```

### 8. Test with Keyboard Only

Try navigating your form using only keyboard:
- Can you reach all fields?
- Can you submit the form?
- Are focus indicators visible?
- Is tab order logical?

### 9. Test with Screen Reader

Test your form with a screen reader:
- Are labels announced correctly?
- Are errors announced?
- Is required status announced?
- Are instructions clear?

### 10. Validate Accessibility

Use automated tools:
- **axe DevTools** - Browser extension
- **WAVE** - Web accessibility evaluation tool
- **Lighthouse** - Chrome DevTools audit

---

## Accessibility Checklist

Before deploying:

- [ ] All inputs have associated labels
- [ ] Required fields are indicated (visual + ARIA)
- [ ] Error messages are clear and associated with fields
- [ ] Keyboard navigation works for all elements
- [ ] Focus indicators are visible
- [ ] Color is not the only means of conveying information
- [ ] Contrast ratios meet WCAG AA standards
- [ ] Form can be completed using keyboard only
- [ ] Screen reader announces all important information
- [ ] Tab order is logical
- [ ] Help text is provided for complex fields
- [ ] Validation errors are announced to screen readers
- [ ] Automated accessibility tests pass (axe, WAVE)

---

## Resources

### WCAG Guidelines
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [Understanding WCAG 2.1](https://www.w3.org/WAI/WCAG21/Understanding/)

### ARIA Specifications
- [WAI-ARIA 1.2](https://www.w3.org/TR/wai-aria-1.2/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)

### Testing Tools
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE](https://wave.webaim.org/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [NVDA Screen Reader](https://www.nvaccess.org/)

---

*For security guidelines, see [SECURITY.md](SECURITY.md)*
*For API reference, see [API_REFERENCE.md](API_REFERENCE.md)*
