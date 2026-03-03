# @canAccessColumn Blade Directive

## Overview

The `@canAccessColumn` Blade directive provides a convenient way to conditionally display content based on column-level permissions in your Blade templates. This allows you to show or hide specific form fields, table columns, or any content based on whether the user has permission to access a particular column of a model.

## Usage

### Basic Usage

```blade
@canAccessColumn('posts.edit', $post, 'status')
    <input type="text" name="status" value="{{ $post->status }}">
@endcanAccessColumn
```

### With Multiple Fields

```blade
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    @canAccessColumn('posts.edit', $post, 'title')
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" value="{{ $post->title }}">
        </div>
    @endcanAccessColumn
    
    @canAccessColumn('posts.edit', $post, 'content')
        <div class="form-group">
            <label>Content</label>
            <textarea name="content">{{ $post->content }}</textarea>
        </div>
    @endcanAccessColumn
    
    @canAccessColumn('posts.edit', $post, 'status')
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>
    @endcanAccessColumn
    
    <button type="submit">Update</button>
</form>
```

### In Table Columns

```blade
<table>
    <thead>
        <tr>
            <th>Title</th>
            @canAccessColumn('posts.view', new Post(), 'author')
                <th>Author</th>
            @endcanAccessColumn
            @canAccessColumn('posts.view', new Post(), 'status')
                <th>Status</th>
            @endcanAccessColumn
            @canAccessColumn('posts.view', new Post(), 'published_at')
                <th>Published</th>
            @endcanAccessColumn
        </tr>
    </thead>
    <tbody>
        @foreach($posts as $post)
            <tr>
                <td>{{ $post->title }}</td>
                @canAccessColumn('posts.view', $post, 'author')
                    <td>{{ $post->author->name }}</td>
                @endcanAccessColumn
                @canAccessColumn('posts.view', $post, 'status')
                    <td>{{ $post->status }}</td>
                @endcanAccessColumn
                @canAccessColumn('posts.view', $post, 'published_at')
                    <td>{{ $post->published_at->format('Y-m-d') }}</td>
                @endcanAccessColumn
            </tr>
        @endforeach
    </tbody>
</table>
```

### With Read-Only Display

```blade
@canAccessColumn('posts.edit', $post, 'featured')
    <div class="form-group">
        <label>Featured</label>
        <input type="checkbox" name="featured" {{ $post->featured ? 'checked' : '' }}>
    </div>
@else
    <div class="form-group">
        <label>Featured</label>
        <p class="text-muted">
            {{ $post->featured ? 'Yes' : 'No' }}
            <small>(You don't have permission to edit this field)</small>
        </p>
    </div>
@endcanAccessColumn
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `permission` | string | Yes | The permission name to check (e.g., 'posts.edit') |
| `model` | object | Yes | The model instance to check access against |
| `column` | string | Yes | The column name to check access for (e.g., 'status') |

## How It Works

The directive checks:

1. If the user is authenticated
2. If the user has the basic permission
3. If the user passes column-level permission rules for the specific column
4. If the user is a super admin (bypasses all checks)

## Examples

### Example 1: Conditional Form Field

```blade
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="{{ $post->title }}" required>
    </div>
    
    @canAccessColumn('posts.edit', $post, 'status')
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="draft" {{ $post->status === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ $post->status === 'published' ? 'selected' : '' }}>Published</option>
            </select>
        </div>
    @else
        <input type="hidden" name="status" value="{{ $post->status }}">
        <div class="alert alert-info">
            Current status: <strong>{{ ucfirst($post->status) }}</strong>
            <br>
            <small>You don't have permission to change the status.</small>
        </div>
    @endcanAccessColumn
    
    <button type="submit" class="btn btn-primary">Update Post</button>
</form>
```

### Example 2: Sensitive Data Display

```blade
<div class="card">
    <div class="card-header">
        <h3>User Profile</h3>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9">{{ $user->name }}</dd>
            
            @canAccessColumn('users.view', $user, 'email')
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>
            @endcanAccessColumn
            
            @canAccessColumn('users.view', $user, 'phone')
                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $user->phone }}</dd>
            @endcanAccessColumn
            
            @canAccessColumn('users.view', $user, 'salary')
                <dt class="col-sm-3">Salary</dt>
                <dd class="col-sm-9">${{ number_format($user->salary, 2) }}</dd>
            @endcanAccessColumn
        </dl>
    </div>
</div>
```

### Example 3: Dynamic Form Builder

```blade
@php
    $fields = [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'content' => ['label' => 'Content', 'type' => 'textarea'],
        'excerpt' => ['label' => 'Excerpt', 'type' => 'textarea'],
        'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft', 'published']],
        'featured' => ['label' => 'Featured', 'type' => 'checkbox'],
    ];
@endphp

<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    
    @foreach($fields as $column => $config)
        @canAccessColumn('posts.edit', $post, $column)
            <div class="form-group">
                <label>{{ $config['label'] }}</label>
                
                @if($config['type'] === 'text')
                    <input type="text" name="{{ $column }}" value="{{ $post->$column }}" class="form-control">
                @elseif($config['type'] === 'textarea')
                    <textarea name="{{ $column }}" class="form-control">{{ $post->$column }}</textarea>
                @elseif($config['type'] === 'select')
                    <select name="{{ $column }}" class="form-control">
                        @foreach($config['options'] as $option)
                            <option value="{{ $option }}" {{ $post->$column === $option ? 'selected' : '' }}>
                                {{ ucfirst($option) }}
                            </option>
                        @endforeach
                    </select>
                @elseif($config['type'] === 'checkbox')
                    <input type="checkbox" name="{{ $column }}" {{ $post->$column ? 'checked' : '' }}>
                @endif
            </div>
        @endcanAccessColumn
    @endforeach
    
    <button type="submit" class="btn btn-primary">Update</button>
</form>
```

### Example 4: Nested with Row-Level Permissions

```blade
@canAccessRow('posts.edit', $post)
    <form method="POST" action="{{ route('posts.update', $post) }}">
        @csrf
        @method('PUT')
        
        {{-- Everyone with row access can edit title --}}
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" value="{{ $post->title }}">
        </div>
        
        {{-- Only users with column access can edit status --}}
        @canAccessColumn('posts.edit', $post, 'status')
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
        @endcanAccessColumn
        
        {{-- Only users with column access can edit featured flag --}}
        @canAccessColumn('posts.edit', $post, 'featured')
            <div class="form-group">
                <label>
                    <input type="checkbox" name="featured" {{ $post->featured ? 'checked' : '' }}>
                    Featured Post
                </label>
            </div>
        @endcanAccessColumn
        
        <button type="submit">Update Post</button>
    </form>
@else
    <div class="alert alert-warning">
        You don't have permission to edit this post.
    </div>
@endcanAccessRow
```

### Example 5: API Response Filtering

```blade
{{-- In a Blade component for API documentation --}}
<div class="api-response">
    <h4>Response Fields</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Field</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>id</td>
                <td>integer</td>
                <td>Post ID</td>
            </tr>
            <tr>
                <td>title</td>
                <td>string</td>
                <td>Post title</td>
            </tr>
            @canAccessColumn('posts.view', new Post(), 'author_id')
                <tr>
                    <td>author_id</td>
                    <td>integer</td>
                    <td>Author ID</td>
                </tr>
            @endcanAccessColumn
            @canAccessColumn('posts.view', new Post(), 'status')
                <tr>
                    <td>status</td>
                    <td>string</td>
                    <td>Post status (draft, published)</td>
                </tr>
            @endcanAccessColumn
        </tbody>
    </table>
</div>
```

## Integration with Theme Engine

The directive works seamlessly with the Theme Engine:

```blade
@canAccessColumn('posts.edit', $post, 'status')
    <div class="form-group">
        <label style="font-family: @themeFont('sans')">Status</label>
        <select name="status" 
                class="form-control bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800"
                style="color: @themeColor('text')">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
        </select>
    </div>
@else
    <div class="alert" 
         style="background: @themeColor('info-light'); color: @themeColor('info-dark')">
        <i data-lucide="lock" class="w-4 h-4 inline"></i>
        {{ __('rbac.fine_grained.field_hidden', ['field' => __('ui.labels.status')]) }}
    </div>
@endcanAccessColumn
```

## Integration with i18n

The directive supports internationalization:

```blade
@canAccessColumn('posts.edit', $post, 'status')
    <div class="form-group">
        <label>{{ __('ui.labels.status') }}</label>
        <select name="status" class="form-control">
            <option value="draft">{{ __('ui.status.draft') }}</option>
            <option value="published">{{ __('ui.status.published') }}</option>
        </select>
    </div>
@else
    <div class="alert alert-info">
        {{ __('rbac.fine_grained.column_access_denied', ['column' => __('ui.labels.status')]) }}
    </div>
@endcanAccessColumn
```

## Performance Considerations

1. **Caching**: Column access checks are automatically cached for 3600 seconds
2. **Batch Checks**: When checking multiple columns, consider using `Gate::getAccessibleColumns()` in the controller
3. **Eager Loading**: The directive doesn't cause N+1 queries as it uses the already-loaded model instance

## Best Practices

1. **Always provide fallback content** using `@else` for better UX
2. **Use with FormBuilder** for automatic column filtering
3. **Combine with row-level checks** for comprehensive access control
4. **Use theme colors** for permission indicators
5. **Use i18n** for all user-facing messages
6. **Test with different user roles** to ensure proper access control

## Common Patterns

### Pattern 1: Read-Only Fields

```blade
@canAccessColumn('posts.edit', $post, 'status')
    <input type="text" name="status" value="{{ $post->status }}">
@else
    <input type="text" value="{{ $post->status }}" disabled>
    <small class="text-muted">Read-only</small>
@endcanAccessColumn
```

### Pattern 2: Hidden Fields with Indicators

```blade
@canAccessColumn('posts.edit', $post, 'featured')
    <input type="checkbox" name="featured" {{ $post->featured ? 'checked' : '' }}>
@else
    <div class="alert alert-info">
        <i data-lucide="lock"></i>
        This field is restricted
    </div>
@endcanAccessColumn
```

### Pattern 3: Progressive Disclosure

```blade
<div class="form-group">
    <label>Basic Information</label>
    <input type="text" name="title" value="{{ $post->title }}">
</div>

@canAccessColumn('posts.edit', $post, 'advanced_settings')
    <details>
        <summary>Advanced Settings</summary>
        <div class="form-group">
            <!-- Advanced fields -->
        </div>
    </details>
@endcanAccessColumn
```

## Related Directives

- `@canAccessRow` - Check row-level permissions
- `@canAccessJsonAttribute` - Check JSON attribute permissions (coming soon)

## See Also

- [Gate::canAccessColumn() Method](../api/gate.md#canaccesscolumn)
- [Column-Level Permissions Guide](../guides/column-level-permissions.md)
- [Fine-Grained Permissions Overview](../features/fine-grained-permissions.md)
- [FormBuilder Integration](../components/form-builder.md#permission-filtering)
- [TableBuilder Integration](../components/table-builder.md#permission-filtering)

---

**Last Updated**: 2026-02-28  
**Version**: 1.0.0  
**Status**: Published
