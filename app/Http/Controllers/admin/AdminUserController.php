<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\Province;
use App\Models\City;

class AdminUserController extends Controller
{
    public function index()
    {
        return view('admin.user');
    }

    public function fetchActiveUsers(Request $request)
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

        // Order by the latest `updated_at`
        $query->orderBy('updated_at', 'desc');

        return response()->json($query->paginate(10));
    }

    public function fetchDeactiveUsers(Request $request)
    {
        $query = User::where('status', 'Deactivated');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Order by the latest `updated_at`
        $query->orderBy('updated_at', 'desc');

        return response()->json($query->paginate(10));
    }
    public function fetch($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $data = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'age' => $user->age,
            'number' => $user->number,
            'street_address' => $user->street_address,
            'province' => $user->province_id,
            'city' => $user->city_id,
        ];

        return response()->json($data);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:18|max:64',
            'number' => 'required|unique:users|min:10|max:11',
            'username' => 'required|unique:users|alpha_num|min:5|max:20',
            'street_address' => 'required',
            'province' => 'required',
            'city' => 'required',
        ], [
            'age.min' => 'You must be at least 18 years old to register.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = new User();
        $user->first_name = Str::title($request->input('first_name'));
        $user->last_name = Str::title($request->input('last_name'));
        $user->age = $request->age;
        $user->number = $request->number;
        $user->username = $request->input('username');
        $user->street_address = Str::title($request->street_address);
        $user->province_id = $request->province;
        $user->city_id = $request->city;
        $password = 'GRACIOUS-CLINIC-' . Str::upper($request->input('last_name'));
        $user->password = bcrypt($password);

        $user->save();

        return response()->json([], 200);
    }

    public function update(Request $request)
    {
        $user = User::findOrFail($request->id);

        // Validation rules
        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'alpha_num',
                'min:5',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'number' => [
                'required',
                'min:10',
                'max:11',
                Rule::unique('users')->ignore($user->id),
            ],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:18',
            'street_address' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ], [
            'age.min' => 'You must be at least 18 years old to register.',
  
        ]);

        if ($validator->passes()) {
            $user->username = $request->username;
            $user->number = $request->number;
            $user->first_name = Str::title($request->first_name);
            $user->last_name = Str::title($request->last_name);
            $user->age = $request->age;
            $user->street_address = $request->street_address;
            $user->province_id = $request->province;
            $user->city_id = $request->city;

            $user->touch(); 
            $user->save();

            return response()->json([], 200);
        } else {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    }

    public function deactivate(Request $request)
    {
        $data = User::findOrFail($request->id);
        $data->status = 'Deactivated';
        $data->touch();
        $data->save();

        return response()->json([], 200);
    }

    public function activate(Request $request)
    {
        $data = User::findOrFail($request->id);
        $data->status = 'Activated';
        $data->touch();
        $data->save();

        return response()->json([], 200);
    }
}
