<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{

    protected $fillable = [
        'patient_id',
        'appointment_date',
        'procedure',
        'amount',
        'paid',
        'balance',
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
