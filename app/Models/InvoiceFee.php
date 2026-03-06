<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'fee_id', 'amount_due', 'amount_paid'
    ];

    // Define the relationship with Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Define the relationship with Fee
    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }
}
