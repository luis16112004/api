<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return response()->json(['message' => 'Auth endpoint']);
    }

    /**
     * Store a newly created resource in storage.
     * Acts as Registration
     */
    public function store(Request $request)
    {
        Log::info('Registering new user', $request->all());

        // Custom messages in English
        $messages = [
            'name.min' => 'Name must be greater than 2 characters',
            'email.email' => 'Please enter a valid email address',
            'password.min' => 'Password must be at least 6 characters',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',     // > 2 characters
            'email' => 'required|email|max:255',           // Valid email format
            'password' => 'required|string|min:6',         // Password validation
        ], $messages);

        if ($validator->fails()) {
            Log::warning('Auth validation failed', $validator->errors()->toArray());
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            // Logic to create user would go here (e.g., Firebase or DB)
            // $user = User::create($request->all());

            return response()->json([
                'message' => 'User registered successfully (Validation Passed)',
                'data' => $request->only(['name', 'email'])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error in register: ' . $e->getMessage());
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Log::info('Updating user', ['id' => $id, 'data' => $request->all()]);

        $messages = [
            'name.min' => 'Name must be greater than 2 characters',
            'email.email' => 'Please enter a valid email address',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:3|max:255',
            'email' => 'sometimes|email|max:255',
            'password' => 'sometimes|string|min:6',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}