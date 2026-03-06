@extends('layouts.app')

@section('subtitle', 'Create Fee')
@section('content_header_title', 'Create New Fee')

@section('content_body')
<div class="card card-primary">

        {{-- if error show errors on div.card  --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    <form method="POST" action="{{ route('fees.store') }}">
        @csrf

        <div class="card-body">
            <div class="form-group">
                <label for="fee_group_id">Fee Group*</label>
                <select name="fee_group_id" id="fee_group_id"
                        class="form-control @error('fee_group_id') is-invalid @enderror" required>
                    <option value="">Select Group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}"
                            {{ (old('fee_group_id', $fee_group->id ?? '') == $group->id) ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                @error('fee_group_id')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="fee_name">Fee Name*</label>
                <input type="text" name="fee_name" id="fee_name"
                       class="form-control @error('fee_name') is-invalid @enderror"
                       value="{{ old('fee_name') }}" required>
                @error('fee_name')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="billing_type">Billing Type*</label>
                <select name="billing_type" id="billing_type"
                        class="form-control @error('billing_type') is-invalid @enderror" required>
                    <option value="recurring" {{ old('billing_type') == 'recurring' ? 'selected' : '' }}>
                        Recurring
                    </option>
                    <option value="one-time" {{ old('billing_type') == 'one-time' ? 'selected' : '' }}>
                        One-Time
                    </option>
                    <option value="term-based" {{ old('billing_type') == 'term-based' ? 'selected' : '' }}>
                        Term-Based
                    </option>
                </select>
                @error('billing_type')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group" id="frequency-group">
                <label for="frequency">Frequency</label>
                <select name="frequency" id="frequency"
                        class="form-control @error('frequency') is-invalid @enderror">
                    <option value="">Select Frequency</option>
                    <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>
                        Monthly
                    </option>
                    <option value="quarterly" {{ old('frequency') == 'quarterly' ? 'selected' : '' }}>
                        Quarterly
                    </option>
                    <option value="termly" {{ old('frequency') == 'termly' ? 'selected' : '' }}>
                        Per Term
                    </option>
                    <option value="annual" {{ old('frequency') == 'annual' ? 'selected' : '' }}>
                        Annual
                    </option>
                </select>
                @error('frequency')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="is_mandatory" id="is_mandatory"
                           class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="is_mandatory">Mandatory Fee</label>
                </div>
            </div>

            <!-- Class Amounts Section -->
            <div class="form-group">
                <label>Class-Specific Amounts</label>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                            <tr>
                                <td>{{ $class->name }}</td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                           name="class_amounts[{{ $class->id }}]"
                                           class="form-control"
                                           value="{{ $classAmounts[$class->id] ?? '' }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save
            </button>
            <a href="{{ route('fees.index') }}" class="btn btn-default float-right">
                Cancel
            </a>
        </div>
    </form>
</div>

@push('js')
<script>
    $(document).ready(function() {
        // Show/hide frequency based on billing type
        function toggleFrequency() {
            if ($('#billing_type').val() === 'recurring') {
                $('#frequency-group').show();
            } else {
                $('#frequency-group').hide();
            }
        }

        $('#billing_type').change(toggleFrequency);
        toggleFrequency(); // Initial call
    });
</script>
@endpush
@stop
