@extends('layouts.app')

@section('title', 'Exam Setup')
@section('content_header_title', 'Exam Setup')
@section('content_header_subtitle', $examAssessmentClass->examAssessment->name . ' - ' . $examAssessmentClass->schoolClass->name)

@section('content_body')
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
                <a href="{{ route('exam-assessment-classes.results.index', $examAssessmentClass) }}" class="btn btn-sm btn-success">View Results</a>
                <a href="{{ route('exam-assessments.index') }}" class="btn btn-sm btn-secondary">Back To Assessments</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Add / Update Subject Setup</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    Subject setup uses grading policy automatically (class + subject). You only configure exam-specific options here.
                </p>
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
                @if($subjectsWithoutPolicy->isNotEmpty())
                    <div class="alert alert-warning">
                        <strong>Missing grading policy:</strong> {{ $subjectsWithoutPolicy->pluck('name')->join(', ') }}.
                        Please create policy first, then come back to setup.
                        <div class="mt-2">
                            <a
                                href="{{ route('grading-policies.create', ['class_id' => $examAssessmentClass->class_id]) }}"
                                class="btn btn-sm btn-outline-warning"
                            >
                                Create Grading Policy
                            </a>
                        </div>
                    </div>
                @endif

                <form action="{{ route('exam-assessment-classes.setup.store-subject', $examAssessmentClass) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-control" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                @php($policy = $policiesBySubject->get($subject->id))
                                <option
                                    value="{{ $subject->id }}"
                                    data-policy-exists="{{ $policy ? '1' : '0' }}"
                                    data-policy-total="{{ $policy->total_marks ?? '' }}"
                                    data-policy-pass="{{ $policy->pass_marks ?? '' }}"
                                    data-policy-scheme="{{ $policy?->gradeScheme?->name ?? '' }}"
                                    {{ (string) old('subject_id') === (string) $subject->id ? 'selected' : '' }}
                                >
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group" id="policyInfoWrapper" style="display: none;">
                        <label>Linked Grading Policy</label>
                        <div class="border rounded p-2 bg-light">
                            <div><strong>Total Marks:</strong> <span id="policyTotalMarks">-</span></div>
                            <div><strong>Pass Marks:</strong> <span id="policyPassMarks">-</span></div>
                            <div><strong>Grade Scheme:</strong> <span id="policySchemeName">-</span></div>
                        </div>
                    </div>

                    <div class="form-group" id="policyMissingWrapper" style="display: none;">
                        <div class="alert alert-warning mb-0">
                            No grading policy found for this class and subject.
                            <a
                                id="policyCreateLink"
                                href="{{ route('grading-policies.create', ['class_id' => $examAssessmentClass->class_id]) }}"
                            >
                                Create policy now
                            </a>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Weight</label>
                            <input type="number" step="0.01" name="weight" class="form-control" value="{{ old('weight', 1) }}">
                            <small class="form-text text-muted">
                                Contribution factor for final result. Use <strong>1.00</strong> for normal subjects, <strong>0.50</strong> for half-weight subjects.
                            </small>
                            @error('weight') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_optional" id="is_optional" class="form-check-input" value="1" {{ old('is_optional') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_optional">Optional Subject</label>
                        </div>
                    </div>

                    <hr>
                    <p class="mb-2"><strong>Components (Optional)</strong></p>
                    <small class="form-text text-muted mb-2">
                        Use components only when this subject has split parts (for example: Written + MCQ + Practical).
                        If the subject has no split, leave all rows empty and enter marks directly at subject level.
                    </small>
                    @for($i = 0; $i < 3; $i++)
                        <div class="border rounded p-2 mb-2">
                            <div class="form-row">
                                <div class="col-md-4">
                                    <input type="text" name="components[{{ $i }}][component_name]" class="form-control" placeholder="Name (Written)">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="components[{{ $i }}][component_code]" class="form-control" placeholder="Code (written)">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.01" name="components[{{ $i }}][total_marks]" class="form-control" placeholder="Total">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" name="components[{{ $i }}][pass_marks]" class="form-control" placeholder="Pass">
                                </div>
                            </div>
                        </div>
                    @endfor
                    @error('components') <span class="text-danger">{{ $message }}</span> @enderror

                    <button type="submit" class="btn btn-success">Save Setup</button>
                    <a href="{{ route('exam-assessments.index') }}" class="btn btn-secondary">Back</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Configured Subjects</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Policy</th>
                            <th>Components</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($examAssessmentClass->assessmentSubjects as $assessmentSubject)
                            <tr>
                                <td>{{ $assessmentSubject->subject->name ?? 'Subject #' . $assessmentSubject->subject_id }}</td>
                                <td>{{ $assessmentSubject->pass_marks }}/{{ $assessmentSubject->total_marks }}</td>
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
                                    <form method="POST" action="{{ route('exam-assessment-subjects.destroy', $assessmentSubject) }}"
                                        onsubmit="return confirm('Delete this subject setup?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No subject setup yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <form method="POST" action="{{ route('exam-assessment-classes.results.publish', $examAssessmentClass) }}" class="d-inline"
                    onsubmit="return confirm('Publish results for this class? This will recalculate and update result summaries.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Publish Results</button>
                </form>
                <a href="{{ route('exam-assessment-classes.results.index', $examAssessmentClass) }}" class="btn btn-outline-success btn-sm">View Results</a>
                <a href="{{ route('exam-assessment-classes.marks.create', $examAssessmentClass) }}" class="btn btn-primary btn-sm">Go To Marks Entry</a>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const subjectSelect = document.getElementById('subject_id');
        const policyInfoWrapper = document.getElementById('policyInfoWrapper');
        const policyMissingWrapper = document.getElementById('policyMissingWrapper');
        const policyTotalMarks = document.getElementById('policyTotalMarks');
        const policyPassMarks = document.getElementById('policyPassMarks');
        const policySchemeName = document.getElementById('policySchemeName');
        const policyCreateLink = document.getElementById('policyCreateLink');

        function updatePolicyInfo() {
            const option = subjectSelect.options[subjectSelect.selectedIndex];
            const subjectId = option?.value || '';
            const hasPolicy = option?.dataset?.policyExists === '1';

            if (!subjectId) {
                policyInfoWrapper.style.display = 'none';
                policyMissingWrapper.style.display = 'none';
                return;
            }

            if (hasPolicy) {
                policyTotalMarks.textContent = option.dataset.policyTotal || '-';
                policyPassMarks.textContent = option.dataset.policyPass || '-';
                policySchemeName.textContent = option.dataset.policyScheme || '-';
                policyInfoWrapper.style.display = 'block';
                policyMissingWrapper.style.display = 'none';
                return;
            }

            policyInfoWrapper.style.display = 'none';
            policyMissingWrapper.style.display = 'block';
            if (policyCreateLink) {
                const baseUrl = "{{ route('grading-policies.create') }}";
                policyCreateLink.href = `${baseUrl}?class_id={{ $examAssessmentClass->class_id }}&subject_id=${subjectId}`;
            }
        }

        subjectSelect?.addEventListener('change', function () {
            updatePolicyInfo();
        });

        updatePolicyInfo();

        const classSwitch = document.getElementById('setup_class_switch');
        classSwitch?.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    })();
</script>
@endsection
