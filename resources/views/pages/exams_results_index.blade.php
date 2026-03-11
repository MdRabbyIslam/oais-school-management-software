@extends('layouts.app')

@section('title', 'Published Results')
@section('content_header_title', 'Published Results')
@section('content_header_subtitle', $examAssessmentClass->examAssessment->name . ' - ' . $examAssessmentClass->schoolClass->name)

@section('content_body')
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
                <form method="POST" action="{{ route('exam-assessment-classes.results.publish', $examAssessmentClass) }}" class="d-inline"
                    onsubmit="return confirm('Publish or republish results for this class?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                        {{ $examAssessmentClass->is_published ? 'Republish Results' : 'Publish Results' }}
                    </button>
                </form>
                <a href="{{ route('exam-assessment-classes.results.download-class-pdf', $examAssessmentClass) }}" class="btn btn-sm btn-primary">Download Full PDF</a>
                <a href="{{ route('exam-assessment-classes.marks.create', $examAssessmentClass) }}" class="btn btn-sm btn-info">Back To Marks</a>
                <a href="{{ route('exam-assessment-classes.setup.edit', $examAssessmentClass) }}" class="btn btn-sm btn-secondary">Back To Setup</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Result Sheet</h3>
        <span class="badge {{ $examAssessmentClass->is_published ? 'badge-success' : 'badge-warning' }}">
            {{ $examAssessmentClass->is_published ? 'Published' : 'Not Published' }}
        </span>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Roll</th>
                        <th>Student</th>
                        <th>Total</th>
                        <th>%</th>
                        <th>GPA</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $row)
                        <tr>
                            <td>{{ $row->position ?? '-' }}</td>
                            <td>{{ $row->studentEnrollment->roll_number ?? '-' }}</td>
                            <td>{{ $row->studentEnrollment->student->name ?? 'Student #' . $row->student_enrollment_id }}</td>
                            <td>{{ $row->total_obtained }}/{{ $row->total_marks }}</td>
                            <td>{{ $row->percentage }}</td>
                            <td>{{ $row->gpa }}</td>
                            <td>{{ $row->final_grade }}</td>
                            <td>
                                <span class="badge {{ $row->is_pass ? 'badge-success' : 'badge-danger' }}">
                                    {{ $row->is_pass ? 'PASS' : 'FAIL' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('exam-assessment-classes.results.show', [$examAssessmentClass, $row->studentEnrollment]) }}" class="btn btn-sm btn-warning">View</a>
                                <a href="{{ route('exam-assessment-classes.results.download', [$examAssessmentClass, $row->studentEnrollment]) }}" class="btn btn-sm btn-primary">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No results found. Publish results first.</td></tr>
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
