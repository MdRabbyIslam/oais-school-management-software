@extends('layouts.app')
@section('plugins.Select2', true)
@section('title', 'Promotions')

@section('content_header_title', 'Promotions')
@section('content_header_subtitle', 'Pending Approvals')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Pending Promotions</h3>
        <div>
            <a href="{{ route('promotions.bulk.form') }}" class="btn btn-sm btn-primary">Bulk Promote</a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-3">
            <div class="form-row align-items-end">
                <div class="col-md-9">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="class_id">Class</label>
                            <select name="class_id" id="class_id" class="form-control select2">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label for="section_id">Section</label>
                            <select name="section_id" id="section_id" class="form-control select2" {{ request('class_id') ? '' : 'disabled' }}>
                                <option value="">All Sections</option>
                            </select>
                        </div>

                        <div class="form-group col-md-2 d-flex" style="gap:10px">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('promotions.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </div>

                <div class="form-group col-md-3">
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name or ID…" onchange="this.form.submit()">
                </div>
            </div>
        </form>

        <form method="POST" id="bulk-action-form">
            @csrf
            <div class="table-responsive">
                <div class="mb-2 d-flex justify-content-end">
                    <button type="submit"
                            formaction="{{ route('promotions.bulk.approve') }}"
                            class="btn btn-sm btn-success mr-2"
                            onclick="return confirm('Approve selected promotions?')">
                        Bulk Approve
                    </button>

                    <button type="submit"
                            formaction="{{ route('promotions.bulk.reject') }}"
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Reject selected promotions?')">
                        Bulk Reject
                    </button>
                </div>
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="select_all"></th>
                            <th>ID</th>
                            <th>Student</th>
                            <th>From (Class / Section)</th>
                            <th>To (Year / Class / Section)</th>
                            <th>Requested By</th>
                            <th>Requested At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $p)
                            <tr>
                                <td><input type="checkbox" name="selected_promotions[]" value="{{ $p->id }}"></td>
                                <td>{{ $p->id }}</td>
                                <td>
                                    <a href="{{ route('students.edit', $p->student->id) }}">
                                        {{ $p->student->student_id }} — {{ $p->student->name }}
                                    </a>
                                </td>
                                <td>
                                    {{ $p->fromEnrollment->schoolClass->name ?? '-' }} /
                                    {{ $p->fromEnrollment->section->section_name ?? '-' }}
                                </td>
                                <td>
                                    {{ $p->targetAcademicYear->name ?? '-' }} /
                                    {{ $p->targetClass->name ?? '-' }} /
                                    {{ $p->targetSection->section_name ?? '-' }}
                                </td>
                                <td>{{ $p->requestedBy->name ?? 'System' }}</td>
                                <td>{{ optional($p->requested_at)->toDateTimeString() }}</td>
                                <td>
                                    <a href="{{ route('promotions.show', $p->id) }}" class="btn btn-sm btn-info">Details</a>

                                    <form method="POST" action="{{ route('promotions.approve', $p->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Approve this promotion?')">Quick Approve</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">No pending promotions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div class="mt-3">{{ $promotions->links() }}</div>
    </div>
</div>

@stop

@section('js')
<script>
    const allSections = @json($sections);
    $('#class_id, #section_id').select2({ placeholder: 'Select…', allowClear: true });

    $('#class_id').on('change', function(){
        const classId = $(this).val();
        const filtered = allSections.filter(s => s.class_id == classId);
        const $sec = $('#section_id').empty().trigger('change');
        $sec.append(new Option('All Sections', '', true, false));
        filtered.forEach(s => {
            const opt = new Option(s.section_name, s.id, false, false);
            $sec.append(opt);
        });
        $sec.prop('disabled', !classId).trigger('change');
    });

    // Select all handler for bulk approve
    $('#select_all').on('change', function(){
        $('input[name="selected_promotions[]"]').prop('checked', $(this).is(':checked'));
    });
</script>
@endsection
