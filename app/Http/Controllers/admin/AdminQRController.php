<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\QR;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AdminQRController extends Controller
{
    public function fetchQRData()
    {
        // Enable query logging
        DB::enableQueryLog();

        // Fetch active and deactivated QR codes
        $activeQRs = QR::where('status', 'active')->get();
        $deactivatedQRs = QR::where('status', 'inactive')->get();

        // Get executed queries and log them
        $queries = DB::getQueryLog();
        \Log::info('Executed Queries:', $queries);

        return response()->json([
            'activeQRs' => $activeQRs,
            'deactivatedQRs' => $deactivatedQRs,
        ]);
    }

    public function addQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'gcash_name' => 'required|string|max:255',
            'number' => 'required|numeric|digits:11',
            'images' => 'required|string', // Accepts Base64 string
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✅ Decode base64 image and store it
        $imageData = $request->input('images');

        if ($imageData) {
            // Remove the "data:image/png;base64," part
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = base64_decode($imageData);

            // Generate a unique filename
            $imageName = 'qr_' . time() . '.png';
            $imagePath = 'qr_images/' . $imageName;

            // Store in the public storage
            Storage::disk('public')->put($imagePath, $imageData);
        } else {
            $imagePath = null; // Allow null if no image is provided
        }

        // ✅ Insert into database (ensure column name is `image_path`)
        $qr = QR::create([
            'name' => $request->input('name'),
            'gcash_name' => $request->input('gcash_name'),
            'number' => $request->input('number'),
            'image_path' => $imagePath, 
            'status' => 'inactive',
        ]);

        return response()->json(['message' => 'QR Code added successfully!', 'qr' => $qr], 200);
    }

     // Fetch QR by ID
     public function showQr($id)
     {
         $qr = QR::find($id);
         if (!$qr) {
             return response()->json(['message' => 'QR code not found.'], 404);
         }
         return response()->json(['qr' => $qr]);
     }
 
     // Update QR Data
     public function updateQr(Request $request, $id)
     {
         $qr = QR::find($id);
         if (!$qr) {
             return response()->json(['message' => 'QR code not found.'], 404);
         }
 
         $request->validate([
             'qr_name' => 'required|string|max:255',
             'gcash_name' => 'required|string|max:255',
             'gcash_number' => 'required|string|max:20',
             'amount' => 'required|numeric|min:0',
             'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
         ]);
 
         // Update text fields
         $qr->name = $request->qr_name;
         $qr->gcash_name = $request->gcash_name;
         $qr->number = $request->gcash_number;
         $qr->amount = $request->amount;
 
         // Handle image upload
         if ($request->hasFile('image')) {
             if ($qr->image_path) {
                 Storage::delete($qr->image_path); // Delete old image
             }
             $path = $request->file('image')->store('qr_images', 'public');
             $qr->image_path = 'storage/' . $path;
         }
 
         $qr->save();
 
         return response()->json(['message' => 'QR Code updated successfully!', 'qr' => $qr]);
     }
    

    
}
