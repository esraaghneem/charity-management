<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DonorController extends Controller
{
    public function updateProfile(Request $request)
    {
        $donor = Auth::user();
    
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'email'      => 'sometimes|required|email|unique:donors,email,' . $donor->id,
            'password'   => 'nullable|string|min:8|confirmed',
            
        ]);
    
        $donor->first_name = $validated['first_name'] ?? $donor->first_name;
        $donor->last_name  = $validated['last_name']  ?? $donor->last_name;
        $donor->email      = $validated['email']      ?? $donor->email;
        $donor->
    
        if (!empty($validated['password'])) {
            $donor->password = Hash::make($validated['password']);
        }
    
        $donor->save();
    
        return response()->json([
            'message' => __('messages.profile_updated_successfully'),
            'donor' => $donor,
        ]);
    }

    public function deleteAccount()
    {
        $donor = Auth::user();
    
        $donor->tokens()->delete();
        $donor->delete();
    
        return response()->json([
            'message' => __('messages.account_deleted_successfully')
        ]);
    }
}
