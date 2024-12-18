<?php

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminEmployeeController extends Controller
{
    public function view()
    {
        return view('admin.employee');
    }

    public function fetchActiveEmployees(Request $request)
    {
        $loggedInUserId = Auth::guard('admin')->id();

        $query = Employee::where('status', 'activated')
            ->where('id', '!=', $loggedInUserId)
            ->orderBy('updated_at', 'desc');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    public function fetchDeactiveEmployees(Request $request)
    {
        $loggedInUserId = Auth::guard('admin')->id();
        $query = Employee::where('status', 'deactivated')
            ->where('id', '!=', $loggedInUserId)
            ->orderBy('updated_at', 'desc');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    public function fetch($id)
    {
        $data = Employee::find($id);
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|unique:employees',
            'number' => 'required|unique:employees|min:10|max:11',
            'role' => ['required', 'string', 'in:Admin,Doctor,Staff'],
        ], [
            'username.unique' => 'Username already exists.',
            'number.unique' => 'Phone number already taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }
        $validated = $validator->validated();
        $employee = Employee::create([
            'first_name' => Str::title($validated['first_name']),
            'last_name' => Str::title($validated['last_name']),
            'number' => $validated['number'],
            'role' => $validated['role'],
            'username' => $validated['username'],
            'password' => Hash::make('GRACIOUS-CLINIC-' . Str::upper($request->input('last_name'))),
        ]);


        return response()->json([], 200);
    }

    public function update(Request $request)
    {
        $employee = Employee::findOrFail($request->id);

        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                Rule::unique('employees')->ignore($employee->id),
            ],
            'number' => [
                'required',
                Rule::unique('employees')->ignore($employee->id),
            ],

            'first_name' => 'required',
            'last_name' => 'required',
            'role' => 'required',
        ], [
            'email.required' => 'Username is required.',
            'number.required' => 'Number is required.',
            'first_name.required' => 'First Name is required.',
            'last_name.required' => 'Last Name is required.',
            'role.required' => 'Role is required.',
            'username.unique' => 'Username already exists',
            'number.unique' => 'Phone number already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }
        $validated = $validator->validated();;

        $employee->update([
            'first_name' => Str::title($validated['first_name']),
            'last_name' => Str::title($validated['last_name']),
            'number' => $validated['number'],
            'role' => $validated['role'],
            'username' => $validated['username'],
        ]);

        return response()->json([], 200);
    }

    public function deactivate(Request $request)
    {
        $employee = Employee::findOrFail($request->id);;

        $employee->update([
            'status' => 'Deactivated',
        ]);

        return response()->json([], 200);
    }

    public function activate(Request $request)
    {
        $employee = Employee::findOrFail($request->id);

        $employee->update([
            'status' => 'Activated',
        ]);

        return response()->json([], 200);
    }
}
