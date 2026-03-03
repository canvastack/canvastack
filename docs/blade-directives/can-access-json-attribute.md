# @canAccessJsonAttribute Blade Directive

## Overview

The `@canAccessJsonAttribute` Blade directive provides a convenient way to conditionally display content based on JSON attribute-level permissions in your Blade templates. This allows you to show or hide specific nested fields within JSON columns based on whether the user has permission to access those particular paths.

## Usage

### Basic Usage

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <input type="text" name="metadata[seo][title]" value="{{ $post->metadata['seo']['title'] ?? '' }}">
@endcanAccessJsonAttribute
```

### With Multiple JSON Fields

```blade
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
        <div class="form-group">
            <label>SEO Title</label>
            <input type="text" name="metadata[seo][title]" value="{{ $post->metadata['seo']['title'] ?? '' }}">
        </div>
    @endcanAccessJsonAttribute
    
    @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.description')
        <div class="form-group">
            <label>SEO Description</label>
            <textarea name="metadata[seo][description]">{{ $post->metadata['seo']['description'] ?? '' }}</textarea>
        </div>
    @endcanAccessJsonAttribute
    
    @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.twitter')
        <div class="form-group">
            <label>Twitter Handle</label>
            <input type="text" name="metadata[social][twitter]" value="{{ $post->metadata['social']['twitter'] ?? '' }}">
        </div>
    @endcanAccessJsonAttribute
    
    <button type="submit">Update</button>
</form>
```

### With Wildcard Paths

```blade
{{-- Check access to all fields under 'seo' --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.*')
    <div class="seo-section">
        <h3>SEO Settings</h3>
        
        <div class="form-group">
            <label>SEO Title</label>
            <input type="text" name="metadata[seo][title]" value="{{ $post->metadata['seo']['title'] ?? '' }}">
        </div>
        
        <div class="form-group">
            <label>SEO Description</label>
            <textarea name="metadata[seo][description]">{{ $post->metadata['seo']['description'] ?? '' }}</textarea>
        </div>
        
        <div class="form-group">
            <label>SEO Keywords</label>
            <input type="text" name="metadata[seo][keywords]" value="{{ $post->metadata['seo']['keywords'] ?? '' }}">
        </div>
    </div>
@endcanAccessJsonAttribute
```

### In Display Views

```blade
<div class="post-metadata">
    <h3>Post Metadata</h3>
    
    <dl class="row">
        @canAccessJsonAttribute('posts.view', $post, 'metadata', 'seo.title')
            <dt class="col-sm-3">SEO Title</dt>
            <dd class="col-sm-9">{{ $post->metadata['seo']['title'] ?? 'Not set' }}</dd>
        @endcanAccessJsonAttribute
        
        @canAccessJsonAttribute('posts.view', $post, 'metadata', 'social.twitter')
            <dt class="col-sm-3">Twitter</dt>
            <dd class="col-sm-9">{{ $post->metadata['social']['twitter'] ?? 'Not set' }}</dd>
        @endcanAccessJsonAttribute
        
        @canAccessJsonAttribute('posts.view', $post, 'metadata', 'layout.featured')
            <dt class="col-sm-3">Featured</dt>
            <dd class="col-sm-9">{{ $post->metadata['layout']['featured'] ? 'Yes' : 'No' }}</dd>
        @endcanAccessJsonAttribute
    </dl>
</div>
```

### With Read-Only Display

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'featured')
    <div class="form-group">
        <label>Featured Post</label>
        <input type="checkbox" name="metadata[featured]" {{ ($post->metadata['featured'] ?? false) ? 'checked' : '' }}>
    </div>
@else
    <div class="form-group">
        <label>Featured Post</label>
        <p class="text-muted">
            {{ ($post->metadata['featured'] ?? false) ? 'Yes' : 'No' }}
            <small>(You don't have permission to edit this field)</small>
        </p>
    </div>
@endcanAccessJsonAttribute
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `permission` | string | Yes | The permission name to check (e.g., 'posts.edit') |
| `model` | object | Yes | The model instance to check access against |
| `jsonColumn` | string | Yes | The JSON column name (e.g., 'metadata') |
| `path` | string | Yes | The JSON path to check access for (e.g., 'seo.title', 'social.*') |

## How It Works

The directive checks:

1. If the user is authenticated
2. If the user has the basic permission
3. If the user passes JSON attribute-level permission rules for the specific path
4. If the user is a super admin (bypasses all checks)

## Path Syntax

### Dot Notation

Use dot notation to specify nested paths:

```blade
{{-- Access metadata.seo.title --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <input name="metadata[seo][title]">
@endcanAccessJsonAttribute

{{-- Access metadata.layout.header.background.color --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'layout.header.background.color')
    <input type="color" name="metadata[layout][header][background][color]">
@endcanAccessJsonAttribute
```

### Wildcard Paths

Use wildcards to match multiple fields:

```blade
{{-- Match all fields under 'seo' --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.*')
    <div class="seo-fields">
        <!-- All SEO fields -->
    </div>
@endcanAccessJsonAttribute

{{-- Match all fields under 'social' --}}
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.*')
    <div class="social-fields">
        <!-- All social media fields -->
    </div>
@endcanAccessJsonAttribute
```

## Examples

### Example 1: SEO Metadata Form

```blade
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    <h3>Basic Information</h3>
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="{{ $post->title }}" required>
    </div>
    
    @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.*')
        <h3>SEO Settings</h3>
        
        <div class="form-group">
            <label>SEO Title</label>
            <input type="text" 
                   name="metadata[seo][title]" 
                   value="{{ $post->metadata['seo']['title'] ?? '' }}"
                   maxlength="60">
            <small class="text-muted">Recommended: 50-60 characters</small>
        </div>
        
        <div class="form-group">
            <label>SEO Description</label>
            <textarea name="metadata[seo][description]" 
                      rows="3" 
                      maxlength="160">{{ $post->metadata['seo']['description'] ?? '' }}</textarea>
            <small class="text-muted">Recommended: 150-160 characters</small>
        </div>
        
        <div class="form-group">
            <label>SEO Keywords</label>
            <input type="text" 
                   name="metadata[seo][keywords]" 
                   value="{{ $post->metadata['seo']['keywords'] ?? '' }}"
                   placeholder="keyword1, keyword2, keyword3">
        </div>
    @else
        <div class="alert alert-info">
            <i data-lucide="lock" class="w-4 h-4 inline"></i>
            SEO settings are restricted for your role.
        </div>
    @endcanAccessJsonAttribute
    
    <button type="submit" class="btn btn-primary">Update Post</button>
</form>
```

### Example 2: Social Media Integration

```blade
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.*')
        <div class="card">
            <div class="card-header">
                <h3>Social Media</h3>
            </div>
            <div class="card-body">
                @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.twitter')
                    <div class="form-group">
                        <label>Twitter Handle</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" 
                                   name="metadata[social][twitter]" 
                                   value="{{ $post->metadata['social']['twitter'] ?? '' }}"
                                   placeholder="username">
                        </div>
                    </div>
                @endcanAccessJsonAttribute
                
                @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.facebook')
                    <div class="form-group">
                        <label>Facebook Page</label>
                        <input type="url" 
                               name="metadata[social][facebook]" 
                               value="{{ $post->metadata['social']['facebook'] ?? '' }}"
                               placeholder="https://facebook.com/page">
                    </div>
                @endcanAccessJsonAttribute
                
                @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'social.instagram')
                    <div class="form-group">
                        <label>Instagram Handle</label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" 
                                   name="metadata[social][instagram]" 
                                   value="{{ $post->metadata['social']['instagram'] ?? '' }}"
                                   placeholder="username">
                        </div>
                    </div>
                @endcanAccessJsonAttribute
            </div>
        </div>
    @endcanAccessJsonAttribute
    
    <button type="submit">Update</button>
</form>
```

### Example 3: Layout Configuration

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'layout.*')
    <div class="card">
        <div class="card-header">
            <h3>Layout Settings</h3>
        </div>
        <div class="card-body">
            @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'layout.template')
                <div class="form-group">
                    <label>Template</label>
                    <select name="metadata[layout][template]" class="form-control">
                        <option value="default" {{ ($post->metadata['layout']['template'] ?? 'default') === 'default' ? 'selected' : '' }}>Default</option>
                        <option value="wide" {{ ($post->metadata['layout']['template'] ?? '') === 'wide' ? 'selected' : '' }}>Wide</option>
                        <option value="narrow" {{ ($post->metadata['layout']['template'] ?? '') === 'narrow' ? 'selected' : '' }}>Narrow</option>
                    </select>
                </div>
            @endcanAccessJsonAttribute
            
            @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'layout.sidebar')
                <div class="form-group">
                    <label>Sidebar Position</label>
                    <select name="metadata[layout][sidebar]" class="form-control">
                        <option value="left" {{ ($post->metadata['layout']['sidebar'] ?? 'right') === 'left' ? 'selected' : '' }}>Left</option>
                        <option value="right" {{ ($post->metadata['layout']['sidebar'] ?? 'right') === 'right' ? 'selected' : '' }}>Right</option>
                        <option value="none" {{ ($post->metadata['layout']['sidebar'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
            @endcanAccessJsonAttribute
            
            @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'layout.header.background.color')
                <div class="form-group">
                    <label>Header Background Color</label>
                    <input type="color" 
                           name="metadata[layout][header][background][color]" 
                           value="{{ $post->metadata['layout']['header']['background']['color'] ?? '#ffffff' }}">
                </div>
            @endcanAccessJsonAttribute
        </div>
    </div>
@endcanAccessJsonAttribute
```

### Example 4: Conditional Display with Fallback

```blade
<div class="post-details">
    <h2>{{ $post->title }}</h2>
    
    <div class="metadata">
        @canAccessJsonAttribute('posts.view', $post, 'metadata', 'seo.title')
            <div class="metadata-item">
                <strong>SEO Title:</strong>
                {{ $post->metadata['seo']['title'] ?? 'Not set' }}
            </div>
        @else
            <div class="metadata-item text-muted">
                <i data-lucide="lock" class="w-3 h-3 inline"></i>
                SEO information is restricted
            </div>
        @endcanAccessJsonAttribute
        
        @canAccessJsonAttribute('posts.view', $post, 'metadata', 'social.twitter')
            <div class="metadata-item">
                <strong>Twitter:</strong>
                <a href="https://twitter.com/{{ $post->metadata['social']['twitter'] ?? '' }}" target="_blank">
                    @{{ $post->metadata['social']['twitter'] ?? 'Not set' }}
                </a>
            </div>
        @endcanAccessJsonAttribute
    </div>
</div>
```

### Example 5: Nested with Row and Column Permissions

```blade
@canAccessRow('posts.edit', $post)
    @canAccessColumn('posts.edit', $post, 'metadata')
        <form method="POST" action="{{ route('posts.update', $post) }}">
            @csrf
            @method('PUT')
            
            {{-- Everyone with column access can edit basic metadata --}}
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="metadata[category]" value="{{ $post->metadata['category'] ?? '' }}">
            </div>
            
            {{-- Only users with JSON attribute access can edit SEO --}}
            @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.*')
                <div class="seo-section">
                    <h4>SEO Settings</h4>
                    <input type="text" name="metadata[seo][title]" value="{{ $post->metadata['seo']['title'] ?? '' }}">
                    <textarea name="metadata[seo][description]">{{ $post->metadata['seo']['description'] ?? '' }}</textarea>
                </div>
            @endcanAccessJsonAttribute
            
            {{-- Only users with JSON attribute access can edit featured flag --}}
            @canAccessJsonAttribute('posts.edit', $post, 'metadata', 'featured')
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="metadata[featured]" {{ ($post->metadata['featured'] ?? false) ? 'checked' : '' }}>
                        Featured Post
                    </label>
                </div>
            @endcanAccessJsonAttribute
            
            <button type="submit">Update Post</button>
        </form>
    @else
        <div class="alert alert-warning">
            You don't have permission to edit metadata for this post.
        </div>
    @endcanAccessColumn
@else
    <div class="alert alert-warning">
        You don't have permission to edit this post.
    </div>
@endcanAccessRow
```

### Example 6: Dynamic JSON Field Builder

```blade
@php
    $jsonFields = [
        'seo.title' => ['label' => 'SEO Title', 'type' => 'text', 'maxlength' => 60],
        'seo.description' => ['label' => 'SEO Description', 'type' => 'textarea', 'maxlength' => 160],
        'social.twitter' => ['label' => 'Twitter', 'type' => 'text', 'prefix' => '@'],
        'social.facebook' => ['label' => 'Facebook', 'type' => 'url'],
        'layout.template' => ['label' => 'Template', 'type' => 'select', 'options' => ['default', 'wide', 'narrow']],
    ];
@endphp

<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    @foreach($jsonFields as $path => $config)
        @canAccessJsonAttribute('posts.edit', $post, 'metadata', $path)
            <div class="form-group">
                <label>{{ $config['label'] }}</label>
                
                @php
                    $fieldName = 'metadata[' . str_replace('.', '][', $path) . ']';
                    $value = data_get($post->metadata, $path, '');
                @endphp
                
                @if($config['type'] === 'text')
                    @if(isset($config['prefix']))
                        <div class="input-group">
                            <span class="input-group-text">{{ $config['prefix'] }}</span>
                            <input type="text" name="{{ $fieldName }}" value="{{ $value }}" 
                                   @if(isset($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif>
                        </div>
                    @else
                        <input type="text" name="{{ $fieldName }}" value="{{ $value }}" 
                               @if(isset($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif>
                    @endif
                @elseif($config['type'] === 'textarea')
                    <textarea name="{{ $fieldName }}" 
                              @if(isset($config['maxlength'])) maxlength="{{ $config['maxlength'] }}" @endif>{{ $value }}</textarea>
                @elseif($config['type'] === 'url')
                    <input type="url" name="{{ $fieldName }}" value="{{ $value }}">
                @elseif($config['type'] === 'select')
                    <select name="{{ $fieldName }}">
                        @foreach($config['options'] as $option)
                            <option value="{{ $option }}" {{ $value === $option ? 'selected' : '' }}>
                                {{ ucfirst($option) }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
        @endcanAccessJsonAttribute
    @endforeach
    
    <button type="submit">Update</button>
</form>
```

## Integration with Theme Engine

The directive works seamlessly with the Theme Engine:

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <div class="form-group">
        <label style="font-family: @themeFont('sans')">SEO Title</label>
        <input type="text" 
               name="metadata[seo][title]" 
               value="{{ $post->metadata['seo']['title'] ?? '' }}"
               class="form-control bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800"
               style="color: @themeColor('text')">
    </div>
@else
    <div class="alert" 
         style="background: @themeColor('info-light'); color: @themeColor('info-dark')">
        <i data-lucide="lock" class="w-4 h-4 inline"></i>
        {{ __('rbac.fine_grained.json_attribute_hidden', ['path' => 'seo.title']) }}
    </div>
@endcanAccessJsonAttribute
```

## Integration with i18n

The directive supports internationalization:

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.title')
    <div class="form-group">
        <label>{{ __('ui.labels.seo_title') }}</label>
        <input type="text" name="metadata[seo][title]" value="{{ $post->metadata['seo']['title'] ?? '' }}">
    </div>
@else
    <div class="alert alert-info">
        {{ __('rbac.fine_grained.json_attribute_access_denied', ['path' => __('ui.labels.seo_title')]) }}
    </div>
@endcanAccessJsonAttribute
```

## Performance Considerations

1. **Caching**: JSON attribute access checks are automatically cached for 3600 seconds
2. **Batch Checks**: When checking multiple paths, consider using `Gate::getAccessibleJsonPaths()` in the controller
3. **Eager Loading**: The directive doesn't cause N+1 queries as it uses the already-loaded model instance
4. **Wildcard Efficiency**: Wildcard paths are evaluated once and cached

## Best Practices

1. **Always provide fallback content** using `@else` for better UX
2. **Use wildcard paths** for groups of related fields
3. **Combine with row and column checks** for comprehensive access control
4. **Use theme colors** for permission indicators
5. **Use i18n** for all user-facing messages
6. **Test with different user roles** to ensure proper access control
7. **Use dot notation** consistently for path specification
8. **Validate JSON structure** before accessing nested values

## Common Patterns

### Pattern 1: Grouped Fields with Wildcard

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'seo.*')
    <div class="card">
        <div class="card-header">SEO Settings</div>
        <div class="card-body">
            <!-- All SEO fields -->
        </div>
    </div>
@else
    <div class="alert alert-info">
        SEO settings are restricted
    </div>
@endcanAccessJsonAttribute
```

### Pattern 2: Individual Field Access

```blade
@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'featured')
    <input type="checkbox" name="metadata[featured]" {{ ($post->metadata['featured'] ?? false) ? 'checked' : '' }}>
@else
    <span class="badge">{{ ($post->metadata['featured'] ?? false) ? 'Featured' : 'Not Featured' }}</span>
@endcanAccessJsonAttribute
```

### Pattern 3: Progressive Disclosure

```blade
<div class="form-group">
    <label>Basic Settings</label>
    <input type="text" name="title" value="{{ $post->title }}">
</div>

@canAccessJsonAttribute('posts.edit', $post, 'metadata', 'advanced.*')
    <details>
        <summary>Advanced Settings</summary>
        <div class="advanced-fields">
            <!-- Advanced JSON fields -->
        </div>
    </details>
@endcanAccessJsonAttribute
```

## Related Directives

- `@canAccessRow` - Check row-level permissions
- `@canAccessColumn` - Check column-level permissions

## See Also

- [Gate::canAccessJsonAttribute() Method](../api/gate.md#canaccessjsonattribute)
- [JSON Attribute Permissions Guide](../guides/json-attribute-permissions.md)
- [Fine-Grained Permissions Overview](../features/fine-grained-permissions.md)
- [FormBuilder Integration](../components/form-builder.md#json-attribute-filtering)

---

**Last Updated**: 2026-02-28  
**Version**: 1.0.0  
**Status**: Published
