{{--
    downscripts.blade.php — Bootstrap 4 Bottom JavaScript Loading Example
    ======================================================================
    Template: default (Bootstrap 4)
    Location: resources/views/default/template/admin/block/downscripts.blade.php

    Renders all bottom-position JavaScript <script> tags.

    Asset loading order:
      1. bottom_first.js → Plugin libraries (Chosen.js, Flatpickr, MetisMenu, etc.)
                           These must load BEFORE app scripts that initialize them.
      2. bottom.js       → Core app scripts (canvastackscripts.js, etc.)
      3. bottom_last.js  → Final scripts (modal adapter, tooltip adapter, custom init)
                           These load LAST to ensure all dependencies are ready.

    All script tags are pre-rendered by the Template component based on
    config/canvastack.templates.php. You do not write <script> tags here directly.

    Configured in: config/canvastack.templates.php
    Keys:
      - default.position.bottom.first.js
      - default.position.bottom.js       (if present)
      - default.position.bottom.last.js
--}}
<?php
// Collect bottom-position JS from the Template component
// Each item is a pre-rendered <script> tag object with a ->html property
$scripts = [
    'bottom_first' => $components->template->scripts['js']['bottom_first'] ?? [],
    'bottom'       => $components->template->scripts['js']['bottom']       ?? [],
    'bottom_last'  => $components->template->scripts['js']['bottom_last']  ?? [],
];
?>

{{--
    Plugin libraries — loaded first.
    For Bootstrap 4 default template, this includes:
    - Chosen.js (select enhancement)
    - Flatpickr (date picker)
    - MetisMenu (sidebar accordion)
    - SlimScroll (sidebar scrolling)
    - DataTables (if on a DataTables page)

    These must load before canvastackscripts.js which initializes them.
--}}
@foreach ($scripts['bottom_first'] as $script)
    {!! $script->html !!}
@endforeach

{{--
    Core app scripts — loaded after plugins.
    For Bootstrap 4 default template, this includes:
    - canvastackscripts.js (initializes Chosen.js, tooltips, etc.)
    - firscripts.js (first-run scripts)
    - delete-handler.js (delete confirmation modals)
    - filter.js (DataTables filter modal)
--}}
@foreach ($scripts['bottom'] as $script)
    {!! $script->html !!}
@endforeach

{{--
    Final scripts — loaded last.
    For Bootstrap 4 default template, this includes:
    - canvastack-modal-adapter.js (framework-agnostic modal API)
    - canvastack-tooltip-adapter.js (framework-agnostic tooltip API)
    - sidebar.js (sidebar toggle and collapse)
    - scripts.js (general UI interactions)

    These load last to ensure all plugins and app scripts are ready.
--}}
@foreach ($scripts['bottom_last'] as $script)
    {!! $script->html !!}
@endforeach
