@extends('layouts.app')

@section('title', 'Create Subject')

@section('content_header_title', 'Subjects')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('subjects.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Subject Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="code">Subject Code</label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                @error('code') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="description">Description (optional)</label>
                <textarea name="description" class="form-control">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Assign to Classes</label>
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllClasses()">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllClasses()">Deselect All</button>
                </div>
                <div class="row">
                    @foreach($classes as $class)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input type="checkbox" name="classes[]" value="{{ $class->id }}" class="form-check-input class-checkbox"
                                    {{ (in_array($class->id, old('classes', []))) ? 'checked' : '' }}>
                                <label class="form-check-label">{{ $class->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-success">Create</button>
            <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<script>
    function selectAllClasses() {
        document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = true);
    }

    function deselectAllClasses() {
        document.querySelectorAll('.class-checkbox').forEach(cb => cb.checked = false);
    }
</script>
@endsection
