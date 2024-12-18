<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', // Ensure this field exists in the table
        'description', // Optional: add other relevant fields
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }
}
