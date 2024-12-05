<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatientRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPatientRecordController extends Controller
{
    public function view()
    {
        $patients = User::all();
        return view('admin.patient-record', compact('patients'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required',
        ]);

        if ($validator->passes()) {
            $record = new PatientRecord();
            $record->patient_id = $request->input('patient_id');
            $record->save();


            return response()->json([], 200);
        } else {
            return response()->json([], 422);
        }
    }

    public function populate()
    {
        $records = PatientRecord::with('user')
            ->orderBy('updated_at')
            ->get();

        $recordsWithPatientName = $records->map(function ($record) {
            $fullName = $record->user ? $record->user->first_name . " " . $record->user->last_name : null;

            return [
                'id' => $record->id,
                'name' => $fullName,
                'medical_record' => $record->medical_record,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ];
        });

        return response()->json($recordsWithPatientName);
    }
}
