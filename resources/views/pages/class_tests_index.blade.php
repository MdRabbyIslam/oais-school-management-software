@extends('layouts.app')

@section('title', 'Class Tests')
@section('content_header_title', 'Class Tests')
@section('content_header_subtitle', 'Manage')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Class Tests</h3>
        <div>
            <a href="{{ route('class-tests.reports.index') }}" class="btn btn-sm btn-info">Result Reports</a>
            <a href="{{ route('class-tests.create') }}" class="btn btn-sm btn-primary">Create Class Test</a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('class-tests.index') }}" class="mb-3">
            <div class="form-row">
                <div class="col-md-3">
                    <label>Academic Year</label>
                    <select name="academic_year_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ (string) request('academic_year_id') === (string) $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Term</label>
                    <select name="term_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ (string) request('term_id') === (string) $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Class</label>
                    <select name="class_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="locked" {{ request('status') === 'locked' ? 'selected' : '' }}>Locked</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-info w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Academic Year</th>
                        <th>Term</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Total/Pass</th>
                        <th>Status</th>
                        <th>Marks Rows</th>
                        <th width="430">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classTests as $classTest)
                        @php($hasMarks = $classTest->marks_count > 0)
                        <tr>
                            <td>{{ $classTest->name }}</td>
                            <td>{{ $classTest->academicYear->name ?? '-' }}</td>
                            <td>{{ $classTest->term->name ?? '-' }}</td>
                            <td>{{ $classTest->schoolClass->name ?? '-' }}</td>
                            <td>{{ $classTest->subject->name ?? '-' }}</td>
                            <td>{{ number_format((float) $classTest->total_marks, 2) }}/{{ $classTest->pass_marks !== null ? number_format((float) $classTest->pass_marks, 2) : '-' }}</td>
                            <td>
                                <span class="badge {{ $classTest->status === 'published' ? 'badge-success' : ($classTest->status === 'locked' ? 'badge-dark' : 'badge-secondary') }}">
                                    {{ strtoupper($classTest->status) }}
                                </span>
                            </td>
                            <td>{{ $classTest->marks_count }}</td>
                            <td>
                                <a href="{{ route('class-tests.edit', $classTest) }}" class="btn btn-sm btn-warning">Edit</a>
                                <a href="{{ route('class-tests.marks.create', $classTest) }}" class="btn btn-sm btn-success">Marks</a>
                                <a href="{{ route('class-tests.print', $classTest) }}" target="_blank" class="btn btn-sm btn-info">Print Result</a>
                                <a href="{{ route('class-tests.print-blank', $classTest) }}" target="_blank" class="btn btn-sm btn-secondary">Print Blank Sheet</a>
                                <form action="{{ route('class-tests.destroy', $classTest) }}" method="POST" class="d-inline js-class-test-delete-form" data-has-marks="{{ $hasMarks ? '1' : '0' }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="force_delete_with_marks" value="{{ $hasMarks ? '1' : '0' }}">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No class tests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $classTests->links() }}
        </div>
    </div>
</div>

<script>
    (function () {
        const forms = document.querySelectorAll('.js-class-test-delete-form');
        forms.forEach((form) => {
            form.addEventListener('submit', function (event) {
                const hasMarks = form.dataset.hasMarks === '1';
                const message = hasMarks
                    ? 'This class test already has marks. If you continue, all related marks will be deleted permanently. Continue?'
                    : 'Delete this class test?';

                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    })();
</script>
@endsection
