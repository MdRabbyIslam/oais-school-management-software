<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassFeeAmount extends Model
{
    use HasFactory;
    protected $fillable = [
        'class_id',
        'fee_id',
        'amount'
    ];

    // Defining the relationship: A ClassFeeAmount belongs to a Fee
    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }
}
