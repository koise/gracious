<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payment';

    protected $fillable = [
        'appointment_id',
        'status',
        'paid',
        'total',
        'qr_id',
        'reference_number',
    ];    

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function qr()
    {
        return $this->belongsTo(Qr::class, 'qr_id');
    }
}
