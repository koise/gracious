<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Qr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class AdminQRController extends Controller
{
    public function index(){
        return view('admin.qr');
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'name' => 'required',
                'file' => 'required|image|max:2048',
                'number' => 'required',
                'gcash_name' => 'required'
            ]);
    
            // Check if the request has a file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $type = $request->type ?? 'qr_images'; // Default folder set to 'qr_images'
                $filename = time() . '_' . $file->getClientOriginalName();
    
                // Directly store the file in the public directory
                $filePath = public_path("$type/$filename");
    
                // Ensure the directory exists
                if (!file_exists(public_path($type))) {
                    mkdir(public_path($type), 0777, true);  // Create directory if it doesn't exist
                }
    
                // Move the file to the public directory
                $file->move(public_path($type), $filename);
    
                // Store QR details in the database
                $QR = Qr::create([
                    'name' => $request->name,
                    'image_path' => "$type/$filename",  // Store relative path
                    'number' => $request->number,
                    'gcash_name' => $request->gcash_name,
                    'status' => 'inactive'
                ]);
    
                return response()->json(['message' => 'QR Code saved successfully!', 'file_path' => "$type/$filename"], 200);
            }
    
            return response()->json(['message' => 'No file uploaded!'], 400);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error occurred while storing QR Code:', [
                'error_message' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
    
            return response()->json(['message' => 'Something went wrong. Please try again.'], 500);
        }
    }    
    public function populateQRs(Request $request)
    {
        try {
            $query = Qr::query();
    
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', "%{$search}%");
            }
    
            $qrData = $query->paginate(10);
            return response()->json($qrData);
    
        } catch (\Exception $e) {
            Log::error('Error fetching QR data:', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['message' => 'Error fetching data. Please try again later.'], 500);
        }
    }    

    public function getQRCodeDetails($id)
    {
        $qrCode = Qr::find($id); // Make sure you are using the correct model name
        if ($qrCode) {
            return response()->json([
                'success' => true,
                'qrCode' => $qrCode
            ]);
        }
        return response()->json(['success' => false, 'message' => 'QR code not found'], 404);
    }
    
    
    public function update(Request $request, $id)
    {
        try {
            \Log::info('Update QR Request:', [
                'id' => $id,
                'data' => $request->all(),
                'has_file' => $request->hasFile('image')
            ]);
    
            // Validate request
            $request->validate([
                'name' => 'required',
                'gcash_name' => 'required',
                'number' => 'required',
                'image' => 'nullable|image|max:2048'
            ]);
    
            // Find the QR Code
            $qrCode = Qr::find($id);
            
            // Log if QR code is not found
            if (!$qrCode) {
                \Log::warning('QR code not found', ['id' => $id]);
                return response()->json(['message' => 'QR code not found'], 404);
            } else {
                \Log::info('QR code found', ['id' => $id, 'qrCode' => $qrCode]);
            }
    
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $folderPath = public_path('qr_images');
    
                // Ensure directory exists
                if (!file_exists($folderPath)) {
                    \Log::info('Creating directory for images');
                    mkdir($folderPath, 0777, true);
                }
    
                // Delete the old image if it exists
                if ($qrCode->image_path && file_exists(public_path($qrCode->image_path))) {
                    \Log::info('Deleting old image', ['path' => $qrCode->image_path]);
                    unlink(public_path($qrCode->image_path));
                }
    
                // Move the new file to the directory
                $file->move($folderPath, $filename);
                $qrCode->image_path = "qr_images/$filename";
            }
    
            // Update other fields
            $qrCode->name = $request->name;
            $qrCode->gcash_name = $request->gcash_name;
            $qrCode->number = $request->number;
            $qrCode->save();
    
            \Log::info('QR Code updated successfully', ['id' => $qrCode->id]);
            return response()->json(['message' => 'QR Code updated successfully', 'qrCode' => $qrCode], 200);
    
        } catch (\Exception $e) {
            \Log::error('Error updating QR code:', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['message' => 'Something went wrong, please try again.'], 500);
        }
    }    
}
