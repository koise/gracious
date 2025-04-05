<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Id extends Model
{
    use HasFactory;

    protected $table = 'id';

    protected $fillable = [
        'patient_id',
        'file_path',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
