<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingPolicyComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_policy_id',
        'component_name',
        'component_code',
        'total_marks',
        'pass_marks',
        'sort_order',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
    ];

    public function gradingPolicy()
    {
        return $this->belongsTo(GradingPolicy::class);
    }
}

