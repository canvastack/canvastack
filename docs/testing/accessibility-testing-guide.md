# Accessibility Testing Guide

## Overview

This guide covers accessibility testing for CanvaStack to ensure WCAG 2.1 Level AA compliance.

---

## 📋 WCAG 2.1 Level AA Requirements

### 1. Perceivable

#### 1.1 Text Alternatives
- ✅ All images have alt text
- ✅ Decorative images use empty alt=""
- ✅ Complex images have detailed descriptions

#### 1.2 Time-based Media
- ✅ Audio/video content has captions
- ✅ Audio descriptions provided

#### 1.3 Adaptable
- ✅ Content structure is semantic
- ✅ Reading order is logical
- ✅ Instructions don't rely on sensory characteristics

#### 1.4 Distinguishable
- ✅ Color contrast ratio ≥ 4.5:1 for normal text
- ✅ Color contrast ratio ≥ 3:1 for large text
- ✅ Text can be resized to 200%
- ✅ No images of text (except logos)

### 2. Operable

#### 2.1 Keyboard Accessible
- ✅ All functionality available via keyboard
- ✅ No keyboard traps
- ✅ Keyboard shortcuts documented

#### 2.2 Enough Time
- ✅ Time limits can be extended
- ✅ Auto-updating content can be paused

#### 2.3 Seizures
- ✅ No content flashes more than 3 times per second

#### 2.4 Navigable
- ✅ Skip to main content link
- ✅ Page titles are descriptive
- ✅ Focus order is logical
- ✅ Link purpose is clear
- ✅ Multiple ways to find pages
- ✅ Headings and labels are descriptive
- ✅ Focus indicator is visible

#### 2.5 Input Modalities
- ✅ Touch targets are at least 44x44px
- ✅ Gestures have alternatives

### 3. Understandable

#### 3.1 Readable
- ✅ Page language is declared
- ✅ Language changes are marked

#### 3.2 Predictable
- ✅ Focus doesn't cause unexpected changes
- ✅ Input doesn't cause unexpected changes
- ✅ Navigation is consistent
- ✅ Components are identified consistently

#### 3.3 Input Assistance
- ✅ Error messages are clear
- ✅ Labels and instructions provided
- ✅ Error suggestions provided
- ✅ Error prevention for critical actions

### 4. Robust

#### 4.1 Compatible
- ✅ Valid HTML
- ✅ Proper ARIA usage
- ✅ Status messages announced

---

## 🧪 Testing Tools

### Automated Testing

#### 1. axe DevTools (Browser Extension)
```bash
# Install Chrome extension
https://chrome.google.com/webstore/detail/axe-devtools-web-accessibility/lhdoppojpmngadmnindnejefpokejbdd

# Run automated scan
1. Open DevTools
2. Go to axe DevTools tab
3. Click "Scan ALL of my page"
4. Review issues
```

#### 2. WAVE (Browser Extension)
```bash
# Install Chrome extension
https://chrome.google.com/webstore/detail/wave-evaluation-tool/jbbplnpkjmmeebjpijfedlgcdilocofh

# Run scan
1. Click WAVE icon
2. Review errors, alerts, and features
```

#### 3. Lighthouse (Built into Chrome)
```bash
# Run Lighthouse audit
1. Open DevTools
2. Go to Lighthouse tab
3. Select "Accessibility" category
4. Click "Generate report"
```

#### 4. Pa11y (Command Line)
```bash
# Install
npm install -g pa11y

# Run test
pa11y http://localhost:8000

# Run with specific standard
pa11y --standard WCAG2AA http://localhost:8000
```

### Manual Testing

#### 1. Keyboard Navigation
```bash
# Test keyboard access
Tab         - Move forward through interactive elements
Shift+Tab   - Move backward
Enter       - Activate links and buttons
Space       - Activate buttons, check checkboxes
Arrow keys  - Navigate within components
Esc         - Close modals and dropdowns
```

#### 2. Screen Reader Testing

**NVDA (Windows - Free)**
```bash
# Download: https://www.nvaccess.org/download/

# Basic commands
NVDA+Space  - Toggle browse/focus mode
NVDA+T      - Read title
NVDA+Down   - Read next item
H           - Next heading
K           - Next link
B           - Next button
F           - Next form field
```

**JAWS (Windows - Commercial)**
```bash
# Basic commands
Insert+Down - Read next item
H           - Next heading
T           - Next table
F           - Next form field
```

**VoiceOver (macOS - Built-in)**
```bash
# Enable: System Preferences > Accessibility > VoiceOver

# Basic commands
VO+A        - Start reading
VO+Right    - Next item
VO+Left     - Previous item
VO+H        - Next heading
VO+Space    - Activate item
```

#### 3. Color Contrast Testing
```bash
# Use browser DevTools
1. Inspect element
2. Check computed color values
3. Use contrast checker: https://webaim.org/resources/contrastchecker/

# Minimum ratios
Normal text (< 18pt): 4.5:1
Large text (≥ 18pt or 14pt bold): 3:1
UI components: 3:1
```

#### 4. Zoom Testing
```bash
# Test at different zoom levels
100% - Default
200% - WCAG requirement
400% - Extreme case

# Check for:
- Text remains readable
- No horizontal scrolling
- No content overlap
- Functionality preserved
```

---

## 🎯 Testing Checklist

### Page Structure
- [ ] Page has valid HTML
- [ ] Page has one h1 heading
- [ ] Heading hierarchy is logical (h1 → h2 → h3)
- [ ] Page language is declared (lang attribute)
- [ ] Page title is descriptive

### Navigation
- [ ] Skip to main content link present
- [ ] Navigation landmarks present (nav, main, footer)
- [ ] Breadcrumbs present where appropriate
- [ ] Current page indicated in navigation

### Images
- [ ] All images have alt text
- [ ] Decorative images have empty alt=""
- [ ] Complex images have detailed descriptions
- [ ] No images of text (except logos)

### Forms
- [ ] All inputs have labels
- [ ] Labels are associated with inputs
- [ ] Required fields are indicated
- [ ] Error messages are clear and specific
- [ ] Errors are associated with fields (aria-describedby)
- [ ] Form can be completed with keyboard only

### Interactive Elements
- [ ] All buttons have accessible names
- [ ] All links have descriptive text
- [ ] Focus indicators are visible
- [ ] No keyboard traps
- [ ] Touch targets are at least 44x44px

### Color and Contrast
- [ ] Color contrast meets WCAG AA (4.5:1)
- [ ] Information not conveyed by color alone
- [ ] Text can be resized to 200%
- [ ] Dark mode has sufficient contrast

### Dynamic Content
- [ ] Modals have role="dialog"
- [ ] Modals trap focus
- [ ] Modals can be closed with Esc
- [ ] Dropdowns have aria-haspopup
- [ ] Loading states are announced
- [ ] Error messages are announced (aria-live)

### Tables
- [ ] Tables have proper structure (thead, tbody)
- [ ] Header cells use th
- [ ] Complex tables have scope attributes
- [ ] Table has caption or aria-label

### Multimedia
- [ ] Videos have captions
- [ ] Audio has transcripts
- [ ] Auto-playing content can be paused

---

## 🔧 Running Tests

### Automated Tests
```bash
# Run all accessibility tests
php artisan dusk tests/Browser/AccessibilityTest.php

# Run specific test
php artisan dusk --filter test_all_images_have_alt_text

# Run with coverage
php artisan dusk --coverage
```

### Manual Testing Workflow
```bash
1. Keyboard Navigation Test
   - Tab through entire page
   - Verify focus order
   - Test all interactive elements
   - Check for keyboard traps

2. Screen Reader Test
   - Enable screen reader
   - Navigate page with keyboard
   - Verify all content is announced
   - Check ARIA labels and descriptions

3. Color Contrast Test
   - Use browser DevTools
   - Check all text colors
   - Verify UI component colors
   - Test in dark mode

4. Zoom Test
   - Zoom to 200%
   - Check for horizontal scrolling
   - Verify text remains readable
   - Test functionality

5. Mobile Test
   - Test on real devices
   - Check touch target sizes
   - Verify gestures work
   - Test with screen reader
```

---

## 🐛 Common Issues and Fixes

### Issue 1: Missing Alt Text
```html
<!-- ❌ Bad -->
<img src="logo.png">

<!-- ✅ Good -->
<img src="logo.png" alt="Company Logo">

<!-- ✅ Decorative -->
<img src="decoration.png" alt="">
```

### Issue 2: Missing Form Labels
```html
<!-- ❌ Bad -->
<input type="text" name="email">

<!-- ✅ Good -->
<label for="email">Email</label>
<input type="text" id="email" name="email">

<!-- ✅ Alternative -->
<input type="text" name="email" aria-label="Email">
```

### Issue 3: Poor Color Contrast
```css
/* ❌ Bad - 2.5:1 contrast */
color: #999;
background: #fff;

/* ✅ Good - 4.6:1 contrast */
color: #666;
background: #fff;
```

### Issue 4: Keyboard Trap
```javascript
// ❌ Bad - Focus trapped in modal
modal.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
        e.preventDefault();
    }
});

// ✅ Good - Focus cycles within modal
modal.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
        const focusable = modal.querySelectorAll('button, a, input');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }
});
```

### Issue 5: Missing ARIA Attributes
```html
<!-- ❌ Bad -->
<div onclick="openMenu()">Menu</div>

<!-- ✅ Good -->
<button aria-haspopup="true" aria-expanded="false" onclick="openMenu()">
    Menu
</button>
```

---

## 📊 Accessibility Report Template

```markdown
# Accessibility Test Report

**Date**: YYYY-MM-DD
**Tester**: [Name]
**Page**: [URL]
**WCAG Level**: AA

## Summary
- Total Issues: X
- Critical: X
- Serious: X
- Moderate: X
- Minor: X

## Critical Issues
1. [Issue description]
   - Location: [Element/Page]
   - WCAG Criterion: [X.X.X]
   - Fix: [Solution]

## Serious Issues
[Same format as above]

## Moderate Issues
[Same format as above]

## Minor Issues
[Same format as above]

## Recommendations
1. [Recommendation]
2. [Recommendation]

## Next Steps
1. [Action item]
2. [Action item]
```

---

## 🎓 Resources

### WCAG Guidelines
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [Understanding WCAG 2.1](https://www.w3.org/WAI/WCAG21/Understanding/)
- [How to Meet WCAG](https://www.w3.org/WAI/WCAG21/quickref/)

### Testing Tools
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE](https://wave.webaim.org/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)
- [Pa11y](https://pa11y.org/)

### Screen Readers
- [NVDA](https://www.nvaccess.org/)
- [JAWS](https://www.freedomscientific.com/products/software/jaws/)
- [VoiceOver](https://www.apple.com/accessibility/voiceover/)

### Learning Resources
- [WebAIM](https://webaim.org/)
- [A11y Project](https://www.a11yproject.com/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)

---

**Last Updated**: 2026-02-27
**Version**: 1.0.0
**Status**: Published
