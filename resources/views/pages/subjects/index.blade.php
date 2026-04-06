@extends('layouts.app')
@section('plugins.SortableJS', true)

@section('title', 'Subject List')

@section('content_header_title', 'Subjects')
@section('content_header_subtitle', 'List')

@section('css')
<style>
    #subjects-tbody tr { transition: background-color .15s ease; }
    #subjects-tbody td.drag-cell { width: 48px; text-align: center; }
    .bg-light { background-color: #f8f9fa !important; }
</style>
@endsection

@section('content_body')
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Subject List</h3>
        <a href="{{ route('subjects.create') }}" class="btn btn-sm btn-primary">Add New Subject</a>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('subjects.index') }}" class="form-row align-items-end">
            <div class="form-group col-md-4 mb-2">
                <label for="class_id">Choose Class for Ordering</label>
                <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedClass)
                <div class="form-group col-md-8 mb-2">
                    <div class="alert alert-info mb-0 py-2">
                        Drag rows to set subject serial for <strong>{{ $selectedClass->name }}</strong>, then click <strong>Save order</strong>.
                    </div>
                </div>
            @endif
        </form>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    @if($selectedClass)
                        <th style="width:48px;"></th>
                        <th>Serial</th>
                    @endif
                    <th>ID</th>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="subjects-tbody">
                @forelse($subjects as $subject)
                    <tr data-id="{{ $subject->id }}">
                        @if($selectedClass)
                            <td class="text-muted drag-cell" style="cursor:grab;" title="Drag to reorder">☰</td>
                            <td>{{ $subject->pivot->sort_order ?? '' }}</td>
                        @endif
                        <td>{{ $subject->id }}</td>
                        <td>{{ $subject->name }}</td>
                        <td>{{ $subject->code }}</td>
                        <td>{{ $subject->description }}</td>
                        <td>
                            <a href="{{ route('subjects.edit', $subject->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('subjects.destroy', $subject->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Are you sure to delete this subject?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $selectedClass ? 7 : 5 }}" class="text-center">No subjects found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($selectedClass && $subjects->count())
        <div class="card-footer d-flex gap-2 align-items-center">
            <button id="save-order" class="btn btn-primary btn-sm">Save order</button>
            <span id="save-status" class="text-muted ml-2"></span>
        </div>
    @endif
</div>
@endsection

@section('js')
@if($selectedClass && $subjects->count())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('subjects-tbody');
    if (!tbody || typeof Sortable === 'undefined') return;

    new Sortable(tbody, {
        handle: '.drag-cell',
        animation: 150,
        ghostClass: 'bg-light',
    });

    function getOrderedIds() {
        return Array.from(tbody.querySelectorAll('tr[data-id]')).map(tr => parseInt(tr.dataset.id, 10));
    }

    async function saveOrder(ids) {
        const status = document.getElementById('save-status');
        status.textContent = 'Saving...';

        const resp = await fetch(@json(route('classes.subjects.reorder', $selectedClass)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ order: ids })
        });

        if (!resp.ok) {
            status.textContent = 'Save failed';
            return;
        }

        status.textContent = 'Saved!';
        Array.from(tbody.querySelectorAll('tr[data-id]')).forEach((tr, index) => {
            tr.children[1].textContent = index + 1;
        });
        setTimeout(() => status.textContent = '', 2000);
    }

    document.getElementById('save-order').addEventListener('click', function () {
        saveOrder(getOrderedIds());
    });
});
</script>
@endif
@endsection
