@extends('layouts.app')

@section('title', 'Create Grading Policy')
@section('content_header_title', 'Grading Policies')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('grading-policies.store') }}" method="POST">
            @csrf

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Class</label>
                    <select name="class_id" id="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) old('class_id', $prefillClassId ?? '') === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('class_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-4">
                    <label>Subject</label>
                    <select name="subject_id" id="subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                data-class-ids="{{ $subject->classes->pluck('id')->implode(',') }}"
                                {{ (string) old('subject_id', $prefillSubjectId ?? '') === (string) $subject->id ? 'selected' : '' }}
                            >
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-4">
                    <label>Grade Scheme</label>
                    <select name="grade_scheme_id" id="grade_scheme_id" class="form-control" required>
                        <option value="">Select Scheme</option>
                        @foreach($schemes as $scheme)
                            <option value="{{ $scheme->id }}" {{ (string) old('grade_scheme_id') === (string) $scheme->id ? 'selected' : '' }}>
                                {{ $scheme->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade_scheme_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Total Marks</label>
                    <input type="number" step="0.01" min="1" name="total_marks" class="form-control" value="{{ old('total_marks') }}" required>
                    @error('total_marks') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Pass Marks</label>
                    <input type="number" step="0.01" min="0" name="pass_marks" class="form-control" value="{{ old('pass_marks') }}" required>
                    @error('pass_marks') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Create</button>
            <a href="{{ route('grading-policies.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<script>
    (function () {
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        const gradeSchemeSelect = document.getElementById('grade_scheme_id');
        const subjectOptions = Array.from(subjectSelect.querySelectorAll('option[value]'));
        const oldSubject = "{{ old('subject_id', $prefillSubjectId ?? '') }}";

        function resetSelect(select) {
            select.value = '';
        }

        function filterSubjectsByClass(classId) {
            subjectOptions.forEach((option) => {
                const classIds = (option.dataset.classIds || '')
                    .split(',')
                    .map((id) => id.trim())
                    .filter(Boolean);

                const show = classId && classIds.includes(classId);
                option.hidden = !show;
                option.disabled = !show;
            });
        }

        function applyClassSelection({ preserveSubject = false } = {}) {
            const classId = classSelect.value;
            filterSubjectsByClass(classId);

            if (!preserveSubject) {
                resetSelect(subjectSelect);
            } else if (oldSubject) {
                const selected = subjectSelect.querySelector(`option[value="${oldSubject}"]`);
                if (selected && !selected.disabled) {
                    subjectSelect.value = oldSubject;
                } else {
                    resetSelect(subjectSelect);
                }
            }
        }

        classSelect.addEventListener('change', function () {
            applyClassSelection({ preserveSubject: false });
            resetSelect(gradeSchemeSelect);
        });

        applyClassSelection({ preserveSubject: true });
    })();
</script>
@endsection
