@extends('layouts.app')
@section('plugins.Select2', true)

@section('subtitle', 'Fee Assignments')
@section('content_header_title', 'Manage Fee Assignments')

@section('content_body')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
             <a href="{{ route('fee-assignments.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Assignment
                </a>
        </div>
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Assignments</h3>
                <form method="GET" class="form-inline" id="filter-form">
                    {{-- Term Filter --}}
                    <select name="term" class="form-control form-control-sm mr-2">
                        <option value="">All Terms</option>
                        @foreach (\App\Models\Term::all() as $term)
                            <option value="{{ $term->id }}" {{ request('term') == $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Class Filter --}}
                    <select name="class_id" id="filter-class" class="form-control form-control-sm mr-2">
                        <option value="">All Classes</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Section Filter --}}
                    <select name="section_id" id="filter-section" class="form-control form-control-sm mr-2">
                        <option value="">All Sections</option>
                        @foreach ($sections as $s)
                            <option value="{{ $s->id }}" {{ request('section_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->schoolClass->name }} – {{ $s->section_name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Student Filter --}}
                    <select name="student_id" id="filter-student" class="form-control form-control-sm select2 mr-2"
                        data-placeholder="Search student by ID, name…" style="width:200px">
                        {{-- Pre-populate on page-load if there’s a selected student --}}
                        @if (request('student_id') && ($preload = \App\Models\Student::with('section.schoolClass')->find(request('student_id'))))
                            <option value="{{ $preload->id }}" selected>
                                {{ $preload->student_id }} – {{ $preload->name }}
                                ({{ $preload->section->schoolClass->name }} – {{ $preload->section->section_name }})
                            </option>
                        @endif
                    </select>

                    {{-- Fee Filter --}}
                    <select name="fee_id" class="form-control form-control-sm mr-2 ml-2">
                        <option value="">All Fees</option>
                        @foreach ($fees as $fee)
                            <option value="{{ $fee->id }}" {{ request('fee_id') == $fee->id ? 'selected' : '' }}>
                                {{ $fee->fee_name }}
                            </option>
                        @endforeach
                    </select>


                    {{-- Status Filter --}}
                    <select name="status" class="form-control form-control-sm mr-2">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Free-text Search --}}
                    {{-- <input type="text" name="search" class="form-control form-control-sm mr-2" style="width:150px"
                        placeholder="Student, Fee…" value="{{ request('search') }}"> --}}

                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('fee-assignments.index') }}" class="btn btn-sm btn-secondary ml-2">
                        <i class="fas fa-history"></i> Reset
                    </a>
                    <button id="printBtn" class="btn btn-sm btn-outline-info ml-2">
                        <i class="fas fa-print"></i> Print
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                @php
                    function nextDir($col, $sortBy, $sortDir) {
                        return $col === $sortBy ? ($sortDir === 'asc' ? 'desc' : 'asc') : 'asc';
                    }
                @endphp

                <thead>
                    <tr>
                        {{-- SL --}}
                        <th>SL</th>
                        {{-- Student ID & Name --}}
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort_by' => 'student_id',
                                'sort_dir' => nextDir('student_id', $sortBy, $sortDir)
                            ]) }}">
                                Student (ID & Name)
                                @if ($sortBy === 'student_id')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </a>
                        </th>
                        {{-- Class / Section (no actual column, skip sorting) --}}
                        <th>Class / Section</th>
                        <th>Academic Year</th>
                        {{-- Fee --}}
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort_by' => 'fee_id',
                                'sort_dir' => nextDir('fee_id', $sortBy, $sortDir)
                            ]) }}">
                                Fee
                                @if ($sortBy === 'fee_id')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </a>
                        </th>
                        {{-- Term --}}
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort_by' => 'term_id',
                                'sort_dir' => nextDir('term_id', $sortBy, $sortDir)
                            ]) }}">
                                Term
                                @if ($sortBy === 'term_id')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </a>
                        </th>
                        {{-- Amount --}}
                        <th class="text-right">Amount</th>
                        {{-- Due Date --}}
                        <th>Due Date</th>
                        {{-- Status --}}
                        <th>
                            <a href="{{ request()->fullUrlWithQuery([
                                'sort_by' => 'status',
                                'sort_dir' => nextDir('status', $sortBy, $sortDir)
                            ]) }}">
                                Status
                                @if ($sortBy === 'status')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </a>
                        </th>
                        {{-- Actions --}}
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            {{-- SL --}}
                            <td>{{ ($assignments->currentPage() - 1) * $assignments->perPage() + $loop->iteration }}</td>
                            {{-- Student ID & Name --}}
                            <td>
                                {{ $assignment->student?->student_id ?? '-' }} – {{ $assignment->student?->name ?? 'N/A' }}
                            </td>
                            <td>
                                {{ $assignment->studentEnrollment?->schoolClass?->name ?? 'N/A' }} /
                                {{ $assignment->studentEnrollment?->section?->section_name ?? 'N/A' }}
                            </td>
                            <td>
                                @php
                                    $ay = $assignment->studentEnrollment?->academicYear?->name
                                        ?? $assignment->student?->activeEnrollment?->academicYear?->name
                                        ?? 'N/A';
                                @endphp 
                                {{ $ay }}
                            </td>
                            <td>{{ $assignment->fee->fee_name }}</td>
                            <td>{{ $assignment->term?->name ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($assignment->amount, 2) }}</td>
                            <td>{{ $assignment->due_date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge badge-{{ $assignment->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('fee-assignments.edit', $assignment) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('fee-assignments.destroy', $assignment) }}" method="POST"
                                    style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this assignment?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            {{ $assignments->links() }}
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('#filter-student').select2({
            placeholder: $('#filter-student').data('placeholder'),
            allowClear: true,
            width: '200px',
            ajax: {
                url: '{{ route('students.ajax') }}',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term
                }),
                processResults: data => ({
                    results: data.results
                }),
                cache: true,
            },
            minimumInputLength: 1,
        });

        $('#printBtn').on('click', function (e) {
            e.preventDefault();
            // Collect current filter values
            let params = $('#filter-form').serialize();
            let url = '{{ route("fee-assignments.print") }}' + (params ? '?' + params : '');
            window.open(url, '_blank');
        });
    </script>
@endsection
