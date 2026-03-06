@extends('layouts.app')

@section('title', 'Create Grade Scheme')
@section('content_header_title', 'Grade Schemes')
@section('content_header_subtitle', 'Create')

@section('content_body')
<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('grade-schemes.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Scheme Name</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <hr>
            <h5>Grade Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Letter Grade</th>
                            <th>GPA</th>
                            <th>Min Mark</th>
                            <th>Max Mark</th>
                            <th>Sort</th>
                            <th width="80">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($rows = old('items', [['letter_grade'=>'A+','gpa'=>'5.00','min_mark'=>'80','max_mark'=>'100','sort_order'=>1]]))
                        @foreach($rows as $i => $row)
                            <tr>
                                <td><input type="text" name="items[{{ $i }}][letter_grade]" class="form-control" value="{{ $row['letter_grade'] ?? '' }}"></td>
                                <td><input type="number" step="0.01" name="items[{{ $i }}][gpa]" class="form-control" value="{{ $row['gpa'] ?? '' }}"></td>
                                <td><input type="number" step="0.01" name="items[{{ $i }}][min_mark]" class="form-control" value="{{ $row['min_mark'] ?? '' }}"></td>
                                <td><input type="number" step="0.01" name="items[{{ $i }}][max_mark]" class="form-control" value="{{ $row['max_mark'] ?? '' }}"></td>
                                <td><input type="number" name="items[{{ $i }}][sort_order]" class="form-control" value="{{ $row['sort_order'] ?? ($i + 1) }}"></td>
                                <td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('items') <div class="text-danger mb-2">{{ $message }}</div> @enderror
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addRow">Add Row</button>

            <div>
                <button type="submit" class="btn btn-success">Create</button>
                <a href="{{ route('grade-schemes.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const table = document.getElementById('itemsTable').querySelector('tbody');
        const addRowBtn = document.getElementById('addRow');

        const buildRow = (index) => `
            <tr>
                <td><input type="text" name="items[${index}][letter_grade]" class="form-control"></td>
                <td><input type="number" step="0.01" name="items[${index}][gpa]" class="form-control"></td>
                <td><input type="number" step="0.01" name="items[${index}][min_mark]" class="form-control"></td>
                <td><input type="number" step="0.01" name="items[${index}][max_mark]" class="form-control"></td>
                <td><input type="number" name="items[${index}][sort_order]" class="form-control" value="${index + 1}"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row">Remove</button></td>
            </tr>
        `;

        addRowBtn.addEventListener('click', function () {
            const index = table.querySelectorAll('tr').length;
            table.insertAdjacentHTML('beforeend', buildRow(index));
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });
    })();
</script>
@endsection
