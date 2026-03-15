<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTestMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_test_id',
        'student_enrollment_id',
        'marks_obtained',
        'is_absent',
        'remarks',
        'entered_by',
        'entered_at',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'is_absent' => 'boolean',
        'entered_at' => 'datetime',
    ];

    public function classTest()
    {
        return $this->belongsTo(ClassTest::class);
    }

    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}

