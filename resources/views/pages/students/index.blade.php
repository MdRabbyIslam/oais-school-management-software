@extends('layouts.app')
@section('plugins.Select2', true)

@section('title', 'Student List')

@section('content_header_title', 'Students')
@section('content_header_subtitle', 'List')

@section('content_body')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Student List</h3>
        <a href="{{ route('admissions.create') }}" class="btn btn-sm btn-primary">Add New Student</a>
    </div>
    <div class="card-body ">
        {{-- show error or suceess message --}}

            @if(session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('error') }}
            </div>
            @endif

            <form method="GET" class="mb-3">
                <div class="form-row align-items-end">
                   <div class="col-md-9">
                        <div class="form-row align-items-end">
                            <!-- Class Filter -->
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

                        <!-- Section Filter -->
                        <div class="form-group col-md-3">
                            <label for="section_id">Section</label>
                            <select name="section_id" id="section_id" class="form-control select2" {{ request('class_id') ? '' : 'disabled' }}>
                                <option value="">All Sections</option>
                                {{-- JS will populate --}}
                            </select>
                        </div>


                        <!-- Buttons -->
                        <div class="form-group col-md-2 d-flex " style="gap: 10px">
                            <button type="submit" class="btn btn-primary  ">Filter</button>
                            <a href="{{ route('students.index') }}" class="btn btn-secondary ">Reset</a>
                        </div>
                        </div>
                   </div>

                      <!-- Search Box -->
                    <div class="form-group col-md-3">
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Search by name or ID…"

                             onchange="this.form.submit()"
                        >
                    </div>

                </div>
            </form>



        <div class="table-responsive mt-3">
            <table class="table table-hover table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Roll</th>
                        <th>Class</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>{{ $student->id }}</td>
                            <td>{{ $student->student_id }}</td>
                            <td>{{ $student->name }}</td>
                            <td>{{ optional($student->activeEnrollment)->roll_number ?? '-' }}</td>
                            <td>{{$student->section->schoolClass->name ?? '-'}} ({{ $student->section->section_name ?? '-' }} )</td>
                            <td>{{ $student->activeEnrollment && $student->activeEnrollment->academicYear ? $student->activeEnrollment->academicYear->name : '-' }}</td>
                            <td>
                                  <a href="{{ route('students.edit', $student->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="{{ route('enrollments.view', $student->id) }}" class="btn btn-sm btn-warning">Enrollment Settings</a>
                                <a href="{{ route('students.promote.create', $student->id) }}" class="btn btn-sm btn-primary">Promote</a>
                                <form action="{{ route('students.destroy', $student->id) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Are you sure to delete this student?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>

                               <form action="{{ route('sms.due', $student) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-info"
                                            onclick="return confirm('Send SMS about due fees to this student’s parent?')">
                                        <i class="fas fa-envelope"></i> Send Due-Fee SMS
                                    </button>
                                </form>
                                <a href="{{ route('students.manage-fees', $student) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-money-bill"></i> Manage Fees
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 ">
            {{ $students->links() }}
        </div>
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
</script>

@endsection


