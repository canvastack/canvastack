@extends('default.template.admin.index')

@section('content')
<div class="container">
  <h2>Table Preview: {{ $title }}</h2>
  <div class="card p-3">
    {!! $tableHtml !!}
  </div>
</div>

<script>
// Lightweight DataTables init for preview
// - Always enable processing and deferRender to reduce initial render cost
// - If table provides data-server-side and data-ajax-url, enable serverSide mode
(function() {
  function init() {
    var table = document.querySelector('div.card table');
    if (!table || typeof window.jQuery === 'undefined') return;
    var $ = window.jQuery;
    if (!$.fn || !$.fn.DataTable) return;

    var options = { processing: true, deferRender: true };
    var isServer = table.getAttribute('data-server-side') === '1';
    if (isServer) {
      options.serverSide = true;
      var ajaxUrl = table.getAttribute('data-ajax-url');
      if (ajaxUrl) options.ajax = ajaxUrl;
    }

    // Guard against double-init
    if (!$.fn.dataTable.isDataTable(table)) {
      $(table).DataTable(options);
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
@endsection