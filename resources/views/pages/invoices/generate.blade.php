@extends('layouts.app')

@section('plugins.Select2', true)
@section('subtitle', 'Generate Invoices')
@section('content_header_title', 'Run Invoice Generation')
@section('content_header_subtitle')
    Use this form to invoke the "invoices:generate" command from the UI.
@endsection

@section('content_body')
    {{-- success / error messages --}}
    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- validation errors --}}
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('invoices-generate.run') }}">
      @csrf
      <div class="card">
        <div class="card-header">Invoice Generation Options</div>
        <div class="card-body">
          <div class="row g-3">
            {{-- Student --}}
            <div class="col-md-4">
              <label for="student" class="form-label">Student (optional)</label>
              <select name="student"
                      id="student"
                      class="form-select form-control select2"
                      data-placeholder="— All Students —">
                <option value=""></option>
                @foreach($students as $stu)
                  <option value="{{ $stu->id }}" {{ old('student')==$stu->id?'selected':'' }}>
                    {{ $stu->student_id }} – {{ $stu->name }} - {{ $stu->schoolClass->name }} - {{ $stu->section->section_name }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Period --}}
            <div class="col-md-4">
              <label for="period" class="form-label">Period (optional)</label>
              <select name="period" id="period" class="form-select form-control">
                <option value="">— All Frequencies —</option>
                <option value="monthly" {{ old('period')=='monthly'?'selected':'' }}>Monthly</option>
                <option value="termly"  {{ old('period')=='termly' ?'selected':'' }}>Termly</option>
                <option value="annual"  {{ old('period')=='annual' ?'selected':'' }}>Annual</option>
              </select>
            </div>

            {{-- Month --}}
            <div class="col-md-4">
              <label for="month" class="form-label">Month (YYYY-MM) (optional)</label>
              <input type="month"
                     id="month"
                     name="month"
                     class="form-control"
                     value="{{ old('month') }}">
            </div>

          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-play-circle me-1"></i> Run Generate Command
            </button>
          </div>
        </div>
      </div>
    </form>
@endsection

@section('js')
<script>
    $(function() {
        // Initialize Select2 on student dropdown
        $('#student').select2({
            placeholder: $('#student').data('placeholder'),
            allowClear: true,
            width: '100%'
        });
    });
</script>
@endsection
