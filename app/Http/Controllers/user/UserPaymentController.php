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
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        // Validate that appointmentId is numeric
        if (!is_numeric($appointmentId)) {
            return response()->json([
                'message' => 'Invalid appointment ID. Must be a numeric value.',
                'error' => 'Invalid appointment ID format.'
            ], 400);
        }

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

    /**
     * Get detailed payment information with proper error handling
     * 
     * @param int|string $id Payment or Appointment ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentDetailsWithJoins($id)
    {
        try {
            // Validate ID is numeric
            if (!is_numeric($id)) {
                return response()->json([
                    'message' => 'Invalid ID. Must be a numeric value.',
                    'error' => 'Invalid ID format'
                ], 400);
            }

            // First try to find it as a payment ID
            $paymentRecord = Payment::find($id);
            
            if ($paymentRecord) {
                // If found as payment, use that record's appointment_id
                $appointmentId = $paymentRecord->appointment_id;
            } else {
                // Otherwise treat the ID as an appointment ID directly
                $appointmentId = $id;
            }
            
            // Fetch appointment with related payment and QR information
            $appointment = Appointment::with(['payments' => function($query) {
                    $query->with('qr');
                }])
                ->where('id', $appointmentId)
                ->first();
            
            if (!$appointment) {
                return response()->json([
                    'message' => 'No appointment found',
                    'error' => 'Record not found'
                ], 404);
            }
            
            // Get the latest payment for this appointment
            $payment = $appointment->payments->sortByDesc('created_at')->first();
            
            // Get QR details if available
            $qr = $payment && $payment->qr ? $payment->qr : null;
            
            // Prepare QR image path if available
            $qrImagePath = null;
            if ($qr && $qr->image_path) {
                if (!str_starts_with($qr->image_path, '/') && 
                    !str_starts_with($qr->image_path, 'http://') && 
                    !str_starts_with($qr->image_path, 'https://')) {
                    $qrImagePath = '/' . $qr->image_path;
                } else {
                    $qrImagePath = $qr->image_path;
                }
            }
            
            // Create response data
            $responseData = [
                'appointment' => [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $appointment->patient_id,
                    'appointment_date' => $appointment->appointment_date,
                    'preference' => $appointment->preference,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $payment ? $payment->status : $appointment->status,
                    'appointment_status' => $appointment->status,
                    'procedures' => $appointment->procedures,
                    'remarks' => $appointment->remarks,
                    'payment_id' => $payment ? $payment->id : null,
                    'paid' => $payment ? $payment->paid : 0,
                    'reference_number' => $payment ? $payment->reference_number : null,
                    'total' => $payment ? $payment->total : 0,
                    'qr_id' => $qr ? $qr->id : null,
                    'qr_name' => $qr ? $qr->name : null,
                    'qr_gcash_name' => $qr ? $qr->gcash_name : null,
                    'qr_image_path' => $qrImagePath
                ]
            ];
            
            Log::info('Payment details fetched successfully', [
                'appointment_id' => $appointment->id,
                'payment_id' => $payment ? $payment->id : null,
                'qr_id' => $qr ? $qr->id : null
            ]);
            
            return response()->json($responseData);
            
        } catch (\Exception $e) {
            Log::error('Error fetching payment details with joins', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while fetching payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function latestAppointmentByUser()
    {
        // Get user ID from session instead of hardcoded value
        $userId = Session::get('user_id');
        
        if (!$userId) {
            Log::error('No user ID found in session for latest appointment fetch');
            return response()->json(['error' => 'Unauthorized - No user ID found in session'], 401);
        }
        
        Log::info('Fetching latest appointment for user ID: ' . $userId);
        
        // Get the latest appointment for the user
        $latestAppointment = Appointment::where('patient_id', $userId)
            ->latest('appointment_date') // You can also sort by created_at if preferred
            ->with([
                'payments' => function($query) {
                    // Sort payments by most recent first
                    $query->latest('created_at');
                },
                'payments.qr' // eager load payment's qr details
            ])
            ->first();

        if (!$latestAppointment) {
            Log::info('No appointment found for user ID: ' . $userId);
            return response()->json(['message' => 'No appointment found'], 404);
        }
        
        // Log the appointment and payments
        Log::info('Latest appointment found', [
            'appointment_id' => $latestAppointment->id,
            'status' => $latestAppointment->status,
            'appointment_date' => $latestAppointment->appointment_date,
            'payment_count' => $latestAppointment->payments->count()
        ]);

        return response()->json([
            'appointment' => $latestAppointment,
            'payments' => $latestAppointment->payments, // Payments are sorted by latest created_at first
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
    
    /**
     * Get a QR code by ID
     * 
     * @param int $id QR code ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQRById($id)
    {
        try {
            // Find the QR code
            $qr = Qr::findOrFail($id);
            
            // Ensure image path is correctly formatted
            if ($qr->image_path && !str_starts_with($qr->image_path, '/') && 
                !str_starts_with($qr->image_path, 'http://') && 
                !str_starts_with($qr->image_path, 'https://')) {
                $qr->image_path = '/' . $qr->image_path;
            }
            
            // Log success
            Log::info('QR code retrieved successfully', [
                'qr_id' => $id,
                'qr_name' => $qr->name,
                'image_path' => $qr->image_path
            ]);
            
            // Return in the same format as AdminQrController::getQRCodeDetails
            return response()->json([
                'success' => true,
                'qrCode' => $qr
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('QR code not found', ['id' => $id]);
            return response()->json(['success' => false, 'message' => 'QR code not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving QR code', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to retrieve QR code'], 500);
        }
    }
    
    /**
     * Get all active QR codes
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllActiveQRCodes()
    {
        try {
            // Get all active QR codes
            $qrCodes = Qr::where('status', 'active')->get();
            
            // Format image paths
            foreach ($qrCodes as $qr) {
                if ($qr->image_path && !str_starts_with($qr->image_path, '/') && 
                    !str_starts_with($qr->image_path, 'http://') && 
                    !str_starts_with($qr->image_path, 'https://')) {
                    $qr->image_path = '/' . $qr->image_path;
                }
            }
            
            // Log success
            Log::info('Active QR codes retrieved successfully', [
                'count' => $qrCodes->count()
            ]);
            
            // Return the QR codes
            return response()->json($qrCodes);
            
        } catch (\Exception $e) {
            Log::error('Error retrieving active QR codes', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to retrieve QR codes'], 500);
        }
    }
    
    /**
     * Get payment history with all joined tables
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistoryWithJoins()
    {
        $userId = Session::get('user_id');
        
        if (!$userId) {
            Log::error('No user ID found in session for payment history fetch');
            return response()->json(['error' => 'Unauthorized - No user ID found in session'], 401);
        }
        
        Log::info('Fetching payment history with joins for user ID: ' . $userId);
        
        try {
            // Use query builder to join all necessary tables
            $paymentHistory = DB::table('payment')
                ->join('appointments', 'payment.appointment_id', '=', 'appointments.id')
                ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id')
                ->where('appointments.patient_id', $userId)
                ->whereIn('appointments.status', ['Cancelled', 'Completed']) // Only include Cancelled and Completed
                ->select(
                    'payment.id as transaction_id',
                    'payment.appointment_id',
                    'appointments.procedures',
                    'payment.total',
                    'payment.paid',
                    'payment.status',
                    'payment.reference_number',
                    'payment.receipt_path',
                    'appointments.appointment_date',
                    'appointments.appointment_time',
                    'appointments.status as appointment_status', // Include appointment status
                    'qr.id as qr_id',
                    'qr.name as qr_name',
                    'qr.gcash_name',
                    'qr.number as gcash_number',
                    'qr.image_path as qr_image_path'
                )
                ->orderByDesc('payment.created_at')
                ->get();
                
            // Format image paths
            foreach ($paymentHistory as &$payment) {
                if ($payment->qr_image_path && !str_starts_with($payment->qr_image_path, '/') && 
                    !str_starts_with($payment->qr_image_path, 'http://') && 
                    !str_starts_with($payment->qr_image_path, 'https://')) {
                    $payment->qr_image_path = '/' . $payment->qr_image_path;
                    $payment->qr_image_url = $payment->qr_image_path; // Add URL format for convenience
                }
                
                if ($payment->receipt_path && !str_starts_with($payment->receipt_path, '/') && 
                    !str_starts_with($payment->receipt_path, 'http://') && 
                    !str_starts_with($payment->receipt_path, 'https://')) {
                    $payment->receipt_path = '/' . $payment->receipt_path;
                    $payment->receipt_url = $payment->receipt_path; // Add URL format for convenience
                }
            }
            
            Log::info('Payment history retrieved successfully', [
                'count' => count($paymentHistory)
            ]);
            
            return response()->json($paymentHistory);
            
        } catch (\Exception $e) {
            Log::error('Error fetching payment history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch payment history: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updatePayment(Request $request, $id)
    {
        try {
            // Log incoming request data
            Log::info('Payment update request received', [
                'payment_id' => $id,
                'request_data' => $request->except(['receipt_image']),
                'has_receipt_image' => $request->hasFile('receipt_image'),
                'client_ip' => $request->ip()
            ]);
            
            $validated = $request->validate([
                'transaction_id'    => 'required|exists:payment,id',
                'status'            => 'required|string',
                'paid'              => 'required|numeric|min:0',
                'total'             => 'required|numeric|min:0',
                'qr_id'             => 'required|exists:qr,id',
                'reference_number'  => 'required|string|max:255',
                'receipt_image'     => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB image
            ]);

            // Get existing payment
            $payment = Payment::where('id', $id)->firstOrFail();
            Log::info('Existing payment found', ['payment' => $payment->toArray()]);
            
            // Update payment details
            $payment->status = $validated['status'];
            $payment->paid = $validated['paid'];
            $payment->qr_id = $validated['qr_id'];
            $payment->reference_number = $validated['reference_number'];
            
            // Upload receipt image if provided
            if ($request->hasFile('receipt_image')) {
                $image = $request->file('receipt_image');
                $filename = 'receipt_' . $id . '_' . time() . '.' . $image->getClientOriginalExtension();
                Log::info('Processing receipt image', [
                    'original_name' => $image->getClientOriginalName(),
                    'size' => $image->getSize(),
                    'mime_type' => $image->getMimeType(),
                    'new_filename' => $filename
                ]);
                
                $path = $image->storeAs('receipts', $filename, 'public');
                $payment->receipt_path = 'storage/' . $path;
                Log::info('Receipt image stored', ['path' => $payment->receipt_path]);
            }
            
            $payment->save();
            Log::info('Payment updated in database');

            // Get updated payment with appointment and QR details
            $updatedPayment = Payment::with(['appointment', 'qr'])
                ->where('id', $id)
                ->first();

            $response = [
                'success' => true,
                'message' => 'Payment updated successfully.',
                'data' => $updatedPayment
            ];
            
            Log::info('Payment update complete', [
                'id' => $id, 
                'status' => $validated['status'],
                'reference_number' => $validated['reference_number'],
                'has_receipt' => $request->hasFile('receipt_image'),
                'response' => $response
            ]);

            return response()->json($response);

        } catch (ValidationException $e) {
            Log::error('Payment validation failed:', [
                'payment_id' => $id,
                'errors' => $e->errors(),
                'request_data' => $request->except(['receipt_image'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);            

        } catch (ModelNotFoundException $e) {
            Log::error('Payment record not found:', [
                'id' => $id, 
                'error' => $e->getMessage(),
                'request_data' => $request->except(['receipt_image'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Payment update failed:', [
                'id' => $id,
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['receipt_image'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Complete a payment and mark the appointment as completed
     * 
     * @param Request $request
     * @param int $id Payment ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function completePayment(Request $request, $id)
    {
        try {
            Log::info('Payment completion request received', [
                'payment_id' => $id,
                'client_ip' => $request->ip()
            ]);
            
            // Find the payment record
            $payment = Payment::with('appointment')->where('id', $id)->firstOrFail();
            
            // Update payment status
            $payment->status = 'Completed';
            $payment->save();
            
            // Update the associated appointment status if it exists
            if ($payment->appointment) {
                $appointment = $payment->appointment;
                $appointment->status = 'Completed';
                $appointment->save();
                
                Log::info('Appointment marked as completed', [
                    'appointment_id' => $appointment->id
                ]);
            }
            
            Log::info('Payment marked as completed', [
                'payment_id' => $payment->id,
                'appointment_id' => $payment->appointment_id
            ]);
            
            // Get the updated payment with relationships
            $updatedPayment = Payment::with(['appointment', 'qr'])
                ->where('id', $id)
                ->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment and appointment marked as completed successfully.',
                'data' => $updatedPayment
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::error('Payment not found for completion:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found.'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error completing payment:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Submit a new payment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitPayment(Request $request)
    {
        try {
            // Log incoming request data
            Log::info('Payment submission request received', [
                'request_data' => $request->except(['receipt']),
                'has_receipt' => $request->hasFile('receipt'),
                'client_ip' => $request->ip()
            ]);
            
            $validated = $request->validate([
                'transaction_id'    => 'required',
                'paid_amount'       => 'required|numeric|min:1',
                'qr_id'             => 'required|exists:qr,id',
                'reference_number'  => 'required|string|max:255',
                'status'            => 'nullable|string',
                'receipt'           => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB image
            ]);

            // Get user ID from session
            $userId = Session::get('user_id');
            if (!$userId) {
                Log::error('No user ID found in session when attempting payment');
                return response()->json([
                    'success' => false,
                    'message' => 'User authentication required to submit payment.'
                ], 401);
            }
            
            // Check if the appointment is the latest one for the user
            $latestAppointment = Appointment::where('patient_id', $userId)
                ->latest('appointment_date')
                ->first();
                
            if (!$latestAppointment || $latestAppointment->id != $validated['transaction_id']) {
                Log::warning('Attempt to submit payment for non-latest appointment', [
                    'user_id' => $userId,
                    'requested_appointment_id' => $validated['transaction_id'],
                    'latest_appointment_id' => $latestAppointment ? $latestAppointment->id : null
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Only the latest appointment can be paid. Please contact support if you need assistance.'
                ], 422);
            }

            // Always create a new payment record
            $payment = new Payment();
            $payment->appointment_id = $validated['transaction_id'];
            $payment->total = $validated['paid_amount'];
            
            Log::info('New payment record created', [
                'appointment_id' => $payment->appointment_id
            ]);
            
            // Set payment details
            $payment->status = $validated['status'] ?? 'paid'; // Default to 'paid' status
            $payment->paid = $validated['paid_amount'];
            $payment->qr_id = $validated['qr_id'];
            $payment->reference_number = $validated['reference_number'];
            
            // Upload receipt image if provided
            if ($request->hasFile('receipt')) {
                $image = $request->file('receipt');
                $filename = 'receipt_' . time() . '.' . $image->getClientOriginalExtension();
                Log::info('Processing receipt image', [
                    'original_name' => $image->getClientOriginalName(),
                    'size' => $image->getSize(),
                    'mime_type' => $image->getMimeType(),
                    'new_filename' => $filename
                ]);
                
                $path = $image->storeAs('receipts', $filename, 'public');
                $payment->receipt_path = 'storage/' . $path;
                Log::info('Receipt image stored', ['path' => $payment->receipt_path]);
            }
            
            $payment->save();
            Log::info('Payment record saved', [
                'payment_id' => $payment->id,
                'total' => $payment->total
            ]);
            
            // Also update the appointment status if it exists
            if ($payment->appointment) {
                $appointment = $payment->appointment;
                // Keep appointment status as 'Pending' instead of changing to 'Accepted'
                // $appointment->status = 'Accepted'; 
                $appointment->save();
                Log::info('Payment recorded but appointment status remains Pending', [
                    'appointment_id' => $appointment->id,
                    'status' => $appointment->status
                ]);
            }

            // Get updated payment with appointment and QR details
            $updatedPayment = Payment::with(['appointment', 'qr'])
                ->where('id', $payment->id)
                ->first();

            $response = [
                'success' => true,
                'message' => 'Payment submitted successfully.',
                'data' => $updatedPayment
            ];
            
            Log::info('Payment submission complete', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'total' => $payment->total,
                'reference_number' => $payment->reference_number
            ]);

            return response()->json($response);

        } catch (ValidationException $e) {
            Log::error('Payment validation failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['receipt'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);            

        } catch (\Exception $e) {
            Log::error('Payment submission failed:', [
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['receipt'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit payment: ' . $e->getMessage(),
            ], 500);
        }
    }
    
}
