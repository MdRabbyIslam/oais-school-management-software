<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        // 'invoice_id',
        'student_id',
        'receipt_number',
        'payment_date',
        'amount',
        'payment_method',
        'transaction_reference',
        'notes',
        'recorded_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];

    // // Relationships
    // public function invoice()
    // {
    //     return $this->belongsTo(Invoice::class);
    // }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    // Helpers
    public static function generateReceiptNumber()
    {
        $lastPayment = static::latest()->first();
        $nextId = $lastPayment ? $lastPayment->id + 1 : 1;

        return 'RCPT-' . now()->format('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
