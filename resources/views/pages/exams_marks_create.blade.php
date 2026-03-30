@extends('layouts.app')

@section('title', 'Marks Entry')
@section('content_header_title', 'Marks Entry')
@section('content_header_subtitle', $examAssessmentClass->examAssessment->name . ' - ' . $examAssessmentClass->schoolClass->name)

@section('content_body')
@php($assessmentStatus = $examAssessmentClass->examAssessment->status)
<div class="card">
    <div class="card-body py-2">
        <div class="form-row align-items-end">
            <div class="col-md-5">
                <label for="marks_class_switch" class="mb-1">Switch Target Class</label>
                <select id="marks_class_switch" class="form-control form-control-sm">
                    @foreach($assessmentClasses as $targetClass)
                        <option
                            value="{{ route('exam-assessment-classes.marks.create', $targetClass) }}"
                            {{ $targetClass->id === $examAssessmentClass->id ? 'selected' : '' }}
                        >
                            {{ $targetClass->schoolClass->name ?? 'Class #' . $targetClass->class_id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7">
                <a href="{{ route('exam-assessment-classes.setup.edit', $examAssessmentClass) }}" class="btn btn-sm btn-info">Setup For Current Class</a>
                @if($assessmentStatus === 'published')
                    <form method="POST" action="{{ route('exam-assessment-classes.results.publish', $examAssessmentClass) }}" class="d-inline"
                        onsubmit="return confirm('Publish or republish results for this class?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            {{ $examAssessmentClass->is_published ? 'Republish Results' : 'Publish Results' }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('exam-assessment-classes.results.index', $examAssessmentClass) }}" class="btn btn-sm btn-warning">View Results</a>
                <a href="{{ route('exam-assessments.index') }}" class="btn btn-sm btn-secondary">Back To Assessments</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Enter Marks</h3>
        <div>
            <span class="badge badge-info mr-2">Assessment: {{ strtoupper($assessmentStatus) }}</span>
            <span class="badge {{ $examAssessmentClass->is_published ? 'badge-success' : 'badge-warning' }} mr-2">
                {{ $examAssessmentClass->is_published ? 'Results Published' : 'Results Not Published' }}
            </span>
            <a href="{{ route('exam-assessment-classes.setup.edit', $examAssessmentClass) }}" class="btn btn-sm btn-info">Back To Setup</a>
        </div>
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
        @if($assessmentStatus === 'locked')
            <div class="alert alert-warning">This assessment is locked. Marks cannot be modified.</div>
        @elseif($assessmentStatus === 'draft')
            <div class="alert alert-info">Assessment is in draft. Result publish/download actions are unavailable until status becomes Published.</div>
        @endif

        @if($assessmentSubjects->isEmpty())
            <div class="alert alert-warning mb-0">No subject setup found. Configure subjects first.</div>
        @else
            <form method="GET" action="{{ route('exam-assessment-classes.marks.create', $examAssessmentClass) }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="col-md-6">
                        <label>Subject</label>
                        <select name="assessment_subject_id" class="form-control" onchange="this.form.submit()">
                            @foreach($assessmentSubjects as $subjectItem)
                                <option value="{{ $subjectItem->id }}" {{ $selectedSubject && $selectedSubject->id === $subjectItem->id ? 'selected' : '' }}>
                                    {{ $subjectItem->subject->name ?? 'Subject #' . $subjectItem->subject_id }} ({{ $subjectItem->total_marks }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            @if($selectedSubject)
                @if(($examAssessmentClass->examAssessment->result_calculation_mode ?? 'standard_weighted') === 'ssc_optional_subject' && ($selectedSubject->is_fourth_subject_eligible ?? false))
                    <div class="alert alert-info">
                        This subject is marked as a 4th-subject option. Only students who selected this subject in their enrollment settings are shown here.
                    </div>
                @endif
                <form method="POST" action="{{ route('exam-assessment-classes.marks.store', $examAssessmentClass) }}">
                    @csrf
                    <input type="hidden" name="assessment_subject_id" value="{{ $selectedSubject->id }}">

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Roll</th>
                                    <th>Student</th>
                                    @if($selectedSubject->components->isNotEmpty())
                                        @foreach($selectedSubject->components as $component)
                                            <th>{{ $component->component_name }} ({{ $component->total_marks }})</th>
                                        @endforeach
                                    @endif
                                    <th>Total Marks</th>
                                    <th>Absent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($enrollments as $i => $enrollment)
                                    @php($mark = $existingMarks->get($enrollment->id))
                                    <tr>
                                        <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                        <td>{{ $enrollment->student->name ?? 'Student #' . $enrollment->student_id }}</td>
                                        <input type="hidden" name="rows[{{ $i }}][student_enrollment_id]" value="{{ $enrollment->id }}">

                                        @if($selectedSubject->components->isNotEmpty())
                                            @foreach($selectedSubject->components as $cIndex => $component)
                                                @php($componentMark = $mark ? $mark->components->firstWhere('assessment_subject_component_id', $component->id) : null)
                                                <td>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="form-control form-control-sm"
                                                        name="rows[{{ $i }}][component_marks][{{ $cIndex }}]"
                                                        value="{{ old("rows.$i.component_marks.$cIndex", optional($componentMark)->marks_obtained) }}"
                                                    >
                                                </td>
                                            @endforeach
                                        @endif

                                        <td>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="form-control form-control-sm"
                                                name="rows[{{ $i }}][marks_obtained]"
                                                value="{{ old("rows.$i.marks_obtained", optional($mark)->marks_obtained) }}"
                                            >
                                        </td>
                                        <td class="text-center">
                                            <input
                                                type="checkbox"
                                                name="rows[{{ $i }}][is_absent]"
                                                value="1"
                                                {{ old("rows.$i.is_absent", optional($mark)->is_absent) ? 'checked' : '' }}
                                            >
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="99" class="text-center">No active enrollments found for this class and academic year.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($assessmentStatus !== 'locked')
                        <button type="submit" class="btn btn-success">Save Marks</button>
                    @else
                        <button type="button" class="btn btn-secondary" disabled>Save Marks (Locked)</button>
                    @endif
                </form>
            @endif
        @endif
    </div>
</div>

<script>
    (function () {
        const classSwitch = document.getElementById('marks_class_switch');
        classSwitch?.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    })();
</script>
@endsection
