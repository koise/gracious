<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Service;
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

            return response()->json(['success' => true, 'message' => 'Appointment booked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while booking the appointment.'], 500);
        }
    }

    public function cancelAppointment(Request $request)
    {
        $patientId = $request->id;

        $appointment = Appointment::where('patient_id', $patientId)
            ->whereIn('status', ['Pending', 'Accepted'])
            ->latest()
            ->first();

        if (!$appointment) {
            return response()->json(['error' => 'No upcoming appointment found for the patient.'], 404);
        }

        $appointment->status = 'Cancelled';
        $appointment->save();

        return response()->json(['success' => true], 200);
    }
}
