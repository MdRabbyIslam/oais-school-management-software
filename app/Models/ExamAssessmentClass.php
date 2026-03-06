<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAssessmentClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_assessment_id',
        'class_id',
        'is_published',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function examAssessment()
    {
        return $this->belongsTo(ExamAssessment::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function assessmentSubjects()
    {
        return $this->hasMany(ExamAssessmentSubject::class, 'assessment_class_id');
    }

    public function studentResults()
    {
        return $this->hasMany(ExamStudentResult::class, 'assessment_class_id');
    }
}

