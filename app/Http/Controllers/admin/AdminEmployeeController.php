<?php

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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
            ->where('id', '!=', $loggedInUserId);

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
        $query = Employee::where('status', 'deactivated');

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
            'number' => 'required|unique:employees|min:11|max:11',
            'role' => ['required', 'string', 'in:Admin,Doctor,Staff'],
        ], [
            'username.unique' => 'Username already exists.',
            'number.unique' => 'Phone number already taken.',
            'confirm_password.same' => 'The password confirmation does not match.',
        ]);

        if ($validator->passes()) {
            $employee = new Employee();
            $employee->first_name = Str::title($request->input('first_name'));
            $employee->last_name = Str::title($request->input('last_name'));
            $employee->number = $request->input('number');
            $employee->role = $request->input('role');
            $employee->username = strtolower($request->input('username'));

            $password = 'GRACIOUS-CLINIC-' . Str::upper($request->input('last_name'));
            $employee->password = bcrypt($password);

            $employee->save();

            return response()->json([$employee]);
        } else {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    }

    public function update(Request $request)
    {
        $data = Employee::findOrFail($request->id);

        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                Rule::unique('employees')->ignore($data->id),
            ],
            'number' => [
                'required',
                Rule::unique('employees')->ignore($data->id),
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

        if ($validator->passes()) {
            $data->first_name = Str::title($request->first_name);
            $data->last_name = Str::title($request->last_name);
            $data->username = $request->username;
            $data->number = $request->number;
            $data->role = $request->role;

            $data->save();

            return response()->json($data);
        } else {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    }

    public function deactivate(Request $request)
    {
        $data = Employee::findOrFail($request->id);
        $data->status = 'Deactivated';
        $data->save();

        return response()->json($data);
    }

    public function activate(Request $request)
    {
        $data = Employee::findOrFail($request->id);
        $data->status = 'Activated';
        $data->save();

        return response()->json($data);
    }
}
