<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeAssignment extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'student_enrollment_id',
        'fee_id',
        'term_id',
        'amount',
        'due_date',
        'start_date',
        'end_date',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'due_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relations
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }



    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class)->with(['section.schoolClass']);
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class)->with(['feeGroup']);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    // Helpers
    public function isRecurring()
    {
        return $this->fee->billing_type === 'recurring';
    }

    public function isInvoicedForPeriod($period = null)
    {
        // If no period specified, use current due_date's period
        $period = $period ?? $this->due_date->format('Y-m');

        // Check meta field first for explicit period tracking
        if (isset($this->meta['invoiced_periods'][$period])) {
            return true;
        }

        // Fallback check in invoice_items (for backward compatibility)
        return $this->invoiceItems()
            ->whereHas('invoice', function($q) use ($period) {
                $q->whereYear('invoice_date', substr($period, 0, 4))
                ->whereMonth('invoice_date', substr($period, 5, 2));
            })
            ->exists();
    }

    // protected static function booted()
    // {
    //     static::updating(function($assignment) {
    //         $assignment->history = array_merge(
    //             (array)$assignment->history,
    //             [now()->format('Y-m-d') => $assignment->getOriginal()]
    //         );
    //     });
    // }

}
