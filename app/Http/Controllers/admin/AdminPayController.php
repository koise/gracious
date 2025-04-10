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

            // Send SMS notification after payment completion (you can add your SMS method here)
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
    
    
    public function getPaymentDetails($paymentId)
    {
        try {
            // Query to join necessary tables and get all data
            $data = DB::table('payment')
                ->join('appointments', 'appointments.id', '=', 'payment.appointment_id')
                ->join('qr', 'qr.id', '=', 'payment.qr_id')
                ->join('users', 'users.id', '=', 'appointments.patient_id')
                ->leftJoin('id', 'id.patient_id', '=', 'users.id')
                ->where('payment.id', $paymentId)
                ->select('payment.*', 'appointments.*', 'qr.*', 'users.*', 'id.*', 'payment.status') // Added 'payment.status'
                ->first();
    
            // Check if data exists
            if (!$data) {
                return response()->json(['message' => 'Payment not found.'], 404);
            }
    
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
            
            if ($request->has('status') && $request->get('status') !== 'All') {
                $query->where('status', $request->get('status'));
            }
    
            if ($request->has('search')) {
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
    
            // Sort by status and prioritize 'pending' first, then by status in ascending order
            $payments = $query->orderByRaw("FIELD(status, 'pending') DESC")
                             ->orderBy('status', 'asc')
                             ->paginate(10);
    
            return response()->json($payments);
    
        } catch (\Exception $e) {
            Log::error('Error in populatePayments:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to fetch payments.'], 500);
        }
    }
    
}
