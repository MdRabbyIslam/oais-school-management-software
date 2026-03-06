<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{


    public function monthlyPaymentSheet(Request $request)
    {
        $classes  = SchoolClass::with('sections')->get();
        $sections = Section::select('id','class_id','section_name')->get();


        $viewData = [
            'year' => date('Y'),
            'month' => date('m'),
            'classes' => $classes,
            'sections' => $sections,
        ];

        return view('pages.reports.monthly_payment_sheet', $viewData);
    }
    public function showMonthlyPaymentSheet(Request $request)
    {
        $viewData = $this->buildMonthlyPaymentSheetData($request);

        $classes  = SchoolClass::with('sections')->get();
        $sections = Section::select('id','class_id','section_name')->get();

        $viewData['classes']  = $classes;
        $viewData['sections'] = $sections;

        $oldStudent = null;
        if (old('student_id')) {
            $oldStudent = Student::with('section.schoolClass')
                                  ->find(old('student_id'));
        }
        $viewData['oldStudent'] = $oldStudent;

        return view('pages.reports.monthly_payment_sheet', $viewData);
    }

    /**
     * Print-friendly Monthly Payment Sheet view.
     *
     * Uses the exact same data/logic as monthlyPaymentSheet(), but renders
     * the print layout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function monthlyPaymentSheetPrint(Request $request)
    {
        $viewData = $this->buildMonthlyPaymentSheetData($request);

        $class = SchoolClass::with('sections')->find($request->class_id);
        $section =$request->section_id? Section::find($request->section_id)->section_name:'';

        $viewData['class']  = $class->name;
        $viewData['section'] = $section;

        return view('pages.reports.monthly_payment_sheet_print', $viewData);
    }

    /**
     * Build the data array for the Monthly Payment Sheet.
     *
     * NOTE: This preserves your exact query and bucketing logic:
     * - Dynamic fee columns via Fee::orderBy('fee_name')
     * - Student scoping (class/section/student)
     * - Only invoices with due_date <= monthEnd and status != 'paid'
     * - Sum of all-time partials per invoice_item (COALESCE(SUM(pa.amount), 0))
     * - Bucket per item by month parsed from invoice_items.description;
     *   fallback to invoice due_date if parsing fails.
     * - Cell displays "prev + curr" while totals use numeric sums.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array  Keys: month, year, monthStart, monthEnd, fees, rows, schoolName, logoPath, title
     */
    private function buildMonthlyPaymentSheetData(Request $request): array
    {
        $data = $request->validate([
            'month'      => 'required|integer|min:1|max:12',
            'year'       => 'required|integer|min:2000|max:2100',
            'class_id'   => 'required|nullable|integer',
            'section_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
        ]);

        $month = isset($data['month']) ? (int) $data['month'] : date('m');
        $year  = isset($data['year']) ? (int) $data['year'] : date('Y');

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd   = (clone $monthStart)->endOfMonth()->endOfDay();
        $prevEnd    = (clone $monthStart)->subDay()->endOfDay(); // kept (not used now, but part of your flow)

        // Dynamic fee columns (kept exactly)
        $fees = Fee::orderBy('fee_name')->get(['id','fee_name']);

        // Scope students (kept exactly)
        $studentScope = Student::query();
        if (!empty($data['class_id'])) {
            $studentScope->whereHas('section.schoolClass', fn($q) => $q->where('id', $data['class_id']));
        }
        if (!empty($data['section_id'])) {
            $studentScope->where('section_id', $data['section_id']);
        }
        if (!empty($data['student_id'])) {
            $studentScope->where('id', $data['student_id']);
        }

        $students = $studentScope->get(['id', 'student_id', 'name']);
        $studentIds = $students->pluck('id');


        // Early return when empty (kept exactly)
        if ($studentIds->isEmpty()) {
            return [
                'month'      => $month,
                'year'       => $year,
                'monthStart' => $monthStart,
                'monthEnd'   => $monthEnd,
                'fees'       => $fees,
                'rows'       => [],
                'schoolName' => config('app.school_name', 'Oasis Model School'),
                'logoPath'   => asset('images/school-logo.png'),
                'title'      => "Monthly Payment Sheet – " . $monthStart->format('F') . " / " . $year,
            ];
        }

        // Query with all-time partial payments + description (kept exactly)
        $items = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('fee_assignments', 'fee_assignments.id', '=', 'invoice_items.fee_assignment_id')
            ->join('fees', 'fees.id', '=', 'fee_assignments.fee_id')
            ->leftJoin('payment_allocations as pa', 'pa.invoice_item_id', '=', 'invoice_items.id')
            ->whereIn('invoices.student_id', $studentIds)
            ->whereDate('invoices.due_date', '<=', $monthEnd->toDateString())
            ->groupBy(
                'invoice_items.id',
                'invoice_items.invoice_id',
                'invoice_items.amount',
                'invoice_items.description',
                'invoices.student_id',
                'invoices.due_date',
                'fees.id'
            )
            ->selectRaw('
                invoice_items.id,
                invoice_items.invoice_id,
                invoice_items.amount,
                invoice_items.description,
                invoices.student_id,
                invoices.due_date,
                fees.id as fee_id,
                COALESCE(SUM(pa.amount), 0) AS paid_all_time
            ')
            ->havingRaw('invoice_items.amount - COALESCE(SUM(pa.amount), 0) > 0')
            ->get();

        // Bucketing by parsed period from description (kept exactly)
        $matrix = [];

        $parsePeriod = function (string $desc) {
            // matches: "Jan 2025", "January 2025", "Jul 25", etc.
            if (preg_match('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec|January|February|March|April|June|July|August|September|October|November|December)\s+(\d{2,4})\b/i', $desc, $m)) {
                $monthName = $m[1];
                $yearRaw   = $m[2];
                $year = (int) (strlen($yearRaw) === 2 ? ('20' . $yearRaw) : $yearRaw);
                try {
                    return Carbon::parse("1 {$monthName} {$year}")->startOfMonth();
                } catch (\Throwable $e) {
                    return null;
                }
            }
            return null;
        };

        foreach ($items as $row) {
            $sid  = $row->student_id;
            $fid  = $row->fee_id;

            $unpaid = (float)$row->amount - (float)$row->paid_all_time;
            if ($unpaid <= 0) continue;

            // Decide bucket by description period; fallback to invoice due_date
            $periodDate = $parsePeriod($row->description) ?: Carbon::parse($row->due_date)->startOfDay();

            $prev = 0.0; $curr = 0.0;
            if ($periodDate->lt($monthStart)) {
                $prev = $unpaid;
            } elseif ($periodDate->between($monthStart, $monthEnd)) {
                $curr = $unpaid;
            } else {
                // after selected month → ignore for this sheet
                continue;
            }

            $feeAgg = $matrix[$sid]['fees'][$fid] ?? ['prev' => 0.0, 'curr' => 0.0, 'sum' => 0.0];
            $feeAgg['prev'] += $prev;
            $feeAgg['curr'] += $curr;
            $feeAgg['sum']  += ($prev + $curr);
            $matrix[$sid]['fees'][$fid] = $feeAgg;
        }

        // Normalize rows for the view (kept exactly)
        $rows = [];
        $sl = 1;
        foreach ($students->sortBy('name') as $student) {
            $sid = $student->id;
            $feeColumns = [];
            $feeDisplay = [];
            $total = 0.0;

            foreach ($fees as $fee) {
                $agg = $matrix[$sid]['fees'][$fee->id] ?? ['prev'=>0.0,'curr'=>0.0,'sum'=>0.0];
                $prev = round($agg['prev'], 2);
                $curr = round($agg['curr'], 2);
                $sum  = round($agg['sum'], 2);

                $feeColumns[$fee->id] = $sum;
                $feeDisplay[$fee->id] = number_format($prev,2).' + '.number_format($curr,2);
                $total += $sum;
            }

            $rows[] = [
                'sl'          => $sl++,
                'student'     => "{$student->student_id} - {$student->name}",
                'fees'        => $feeColumns,
                'feesDisplay' => $feeDisplay,
                'total'       => round($total, 2),
            ];
        }

        return [
            'month'      => $month,
            'year'       => $year,
            'monthStart' => $monthStart,
            'monthEnd'   => $monthEnd,
            'fees'       => $fees,
            'rows'       => $rows,
            'schoolName' => config('app.school_name', 'Oasis Model School'),
            'logoPath'   => asset('images/school-logo.png'),
            'title'      => "Monthly Payment Sheet – " . $monthStart->format('F') . " / " . $year,
        ];
    }

    /**
     * Show form to record payments by student.
     */
    public function createByStudent()
    {
        // If we’re re‐showing after a validation error,
        // preload that one “old” student so the <select> has an initial <option>.
        $oldStudent = null;
        if (old('student_id')) {
            $oldStudent = Student::with('section.schoolClass')
                                  ->find(old('student_id'));
        }

        return view('pages.payments.create_by_student', compact('oldStudent'));
    }

    /**
     * AJAX: return all due invoice items for the given student.
     */

    public function dueItems(Student $student)
    {
        $items = InvoiceItem::with(['invoice','paymentAllocations'])
            ->whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
            ->get()
            ->filter(fn($item) =>
                $item->amount > $item->paymentAllocations->sum('amount')
            )
            ->map(fn($item) => [
                'id'             => $item->id,
                'invoice_number' => $item->invoice->invoice_number,
                'description'    => $item->description,
                'due'            => round($item->amount - $item->paymentAllocations->sum('amount'), 2),
            ])
            // ← reset keys to 0,1,2… so JSON is always an array
            ->values();

        return response()->json($items);
    }


    /**
     * Store payment(s) for one student across multiple invoices.
     */


    public function storeByStudent(Request $request)
    {

        // 1) Custom messages for the validator
        $messages = [
            'allocations.required'    => 'Please choose at least one fee item to pay.',
            'allocations.array'       => 'Payment details are invalid. Try again.',
            'allocations.*.numeric'   => 'Each payment amount must be a number.',
            'allocations.*.min'       => 'Payment amounts cannot be negative.',
        ];

        // 2) Validate basic shape
        $data = $request->validate([
            'student_id'            => 'required|exists:students,id',
            'payment_date'          => 'required|date',
            'payment_method'        => 'required|in:cash,card,bank_transfer',
            'transaction_reference' => 'nullable|string',
            'notes'                 => 'nullable|string',
            'allocations'           => 'required|array',
            'allocations.*'         => 'nullable|numeric|min:0',
        ], $messages);

        // 3) Only keep positives
        $allocs = collect($data['allocations'])
            ->filter(fn($amt) => $amt > 0);

        // 4) Must have at least one positive entry
        if ($allocs->isEmpty()) {
            return back()->withErrors('You must enter at least one payment amount.');
        }

         // 5) Ensure each key is a real invoice‐item, else bail with a single message
        foreach ($allocs->keys() as $itemId) {
            if (! InvoiceItem::find($itemId)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'allocations' => 'One or more selected fee items are no longer valid. Please reload the page.',
                    ]);
            }
        }

        // 6)over‐/under‐payment check
        $overUnderErrors = [];
        foreach ($allocs as $itemId => $amt) {
            $item = InvoiceItem::with('paymentAllocations')->find($itemId);
            if (! $item) {
                $overUnderErrors["allocations.{$itemId}"] = "Invalid invoice item.";
                continue;
            }

            // amount already paid on this line
            $paidBefore = $item->paymentAllocations->sum('amount');
            $due        = round($item->amount - $paidBefore, 2);

            if ($amt > $due) {
                $overUnderErrors["allocations.{$itemId}"] =
                    "Cannot pay {$amt}—only {$due} is due for “{$item->description}.”";
            }
        }

        if (! empty($overUnderErrors)) {
            return back()
                ->withInput()
                ->withErrors($overUnderErrors);
        }

        try {
            DB::transaction(function() use ($allocs, $data) {
                // 1) Create one Payment
                $payment = Payment::create([
                    'student_id'            => $data['student_id'],
                    'receipt_number'        => Payment::generateReceiptNumber(),
                    'payment_date'          => $data['payment_date'],
                    'amount'                => $allocs->sum(),
                    'payment_method'        => $data['payment_method'],
                    'transaction_reference' => $data['transaction_reference'] ?? null,
                    'notes'                 => $data['notes'] ?? null,
                    'recorded_by'           => auth()->id(),
                ]);

                // 2) Allocate each invoice item & update invoice status
                $allocs->each(function($amt, $itemId) use ($payment) {
                    $item = InvoiceItem::findOrFail($itemId);

                    PaymentAllocation::create([
                        'payment_id'      => $payment->id,
                        'invoice_id'      => $item->invoice_id,
                        'invoice_item_id' => $itemId,
                        'amount'          => $amt,
                    ]);

                    $invoice = $item->invoice;
                    $paid    = $invoice?->allocations->sum('amount')??0;
                    $invoice->update([
                        'paid_amount' => $paid,
                        'status'      => $paid >= $invoice->total_amount
                                        ? 'paid'
                                        : 'partially_paid',
                    ]);
                });
            });

            return redirect()->route('payments.index')
                            ->with('success', 'Payment recorded with one receipt.');

        } catch (\Throwable $e) {
            // Log full stack for deeper inspection
            Log::error('Error storing student payment', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Send you right back to the form with the error message
            return back()
                ->withInput()
                ->withErrors(['exception' => 'Failed to record payment: ' . $e->getMessage()]);
        }
    }
















 /**
     * Show form to edit a student‐centric payment
     */
    public function editByStudent(Payment $payment)
    {
        // reload allocations & invoiceItems
        $payment->load('allocations.invoice','allocations.invoiceItem');

        // same student dropdown you used in create
        $students = Student::orderBy('name')
                           ->get(['id','student_id','name']);

        return view('pages.payments.edit_by_student', compact('payment','students'));
    }

    /**
     * Update an existing student‐centric payment
     */
    public function updateByStudent(Request $request, Payment $payment)
    {
        // 1) Friendly messages for allocations validation
        $messages = [
            'allocations.required'  => 'Please enter a payment amount for at least one fee item.',
            'allocations.array'     => 'Payment details are invalid. Please try again.',
            'allocations.*.numeric' => 'Each payment amount must be a valid number.',
            'allocations.*.min'     => 'Payment amounts cannot be negative.',
        ];

        // 2) Validate the basic shape
        $data = $request->validate([
            'student_id'            => 'required|exists:students,id',
            'payment_date'          => 'required|date',
            'payment_method'        => 'required|in:cash,card,bank_transfer',
            'transaction_reference' => 'nullable|string',
            'notes'                 => 'nullable|string',
            'allocations'           => 'required|array',
            'allocations.*'         => 'nullable|numeric|min:0',
        ], $messages);

        // 3) Keep only positive amounts
        $allocs = collect($data['allocations'])->filter(fn($amt) => $amt > 0);

        // 4) Must have at least one positive entry
        if ($allocs->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['allocations'=>'Enter at least one payment amount.']);
        }

        // 5) Guard against invalid invoice‐item IDs
        foreach ($allocs->keys() as $itemId) {
            if (! \App\Models\InvoiceItem::where('id', $itemId)->exists()) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'allocations' => 'One or more selected fee items are no longer valid. Please reload the page.',
                    ]);
            }
        }

        // Validate you’re not overpaying any line
        $overErrors = [];
        foreach ($allocs as $itemId => $amt) {
            $item = InvoiceItem::with('paymentAllocations')->find($itemId);
            if (!$item) {
                $overErrors["allocations.{$itemId}"] = 'Invalid invoice item.';
                continue;
            }
            // previous payments (excluding this one)
            $prev = $item->paymentAllocations
                         ->where('payment_id','!=',$payment->id)
                         ->sum('amount');
            $due  = round($item->amount - $prev,2);
            if ($amt > $due) {
                $overErrors["allocations.{$itemId}"] =
                   "Cannot pay {$amt}—only {$due} due for “{$item->description}.”";
            }
        }
        if ($overErrors) {
            return back()->withInput()->withErrors($overErrors);
        }

        // All good—rebuild the payment & allocations
        DB::transaction(function() use ($payment, $data, $allocs) {
            // 1) update Payment record
            $payment->update([
                'student_id'            => $data['student_id'],
                'payment_date'          => $data['payment_date'],
                'payment_method'        => $data['payment_method'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes'                 => $data['notes'] ?? null,
                'amount'                => $allocs->sum(),
            ]);

            // 2) remove old allocations
            $payment->allocations()->delete();

            // 3) re-create allocations & update each invoice
            foreach ($allocs as $itemId => $amt) {
                $item = InvoiceItem::findOrFail($itemId);
                PaymentAllocation::create([
                    'payment_id'      => $payment->id,
                    'invoice_id'      => $item->invoice_id,
                    'invoice_item_id' => $itemId,
                    'amount'          => $amt,
                ]);

                // recalc invoice status
                $inv = $item->invoice;
                $paid = $inv->allocations->sum('amount');
                $inv->update([
                    'paid_amount' => $paid,
                    'status'      => $paid >= $inv->total_amount
                                    ? 'paid' : 'partially_paid',
                ]);
            }
        });

        return redirect()->route('payments.show',$payment)
                         ->with('success','Payment updated successfully.');
    }


















/**
     * Display a listing of payments, with filters for receipt, student, method, and date range.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['student', 'allocations.invoice']);

        // Search by receipt number or student name/ID
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('receipt_number', 'like', "%{$term}%")
                  ->orWhereHas('student', function ($sq) use ($term) {
                      $sq->where('name', 'like', "%{$term}%")
                         ->orWhere('student_id', 'like', "%{$term}%");
                  });
            });
        }

        // Filter by payment method
        if ($request->filled('method')) {
            $query->where('payment_method', $request->input('method'));
        }

        // Date range filters
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->input('to_date'));
        }

        // Paginate
        $payments = $query->latest()
                          ->paginate(15)
                          ->withQueryString();


        return view('pages.payments.index', compact('payments'));
    }


    /**
     * Show form to record a new payment for an invoice.
     */
    public function create(Invoice $invoice)
    {
        // Ensure there is remaining balance
        $balance = $invoice->total_amount - $invoice->payments->sum('amount');
        if ($balance <= 0) {
            return redirect()->route('invoices.show', $invoice)
                ->with('info', 'Invoice is already fully paid.');
        }

        $receiptNumber = Payment::generateReceiptNumber();
        return view('pages.payments.create', compact('invoice', 'receiptNumber'));
    }

    /**
     * Store a new payment and allocate amounts to invoice items.
     */
    public function store(Request $request, Invoice $invoice)
    {
        // Calculate remaining balance before this payment
        $balance = $invoice->total_amount - $invoice->payments->sum('amount');

        $validated = $request->validate([
            'payment_date'       => 'required|date',
            'payment_method'     => 'required|in:cash,card,bank_transfer',
            'receipt_number'     => 'required|string',
            'allocations'        => 'required|array',
            'allocations.*'      => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
        ]);

        // Compute total allocated
        $totalAllocated = array_sum($validated['allocations']);
        if ($totalAllocated <= 0 || $totalAllocated > $balance) {
            return back()->withErrors([
                'allocations' => 'Allocated amount must be positive and not exceed the remaining balance.'
            ]);
        }

        // Create Payment record
        $payment = Payment::create([
            'invoice_id'            => $invoice->id,
            'student_id'            => $invoice->student_id,
            'receipt_number'        => $validated['receipt_number'],
            'payment_date'          => $validated['payment_date'],
            'amount'                => $totalAllocated,
            'payment_method'        => $validated['payment_method'],
            'transaction_reference' => $request->input('transaction_reference'),
            'notes'                 => $validated['notes'],
            'recorded_by'           => Auth::id(),
        ]);

        // Allocate to invoice items
        foreach ($validated['allocations'] as $itemId => $allocAmount) {
            if ($allocAmount > 0) {
                PaymentAllocation::create([
                    'payment_id'      => $payment->id,
                    'invoice_id'      => $invoice->id,
                    'invoice_item_id' => $itemId,
                    'amount'          => $allocAmount,
                ]);
            }
        }

        // Recalculate total paid and update invoice status
        $newPaidTotal = $invoice->payments()->sum('amount');

        // Update status AND paid_amount
        $invoice->update([
            'status'      => $newPaidTotal >= $invoice->total_amount
                            ? 'paid'
                            : 'partially_paid',
            'paid_amount' => $newPaidTotal,
        ]);
        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Payment recorded successfully.');
    }

     /**
     * Display the specified payment receipt.
     */
    public function show(Payment $payment)
    {
        // Eager-load student and allocation details
        $payment->load('student', 'allocations.invoice', 'allocations.invoiceItem');

        return view('pages.payments.show', compact('payment'));
    }


      /**
     * Show the form to edit an existing payment.
     */
    public function edit(Payment $payment)
    {
        $invoice = $payment->invoice;
        $balanceBefore = $invoice->total_amount
                        - ($invoice->payments->sum('amount') - $payment->amount);

        return view('pages.payments.edit', compact('payment', 'invoice', 'balanceBefore'));
    }

    /**
     * Update an existing payment & its allocations.
     */
    public function update(Request $request, Payment $payment)
    {
        $invoice = $payment->invoice;

        // Calculate the “available” balance if we remove this payment first
        $balanceBefore = $invoice->total_amount - ($invoice->payments->sum('amount') - $payment->amount);

        $validated = $request->validate([
            'payment_date'       => 'required|date',
            'payment_method'     => 'required|in:cash,card,bank_transfer',
            'receipt_number'     => 'required|string',
            'allocations'        => 'required|array',
            'allocations.*'      => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
        ]);

        $newTotalAllocated = array_sum($validated['allocations']);
        if ($newTotalAllocated <= 0 || $newTotalAllocated > $balanceBefore) {
            return back()->withErrors([
                'allocations' => 'Allocated amount must be positive and not exceed the remaining balance.'
            ]);
        }

        DB::transaction(function() use ($payment, $validated, $newTotalAllocated, $invoice) {
            // 1) Reset allocations
            $payment->allocations()->delete();

            // 2) Update the payment record
            $payment->update([
                'payment_date'          => $validated['payment_date'],
                'payment_method'        => $validated['payment_method'],
                'receipt_number'        => $validated['receipt_number'],
                'amount'                => $newTotalAllocated,
                'notes'                 => $validated['notes'],
            ]);

            // 3) Re-create allocations
            foreach ($validated['allocations'] as $itemId => $allocAmount) {
                if ($allocAmount > 0) {
                    PaymentAllocation::create([
                        'payment_id'      => $payment->id,
                        'invoice_id'      => $invoice->id,
                        'invoice_item_id' => $itemId,
                        'amount'          => $allocAmount,
                    ]);
                }
            }

            // 4) Recompute invoice status and paid_amount
            $paidTotal = $invoice->payments()->sum('amount');

            $invoice->update([
                'status'      => $paidTotal >= $invoice->total_amount
                                ? 'paid'
                                : 'partially_paid',
                'paid_amount' => $paidTotal,
            ]);

        });

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Download a printable PDF receipt for a specific payment.
     */
    public function download(Payment $payment)
    {
        // eager-load relations
        $payment->load(['student.section.schoolClass', 'allocations.invoiceItem']);

        $pdf = Pdf::loadView('pages.payments.pdf', compact('payment'));

        return $pdf->download("receipt-{$payment->receipt_number}.pdf");
    }


    public function print(Payment $payment)
    {
        // eager-load what you need
        $payment->load(['student.section.schoolClass', 'allocations.invoiceItem', 'recorder']);

        $pdf = Pdf::loadView('pages.payments.pdf', compact('payment'))
                ->setPaper('a4', 'landscape');

        // stream() will send Content-Disposition:inline rather than attachment
        return $pdf->stream("receipt-{$payment->receipt_number}.pdf");
    }



    public function printHtml(Payment $payment)
    {
        // 1) Eager-load as before
        $payment->load([
            'student.section.schoolClass',
            'allocations.invoiceItem.invoice',
            'allocations.invoiceItem',
            'recorder',
        ]);
        $payment->student->load([
            'invoices.items.paymentAllocations.payment'
        ]);

        $student = $payment->student;
        $cutoff  = $payment->created_at;

        // 2) Build and run the query
        $rows = InvoiceItem::query()
            // only the minimal columns
            ->select('invoice_items.id', 'invoice_items.amount')
            ->whereHas('invoice', fn($q) =>
                $q->where('student_id', $student->id)
            )
            ->withSum([
                'paymentAllocations as prev_paid' => fn($q) =>
                    $q->where('payment_id', '<>', $payment->id)
                    ->whereHas('payment', fn($q2) =>
                        $q2->where('created_at', '<', $cutoff)
                    )
            ], 'amount')
            ->withSum([
                'paymentAllocations as paid_today' => fn($q) =>
                    $q->where('payment_id', $payment->id)
            ], 'amount')
            // group by exactly the selected, non-aggregated columns
            ->groupBy('invoice_items.id', 'invoice_items.amount')
            // keep only rows you’ll show
            ->havingRaw('
                COALESCE(paid_today, 0) > 0
                OR (invoice_items.amount - COALESCE(prev_paid, 0)) > 0
            ')
            ->get();            // actually fetch the rows

        // 3) Count them in PHP
        $rowsCount = $rows->count();

        return view('pages.payments.printable', [
            'payment'   => $payment,
            'student'   => $student,
            'rowsCount' => $rowsCount,
        ]);
    }

     /**
     * Print receipt in Bengali.
     */
    public function printHTMLBn(Payment $payment)
    {
        // 1) Eager-load as before
        $payment->load([
            'student.section.schoolClass',
            'allocations.invoiceItem.invoice',
            'allocations.invoiceItem',
            'recorder',
        ]);
        $payment->student->load([
            'invoices.items.paymentAllocations.payment'
        ]);

        $student = $payment->student;
        $cutoff  = $payment->created_at;

        // 2) Build and run the query
        $rows = InvoiceItem::query()
            // only the minimal columns
            ->select('invoice_items.id', 'invoice_items.amount')
            ->whereHas('invoice', fn($q) =>
                $q->where('student_id', $student->id)
            )
            ->withSum([
                'paymentAllocations as prev_paid' => fn($q) =>
                    $q->where('payment_id', '<>', $payment->id)
                    ->whereHas('payment', fn($q2) =>
                        $q2->where('created_at', '<', $cutoff)
                    )
            ], 'amount')
            ->withSum([
                'paymentAllocations as paid_today' => fn($q) =>
                    $q->where('payment_id', $payment->id)
            ], 'amount')
            // group by exactly the selected, non-aggregated columns
            ->groupBy('invoice_items.id', 'invoice_items.amount')
            // keep only rows you’ll show
            ->havingRaw('
                COALESCE(paid_today, 0) > 0
                OR (invoice_items.amount - COALESCE(prev_paid, 0)) > 0
            ')
            ->get();            // actually fetch the rows

        // 3) Count them in PHP
        $rowsCount = $rows->count();



        return view('pages.payments.printable_bn', compact('payment','student','rowsCount'));
    }


}
