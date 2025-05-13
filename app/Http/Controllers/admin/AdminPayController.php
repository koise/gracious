<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use App\Models\Payment;
use App\Models\Qr;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\Id; 
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class AdminPayController extends Controller
{   
    public function markPaymentCompleted(Request $request)
    {
        try {
            // Log the incoming request data for debugging
            Log::info('Incoming request data for markPaymentCompleted:', $request->all());

            // Validate the incoming request
            $validated = $request->validate([
                'payment_id' => 'required|exists:payment,id', // Ensure the payment ID exists
            ]);

            // Retrieve the payment by ID
            $payment = Payment::findOrFail($request->payment_id);

            // Mark the payment as completed
            $payment->status = 'completed'; // Set the status to 'completed'

            // Save the updated payment
            $payment->save();
            
            // Update the associated appointment status if it exists
            if ($payment->appointment_id) {
                $appointment = Appointment::find($payment->appointment_id);
                if ($appointment) {
                    // Update appointment status to 'Accepted' (valid enum value in appointments table)
                    $appointment->status = 'Accepted';
                    $appointment->save();
                    
                    Log::info('Appointment status updated to Accepted', [
                        'appointment_id' => $appointment->id,
                        'previous_status' => $appointment->getOriginal('status'),
                        'new_status' => 'Accepted'
                    ]);
                }
            }

            // Send SMS notification after payment completion
            $sent = $this->sendPaymentCompletedSms($payment);

            if ($sent) {
                // Return a success response
                return response()->json([
                    'message' => 'Payment marked as completed and SMS sent.',
                    'payment' => $payment,
                ], 200);
            } else {
                // If SMS failed, return an error response
                return response()->json([
                    'message' => 'Payment marked as completed, but failed to send SMS.',
                    'payment' => $payment,
                ], 500);
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error marking payment as completed: " . $e->getMessage());

            // Handle errors and return an error response
            return response()->json([
                'error' => 'Unable to process the payment completion.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    private function sendPaymentCompletedSms(Payment $payment)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';  
        $url = 'https://api.semaphore.co/api/v4/priority';  
    
        $client = new Client();
    
        try {
            // Retrieve the associated appointment and user
            $appointment = $payment->appointment; // Adjust as needed for the actual relationship
            $user = $appointment ? $appointment->user : null;
    
            if ($user) {
                $formattedAmount = number_format($payment->total, 2);
                $transactionId = $payment->id;  // The payment ID will serve as the transaction ID
                
                $response = $client->post($url, [
                    'form_params' => [
                        'apikey' => $apiKey,
                        'number' => $user->number, // Get the user's number
                        'message' => "Dear {$user->first_name}, your balance of PHP {$formattedAmount} for Transaction ID: {$transactionId} has been completed for your appointment. Thank you! - Gracious Clinic",
                    ]
                ]);
    
                if ($response->getStatusCode() === 200) {
                    return true;
                } else {
                    // Log the error response
                    Log::error("SMS failed: " . $response->getBody());
                    return false;
                }
            } else {
                // Log or handle case where user is not found
                Log::error('User not found for payment ID ' . $payment->id);
                return false;
            }
        } catch (RequestException $e) {
            // Log the exception message
            Log::error("Error sending SMS: " . $e->getMessage());
            return false;
        }
    }

    public function receivePayment(Request $request)
    {
        try {
            // Log the incoming request data for debugging
            Log::info('Incoming request data for receivePayment:', $request->all());
    
            // Validate the request data
            $validated = $request->validate([
                'payment_id' => 'required|exists:payment,id',  // Ensure payment_id exists
                'total' => 'required|numeric|min:0',  
            ]);
    
            // Retrieve the payment by ID
            $payment = Payment::findOrFail($request->payment_id);
    
            // Update the total payment value and status
            $payment->total = $request->total;
            $payment->status = 'paid';  // Update status to 'paid'
    
            // Save the updated payment
            $payment->save();

            // Find the associated appointment but don't change its status
            // Keep it as "Pending" for admin review
            $appointment = Appointment::where('id', $payment->appointment_id)->first();
            if ($appointment) {
                Log::info('Associated appointment found, keeping status as is:', [
                    'appointment_id' => $appointment->id,
                    'current_status' => $appointment->status
                ]);
            }
    
            // Send SMS notification after payment received
            $sent = $this->sendPaymentUpdateSms($payment);
    
            if ($sent) {
                // Return success response
                return response()->json([
                    'message' => 'Payment received successfully and SMS sent.',
                    'payment' => $payment,
                ], 200);
            } else {
                // If SMS failed, return error response
                return response()->json([
                    'message' => 'Payment received, but failed to send SMS.',
                    'payment' => $payment,
                ], 500);
            }
    
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error receiving payment: " . $e->getMessage());
    
            // Return error response
            return response()->json([
                'error' => 'Unable to process payment reception.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendBookingReminderSms(Payment $payment)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';  
        $url = 'https://api.semaphore.co/api/v4/priority';  

        $client = new Client();

        try {
            // Retrieve the associated appointment and user
            $appointment = $payment->appointment; // Adjust as needed for the actual relationship
            $user = $appointment ? $appointment->user : null;

            if ($user) {
                // Format the total amount for the message
                $formattedTotal = number_format($payment->total, 2);

                // Construct the message
                $message = "Dear {$user->first_name} {$user->last_name},\n\n" .
                        "We hope you're doing well. This is a reminder for your upcoming appointment at Gracious Clinic. " .
                        "To proceed with your booking for the {$appointment->service_name} service, please settle your balance of PHP {$formattedTotal}.\n\n" .
                        "Kindly make your payment to confirm your appointment. If you need assistance, don't hesitate to contact us.\n\n" .
                        "Thank you for choosing Gracious Clinic. We look forward to serving you soon!";

                // Send the SMS using Semaphore API
                $response = $client->post($url, [
                    'form_params' => [
                        'apikey' => $apiKey,
                        'number' => $user->number, // Get the user's number
                        'message' => $message,
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    return true;
                } else {
                    // Log the error response
                    Log::error("SMS failed: " . $response->getBody());
                    return false;
                }
            } else {
                // Log or handle case where user is not found
                Log::error('User not found for payment ID ' . $payment->id);
                return false;
            }
        } catch (RequestException $e) {
            // Log the exception message
            Log::error("Error sending SMS: " . $e->getMessage());
            return false;
        }
    }


    private function sendPaymentUpdateSms(Payment $payment)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';  
        $url = 'https://api.semaphore.co/api/v4/priority';  
    
        $client = new Client();
    
        try {
            // Retrieve the associated appointment and user
            $appointment = $payment->appointment; // Adjust as needed for the actual relationship
            $user = $appointment ? $appointment->user : null;
    
            if ($user) {
                $formattedTotal = number_format($payment->total, 2);
                $formattedPaid = number_format($payment->paid, 2);  // Assuming 'paid' field exists in the Payment model
                $unsettledBalance = $payment->total - $payment->paid;
                $formattedUnsettledBalance = number_format($unsettledBalance, 2);
    
                $response = $client->post($url, [
                    'form_params' => [
                        'apikey' => $apiKey,
                        'number' => $user->number, // Get the user's number
                        'message' => "Dear {$user->first_name} {$user->last_name},\n\nYour payment of PHP {$formattedPaid} has been received for your appointment. Your unsettled balance for Transaction Number:{$payment -> id} is PHP {$formattedUnsettledBalance}.\nThank you for choosing Gracious Clinic!",
                    ]
                ]);
    
                if ($response->getStatusCode() === 200) {
                    return true;
                } else {
                    // Log the error response
                    Log::error("SMS failed: " . $response->getBody());
                    return false;
                }
            } else {
                // Log or handle case where user is not found
                Log::error('User not found for payment ID ' . $payment->id);
                return false;
            }
        } catch (RequestException $e) {
            // Log the exception message
            Log::error("Error sending SMS: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendTotalPayment(Request $request)
    {
        try {
            // Log the incoming request data for debugging
            Log::info('Incoming request data:', $request->all());
    
            // Validate the incoming request
            $validated = $request->validate([
                'payment_id' => 'required|exists:payment,id', // Ensure the payment ID exists
                'total' => 'required|numeric|min:0', // Ensure the total is a positive number
            ]);
    
            // Retrieve the payment by ID
            $payment = Payment::findOrFail($request->payment_id); // Find payment by the ID
    
            // Update the total payment value
            $payment->total = $request->total; // Set the new total value
    
            // Save the updated payment
            $payment->save();
    
            // Send SMS notification (use the correct method here)
            $sent = $this->sendBookingReminderSms($payment);
    
            if ($sent) {
                // Return a success response
                return response()->json([
                    'message' => 'Total payment updated successfully and SMS sent.',
                    'payment' => $payment,
                ], 200);
            } else {
                // If SMS failed, return an error response
                return response()->json([
                    'message' => 'Total payment updated, but failed to send SMS.',
                    'payment' => $payment,
                ], 500);
            }
    
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error updating total payment: " . $e->getMessage());
    
            // Handle errors and return an error response
            return response()->json([
                'error' => 'Unable to update total payment.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    private function getQrImageUrl($imagePath)
    {
        // Check if file exists in public directory
        if ($imagePath && File::exists(public_path($imagePath))) {
            return '/' . $imagePath;
        }
        
        // Check if file exists in storage directory
        if ($imagePath && Storage::exists('public/' . $imagePath)) {
            return '/storage/' . $imagePath;
        }
        
        // If image doesn't exist in either location, check if it's a full URL
        if ($imagePath && (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://'))) {
            return $imagePath;
        }
        
        // Return default image path if none found
        return '/default-image.jpg';
    }

    public function getPaymentDetails($paymentId)
    {
        try {
            // Validate that paymentId is numeric
            if (!is_numeric($paymentId)) {
                return response()->json([
                    'message' => 'Invalid payment ID. Must be a numeric value.',
                    'error' => 'Invalid payment ID format.'
                ], 400);
            }

            // Query to join necessary tables and get all data
            $data = DB::table('payment')
                ->join('appointments', 'appointments.id', '=', 'payment.appointment_id')
                ->join('qr', 'qr.id', '=', 'payment.qr_id')
                ->join('users', 'users.id', '=', 'appointments.patient_id')
                ->leftJoin('id', 'id.patient_id', '=', 'users.id')
                ->where('payment.id', $paymentId)
                ->select(
                    'payment.*', 
                    'appointments.*', 
                    'qr.*', 
                    'users.*', 
                    'id.*', 
                    'payment.status',
                    'qr.image_path as qr_image_path' // Explicitly select QR image path
                )
                ->first();
    
            // Check if data exists
            if (!$data) {
                return response()->json(['message' => 'Payment not found.'], 404);
            }
    
            // Format the image path to ensure it's correctly displayed
            if ($data->image_path) {
                $data->qr_image_url = $this->getQrImageUrl($data->image_path);
            }
    
            // Log successful data retrieval with image path info
            Log::info('Payment details retrieved successfully', [
                'payment_id' => $paymentId,
                'qr_image_path' => $data->image_path,
                'qr_image_url' => $data->qr_image_url ?? null
            ]);
    
            // Return the data with payment status included
            return response()->json($data);
        } catch (\Exception $e) {
            // Log error if there's an exception
            Log::error("Error retrieving payment data for PaymentID {$paymentId}: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching payment data.'], 500);
        }
    }
    
    public function populatePayments(Request $request)
    {
        try {
            $query = Payment::with(['appointment.user', 'qr']);
            
            // Status filter
            if ($request->has('status') && $request->get('status') !== 'All') {
                $query->where('status', $request->get('status'));
            }
    
            // Search filter
            if ($request->has('search') && !empty($request->get('search'))) {
                $search = $request->get('search');
    
                $query->where(function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhereHas('qr', function ($qrQuery) use ($search) {
                          $qrQuery->where('gcash_name', 'like', "%{$search}%")
                                  ->orWhere('number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('appointment.user', function ($userQuery) use ($search) {
                          $userQuery->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }
            
            // Date range filter
            if ($request->has('date_from') && !empty($request->get('date_from'))) {
                $query->whereDate('created_at', '>=', $request->get('date_from'));
            }
            
            if ($request->has('date_to') && !empty($request->get('date_to'))) {
                $query->whereDate('created_at', '<=', $request->get('date_to'));
            }
            
            // Amount range filter
            if ($request->has('min_amount') && !empty($request->get('min_amount'))) {
                $query->where('total', '>=', $request->get('min_amount'));
            }
            
            if ($request->has('max_amount') && !empty($request->get('max_amount'))) {
                $query->where('total', '<=', $request->get('max_amount'));
            }
    
            // Sort by status and prioritize 'pending' first, then by status in ascending order
            $payments = $query->orderByRaw("FIELD(status, 'pending') DESC")
                             ->orderBy('status', 'asc')
                             ->orderBy('created_at', 'desc') // Most recent payments first
                             ->paginate(10);
            
            // Log the filters being used
            Log::info('Payment filters applied', [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'min_amount' => $request->get('min_amount'),
                'max_amount' => $request->get('max_amount'),
                'results_count' => $payments->total()
            ]);
    
            return response()->json($payments);
    
        } catch (\Exception $e) {
            Log::error('Error in populatePayments:', [
                'message' => $e->getMessage(),
                'filters' => $request->all()
            ]);
            return response()->json(['error' => 'Unable to fetch payments.'], 500);
        }
    }
    
    /**
     * Handle payment cancellation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelPayment(Request $request)
    {
        try {
            // Log the incoming request data for debugging
            Log::info('Incoming request data for cancelPayment:', $request->all());
    
            // Validate the request data
            $validated = $request->validate([
                'payment_id' => 'required|exists:payment,id',  // Ensure payment_id exists
            ]);
    
            // Retrieve the payment by ID
            $payment = Payment::findOrFail($request->payment_id);
    
            // Update the payment status to cancelled
            $payment->status = 'cancelled';
    
            // Save the updated payment
            $payment->save();
    
            // Find the associated appointment but don't change its status
            // Leave it for admin to decide what to do with the appointment
            $appointment = Appointment::where('id', $payment->appointment_id)->first();
            if ($appointment) {
                Log::info('Associated appointment found for cancelled payment:', [
                    'appointment_id' => $appointment->id,
                    'current_status' => $appointment->status
                ]);
            }
    
            return response()->json([
                'message' => 'Payment cancelled successfully.',
                'payment' => $payment,
            ], 200);
    
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Error cancelling payment: " . $e->getMessage());
    
            // Return error response
            return response()->json([
                'error' => 'Unable to cancel payment.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
