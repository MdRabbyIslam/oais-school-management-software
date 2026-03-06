@extends('layouts.app')

@section('title', 'Class List')

@section('content_header_title', 'Classes')
@section('content_header_subtitle', 'List')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Class List</h3>
        <a href="{{ route('classes.create') }}" class="btn btn-sm btn-primary">Add New Class</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Class Name</th>
                    <th>Grade Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->id }}</td>
                        <td>{{ $class->name }}</td>
                        <td>{{ $class->class_level }}</td>
                        <td>
                            <a href="{{ route('classes.edit', $class->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('classes.destroy', $class->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure to delete this class?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No classes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
