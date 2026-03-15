<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id', // Add this field
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

       /**
     * A user belongs to a role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function createdExamAssessments()
    {
        return $this->hasMany(ExamAssessment::class, 'created_by');
    }

    public function publishedExamAssessments()
    {
        return $this->hasMany(ExamAssessment::class, 'published_by');
    }

    public function publishedExamAssessmentClasses()
    {
        return $this->hasMany(ExamAssessmentClass::class, 'published_by');
    }

    public function enteredExamMarks()
    {
        return $this->hasMany(ExamMark::class, 'entered_by');
    }

    public function verifiedExamMarks()
    {
        return $this->hasMany(ExamMark::class, 'verified_by');
    }

    public function createdClassTests()
    {
        return $this->hasMany(ClassTest::class, 'created_by');
    }

    public function publishedClassTests()
    {
        return $this->hasMany(ClassTest::class, 'published_by');
    }

    public function enteredClassTestMarks()
    {
        return $this->hasMany(ClassTestMark::class, 'entered_by');
    }
}
