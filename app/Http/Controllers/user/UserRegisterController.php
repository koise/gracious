<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\City;
use Illuminate\Support\Str;

class UserRegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create()
    {
        return view('user.register');
    }

    /**
     * Process the registration request.
     */
    public function processRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:18|max:64',
            'number' => 'required|unique:users,number|min:10|max:11',
            'username' => 'required|unique:users,username|alpha_num|min:5|max:20',
            'street_address' => 'required|string|max:255',
            'province' => 'required|integer',
            'city' => 'required|integer',
            'password' => 'required|min:8|max:16',
            'confirm_password' => 'required|same:password',
            'terms' => 'accepted',
        ], [
            'age.min' => 'You must be at least 18 to 64 years old to register.',
            'number.unique' => 'Phone number already exists.',
            'username.unique' => 'Username already exists.',
            'terms.accepted' => 'You must accept our terms and conditions.',
            'confirm_password.same' => 'The password confirmation does not match.',
            'username.alpha_num' => 'Username must only contain letters and numbers.',
            'username.min' => 'Username must be at least 5 characters long.',
            'username.max' => 'Username cannot be longer than 20 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one digit, one special character, and be at least 8 characters long.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::create([
            'first_name' => Str::title($validated['first_name']),
            'last_name' => Str::title($validated['last_name']),
            'age' => $validated['age'],
            'number' => $validated['number'],
            'street_address' => Str::title($validated['street_address']),
            'city_id' => $validated['city'],
            'province_id' => $validated['province'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'number' => $user->number,
        ], 200);
    }


    /**
     * Fetch cities for a given province.
     */
    public function populateCities($provinceId)
    {
        $cities = City::where('province_id', $provinceId)->get(['id', 'name']);

        return response()->json($cities);
    }
}
