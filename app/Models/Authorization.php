<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Authorization extends Model
{
    protected $fillable = [
        'patient_id',
        'file_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
