@extends('layouts.app')
@section('plugins.Select2', true)
@section('title', 'Send SMS')

@section('content_header_title', 'SMS')
@section('content_header_subtitle', 'Send Custom SMS')

@section('content_body')
<div class="card">
    <div class="card-body">

        {{-- success / error messages --}}
        @if (session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('sms.custom') }}">
            @csrf

            <div class="form-group">
            <label>Recipient Type</label>
            <select id="recipient_type" name="recipient_type"
                    class="form-control @error('recipient_type') is-invalid @enderror">
                <option value="all"     {{ old('recipient_type')=='all'?'selected':'' }}>All Students</option>
                <option value="student" {{ old('recipient_type')=='student'?'selected':'' }}>Single Student</option>
                <option value="section" {{ old('recipient_type')=='section'?'selected':'' }}>Class/Section</option>
                <option value="custom"  {{ old('recipient_type')=='custom'?'selected':'' }}>Custom Numbers</option>
            </select>
            @error('recipient_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-12 d-none " id="group-student">
                    <label  class="form-label" for="student_id"> Select Student</label>

                        <select id="student_id" name="student_id"
                            class="form-select form-control select2 @error('student_id') is-invalid @enderror"></select>
                    @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror

                </div>

                <div class="col-12 d-none" id="group-section">
                    <label  class="form-label" for="section_id">Select Class/Section</label>
                    <select id="section_id" name="section_id"
                            class="form-select form-control select2 @error('section_id') is-invalid @enderror"></select>
                    @error('section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>

            <div class=" d-none" id="group-custom">
                <label>Custom Numbers (comma‐separated)</label>
                <input id="custom_numbers" type="text" name="custom_numbers"
                    class="form-control @error('custom_numbers') is-invalid @enderror"
                    placeholder="88017XXXXXXXX,88018XXXXXXXX" value="{{ old('custom_numbers') }}">
                @error('custom_numbers')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
            <label>Message</label>
            <textarea name="message" rows="4"
                class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary">Send SMS</button>
        </form>
    </div>
</div>
@endsection


@section('js')
<script>
  function toggleGroups() {
    const type = $('#recipient_type').val();
    $('#group-student,#group-section,#group-custom').addClass('d-none');
    if(type==='student')  $('#group-student').removeClass('d-none');
    if(type==='section')  $('#group-section').removeClass('d-none');
    if(type==='custom')   $('#group-custom').removeClass('d-none');
  }

  $(function(){
    // init select2
    $('#student_id').select2({
      placeholder: 'Type to search…',
      width: '100%',
      ajax: {
        url: '/api/students',
        dataType: 'json',
        delay: 250,
        data: params => ({ q: params.term }),
        processResults: data => data
      }
    });

    $('#section_id').select2({
      placeholder: 'Type to search…',
      width: '100%',
      ajax: {
        url: '/api/sections',
        dataType: 'json',
        delay: 250,
        data: params => ({ q: params.term }),
        processResults: data => data
      }
    });

    $('#recipient_type').on('change', toggleGroups);
    toggleGroups();
  });
</script>
@endsection
