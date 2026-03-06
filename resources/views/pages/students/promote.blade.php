@extends('layouts.app')
@section('plugins.Select2', true)

@section('title', 'Promote Student')

@section('content_header_title', 'Students')
@section('content_header_subtitle', 'Promote')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Promote Student: {{ $student->name }}</h3>
        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-sm btn-secondary">Back</a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('students.promote.store', $student->id) }}">
            @csrf

            <div class="form-group">
                <label for="from_enrollment_id">From Enrollment</label>
                <select name="from_enrollment_id" id="from_enrollment_id" class="form-control select2" required>
                    @if($currentEnrollment)
                        <option value="{{ $currentEnrollment->id }}">{{ $currentEnrollment->schoolClass->name ?? $currentEnrollment->class_id }} / {{ $currentEnrollment->section->section_name ?? $currentEnrollment->section_id }} (Active)</option>
                    @else
                        <option value="">-- No active enrollment --</option>
                    @endif
                </select>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="target_academic_year_id">Target Academic Year</label>
                    <select name="target_academic_year_id" id="target_academic_year_id" class="form-control select2" required>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <label for="target_class_id">Target Class</label>
                    <select name="target_class_id" id="target_class_id" class="form-control select2" required>
                        <option value="">Select class…</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <label for="target_section_id">Target Section</label>
                    <select name="target_section_id" id="target_section_id" class="form-control select2" required disabled>
                        <option value="">Select section…</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="reason">Reason (optional)</label>
                <textarea name="reason" id="reason" class="form-control"></textarea>
            </div>

            <button class="btn btn-primary">Request Promotion</button>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
    const allSections = @json($sections ?? []);
    $('#from_enrollment_id, #target_academic_year_id, #target_class_id, #target_section_id').select2({ placeholder: 'Select…', allowClear: true });

    $('#target_class_id').on('change', function(){
        const classId = $(this).val();
        const filtered = allSections.filter(s => s.class_id == classId);
        const $sec = $('#target_section_id').empty().trigger('change');
        $sec.append(new Option('Select section…', '', true, false));
        filtered.forEach(s => {
            const opt = new Option(s.section_name, s.id, false, false);
            $sec.append(opt);
        });
        $sec.prop('disabled', !classId).trigger('change');
    });
</script>
@endsection
