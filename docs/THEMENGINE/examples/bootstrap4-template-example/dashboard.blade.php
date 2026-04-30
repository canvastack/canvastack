{{--
    dashboard.blade.php — Bootstrap 4 Dashboard Page Example
    =========================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/pages/admin/dashboard.blade.php

    This page extends the admin layout and provides the dashboard content.
    It demonstrates:
    - Extending the admin layout
    - Bootstrap 4 card components
    - Bootstrap 4 grid system (row, col-*)
    - CanvaStack helper functions (tabs, alerts, selects)
    - DataTables integration
    - Bootstrap 4 specific classes

    Bootstrap 4 specific:
    - card (not panel — Bootstrap 3 used panel)
    - data-toggle="tab" for tab navigation
    - btn-xs for extra small buttons
    - pull-right for right-aligned elements
--}}

{{-- Extend the admin layout — resolved by View.php via canvastack_current_template() --}}
@extends('default.pages.admin')

@section('content')

{{-- ── Stats Row ──────────────────────────────────────────────────────── --}}
<div class="row">

    {{-- Stat card 1 --}}
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Users
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $stats['total_users'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-users fa-2x text-gray-300" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat card 2 --}}
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Active Sessions
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            {{ $stats['active_sessions'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-check-circle fa-2x text-gray-300" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
{{-- End stats row --}}

{{-- ── Flash Messages ─────────────────────────────────────────────────── --}}
{{--
    canvastack_form_alert_message() delegates to DefaultAdapter::renderAlertMessage()
    which produces Bootstrap 4 alert HTML with:
    - alert-block class (Bootstrap 4 specific)
    - data-dismiss="alert" (Bootstrap 4 specific)
--}}
@if (session('success'))
    {!! canvastack_form_alert_message(session('success'), 'success', 'Success', 'msg', false) !!}
@endif

@if (session('error'))
    {!! canvastack_form_alert_message(session('error'), 'danger', 'Error', 'msg', false) !!}
@endif

{{-- ── Tab Navigation Example ─────────────────────────────────────────── --}}
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Dashboard Tabs</h6>
    </div>
    <div class="card-body">

        {{--
            Tab headers — canvastack_form_create_header_tab() delegates to
            DefaultAdapter::renderTabHeader() which produces:
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#overview">Overview</a>
            </li>
        --}}
        <ul class="nav nav-tabs" role="tablist">
            {!! canvastack_form_create_header_tab('Overview',  'overview',  'overview',  false) !!}
            {!! canvastack_form_create_header_tab('Analytics', 'analytics', false,       false) !!}
            {!! canvastack_form_create_header_tab('Reports',   'reports',   false,       false) !!}
        </ul>

        {{-- Tab content panes --}}
        <div class="tab-content mt-3">

            {!! canvastack_form_create_content_tab('overview', 'overview', true) !!}
                <p>Overview content goes here.</p>
                <p>This tab is active by default.</p>
            </div>

            {!! canvastack_form_create_content_tab('analytics', 'analytics', false) !!}
                <p>Analytics content goes here.</p>
            </div>

            {!! canvastack_form_create_content_tab('reports', 'reports', false) !!}
                <p>Reports content goes here.</p>
            </div>

        </div>
    </div>
</div>

{{-- ── DataTable Example ───────────────────────────────────────────────── --}}
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
        {{-- btn-xs is Bootstrap 4 specific (use btn-sm in Bootstrap 5) --}}
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-xs">
            <i class="fa fa-plus" aria-hidden="true"></i> Add New
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            {{--
                canvastack_table_class() returns the table CSS class string
                from DefaultAdapter::getTableClass()
                = 'CanvaStack-table table animated fadeIn table-striped ...'
            --}}
            <table id="users-table"
                   class="{{ canvastack_table_class() }}"
                   style="width:100%"
                   aria-label="Users table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables with Bootstrap 4 styling
    $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("users.datatables") }}',
            type: 'POST',
            data: function(d) {
                // Include CSRF token for POST requests
                d._token = $('meta[name="csrf-token"]').attr('content');
            }
        },
        columns: [
            { data: 'id',      name: 'id',      width: '60px' },
            { data: 'name',    name: 'name' },
            { data: 'email',   name: 'email' },
            { data: 'role',    name: 'role' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        // Bootstrap 4 DataTables DOM layout
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
        }
    });
});
</script>
@endpush
