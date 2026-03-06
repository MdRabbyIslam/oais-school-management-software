@extends('layouts.app')
@section('title', 'Admission Applications')

@section('content_header_title', 'Admissions')
@section('content_header_subtitle', 'Applications')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Applications</h3>
        <a href="{{ route('admissions.create') }}" class="btn btn-sm btn-primary">New Application</a>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Application No</th>
                    <th>Name</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Submitted At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>{{ $app->id }}</td>
                        <td>{{ $app->application_no }}</td>
                        <td>{{ $app->name }}</td>
                        <td>{{ $app->academicYear->name ?? '-' }}</td>
                        <td>{{ $app->status }}</td>
                        <td>{{ optional($app->submitted_at)->toDateString() }}</td>
                        <td>
                            {{-- edit route --}}
                            <a href="{{ route('admissions.edit', $app->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            {{-- view route --}}
                            <a href="{{ route('admissions.show', $app->id) }}" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No applications found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $applications->links() }}</div>
    </div>
</div>
@stop
