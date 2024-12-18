<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Authorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    public function populateAuthorizations(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }
        $authorizations = Authorization::where('patient_id', $userId)
            ->with('user')
            ->get();

        $authorizationWithPatientName = $authorizations->map(function ($authorization) {
            $fullName = $authorization->user ? $authorization->user->first_name . " " . $authorization->user->last_name : null;
            return [
                'id' => $authorization->id,
                'name' => $fullName,
                'created_at' => $authorization->created_at->format('Y-m-d'),
                'file_path' => $authorization->file_path,
            ];
        });
        return response()->json([
            'data' => $authorizationWithPatientName,
        ]);
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'id' => 'required|exists:users,id',
            'file' => 'required|image|max:2048',
        ]);

        // Handle the file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->move(public_path('authorizations'), $filename);

            $authorization = Authorization::create([
                'patient_id' => $request->id,
                'file_path' => 'authorizations/' . $filename,
            ]);

            return response()->json([
                'message' => 'Authorization added successfully!',
                'data' => [
                    'authorization' => $authorization,
                    'file_url' => asset('authorizations/' . $filename),
                ]
            ]);
        }

        return response()->json(['message' => 'No file uploaded!'], 400);
    }
}
