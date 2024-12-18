<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class AdminProcedureController extends Controller
{
    public function populateUsers(Request $request)
    {
        $query = User::where('status', 'Activated')
            ->whereHas('appointment', function ($q) {
                $q->where('service', 'Orthodontic Treatment');
            })
            ->with('appointment');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $query->orderBy('updated_at', 'desc');

        $users = $query->paginate(10);
        return response()->json($users);
    }
}
