<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCreatedMail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = User::with('category:id,name')
        ->select('id', 'name', 'email', 'role', 'status', 'category_id')
        ->get();

        return response()->json([
            'status' => true,
            'users' => $list
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],
            'password' => 'required|min:6',
            'phone'    => 'required',
            'name'     => 'required',
            'role'     => 'required',
            'category_id' => 'required|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($request->status == '') {
            $status = 'active';
        } else {
            $status = $request->status;
        }

         $plainPassword = $request->password;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password'),
            'role'     => $request->role,
            'phone'   => $request->phone,
            'category_id' =>$request->category_id,
            'status'  => $status
        ]);

          Mail::to($user->email)->send(
            new UserCreatedMail($user->email, $plainPassword)
        );

        return response()->json([
        'status' => true,
        'message' => 'User created successfully',
        'data' => [
            'user_id'     => $user->id,
            // 'name'        => $user->name,
            // 'email'       => $user->email,
            // 'phone'       => $user->phone,
            // 'role'        => $user->role,
            // 'status'      => $user->status,
            'category_id' => $user->category_id,
            'category_name' => $user->category?->name
        ]
        ]);
    }


    public function deactivateUser(Request $request)
        {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $user = User::find($validated['user_id']);

            // If already inactive
            if ($user->status === 'inactive') {
                return response()->json([
                    'status'  => false,
                    'message' => 'User is already inactive'
                ], 400);
            }

            $user->update([
                'status' => 'inactive'
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'User deactivated successfully'
            ]);
        }

        public function activateUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::find($validated['user_id']);

        if ($user->status === 'active') {
            return response()->json([
                'status'  => false,
                'message' => 'User is already active'
            ], 400);
        }

        $user->update([
            'status' => 'active'
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'User activated successfully'
        ]);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
