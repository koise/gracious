<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Id;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class UserProfileController extends Controller
{
    public function fetch()
    {
        $userId = Session::get('user_id');
    
        if (!$userId) {
            return response()->json(['error' => 'User not logged in'], 401);
        }
    
        $user = User::with(['province', 'city'])->find($userId); // Eager load province and city

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Prepare the user data
        $userDetails = $user->makeHidden(['password']);
        
        // Add province and city names to the response data
        $userDetails->province_name = $user->province ? $user->province->name : 'N/A';
        $userDetails->city_name = $user->city ? $user->city->name : 'N/A';
        $idImage = Id::where('patient_id', $userId)->first();
        Log::info('ID image fetched for user ID: ' . $idImage); // <-- Log success
        return response()->json([
            'image' => $idImage ? $idImage : 'No image found',
            'status' => 'success',
            'data' => $userDetails,
        ]);
    }

    public function uploadIdImage(Request $request)
    {
        $request->validate([
            'id_image' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $userId = Session::get('user_id'); // Assuming you're getting the logged-in user ID

        // Store the image in the 'id_images' directory
        $path = $request->file('id_image')->store('id_images', 'public');

        // Save the image path to the database
        $idImage = new Id();
        $idImage->patient_id = $userId;
        $idImage->file_path = $path;
        $idImage->save();

        return response()->json(['status' => 'success', 'message' => 'Image uploaded successfully', 'file_path' => $path]);
    }

   public function fetchImage(Request $request)
    {
        $userId = Session::get('user_id');  // Assuming 'user_id' is actually the patient_id
        
        if (!$userId) {
            return response()->json(['error' => 'User not logged in'], 401);
        }

        try {
            // Fetch the associated 'id' record for the user using the patient_id (userId)
            $idImage = Id::where('patient_id', $userId)->first();  // Ensure `patient_id` matches correctly

            if (!$idImage) {
                return response()->json(['error' => 'No ID image found for the user'], 404);
            }

            // Return the file path (or any additional data you need from the 'id' table)
            return response()->json([
                'status' => 'success',
                'image' => asset('storage/' . $idImage->file_path)
            ]);            
        } catch (\Exception $e) {
            // Log the error with the exception message
            Log::error('Image: ' . $e->getMessage());
            Log::info('Image URL: ' . asset('storage/' . $idImage->file_path));
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

}