@extends('layouts.app')

@section('title', 'Student Extra Marks')
@section('content_header_title', 'Student Extra Marks')
@section('content_header_subtitle', 'Term Wise Entry')

@section('content_body')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filter</h3>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="GET" action="{{ route('student-term-extra-marks.index') }}" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col-md-3">
                    <label>Academic Year</label>
                    <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                        <option value="">Select Academic Year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ (string) request('academic_year_id') === (string) $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Term</label>
                    <select name="term_id" id="term_id" class="form-control" required>
                        <option value="">Select Term</option>
                        @foreach($terms as $term)
                            <option
                                value="{{ $term->id }}"
                                data-academic-year-id="{{ $term->academic_year_id }}"
                                {{ (string) request('term_id') === (string) $term->id ? 'selected' : '' }}
                            >
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Class</label>
                    <select name="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-info">Load Students</button>
                </div>
            </div>
        </form>

        @if(request()->filled('academic_year_id') && request()->filled('term_id') && request()->filled('class_id'))
            <form method="POST" action="{{ route('student-term-extra-marks.store') }}">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
                <input type="hidden" name="term_id" value="{{ request('term_id') }}">
                <input type="hidden" name="class_id" value="{{ request('class_id') }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Roll</th>
                                <th>Student</th>
                                <th>Homework Marks</th>
                                <th>Attendance Marks</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($enrollments as $i => $enrollment)
                                @php($existing = $existingMarks->get($enrollment->id))
                                <tr>
                                    <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                    <td>{{ $enrollment->student->name ?? 'Student #' . $enrollment->student_id }}</td>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][student_enrollment_id]" value="{{ $enrollment->id }}">
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="rows[{{ $i }}][homework_marks]"
                                            class="form-control form-control-sm"
                                            value="{{ old("rows.$i.homework_marks", optional($existing)->homework_marks) }}"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="rows[{{ $i }}][attendance_marks]"
                                            class="form-control form-control-sm"
                                            value="{{ old("rows.$i.attendance_marks", optional($existing)->attendance_marks) }}"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            maxlength="255"
                                            name="rows[{{ $i }}][remarks]"
                                            class="form-control form-control-sm"
                                            value="{{ old("rows.$i.remarks", optional($existing)->remarks) }}"
                                        >
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No active students found for selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($enrollments->isNotEmpty())
                    <button type="submit" class="btn btn-success">Save Extra Marks</button>
                @endif
            </form>
        @endif
    </div>
</div>

<script>
    (function () {
        const academicYearSelect = document.getElementById('academic_year_id');
        const termSelect = document.getElementById('term_id');
        const termOptions = Array.from(termSelect.querySelectorAll('option[value]'));
        const oldTerm = "{{ request('term_id') }}";

        function resetSelect(select) {
            select.value = '';
        }

        function filterTermsByYear(academicYearId) {
            termOptions.forEach((option) => {
                const show = academicYearId && option.dataset.academicYearId === academicYearId;
                option.hidden = !show;
                option.disabled = !show;
            });
        }

        academicYearSelect.addEventListener('change', function () {
            filterTermsByYear(this.value);
            resetSelect(termSelect);
        });

        filterTermsByYear(academicYearSelect.value);
        if (oldTerm) {
            const selected = termSelect.querySelector(`option[value="${oldTerm}"]`);
            if (selected && !selected.disabled) {
                termSelect.value = oldTerm;
            }
        }
    })();
</script>
@endsection

