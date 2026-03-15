<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'term_id',
        'class_id',
        'subject_id',
        'name',
        'test_date',
        'total_marks',
        'pass_marks',
        'status',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'test_date' => 'date',
        'total_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
        'published_at' => 'datetime',
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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function marks()
    {
        return $this->hasMany(ClassTestMark::class);
    }
}

