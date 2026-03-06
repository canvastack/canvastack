{{--
    TanStack Table Wrapper with Theme Injection
    
    This wrapper ensures theme CSS is injected before the table component renders.
    It provides the @themeInject directive for complete theme integration.
    
    @var array $config - Table configuration
    @var array $columns - Column definitions
    @var array $data - Initial data (for client-side mode)
    @var array $alpineData - Alpine.js state configuration
    @var string $tableId - Unique table identifier
--}}

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app('canvastack.rtl')->getDirection() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {{-- Theme Injection (MANDATORY for Theme Engine Compliance) --}}
    @themeInject
    
    {{-- Additional table-specific styles --}}
    @stack('table-styles')
</head>
<body>
    {{-- Include the main TanStack table component --}}
    @include('canvastack::components.table.tanstack', [
        'config' => $config,
        'columns' => $columns,
        'data' => $data ?? [],
        'alpineData' => $alpineData,
        'tableId' => $tableId,
    ])
    
    {{-- Additional table-specific scripts --}}
    @stack('table-scripts')
</body>
</html>
