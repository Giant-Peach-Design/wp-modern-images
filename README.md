# WP Modern Images

A WordPress plugin for generating responsive `<picture>` elements with WebP support, retina handling, and art direction capabilities.

## Installation

```bash
composer require giantpeach/wp-modern-images
```

After installation, activate the plugin and flush permalinks (Settings → Permalinks → Save).

## Usage

```php
echo gp_image(123, [
    0 => [
        'w' => 400,
        'h' => 300,
    ],
    768 => [
        'w' => 800,
        'h' => 600,
    ],
    1024 => [
        'w' => 1200,
        'h' => 800,
    ],
]);
```

### Parameters

**`gp_image(int $image_id, array $sizes, array $options = []): string`**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$image_id` | int | WordPress attachment ID |
| `$sizes` | array | Breakpoint-keyed array of size configurations |
| `$options` | array | Optional rendering options |

### Size Configuration

Array keys define the min-width breakpoint in pixels (0 = default/mobile):

```php
$sizes = [
    0 => [                    // Default (mobile)
        'w' => 400,           // Width (required)
        'h' => 300,           // Height (required)
        'fit' => 'cover',     // Fit mode (default: 'cover')
        'q' => 80,            // Quality 1-100 (default: 80)
    ],
    768 => [                  // min-width: 768px
        'w' => 800,
        'h' => 600,
    ],
    1024 => [                 // min-width: 1024px
        'w' => 1200,
        'h' => 800,
        'image_id' => 456,    // Art direction: use different image
    ],
];
```

### Fit Modes

| Value | Description |
|-------|-------------|
| `cover` | Fill dimensions, crop excess (like CSS `object-fit: cover`) |
| `contain` | Fit within dimensions, maintain aspect ratio |
| `fill` | Stretch to exact dimensions |

### Options

```php
$options = [
    'retina' => true,           // Generate 2x versions (default: true)
    'picture_class' => '',      // Class for <picture> element
    'img_class' => '',          // Class for <img> element
    'alt' => '',                // Alt text (defaults to WP attachment alt)
    'loading' => 'lazy',        // 'lazy' or 'eager'
];
```

### Full Example

```php
echo gp_image(123, [
    0 => [
        'w' => 400,
        'h' => 300,
        'fit' => 'cover',
        'q' => 85,
    ],
    768 => [
        'w' => 800,
        'h' => 600,
        'fit' => 'cover',
    ],
    1024 => [
        'w' => 1200,
        'h' => 800,
        'fit' => 'contain',
        'q' => 90,
        'image_id' => 456,
    ],
], [
    'picture_class' => 'hero',
    'img_class' => 'hero__img',
    'loading' => 'eager',
]);
```

### Output

```html
<picture class="hero">
    <source media="(min-width: 1024px)" srcset="...-1200x800.webp 1x, ...-2400x1600.webp 2x" type="image/webp">
    <source media="(min-width: 1024px)" srcset="...-1200x800.jpg 1x, ...-2400x1600.jpg 2x" type="image/jpeg">
    <source media="(min-width: 768px)" srcset="...-800x600.webp 1x, ...-1600x1200.webp 2x" type="image/webp">
    <source media="(min-width: 768px)" srcset="...-800x600.jpg 1x, ...-1600x1200.jpg 2x" type="image/jpeg">
    <source srcset="...-400x300.webp 1x, ...-800x600.webp 2x" type="image/webp">
    <img src="...-400x300.jpg" srcset="...-400x300.jpg 1x, ...-800x600.jpg 2x" alt="..." class="hero__img" loading="eager">
</picture>
```

## Blade Directive (Sage/Acorn)

If you're using Sage with Acorn, a `@gpImage` directive is automatically registered:

```blade
@gpImage(123, [
    0 => ['w' => 400, 'h' => 300],
    768 => ['w' => 800, 'h' => 600],
    1024 => ['w' => 1200, 'h' => 800],
])

{{-- With options --}}
@gpImage($image_id, $sizes, ['picture_class' => 'hero', 'img_class' => 'hero__img'])
```

## Configuration

### Cache Directory

By default, processed images are cached in `wp-content/cache/wp-modern-images/`.

**Via constant:**
```php
define('WP_MODERN_IMAGES_CACHE_DIR', '/path/to/cache');
define('WP_MODERN_IMAGES_CACHE_URL', 'https://example.com/cache');
```

**Via filter:**
```php
add_filter('gp_modern_images_cache_path', function($path) {
    return '/custom/cache/path';
});
```

## Filters

| Filter | Description |
|--------|-------------|
| `gp_modern_images_cache_path` | Modify cache directory path |
| `gp_modern_images_cache_url` | Modify cache directory URL |
| `gp_modern_images_default_options` | Modify default options |
| `gp_modern_images_picture_html` | Modify final HTML output |

## Requirements

- PHP 7.4+
- WordPress 5.0+
- GD or Imagick extension

## License

MIT
