<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'invoice_date', 'invoice_number','due_date', 'total_amount', 'paid_amount','status'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date'
    ];

    // Relationships
    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function items() {
        return $this->hasMany(InvoiceItem::class);
    }

     /**
     * Direct allocations from this invoice via PaymentAllocation.
     */
    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
    // Helpers
    public function isOverdue() {
        return $this->due_date->isPast() && !$this->isPaid();
    }

    // relation with payment
    public function payments() {
        return $this->hasMany(Payment::class);
    }
    public function isPaid() {
        return $this->paid_amount >= $this->total_amount;
    }
}
