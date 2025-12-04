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
            // Si el usuario está autenticado, filtrar por userId
            $userId = null;
            if ($request->user()) {
                // Opcional: solo mostrar proveedores del usuario actual
                // $userId = $request->user()->id;
            }
            
            $providers = $this->firebaseService->getProviders($userId);
            
            Log::info('Providers listados', ['count' => count($providers)]);
            
            return response()->json([
                'message' => 'Proveedores obtenidos exitosamente',
                'data' => $providers,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en index: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener proveedores',
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
        Log::info('Creando provider', $request->all());
        
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

        $validator = Validator::make($mappedData, [
            'companyName' => 'required|string|max:255',
            'contactName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phoneNumber' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postalCode' => 'required|string|max:10',
            'country' => 'required|string|max:100',
            'userId' => 'nullable',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            // Si no se envía userId, usar el del usuario autenticado
            if (empty($mappedData['userId']) && $request->user()) {
                $mappedData['userId'] = $request->user()->id;
            }
            
            $provider = $this->firebaseService->createProvider($mappedData);

            Log::info('Provider creado', ['id' => $provider['id']]);

            return response()->json([
                'message' => 'Proveedor creado exitosamente',
                'data' => $provider,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error en store: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al crear proveedor',
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
                    'error' => 'Proveedor no encontrado'
                ], 404);
            }

            Log::info('Provider obtenido', ['id' => $id]);

            return response()->json([
                'message' => 'Proveedor obtenido exitosamente',
                'data' => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo provider', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener proveedor',
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
        Log::info('Actualizando provider', ['id' => $id, 'data' => $request->all()]);
        
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

        $validator = Validator::make($mappedData, [
            'companyName' => 'sometimes|required|string|max:255',
            'contactName' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phoneNumber' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postalCode' => 'sometimes|required|string|max:10',
            'country' => 'sometimes|required|string|max:100',
            'userId' => 'sometimes|nullable',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $provider = $this->firebaseService->updateProvider($id, $mappedData);

            Log::info('Provider actualizado', ['id' => $id]);

            return response()->json([
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando provider', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al actualizar proveedor',
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

            Log::info('Provider eliminado', ['id' => $id]);

            return response()->json([
                'message' => 'Proveedor eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando provider', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al eliminar proveedor',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}