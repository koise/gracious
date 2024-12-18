<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $fillable = [
        'patient_id',
        'number',
        'subject',
        'message',
    ];
}
