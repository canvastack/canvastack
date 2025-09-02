# CanvaStack Form System

A server-rendered form toolkit built on top of LaravelCollective that standardizes markup, validation hooks, icon decoration, and common input factories. It is exposed via the Canvatility facade and controller plugins `$this->form`.

- Namespace: `Canvastack\\Canvastack\\Library\\Components\\Utility\\Html\\FormUi`
- Facade entry: `Canvatility::*` (wrappers around FormUi and LaravelCollective)
- Controller plugin: `$this->form` (instance of `Library\\Components\\Form\\Objects`)
- Helpers: functions in `src/Library/Helpers/FormObject.php`

## Key Concepts

- Declarative helpers return HTML strings you can echo in Blade.
- Or, prefer controller-driven composition via `$this->form` then `$this->render()`.
- Consistent Bootstrap-compatible CSS classes.
- Small utilities for icon placement, attribute merging, and quick alert messages.

## Installation Notes

- Package depends on `laravelcollective/html ~6.4` and registers it automatically through Laravel's container.

## Controller Integration: Using $this->form

The base controller `Canvastack\\Canvastack\\Controllers\\Core\\Controller` wires a form plugin (`$this->form`) via trait `Components\Form`. Typical flow:

1) Extend the base controller.
2) Optionally set page metadata via `$this->setPage('Module Name')`.
3) Compose form with `$this->form->open()`, fields, and `$this->form->close()`.
4) Return `$this->render()` which assembles all registered components.

Example: Create/Edit form

```php
use Canvastack\\Canvastack\\Controllers\\Core\\Controller as CoreController;
use App\\Models\\User;

class UserController extends CoreController
{
    public function __construct() {
        parent::__construct(User::class, 'system.accounts.user'); // sets model_class and route page
    }

    public function create() {
        $this->setPage('Create User');

        // Open form; default action auto-resolves from route: *.create -> *.store
        $this->form->open();
        $this->form->token();

        // Label + inputs
        $this->form->draw(\Collective\Html\FormFacade::label('name', 'Full Name', ['class' => 'col-sm-3 control-label']));
        $this->form->text('name', null, ['class' => 'form-control']);
        $this->form->text('email', null, ['class' => 'form-control']);
        $this->form->password('password', ['class' => 'form-control']);

        // Submit button and close
        $this->form->close('Create');

        return $this->render();
    }

    public function edit($id) {
        $this->setPage('Edit User');

        // Model bound form; default action auto-resolves *.edit -> *.update
        $this->form->model(null, $id); // will infer model from base controller and find($id)
        $this->form->token();

        $this->form->text('name', null, ['class' => 'form-control']);
        $this->form->text('email', null, ['class' => 'form-control']);

        $this->form->close('Update');
        return $this->render();
    }
}
```

Notes
- `$this->form->open($path = false, $method = false, $type = false, $file = false)`
  - When `$path` is false, it auto-derives from current route: `.create` -> `.store`, `.edit` -> `.update`.
  - `$type` supports `route`, `url`, or `action` (auto-detected if omitted).
  - Set `$file = true` for multipart forms.
- `$this->form->model($model = null, $row_selected = false, $path = false, $file = false, $type = false)`
  - When `$model` is null, it uses controller’s `$this->model` or `$this->model_class` already set by `parent::__construct()` or `$this->model(Class::class)`.
  - If `$row_selected` is false and route is edit/show, it infers the current `id` from URL.
  - To enable file upload, pass `$file = true` or use `$this->form->modelWithFile()`.
- Close with submit button: `$this->form->close('Save', ['class' => 'btn btn-success'])`.

## Common Tasks

### Open/Close via Facade (alternative)

```php
use Canvastack\\Canvastack\\Library\\Components\\Utility\\Canvatility;

echo Canvatility::formOpen(['url' => route('users.store'), 'method' => 'POST', 'class' => 'form-horizontal']);
// ... fields ...
echo Canvatility::formClose();
```

### Labels and Inputs via Facade

```php
// Label (escaped by default)
echo Canvatility::formLabel('name', 'Full Name');

// Text input with attributes
echo Canvatility::formText('name', old('name'), ['class' => 'form-control', 'placeholder' => 'Your name']);

// Password and submit
echo Canvatility::formPassword('password', ['class' => 'form-control']);
echo Canvatility::formSubmit('Create User', ['class' => 'btn btn-primary']);
```

### Alerts

```php
// $type: success | info | warning | danger
echo Canvatility::formAlertMessage('Saved successfully', 'success', 'Success', 'fa-check');
```

### Select Boxes

```php
$options = [1 => 'Admin', 2 => 'Editor', 3 => 'User'];
echo Canvatility::formSelectbox('role_id', $options, 2, ['class' => 'form-control'], true, [null => 'Select role']);
```

### Checkboxes (quick list)

```php
echo Canvatility::formCheckList('newsletter', 1, 'Subscribe', true, 'success', 'newsletter');
```

### Header/Content Tabs

```php
$header = [
  'Profile' => 'tab-profile',
  'Security' => 'tab-security',
];

echo Canvatility::formCreateHeaderTab($header, 'tab', 'Profile');
echo Canvatility::formCreateContentTab('<p>Profile form here</p>', 'tab-profile', true);
echo Canvatility::formCreateContentTab('<p>Security form here</p>', 'tab-security');
```

### Icon Attributes for Inputs

```php
// Returns an array with icon data used internally by FormUi
\Canvastack\\Canvastack\\Library\\Components\\Utility\\Canvatility::formIconAttributes('fa-user', ['class' => 'form-control'], 'left');
```

### Attribute manipulation

```php
$attrs = Canvatility::formChangeInputAttribute(['class' => 'form-control'], 'class', 'form-control is-invalid');
```

## Validation Integration

- Use standard Laravel validation. Display errors near fields as usual, or render a top-level alert using `formAlertMessage()`.

Example in Blade:

```blade
@if ($errors->any())
  {!! Canvatility::formAlertMessage('Please fix the errors below.', 'danger', 'Validation Error', 'fa-exclamation-triangle') !!}
@endif
```

## Full Example (Create User view using Facade)

```php
@extends('layouts.app')
@section('content')
  {!! Canvatility::formOpen(['route' => 'users.store']) !!}
    <div class="mb-3">
      {!! Canvatility::formLabel('name', 'Full Name') !!}
      {!! Canvatility::formText('name', old('name'), ['class' => 'form-control']) !!}
    </div>
    <div class="mb-3">
      {!! Canvatility::formLabel('email', 'Email') !!}
      {!! Canvatility::formText('email', old('email'), ['class' => 'form-control']) !!}
    </div>
    <div class="mb-3">
      {!! Canvatility::formLabel('password', 'Password') !!}
      {!! Canvatility::formPassword('password', ['class' => 'form-control']) !!}
    </div>
    {!! Canvatility::formSubmit('Create', ['class' => 'btn btn-primary']) !!}
  {!! Canvatility::formClose() !!}
@endsection
```

## API Reference (Selected)

- `$this->form->open($path = false, $method = false, $type = false, $file = false)`
- `$this->form->model($model = null, $row_selected = false, $path = false, $file = false, $type = false)`
- `$this->form->modelWithFile(...)`
- `$this->form->close($action_buttons = false, $option_buttons = false, $prefix = false, $suffix = false)`
- `$this->form->token()`
- Field builders via traits: `text()`, `select()`, `radio()`, `check()`, `password()` etc.

Facade alternatives:
- `Canvatility::formOpen(array $attributes = []): string`
- `Canvatility::formClose(): string`
- `Canvatility::formLabel($name, $value = null, $options = [], $escape_html = true): string`
- `Canvatility::formText($name, $value = null, $options = []): string`
- `Canvatility::formPassword($name, $options = []): string`
- `Canvatility::formSubmit($value = null, $options = []): string`
- `Canvatility::formButton(...) : string`
- `Canvatility::formSelectbox(...)`
- `Canvatility::formCheckList(...)`
- `Canvatility::formAlertMessage(...)`
- `Canvatility::formCreateHeaderTab(...)`
- `Canvatility::formCreateContentTab(...)`
- `Canvatility::formChangeInputAttribute(...)`

For advanced customization, see `src/Library/Components/Utility/Html/FormUi.php` and `src/Library/Components/Form/Objects.php`.