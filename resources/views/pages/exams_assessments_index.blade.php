@extends('layouts.app')

@section('title', 'Exam Assessments')
@section('content_header_title', 'Exam Assessments')
@section('content_header_subtitle', 'Manage')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Exam Assessments</h3>
        <a href="{{ route('exam-assessments.create') }}" class="btn btn-sm btn-primary">Create Assessment</a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Academic Year</th>
                        <th>Term</th>
                        <th>Status</th>
                        <th>Classes</th>
                        <th width="320">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assessments as $assessment)
                        @php($firstClass = $assessment->assessmentClasses->first())
                        @php($hasMarks = $assessment->assessmentClasses()->whereHas('assessmentSubjects.marks')->exists())
                        <tr>
                            <td>{{ $assessment->name }}</td>
                            <td>{{ $assessment->academicYear->name ?? '-' }}</td>
                            <td>{{ $assessment->term->name ?? '-' }}</td>
                            <td><span class="badge badge-secondary">{{ strtoupper($assessment->status) }}</span></td>
                            <td>
                                @foreach($assessment->assessmentClasses as $assessmentClass)
                                    <span class="badge badge-light">{{ $assessmentClass->schoolClass->name ?? 'Class #' . $assessmentClass->class_id }}</span>
                                @endforeach
                            </td>
                            <td>
                                <a href="{{ route('exam-assessments.edit', $assessment) }}" class="btn btn-sm btn-warning">Edit</a>
                                @if($firstClass)
                                    <a href="{{ route('exam-assessment-classes.setup.edit', $firstClass) }}" class="btn btn-sm btn-info">Setup</a>
                                    <a href="{{ route('exam-assessment-classes.marks.create', $firstClass) }}" class="btn btn-sm btn-success">Marks</a>
                                    <a href="{{ route('exam-assessment-classes.results.index', $firstClass) }}" class="btn btn-sm btn-primary">Results</a>
                                @endif
                                <form action="{{ route('exam-assessments.destroy', $assessment) }}" method="POST" class="d-inline js-assessment-delete-form"
                                    data-has-marks="{{ $hasMarks ? '1' : '0' }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="force_delete_with_marks" value="{{ $hasMarks ? '1' : '0' }}">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No assessments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $assessments->links() }}
        </div>
    </div>
</div>

<script>
    (function () {
        const forms = document.querySelectorAll('.js-assessment-delete-form');
        forms.forEach((form) => {
            form.addEventListener('submit', function (event) {
                const hasMarks = form.dataset.hasMarks === '1';
                const message = hasMarks
                    ? 'This assessment already has marks/results. If you continue, all related marks and results will be deleted permanently. Continue?'
                    : 'Delete this assessment?';

                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    })();
</script>
@endsection
