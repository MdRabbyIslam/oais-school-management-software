@extends('layouts.app')

@section('title', 'Edit Fee Group')

@section('content_header_title', 'Edit Fee Group')
@section('content_header_subtitle', 'Modify')

@section('content_body')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Fee Group</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('fee-groups.update', $feeGroup->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Fee Group Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $feeGroup->name }}" required>
            </div>
            <button type="submit" class="btn btn-primary ">Update</button>
            <a href="{{ route('fee-groups.index') }}" class="btn btn-secondary">Cancel</a>

        </form>
    </div>
</div>
@endsection
