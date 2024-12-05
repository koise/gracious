<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'age',
        'number_verified',
        'number',
        'street_address',
        'province',
        'city',
        'country',
        'username',
        'password',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'number_verified' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Define relationship to appointments.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }
}
