<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qr extends Model
{
    use HasFactory;

    protected $table = 'qr';

    protected $fillable = [
        'name',
        'image_path',
        'number',
        'gcash_name',
        'status',
    ];
}
