@extends('layouts.app')

@section('title', 'Edit Section')

@section('content_header_title', 'Sections')
@section('content_header_subtitle', 'Edit')

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('sections.update', $section->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="class_id">Class</label>
                <select name="class_id" class="form-control" required>
                    <option value="">Select Class</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ $class->id == $section->class_id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
                @error('class_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="section_name">Section Name</label>
                <input type="text" name="section_name" class="form-control" value="{{ old('section_name', $section->section_name) }}" required>
                @error('section_name') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('sections.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
