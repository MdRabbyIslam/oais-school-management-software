@php
    $isEdit = isset($group);
    $action = $isEdit ? route('fee-groups.update', $group->id) : route('fee-groups.store');
@endphp

@extends('layouts.app')

@section('subtitle', $isEdit ? 'Edit Fee Group' : 'Create Fee Group')
@section('content_header_title', $isEdit ? 'Edit Fee Group' : 'Create New Fee Group')

@section('content_body')
<div class="card card-primary">
    <form method="POST" action="{{ $action }}">
        @if($isEdit) @method('PUT') @endif
        @csrf

        <div class="card-body">
            <div class="form-group">
                <label for="name">Group Name*</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $group->name ?? '') }}" required>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="type">Fee Type*</label>
                <select class="form-control @error('type') is-invalid @enderror"
                        id="type" name="type" required>
                    <option value="core" {{ old('type', $group->type ?? '') == 'core' ? 'selected' : '' }}>Core Fee</option>
                    <option value="service" {{ old('type', $group->type ?? '') == 'service' ? 'selected' : '' }}>Service Fee</option>
                    <option value="penalty" {{ old('type', $group->type ?? '') == 'penalty' ? 'selected' : '' }}>Penalty</option>
                </select>
                @error('type')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ $isEdit ? 'Update' : 'Save' }}
            </button>
        </div>
    </form>
</div>
@stop
