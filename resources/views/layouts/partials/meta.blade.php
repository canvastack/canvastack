{{-- Meta Tags Block --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Page Title --}}
<title>@yield('title', config('app.name', 'CanvaStack')) - Admin Panel</title>

{{-- Favicon --}}
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />

{{-- Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

{{-- Vite CSS & JS --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- CanvaStack Main CSS --}}
{{-- Note: CanvaStack CSS loaded via <link> tag in meta.blade.php --}}
{{-- Note: Layout styles (gradient, filter modal, etc.) loaded via <link> tag --}}
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack.css') }}">

{{-- CanvaStack Layout CSS (gradient, filter modal, etc.) --}}
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/canvastack-layout.css') }}">

{{-- Conditional DataTables Loading - Only load if NOT using TanStack engine --}}
@php
    \Log::info('Meta.blade.php (VENDOR): $table_engine = ' . (isset($table_engine) ? $table_engine : 'NOT SET'));
@endphp
@if(!isset($table_engine) || $table_engine !== 'tanstack')
{{-- jQuery (required for DataTables) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

{{-- DataTables CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">

{{-- DataTables JS --}}
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>

{{-- DataTables Buttons Extension (compatible with DataTables 2.0) --}}
<script src="https://cdn.datatables.net/buttons/3.0.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.0/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.0/css/buttons.dataTables.min.css">
@endif

{{-- Flatpickr (Date Picker for Filter Modal) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

{{-- Additional Head Content --}}
@stack('head')

{{-- Additional Styles --}}
@stack('styles')

{{-- DataTables Specific Styles - Only load if NOT using TanStack engine --}}
@if(!isset($table_engine) || $table_engine !== 'tanstack')
<link rel="stylesheet" href="{{ asset('vendor/canvastack/css/datatables-custom.css') }}">
@endif
