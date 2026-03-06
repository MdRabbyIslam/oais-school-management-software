@extends('layouts.app')

@section('subtitle', isset($feeGroup) ? 'Edit Fee Group' : 'Create Fee Group')
@section('content_header_title', isset($feeGroup) ? 'Edit Fee Group' : 'Create Fee Group')

@section('content_body')
<div class="card card-primary">
    <form method="POST"
          action="{{ isset($feeGroup) ? route('fee-groups.update', $feeGroup->id) : route('fee-groups.store') }}">
        @csrf
        @if(isset($feeGroup)) @method('PUT') @endif

        <div class="card-body">
            <div class="form-group">
                <label for="name">Group Name</label>
                <input type="text" name="name" id="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $feeGroup->name ?? '') }}" required>
                @error('name')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="type">Group Type</label>
                <select name="type" id="type"
                        class="form-control @error('type') is-invalid @enderror" required>
                    <option value="core" {{ old('type', $feeGroup->type ?? '') == 'core' ? 'selected' : '' }}>
                        Core Fee
                    </option>
                    <option value="service" {{ old('type', $feeGroup->type ?? '') == 'service' ? 'selected' : '' }}>
                        Service Fee
                    </option>
                    <option value="penalty" {{ old('type', $feeGroup->type ?? '') == 'penalty' ? 'selected' : '' }}>
                        Penalty
                    </option>
                </select>
                @error('type')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ isset($feeGroup) ? 'Update' : 'Save' }}
            </button>
            <a href="{{ route('fee-groups.index') }}" class="btn btn-default float-right">
                Cancel
            </a>
        </div>
    </form>
</div>
@stop
