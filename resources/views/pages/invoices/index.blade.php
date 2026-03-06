@extends('layouts.app')
@section('plugins.Select2', true)

@section('content_header_title', 'Invoices')
@section('content_header_subtitle', 'Manage Student Invoices')

@section('content_body')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Invoice List</h3>
                <form method="GET" class="form-inline">
                    {{-- Class Filter --}}
                    <select name="class_id" id="filter-class" class="form-control form-control-sm mr-2">
                        <option value="">All Classes</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Section Filter --}}
                    <select name="section_id" id="filter-section" class="form-control form-control-sm mr-2">
                        <option value="">All Sections</option>
                        @foreach ($sections as $s)
                            <option value="{{ $s->id }}" {{ request('section_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->schoolClass->name }} – {{ $s->section_name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Student Filter (searchable) --}}
                    <select name="student_id" id="filter-student" class="select2 form-control form-control-sm mx-2">
                        <option value="">All Students</option>
                        @foreach ($students as $stu)
                            <option value="{{ $stu->id }}" {{ request('student_id') == $stu->id ? 'selected' : '' }}>
                                {{ $stu->student_id }} – {{ $stu->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="form-control form-control-sm mx-2">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Free-text search --}}
                    <input type="text" name="search" class="form-control form-control-sm mr-2" style="width:150px"
                        placeholder="Invoice # or Name…" value="{{ request('search') }}">

                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-secondary ml-2">
                        <i class="fas fa-history"></i> Reset
                    </a>
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Student</th>
                        <th class="text-right">Amount</th>\
                        <th class="text-right">Paid</th>
                        <th class="text-right">Due</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>
                                {{ $invoice->student->student_id }} –
                                {{ $invoice->student->name }}<br>
                                <small class="text-muted">
                                    {{ $invoice->student->section->schoolClass->name }} /
                                    {{ $invoice->student->section->section_name }}
                                </small>
                            </td>
                            <td class="text-right">{{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->paid_amount, 2) }}</td>
                           <td class="text-right">
                               {{ number_format(max($invoice->total_amount - $invoice->paid_amount, 0), 2) }}
                           </td>
                            <td>
                                <span
                                    class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'issued' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                            <td class="text-center">
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-pdf"></i>
                                </a>

                                {{-- Show “Make Payment” unless already paid --}}
                                @unless ($invoice->status === 'paid')
                                    <a href="{{ route('invoices.payments.create', $invoice) }}" class="btn btn-sm btn-success"
                                        title="Record Payment">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </a>
                                @endunless

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer clearfix">
            {{ $invoices->links() }}
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Initialize Select2 for student dropdown
        $('#filter-student').select2({
            placeholder: 'All Students',
            allowClear: true,
            width: '200px'
        });
    </script>
@endsection
