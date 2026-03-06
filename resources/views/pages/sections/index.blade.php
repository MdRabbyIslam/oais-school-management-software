@extends('layouts.app')

@section('title', 'Section List')

@section('content_header_title', 'Sections')
@section('content_header_subtitle', 'List')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Section List</h3>
        <a href="{{ route('sections.create') }}" class="btn btn-sm btn-primary">Add New Section</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Class</th>
                    <th>Section Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sections as $section)
                    <tr>
                        <td>{{ $section->id }}</td>
                        <td>{{ $section->schoolClass->name ?? '-' }}</td>
                        <td>{{ $section->section_name }}</td>
                        <td>
                            <a href="{{ route('sections.edit', $section->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('sections.destroy', $section->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure to delete this section?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No sections found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
