@extends('layouts.app')

@section('title', 'Subject List')

@section('content_header_title', 'Subjects')
@section('content_header_subtitle', 'List')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Subject List</h3>
        <a href="{{ route('subjects.create') }}" class="btn btn-sm btn-primary">Add New Subject</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                    <tr>
                        <td>{{ $subject->id }}</td>
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->code }}</td>
                        <td>{{ $subject->description }}</td>
                        <td>
                            <a href="{{ route('subjects.edit', $subject->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('subjects.destroy', $subject->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure to delete this subject?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No subjects found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
