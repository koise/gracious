<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_date',
        'preference',
        'appointment_time',
        'status',
        'procedures',
        'remarks',
    ];

    /**
     * Define the relationship with the User model.
     */

     public function patient()
     {
         return $this->belongsTo(User::class, 'patient_id');
     }

    public function user()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
