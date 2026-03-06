<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Artisan;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // fetch dropdown data
        $classes  = SchoolClass::all();
        $sections = Section::with('schoolClass')->get();
        $students = Student::select('id','student_id','name')->get(); // for the searchable student select
        // get all distinct statuses
        // $statuses  = Invoice::select('status')->distinct()->pluck('status');
            // 2) build a statuses list: “unpaid” plus whatever’s in the DB
        $dbStatuses = Invoice::select('status')->distinct()->pluck('status')->toArray();
        // ensure “unpaid” is the first option
        $statuses   = array_merge(['unpaid'], $dbStatuses);

        // build invoice query
        $query = Invoice::with('student.section.schoolClass');

        // status filter
        if ($request->filled('status')) {
            if ($request->status === 'unpaid') {
                // issued OR partially_paid (anything except paid)
                $query->where('status', '<>', 'paid');
            } else {
                $query->where('status', $request->status);
            }
        }


        // filter by class
        if ($request->filled('class_id')) {
            $query->whereHas('student.section.schoolClass', fn($q) =>
                $q->where('id', $request->class_id)
            );
        }

        // filter by section
        if ($request->filled('section_id')) {
            $query->whereHas('student', fn($q) =>
                $q->where('section_id', $request->section_id)
            );
        }

        // filter by student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        // free-text search: invoice number or student name
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function($q) use ($term) {
                $q->where('invoice_number','like',"%{$term}%")
                ->orWhereHas('student', fn($q2) =>
                    $q2->where('name','like',"%{$term}%")
                        ->orWhere('student_id','like',"%{$term}%")
                );
            });
        }

        // paginate with filters
        $invoices = $query->latest()
                        ->paginate(20)
                        ->withQueryString();

        return view('pages.invoices.index', compact(
            'invoices','classes','sections','students','statuses'
        ));
    }


    public function generate(Student $student)
    {
        $uninvoicedFees = $student->feeAssignments()
            ->whereDoesntHave('invoiceItem')
            ->get();

        if ($uninvoicedFees->isEmpty()) {
            return back()->with('error', 'No fees to invoice!');
        }

        Artisan::call('invoices:generate', [
            '--student' => $student->id
        ]);

        return back()->with('success', 'Invoice generated!');
    }


    public function show(Invoice $invoice)
    {
        return view('pages.invoices.show', [
            'invoice' => $invoice->load(['student.section.schoolClass', 'items'])
        ]);
    }

    public function download(Invoice $invoice)
    {
        $invoice->load(['student.section.schoolClass', 'items']);

        $pdf = Pdf::loadView('pages.invoices.pdf', compact('invoice'));

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
