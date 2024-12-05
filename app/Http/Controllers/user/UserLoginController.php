<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class UserLoginController extends Controller
{
    public function create()
    {
        return view('user.login');
    }

    public function authenticate(Request $request)
    {
        // Validate the form inputs
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['errors' => ['username' => ['User not found.']]], 422);
        }

        if ($user->number_verified != 1) {
            return response()->json(['errors' => ['username' => ['Account not verified. Please verify your account.']]], 422);
        }

        if ($user->status !== 'Activated') {
            return response()->json(['errors' => ['username' => ['Account inactive.']]], 422);
        }

        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            Session::put('user_id', $user->id);
            return response()->json(['status' => 'success']);
        } else {
            return response()->json(['errors' => ['password' => ['Incorrect password.']]], 422);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('user.login');
    }
}
