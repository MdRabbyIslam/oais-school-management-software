@extends('layouts.app')

@section('title', 'Create Class')

@section('content_header_title', 'Classes')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('classes.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Class Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="class_level">Class Level</label>
                <input type="number" name="class_level" class="form-control" value="{{ old('class_level') }}" required>
                @error('class_level') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn btn-success">Create</button>
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>
@endsection
