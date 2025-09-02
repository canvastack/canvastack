@extends('default.template.admin.index')

@section('content')
<div class="container mt-4">
  <h1>HybridCompare Runner</h1>
  <p class="text-muted">Pilih named route untuk dieksekusi dalam mode <code>hybrid</code>. Jika ragu, gunakan default <code>system.accounts.user</code>.</p>

  @if ($errors->any())
    <div class="alert alert-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ route('modules.development.hybrid_compare.run') }}">
    @csrf
    <div class="form-group mb-3">
      <label for="route_name">Route name</label>
      <select id="route_name" name="route_name" class="form-control">
        @foreach ($routes as $name)
          <option value="{{ $name }}" {{ $default === $name ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Run HybridCompare</button>
    <a href="{{ route('modules.development.hybrid_compare') }}" class="btn btn-secondary ms-2">Reset</a>
    <a href="{{ url('/artisan/hybrid/run?route=' . urlencode($default)) }}" class="btn btn-outline-info ms-2" onclick="event.preventDefault(); document.getElementById('run-cli-form').submit();">Run via CLI (simulate)</a>
  </form>
  <form id="run-cli-form" method="POST" action="{{ route('modules.development.hybrid_compare.run') }}" class="d-none">
    @csrf
    <input type="hidden" name="route_name" value="{{ $default }}">
  </form>

  @if ($result)
  <div class="card mt-4">
    <div class="card-body">
      <h5 class="card-title">Result</h5>
      <p><strong>Route</strong>: <code>{{ $result['route'] }}</code></p>
      <p><strong>Status</strong>: <code>{{ $result['status'] }}</code></p>
      <p><strong>Note</strong>: {{ $result['note'] }}</p>
      <hr>
      <p class="text-muted">Hints:</p>
      <ul>
        <li>Cek logs: <code>[DT Hybrid] Diff</code> dan <code>[DT HybridCompare] Preflight</code>.</li>
        <li>Inspector JSON: <code>storage/app/datatable-inspector/*.json</code></li>
        <li>Atur env gate: <code>CANVASTACK_PIPELINE_GATE=off|soft|strict</code> untuk CI Gate.</li>
      </ul>
    </div>
  </div>
  @endif
</div>
@endsection