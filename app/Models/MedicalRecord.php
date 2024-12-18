<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'file_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function procedure()
    {
        return $this->belongsTo(Service::class, 'procedure');
    }
}
