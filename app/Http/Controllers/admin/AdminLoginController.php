<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        ], [
            'username.required' => 'Username is required.',
            'password.required' => 'Password is required.'
        ]);

        $user = \App\Models\Employee::where('username', $request->username)->first();

        if ($validator->fails()) {
            return redirect()->route('admin.login')->withErrors($validator)->withInput();
        }

        if (!$user) {
            return redirect()->route('admin.login')
                ->withErrors(['username' => 'Invalid username. Please try again.'])
                ->withInput();
        }

        if (!Auth::guard('admin')->attempt(['username' => $request->username, 'password' => $request->password])) {
            return redirect()->route('admin.login')
                ->withErrors(['password' => 'Invalid password. Please try again.'])
                ->withInput();
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
