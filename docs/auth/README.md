# CanvaStack User & Role Management

This module outlines recommended patterns to implement user and role management in CanvaStack-backed applications, integrating smoothly with Laravel policies, gates, and middleware. A native RBAC module is on the roadmap; until then, use these conventions.

## Goals

- Consistent roles/permissions structure aligned with route-level and UI-level controls.
- Simple checks in controllers/views and consistent rendering of action buttons based on permissions.

## Database Structure (Suggested)

- `users` — default Laravel table (published migrations add missing columns where needed).
- `roles` — role records (e.g., Admin, Editor, User).
- `permissions` — granular capability records (e.g., user.view, user.create, user.delete).
- `role_user` — pivot for many-to-many (user ↔ role).
- `permission_role` — pivot for many-to-many (role ↔ permission).

If you already use a package (e.g., spatie/laravel-permission), adopt that package’s tables and API, and simply bridge checks where needed.

## Authorization Checks

- Use Laravel policies/gates for model-level authorization.
- Wrap action buttons with permission checks to prevent UI drift from server rules.

Example using Laravel’s Gate:

```php
use Illuminate\\Support\\Facades\\Gate;

if (Gate::allows('users.view', $user)) {
    $viewUrl = route('users.show', $user->id);
}
```

In Blade:

```blade
@can('users.edit', $user)
  <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">Edit</a>
@endcan
```

## Integrating with Action Buttons

Generate action buttons only for allowed operations:

```php
$actions = [];
if (auth()->user()->can('users.view', $user)) {
  $actions[] = 'info|View:'.route('users.show', $user->id);
}
if (auth()->user()->can('users.edit', $user)) {
  $actions[] = 'warning|Edit:'.route('users.edit', $user->id);
}
// Danger actions use form+DELETE pattern internally
if (auth()->user()->can('users.delete', $user)) {
  $actions[] = 'danger|Delete:'.route('users.destroy', $user->id);
}
$html = Canvatility::createActionButtons(false, false, false, Canvatility::addActionButtonByString($actions, true));
```

## Route & Middleware

- Protect groups with middleware:

```php
Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class);
});
```

- Add policy mappings in `AuthServiceProvider`:

```php
protected $policies = [
  App\\Models\\User::class => App\\Policies\\UserPolicy::class,
];
```

## UI Conventions

- Hide/disable actions user cannot perform.
- Use uniform color coding: primary (create), info (view), warning (edit), danger (delete/restore).

## Auditing & Activity

- Consider saving `performed_by` and timestamps in critical operations for traceability.
- Use events/listeners for audit logs.

## Migration Path to Native RBAC

- Keep abilities expressed as dot-notated strings (e.g., `module.action`).
- Centralize role seeding so the future native module can import/align easily.

## Testing

- Add feature tests to assert unauthorized users cannot access routes and buttons are not rendered.
- Snapshot tests can verify that tables render only allowed actions per role.