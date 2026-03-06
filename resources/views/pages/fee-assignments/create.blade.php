@extends('layouts.app')
@section('plugins.Select2', true)


@section('subtitle', 'Assign Fees')
@section('content_header_title', 'Flexible Fee Assignment')

@section('content_body')
    <div class="card card-primary">

        {{-- display validation errors --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('fee-assignments.bulk-store') }}">
            @csrf



            <div class="card-body">
                <!-- Academic Years Selectbox -->
                <div class="form-group">
                    <label for="academic_year_id">Academic Year*</label>
                    <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" {{ old('academic_year_id') == $academicYear->id ? 'selected' : '' }}>
                                {{ $academicYear->name }} ({{ $academicYear->start_date->format('Y') }} - {{ $academicYear->end_date->format('Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Assignment Scope -->
                <div class="form-group">
                    <label>Assign To*</label>
                    <select name="scope" id="scope" class="form-control" required>
                        <option value="">Select</option>
                        <option value="student" {{ old('scope') == 'student' ? 'selected' : '' }}>Single Student</option>
                        <option value="class" {{ old('scope') == 'class' ? 'selected' : '' }}>Entire Class</option>
                        <option value="section" {{ old('scope') == 'section' ? 'selected' : '' }}>Specific Section</option>
                        <option value="all" {{ old('scope') == 'all' ? 'selected' : '' }}>All Active Students</option>
                    </select>
                </div>

                <!-- Dynamic Fields Based on Scope -->
                {{-- <div id="student-field" class="scope-field" style="display: none;">
                    <div class="form-group">
                        <label for="student_id">Student*</label>
                        <select name="student_id" id="student_id" class="form-control select2">
                            <option value="">Select</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}"
                                    {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} ({{ $student->schoolClass->name }} -
                                    {{ $student->section->section_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}

                {{-- <div id="student-field" class="scope-field" style="display: none;">
                    <div class="form-group">
                        <label for="student_id">Student*</label>
                        <select name="student_id"
                                id="student_id"
                                class="form-control select2"
                                data-placeholder="Search by student ID or name…">
                        <option value=""></option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}"
                            {{ old('student_id') == $student->id ? 'selected' : '' }}>
                            {{ $student->student_id }} – {{ $student->name }} - {{ $student->schoolClass->name }} - {{ $student->section->section_name }}
                            </option>
                        @endforeach
                        </select>
                    </div>
                </div> --}}

                <div id="student-field" class="scope-field" style="display: none;">
                    <div class="form-group">
                        <label for="student_id">Student*</label>
                        <select name="student_id" id="student_id" class="form-control select2">
                            <option value="">Select Academic Year First</option>
                        </select>
                        <small class="text-muted">Only students enrolled in the selected academic year are shown.</small>
                    </div>
                </div>


                <div id="class-field" class="scope-field" style="display: none;">
                    <div class="form-group">
                        <label for="class_id">Class*</label>
                        <select name="class_id" id="class_id" class="form-control">
                            <option value="">Select</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="section-field" class="scope-field" style="display: none;">
                    <div class="form-group">
                        <label for="section_id">Section*</label>
                        <select name="section_id" id="section_id" class="form-control">
                            <option value="">Select</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}"
                                    {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                    {{ $section->schoolClass->name }} - {{ $section->section_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Fee Selection -->
                <div class="form-group">
                    <label for="fee_id">Fee Type*</label>
                    <select name="fee_id" id="fee_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach ($fees as $fee)
                            <option value="{{ $fee->id }}" data-type="{{ $fee->billing_type }}"
                                data-class-amounts="{{ $fee->classFeeAmounts->pluck('amount', 'class_id')->toJson() }}"
                                {{ old('fee_id') == $fee->id ? 'selected' : '' }}>
                                {{ $fee->fee_name }} ({{ ucfirst($fee->billing_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Term Selection (Conditional) -->
                <div id="term-field" class="form-group" style="display: none">
                    <label for="term_id">Term*</label>
                    <select name="term_id" id="term_id" class="form-control">
                        <option value="">Select</option>
                        @foreach ($terms as $term)
                            <option value="{{ $term->id }}" {{ old('term_id') == $term->id ? 'selected' : '' }}>
                                {{ $term->name }} ({{ $term->start_date->format('M d, Y') }} -
                                {{ $term->end_date->format('M d, Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount Management -->
                <div class="form-group">
                    <label>Amount Handling*</label>
                    <div class="form-check">
                        <input type="radio" name="amount_type" id="use-class-default" value="class_default"
                            {{ old('amount_type', 'class_default') == 'class_default' ? 'checked' : '' }}>
                        <label for="use-class-default">Use Class Default Amounts</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" name="amount_type" id="custom-amount" value="custom"
                            {{ old('amount_type') == 'custom' ? 'checked' : '' }}>
                        <label for="custom-amount">Set Custom Amount</label>
                    </div>
                    <div id="custom-amount-field" style="display:none; margin-top:10px">
                        <input type="number" name="custom_amount" class="form-control" value="{{ old('custom_amount') }}"
                            placeholder="Enter amount…">
                    </div>
                </div>

                <!-- Dates -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="due_date">Due Date*</label>
                            <input type="date" name="due_date" class="form-control"  value="{{ old('due_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">Effective From (Optional)</label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Preview Button -->
                <div class="form-group">
                    <button type="button" id="preview-btn" class="btn btn-info">
                        <i class="fas fa-eye"></i> Preview Affected Students
                    </button>
                </div>

                <!-- Preview Results -->
                <div id="preview-results">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> This will affect:</h5>
                        <div id="affected-count">0 students</div>
                        <div id="sample-students" class="mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Confirm Assignments
                </button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            $(document).ready(function() {
                // Toggle scope fields
                $('#scope').change(function() {
                    $('.scope-field').hide();
                    $(`#${this.value}-field`).show();

                     $(`#${this.value}-field`).find('select').prop('required', true);
                });

                // Toggle term field based on fee type
                $('#fee_id').change(function() {
                    const isTermBased = $(this).find(':selected').data('type') === 'term-based';
                    $('#term_id').prop('required', isTermBased);
                    $('#term-field').toggle(isTermBased);
                });

                // Toggle custom amount field
                $('input[name="amount_type"]').change(function() {
                    $('#custom-amount-field').toggle(this.value === 'custom');
                });

                // Preview affected students
                $('#preview-btn').click(function() {
                    const formData = {
                        academic_year_id: $('#academic_year_id').val(),
                        scope: $('#scope').val(),
                        student_id: $('#student_id').val(),
                        class_id: $('#class_id').val(),
                        section_id: $('#section_id').val(),
                        _token: '{{ csrf_token() }}'
                    };

                    $.post('{{ route('fee-assignments.preview') }}', formData, function(data) {
                        $('#affected-count').text(`${data.count} students`);

                        let sampleHtml = '';
                        data.sample.forEach(student => {
                            sampleHtml +=
                                `<div>${student.name} (${student.class}, ${student.section})</div>`;
                        });

                        if (data.count > data.sample.length) {
                            sampleHtml += `<div>...and ${data.count - data.sample.length} more</div>`;
                        }

                        $('#sample-students').html(sampleHtml);
                        $('#preview-results').show();
                    });
                });



                // 1) Scope panel
                const oldScope = '{{ old('scope') }}';
                if (oldScope) {
                    $('#scope').val(oldScope).trigger('change');
                }

                // 2) Fee & term panel
                const oldFee = '{{ old('fee_id') }}';
                if (oldFee) {
                    $('#fee_id').val(oldFee).trigger('change');
                }

                // 3) Amount type panel
                const oldAmtType = '{{ old('amount_type', 'class_default') }}';
                $('input[name="amount_type"][value="' + oldAmtType + '"]').prop('checked', true).trigger('change');

                // 4) Custom amount value is already set via blade value="{{ old('custom_amount') }}"
            });
        </script>


        <script>
       

        $(document).ready(function() {
            const studentSelect = $('#student_id').select2({
                placeholder: "Select a student",
                allowClear: true,
                width: '100%'
            });

            $('#academic_year_id').change(function() {
                const yearId = $(this).val();
                
                // Reset student selection
                studentSelect.val(null).trigger('change');
                
                if (!yearId) {
                    studentSelect.html('<option value="">Select Academic Year First</option>');
                    return;
                }

                // Fetch filtered students
                $.get("{{ route('fee-assignments.get-students-by-year') }}", { academic_year_id: yearId }, function(data) {
                    let options = '<option value=""></option>';
                    data.forEach(function(item) {
                        options += `<option value="${item.id}">${item.text}</option>`;
                    });
                    studentSelect.html(options).trigger('change');
                });
            });

            // Trigger change on load if year is already selected (for old input)
            if ($('#academic_year_id').val()) {
                $('#academic_year_id').trigger('change');
            }
        });
        </script>

    @endpush
@stop
