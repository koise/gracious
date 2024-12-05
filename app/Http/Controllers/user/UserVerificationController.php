<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class UserVerificationController extends Controller
{
    public function create()
    {
        return view('user.verification');
    }

    public function verify($number)
    {
        return view('user.verification', ['phoneNumber' => $number]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'number' => 'required|numeric|exists:users,number'
        ]);

        $user = User::where('number', $request->number)->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

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
            return response()->json(['status' => 'ok', 'message' => 'OTP sent successfully']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP. Please try again later.'], 422);
        }
    }

    private function sendOtpViaSms($number, $otp)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';
        $url = 'https://api.semaphore.co/api/v4/otp';

        $client = new Client();

        try {
            $response = $client->post($url, [
                'form_params' => [
                    'apikey' => $apiKey,
                    'number' => $number,
                    'message' => "Your One Time Password is: $otp. Please use it within 5 minutes.",
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return true;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
    }

    public function process(Request $request)
    {
        $request->validate([
            'number' => 'required|numeric|exists:users,number',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('number', $request->input('number'))->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Number not found'], 404);
        }

        $otpEntry = Otp::where('user_id', $user->id)
            ->where('otp', $request->input('otp'))
            ->where('expires_at', '>=', now())
            ->first();

        if ($otpEntry) {
            $user->update(['number_verified' => 1]);
            return response()->json(['status' => 'ok', 'message' => 'Account verified']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired OTP'], 422);
        }
    }
}
