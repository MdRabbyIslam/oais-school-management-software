
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


            <!-- Personal Information Section -->
            <div class="card mb-3">
                <div style="cursor: pointer;" class="card-header d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#collapsePersonalInfo" aria-expanded="true" aria-controls="collapsePersonalInfo">
                    <h5 class="mb-0">Personal Information</h5>

                </div>
                <div id="collapsePersonalInfo" class="collapse show">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name">Student Name</label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $student->name ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="dob">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" value="{{ old('dob', $student->dob ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="blood_group">Blood Group</label>
                                    <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $student->blood_group ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guardian Information Section -->
            <div class="card mb-3">
                <div style="cursor: pointer;" class="card-header d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#collapseGuardianInfo" aria-expanded="true" aria-controls="collapseGuardianInfo">
                    <h5 class="mb-0">Guardian Information</h5>

                </div>
                <div id="collapseGuardianInfo" class="collapse">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primary_guardian_name">Primary Guardian Name</label>
                                    <input type="text" name="primary_guardian_name" class="form-control" value="{{ old('primary_guardian_name', $student->primary_guardian_name ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primary_guardian_contact">Primary Guardian Contact</label>
                                    <input type="text" name="primary_guardian_contact" class="form-control" value="{{ old('primary_guardian_contact', $student->primary_guardian_contact ?? '') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primary_guardian_relation">Primary Guardian Relation</label>
                                    <input type="text" name="primary_guardian_relation" class="form-control" value="{{ old('primary_guardian_relation', $student->primary_guardian_relation ?? '') }}" required>
                                </div>
                            </div>
                        </div>

                            <!-- Secondary Guardian Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secondary_guardian_name">Secondary Guardian Name</label>
                                    <input
                                        type="text"
                                        name="secondary_guardian_name"
                                        class="form-control"
                                        value="{{ old('secondary_guardian_name', $student->secondary_guardian_name ?? '') }}"
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secondary_guardian_contact">Secondary Guardian Contact</label>
                                    <input
                                        type="text"
                                        name="secondary_guardian_contact"
                                        class="form-control"
                                        value="{{ old('secondary_guardian_contact', $student->secondary_guardian_contact ?? '') }}"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secondary_guardian_relation">Secondary Guardian Relation</label>
                                    <input
                                        type="text"
                                        name="secondary_guardian_relation"
                                        class="form-control"
                                        value="{{ old('secondary_guardian_relation', $student->secondary_guardian_relation ?? '') }}"
                                    >
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>

            <!-- Section Information Section -->
            {{-- <div class="card mb-3">
                <div style="cursor: pointer;" class="card-header d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#collapseSectionInfo" aria-expanded="true" aria-controls="collapseSectionInfo">
                    <h5 class="mb-0">Section Information</h5>

                </div>
                <div id="collapseSectionInfo" class="collapse">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="section_id">Section</label>
                            <select name="section_id" class="form-control" required>
                                <option value="">Select Section</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" {{ (old('section_id', $student->section_id ?? '') == $section->id) ? 'selected' : '' }}>
                                        {{ $section->section_name }} (Class: {{ $section->schoolClass->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div> --}}

            <!-- Fee Assignment Section -->
            {{-- <div class="card mb-3">
                <div style="cursor: pointer;" class="card-header d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#collapseFeeAssignment" aria-expanded="true" aria-controls="collapseFeeAssignment">
                    <h5 class="mb-0">Fee Assignment</h5>

                </div>
                <div id="collapseFeeAssignment" class="collapse">
                    <div class="card-body">
                        <div class="fee-assignment-section"">
                            @foreach($feeGroups as $feeGroup)
                                <div class="fee-group mb-3">
                                    <h6>{{ $feeGroup->name }}</h6>
                                    <div class="fee-group-items">
                                        <div class="row">
                                            @foreach($feeGroup->fees as $fee)
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input type="checkbox" name="fee_ids[]" value="{{ $fee->id }}" id="fee_{{ $fee->id }}"
                                                    class="form-check-input"
                                                    @if(in_array($fee->id, old('fee_ids', isset($student) ? $student->fees->pluck('id')->toArray() : [])))
                                                        checked
                                                    @endif
                                                    @if($fee->is_mandatory)checked  @endif
                                                >
                                                <label class="form-check-label" for="fee_{{ $fee->id }}">
                                                    {{ $fee->fee_name }}
                                                    @if($fee->is_mandatory)
                                                        <span class="badge badge-success ml-2">Mandatory</span>
                                                    @endif
                                                </label>
                                                </div>

                                            </div>
                                        @endforeach
                                        </div>

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div> --}}

            <!-- Admission Details Section -->
            {{-- <div class="card mb-3">
                <div style="cursor: pointer;" class="card-header d-flex justify-content-between align-items-center"  data-toggle="collapse" data-target="#collapseAdmissionDetails" aria-expanded="true" aria-controls="collapseAdmissionDetails">
                    <h5 class="mb-0">Admission Details</h5>

                </div>
                <div id="collapseAdmissionDetails" class="collapse">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admission_date">Admission Date</label>
                                    <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $student->admission_date ?? '') }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="birth_certificate_path">Birth Certificate (optional)</label>
                                    <input type="file" name="birth_certificate_path" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="marksheet_path">Previous Marksheets (optional)</label>
                                    <input type="file" name="marksheet_path" class="form-control">
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div> --}}
            <button type="submit" class="btn btn-success">Submit</button>
            <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
