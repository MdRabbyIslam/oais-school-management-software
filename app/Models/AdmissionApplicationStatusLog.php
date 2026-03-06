<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionApplicationStatusLog extends Model
{
    use HasFactory;

    protected $fillable = ['admission_application_id','from_status','to_status','changed_by_user_id','notes'];

    public function application()
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }
}
