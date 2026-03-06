@extends('layouts.app')

@section('subtitle', 'SMS Logs')
@section('content_header_title', 'SMS Logs')

@section('content_body')
<div class="card">
    {{-- show error or suceess message --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    @endif
  <div class="card-header">
    <form method="GET" class="form-inline">
      <div class="form-group mr-2">
        <label class="mr-1">From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="form-control form-control-sm">
      </div>
      <div class="form-group mr-2">
        <label class="mr-1">To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="form-control form-control-sm">
      </div>
      <div class="form-group mr-2">
        <label class="mr-1">Status</label>
        <select name="status" class="form-control form-control-sm">
          <option value="">All</option>
          <option value="success" {{ request('status')=='success'?'selected':'' }}>Success</option>
          <option value="error"   {{ request('status')=='error'  ?'selected':'' }}>Error</option>
        </select>
      </div>
      <button class="btn btn-sm btn-primary mr-2">Filter</button>
      <a href="{{ route('sms.logs') }}" class="btn btn-sm btn-secondary">Clear</a>
    </form>
  </div>

  <form method="POST" action="{{ route('sms.resend') }}">
    @csrf
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th style="width:30px">
              <input type="checkbox" id="select-all">
            </th>
            <th>Time</th>
            <th>Student</th>
            <th>To</th>
            <th>Message</th>
            <th>Status</th>
            <th>Response</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
            <tr>
              <td>
                <input type="checkbox" name="selected[]" value="{{ $log->id }}">
              </td>
              <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
              <td>{{ optional($log->student)->name ?? '—' }}</td>
              <td>{{ $log->to }}</td>
              <td>{{ $log->message }}</td>
              <td>
                @if($log->status==='success')
                  <span class="badge badge-success">Success</span>
                @else
                  <span class="badge badge-danger">Error</span>
                @endif
              </td>
              <td style="max-width:200px;overflow:auto">{{ $log->response }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted">No SMS logs found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <button type="submit" class="btn btn-sm btn-warning"
              onclick="return confirm('Resend selected SMS?')">
        Resend Selected
      </button>
      {{ $logs->links() }}
    </div>
  </form>
</div>
@endsection

@section('js')
<script>
  // Toggle all checkboxes
  document.getElementById('select-all').addEventListener('change', function() {
    document.querySelectorAll('input[name="selected[]"]').forEach(cb => {
      cb.checked = this.checked;
    });
  });
</script>
@endsection
