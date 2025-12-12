<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceToken;

class FCMController extends Controller
{
    public function saveToken(Request $request)
    {


       // return response()->json($request->all());
       // return response()->json(['msg' => 'API working']);
        try {
            $validated = $request->validate([
                'user_id'   => 'required|exists:users,id',
                'fcm_token' => 'required|string'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $userId   = $validated['user_id'];
        $token    = $validated['fcm_token'];

        // 1️⃣ Token already exists for this user → Do nothing
        $exists = DeviceToken::where('user_id', $userId)
                    ->where('fcm_token', $token)
                    ->first();

        if ($exists) {
            return response()->json([
                'status'  => true,
                'message' => 'Token already exists'
            ]);
        }

        // Token does not exist at all → Insert new record
        DeviceToken::create([
            'user_id'   => $userId,
            'fcm_token' => $token
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'FCM token saved successfully'
        ]);
    }
}

