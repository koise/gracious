<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminPatientRecordController extends Controller
{
    public function view()
    {
        return view('admin.patient-record');
    }

    public function populateUsers(Request $request)
    {
        try {
            // Build the query
            $query = User::where('status', 'Activated')
                ->whereHas('appointments', function ($q) {
                    $q->where('procedures', 'LIKE', '%Orthodontic Treatment%');
                })
                ->with('appointments')
                ->orderBy('updated_at', 'desc');

            // Add search filter
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%");
                });
            }

            // Paginate the query (without calling get())
            $paginatedUsers = $query->paginate(10);

            return response()->json($paginatedUsers);
        } catch (\Exception $e) {
            // Log any exceptions
            Log::error('Error in populateUsers:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to fetch users.'], 500);
        }
    }

    public function populateRecords(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        $medicalRecords = MedicalRecord::where('patient_id', $userId)
            ->get();

        $medicalRecordsWithFilePath = $medicalRecords->map(function ($record) {
            return [
                'id' => $record->id,
                'file_path' => $record->file_path,
            ];
        });
        $procedures = Procedure::where('patient_id', $userId)
            ->with('user')
            ->with('procedure')
            ->get();

        $procedureWithPatientName = $procedures->map(function ($procedure) {
            $fullName = $procedure->user ? $procedure->user->first_name . " " . $procedure->user->last_name : null;
            $service = $procedure->procedure ? $procedure->procedure->service : null;
            return [
                'id' => $procedure->id,
                'patient_id' => $procedure->patient_id,
                'name' => $fullName,
                'appointment_date' => $procedure->appointment_date ? $procedure->appointment_date->format('Y-m-d') : null,
                'procedure' => $service,
                'amount' => $procedure->amount,
                'paid' => $procedure->paid,
                'balance' => $procedure->balance,
            ];
        });

        return response()->json([
            'data' => [
                'procedures' => $procedureWithPatientName,
                'medical_records' => $medicalRecordsWithFilePath,
            ],
        ]);
    }

    public function addRecord(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        Procedure::create([
            'patient_id' => $userId,
            'appointment_date' => null,
            'procedure' => null,
            'amount' => null,
            'paid' => null,
            'balance' => null
        ]);

        return response()->json([], 200);
    }

    public function deleteRecord(Request $request)
    {
        $recordId = $request->input('record_id');

        if (!$recordId) {
            return response()->json(['error' => 'Record ID is required'], 400);
        }
        $deletedRows = Procedure::where('id', $recordId)->delete();

        if ($deletedRows > 0) {
            return response()->json([], 200);
        } else {
            return response()->json(['error' => 'No records found for the given record ID'], 404);
        }
    }

    public function saveRecord(Request $request)
    {
        $procedures = $request->input('procedures');

        foreach ($procedures as $procedureData) {
            // Log the procedure data before updating
            Log::info('Saving procedure data:', $procedureData);

            $procedure = Procedure::findOrFail($procedureData['id']);

            $procedure->update([
                'appointment_date' => $procedureData['appointment_date'] ?: null,
                'procedure' => $procedureData['procedure'] ?: null,
                'amount' => $procedureData['amount'] ?: null,
                'paid' => $procedureData['paid'] ?: null,
                'balance' => $procedureData['balance'] ?: null,
            ]);
        }

        return response()->json([], 200);
    }

    public function storeRecord(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'file' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();

            $existingRecord = MedicalRecord::where('patient_id', $request->id)->first();

            if ($existingRecord) {
                if (file_exists(public_path($existingRecord->file_path))) {
                    unlink(public_path($existingRecord->file_path));
                }
                $file->move(public_path('medical_records'), $filename);
                $existingRecord->update([
                    'file_path' => 'medical_records/' . $filename,
                ]);
            } else {
                $file->move(public_path('medical_records'), $filename);
                MedicalRecord::create([
                    'patient_id' => $request->id,
                    'file_path' => 'medical_records/' . $filename,
                ]);
            }

            return response()->json([], 200);
        }

        return response()->json(['message' => 'No file uploaded!'], 400);
    }
}
