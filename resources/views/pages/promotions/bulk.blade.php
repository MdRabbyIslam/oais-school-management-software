@extends('layouts.app')
@section('plugins.Select2', true)
@section('title', 'Bulk Promotions')

@section('content_header_title', 'Bulk Promotions')
@section('content_header_subtitle', 'Create promotion requests for multiple students')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Bulk Promotion Requests</h3>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col-md-10">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="source_academic_year_id">Source Academic Year</label>
                            <select name="source_academic_year_id" id="source_academic_year_id" class="form-control select2">
                                <option value="">Select Year</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ request('source_academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="source_class_id">Source Class</label>
                            <select name="source_class_id" id="source_class_id" class="form-control select2">
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('source_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="source_section_id">Source Section</label>
                            <select name="source_section_id" id="source_section_id" class="form-control select2" {{ request('source_class_id') ? '' : 'disabled' }}>
                                <option value="">All Sections</option>
                            </select>
                        </div>

                        <div class="form-group col-md-3 d-flex" style="gap:10px">
                            <button type="submit" class="btn btn-primary">Show Students</button>
                            <a href="{{ route('promotions.bulk.form') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if($enrollments)
            <form method="POST" action="{{ route('promotions.bulk.store') }}">
                @csrf
                <div class="mb-3">
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="target_academic_year_id">Target Academic Year</label>
                            <select name="target_academic_year_id" id="target_academic_year_id" class="form-control select2" required>
                                <option value="">Select Year</option>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="target_class_id">Target Class</label>
                            <select name="target_class_id" id="target_class_id" class="form-control select2" required>
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="target_section_id">Target Section</label>
                            <select name="target_section_id" id="target_section_id" class="form-control select2" disabled required>
                                <option value="">Select Section</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select_all"></th>
                                <th>Enrollment ID</th>
                                <th>Student</th>
                                <th>Class / Section</th>
                                <th>Roll</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($enrollments as $e)
                                <tr>
                                    <td><input type="checkbox" name="selected_enrollments[]" value="{{ $e->id }}"></td>
                                    <td>{{ $e->id }}</td>
                                    <td>{{ $e->student->student_id }} — {{ $e->student->name }}</td>
                                    <td>{{ $e->schoolClass->name ?? '-' }} / {{ $e->section->section_name ?? '-' }}</td>
                                    <td>{{ $e->roll_number ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">No enrollments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button class="btn btn-success" onclick="return confirm('Create promotion requests for selected students?')">Create Promotion Requests</button>
                    <a href="{{ route('promotions.index') }}" class="btn btn-secondary">Back to Promotions</a>
                </div>
            </form>
        @endif
    </div>
</div>
@stop

@section('js')
<script>
    const allSections = @json($sections);
    $('#source_class_id, #source_section_id, #target_class_id, #target_section_id, #source_academic_year_id, #target_academic_year_id').select2({ placeholder: 'Select…', allowClear: true });

    function populateSections(classSelector, sectionSelector) {
        const classId = $(classSelector).val();
        const filtered = allSections.filter(s => s.class_id == classId);
        const $sec = $(sectionSelector).empty().trigger('change');
        $sec.append(new Option('All Sections', '', true, false));
        filtered.forEach(s => {
            const opt = new Option(s.section_name, s.id, false, false);
            $sec.append(opt);
        });
        $sec.prop('disabled', !classId).trigger('change');
    }

    $('#source_class_id').on('change', function(){ populateSections('#source_class_id', '#source_section_id'); });
    $('#target_class_id').on('change', function(){ populateSections('#target_class_id', '#target_section_id'); });

    $('#select_all').on('change', function(){ $('input[name="selected_enrollments[]"]').prop('checked', $(this).is(':checked')); });
</script>
@endsection
