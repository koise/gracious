<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Otp;
use GuzzleHttp\Client;

class UserResetPasswordController extends Controller
{
    public function create()
    {
        return view('user.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|numeric|exists:users,number',
        ], [
            'number.exists' => 'Phone Number do not exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('number', $request->number)->first();


        $otp = random_int(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        Otp::create([
            'user_id' => $user->id,
            'number' => $request->number,
            'otp' => $otp,
            'expires_at' => $expiresAt
        ]);

        // Send OTP via SMS and handle the response
        if ($this->sendOtpViaSms($request->number, $otp)) {
            return response()->json([], 200);
        } else {
            return response()->json(['errors' => ['otp' => ['Failed to send OTP. Please try again later.']]], 422);
        }
    }

    private function sendOtpViaSms($number, $otp)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';
        $url = 'https://api.semaphore.co/api/v4/messages';

        $client = new Client();

        $response = $client->post($url, [
            'form_params' => [
                'apikey' => $apiKey,
                'number' => $number,
                'message' => "Your Reset Password Code is: $otp. Please use it within 5 minutes.",
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            return true;
        } else {
            return false;
        }
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|numeric|exists:users,number',
            'otp' => 'required|numeric',
        ], [
            'number.exists' => 'Phone Number do not exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('number', $request->number)->first();

        $otpEntry = Otp::where('user_id', $user->id)
            ->where('otp', $request->input('otp'))
            ->where('expires_at', '>=', now())
            ->first();

        if ($otpEntry) {
            return response()->json([], 200);
        } else {
            return response()->json(['errors' => ['otp' => ['Otp is invalid or expired.']]], 422);
        }
    }
}
