<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
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
// app/Http/Controllers/User/UserPaymentController.php

    public function getQRDetailsByTransaction($transactionId)
    {
        try {
            // 1) Active QR codes
            $qrDetails = Qr::where('status', 'active')->get();

            // 2) The payment record (throws 404 if not found)
            $payment = Payment::with('appointment')->findOrFail($transactionId);
            $appointment = $payment->appointment;
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'qr'          => $qrDetails,       // collection of Qr
                    'appointment' => $appointment,     // single Appointment model (or null)
                    'payment'     => $payment,         // single Payment model
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaction not found',
            ], 404);

        } catch (\Exception $e) {
            \Log::error('getQRDetailsByTransaction Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch details: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }public function updatePayment(Request $request, $id)
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
