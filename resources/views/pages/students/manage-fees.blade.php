@extends('layouts.app')

@section('title', 'Manage Fee Assignments')

@section('content_header_title', 'Manage Fee Assignments')
@section('content_header_subtitle', $student->name . ' ('.$student->student_id.')')

@section('content_body')
<div class="card">

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('students.update-fees', $student) }}">
            @csrf

            <h5>Assigned Fees</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Edit?</th>
                        <th>Fee</th>
                        <th>Term</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assigned as $key => $a)
                        <tr>
                            <td>
                                <input type="checkbox" name="edit_assigned_keys[]" value="{{ $a->fee->id }}{{ $a->fee->billing_type === 'term-based' ? '-' . $a->term_id : '' }}" onchange="toggleAssignFields(this)">
                            </td>
                            <td>
                                {{ $a->fee->fee_name }}
                                <input type="hidden" name="assigned_ids[]" value="{{ $a->fee->id }}{{ $a->fee->billing_type === 'term-based' ? '-' . $a->term_id : '' }}" disabled>
                            </td>
                            <td>
                                @if($a->fee->billing_type === 'term-based')
                                    {{ $a->term->name ?? '' }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td><input type="number" name="assigned_amounts[]" value="{{ $a->amount }}" disabled required></td>
                            <td><input type="date" name="assigned_due_dates[]" value="{{ $a->due_date ? $a->due_date->format('Y-m-d') : '' }}" disabled required></td>
                            <td>
                                <select name="assigned_statuses[]" disabled required>
                                    <option value="active" {{ $a->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="cancelled" {{ $a->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    <option value="completed" {{ $a->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h5>Unassigned Fees</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Assign?</th>
                        <th>Fee</th>
                        <th>Term</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unassigned as $i => $row)
                        <tr>
                            <td>
                                <input type="checkbox" name="assign_keys[]" value="{{ $row['fee']->id }}{{ $row['fee']->billing_type === 'term-based' ? '-' . $row['term']->id : '' }}" onchange="toggleAssignFields(this, {{ $i }})">
                            </td>
                            <td>{{ $row['fee']->fee_name }}</td>
                            <td>
                                @if($row['fee']->billing_type === 'term-based')
                                    {{ $row['term']->name }}
                                    <input type="hidden" name="assign_term_ids[]" value="{{ $row['term']->id }}">
                                @else
                                    <span class="text-muted">N/A</span>
                                    <input type="hidden" name="assign_term_ids[]" value="">
                                @endif
                            </td>
                            <td>
                                <input type="number" name="assign_amounts[]" step="0.01" disabled required value="{{ $row['default_amount'] }}">
                            </td>
                            <td>
                                <input type="date" name="assign_due_dates[]" disabled required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="submit" class="btn btn-primary">Save Assignments</button>
        </form>

        <script>

        function toggleAssignFields(checkbox) {
            const row = checkbox.closest('tr');
            row.querySelectorAll('input,select').forEach(el => {
                if (el !== checkbox) {
                    el.disabled = !checkbox.checked;
                    el.required = checkbox.checked;
                }
            });
        }
        </script>
    </div>
</div>
@endsection
