<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Qr;

class UserPaymentController extends Controller
{
    public function indexPayment()
    {
        return view('user.payment');
    }

    public function fetch()
    {
        $userId = Session::get('user_id');
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    public function latestAppointment()
    {
        $user = auth()->user(); // Ensure user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $latestAppointment = Appointment::where('patient_id', $user->id)
            ->where('status', '!=', 'Cancelled') 
            ->latest('appointment_date') 
            ->with('payment') 
            ->first();
    
        return response()->json($latestAppointment);
    }

    public function joinedDetailsAppointment()
{
    $userId = Session::get('user_id');

    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $appointments = Payment::join('appointments', 'payment.appointment_id', '=', 'appointments.id')
        ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id') 
        ->where('appointments.patient_id', $userId) 
        ->select(
            'payment.id as transaction_id',
            'appointments.procedures',
            'payment.total as balance',
            'payment.status',
            'appointments.appointment_date as date',
            'qr.gcash_name as payment_recipient'
        )
        ->orderByDesc('payment.created_at')
        ->get();

    return response()->json($appointments);
}


}
