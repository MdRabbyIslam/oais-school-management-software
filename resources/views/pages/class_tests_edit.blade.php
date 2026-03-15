@extends('layouts.app')

@section('title', 'Edit Class Test')
@section('content_header_title', 'Class Tests')
@section('content_header_subtitle', 'Edit')

@section('content_body')
<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('class-tests.update', $classTest) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Academic Year</label>
                    <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                        <option value="">Select Academic Year</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ (string) old('academic_year_id', $classTest->academic_year_id) === (string) $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-4">
                    <label>Term</label>
                    <select name="term_id" id="term_id" class="form-control" required>
                        <option value="">Select Term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" data-academic-year-id="{{ $term->academic_year_id }}" {{ (string) old('term_id', $classTest->term_id) === (string) $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('term_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-4">
                    <label>Class</label>
                    <select name="class_id" id="class_id" class="form-control" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) old('class_id', $classTest->class_id) === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('class_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Subject</label>
                    <select name="subject_id" id="subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                data-class-ids="{{ $subject->classes->pluck('id')->implode(',') }}"
                                {{ (string) old('subject_id', $classTest->subject_id) === (string) $subject->id ? 'selected' : '' }}
                            >
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-5">
                    <label>Class Test Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $classTest->name) }}" required>
                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Test Date</label>
                    <input type="date" name="test_date" class="form-control" value="{{ old('test_date', optional($classTest->test_date)->format('Y-m-d')) }}">
                    @error('test_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Total Marks</label>
                    <input type="number" step="0.01" min="1" name="total_marks" class="form-control" value="{{ old('total_marks', $classTest->total_marks) }}" required>
                    @error('total_marks') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Pass Marks</label>
                    <input type="number" step="0.01" min="0" name="pass_marks" class="form-control" value="{{ old('pass_marks', $classTest->pass_marks) }}">
                    @error('pass_marks') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3">
                    <label>Status</label>
                    @php($selectedStatus = old('status', $classTest->status))
                    <select name="status" class="form-control" required>
                        <option value="draft" {{ $selectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ $selectedStatus === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="locked" {{ $selectedStatus === 'locked' ? 'selected' : '' }}>Locked</option>
                    </select>
                    <small class="form-text text-muted">
                        Status guide: Draft = working stage (not counted in term exam AV). Published = counted in term exam AV and editable. Locked = counted in term exam AV and editing blocked.
                    </small>
                    <small class="form-text text-info">
                        To include class test marks in term exam results (AV), keep this class test as Published or Locked.
                    </small>
                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success">Update</button>
            <a href="{{ route('class-tests.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<script>
    (function () {
        const academicYearSelect = document.getElementById('academic_year_id');
        const termSelect = document.getElementById('term_id');
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        const termOptions = Array.from(termSelect.querySelectorAll('option[value]'));
        const subjectOptions = Array.from(subjectSelect.querySelectorAll('option[value]'));
        const oldTerm = "{{ old('term_id', $classTest->term_id) }}";
        const oldSubject = "{{ old('subject_id', $classTest->subject_id) }}";

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

        academicYearSelect.addEventListener('change', function () {
            filterTermsByYear(this.value);
            resetSelect(termSelect);
        });

        classSelect.addEventListener('change', function () {
            filterSubjectsByClass(this.value);
            resetSelect(subjectSelect);
        });

        filterTermsByYear(academicYearSelect.value);
        const selectedTerm = termSelect.querySelector(`option[value="${oldTerm}"]`);
        if (selectedTerm && !selectedTerm.disabled) {
            termSelect.value = oldTerm;
        }

        filterSubjectsByClass(classSelect.value);
        const selectedSubject = subjectSelect.querySelector(`option[value="${oldSubject}"]`);
        if (selectedSubject && !selectedSubject.disabled) {
            subjectSelect.value = oldSubject;
        }
    })();
</script>
@endsection
