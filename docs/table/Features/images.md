# Image Handling

CanvaStack Table provides comprehensive image handling capabilities including display, thumbnails, lightbox integration, lazy loading, and image optimization. This guide covers all aspects of working with images in tables.

## Table of Contents

- [Basic Image Display](#basic-image-display)
- [Image Configuration](#image-configuration)
- [Thumbnails and Sizing](#thumbnails-and-sizing)
- [Lightbox Integration](#lightbox-integration)
- [Lazy Loading](#lazy-loading)
- [Image Fallbacks](#image-fallbacks)
- [Multiple Images](#multiple-images)
- [Image Security](#image-security)
- [Performance Optimization](#performance-optimization)

## Basic Image Display

### Enable Image Display

Configure columns to display as images:

```php
public function index()
{
    $this->setPage();

    // Set specific columns as image columns
    $this->table->setFieldAsImage(['avatar', 'profile_picture']);

    $this->table->lists('users', [
        'avatar:Profile Photo',
        'name:Full Name',
        'email:Email Address',
        'created_at:Registration Date'
    ]);

    return $this->render();
}
```

### Multiple Image Columns

Handle multiple image columns in a single table:

```php
public function index()
{
    $this->setPage();

    // Configure multiple image columns
    $this->table->setFieldAsImage([
        'avatar',
        'cover_photo',
        'id_card_image',
        'signature'
    ]);

    $this->table->lists('employees', [
        'avatar:Profile',
        'name:Full Name',
        'cover_photo:Cover Photo',
        'department:Department',
        'id_card_image:ID Card',
        'signature:Signature'
    ]);

    return $this->render();
}
```

## Image Configuration

### Basic Image Settings

Configure basic image display settings:

```php
public function index()
{
    $this->setPage();

    $this->table->setFieldAsImage(['avatar']);

    // Configure image settings
    $this->table->setImageConfig([
        'width' => 50,
        'height' => 50,
        'class' => 'rounded-circle',
        'style' => 'object-fit: cover;',
        'alt_attribute' => 'User Avatar'
    ]);

    $this->table->lists('users', [
        'avatar:Photo',
        'name:Name',
        'email:Email'
    ]);

    return $this->render();
}
```

### Column-Specific Image Settings

Configure different settings for different image columns:

```php
$this->table->setColumnImageConfig([
    'avatar' => [
        'width' => 40,
        'height' => 40,
        'class' => 'rounded-circle border',
        'style' => 'object-fit: cover;',
        'alt' => function($row) {
            return $row->name . "'s avatar";
        }
    ],
    'cover_photo' => [
        'width' => 100,
        'height' => 60,
        'class' => 'rounded',
        'style' => 'object-fit: cover;',
        'alt' => 'Cover Photo'
    ],
    'product_image' => [
        'width' => 80,
        'height' => 80,
        'class' => 'border shadow-sm',
        'style' => 'object-fit: contain; background: white;',
        'alt' => function($row) {
            return $row->product_name . ' image';
        }
    ]
]);
```

### Responsive Image Settings

Configure responsive image behavior:

```php
$this->table->setResponsiveImageConfig([
    'breakpoints' => [
        'xs' => ['width' => 30, 'height' => 30],
        'sm' => ['width' => 40, 'height' => 40],
        'md' => ['width' => 50, 'height' => 50],
        'lg' => ['width' => 60, 'height' => 60]
    ],
    'hide_on_mobile' => false,
    'stack_on_mobile' => true
]);
```

## Thumbnails and Sizing

### Automatic Thumbnail Generation

Generate thumbnails automatically:

```php
$this->table->setImageThumbnails([
    'enabled' => true,
    'sizes' => [
        'small' => ['width' => 50, 'height' => 50],
        'medium' => ['width' => 100, 'height' => 100],
        'large' => ['width' => 200, 'height' => 200]
    ],
    'quality' => 85,
    'format' => 'webp', // webp, jpg, png
    'cache_path' => 'thumbnails/',
    'cache_duration' => 86400 // 24 hours
]);

$this->table->setFieldAsImage(['product_image']);

$this->table->lists('products', [
    'product_image:Image',
    'name:Product Name',
    'price:Price'
]);
```

### Custom Thumbnail Logic

Implement custom thumbnail generation:

```php
$this->table->setCustomThumbnailGenerator(function($imagePath, $size, $options) {
    // Custom thumbnail generation logic
    $thumbnailPath = 'thumbnails/' . $size . '/' . basename($imagePath);
    
    if (!file_exists(public_path($thumbnailPath))) {
        // Generate thumbnail using your preferred image library
        $image = Image::make(public_path($imagePath));
        $image->fit($options['width'], $options['height']);
        $image->save(public_path($thumbnailPath), $options['quality'] ?? 85);
    }
    
    return asset($thumbnailPath);
});
```

### Dynamic Image Sizing

Size images based on content or context:

```php
$this->table->setDynamicImageSizing([
    'avatar' => function($row) {
        // Larger avatars for VIP users
        if ($row->is_vip) {
            return ['width' => 60, 'height' => 60];
        }
        return ['width' => 40, 'height' => 40];
    },
    'product_image' => function($row) {
        // Different sizes based on product category
        $sizes = [
            'electronics' => ['width' => 80, 'height' => 80],
            'clothing' => ['width' => 60, 'height' => 80],
            'books' => ['width' => 50, 'height' => 70]
        ];
        
        return $sizes[$row->category] ?? ['width' => 60, 'height' => 60];
    }
]);
```

## Lightbox Integration

### Basic Lightbox

Enable lightbox for image viewing:

```php
public function index()
{
    $this->setPage();

    $this->table->setFieldAsImage(['product_image']);

    // Enable lightbox
    $this->table->setImageLightbox([
        'enabled' => true,
        'library' => 'fancybox', // fancybox, lightbox2, photoswipe
        'group_by_row' => true,
        'show_caption' => true,
        'show_counter' => true
    ]);

    $this->table->lists('products', [
        'product_image:Image',
        'name:Product Name',
        'description:Description'
    ]);

    return $this->render();
}
```

### Advanced Lightbox Configuration

Configure advanced lightbox features:

```php
$this->table->setAdvancedLightbox([
    'library' => 'photoswipe',
    'options' => [
        'bgOpacity' => 0.8,
        'showHideOpacity' => true,
        'showAnimationDuration' => 333,
        'hideAnimationDuration' => 333,
        'closeOnScroll' => false,
        'closeOnVerticalDrag' => true,
        'mouseUsed' => false,
        'escKey' => true,
        'arrowKeys' => true,
        'history' => false,
        'galleryUID' => 1
    ],
    'caption_template' => function($row, $column) {
        return '<div class="pswp__caption__center">' .
               '<h4>' . $row->name . '</h4>' .
               '<p>' . $row->description . '</p>' .
               '</div>';
    }
]);
```

### Gallery Mode

Create image galleries from table rows:

```php
$this->table->setImageGallery([
    'enabled' => true,
    'group_by' => 'category_id', // Group images by category
    'navigation' => true,
    'thumbnails' => true,
    'autoplay' => false,
    'transition' => 'slide',
    'loop' => true
]);
```

## Lazy Loading

### Enable Lazy Loading

Implement lazy loading for better performance:

```php
public function index()
{
    $this->setPage();

    $this->table->setFieldAsImage(['product_image', 'gallery_image']);

    // Enable lazy loading
    $this->table->setImageLazyLoading([
        'enabled' => true,
        'threshold' => 100, // pixels before image enters viewport
        'placeholder' => asset('images/placeholder.svg'),
        'loading_class' => 'image-loading',
        'loaded_class' => 'image-loaded',
        'error_class' => 'image-error'
    ]);

    $this->table->lists('products', [
        'product_image:Image',
        'name:Product Name',
        'gallery_image:Gallery'
    ]);

    return $this->render();
}
```

### Progressive Loading

Implement progressive image loading:

```php
$this->table->setProgressiveLoading([
    'enabled' => true,
    'low_quality_placeholder' => true,
    'blur_effect' => true,
    'fade_in_duration' => 300,
    'quality_levels' => [
        'placeholder' => 10, // Very low quality for initial load
        'preview' => 30,     // Medium quality for preview
        'full' => 85         // Full quality for final image
    ]
]);
```

### Intersection Observer

Use Intersection Observer for efficient lazy loading:

```php
$this->table->setIntersectionObserver([
    'enabled' => true,
    'root_margin' => '50px',
    'threshold' => 0.1,
    'unobserve_loaded' => true,
    'retry_failed' => true,
    'retry_delay' => 2000
]);
```

## Image Fallbacks

### Default Fallback Images

Set fallback images for missing or broken images:

```php
$this->table->setImageFallbacks([
    'avatar' => asset('images/default-avatar.png'),
    'product_image' => asset('images/no-image.png'),
    'cover_photo' => asset('images/default-cover.jpg'),
    'default' => asset('images/placeholder.svg')
]);
```

### Dynamic Fallbacks

Generate fallbacks dynamically:

```php
$this->table->setDynamicFallbacks([
    'avatar' => function($row) {
        // Generate avatar based on initials
        return 'https://ui-avatars.com/api/?name=' . urlencode($row->name) . 
               '&background=random&color=fff&size=100';
    },
    'product_image' => function($row) {
        // Category-specific fallback
        $fallbacks = [
            'electronics' => 'images/fallback-electronics.png',
            'clothing' => 'images/fallback-clothing.png',
            'books' => 'images/fallback-books.png'
        ];
        
        return asset($fallbacks[$row->category] ?? 'images/no-image.png');
    }
]);
```

### Error Handling

Handle image loading errors gracefully:

```php
$this->table->setImageErrorHandling([
    'retry_attempts' => 3,
    'retry_delay' => 1000,
    'show_error_message' => false,
    'error_callback' => 'function(img, error) { 
        console.log("Image failed to load:", img.src, error); 
    }',
    'fallback_on_error' => true,
    'hide_on_error' => false
]);
```

## Multiple Images

### Image Arrays

Handle columns containing multiple images:

```php
public function index()
{
    $this->setPage();

    // Configure multiple image display
    $this->table->setMultipleImages([
        'gallery_images' => [
            'type' => 'array',
            'separator' => ',',
            'max_display' => 3,
            'show_count' => true,
            'layout' => 'grid', // grid, carousel, stack
            'thumbnail_size' => ['width' => 40, 'height' => 40]
        ]
    ]);

    $this->table->setFieldAsImage(['gallery_images']);

    $this->table->lists('products', [
        'name:Product Name',
        'gallery_images:Gallery',
        'price:Price'
    ]);

    return $this->render();
}
```

### Image Carousel

Display multiple images as a carousel:

```php
$this->table->setImageCarousel([
    'gallery_images' => [
        'enabled' => true,
        'auto_play' => false,
        'show_dots' => true,
        'show_arrows' => true,
        'infinite_loop' => true,
        'transition_speed' => 300,
        'pause_on_hover' => true
    ]
]);
```

### Image Grid

Display multiple images in a grid layout:

```php
$this->table->setImageGrid([
    'gallery_images' => [
        'columns' => 3,
        'gap' => '2px',
        'max_height' => '100px',
        'overflow_indicator' => '+{count} more',
        'click_to_expand' => true
    ]
]);
```

## Image Security

### Secure Image URLs

Generate secure, time-limited image URLs:

```php
$this->table->setSecureImageUrls([
    'enabled' => true,
    'expiry_time' => 3600, // 1 hour
    'secret_key' => config('app.key'),
    'hash_algorithm' => 'sha256',
    'url_parameter' => 'signature'
]);
```

### Image Access Control

Control image access based on permissions:

```php
$this->table->setImageAccessControl([
    'check_permissions' => true,
    'permission_callback' => function($imagePath, $row, $user) {
        // Check if user can view this image
        if ($imagePath === $row->avatar) {
            return true; // Avatars are always visible
        }
        
        if ($imagePath === $row->private_document) {
            return $user->can('view-private-documents', $row);
        }
        
        return true;
    },
    'access_denied_image' => asset('images/access-denied.png')
]);
```

### Image Watermarking

Add watermarks to images:

```php
$this->table->setImageWatermark([
    'enabled' => true,
    'watermark_image' => asset('images/watermark.png'),
    'position' => 'bottom-right',
    'opacity' => 0.7,
    'margin' => 10,
    'apply_to' => ['product_image', 'gallery_images']
]);
```

## Performance Optimization

### Image Optimization

Optimize images for better performance:

```php
$this->table->setImageOptimization([
    'enabled' => true,
    'format_conversion' => [
        'webp' => true,
        'avif' => false
    ],
    'quality' => [
        'jpeg' => 85,
        'webp' => 80,
        'png' => 90
    ],
    'progressive_jpeg' => true,
    'strip_metadata' => true,
    'cache_optimized' => true
]);
```

### CDN Integration

Integrate with CDN for image delivery:

```php
$this->table->setImageCDN([
    'enabled' => true,
    'base_url' => 'https://cdn.example.com',
    'transformations' => [
        'avatar' => 'w_100,h_100,c_fill,f_auto,q_auto',
        'product_image' => 'w_200,h_200,c_fit,f_auto,q_auto'
    ],
    'fallback_to_original' => true
]);
```

### Caching Strategy

Implement image caching:

```php
$this->table->setImageCaching([
    'browser_cache' => [
        'max_age' => 86400, // 24 hours
        'must_revalidate' => false
    ],
    'server_cache' => [
        'enabled' => true,
        'driver' => 'redis',
        'ttl' => 3600
    ],
    'preload_images' => true,
    'prefetch_next_page' => true
]);
```

---

## Related Documentation

- [Basic Usage](../basic-usage.md) - Basic table setup with images
- [Column Management](../columns.md) - Column configuration
- [Performance Optimization](../advanced/performance.md) - Image performance optimization
- [API Reference](../api/objects.md) - Complete image method documentation