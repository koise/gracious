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
        'username',
        'number_verified',
        'age',
        'number',
        'street_address',
        'city_id',
        'province_id',
        'status',
        'password',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
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

    public function authorizations()
    {
        return $this->hasMany(Authorization::class, 'patient_id');
    }

    public function id()
    {
        return $this->hasOne(Id::class, 'patient_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

}
