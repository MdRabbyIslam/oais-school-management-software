<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'student_id', 'to', 'message', 'response', 'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
