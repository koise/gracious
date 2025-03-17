<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Appointment;

class UserPaymentController extends Controller
{
    public function create()
    {
        return view('admin.payment');
    }
}
