@extends('layouts.app')

@section('title', 'Teacher List')
@section('content_header_title', 'Teachers')
@section('content_header_subtitle', 'List')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Teacher List</h3>
        <a href="{{ route('teachers.create') }}" class="btn btn-sm btn-primary">Add New Teacher</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Teacher ID</th>
                    <th>Name</th>
                    <th>Qualification</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->id }}</td>
                        <td>{{ $teacher->teacher_id }}</td>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $teacher->qualification }}</td>
                        <td>{{ $teacher->contact_info }}</td>
                        <td>{{ $teacher->status }}</td>
                        <td>
                            <a href="{{ route('teachers.edit', $teacher->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('teachers.destroy', $teacher->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure to delete this teacher?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No teachers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
