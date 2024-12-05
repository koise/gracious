<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserForgotController extends Controller
{
    public function create()
    {
        return view('user.forgot-password');
    }
}
