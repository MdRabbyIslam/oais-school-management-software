@extends('layouts.app')

@section('title', 'Grading Policies')
@section('content_header_title', 'Grading Policies')
@section('content_header_subtitle', 'Manage')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Grading Policies</h3>
        <a href="{{ route('grading-policies.create') }}" class="btn btn-sm btn-primary">Add Policy</a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('grading-policies.index') }}" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col-md-4">
                    <label for="class_id">Filter by Class</label>
                    <select name="class_id" id="class_id" class="form-control">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="grade_scheme_id">Filter by Grade Scheme</label>
                    <select name="grade_scheme_id" id="grade_scheme_id" class="form-control">
                        <option value="">All Schemes</option>
                        @foreach($schemes as $scheme)
                            <option value="{{ $scheme->id }}" {{ (string) request('grade_scheme_id') === (string) $scheme->id ? 'selected' : '' }}>
                                {{ $scheme->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('grading-policies.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Marks</th>
                        <th>Weight</th>
                        <th>Final GPA</th>
                        <th>4th Subject</th>
                        <th>Components</th>
                        <th>Scheme</th>
                        <th>Status</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($policies as $policy)
                        <tr>
                            <td>{{ $policy->schoolClass->name ?? 'Class #' . $policy->class_id }}</td>
                            <td>{{ $policy->subject->name ?? 'Subject #' . $policy->subject_id }}</td>
                            <td>{{ $policy->pass_marks }}/{{ $policy->total_marks }}</td>
                            <td>{{ $policy->weight ?? '1.00' }}</td>
                            <td>{{ ($policy->exclude_from_final_gpa ?? $policy->is_optional) ? 'Excluded' : 'Included' }}</td>
                            <td>{{ ($policy->is_fourth_subject_eligible ?? false) ? 'Eligible' : 'No' }}</td>
                            <td>{{ $policy->components->count() ?: '-' }}</td>
                            <td>{{ $policy->gradeScheme->name ?? 'Scheme #' . $policy->grade_scheme_id }}</td>
                            <td>
                                <span class="badge {{ $policy->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $policy->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('grading-policies.edit', $policy) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('grading-policies.destroy', $policy) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Delete this grading policy?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No grading policies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $policies->links() }}</div>
    </div>
</div>
@endsection
