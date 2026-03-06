@extends('layouts.app')

@section('title', 'Edit Admission Application')
@section('content_header_title', 'Edit Application')
@section('content_header_subtitle', $admission->application_no)

@section('content_body')
<div class="card">
    <div class="card-header"><h3 class="card-title">Update Details</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admissions.update', $admission->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3 p-2 border rounded">
                <h5 class="mb-2">Student Details</h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="academic_year_id">Academic Year</label>
                        <select name="academic_year_id" id="academic_year_id" class="form-control">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $admission->academic_year_id == $ay->id ? 'selected' : '' }}>
                                    {{ $ay->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="preferred_class_id">Preferred Class</label>
                        <select name="preferred_class_id" id="preferred_class_id" class="form-control">
                            <option value="">--</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $admission->preferred_class_id == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="name">Student Name</label>
                        <input type="text" name="name" id="name" class="form-control" required value="{{ old('name', $admission->name) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="dob">DOB</label>
                        <input type="date" name="dob" id="dob" class="form-control" value="{{ $admission->dob ? $admission->dob->format('Y-m-d') : '' }}">
                    </div>
                    <div class="form-group col-md-9">
                        <label for="address">Address</label>
                        <input type="text" name="address" id="address" class="form-control" value="{{ old('address', $admission->address) }}">
                    </div>
                </div>
            </div>

            <div class="mb-3 p-2 border rounded">
                <h5 class="mb-2">Guardian Details</h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="primary_guardian_name">Primary Guardian</label>
                        <input type="text" name="primary_guardian_name" id="primary_guardian_name" class="form-control" value="{{ $admission->primary_guardian_name }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="primary_guardian_relation">Relation</label>
                        <input type="text" name="primary_guardian_relation" id="primary_guardian_relation" class="form-control" value="{{ $admission->primary_guardian_relation }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="primary_guardian_contact">Guardian Contact</label>
                        <input type="text" name="primary_guardian_contact" id="primary_guardian_contact" class="form-control" value="{{ $admission->primary_guardian_contact }}">
                    </div>
                </div>
            </div>

            <div class="mb-3 p-2 border rounded">
                <h5 class="mb-2">Documents</h5>
                <div class="form-row">
                    @foreach(\App\Models\AdmissionDocument::TYPES as $key => $label)
                        <div class="form-group col-md-6 border-bottom pb-2">
                            <label for="documents[{{ $key }}]">{{ $label }}</label>
                            
                            @php $existingDoc = $admission->documents->where('type', $key)->first(); @endphp
                            @if($existingDoc)
                                <div class="mb-1">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle"></i> Currently: 
                                        <a href="{{ Storage::disk('public_upload')->url($existingDoc->path) }}" target="_blank">
                                            {{ $existingDoc->original_name }}
                                        </a>
                                    </small>
                                </div>
                            @endif

                            <input type="file" name="documents[{{ $key }}]" class="form-control-file">
                            <small class="form-text text-muted">Upload new to replace existing {{ strtolower($label) }}.</small>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update Application</button>
                <a href="{{ route('admissions.show', $admission->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@stop