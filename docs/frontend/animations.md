# GSAP Animations

Professional-grade animations for CanvaStack using GSAP (GreenSock Animation Platform).

## 📦 Location

- **Animation Module**: `resources/js/animations/gsap-animations.js`
- **Main Integration**: `resources/js/canvastack.js`
- **Example Components**: `resources/views/components/ui/gsap-examples.blade.php`

## 🎯 Features

- Page transition animations (fade, slide, scale)
- Modal enter/exit animations with backdrop
- Sidebar collapse/expand animations
- Loading state animations (spinner, pulse, skeleton, progress bar)
- Utility animations (bounce, shake, fade, slide)
- Card hover animations
- Notification animations
- Timeline-based complex animations

## 📖 Basic Usage

### Page Transitions

Add classes to elements for automatic animations on page load:

```html
<!-- Fade in with upward motion -->
<div class="fade-in">Content</div>

<!-- Fade in from left -->
<div class="fade-in-left">Content</div>

<!-- Fade in from right -->
<div class="fade-in-right">Content</div>

<!-- Scale in with bounce -->
<div class="scale-in">Content</div>

<!-- Slide up -->
<div class="slide-up">Content</div>
```

### Modal Animations

```javascript
// Modal enter animation
const modal = document.getElementById('my-modal');
const backdrop = document.getElementById('modal-backdrop');

animations.modalEnter(modal, backdrop);

// Modal exit animation
animations.modalExit(modal, backdrop, () => {
    console.log('Modal closed');
});

// Modal shake (for validation errors)
animations.modalShake(modal);
```

### Sidebar Animations

```javascript
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');

// Collapse sidebar
animations.sidebarCollapse(sidebar, mainContent, () => {
    console.log('Sidebar collapsed');
});

// Expand sidebar
animations.sidebarExpand(sidebar, mainContent, () => {
    console.log('Sidebar expanded');
});

// Mobile sidebar slide in
const overlay = document.getElementById('sidebar-overlay');
animations.sidebarSlideIn(sidebar, overlay);

// Mobile sidebar slide out
animations.sidebarSlideOut(sidebar, overlay, () => {
    console.log('Sidebar closed');
});
```

### Loading States

```javascript
// Spinner animation
const spinner = document.querySelector('.spinner');
const spinnerAnim = animations.spinnerAnimation(spinner);

// Pulse animation
const pulse = document.querySelector('.pulse');
const pulseAnim = animations.pulseAnimation(pulse);

// Skeleton loading
const skeleton = document.querySelector('.skeleton');
const skeletonAnim = animations.skeletonAnimation(skeleton);

// Progress bar
const progressBar = document.getElementById('progress-bar');
animations.progressBarAnimation(progressBar, 75); // 75%

// Dots loading
const dotsAnim = animations.dotsLoadingAnimation('#dots-container');

// Fade loading (content replacement)
const content = document.getElementById('content');
animations.fadeLoading(content, () => {
    content.innerHTML = 'New content';
});

// Stop animations
spinnerAnim.kill();
pulseAnim.kill();
dotsAnim.kill();
```

## 🔧 Animation Functions

### Page Transitions

| Function | Parameters | Description |
|----------|------------|-------------|
| `initPageTransitions()` | None | Initialize all page transition animations |
| `pageTransitionOut(callback)` | callback: Function | Fade out page before navigation |
| `pageTransitionIn()` | None | Fade in page on load |

### Modal Animations

| Function | Parameters | Description |
|----------|------------|-------------|
| `modalEnter(modal, backdrop)` | modal: HTMLElement, backdrop: HTMLElement | Animate modal entrance |
| `modalExit(modal, backdrop, callback)` | modal: HTMLElement, backdrop: HTMLElement, callback: Function | Animate modal exit |
| `modalShake(modal)` | modal: HTMLElement | Shake modal (for errors) |

### Sidebar Animations

| Function | Parameters | Description |
|----------|------------|-------------|
| `sidebarCollapse(sidebar, mainContent, callback)` | sidebar: HTMLElement, mainContent: HTMLElement, callback: Function | Collapse sidebar animation |
| `sidebarExpand(sidebar, mainContent, callback)` | sidebar: HTMLElement, mainContent: HTMLElement, callback: Function | Expand sidebar animation |
| `sidebarSlideIn(sidebar, overlay)` | sidebar: HTMLElement, overlay: HTMLElement | Mobile sidebar slide in |
| `sidebarSlideOut(sidebar, overlay, callback)` | sidebar: HTMLElement, overlay: HTMLElement, callback: Function | Mobile sidebar slide out |

### Loading States

| Function | Parameters | Description |
|----------|------------|-------------|
| `spinnerAnimation(element)` | element: HTMLElement | Rotate spinner continuously |
| `pulseAnimation(element)` | element: HTMLElement | Pulse animation for loading |
| `skeletonAnimation(element)` | element: HTMLElement | Skeleton loading animation |
| `progressBarAnimation(element, progress)` | element: HTMLElement, progress: Number (0-100) | Animate progress bar |
| `dotsLoadingAnimation(containerSelector)` | containerSelector: String | Animate loading dots |
| `fadeLoading(element, callback)` | element: HTMLElement, callback: Function | Fade out, replace content, fade in |

### Utility Animations

| Function | Parameters | Description |
|----------|------------|-------------|
| `bounceAnimation(element)` | element: HTMLElement | Bounce element |
| `shakeAnimation(element)` | element: HTMLElement | Shake element (for errors) |
| `fadeIn(element, duration)` | element: HTMLElement, duration: Number (default: 0.3) | Fade in element |
| `fadeOut(element, duration)` | element: HTMLElement, duration: Number (default: 0.3) | Fade out element |
| `slideIn(element, direction)` | element: HTMLElement, direction: String ('up', 'down', 'left', 'right') | Slide in element |
| `scaleAnimation(element, scale)` | element: HTMLElement, scale: Number (default: 1.05) | Scale element |

### Card Animations

| Function | Parameters | Description |
|----------|------------|-------------|
| `cardHoverIn(card)` | card: HTMLElement | Card hover in animation |
| `cardHoverOut(card)` | card: HTMLElement | Card hover out animation |

### Notification Animations

| Function | Parameters | Description |
|----------|------------|-------------|
| `notificationSlideIn(notification, position)` | notification: HTMLElement, position: String ('top-right', 'top-left', 'bottom-right', 'bottom-left') | Slide in notification |
| `notificationSlideOut(notification, callback)` | notification: HTMLElement, callback: Function | Slide out notification |

## 📝 Examples

### Example 1: Animated Modal

```html
<div x-data="{ open: false }">
    <button @click="open = true">Open Modal</button>
    
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div 
            id="modal-backdrop"
            class="absolute inset-0 bg-black/50"
            @click="open = false"
        ></div>
        
        <!-- Modal -->
        <div 
            id="modal-content"
            class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 max-w-md w-full"
        >
            <h3 class="text-lg font-bold mb-4">Modal Title</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Modal content</p>
            <button @click="open = false" class="px-4 py-2 gradient-bg text-white rounded-xl">
                Close
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('modal', () => ({
        open: false,
        show() {
            this.open = true;
            this.$nextTick(() => {
                const modal = document.getElementById('modal-content');
                const backdrop = document.getElementById('modal-backdrop');
                animations.modalEnter(modal, backdrop);
            });
        },
        hide() {
            const modal = document.getElementById('modal-content');
            const backdrop = document.getElementById('modal-backdrop');
            animations.modalExit(modal, backdrop, () => {
                this.open = false;
            });
        }
    }));
});
</script>
```

### Example 2: Loading Spinner

```html
<div class="flex justify-center">
    <div 
        id="spinner"
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full"
    ></div>
</div>

<script>
const spinner = document.getElementById('spinner');
const spinnerAnim = animations.spinnerAnimation(spinner);

// Stop spinner after 3 seconds
setTimeout(() => {
    spinnerAnim.kill();
}, 3000);
</script>
```

### Example 3: Progress Bar

```html
<div class="bg-gray-200 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
    <div id="progress" class="h-full gradient-bg" style="width: 0%"></div>
</div>

<script>
const progress = document.getElementById('progress');

// Simulate progress
let currentProgress = 0;
const interval = setInterval(() => {
    currentProgress += 10;
    animations.progressBarAnimation(progress, currentProgress);
    
    if (currentProgress >= 100) {
        clearInterval(interval);
    }
}, 500);
</script>
```

### Example 4: Card Hover Animation

```html
<div 
    class="card bg-white dark:bg-gray-900 rounded-2xl border p-6 shadow-sm cursor-pointer"
    onmouseenter="animations.cardHoverIn(this)"
    onmouseleave="animations.cardHoverOut(this)"
>
    <h3 class="text-lg font-semibold mb-2">Hover Card</h3>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Hover over this card to see the animation.
    </p>
</div>
```

### Example 5: Notification

```html
<div 
    id="notification"
    class="fixed top-4 right-4 bg-white dark:bg-gray-900 rounded-xl border p-4 shadow-lg"
    style="display: none;"
>
    <p class="text-sm font-medium">Notification message</p>
</div>

<script>
function showNotification() {
    const notification = document.getElementById('notification');
    notification.style.display = 'block';
    
    animations.notificationSlideIn(notification, 'top-right');
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        animations.notificationSlideOut(notification, () => {
            notification.style.display = 'none';
        });
    }, 3000);
}
</script>
```

### Example 6: Fade Loading Content

```html
<div id="content" class="p-6">
    <p>Original content</p>
</div>

<button onclick="loadNewContent()">Load New Content</button>

<script>
function loadNewContent() {
    const content = document.getElementById('content');
    
    animations.fadeLoading(content, () => {
        // Simulate API call
        setTimeout(() => {
            content.innerHTML = '<p>New content loaded!</p>';
        }, 500);
    });
}
</script>
```

## 🎮 Programmatic Control

### Creating Custom Timelines

```javascript
import { gsap } from 'gsap';

// Create a timeline
const timeline = gsap.timeline({
    onComplete: () => console.log('Animation complete')
});

// Add animations to timeline
timeline
    .from('.element1', { opacity: 0, y: 20, duration: 0.5 })
    .from('.element2', { opacity: 0, y: 20, duration: 0.5 }, '-=0.3') // Overlap
    .from('.element3', { opacity: 0, y: 20, duration: 0.5 }, '-=0.3');
```

### Controlling Animations

```javascript
const anim = animations.spinnerAnimation(element);

// Pause animation
anim.pause();

// Resume animation
anim.resume();

// Restart animation
anim.restart();

// Kill animation
anim.kill();

// Reverse animation
anim.reverse();
```

### Animation Events

```javascript
const anim = gsap.to(element, {
    x: 100,
    duration: 1,
    onStart: () => console.log('Animation started'),
    onUpdate: () => console.log('Animation updating'),
    onComplete: () => console.log('Animation completed'),
    onReverseComplete: () => console.log('Reverse completed')
});
```

## 🔍 Implementation Details

### GSAP Configuration

CanvaStack uses GSAP 3.x with the following configuration:

```javascript
import { gsap } from 'gsap';

// GSAP is available globally
window.gsap = gsap;

// Animations module is available globally
window.animations = animations;
```

### Easing Functions

Common easing functions used:

- `power2.out` - Smooth deceleration
- `power2.in` - Smooth acceleration
- `power2.inOut` - Smooth acceleration and deceleration
- `back.out(1.5)` - Overshoot effect
- `linear` - Constant speed

### Performance Considerations

1. **Use `will-change` CSS property** for animated elements:
   ```css
   .animated-element {
       will-change: transform, opacity;
   }
   ```

2. **Kill animations when not needed**:
   ```javascript
   const anim = animations.spinnerAnimation(element);
   // Later...
   anim.kill();
   ```

3. **Use GSAP's `set()` for instant changes**:
   ```javascript
   gsap.set(element, { opacity: 0 });
   ```

4. **Batch similar animations**:
   ```javascript
   gsap.to('.cards', {
       y: -4,
       duration: 0.3,
       stagger: 0.1
   });
   ```

## 🎯 Accessibility

### Respecting User Preferences

```javascript
// Check if user prefers reduced motion
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (prefersReducedMotion) {
    // Disable or reduce animations
    gsap.globalTimeline.timeScale(0); // Instant animations
} else {
    // Normal animations
    animations.initPageTransitions();
}
```

### Focus Management

```javascript
// Ensure focus is managed during animations
animations.modalEnter(modal, backdrop);
modal.querySelector('button').focus();
```

## 💡 Tips & Best Practices

1. **Use timelines for complex animations** - Easier to control and sequence
2. **Kill animations when components unmount** - Prevent memory leaks
3. **Use stagger for list animations** - Creates smooth cascading effect
4. **Combine with Alpine.js** - Use Alpine's reactivity with GSAP animations
5. **Test on different devices** - Ensure animations perform well on mobile
6. **Respect user preferences** - Check for `prefers-reduced-motion`
7. **Use appropriate durations** - 0.2-0.5s for UI interactions, 0.5-1s for page transitions
8. **Add easing for natural feel** - Avoid linear animations except for continuous loops

## 🎭 Common Patterns

### Pattern 1: Staggered List Animation

```javascript
gsap.from('.list-item', {
    opacity: 0,
    y: 20,
    duration: 0.5,
    stagger: 0.1,
    ease: 'power2.out'
});
```

### Pattern 2: Sequential Timeline

```javascript
const timeline = gsap.timeline();

timeline
    .from('.header', { opacity: 0, y: -20, duration: 0.5 })
    .from('.content', { opacity: 0, y: 20, duration: 0.5 }, '-=0.3')
    .from('.footer', { opacity: 0, y: 20, duration: 0.5 }, '-=0.3');
```

### Pattern 3: Hover Animation

```javascript
element.addEventListener('mouseenter', () => {
    gsap.to(element, { scale: 1.05, duration: 0.3 });
});

element.addEventListener('mouseleave', () => {
    gsap.to(element, { scale: 1, duration: 0.3 });
});
```

### Pattern 4: Infinite Loop

```javascript
gsap.to(element, {
    rotation: 360,
    duration: 2,
    repeat: -1,
    ease: 'linear'
});
```

## 🔗 Related Components

- [Alpine.js Integration](alpine-js.md) - Reactive JavaScript framework
- [Modal Component](components/modal.md) - Modal with animations
- [Sidebar Toggle](components/sidebar-toggle.md) - Sidebar with animations
- [Dark Mode Toggle](components/dark-mode-toggle.md) - Theme switching

## 📚 Resources

- [GSAP Documentation](https://greensock.com/docs/)
- [GSAP Easing Visualizer](https://greensock.com/ease-visualizer/)
- [GSAP Cheat Sheet](https://greensock.com/cheatsheet/)
- [GSAP Forum](https://greensock.com/forums/)

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0  
**Status**: Published
