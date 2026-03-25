@extends('layouts.app')

@section('title', 'Bulk Class Test Marks')
@section('content_header_title', 'Class Tests')
@section('content_header_subtitle', 'Bulk Marks')

@section('content_body')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Select Class Test Event (All Subjects)</h3>
    </div>
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <p class="text-muted mb-3">
            Step 1: Choose Academic Year, Term, and Class. Step 2: Select the class test event. Step 3: Click "Open Bulk Marks Sheet".
        </p>

        <form method="GET" action="{{ route('class-tests.marks.bulk.index') }}" id="bulk-marks-filter-form">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Academic Year</label>
                    <select name="academic_year_id" id="academic_year_id" class="form-control">
                        <option value="">All</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ (string) request('academic_year_id') === (string) $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Term</label>
                    <select name="term_id" id="term_id" class="form-control">
                        <option value="">All</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" data-academic-year-id="{{ $term->academic_year_id }}" {{ (string) request('term_id') === (string) $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Class</label>
                    <select name="class_id" id="class_id" class="form-control">
                        <option value="">All</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Class Test Event</label>
                    <select name="class_test_id" id="class_test_id" class="form-control">
                        <option value="">Select Class Test Event</option>
                        @foreach($testOptions as $test)
                        <option value="{{ $test->id }}" {{ (string) request('class_test_id') === (string) $test->id ? 'selected' : '' }}>
                                {{ ($test->name ?? 'Class Test') }} | {{ $test->test_date ? \Carbon\Carbon::parse($test->test_date)->format('d M Y') : '-' }} | {{ $test->subjects_count }} subject(s)
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <small class="form-text text-muted mb-3">
                Class test event list updates instantly after changing filters. Use the button below after selecting a class test event.
            </small>

            <button type="submit" name="action" value="go" id="openBulkMarksBtn" class="btn btn-primary">Open Bulk Marks Sheet</button>
            <a href="{{ route('class-tests.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('bulk-marks-filter-form');
        const academicYearSelect = document.getElementById('academic_year_id');
        const termSelect = document.getElementById('term_id');
        const classSelect = document.getElementById('class_id');
        const classTestSelect = document.getElementById('class_test_id');
        const openBulkMarksBtn = document.getElementById('openBulkMarksBtn');
        const eventsEndpoint = "{{ route('class-tests.marks.bulk.events') }}";
        const termOptions = Array.from(termSelect.querySelectorAll('option[value]'));
        const oldTerm = "{{ request('term_id') }}";
        const oldClassTestId = "{{ request('class_test_id') }}";

        function filterTermsByYear(academicYearId) {
            termOptions.forEach((option) => {
                const show = !academicYearId || option.dataset.academicYearId === academicYearId;
                option.hidden = !show;
                option.disabled = !show;
            });
        }

        academicYearSelect.addEventListener('change', function () {
            filterTermsByYear(this.value);
            if (termSelect.selectedOptions.length && termSelect.selectedOptions[0].disabled) {
                termSelect.value = '';
            }
            loadEventOptions();
        });

        termSelect.addEventListener('change', function () {
            loadEventOptions();
        });

        classSelect.addEventListener('change', function () {
            loadEventOptions();
        });

        classTestSelect.addEventListener('change', toggleOpenButton);

        filterTermsByYear(academicYearSelect.value);
        if (oldTerm) {
            const selected = termSelect.querySelector(`option[value="${oldTerm}"]`);
            if (selected && !selected.disabled) {
                termSelect.value = oldTerm;
            }
        }

        function renderEventOptions(events, selectedId = '') {
            classTestSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select Class Test Event';
            classTestSelect.appendChild(placeholder);

            events.forEach((event) => {
                const option = document.createElement('option');
                option.value = String(event.id);
                const dateText = event.test_date ? formatDate(event.test_date) : '-';
                option.textContent = `${event.name} | ${dateText} | ${event.subjects_count} subject(s)`;
                if (selectedId && String(event.id) === String(selectedId)) {
                    option.selected = true;
                }
                classTestSelect.appendChild(option);
            });
        }

        function formatDate(isoDate) {
            const date = new Date(isoDate);
            if (Number.isNaN(date.getTime())) {
                return '-';
            }
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        async function loadEventOptions(preserveSelected = false) {
            const params = new URLSearchParams({
                academic_year_id: academicYearSelect.value || '',
                term_id: termSelect.value || '',
                class_id: classSelect.value || '',
            });

            classTestSelect.disabled = true;
            openBulkMarksBtn.disabled = true;

            try {
                const response = await fetch(`${eventsEndpoint}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                if (!response.ok) {
                    throw new Error('Failed to load class test events.');
                }

                const payload = await response.json();
                const selectedId = preserveSelected ? classTestSelect.value || oldClassTestId : '';
                renderEventOptions(payload.events || [], selectedId);
            } catch (error) {
                renderEventOptions([], '');
            } finally {
                classTestSelect.disabled = false;
                toggleOpenButton();
            }
        }

        function toggleOpenButton() {
            openBulkMarksBtn.disabled = !classTestSelect.value;
        }

        loadEventOptions(true);
        form.addEventListener('submit', function () {
            if (!classTestSelect.value) {
                return;
            }
            form.action = "{{ route('class-tests.marks.bulk.create', ['classTest' => '__ID__']) }}".replace('__ID__', classTestSelect.value);
        });
    })();
</script>
@endsection
