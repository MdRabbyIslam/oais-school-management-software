@extends('layouts.app')

@section('title', 'Class Test Reports')
@section('content_header_title', 'Class Test Reports')
@section('content_header_subtitle', 'Filter & Print')

@section('content_body')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filter Criteria</h3>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form id="class-test-report-form" method="GET">
            <div class="form-row">
                <div class="form-group col-md-3">
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
                <div class="form-group col-md-3">
                    <label>Term</label>
                    <select name="term_id" id="term_id" class="form-control">
                        <option value="">All Terms</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" data-academic-year-id="{{ $term->academic_year_id }}" {{ (string) request('term_id') === (string) $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Class</label>
                    <select name="class_id" id="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Subject</label>
                    <select name="subject_id" id="subject_id" class="form-control">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ (string) request('subject_id') === (string) $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Student (for Single Student report)</label>
                    <select name="student_enrollment_id" id="student_enrollment_id" class="form-control">
                        <option value="">Select Student</option>
                        @foreach($students as $enrollment)
                            <option value="{{ $enrollment->id }}" {{ (string) request('student_enrollment_id') === (string) $enrollment->id ? 'selected' : '' }}>
                                {{ ($enrollment->roll_number ?? '-') . ' - ' . ($enrollment->student->name ?? ('Student #' . $enrollment->student_id)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button
                type="submit"
                class="btn btn-info"
                formaction="{{ route('class-tests.reports.index') }}"
                formmethod="GET"
            >
                Apply Filters
            </button>
            <button
                type="submit"
                class="btn btn-primary"
                formaction="{{ route('class-tests.reports.print-all-students') }}"
                formmethod="GET"
                formtarget="_blank"
            >
                Print All Students
            </button>
            <button
                id="printSingleStudentBtn"
                type="submit"
                class="btn btn-success"
                formaction="{{ route('class-tests.reports.print-single-student') }}"
                formmethod="GET"
                formtarget="_blank"
            >
                Print Single Student
            </button>
            <a href="{{ route('class-tests.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<script>
    (function () {
        const academicYearSelect = document.getElementById('academic_year_id');
        const termSelect = document.getElementById('term_id');
        const studentSelect = document.getElementById('student_enrollment_id');
        const printSingleStudentBtn = document.getElementById('printSingleStudentBtn');
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

        function toggleSingleStudentPrintButton() {
            printSingleStudentBtn.disabled = !studentSelect.value;
        }

        studentSelect.addEventListener('change', toggleSingleStudentPrintButton);
        toggleSingleStudentPrintButton();
    })();
</script>
@endsection
