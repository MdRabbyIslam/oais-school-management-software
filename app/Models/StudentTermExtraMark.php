<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTermExtraMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'term_id',
        'class_id',
        'student_enrollment_id',
        'homework_marks',
        'attendance_marks',
        'remarks',
        'entered_by',
        'entered_at',
    ];

    protected $casts = [
        'homework_marks' => 'decimal:2',
        'attendance_marks' => 'decimal:2',
        'entered_at' => 'datetime',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
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

