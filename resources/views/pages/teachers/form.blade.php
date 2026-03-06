<div class="form-group">
    <label for="name">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="qualification">Qualification</label>
    <input type="text" name="qualification" class="form-control" value="{{ old('qualification', $teacher->qualification ?? '') }}" required>
</div>

<div class="form-group">
    <label for="experience">Experience</label>
    <textarea name="experience" class="form-control">{{ old('experience', $teacher->experience ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="contact_info">Contact Info</label>
    <input type="text" name="contact_info" class="form-control" value="{{ old('contact_info', $teacher->contact_info ?? '') }}" required>
</div>

<div class="form-group">
    <label for="base_salary">Base Salary</label>
    <input type="number" step="0.01" name="base_salary" class="form-control" value="{{ old('base_salary', $teacher->base_salary ?? '') }}" required>
</div>

<div class="form-group">
    <label for="status">Employment Status</label>
    <select name="status" class="form-control" required>
        @foreach(['Active', 'Inactive', 'Resigned', 'Retired'] as $status)
            <option value="{{ $status }}" {{ (old('status', $teacher->status ?? '') == $status) ? 'selected' : '' }}>
                {{ $status }}
            </option>
        @endforeach
    </select>
</div>

<button type="submit" class="btn btn-success">Save</button>
<a href="{{ route('teachers.index') }}" class="btn btn-secondary">Cancel</a>
