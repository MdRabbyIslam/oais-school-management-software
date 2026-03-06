@extends('layouts.app')

@section('title', 'Create Exam Assessment')
@section('content_header_title', 'Exam Assessments')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('exam-assessments.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="academic_year_id">Academic Year</label>
                <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                    <option value="">Select Academic Year</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ (string) old('academic_year_id') === (string) $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
                @error('academic_year_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="term_id">Term</label>
                <select name="term_id" id="term_id" class="form-control">
                    <option value="">None</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" {{ (string) old('term_id') === (string) $term->id ? 'selected' : '' }}>
                            {{ $term->name }}
                        </option>
                    @endforeach
                </select>
                @error('term_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="name">Assessment Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}">
                    @error('start_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}">
                    @error('end_date') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                @php($selectedStatus = old('status', 'draft'))
                <select name="status" id="status" class="form-control" required>
                    <option value="draft" {{ $selectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ $selectedStatus === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="locked" {{ $selectedStatus === 'locked' ? 'selected' : '' }}>Locked</option>
                </select>
                @error('status') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label>Target Classes</label>
                <small class="form-text text-muted mb-2">
                    After creation, subject setup will be auto-initialized from class-subject mappings using active grading policies.
                    Missing policies will be skipped and shown as warning.
                </small>
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllClasses">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="unselectAllClasses">Unselect All</button>
                </div>
                <div class="row">
                    @foreach($classes as $class)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    name="class_ids[]"
                                    value="{{ $class->id }}"
                                    class="form-check-input target-class-checkbox"
                                    id="class_{{ $class->id }}"
                                    {{ in_array($class->id, old('class_ids', [])) ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="class_{{ $class->id }}">{{ $class->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('class_ids') <span class="text-danger">{{ $message }}</span> @enderror
                @error('class_ids.*') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-success">Create</button>
            <a href="{{ route('exam-assessments.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<script>
    (function () {
        const checkboxes = document.querySelectorAll('.target-class-checkbox');
        const selectAllBtn = document.getElementById('selectAllClasses');
        const unselectAllBtn = document.getElementById('unselectAllClasses');

        selectAllBtn?.addEventListener('click', function () {
            checkboxes.forEach((checkbox) => checkbox.checked = true);
        });

        unselectAllBtn?.addEventListener('click', function () {
            checkboxes.forEach((checkbox) => checkbox.checked = false);
        });
    })();
</script>
@endsection
