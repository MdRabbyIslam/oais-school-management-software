<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'roll_number',
        'enrollment_date',
        'completion_date',
        'status',
        'meta'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'meta' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }


    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function promotionsFrom()
    {
        return $this->hasMany(StudentPromotion::class, 'from_enrollment_id');
    }

    public function promotionsTo()
    {
        return $this->hasMany(StudentPromotion::class, 'to_enrollment_id');
    }

    public function examMarks()
    {
        return $this->hasMany(ExamMark::class);
    }

    public function examStudentResults()
    {
        return $this->hasMany(ExamStudentResult::class);
    }

    public function classTestMarks()
    {
        return $this->hasMany(ClassTestMark::class);
    }

    public function termExtraMarks()
    {
        return $this->hasMany(StudentTermExtraMark::class);
    }
}
