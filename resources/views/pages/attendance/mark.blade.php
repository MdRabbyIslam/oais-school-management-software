@extends('layouts.app')

@section('title', 'Mark Attendance')
@section('content_header_title', 'Attendance')
@section('content_header_subtitle', 'Mark by Section & Date')

@section('css')
<style>
      select.is-invalid {
    border-color: #dc3545;
  }
</style>
@endsection

@section('content_body')
<div class="card">
    <div class="card-body">
        <form action="{{ route('attendance.create') }}" method="GET" class="form-inline mb-3">
            <div class="form-group mr-2">
                <label for="section_id" class="mr-2">Section</label>
                <select name="section_id" class="form-control" required>
                    <option value="">Choose Section</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->section_name }} (Class: {{ $section->schoolClass->name ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="date" class="mr-2">Date</label>
                <input type="date" name="date" class="form-control" value="{{ request('date', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}">
            </div>
            <button type="submit" class="btn btn-primary">Load Students</button>
        </form>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Success message --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(!empty($students) && count($students) > 0)
            <form action="{{ route('attendance.store') }}" method="POST" id="attendanceForm">
                @csrf



                <input type="hidden" name="section_id" value="{{ request('section_id') }}">
                <input type="hidden" name="date" value="{{ request('date', today()->format('Y-m-d')) }}">

                <div class="mb-3 d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-success btn-sm mr-2" onclick="markAll('Present')">Present All</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="markAll('Absent')">Absent All</button>
                </div>


                <!-- Error message container -->
                <div id="attendanceError" class="alert alert-danger d-none mb-2"></div>

                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $student->name }}</td>
                                <td>
                                    <select name="attendance[{{ $student->id }}]" class="form-control" required>
                                        <option value="">Select</option>
                                        {{-- <option value="Present" {{ (isset($existing[$student->id]) && $existing[$student->id] == 'Present') ? 'selected' : '' }}>Present</option>
                                        <option value="Absent" {{ (isset($existing[$student->id]) && $existing[$student->id] == 'Absent') ? 'selected' : '' }}>Absent</option> --}}
                                        <option value="Present" {{ (isset($existing[$student->id]) && $existing[$student->id]=='Present')?'selected':'' }}>Present</option>
                                        <option value="Absent"  {{ (isset($existing[$student->id]) && $existing[$student->id]=='Absent')?'selected':'' }}>Absent</option>

                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-right mt-3">
                    <button type="button" class="btn btn-success" onclick="confirmSubmit()">Confirm & Save</button>
                </div>
            </form>
        @elseif(request('section_id'))
            <p class="text-danger">No students found in this section.</p>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
    function markAll(status) {
        document.querySelectorAll('select[name^="attendance"]').forEach(select => {
            select.value = status;
            select.classList.remove('is-invalid'); // clear invalid on bulk set
        });
        hideError();
    }

    function showError(msg) {
        const el = document.getElementById('attendanceError');
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideError() {
        const el = document.getElementById('attendanceError');
        el.textContent = '';
        el.classList.add('d-none');
    }

    function confirmSubmit() {
        hideError();

        const selects = document.querySelectorAll('select[name^="attendance"]');
        let missing = false;

        selects.forEach(sel => {
            if (!sel.value) {
                sel.classList.add('is-invalid');
                missing = true;
            } else {
                sel.classList.remove('is-invalid');
            }
        });

        if (missing) {
            showError('Please select a status for every student before submitting.');
            return;
        }

        if (confirm('Are you sure you want to submit attendance for the selected date?')) {
            document.getElementById('attendanceForm').submit();
        }
    }

    // Remove red highlight when user picks a value
    document.querySelectorAll('select[name^="attendance"]').forEach(sel => {
        sel.addEventListener('change', () => {
            if (sel.value) {
                sel.classList.remove('is-invalid');
            }
        });
    });
</script>

@endsection
