@extends('layouts.app')

@section('title', 'Edit Class')

@section('content_header_title', 'Classes')
@section('content_header_subtitle', 'Edit')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('classes.update', $class->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Class Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $class->name) }}" required>
                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="class_level">Grade Level</label>
                <input type="number" name="class_level" class="form-control" value="{{ old('class_level', $class->class_level) }}" required>
                @error('class_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
