<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProviderController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Display a listing of the resource.
     * GET /api/providers
     */
    public function index(Request $request)
    {
        try {
            // If the user is authenticated, filter by userId
            $userId = null;
            if ($request->user()) {
                // Optional: only show providers for the current user
                // $userId = $request->user()->id;
            }
            
            $providers = $this->firebaseService->getProviders($userId);
            
            Log::info('Providers listed', ['count' => count($providers)]);
            
            return response()->json([
                'message' => 'Providers retrieved successfully',
                'data' => $providers,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error retrieving providers',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/providers
     */
    public function store(Request $request)
    {
        Log::info('Creating provider', $request->all());
        
        $data = $request->all();
        $mappedData = [
            'companyName' => $data['companyName'] ?? $data['company_name'] ?? null,
            'contactName' => $data['contactName'] ?? $data['contact_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phoneNumber' => $data['phoneNumber'] ?? $data['phone_number'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postalCode' => $data['postalCode'] ?? $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'userId' => $data['userId'] ?? $data['user_id'] ?? null,
        ];

        // Custom messages to match visual validations
        $messages = [
            'companyName.min' => 'Name must be greater than 2 characters',
            'contactName.min' => 'Name must be greater than 2 characters',
            'phoneNumber.digits' => 'Phone number must be exactly 10 digits',
            'address.min' => 'Address must have at least 5 characters',
            'postalCode.regex' => 'Postal code must contain only numbers',
        ];

        $validator = Validator::make($mappedData, [
            'companyName' => 'required|string|min:3|max:255', // > 2 chars
            'contactName' => 'required|string|min:3|max:255', // > 2 chars
            'email' => 'required|email|max:255',
            'phoneNumber' => 'required|numeric|digits:10', // exactly 10 digits
            'address' => 'required|string|min:5|max:255', // at least 5 chars
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postalCode' => 'required|string|regex:/^[0-9]+$/|max:10', // Only numbers
            'country' => 'required|string|max:100',
            'userId' => 'nullable',
        ], $messages);

        if ($validator->fails()) {
            Log::warning('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            // If userId is not sent, use the authenticated user's ID
            if (empty($mappedData['userId']) && $request->user()) {
                $mappedData['userId'] = $request->user()->id;
            }
            
            $provider = $this->firebaseService->createProvider($mappedData);

            Log::info('Provider created', ['id' => $provider['id']]);

            return response()->json([
                'message' => 'Provider created successfully',
                'data' => $provider,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in store: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error creating provider',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/providers/{id}
     */
    public function show(string $id)
    {
        try {
            $provider = $this->firebaseService->getProvider($id);

            if (!$provider) {
                return response()->json([
                    'error' => 'Provider not found'
                ], 404);
            }

            Log::info('Provider retrieved', ['id' => $id]);

            return response()->json([
                'message' => 'Provider retrieved successfully',
                'data' => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving provider', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error retrieving provider',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/providers/{id}
     */
    public function update(Request $request, string $id)
    {
        Log::info('Updating provider', ['id' => $id, 'data' => $request->all()]);
        
        $data = $request->all();
        $mappedData = [
            'companyName' => $data['companyName'] ?? $data['company_name'] ?? null,
            'contactName' => $data['contactName'] ?? $data['contact_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phoneNumber' => $data['phoneNumber'] ?? $data['phone_number'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postalCode' => $data['postalCode'] ?? $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
            'userId' => $data['userId'] ?? $data['user_id'] ?? null,
        ];

        // Filter out nulls to allow partial updates
        $mappedData = array_filter($mappedData, function($value) { return !is_null($value); });

        // Custom messages for update as well
        $messages = [
            'companyName.min' => 'Name must be greater than 2 characters',
            'contactName.min' => 'Name must be greater than 2 characters',
            'phoneNumber.digits' => 'Phone number must be exactly 10 digits',
            'address.min' => 'Address must have at least 5 characters',
            'postalCode.regex' => 'Postal code must contain only numbers',
        ];

        $validator = Validator::make($mappedData, [
            'companyName' => 'sometimes|required|string|min:3|max:255',
            'contactName' => 'sometimes|required|string|min:3|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phoneNumber' => 'sometimes|required|numeric|digits:10',
            'address' => 'sometimes|required|string|min:5|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postalCode' => 'sometimes|required|string|regex:/^[0-9]+$/|max:10',
            'country' => 'sometimes|required|string|max:100',
            'userId' => 'sometimes|nullable',
        ], $messages);

        if ($validator->fails()) {
            Log::warning('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $provider = $this->firebaseService->updateProvider($id, $mappedData);

            Log::info('Provider updated', ['id' => $id]);

            return response()->json([
                'message' => 'Provider updated successfully',
                'data' => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating provider', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error updating provider',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/providers/{id}
     */
    public function destroy(string $id)
    {
        try {
            $this->firebaseService->deleteProvider($id);

            Log::info('Provider deleted', ['id' => $id]);

            return response()->json([
                'message' => 'Provider deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting provider', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error deleting provider',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}