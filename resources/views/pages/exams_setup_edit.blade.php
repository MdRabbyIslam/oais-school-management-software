@extends('layouts.app')

@section('title', 'Exam Setup')
@section('content_header_title', 'Exam Setup')
@section('content_header_subtitle', $examAssessmentClass->examAssessment->name . ' - ' . $examAssessmentClass->schoolClass->name)

@section('content_body')
@php($assessmentStatus = $examAssessmentClass->examAssessment->status)
<div class="card">
    <div class="card-body py-2">
        <div class="form-row align-items-end">
            <div class="col-md-5">
                <label for="setup_class_switch" class="mb-1">Switch Target Class</label>
                <select id="setup_class_switch" class="form-control form-control-sm">
                    @foreach($assessmentClasses as $targetClass)
                        <option
                            value="{{ route('exam-assessment-classes.setup.edit', $targetClass) }}"
                            {{ $targetClass->id === $examAssessmentClass->id ? 'selected' : '' }}
                        >
                            {{ $targetClass->schoolClass->name ?? 'Class #' . $targetClass->class_id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7">
                <a href="{{ route('exam-assessment-classes.marks.create', $examAssessmentClass) }}" class="btn btn-sm btn-primary">Marks For Current Class</a>
                @if($assessmentStatus === 'published')
                    <a href="{{ route('exam-assessment-classes.results.index', $examAssessmentClass) }}" class="btn btn-sm btn-success">View Results</a>
                @endif
                <a href="{{ route('exam-assessments.index') }}" class="btn btn-sm btn-secondary">Back To Assessments</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Configured Subjects</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Assessment Status:</strong> {{ strtoupper($assessmentStatus) }}.
            Subject setup is now fully managed by <strong>Grading Policies</strong> (marks, pass, optional, weight, components).
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if(!empty($syncSummary))
            <div class="alert alert-light border">
                Synced {{ $syncSummary['synced_subjects'] ?? 0 }} subject(s) from active grading policies.
                @if(($syncSummary['skipped_subjects_without_policy'] ?? 0) > 0)
                    <br>{{ $syncSummary['skipped_subjects_without_policy'] }} subject(s) skipped due to missing active policy.
                @endif
                @if(($syncSummary['component_sync_locked_by_marks'] ?? 0) > 0)
                    <br>{{ $syncSummary['component_sync_locked_by_marks'] }} subject(s) kept existing component structure because marks already exist.
                @endif
            </div>
        @endif

        @if($subjectsWithoutPolicy->isNotEmpty())
            <div class="alert alert-warning">
                <strong>Missing grading policy:</strong> {{ $subjectsWithoutPolicy->pluck('name')->join(', ') }}.
                <div class="mt-2">
                    <a href="{{ route('grading-policies.create', ['class_id' => $examAssessmentClass->class_id]) }}" class="btn btn-sm btn-outline-warning">
                        Create Missing Policy
                    </a>
                </div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Marks</th>
                        <th>Weight</th>
                        <th>Final GPA</th>
                        <th>4th Subject</th>
                        <th>Policy</th>
                        <th>Components</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($examAssessmentClass->assessmentSubjects as $assessmentSubject)
                        <tr>
                            <td>{{ $assessmentSubject->subject->name ?? 'Subject #' . $assessmentSubject->subject_id }}</td>
                            <td>{{ $assessmentSubject->pass_marks }}/{{ $assessmentSubject->total_marks }}</td>
                            <td>{{ $assessmentSubject->weight }}</td>
                            <td>
                                <span class="badge {{ ($assessmentSubject->exclude_from_final_gpa ?? $assessmentSubject->is_optional) ? 'badge-warning' : 'badge-secondary' }}">
                                    {{ ($assessmentSubject->exclude_from_final_gpa ?? $assessmentSubject->is_optional) ? 'EXCLUDED' : 'INCLUDED' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ ($assessmentSubject->is_fourth_subject_eligible ?? false) ? 'badge-info' : 'badge-secondary' }}">
                                    {{ ($assessmentSubject->is_fourth_subject_eligible ?? false) ? 'ELIGIBLE' : 'NO' }}
                                </span>
                            </td>
                            <td>{{ $assessmentSubject->gradingPolicy->gradeScheme->name ?? 'Policy #' . $assessmentSubject->grading_policy_id }}</td>
                            <td>
                                @if($assessmentSubject->components->isEmpty())
                                    <span class="text-muted">No components</span>
                                @else
                                    @foreach($assessmentSubject->components as $component)
                                        <div>{{ $component->component_name }} ({{ $component->total_marks }})</div>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if($assessmentSubject->gradingPolicy)
                                    <a href="{{ route('grading-policies.edit', $assessmentSubject->gradingPolicy) }}" class="btn btn-sm btn-warning">Edit Policy</a>
                                @else
                                    <a href="{{ route('grading-policies.create', ['class_id' => $examAssessmentClass->class_id, 'subject_id' => $assessmentSubject->subject_id]) }}" class="btn btn-sm btn-outline-warning">Create Policy</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No subject setup yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        @if($assessmentStatus === 'published')
            <form method="POST" action="{{ route('exam-assessment-classes.results.publish', $examAssessmentClass) }}" class="d-inline"
                onsubmit="return confirm('Publish results for this class? This will recalculate and update result summaries.');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">Publish Results</button>
            </form>
            <a href="{{ route('exam-assessment-classes.results.index', $examAssessmentClass) }}" class="btn btn-outline-success btn-sm">View Results</a>
        @endif
        <a href="{{ route('exam-assessment-classes.marks.create', $examAssessmentClass) }}" class="btn btn-primary btn-sm">Go To Marks Entry</a>
    </div>
</div>

<script>
    (function () {
        const classSwitch = document.getElementById('setup_class_switch');
        classSwitch?.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    })();
</script>
@endsection
