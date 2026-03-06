<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class InvoiceItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'fee_assignment_id',
        'amount',
        'description'
    ];
    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }

    public function feeAssignment() {
        return $this->belongsTo(FeeAssignment::class);
    }

        /**
     * All the allocations (partial payments) applied to this line item.
     */
    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class, 'invoice_item_id');
    }

}
