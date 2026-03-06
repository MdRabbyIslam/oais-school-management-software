<?php

namespace App\Http\Controllers;

use App\Models\FeeAssignment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Sms\SmsServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
        protected $sms;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
  public function __construct(SmsServiceInterface $sms)
    {
        $this->middleware('auth');
        $this->sms = $sms;
    }


    public function fixData()
    {


        $term_fee_assignments = FeeAssignment::with(['fee','invoiceItems.paymentAllocations'])->whereHas('fee', function($query) {
            $query->where('billing_type', 'term-based');
        })->get();

        $invoices_due_need_to_recalculate = [];

        foreach($term_fee_assignments->groupBy('term_id') as $term_id => $fee_assignments) {
            foreach($fee_assignments as $assignment) {
                $invoice_items = $assignment->invoiceItems;

                // add invoice ids to recalculate
                if ($invoice_items->count() > 0) {
                    foreach($invoice_items as $item) {
                        if ($item->paymentAllocations->count() > 0) {
                            $invoices_due_need_to_recalculate[] = $item->invoice_id;
                        }
                    }
                }

                if ($invoice_items->count() > 1) {
                    // skip first item, delete the rest with payment allocations
                    $firstItem = $invoice_items->first();
                    $itemsToDelete = $invoice_items->slice(1);
                    $itemsToDelete->each(function($item) {
                        $item->paymentAllocations()->delete();
                        $item->delete();
                    });
                }


            }
        }

        $invoices_due_need_to_recalculate = array_unique($invoices_due_need_to_recalculate);

        // dd($invoices_due_need_to_recalculate);

        // recalculate due for invoices that have payment allocations
        if (count($invoices_due_need_to_recalculate) > 0) {
            foreach($invoices_due_need_to_recalculate as $invoice_id) {
                $invoice = Invoice::with(['allocations','items'])->where('id',$invoice_id)->first();


                $total_amount =  $invoice->items()->sum('amount');
                $total_paid = $invoice->allocations()->sum('amount');

                $invoice->update([
                    'total_amount' => $total_amount,
                    'paid_amount' => $total_paid,
                ]);

            }
        }

    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {


        // $this->fixData();

        // 1) Counts
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();

        // 2) Collections
        $today       = today()->toDateString();
        $thisMonth   = today()->month;
        $thisYear    = today()->year;
        $collectToday = Payment::whereDate('payment_date', $today)->sum('amount');
        $collectMonth = Payment::whereYear('payment_date', $thisYear)
                               ->whereMonth('payment_date', $thisMonth)
                               ->sum('amount');

        // 3) Total due = invoiced – paid
        $totalInvoiced = InvoiceItem::sum('amount');
        $totalPaid     = Payment::sum('amount');  // or sum of all payment_allocations
        $totalDue      = max($totalInvoiced - $totalPaid, 0);

        // 4) SMS balance
        $smsBalance    = $this->sms->getBalance();

        // 5) Monthly Collections for chart
        $byMonth = Payment::selectRaw('MONTH(payment_date) as m, SUM(amount) as total')
            ->whereYear('payment_date', $thisYear)
            ->groupBy('m')
            ->pluck('total','m')
            ->toArray();

        $labels = collect(range(1,12))
            ->map(fn($m) => Carbon::create($thisYear, $m, 1)->format('M'))
            ->all();

        $data = collect(range(1,12))
            ->map(fn($m) => $byMonth[$m] ?? 0)
            ->all();

        return view('home', compact(
            'totalStudents','totalTeachers',
            'collectToday','collectMonth','totalDue',
            'smsBalance','labels','data'
        ));
    }
}
