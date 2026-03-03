# Theme Examples

A collection of example custom themes demonstrating various use cases and design patterns.

## 📦 Location

- **Example Themes**: `resources/themes/`
- **Theme Documentation**: Individual README.md files in each theme directory

## 🎯 Overview

CanvaStack includes several example themes to help you understand theme development and provide starting points for your own custom themes.

## 📖 Included Example Themes

### 1. Ocean Theme

**Path**: `resources/themes/ocean/`

A refreshing ocean-inspired theme with blue and teal gradients.

**Key Features**:
- Ocean-inspired color palette
- Blue and teal gradients
- Perfect for SaaS and tech applications

**Color Palette**:
```json
{
  "primary": "#0ea5e9",
  "secondary": "#06b6d4",
  "accent": "#14b8a6"
}
```

**Use Cases**:
- SaaS applications
- Tech startups
- Data dashboards
- Analytics platforms

**Activation**:
```php
theme()->setActive('ocean');
```

---

### 2. Sunset Theme

**Path**: `resources/themes/sunset/`

A warm and vibrant theme inspired by sunset colors with orange and pink gradients.

**Key Features**:
- Warm color palette
- Orange, pink, and purple gradients
- Vibrant and energetic feel

**Color Palette**:
```json
{
  "primary": "#f97316",
  "secondary": "#ec4899",
  "accent": "#a855f7"
}
```

**Use Cases**:
- Creative agencies
- Photography portfolios
- Food & beverage businesses
- Lifestyle brands

**Activation**:
```php
theme()->setActive('sunset');
```

---

### 3. Forest Theme

**Path**: `resources/themes/forest/`

A natural and calming theme inspired by forest greens.

**Key Features**:
- Nature-inspired color palette
- Green and teal gradients
- Calming and eco-friendly

**Color Palette**:
```json
{
  "primary": "#10b981",
  "secondary": "#059669",
  "accent": "#14b8a6"
}
```

**Use Cases**:
- Environmental organizations
- Eco-friendly businesses
- Health & wellness
- Sustainability platforms

**Activation**:
```php
theme()->setActive('forest');
```

---

### 4. Midnight Theme

**Path**: `resources/themes/midnight/`

A sleek dark theme with deep blues and purples, perfect for night owls.

**Key Features**:
- Dark-first design
- Deep blue and purple gradients
- Reduced eye strain
- Professional appearance

**Color Palette**:
```json
{
  "dark_mode": {
    "default": "dark",
    "colors": {
      "primary": "#818cf8",
      "background": "#020617",
      "surface": "#0f172a"
    }
  }
}
```

**Use Cases**:
- Developer tools
- Code editors
- Admin dashboards
- Night-time usage

**Activation**:
```php
theme()->setActive('midnight');
```

---

### 5. Corporate Theme

**Path**: `resources/themes/corporate/`

A professional corporate theme with neutral colors and subtle accents.

**Key Features**:
- Professional appearance
- Neutral color palette
- Business-focused design
- Conservative styling

**Color Palette**:
```json
{
  "primary": "#2563eb",
  "secondary": "#475569",
  "accent": "#0ea5e9"
}
```

**Use Cases**:
- Enterprise applications
- Financial services
- B2B platforms
- Corporate intranets

**Activation**:
```php
theme()->setActive('corporate');
```

---

## 🎨 Theme Comparison

| Theme | Primary Color | Style | Best For |
|-------|--------------|-------|----------|
| Ocean | Blue (#0ea5e9) | Modern, Tech | SaaS, Dashboards |
| Sunset | Orange (#f97316) | Warm, Vibrant | Creative, Lifestyle |
| Forest | Green (#10b981) | Natural, Calm | Eco, Health |
| Midnight | Indigo (#818cf8) | Dark, Professional | Developer Tools |
| Corporate | Blue (#2563eb) | Neutral, Business | Enterprise Apps |

## 📝 Creating Custom Themes

### Example 1: Brand-Specific Theme

Create a theme matching your brand colors:

```json
{
  "name": "my-brand",
  "display_name": "My Brand",
  "version": "1.0.0",
  "author": "Your Company",
  "description": "Official brand theme",
  
  "colors": {
    "primary": "#FF6B00",
    "secondary": "#00A3FF",
    "accent": "#FFD700"
  },
  
  "typography": {
    "font_family": "Montserrat"
  }
}
```

### Example 2: Seasonal Theme

Create a theme for specific seasons:

```json
{
  "name": "winter",
  "display_name": "Winter",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "Cool winter theme",
  
  "colors": {
    "primary": "#60a5fa",
    "secondary": "#93c5fd",
    "accent": "#dbeafe"
  },
  
  "gradients": {
    "hero": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
  }
}
```

### Example 3: High Contrast Theme

Create an accessible high-contrast theme:

```json
{
  "name": "high-contrast",
  "display_name": "High Contrast",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "High contrast for accessibility",
  
  "colors": {
    "primary": "#000000",
    "secondary": "#FFFFFF",
    "background": "#FFFFFF",
    "text": "#000000"
  },
  
  "dark_mode": {
    "enabled": true,
    "colors": {
      "primary": "#FFFFFF",
      "background": "#000000",
      "text": "#FFFFFF"
    }
  }
}
```

### Example 4: Child Theme

Extend an existing theme:

```json
{
  "name": "ocean-dark",
  "display_name": "Ocean Dark",
  "parent": "ocean",
  "version": "1.0.0",
  "author": "Your Name",
  "description": "Dark variant of Ocean theme",
  
  "dark_mode": {
    "default": "dark",
    "colors": {
      "background": "#001a33"
    }
  }
}
```

## 🎮 Using Themes

### Activate Theme

```php
// Via theme manager
theme()->setActive('ocean');

// Via config
// config/canvastack-ui.php
'default_theme' => 'ocean',
```

### Get Theme Values

```php
// Get color
$primaryColor = theme('colors.primary');

// Get gradient
$heroGradient = theme('gradients.hero');

// Get typography
$fontFamily = theme('typography.font_family');
```

### Check Active Theme

```php
if (theme()->isActive('ocean')) {
    // Ocean theme is active
}

$activeTheme = theme()->getActive();
echo $activeTheme->getName(); // 'ocean'
```

### Override Theme Values

```php
// Override specific values
theme()->override('colors.primary', '#0284c7');

// Override multiple values
theme()->override([
    'colors.primary' => '#0284c7',
    'colors.secondary' => '#0891b2',
]);
```

## 🎭 Common Patterns

### Pattern 1: Industry-Specific Themes

Create themes for specific industries:

**Healthcare Theme**:
```json
{
  "colors": {
    "primary": "#0ea5e9",
    "secondary": "#10b981",
    "accent": "#06b6d4"
  }
}
```

**Finance Theme**:
```json
{
  "colors": {
    "primary": "#1e40af",
    "secondary": "#475569",
    "accent": "#0ea5e9"
  }
}
```

**Education Theme**:
```json
{
  "colors": {
    "primary": "#8b5cf6",
    "secondary": "#a855f7",
    "accent": "#c084fc"
  }
}
```

### Pattern 2: Time-Based Themes

Switch themes based on time of day:

```php
$hour = date('H');

if ($hour >= 6 && $hour < 18) {
    theme()->setActive('ocean'); // Day theme
} else {
    theme()->setActive('midnight'); // Night theme
}
```

### Pattern 3: User Preference Themes

Allow users to choose themes:

```php
// Save user preference
auth()->user()->update([
    'theme' => 'sunset'
]);

// Load user theme
$userTheme = auth()->user()->theme ?? 'ocean';
theme()->setActive($userTheme);
```

### Pattern 4: Context-Based Themes

Different themes for different sections:

```php
// Admin panel
if (request()->is('admin/*')) {
    theme()->setActive('corporate');
}

// Public site
if (request()->is('public/*')) {
    theme()->setActive('ocean');
}
```

## 💡 Best Practices

### 1. Provide Complete Documentation

Each theme should include:
- README.md with description
- Color palette documentation
- Use case examples
- Installation instructions

### 2. Include Preview Images

Create preview images showing:
- Light mode
- Dark mode
- Key components
- Responsive layouts

### 3. Test Accessibility

Ensure themes meet:
- WCAG AA contrast ratios
- Keyboard navigation
- Screen reader compatibility
- Focus indicators

### 4. Support Dark Mode

Always provide dark mode variants:
```json
{
  "dark_mode": {
    "enabled": true,
    "colors": {
      "primary": "#lighter-shade",
      "background": "#dark-shade"
    }
  }
}
```

### 5. Use Semantic Naming

Name themes based on:
- Visual style (Ocean, Sunset)
- Purpose (Corporate, Developer)
- Mood (Calm, Vibrant)

Avoid generic names like "Theme1", "Blue Theme"

## 🧪 Testing Themes

### Visual Testing Checklist

- [ ] Test all components with theme
- [ ] Verify light mode appearance
- [ ] Verify dark mode appearance
- [ ] Check responsive design
- [ ] Test color contrast
- [ ] Verify gradients render correctly
- [ ] Check typography rendering
- [ ] Test on different browsers

### Automated Testing

```php
public function test_theme_loads_correctly()
{
    $theme = theme()->load('ocean');
    
    $this->assertEquals('ocean', $theme->getName());
    $this->assertEquals('#0ea5e9', $theme->get('colors.primary'));
}

public function test_theme_has_required_properties()
{
    $theme = theme()->load('ocean');
    
    $this->assertNotNull($theme->get('colors.primary'));
    $this->assertNotNull($theme->get('colors.secondary'));
    $this->assertTrue($theme->get('dark_mode.enabled'));
}
```

## 🔗 Related Documentation

- [Theme Development Guide](theme-development.md)
- [Theme Configuration Format](../api/theme-configuration-format.md)
- [Tailwind CSS Integration](../frontend/tailwind-css.md)
- [Dark Mode Guide](../frontend/dark-mode.md)

## 📚 Resources

- [Color Palette Generators](https://coolors.co)
- [Gradient Generators](https://cssgradient.io)
- [Contrast Checker](https://webaim.org/resources/contrastchecker)
- [Google Fonts](https://fonts.google.com)

---

**Last Updated**: 2026-02-26  
**Version**: 1.0.0  
**Status**: Published  
**Author**: CanvaStack Team
