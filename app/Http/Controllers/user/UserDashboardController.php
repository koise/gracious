<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function create()
    {
        return view('user.dashboard');
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

    public function populateAppointments()
    {
        $userId = Session::get('user_id');

        $appointments = Appointment::whereNotIn('status', ['Pending', 'Accepted'])
            ->where('patient_id', $userId)
            ->with('user')
            ->orderByDesc('updated_at')
            ->get();
        return response()->json($appointments);
    }

    public function fetchAppointments()
    {
        $userId = Session::get('user_id');
        $user = User::find($userId);

        $today = now()->toDateString();

        $appointments = Appointment::where('patient_id', $user->id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereIn('status', ['Pending', 'Accepted'])
            ->latest('created_at')
            ->get();

        return response()->json($appointments);
    }

    public function bookAppointment(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date|after:today',
                'preference' => 'required|string',
                'service' => 'required|string',
                'remarks' => 'nullable|string',
            ]);
            $appointment = Appointment::create([
                'patient_id' => $request->id,
                'appointment_date' => $request->date,
                'preference' => $request->preference,
                'service' => $request->service,
                'remarks' => $request->remarks,
            ]);

            return response()->json(['success' => true, 'message' => 'Appointment booked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while booking the appointment.'], 500);
        }
    }

    public function cancelAppointment(Request $request)
    {
        // Ensure $request->id is correctly passed and contains the expected patient ID
        $patientId = $request->id;

        // Debugging: Log queries to see what SQL is being executed
        DB::enableQueryLog();

        $appointment = Appointment::where('patient_id', $patientId)
            ->whereIn('status', ['Pending', 'Accepted'])
            ->latest()
            ->first();

        // Get the executed SQL query for debugging
        $queries = DB::getQueryLog();
        Log::info('SQL queries: ' . print_r($queries, true));

        if (!$appointment) {
            return response()->json(['error' => 'No upcoming appointment found for the patient.'], 404);
        }

        // Update appointment status to 'Cancelled'
        $appointment->status = 'Cancelled';
        $appointment->save();

        return response()->json(['success' => true], 200);
    }
}
