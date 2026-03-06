<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;
    protected $fillable  =[
        'fee_group_id',
        'fee_name',
        'billing_type',
        'frequency',
        'is_mandatory',
        'sl_no',

    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'sl_no' => 'integer',
    ];
    /**
     * A fee belongs to a fee group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feeGroup()
    {
        return $this->belongsTo(FeeGroup::class);
    }


    public function classFeeAmounts()
    {
        return $this->hasMany(ClassFeeAmount::class);
    }


}
