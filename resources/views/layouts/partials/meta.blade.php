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

{{-- Vite CSS --}}
@vite(['resources/css/app.css'])

{{-- jQuery (required for DataTables) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

{{-- DataTables CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">

{{-- DataTables JS --}}
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>

{{-- DataTables Buttons Extension (Phase 8: P2 Features - Export Buttons) --}}
<script src="https://cdn.datatables.net/buttons/3.0.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.0/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.0/css/buttons.dataTables.min.css">

{{-- Flatpickr (Date Picker for Filter Modal) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

{{-- Additional Head Content --}}
@stack('head')

{{-- Additional Styles --}}
@stack('styles')

{{-- Custom Styles --}}
<style>
    .gradient-text {
        background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .gradient-bg {
        background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
    }
    .sidebar-transition {
        transition: width 0.3s ease, margin-left 0.3s ease;
    }
    
    /* Fixed Columns Styling (Phase 4) */
    .dtfc-fixed-left,
    .dtfc-fixed-right {
        background-color: white;
        border-right: 2px solid #e5e7eb;
        z-index: 10;
    }

    .dark .dtfc-fixed-left,
    .dark .dtfc-fixed-right {
        background-color: #1f2937;
        border-right: 2px solid #374151;
    }

    /* Fixed column borders */
    .dtfc-fixed-left table.dataTable thead th,
    .dtfc-fixed-left table.dataTable tbody td {
        border-right: 1px solid #e5e7eb;
        background-color: white;
    }

    .dark .dtfc-fixed-left table.dataTable thead th,
    .dark .dtfc-fixed-left table.dataTable tbody td {
        border-right: 1px solid #374151;
        background-color: #1f2937;
    }

    .dtfc-fixed-right table.dataTable thead th,
    .dtfc-fixed-right table.dataTable tbody td {
        border-left: 1px solid #e5e7eb;
        background-color: white;
    }

    .dark .dtfc-fixed-right table.dataTable thead th,
    .dark .dtfc-fixed-right table.dataTable tbody td {
        border-left: 1px solid #374151;
        background-color: #1f2937;
    }

    /* Scrollable container */
    .dataTables_scroll {
        overflow-x: auto;
    }

    .dataTables_scrollBody {
        overflow-x: auto !important;
        overflow-y: auto !important;
    }
    
    /* Fixed columns hover effect */
    .dtfc-fixed-left tbody tr:hover td,
    .dtfc-fixed-right tbody tr:hover td {
        background-color: #f9fafb !important;
    }
    
    .dark .dtfc-fixed-left tbody tr:hover td,
    .dark .dtfc-fixed-right tbody tr:hover td {
        background-color: #111827 !important;
    }
    
    /* DataTables Buttons Dark Mode (Phase 8: P2 Features) */
    .dark .dt-buttons {
        background: rgb(31 41 55);
        border-color: rgb(55 65 81);
    }

    .dark .dt-button {
        background: rgb(55 65 81) !important;
        color: rgb(243 244 246) !important;
        border-color: rgb(75 85 99) !important;
    }

    .dark .dt-button:hover {
        background: rgb(75 85 99) !important;
        border-color: rgb(107 114 128) !important;
    }
    
    /* Export buttons styling */
    .dt-buttons {
        margin-bottom: 0;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .dt-button {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    
    .dt-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    /* DataTables Header Sort Icon Alignment */
    table.dataTable thead th {
        vertical-align: middle !important;
    }
    
    table.dataTable thead th > div {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    table.dataTable thead th > div i,
    table.dataTable thead th > div svg {
        flex-shrink: 0;
        vertical-align: middle;
    }
    
    /* DataTables sorting icons - force vertical center */
    .dt-column-order {
        display: inline-flex !important;
        align-items: center !important;
        vertical-align: middle !important;
        margin-left: 0.25rem;
        line-height: 1 !important;
    }
    
    /* DataTables sorting icon wrapper */
    table.dataTable thead .dt-orderable-asc,
    table.dataTable thead .dt-orderable-desc,
    table.dataTable thead .dt-ordering-asc,
    table.dataTable thead .dt-ordering-desc {
        display: inline-flex !important;
        align-items: center !important;
        vertical-align: middle !important;
    }
    
    /* Fix for DataTables 2.0 sort icons */
    table.dataTable thead th.dt-orderable-asc span.dt-column-order,
    table.dataTable thead th.dt-orderable-desc span.dt-column-order,
    table.dataTable thead th.dt-ordering-asc span.dt-column-order,
    table.dataTable thead th.dt-ordering-desc span.dt-column-order {
        position: relative;
        top: 0 !important;
        vertical-align: middle !important;
    }
    
    /* Fallback: Use transform to center if flexbox doesn't work */
    .dt-column-order:before,
    .dt-column-order:after {
        vertical-align: middle !important;
        transform: translateY(0) !important;
    }
</style>
