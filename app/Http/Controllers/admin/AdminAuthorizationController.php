<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Authorization;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
class AdminAuthorizationController extends Controller
{
    public function view()
    {

        return view('admin.authorization');
    }

    public function populateUsers(Request $request)
    {
        $query = User::where('status', 'Activated');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $query->orderBy('updated_at', 'desc');

        return response()->json($query->paginate(10));
    }

    public function populateRecords(Request $request)
{
    $query = Authorization::where('patient_id', $request->user_id);

    if ($request->has('search')) {
        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
                ->orWhere('patient_id', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('appointment_date', 'like', "%{$search}%");
        });
    }

    $records = $query->with('patient')->paginate(10);

    $mappedRecords = $records->getCollection()->map(function ($record) {
        return [
            'id' => $record->id,
            'patient_id' => $record->patient_id,
            'patient_name' => $record->patient ? $record->patient->first_name . ' ' . $record->patient->last_name : 'Unknown',
            'type' => $record->type,
            'file_path' => $record->file_path,
            'appointment_date' => $record->appointment_date,
            'created_at' => Carbon::parse($record->created_at)->format('Y-m-d'),
        ];
    });

    $records->setCollection(collect($mappedRecords));

    Log::info('Mapped Authorization Records:', ['data' => $mappedRecords]);

    return response()->json($records);
}

    public function getAppointmentDates($id)
{
    $appointments = Appointment::where('patient_id', $id)
        ->get(['appointment_date', 'procedures'])
        ->map(function ($appointment) {
            return [
                'appointment_date' => $appointment->appointment_date,
                'procedures' => $appointment->procedures,
            ];
        });

    Log::info('Appointments fetched for patient ID ' . $id, ['appointments' => $appointments]);

    return response()->json($appointments);
}


    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'type' => 'required',
            'appointment_date' => 'required',
            'file' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $type = $request->type;
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->move(public_path($type), $filename);

            $authorization = Authorization::create([
                'patient_id' => $request->id,
                'type' => $type,
                'appointment_date' => $request->appointment_date,
                'file_path' => "$type/$filename",
            ]);

            return response()->json([], 200);
        }

        return response()->json(['message' => 'No file uploaded!'], 400);
    }

    public function update(Request $request)
{
    $request->validate([
        'id' => 'required|exists:users,id',
        'type' => 'required',
        'appointment_date' => 'required',
    ]);

    $authorization = Authorization::where('id', $request->id)->first();

    if (!$authorization) {
        return response()->json(['message' => 'Authorization record not found!'], 404);
    }

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $type = $request->type;
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = public_path($type);

        // Delete the old file if it exists
        if ($authorization->file_path && file_exists(public_path($authorization->file_path))) {
            unlink(public_path($authorization->file_path));
        }

        // Move the new file to the designated folder
        $file->move($filePath, $filename);

        // Update the record with the new file path
        $authorization->update([
            'type' => $type,
            'appointment_date' => $request->appointment_date,
            'file_path' => "$type/$filename",
        ]);
    } else {
        // Update other fields if no file is uploaded
        $authorization->update([
            'type' => $request->type,
            'appointment_date' => $request->appointment_date,
        ]);
    }

    Log::info($authorization);

    return response()->json(['message' => 'Authorization updated successfully!'], 200);
}
}
