# MetaTags Component

Modern meta tags management with SEO optimization for CanvaStack applications.

## 📦 Location

- **File Location**: `packages/canvastack/canvastack/src/Library/Components/MetaTags.php`
- **Namespace**: `Canvastack\Canvastack\Library\Components\MetaTags`
- **Type**: Component

## 🎯 Features

- Basic meta tags (title, description, keywords, author)
- Open Graph meta tags for social media sharing
- Twitter Card meta tags
- JSON-LD structured data for rich snippets
- Theme integration
- Database preference support with config fallback
- Backward compatible with Origin API

## 📖 Basic Usage

### Controller

```php
<?php

namespace App\Http\Controllers;

use Canvastack\Canvastack\Library\Components\MetaTags;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function index(MetaTags $meta): View
    {
        // Configure basic meta tags
        $meta->title('Page Title');
        $meta->description('Page description for SEO');
        $meta->keywords('keyword1, keyword2, keyword3');
        $meta->author('Author Name');
        
        return view('pages.index', ['meta' => $meta]);
    }
}
```

### View

```blade
@extends('canvastack::layouts.admin')

@push('head')
    {!! $meta->tags() !!}
@endpush

@section('content')
    <h1>Page Content</h1>
@endsection
```

## 🔧 API Reference

### Basic Methods

#### `title(?string $string = null): self`

Set the page title. Automatically appends site name from preferences.

```php
$meta->title('Dashboard');
// Output: <title>Dashboard | My Site</title>
```

#### `description(?string $string = null): self`

Set the meta description for SEO.

```php
$meta->description('Manage your application settings and preferences');
// Output: <meta name="description" content="Manage your application settings and preferences" />
```

#### `keywords(?string $string = null): self`

Set meta keywords for SEO.

```php
$meta->keywords('dashboard, admin, settings, management');
// Output: <meta name="keywords" content="dashboard, admin, settings, management" />
```

#### `author(?string $string = null): self`

Set the author meta tag.

```php
$meta->author('John Doe');
// Output: <meta name="author" content="John Doe" />
```

#### `viewport(?string $string = null): self`

Set the viewport meta tag (defaults to responsive viewport).

```php
$meta->viewport('width=device-width, initial-scale=1.0');
// Output: <meta name="viewport" content="width=device-width, initial-scale=1.0" />
```

#### `charset(?string $string = null): self`

Set the character encoding (defaults to UTF-8).

```php
$meta->charset('UTF-8');
// Output: <meta charset="UTF-8" />
```

#### `language(?string $string = null): self`

Set the content language.

```php
$meta->language('en');
// Output: <meta http-equiv="content-language" content="en">
```

#### `appName(?string $string = null): self`

Set the application name.

```php
$meta->appName('My Application');
// Output: <meta name="application-name" content="My Application" />
```

#### `baseURL(?string $string = null): self`

Set the base URL for relative links.

```php
$meta->baseURL('https://example.com/');
// Output: <base href="https://example.com/" />
```

#### `httpEquiv(?string $type = null, ?string $content = null): self`

Set HTTP-EQUIV meta tags.

```php
$meta->httpEquiv('X-UA-Compatible', 'IE=edge');
// Output: <meta http-equiv="X-UA-Compatible" content="IE=edge" />
```

#### `csrf(string $inject): void`

Set CSRF token meta tag.

```php
$meta->csrf(csrf_token());
// Output: <meta name="csrf-token" content="..." />
```

### Social Media Methods

#### `openGraph(array $data): self`

Set Open Graph meta tags for Facebook, LinkedIn, etc.

```php
$meta->openGraph([
    'title' => 'Page Title',
    'description' => 'Page description',
    'image' => 'https://example.com/image.jpg',
    'url' => 'https://example.com/page',
    'type' => 'website',
    'site_name' => 'My Site',
]);

// Output:
// <meta property="og:title" content="Page Title" />
// <meta property="og:description" content="Page description" />
// <meta property="og:image" content="https://example.com/image.jpg" />
// <meta property="og:url" content="https://example.com/page" />
// <meta property="og:type" content="website" />
// <meta property="og:site_name" content="My Site" />
```

#### `twitterCard(array $data): self`

Set Twitter Card meta tags.

```php
$meta->twitterCard([
    'card' => 'summary_large_image',
    'site' => '@username',
    'title' => 'Page Title',
    'description' => 'Page description',
    'image' => 'https://example.com/image.jpg',
]);

// Output:
// <meta name="twitter:card" content="summary_large_image" />
// <meta name="twitter:site" content="@username" />
// <meta name="twitter:title" content="Page Title" />
// <meta name="twitter:description" content="Page description" />
// <meta name="twitter:image" content="https://example.com/image.jpg" />
```

### Structured Data Methods

#### `jsonLd(array $data): self`

Set JSON-LD structured data for rich snippets.

```php
$meta->jsonLd([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'My Company',
    'url' => 'https://example.com',
    'logo' => 'https://example.com/logo.png',
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+1-555-1234',
        'contactType' => 'customer service',
    ],
]);

// Output:
// <script type="application/ld+json">
// {"@context":"https://schema.org","@type":"Organization",...}
// </script>
```

### Output Methods

#### `tags(string $as = 'html'): string|array`

Render all meta tags.

```php
// Render as HTML (default)
{!! $meta->tags() !!}
{!! $meta->tags('html') !!}

// Get as array
$metaArray = $meta->tags('text');
```

#### `getMetaHTML(string $metaName): string`

Get specific meta tag HTML.

```php
$titleHtml = $meta->getMetaHTML('title');
// Returns: <title>Page Title | My Site</title>
```

#### `getMetaText(string $metaName): string`

Get specific meta tag text value.

```php
$titleText = $meta->getMetaText('title');
// Returns: Page Title | My Site
```

## 📝 Examples

### Example 1: Basic Page

```php
public function index(MetaTags $meta): View
{
    $meta->title('Dashboard');
    $meta->description('View your dashboard and analytics');
    $meta->keywords('dashboard, analytics, statistics');
    
    return view('dashboard.index', ['meta' => $meta]);
}
```

### Example 2: Blog Post with Social Sharing

```php
public function show(Post $post, MetaTags $meta): View
{
    // Basic meta tags
    $meta->title($post->title);
    $meta->description($post->excerpt);
    $meta->keywords($post->tags->pluck('name')->implode(', '));
    $meta->author($post->author->name);
    
    // Open Graph for Facebook
    $meta->openGraph([
        'title' => $post->title,
        'description' => $post->excerpt,
        'image' => $post->featured_image_url,
        'url' => route('posts.show', $post),
        'type' => 'article',
        'article:published_time' => $post->published_at->toIso8601String(),
        'article:author' => $post->author->name,
    ]);
    
    // Twitter Card
    $meta->twitterCard([
        'card' => 'summary_large_image',
        'title' => $post->title,
        'description' => $post->excerpt,
        'image' => $post->featured_image_url,
    ]);
    
    // JSON-LD for rich snippets
    $meta->jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $post->excerpt,
        'image' => $post->featured_image_url,
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $post->author->name,
        ],
    ]);
    
    return view('posts.show', ['post' => $post, 'meta' => $meta]);
}
```

### Example 3: Product Page with Rich Snippets

```php
public function show(Product $product, MetaTags $meta): View
{
    $meta->title($product->name);
    $meta->description($product->description);
    $meta->keywords($product->categories->pluck('name')->implode(', '));
    
    // Open Graph
    $meta->openGraph([
        'title' => $product->name,
        'description' => $product->description,
        'image' => $product->image_url,
        'url' => route('products.show', $product),
        'type' => 'product',
        'product:price:amount' => $product->price,
        'product:price:currency' => 'USD',
    ]);
    
    // JSON-LD Product Schema
    $meta->jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->description,
        'image' => $product->image_url,
        'sku' => $product->sku,
        'offers' => [
            '@type' => 'Offer',
            'price' => $product->price,
            'priceCurrency' => 'USD',
            'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => $product->average_rating,
            'reviewCount' => $product->reviews_count,
        ],
    ]);
    
    return view('products.show', ['product' => $product, 'meta' => $meta]);
}
```

## 🔍 Implementation Details

### Preference Loading

The MetaTags component automatically loads default values from:

1. **Database** (Priority 1): `Preference` model if available
2. **Config** (Priority 2): `config/canvastack.php` or `config/canvas.settings.php`
3. **Fallback** (Priority 3): Hardcoded defaults

```php
protected function loadPreferences(): void
{
    // Try database first
    if (class_exists('Canvastack\Origin\Models\Admin\System\Preference')) {
        try {
            $preference = app('Canvastack\Origin\Models\Admin\System\Preference');
            $prefData = $preference->first()?->getAttributes() ?? [];
            
            $this->preference = [
                'app_name' => $prefData['title'] ?? config('app.name'),
                'meta_title' => $prefData['meta_title'] ?? config('app.name'),
                // ...
            ];
            return;
        } catch (\Exception $e) {
            // Fall through to config
        }
    }
    
    // Load from config
    $this->loadFromConfig();
}
```

### Title Concatenation

When setting a title, it automatically appends the site name:

```php
$meta->title('Dashboard');
// Output: Dashboard | My Site Name
```

To set title without concatenation, modify the `meta_title` preference.

## 💡 Tips & Best Practices

1. **Always Use MetaTags** - Every page should have meta tags for SEO
2. **Dynamic Content** - Use model data for dynamic meta tags
3. **Social Sharing** - Always include Open Graph and Twitter Cards for shareable content
4. **Structured Data** - Use JSON-LD for rich snippets in search results
5. **Image URLs** - Always use absolute URLs for images in social meta tags
6. **Description Length** - Keep descriptions between 150-160 characters
7. **Title Length** - Keep titles under 60 characters
8. **Keywords** - Use 5-10 relevant keywords, comma-separated

## 🎭 Common Patterns

### Pattern 1: Admin Page

```php
public function index(MetaTags $meta): View
{
    $meta->title('User Management');
    $meta->description('Manage users, roles, and permissions');
    $meta->keywords('users, admin, management, roles, permissions');
    
    return view('admin.users.index', ['meta' => $meta]);
}
```

### Pattern 2: Public Content Page

```php
public function show(Article $article, MetaTags $meta): View
{
    $meta->title($article->title);
    $meta->description($article->excerpt);
    $meta->keywords($article->tags->pluck('name')->implode(', '));
    $meta->author($article->author->name);
    
    $meta->openGraph([
        'title' => $article->title,
        'description' => $article->excerpt,
        'image' => $article->image_url,
        'url' => route('articles.show', $article),
        'type' => 'article',
    ]);
    
    return view('articles.show', ['article' => $article, 'meta' => $meta]);
}
```

### Pattern 3: API Documentation Page

```php
public function docs(MetaTags $meta): View
{
    $meta->title('API Documentation');
    $meta->description('Complete API reference and integration guides');
    $meta->keywords('api, documentation, reference, integration, rest');
    
    $meta->jsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        'headline' => 'API Documentation',
        'description' => 'Complete API reference and integration guides',
    ]);
    
    return view('docs.api', ['meta' => $meta]);
}
```

## 🔗 Related Components

- [TableBuilder](table-builder.md) - Data table component
- [FormBuilder](form-builder.md) - Form component
- [ChartBuilder](chart-builder.md) - Chart component

## 📚 Resources

- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Schema.org](https://schema.org/)
- [Google Rich Results](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)

---

**Last Updated**: 2024-02-26  
**Version**: 1.0.0  
**Status**: Published
