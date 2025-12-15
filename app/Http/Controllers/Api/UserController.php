<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = User::select('id', 'name', 'email', 'role', 'status')->get();

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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password'),
            'role'     => $request->role,
            'phone'   => $request->phone,
            'category_id' =>$request->category_id,
            'status'  => $status
        ]);

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
