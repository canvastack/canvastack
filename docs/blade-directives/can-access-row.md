# @canAccessRow Blade Directive

## Overview

The `@canAccessRow` Blade directive provides a convenient way to conditionally display content based on row-level permissions in your Blade templates.

## Usage

### Basic Usage

```blade
@canAccessRow('posts.edit', $post)
    <button class="btn btn-primary">Edit Post</button>
@endcanAccessRow
```

### With Multiple Actions

```blade
<div class="actions">
    @canAccessRow('posts.edit', $post)
        <button class="btn btn-warning">Edit</button>
    @endcanAccessRow
    
    @canAccessRow('posts.delete', $post)
        <button class="btn btn-error">Delete</button>
    @endcanAccessRow
</div>
```

### In Table Rows

```blade
<table>
    <thead>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($posts as $post)
            <tr>
                <td>{{ $post->title }}</td>
                <td>{{ $post->author->name }}</td>
                <td>
                    @canAccessRow('posts.view', $post)
                        <a href="{{ route('posts.show', $post) }}">View</a>
                    @endcanAccessRow
                    
                    @canAccessRow('posts.edit', $post)
                        <a href="{{ route('posts.edit', $post) }}">Edit</a>
                    @endcanAccessRow
                    
                    @canAccessRow('posts.delete', $post)
                        <form method="POST" action="{{ route('posts.destroy', $post) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>
                    @endcanAccessRow
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### With Complex Content

```blade
@canAccessRow('posts.edit', $post)
    <div class="card">
        <div class="card-header">
            <h3>Edit Post</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('posts.update', $post) }}">
                @csrf
                @method('PUT')
                <!-- Form fields -->
                <button type="submit">Update</button>
            </form>
        </div>
    </div>
@endcanAccessRow
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `permission` | string | Yes | The permission name to check (e.g., 'posts.edit') |
| `model` | object | Yes | The model instance to check access against |

## How It Works

The directive checks:

1. If the user is authenticated
2. If the user has the basic permission
3. If the user passes row-level permission rules
4. If the user is a super admin (bypasses all checks)

## Examples

### Example 1: Edit Button

```blade
@canAccessRow('posts.edit', $post)
    <a href="{{ route('posts.edit', $post) }}" class="btn btn-primary">
        <i data-lucide="edit"></i>
        Edit
    </a>
@endcanAccessRow
```

### Example 2: Conditional Form

```blade
@canAccessRow('posts.update', $post)
    <form method="POST" action="{{ route('posts.update', $post) }}">
        @csrf
        @method('PUT')
        
        <input type="text" name="title" value="{{ $post->title }}">
        <textarea name="content">{{ $post->content }}</textarea>
        
        <button type="submit">Update Post</button>
    </form>
@else
    <div class="alert alert-warning">
        You don't have permission to edit this post.
    </div>
@endcanAccessRow
```

### Example 3: Nested Permissions

```blade
@canAccessRow('posts.view', $post)
    <div class="post">
        <h2>{{ $post->title }}</h2>
        <p>{{ $post->excerpt }}</p>
        
        @canAccessRow('posts.edit', $post)
            <a href="{{ route('posts.edit', $post) }}">Edit</a>
        @endcanAccessRow
    </div>
@endcanAccessRow
```

## Related Directives

- `@canAccessColumn` - Check column-level permissions (coming soon)
- `@canAccessJsonAttribute` - Check JSON attribute permissions (coming soon)

## See Also

- [Gate::canAccessRow() Method](../api/gate.md#canaccessrow)
- [Row-Level Permissions Guide](../guides/row-level-permissions.md)
- [Fine-Grained Permissions Overview](../features/fine-grained-permissions.md)

---

**Last Updated**: 2026-02-28  
**Version**: 1.0.0  
**Status**: Published
