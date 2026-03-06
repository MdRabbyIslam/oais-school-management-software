<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AdmissionDocument extends Model
{
    use HasFactory;

    protected $fillable = ['admission_application_id','type','path','original_name'];

    // Supported document types (key => human label)
    public const TYPES = [
        'birth_certificate' => 'Birth Certificate',
        'marksheet' => 'Marksheet',
    ];

    public function application()
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }

    public function getUrlAttribute()
    {
        if (! $this->path) return null;
        try {
            return Storage::disk('public_upload')->url($this->path);
        } catch (\Throwable $e) {
            return '/' . ltrim($this->path, '/');
        }
    }

    // Human label for the document type
    public function getLabelAttribute()
    {
        return static::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
