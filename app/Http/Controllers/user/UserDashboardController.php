<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Service;
use App\Models\Payment;
use App\Models\Qr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserDashboardController extends Controller
{
    public function create()
    {
        $services = Service::all();
        return view('user.dashboard', compact('services'));
    }

    public function fetch()
    {
        $userId = Session::get('user_id');
        
        // Log the user ID for debugging
        Log::info('Fetching user ID from session', ['user_id' => $userId]);
        
        // If no user ID in session, return error
        if (!$userId) {
            Log::error('No user ID found in session');
            return response()->json(['error' => 'No user ID found in session'], 404);
        }

        $user = User::find($userId);

        if (!$user) {
            Log::error('User not found in database', ['user_id' => $userId]);
            return response()->json(['error' => 'User not found in database'], 404);
        }

        // Log successful fetch
        Log::info('Successfully fetched user data', ['user_id' => $userId]);
        
        return response()->json($user);
    }

    public function populateAppointments(Request $request)
    {
        $userId = Session::get('user_id');

        $query = Appointment::whereNotIn('status', ['Pending', 'Accepted', 'Ongoing'])
            ->where('patient_id', $userId)
            ->orderByDesc('updated_at');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('procedures', 'like', "%{$search}%")
                    ->orWhere('appointment_date', 'like', "%{$search}%");
            });
        }

        $appointments = $query->paginate(10)->through(function ($appointment) {
            // Format the appointment time and date
            $dateTime = $appointment->appointment_time
                ? $appointment->appointment_date . ' ' . $appointment->appointment_time
                : $appointment->appointment_date;

            $appointment->appointment_date_time = $appointment->appointment_time
                ? date('Y-m-d\TH:i:s', strtotime($dateTime))
                : date('Y-m-d\T00:00:00', strtotime($appointment->appointment_date));

            // Format the time display
            $appointment->formatted_time = $appointment->appointment_time
                ? date('g:i A', strtotime($appointment->appointment_time))
                : 'None';

            // Include procedures instead of service name
            $appointment->procedures_name = $appointment->procedures;

            unset($appointment->appointment_time);

            return $appointment;
        });

        return response()->json($appointments);
    }

    public function fetchAppointments()
    {
        try {
            $patientId = Session::get('user_id');
            $user = User::find($patientId);

            if (!$user) {
                return response()->json([]);
            }

            $appointments = Appointment::select(
                'id',
                'appointment_date', 
                'appointment_time',
                'procedures',
                'status',
                DB::raw('appointment_time as formatted_time'),
                DB::raw('TIMESTAMPDIFF(HOUR, NOW(), CONCAT(appointment_date, " ", appointment_time)) as hours_difference')
            )
            ->where('patient_id', $patientId)
            ->whereIn('status', ['Pending', 'Accepted', 'Ongoing'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get();

            foreach ($appointments as $appointment) {
                // Format the time for display
                $timeValue = $appointment->formatted_time;
                if ($timeValue) {
                    $time = Carbon::createFromFormat('H:i:s', $timeValue);
                    $appointment->formatted_time = $time->format('h:i A');
                } else {
                    $appointment->formatted_time = 'Not specified';
                }
            }

            // Log the response for debugging
            Log::info('Fetched appointments for user', [
                'patient_id' => $patientId,
                'appointments_count' => count($appointments)
            ]);

            return response()->json($appointments);
        } catch (\Exception $e) {
            Log::error('Error fetching appointments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([]);
        }
    }

    public function bookAppointment(Request $request)
    {
        try {
            // Add custom validation rule for weekdays
            Validator::extend('weekday', function ($attribute, $value, $parameters, $validator) {
                $date = new \DateTime($value);
                $dayOfWeek = $date->format('N'); // 1 (Monday) to 7 (Sunday)
                return $dayOfWeek <= 5; // Must be 1-5 (Monday-Friday)
            });
            
            $validator = Validator::make($request->all(), [
                'date' => 'required|date|after:today|weekday',
                'preference' => 'required|string|in:Morning,Afternoon',
                'procedures' => 'required|string|max:255',
                'remarks' => 'nullable|string|max:500',
                'terms' => 'required|accepted',
            ], [
                'date.required' => 'Please select an appointment date',
                'date.after' => 'Appointment date must be after today',
                'date.weekday' => 'Appointments cannot be booked on weekends. Please select a weekday.',
                'preference.required' => 'Please select a time preference',
                'preference.in' => 'Time preference must be either Morning or Afternoon',
                'procedures.required' => 'Please select at least one service',
                'terms.accepted' => 'You must accept the terms and conditions'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Log the request data for debugging
            Log::info('Appointment booking request', [
                'request_data' => $request->all()
            ]);

            // Get patient ID from request or session
            $patientId = $request->id;
            
            // If ID is not in the request, try to get it from the session
            if (!$patientId) {
                $patientId = Session::get('user_id');
                Log::info('Using patient ID from session', ['session_user_id' => $patientId]);
            }

            // Check if patient_id is valid
            if (!$patientId || !User::find($patientId)) {
                Log::error('Invalid patient ID', ['id' => $patientId]);
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid patient ID'
                ], 400);
            }

            // Check if user already has a pending or accepted appointment
            $existingAppointment = Appointment::where('patient_id', $patientId)
                ->whereIn('status', ['Pending', 'Accepted'])
                ->first();

            if ($existingAppointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only have ONE active appointment at a time. Please cancel your existing appointment before booking a new one.'
                ], 400);
            }

            // Create appointment with default status 'Pending'
            $appointment = Appointment::create([
                'patient_id' => $patientId,
                'appointment_date' => $request->date,
                'preference' => $request->preference,
                'procedures' => $request->procedures,
                'remarks' => $request->remarks,
                'status' => 'Pending'
            ]);

            // Create payment record
            Payment::create([
                'appointment_id' => $appointment->id,
                'status' => 'Pending',
                'paid' => 0.00,
                'total' => 0.00, 
                'qr_id' => null, 
            ]);

            Log::info('Appointment booked successfully', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patientId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment booked successfully.',
                'appointment' => $appointment
            ]);
        } catch (\Exception $e) {
            Log::error('Error booking appointment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while booking the appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelAppointment(Request $request)
    {
        try {
            // Get appointment ID from the request
            $appointmentId = $request->id;
            
            if (!$appointmentId) {
                Log::error('No appointment ID found for cancellation');
                return response()->json(['error' => 'No appointment ID provided for cancellation.'], 400);
            }

            // Find the appointment by ID
            $appointment = Appointment::find($appointmentId);

            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found.'], 404);
            }

            // Check if the appointment belongs to the current user
            $patientId = Session::get('user_id');
            if ($appointment->patient_id != $patientId) {
                Log::error('Unauthorized cancellation attempt', [
                    'appointment_id' => $appointmentId,
                    'patient_id' => $patientId,
                    'appointment_patient_id' => $appointment->patient_id
                ]);
                return response()->json(['error' => 'You are not authorized to cancel this appointment.'], 403);
            }

            // Check if the appointment is already cancelled or completed
            if (in_array($appointment->status, ['Cancelled', 'Completed', 'Rejected'])) {
                return response()->json(['error' => 'This appointment cannot be cancelled because it is already ' . $appointment->status . '.'], 400);
            }

            // Update appointment status to "Cancelled"
            $appointment->status = 'Cancelled';
            $appointment->save();

            // Update payment status if exists
            $payment = DB::table('payment')
                ->where('appointment_id', $appointment->id)
                ->first();

            if ($payment) {
                DB::table('payment')
                    ->where('appointment_id', $appointment->id)
                    ->update(['status' => 'Cancelled']);
            }

            Log::info('Appointment cancelled successfully', [
                'appointment_id' => $appointment->id,
                'patient_id' => $patientId
            ]);

            return response()->json(['success' => true, 'message' => 'Appointment cancelled successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error cancelling appointment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling the appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    //PAYMENT 
    public function indexPayment()
    {
        return view('user.payment');
    }

    public function getLatestAppointmentDetails()
    {
        $userId = 152; // Get logged-in user ID
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Fetch the latest appointment for the user based on updated_at
        $latestAppointment = Appointment::where('patient_id', $user->id)
            ->whereIn('status', ['Pending', 'Accepted', 'Ongoing', 'Completed'])
            ->orderByDesc('updated_at')
            ->select('id', 'appointment_date', 'appointment_time', 'status', 'procedures')
            ->first();

        if (!$latestAppointment) {
            return response()->json([
                'message' => 'No appointments found',
                'appointment' => null,
                'payments' => []
            ]);
        }

        // Fetch payment details related to the latest appointment
        $paymentDetails = DB::table('payment')
            ->join('appointments', 'payment.appointment_id', '=', 'appointments.id')
            ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id')
            ->leftJoin('services', 'appointments.procedures', '=', 'services.service')
            ->where('appointments.id', $latestAppointment->id)
            ->select(
                'payment.id as transaction_id',
                'appointments.procedures as procedure_name',
                DB::raw('COALESCE(services.service, "Unknown") as service_name'),
                'payment.total as balance',
                'payment.status',
                'appointments.appointment_date as date',
                'qr.id as qr_id',
                'qr.gcash_name as payment_recipient'
            )
            ->orderByDesc('payment.created_at')
            ->first(); // Using `first()` to match the expected single result

        // If no payment record exists, return a default structure
        if (!$paymentDetails) {
            $paymentDetails = (object) [
                'transaction_id' => null,
                'procedure_name' => $latestAppointment->procedures,
                'service_name' => 'Unknown',
                'balance' => '0.00',
                'status' => $latestAppointment->status,
                'date' => $latestAppointment->appointment_date,
                'qr_id' => null,
                'payment_recipient' => null
            ];
        }

        return response()->json($paymentDetails);
    }

    public function paymentHistory(Request $request)
    {
        $userId = Session::get('user_id');
        $user = User::find($userId);
    
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
    
        // Get payment history showing only Cancelled and Completed appointments
        $appointments = DB::table('payment')
            ->join('appointments', 'payment.appointment_id', '=', 'appointments.id')
            ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id')
            ->leftJoin('services', 'appointments.procedures', '=', 'services.service') 
            ->where('appointments.patient_id', $user->id)
            ->whereIn('appointments.status', ['Cancelled', 'Completed']) // Only include Cancelled and Completed
            ->orderByDesc('payment.created_at')
            ->select(
                'payment.id as transaction_id',
                'appointments.id as appointment_id',
                'appointments.procedures as procedure_name', 
                'services.service as service_name', 
                'payment.total as balance',
                'payment.status',
                'appointments.appointment_date as date',
                'appointments.status as appointment_status', // Include appointment status
                'qr.gcash_name as payment_recipient'
            )
            ->get();
    
        if ($appointments->isEmpty()) {
            return response()->json(['message' => 'No payment history found'], 404);
        }
    
        return response()->json($appointments);
    }

    // Calendar Events API
    public function getCalendarEvents(Request $request)
    {
        try {
            $start = $request->query('start');
            $end = $request->query('end');
            $userId = Session::get('user_id');

            if (!$start || !$end) {
                return response()->json(['error' => 'Start and end dates are required'], 400);
            }

            // Get all appointments for the current user that fall within the date range
            $appointments = Appointment::where('patient_id', $userId)
                ->where('appointment_date', '>=', date('Y-m-d', strtotime($start)))
                ->where('appointment_date', '<=', date('Y-m-d', strtotime($end)))
                ->get();

            // Format appointments for the calendar
            $events = $appointments->map(function ($appointment) {
                // Determine the event's end time based on appointment time
                $startTime = $appointment->appointment_time 
                    ? $appointment->appointment_date . ' ' . $appointment->appointment_time
                    : $appointment->appointment_date . ' 09:00:00'; // Default to 9 AM if no time

                $endTime = $appointment->appointment_time 
                    ? date('Y-m-d H:i:s', strtotime($startTime) + 3600) // Add 1 hour for end time
                    : $appointment->appointment_date . ' 10:00:00'; // Default to 10 AM if no time

                return [
                    'id' => $appointment->id,
                    'title' => $appointment->procedures,
                    'start' => date('Y-m-d\TH:i:s', strtotime($startTime)),
                    'end' => date('Y-m-d\TH:i:s', strtotime($endTime)),
                    'extendedProps' => [
                        'status' => $appointment->status,
                        'remarks' => $appointment->remarks,
                    ],
                    'allDay' => false,
                ];
            });

            return response()->json($events);
        } catch (\Exception $e) {
            \Log::error('Error fetching calendar events: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch calendar events'], 500);
        }
    }

    // Get booked time slots for a specific date
    public function getBookedSlots(Request $request)
    {
        try {
            $date = $request->query('date');
            
            if (!$date) {
                return response()->json(['error' => 'Date parameter is required'], 400);
            }

            // Find all appointments for this date
            $appointments = Appointment::where('appointment_date', $date)
                ->whereNotIn('status', ['Cancelled'])
                ->get();

            // Extract and format the booked time slots
            $bookedSlots = $appointments->map(function ($appointment) {
                if ($appointment->appointment_time) {
                    return date('H:i', strtotime($appointment->appointment_time));
                }
                
                // If no specific time, block the default time slot based on preference
                return $appointment->preference === 'Morning' ? '09:00' : '13:00';
            })->toArray();

            return response()->json(['booked_slots' => $bookedSlots]);
        } catch (\Exception $e) {
            \Log::error('Error fetching booked slots: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch booked slots'], 500);
        }
    }

    // Get booked time slots for an entire month
    public function getBookedSlotsMonth(Request $request)
    {
        try {
            $year = $request->query('year');
            $month = $request->query('month');
            
            if (!$year || !$month) {
                return response()->json(['error' => 'Year and month parameters are required'], 400);
            }

            // Find all appointments for this month
            $appointments = Appointment::whereYear('appointment_date', $year)
                ->whereMonth('appointment_date', $month)
                ->whereNotIn('status', ['Cancelled'])
                ->get();

            // Group appointments by date and format the times
            $bookedSlotsByDate = [];
            
            foreach ($appointments as $appointment) {
                $date = $appointment->appointment_date;
                
                if (!isset($bookedSlotsByDate[$date])) {
                    $bookedSlotsByDate[$date] = [];
                }
                
                if ($appointment->appointment_time) {
                    $bookedSlotsByDate[$date][] = date('H:i', strtotime($appointment->appointment_time));
                } else {
                    // If no specific time, block the default time slot based on preference
                    $bookedSlotsByDate[$date][] = $appointment->preference === 'Morning' ? '09:00' : '13:00';
                }
            }

            return response()->json(['booked_slots' => $bookedSlotsByDate]);
        } catch (\Exception $e) {
            \Log::error('Error fetching monthly booked slots: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch monthly booked slots'], 500);
        }
    }

    /**
     * Fetch the latest appointment for the user
     * This is specifically designed to return the latest active appointment 
     * (pending, accepted, ongoing, or completed) for payment processing
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchLatestAppointment()
    {
        try {
            $userId = Session::get('user_id');
            
            Log::info('Fetching latest appointment for user', ['user_id' => $userId]);
            
            if (!$userId) {
                Log::error('User not authenticated');
                return response()->json([
                    'error' => 'User not authenticated',
                    'message' => 'Please log in to view your appointments'
                ], 401);
            }

            // Get latest active appointment (pending, accepted, ongoing, or completed)
            $latestAppointment = Appointment::where('patient_id', $userId)
                ->whereIn('status', ['Pending', 'Accepted', 'Ongoing', 'Completed'])
                ->latest('appointment_date')
                ->first();
            
            if (!$latestAppointment) {
                Log::info('No active appointments found for user', ['user_id' => $userId]);
                return response()->json([
                    'error' => false,
                    'message' => 'No active appointments found',
                    'appointment' => null
                ]);
            }

            // Get payment information for this appointment if it exists
            $payment = Payment::where('appointment_id', $latestAppointment->id)->first();
            
            // Prepare appointment data with payment information
            $appointmentData = [
                'id' => $latestAppointment->id,
                'patient_id' => $latestAppointment->patient_id,
                'procedures' => $latestAppointment->procedures,
                'appointment_date' => $latestAppointment->appointment_date,
                'preference' => $latestAppointment->appointment_time ?? null,
                'status' => $latestAppointment->status,
                'remarks' => $latestAppointment->remarks ?? null,
                'payment_id' => $payment ? $payment->id : null,
                'balance' => $payment ? $payment->total : 0,
                'paid' => $payment ? $payment->paid : 0,
                'total' => $payment ? $payment->total : 0,
                'reference_number' => $payment ? $payment->reference_number : null,
                'qr_id' => $payment ? $payment->qr_id : null
            ];
            
            // If payment has a QR code, include its details
            if ($payment && $payment->qr_id) {
                $qr = Qr::find($payment->qr_id);
                if ($qr) {
                    $appointmentData['qr_name'] = $qr->name;
                    $appointmentData['gcash_name'] = $qr->gcash_name;
                    $appointmentData['qr_image_path'] = $qr->image_path;
                }
            }
            
            Log::info('Successfully fetched latest appointment data', [
                'appointment_id' => $latestAppointment->id,
                'status' => $latestAppointment->status
            ]);
            
            return response()->json([
                'error' => false,
                'message' => 'Latest appointment retrieved successfully',
                'appointment' => $appointmentData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching latest appointment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => 'Failed to retrieve latest appointment: ' . $e->getMessage()
            ], 500);
        }
    }
}