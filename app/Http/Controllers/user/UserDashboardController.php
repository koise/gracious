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

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

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
        $userId = Session::get('user_id');
        $user = User::find($userId);

        $today = now();

        // Fetch the latest appointment based on updated_at regardless of status
        $latestAppointment = Appointment::where('patient_id', $user->id)
            ->orderByDesc('updated_at')
            ->select('id', 'appointment_date', 'appointment_time', 'status', 'procedures')
            ->first();

        // Check if the latest appointment matches the filter criteria
        if (!$latestAppointment || !in_array($latestAppointment->status, ['Pending', 'Accepted', 'Ongoing', 'Completed']) || $latestAppointment->appointment_date < $today->toDateString()) {
            return response()->json([]);
        }

        // Process the latest appointment
        $appointments = collect([$latestAppointment])->map(function ($appointment) use ($today) {
            $dateTime = $appointment->appointment_time
                ? $appointment->appointment_date . ' ' . $appointment->appointment_time
                : $appointment->appointment_date;

            $appointmentTimestamp = strtotime($dateTime);
            $hoursDifference = ($appointmentTimestamp - $today->timestamp) / 3600;

            // Cancel appointment if pending and within 24 hours
            if ($appointment->status === 'Pending' && $hoursDifference < 24) {
                $appointmentModel = Appointment::find($appointment->id);
                $appointmentModel->status = 'Cancelled';
                $appointmentModel->save();

                // Update status to reflect the change
                $appointment->status = 'Cancelled';
            }

            // Set to ongoing if accepted and time has passed
            if ($appointment->status === 'Accepted' && $hoursDifference <= 0) {
                $appointmentModel = Appointment::find($appointment->id);
                $appointmentModel->status = 'Ongoing';
                $appointmentModel->save();

                // Update status to reflect the change
                $appointment->status = 'Ongoing';
            }

            $appointment->hours_difference = $hoursDifference;
            $appointment->appointment_date_time = $appointment->appointment_time
                ? date('Y-m-d\TH:i:s', strtotime($dateTime))
                : date('Y-m-d\T00:00:00', strtotime($appointment->appointment_date));

            $appointment->formatted_time = $appointment->appointment_time
                ? date('g:i A', strtotime($appointment->appointment_time))
                : 'None';

            return $appointment;
        });

        return response()->json($appointments);
    }



    public function bookAppointment(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date|after:today',
                'preference' => 'required|string',
                'procedures' => 'required|string',
                'remarks' => 'nullable|string',
            ]);
            $appointment = Appointment::create([
                'patient_id' => $request->id,
                'appointment_date' => $request->date,
                'preference' => $request->preference,
                'procedures' => $request->procedures,
                'remarks' => $request->remarks,
            ]);
            //DAGDAG SA SQL
            Payment::create([
                'appointment_id' => $appointment->id,
                'status' => 'Pending',
                'paid' => 0.00,
                'total' => 0.00, 
                'qr_id' => null, 
            ]);

            return response()->json(['success' => true, 'message' => 'Appointment booked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while booking the appointment.'], 500);
        }
    }

    public function cancelAppointment(Request $request)
    {
        $patientId = $request->id;

        // Find the latest pending or accepted appointment
        $appointment = Appointment::where('patient_id', $patientId)
            ->whereIn('status', ['Pending', 'Accepted'])
            ->latest()
            ->first();

        if (!$appointment) {
            return response()->json(['error' => 'No upcoming appointment found for the patient.'], 404);
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

        return response()->json(['success' => true, 'message' => 'Appointment and payment cancelled successfully.'], 200);
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
    
        // Get the latest appointment ID
        $latestAppointment = DB::table('appointments')
            ->where('patient_id', $user->id)
            ->orderByDesc('updated_at')
            ->value('id');
    
        // Get all payment history excluding the latest appointment
        $appointments = DB::table('payment')
            ->join('appointments', 'payment.appointment_id', '=', 'appointments.id')
            ->leftJoin('qr', 'payment.qr_id', '=', 'qr.id')
            ->leftJoin('services', 'appointments.procedures', '=', 'services.service') 
            ->where('appointments.patient_id', $user->id)
            ->when($latestAppointment, function ($query) use ($latestAppointment) {
                return $query->where('appointments.id', '!=', $latestAppointment);
            })
            ->orderByDesc('payment.created_at')
            ->select(
                'payment.id as transaction_id',
                'appointments.id as appointment_id',
                'appointments.procedures as procedure_name', 
                'services.service as service_name', 
                'payment.total as balance',
                'payment.status',
                'appointments.appointment_date as date',
                'qr.gcash_name as payment_recipient'
            )
            ->get();
    
        if ($appointments->isEmpty()) {
            return response()->json(['message' => 'No payment history found'], 404);
        }
    
        return response()->json($appointments);
    }
    


}