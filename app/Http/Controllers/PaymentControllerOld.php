<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PaymentControllerOld extends Controller
{
    public function create(Invoice $invoice)
    {
        return view('pages.payments.create', [
            'invoice' => $invoice->load('student', 'items'),
            'paymentMethods' => ['cash', 'bank_transfer', 'mobile_money', 'cheque']
        ]);
    }

    public function store(StorePaymentRequest $request, Invoice $invoice)
    {
        $payment = DB::transaction(function () use ($request, $invoice) {
            // Create payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference,
                'notes' => $request->notes,
                'recorded_by' => auth()->id()
            ]);

            // Allocate payment to invoice items (or whole invoice)
            if ($request->allocate_to_items) {
                foreach ($request->allocations as $allocation) {
                    if ($allocation['amount'] > 0) {
                        $payment->allocations()->create([
                            'invoice_id' => $invoice->id,
                            'invoice_item_id' => $allocation['item_id'],
                            'amount' => $allocation['amount']
                        ]);
                    }
                }
            } else {
                $payment->allocations()->create([
                    'invoice_id' => $invoice->id,
                    'amount' => $request->amount
                ]);
            }

            // Update invoice status
            $this->updateInvoiceStatus($invoice);

            return $payment;
        });

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment recorded successfully');
    }

    public function show(Payment $payment)
    {
        return view('pages.payments.show', [
            'payment' => $payment->load('invoice', 'student', 'allocations.invoiceItem')
        ]);
    }

    protected function updateInvoiceStatus($invoice)
    {
        $totalPaid = $invoice->payments()->sum('amount');
        $balance = $invoice->total_amount - $totalPaid;

        if ($balance <= 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partially_paid';
        } else {
            $status = $invoice->status;
        }

        $invoice->update([
            'paid_amount' => $totalPaid,
            'status' => $status
        ]);
    }

    public function download(Payment $payment)
    {
        $payment->load('student.section.schoolClass', 'invoice', 'allocations.invoiceItem');

        $pdf = Pdf::loadView('pages.payments.receipt', compact('payment'))
            ->setPaper('a5', 'portrait');

        return $pdf->download("receipt-{$payment->receipt_number}.pdf");
    }
}
