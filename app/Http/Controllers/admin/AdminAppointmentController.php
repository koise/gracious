<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Qr;
use App\Models\Payment;
use App\Models\Id;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Facades\Log;

class AdminAppointmentController extends Controller
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
            $response = $client->post($url, [
                'form_params' => [
                    'apikey' => $apiKey,
                    'number' => $user->number, // Get the user's number
                    'message' => "Dear {$user->first_name}, your payment of PHP {$formattedAmount} has been completed for your appointment. Thank you! - Gracious Clinic",
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

    public function getPatientIdDetails($appointmentId)
    {
        // Step 1: Find the appointment
        $appointment = Appointment::find($appointmentId);
    
        if (!$appointment) {
            Log::error("Appointment not found", ['appointment_id' => $appointmentId]);
    
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.'
            ], 404);
        }
    
        // Step 2: Get patient_id from appointment
        $patientId = $appointment->patient_id;
    
        // Step 3: Find patient ID photo
        $id = Id::with('patient')->where('patient_id', $patientId)->first();
    
        if (!$id) {
    
            return response()->json([
                'success' => false,
                'message' => 'No uploaded ID for this patient yet.'
            ], 404);
        }
    
        if (!$id->patient) {
            Log::error("Related patient record not found for ID entry", [
                'id_entry' => $id->id,
                'expected_patient_id' => $patientId
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found.'
            ], 404);
        }
    
        $fullName = $id->patient->first_name . ' ' . $id->patient->last_name;
    
        return response()->json([
            'success' => true,
            'data' => [
                'patient_id' => $id->patient_id,
                'full_name' => $fullName,
                'file_path' => asset('storage/' . $id->file_path),
            ]
        ]);
    }

    public function viewPending()
    {
        return view('admin.appointment-pending');
    }
    public function viewAppointments()
    {
        return view('admin.appointment-list');
    }

    public function populatePendingAppointment(Request $request)
    {
        $now = Carbon::now()->startOfDay();
        Appointment::where('status', 'Pending')
            ->whereDate('appointment_date', '<=', $now)
            ->update(['status' => 'Rejected']);

        $date = $request->input('filterDate') ?: Carbon::tomorrow()->toDateString();

        $appointments = Appointment::where('status', 'Pending')
            ->whereDate('appointment_date', $date)
            ->with(['user'])
            ->get();

        $appointmentsWithPatientName = $appointments->map(function ($appointment) {
            $fullName = $appointment->user ? $appointment->user->first_name . " " . $appointment->user->last_name : null;

            return [
                'id' => $appointment->id,
                'name' => $fullName,
                'appointment_date' => $appointment->appointment_date,
                'preference' => $appointment->preference,
                'status' => $appointment->status,
                'procedures' => $appointment->procedures, // Use 'procedures' column
                'remarks' => $appointment->remarks,
            ];
        });

        $appointmentCount = Appointment::whereDate('appointment_date', $date)
            ->where('status', 'Pending')
            ->count();

        return response()->json([
            'appointments' => $appointmentsWithPatientName,
            'appointmentCount' => $appointmentCount,
            'appointmentCap' => 30,
        ]);
    }

    public function populateScheduledAppointment(Request $request)
    {
        $date = $request->input('filterDate') ?: Carbon::tomorrow()->toDateString();

        $appointments = Appointment::whereIn('status', ['Accepted', 'Missed', 'Ongoing', 'Completed'])
            ->whereDate('appointment_date', $date)
            ->with(['user'])
            ->get()
            ->groupBy('preference');

        $appointmentsWithPatientDetails = $appointments->map(function ($appointmentGroup) {
            return $appointmentGroup->map(function ($appointment) {
                $fullName = $appointment->user ? $appointment->user->first_name . " " . $appointment->user->last_name : null;
                $userNumber = $appointment->user ? $appointment->user->number : null;

                return [
                    'id' => $appointment->id,
                    'name' => $fullName,
                    'number' => $userNumber,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => date('h:i A', strtotime($appointment->appointment_time)),
                    'preference' => $appointment->preference,
                    'status' => $appointment->status,
                    'procedures' => $appointment->procedures, // Use 'procedures' column
                    'remarks' => $appointment->remarks,
                ];
            });
        });

        return response()->json([
            'appointments' => $appointmentsWithPatientDetails,
        ]);
    }

    public function generateSchedulePDF(Request $request)
    {
        // Get the HTML content from the request
        $content = $request->input('content');

        if (strpos($content, '<table') === false) {
            // Wrap the content in a table only if it's not already inside a table tag
            $content = '<table id="scheduleTable">' . $content . '</table>';
        }

        $content = '
    <html>
        <head>
            <style>
                /* Add your CSS styles here */
                body {
                    margin: 0;
                    padding: 0;
                }

                html {
                    margin: 25px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    table-layout: fixed;
                    color: v.$black;
                }
                th, td {
                    border: 1px solid black;
                    padding: 0;
                    font-size: 0.8rem;
                    height: 30px;
                    text-align: center;
                }
            </style>
        </head>
        <body>' . $content . '</body>
    </html>';

        $pdf = PDF::loadHTML($content)
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', 20)  // Set top margin to 20 points
            ->setOption('margin-bottom', 20) // Set bottom margin to 20 points
            ->setOption('margin-left', 20)  // Set left margin to 20 points
            ->setOption('margin-right', 20);
        // Generate the file name
        $fileName = 'schedule_' . time() . '.pdf';

        // Return the PDF directly as a download
        return $pdf->download($fileName);
    }


    public function populateAppointmentList(Request $request)
    {
        $query = Appointment::whereNotIn('status', ['Pending', 'Accepted', 'Ongoing'])
            ->with('user')
            ->orderBy('updated_at', 'desc');

        // If there is a search filter, apply it to the query
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('procedures', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('appointment_date', 'like', "%{$search}%")
                    ->orWhere('appointment_time', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $appointments = $query->paginate(10);

        // Map the appointments for desired output format
        $appointmentsWithPatientName = $appointments->getCollection()->map(function ($appointment) {
            $fullName = $appointment->user ? $appointment->user->first_name . " " . $appointment->user->last_name : 'Unknown';

            return [
                'id' => $appointment->id,
                'name' => $fullName,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time ? date('h:i A', strtotime($appointment->appointment_time)) : 'None',
                'status' => $appointment->status,
                'procedures' => $appointment->procedures,
                'created_at' => $appointment->created_at->format('Y-m-d'),
            ];
        });

        // Log the final list of appointments to be returned
        Log::info('Final appointments to be returned:', $appointmentsWithPatientName->toArray());

        // Return paginated results as JSON
        return response()->json([
            'data' => $appointmentsWithPatientName,
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
            ]
        ]);
    }


    public function fetch($id)
    {
        $data = Appointment::find($id);

        if (!$data) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        $preferredTimes = [
            'Morning' => ['08:00', '12:00'],
            'Afternoon' => ['12:00', '16:00'],
        ];

        $timeRange = $preferredTimes[$data->preference] ?? null;

        return response()->json([
            'appointment' => $data,
            'timeRange' => $timeRange,
        ]);
    }

    public function confirm(Request $request)
    {

        $appointment = Appointment::findOrFail($request->id);

        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ], [
            'id.required' => 'ID does not exist',
        ]);

        $user = User::findOrFail($appointment->patient_id);

        $formattedDate = date('m-d-Y', strtotime($appointment->appointment_date));

        $formattedTime = date('g:i a', strtotime($request->time));

        $sent = $this->sendAcceptedSms(
            $user->first_name,
            $user->number,
            $formattedDate,
            $formattedTime
        );

        if ($validator->fails()) {
            return response()->json(false, 422);
        }

        if ($sent) {
            $appointment->status = 'Accepted';
            $appointment->appointment_time = $request->time;
            $appointment->touch();
            $appointment->save();


            return response()->json(true, 200);
        }
    }

    private function sendAcceptedSms($firstname, $number, $appointment, $formattedTimed)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';
        $url = 'https://api.semaphore.co/api/v4/priority';

        $client = new Client();

        try {
            $response = $client->post($url, [
                'form_params' => [
                    'apikey' => $apiKey,
                    'number' => $number,
                    'message' => "Dear $firstname, your appointment has been accepted on $appointment at $formattedTimed. Thank you! Best regards, Gracious Clinic",
                ]
            ]);


            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
    }

    private function sendRejectedSms($firstname, $number, $reason, $appointment)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';
        $url = 'https://api.semaphore.co/api/v4/priority';

        $client = new Client();

        // Conditionally append the reason to the message
        $reasonMessage = $reason ? " The reason is {$reason}." : '';

        $message = "Dear $firstname, your appointment has been rejected on $appointment.{$reasonMessage} Thank you! Best regards, Gracious Clinic.";

        try {
            $response = $client->post($url, [
                'form_params' => [
                    'apikey' => $apiKey,
                    'number' => $number,
                    'message' => $message,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
    }


    public function reject(Request $request)
    {
        $appointment = Appointment::findOrFail($request->id);
    
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ], [
            'id.required' => 'ID does not exist',
        ]);
    
        // Find the user associated with the appointment
        $user = User::findOrFail($appointment->patient_id);
    
        // Format the appointment date
        $formattedDate = date('m-d-Y', strtotime($appointment->appointment_date));
    
        // Get the rejection reason if provided
        $reason = $request->reason ? $request->reason : '';
    
        // Send rejection SMS
        $sent = $this->sendRejectedSms(
            $user->first_name,
            $user->number,
            $reason,
            $formattedDate
        );
    
        // Validate the request
        if ($validator->fails()) {
            return response()->json(false, 422);
        }
    
        // If the SMS was sent successfully, proceed to update the appointment and payment status
        if ($sent) {
            // Update the appointment status to 'Rejected'
            $appointment->status = 'Rejected';
            $appointment->touch();  // Update the timestamp
            $appointment->save();
    
            // Find the payment associated with the appointment (assuming the payment has a foreign key to appointment)
            $payment = Payment::where('appointment_id', $appointment->id)->first();
    
            if ($payment) {
                $payment->status = 'cancelled';
                $payment->save();
            }
    
            return response()->json(true, 200);
        }
    }
    
    public function update(Request $request)
    {
        $appointment = Appointment::findOrFail($request->id);

        $request->validate([
            'status' => 'required|string|in:Completed,Missed'
        ]);

        Log::info('Appointment status updated', [
            'appointment_id' => $appointment->id,
            'previous_status' => $appointment->status,
            'new_status' => $request->status,
        ]);

        $appointment->status = $request->status;
        $appointment->save();

        return response()->json([], 200);
    }
}
