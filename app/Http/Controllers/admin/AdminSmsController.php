<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sms;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AdminSmsController extends Controller
{
    public function view()
    {
        return view('admin.sms-sender');
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

    public function confirm(Request $request)
    {
        $user = User::findOrFail($request->id);

        $request->validate([
            'id' => 'required|exists:users,id',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // Send SMS
        $sent = $this->sendSms(
            $user->first_name,
            $user->number,
            $request->subject,
            $request->message
        );

        $smsLog = new Sms();
        $smsLog->patient_id = $user->id;
        $smsLog->number = $user->number;
        $smsLog->subject = $request->subject;
        $smsLog->message = $request->message;
        $smsLog->save();

        if ($sent) {
            return response()->json([], 200);
        } else {
            return response()->json(['error' => 'Failed to send SMS'], 500);
        }
    }

    private function sendSms($firstname, $number, $subject, $message)
    {
        $apiKey = '8a187bf2a00ac9d4d87a1bfa37bed908';
        $url = 'https://api.semaphore.co/api/v4/priority';

        $client = new Client();

        try {
            $response = $client->post($url, [
                'form_params' => [
                    'apikey' => $apiKey,
                    'number' => $number,
                    'message' =>
                    "Subject: {$subject}\n\nDear {$firstname}, {$message}",
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
}
