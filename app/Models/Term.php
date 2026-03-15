<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $table = 'terms';
    protected $fillable =[
        'academic_year_id',
        'name',
        'order',
        'start_date',
        'end_date',
        'fee_due_date'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'fee_due_date' => 'date'
    ];

    /**
     * Get the academic year that owns the term.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the upcoming term.
     */
    public static function upcoming()
    {
        return self::where('start_date', '>', now())
            ->orderBy('start_date');
    }
    /**
     * Get the current term.
     */
    public static function current()
    {
        return self::where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
    /**
     * Get the previous term.
     */
    public static function previous()
    {
        return self::where('end_date', '<', now())
            ->orderBy('end_date', 'desc');
    }

    /**
     * Get exam assessments for the term.
     */
    public function examAssessments()
    {
        return $this->hasMany(ExamAssessment::class);
    }

    /**
     * Get class tests for the term.
     */
    public function classTests()
    {
        return $this->hasMany(ClassTest::class);
    }

}
