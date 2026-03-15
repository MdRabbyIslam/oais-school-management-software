<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'subject_id', 'class_id');
    }

    public function assignments()
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    public function gradingPolicies()
    {
        return $this->hasMany(GradingPolicy::class);
    }

    public function examAssessmentSubjects()
    {
        return $this->hasMany(ExamAssessmentSubject::class);
    }

    public function classTests()
    {
        return $this->hasMany(ClassTest::class);
    }
}
