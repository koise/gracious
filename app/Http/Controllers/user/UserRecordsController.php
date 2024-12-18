<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\Authorization;
use App\Models\MedicalRecord;
use App\Models\Procedure;

class UserRecordsController extends Controller
{
    public function create()
    {

        return view('user.records');
    }

    public function populateAuthorizations(Request $request)
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        $query = Authorization::where('patient_id', $userId)->with('user');

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('created_at', 'like', "%{$search}%");
            });
        }

        // Order and paginate results
        $results = $query->orderBy('updated_at', 'desc')->paginate(10);

        // Explicitly map and format created_at
        $formattedResults = $results->toArray(); // Convert to array for manipulation
        $formattedResults['data'] = array_map(function ($item) {
            $item['created_at'] = date('Y-m-d', strtotime($item['created_at'])); // Format created_at
            $item['updated_at'] = date('Y-m-d', strtotime($item['updated_at'])); // Format updated_at if needed
            return $item;
        }, $formattedResults['data']);

        return response()->json($formattedResults);
    }

    public function populateRecords(Request $request)
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        $query = MedicalRecord::where('patient_id', $userId)->with('user');

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('created_at', 'like', "%{$search}%");
            });
        }

        // Order and paginate results
        $results = $query->orderBy('updated_at', 'desc')->paginate(10);

        // Explicitly map and format created_at
        $formattedResults = $results->toArray(); // Convert to array for manipulation
        $formattedResults['data'] = array_map(function ($item) {
            $item['created_at'] = date('Y-m-d', strtotime($item['created_at'])); // Format created_at
            $item['updated_at'] = date('Y-m-d', strtotime($item['updated_at'])); // Format updated_at if needed
            return $item;
        }, $formattedResults['data']);

        return response()->json($formattedResults);
    }

    public function populateModalRecords()
    {
        $userId = Session::get('user_id');

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
}
