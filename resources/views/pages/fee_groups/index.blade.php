@extends('layouts.app')

@section('subtitle', 'Fee Groups')
@section('content_header_title', 'Fee Groups Management')

@section('content_body')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between">
            <h3 class="card-title">All Fee Groups</h3>
            <div>
                <form method="GET" action="{{ route('fee-groups.index') }}" class="form-inline">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search..." value="{{ $search }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <a href="{{ route('fee-groups.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Group
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 10%">ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th style="width: 20%">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td>{{ $group->name }}</td>
                    <td>
                        <span class="badge bg-{{ $group->type === 'core' ? 'primary' : 'secondary' }}">
                            {{ ucfirst($group->type) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('fee-groups.edit', $group->id) }}"
                           class="btn btn-sm btn-info">
                           <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('fee-groups.destroy', $group->id) }}"
                              method="POST" style="display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No fee groups found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($groups->hasPages())
    <div class="card-footer clearfix">
        {{ $groups->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@stop
