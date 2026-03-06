@extends('layouts.app')

@section('title', 'Grade Schemes')
@section('content_header_title', 'Grade Schemes')
@section('content_header_subtitle', 'Manage')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Grade Schemes</h3>
        <a href="{{ route('grade-schemes.create') }}" class="btn btn-sm btn-primary">Add Grade Scheme</a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gradeSchemes as $scheme)
                        <tr>
                            <td>{{ $scheme->name }}</td>
                            <td>
                                <span class="badge {{ $scheme->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $scheme->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </td>
                            <td>{{ $scheme->items_count }}</td>
                            <td>
                                <a href="{{ route('grade-schemes.edit', $scheme) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('grade-schemes.destroy', $scheme) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete this grade scheme?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No grade schemes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $gradeSchemes->links() }}</div>
    </div>
</div>
@endsection

