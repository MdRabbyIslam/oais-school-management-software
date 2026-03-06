@extends('layouts.app')
@section('plugins.SortableJS', true)

@section('subtitle', 'Fees')
@section('content_header_title', 'Fees Management')

@section('css')
    <style>
        /* Subtle visual feedback while dragging */
        .bg-light { background-color: #f8f9fa !important; }
        #fees-tbody tr { transition: background-color .15s ease; }
        #fees-tbody td:first-child { width: 48px; text-align: center; }
    </style>
@stop

@section('content_body')
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between">
            <h3 class="card-title">All Fees</h3>
            <a href="{{ route('fees.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Fee
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width:48px;"></th>
                    <th>SL no</th>
                    <th>Name</th>
                    <th>Group</th>
                    <th>Type</th>
                    <th>Mandatory</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="fees-tbody">
                @foreach($fees as $fee)
                    <tr data-id="{{ $fee->id }}">
                        <td class="text-muted" style="cursor:grab;" title="Drag to reorder">☰</td>
                        <td>{{ $fee->sl_no??'' }}</td>
                        <td>{{ $fee->fee_name }}</td>
                        <td>{{ $fee->feeGroup->name }}</td>
                        <td>{{ ucfirst($fee->billing_type) }}</td>
                        <td>
                            <span class="badge bg-{{ $fee->is_mandatory ? 'success' : 'warning' }}">
                                {{ $fee->is_mandatory ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('fees.edit', $fee->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex gap-2">
        <button id="save-order" class="btn btn-primary btn-sm">Save order</button>
        <span id="save-status" class="text-muted ml-2"></span>
    </div>

    @if($fees->hasPages())
    <div class="card-footer clearfix">
        {{ $fees->links() }}
    </div>
    @endif
</div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('fees-tbody');
    if (!tbody || typeof Sortable === 'undefined') return;

    const sortable = new Sortable(tbody, {
        handle: 'td:first-child',
        animation: 150,
        ghostClass: 'bg-light',
        onEnd() { saveOrder(getOrderedIds()); },
    });

    function getOrderedIds() {
        return Array.from(tbody.querySelectorAll('tr')).map(tr => parseInt(tr.dataset.id));
    }

    async function saveOrder(ids) {
        const status = document.getElementById('save-status');
        status.textContent = 'Saving…';

        const resp = await fetch(@json(route('fees.reorder')), {
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
            console.error(await resp.text());
            return;
        }

        status.textContent = 'Saved!';
        Array.from(tbody.querySelectorAll('tr')).forEach((tr, i) => {
            tr.children[1].textContent = i + 1; // update SL no
        });
        setTimeout(() => status.textContent = '', 2000);
    }

    document.getElementById('save-order').addEventListener('click', () => saveOrder(getOrderedIds()));
});
</script>
@endsection

