<?php

namespace App\Http\Controllers\Admin;

use App\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function view()
    {
        return view('admin.login');
    }

    /**
     * Handle an admin login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Employee::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['errors' => ['username' => ['Username do not exists.']]], 422);
        }

        if ($user->status !== 'Activated') {
            return response()->json(['errors' => ['status' => ['Account deactivated.']]], 422);
        }

        if (!Auth::guard('admin')->attempt(['username' => $request->username, 'password' => $request->password])) {
            return response()->json(['errors' => ['password' => ['Incorrect password.']]], 422);
        }

        $request->session()->regenerate();

        $user = Auth::guard('admin')->user();

        if ($user->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'Doctor') {
            return redirect()->route('doctor.user');
        } elseif ($user->role === 'Staff') {
            return redirect()->route('staff.pending.appointment');
        }
    }

    /**
     * Logout the admin.
     *
     * @return \Illuminate\Http\RedirectResponse
     */

    public function logout()
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login');
    }
}
