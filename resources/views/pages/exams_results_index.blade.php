@extends('layouts.app')

@section('title', 'Published Results')
@section('content_header_title', 'Published Results')
@section('content_header_subtitle', $examAssessmentClass->examAssessment->name . ' - ' . $examAssessmentClass->schoolClass->name)

@section('content_body')
@php($assessmentStatus = $examAssessmentClass->examAssessment->status)
<div class="card">
    <div class="card-body py-2">
        <div class="form-row align-items-end">
            <div class="col-md-5">
                <label for="results_class_switch" class="mb-1">Switch Target Class</label>
                <select id="results_class_switch" class="form-control form-control-sm">
                    @foreach($assessmentClasses as $targetClass)
                        <option
                            value="{{ route('exam-assessment-classes.results.index', $targetClass) }}"
                            {{ $targetClass->id === $examAssessmentClass->id ? 'selected' : '' }}
                        >
                            {{ $targetClass->schoolClass->name ?? 'Class #' . $targetClass->class_id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7">
                @if($assessmentStatus === 'published')
                    <form method="POST" action="{{ route('exam-assessment-classes.results.publish', $examAssessmentClass) }}" class="d-inline"
                        onsubmit="return confirm('Publish or republish results for this class?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            {{ $examAssessmentClass->is_published ? 'Republish Results' : 'Publish Results' }}
                        </button>
                    </form>
                    <a href="{{ route('exam-assessment-classes.results.print-class', $examAssessmentClass) }}" target="_blank" class="btn btn-sm btn-dark">Print Results</a>
                    <a href="{{ route('exam-assessment-classes.results.download-class-pdf', $examAssessmentClass) }}" class="btn btn-sm btn-primary">Download Full PDF</a>
                @endif
                <a href="{{ route('exam-assessment-classes.marks.create', $examAssessmentClass) }}" class="btn btn-sm btn-info">Back To Marks</a>
                <a href="{{ route('exam-assessment-classes.setup.edit', $examAssessmentClass) }}" class="btn btn-sm btn-secondary">Back To Setup</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Result Sheet</h3>
        <span class="badge badge-info">
            Assessment: {{ strtoupper($assessmentStatus) }}
        </span>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if($assessmentStatus !== 'published')
            <div class="alert alert-info mb-3">
                This assessment is <strong>{{ strtoupper($assessmentStatus) }}</strong>. Set status to <strong>Published</strong> to enable result publishing and PDF download.
            </div>
        @endif
        @if(($draftClassTestsCount ?? 0) > 0)
            <div class="alert alert-warning mb-3">
                <strong>AV Warning:</strong> {{ $draftClassTestsCount }} class test(s) are still in <strong>Draft</strong> and will be excluded from AV in exam results.
                Set those class tests to <strong>Published</strong> or <strong>Locked</strong>, then republish results.
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Manual Override</th>
                        <th>Roll</th>
                        <th>Student</th>
                        <th>Total (Exam)</th>
                        <th>Total (Class Test)</th>
                        <th>HW</th>
                        <th>Attend.</th>
                        <th>Grand Total</th>
                        <th>%</th>
                        <th>GPA</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        @php($extraMarks = $extraMarksByEnrollment[$row->student_enrollment_id] ?? ['homework_marks' => 0, 'attendance_marks' => 0])
                        @php($splitTotals = $splitTotalsByEnrollment[$row->student_enrollment_id] ?? ['exam_total' => 0, 'class_test_total' => 0, 'grand_total' => 0])
                        @php($homeworkMarks = (float) ($extraMarks['homework_marks'] ?? 0))
                        @php($attendanceMarks = (float) ($extraMarks['attendance_marks'] ?? 0))
                        <tr>
                            <td>
                                {{ $row->effective_position ?? ($row->position ?? '-') }}
                                @if($row->manual_position !== null)
                                    <span class="badge badge-warning ml-1">Manual</span>
                                @endif
                            </td>
                            <td>
                                @if($assessmentStatus === 'published')
                                    <form method="POST" action="{{ route('exam-assessment-classes.results.update-position-override', [$examAssessmentClass, $row->studentEnrollment]) }}" class="form-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            type="number"
                                            name="manual_position"
                                            class="form-control form-control-sm mr-1"
                                            style="width: 80px;"
                                            min="1"
                                            max="{{ $results->total() }}"
                                            value="{{ $row->manual_position ?? '' }}"
                                            placeholder="Auto"
                                        >
                                        <button type="submit" class="btn btn-sm btn-outline-primary mr-1">Save</button>
                                        @if($row->manual_position !== null)
                                            <button type="submit" name="clear_manual_position" value="1" class="btn btn-sm btn-outline-secondary">Clear</button>
                                        @endif
                                    </form>
                                @else
                                    <span class="text-muted">Unavailable</span>
                                @endif
                            </td>
                            <td>{{ $row->studentEnrollment->roll_number ?? '-' }}</td>
                            <td>{{ $row->studentEnrollment->student->name ?? 'Student #' . $row->student_enrollment_id }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) ($splitTotals['exam_total'] ?? 0), 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) ($splitTotals['class_test_total'] ?? 0), 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($homeworkMarks, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format($attendanceMarks, 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) ($splitTotals['grand_total'] ?? 0), 2, '.', ''), '0'), '.') }}</td>
                            <td>{{ $row->percentage }}</td>
                            <td>{{ $row->gpa }}</td>
                            <td>{{ $row->final_grade }}</td>
                            <td>
                                <span class="badge {{ $row->is_pass ? 'badge-success' : 'badge-danger' }}">
                                    {{ $row->is_pass ? 'PASS' : 'FAIL' }}
                                </span>
                            </td>
                            <td>
                                @if($assessmentStatus === 'published')
                                    <a href="{{ route('exam-assessment-classes.results.show', [$examAssessmentClass, $row->studentEnrollment]) }}" class="btn btn-sm btn-warning">View</a>
                                    <a href="{{ route('exam-assessment-classes.results.print-student', [$examAssessmentClass, $row->studentEnrollment]) }}" target="_blank" class="btn btn-sm btn-dark">Print</a>
                                    <a href="{{ route('exam-assessment-classes.results.download', [$examAssessmentClass, $row->studentEnrollment]) }}" class="btn btn-sm btn-primary">PDF</a>
                                @else
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>Locked By Status</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="text-center">No results found. Publish results first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $results->links() }}</div>
    </div>
</div>

<script>
    (function () {
        const classSwitch = document.getElementById('results_class_switch');
        classSwitch?.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    })();
</script>
@endsection
