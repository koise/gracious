<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AdminAppointmentController extends Controller
{
    public function viewPending()
    {
        return view('admin.appointment-pending');
    }


    public function populatePendingAppointment(Request $request)
    {
        $now = Carbon::now()->startOfDay();
        Appointment::where('status', 'pending')
            ->whereDate('appointment_date', '<=', $now)
            ->update(['status' => 'rejected']);

        $date = $request->input('filterDate') ?: Carbon::tomorrow()->toDateString();

        $appointments = Appointment::where('status', 'pending')
            ->whereDate('appointment_date', $date)
            ->with('user')
            ->get();

        $appointmentsWithPatientName = $appointments->map(function ($appointment) {
            $fullName = $appointment->user ? $appointment->user->first_name . " " . $appointment->user->last_name : null;

            return [
                'id' => $appointment->id,
                'name' => $fullName,
                'appointment_date' => $appointment->appointment_date,
                'preference' => $appointment->preference,
                'status' => $appointment->status,
                'service' => $appointment->service,
                'remarks' => $appointment->remarks,
            ];
        });

        // Check the appointment count for the selected date
        $appointmentCount = Appointment::whereDate('appointment_date', $date)
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'appointments' => $appointmentsWithPatientName,
            'appointmentCount' => $appointmentCount,
            'appointmentCap' => 30,
        ]);
    }



    public function populateAppointmentList()
    {
        $appointments = Appointment::where('status', '!=', 'Pending')
            ->with('user')
            ->orderBy('updated_at', 'desc')
            ->get();

        $appointmentsWithPatientName = $appointments->map(function ($appointment) {
            $fullName = $appointment->user ? $appointment->user->first_name . " " . $appointment->user->last_name : null;

            return [
                'id' => $appointment->id,
                'name' => $fullName,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'service' => $appointment->service,
                'created_at' => $appointment->created_at,
            ];
        });

        return response()->json($appointmentsWithPatientName);
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

    private function sendRejectedSms($firstname, $number, $appointment)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';
        $url = 'https://api.semaphore.co/api/v4/priority';

        $client = new Client();

        try {
            $response = $client->post($url, [
                'form_params' => [
                    'apikey' => $apiKey,
                    'number' => $number,
                    'message' => "Dear $firstname, your appointment has been rejected on $appointment. Thank you! Best regards, Gracious Clinic",
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


    public function reject(Request $request)
    {
        $appointment = Appointment::findOrFail($request->id);

        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ], [
            'id.required' => 'ID does not exist',
        ]);

        $user = User::findOrFail($appointment->patient_id);

        $formattedDate = date('m-d-Y', strtotime($appointment->appointment_date));

        $sent = $this->sendRejectedSms(
            $user->first_name,
            $user->number,
            $formattedDate,
        );

        if ($validator->fails()) {
            return response()->json(false, 422);
        }


        if ($sent) {
            $appointment->status = 'Rejected';
            $appointment->touch();
            $appointment->save();

            return response()->json(true, 200);
        }
    }
}
