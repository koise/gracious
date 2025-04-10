<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Qr;

class UserPaymentController extends Controller
{

    public function getAllPaymentsExceptLatestAppointment()
{
    Log::info('Fetching payments excluding latest appointment.');
    try {
        // Step 1: Retrieve the user ID from the session
        $userId = Session::get('user_id');
        Log::info('User ID from session: ' . $userId);

        if (!$userId) {
            Log::warning('Unauthorized - No user ID found in session');
            return response()->json(['error' => 'Unauthorized - No user ID found in session'], 401);
        }

        // Step 2: Get the latest appointment for the user from the session
        $latestAppointment = Appointment::where('patient_id', $userId)
            ->latest('appointment_date') 
            ->first();

        if (!$latestAppointment) {
            Log::warning('No latest appointment found for user ID ' . $userId);
            return response()->json(['message' => 'No appointments found for the user'], 404);
        }

        // Step 3: Fetch all appointments for the user excluding the latest one
        $appointments = Appointment::where('patient_id', $userId)
            ->where('id', '!=', $latestAppointment->id) // Exclude the latest appointment
            ->with('payments') // Eager load the payments for each appointment
            ->get();

        // Step 4: Check if there are no other appointments
        if ($appointments->isEmpty()) {
            Log::warning('No other appointments found for user ID ' . $userId);
            // Instead of returning 404, return an empty array or a 200 response
            return response()->json(['message' => 'No other appointments found for the user', 'payments' => []], 200);
        }

        // Step 5: Extract payments from the appointments
        $payments = $appointments->flatMap(function($appointment) {
            return $appointment->payments;
        });

        Log::info('Payments fetched: ' . $payments->count());

        return response()->json($payments);
    } catch (\Exception $e) {
        \Log::error('Error fetching payments except latest appointment: ' . $e->getMessage());
        return response()->json(['error' => 'Something went wrong, please try again later.'], 500);
    }
}



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

    public function getPaymentDetailsById($appointmentId)
    {
        $appointment = DB::table('appointments')
            ->leftJoin('payment', 'appointments.id', '=', 'payment.appointment_id')
            ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id')
            ->select(
                'appointments.id as appointment_id',
                'appointments.patient_id',
                'appointments.appointment_date',
                'appointments.preference',
                'appointments.appointment_time',
                'appointments.status',
                'appointments.procedures',
                'appointments.remarks',
                'payment.id as payment_id',
                'payment.paid',
                'payment.reference_number',
                'payment.total',
                'payment.qr_id',
                'qr.id as qr_id',
                'qr.name as qr_name',
                'qr.gcash_name as qr_gcash_name',
                'qr.image_path as qr_image_path'
            )
            ->where('appointments.id', $appointmentId)
            ->first();
    
        if (!$appointment) {
            return response()->json(['message' => 'No appointment found for the given ID'], 404);
        }
    
        return response()->json([
            'appointment' => $appointment
        ]);
    }    

    public function latestAppointmentByUser()
    {
        //$userId = Session::get('user_id');
        $userId = 154;
        // Get the latest appointment for the user
        $latestAppointment = Appointment::where('patient_id', $userId)
            ->latest('appointment_date') // You can also sort by created_at if preferred
            ->with([
                'payments.qr', // eager load payment and its qr
            ])
            ->first();
    
        if (!$latestAppointment) {
            return response()->json(['message' => 'No appointment found'], 404);
        }
    
        return response()->json([
            'appointment' => $latestAppointment,
            'payments' => $latestAppointment->payments, // May contain multiple payments
        ]);
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
            Log::error('Unauthorized access - No user ID found in session.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        try {
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
    
            // Log the details of the appointments received
            Log::info('Appointments received: ' . $appointments->toJson());
    
            return response()->json($appointments);
        } catch (\Exception $e) {
            // Log the exception error message
            Log::error('Error fetching joined details appointment: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong, please try again later.'], 500);
        }
    }

    public function getAppointmentDetails($transactionId)
    {
        $appointment = Payment::join('appointments', 'payment.appointment_id', '=', 'appointments.id')
            ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id')
            ->where('payment.id', $transactionId)
            ->select(
                'payment.id as transaction_id',
                'payment.status as payment_status',
                'payment.paid',
                'payment.total',
                'appointments.procedures',
                'appointments.appointment_date as date',
                'qr.gcash_name as payment_recipient',
                'appointments.service_name'
            )
            ->first();
    
        return response()->json($appointment);
    }

    public function getQRDetailsPaymentDetailsByAppointment($appointmentId)
    {
        try {
            // 1) Get active QR codes
            $qrDetails = Qr::where('status', 'active')->get();
    
            // 2) Find the payment by appointment_id
            $payment = Payment::with('appointment')
                ->where('appointment_id', $appointmentId)
                ->latest() // in case of multiple payments per appointment, get the latest
                ->firstOrFail();
    
            // 3) Get the appointment from the relationship
            $appointment = $payment->appointment;
    
            return response()->json([
                'status' => 'success',
                'data' => [
                    'qr'          => $qrDetails,
                    'appointment' => $appointment,
                    'payment'     => $payment,
                ],
            ], 200);
    
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payment record not found for this appointment',
            ], 404);
    
        } catch (\Exception $e) {
            \Log::error('getQRDetailsPaymentDetailsByAppointment Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
    
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch details: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }
    

    
    
    public function updatePayment(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'transaction_id'    => 'required|exists:payment,id',
                'status'            => 'required|string',
                'paid'              => 'required|numeric',
                'total'             => 'required|numeric',
                'qr_id'             => 'required|exists:qr,id',
                'reference_number'  => 'nullable|string|max:255',
            ]);
    
            // Find payment by transaction_id
            $payment = Payment::where('id', $id)->firstOrFail();
            $payment->update($validated);
    
            Log::info('Payment updated successfully', ['data' => $validated]);
    
            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully.',
                'data' => $payment
            ]);
    
        } catch (ValidationException $e) {
            Log::error('Payment validation failed:', $e->errors());
    
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);            
    
        } catch (\Exception $e) {
            Log::error('Payment update failed:', ['error' => $e->getMessage()]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment.',
            ], 500);
        }
    }
    
}
