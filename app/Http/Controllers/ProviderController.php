<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;

class ProviderController extends Controller
{
    protected $firestore;
    protected $collectionName = 'providers';

    public function __construct()
    {
        // Ruta al archivo JSON de credenciales
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));

        // Inicializamos Firestore
        $factory = (new Factory)->withServiceAccount($credentialsPath);
        
        // Aquí es donde te daba el error antes si no tenías la librería instalada
        $this->firestore = $factory->createFirestore()->database();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $documents = $this->firestore->collection($this->collectionName)->documents();
            $providers = [];

            foreach ($documents as $document) {
                if ($document->exists()) {
                    $data = $document->data();
                    $data['id'] = $document->id();
                    $providers[] = $data;
                }
            }
            return response()->json($providers);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            // Validamos que no venga vacío (básico)
            if(empty($data)) {
                 return response()->json(['message' => 'No data provided'], 400);
            }

            $data['createdAt'] = now()->toIso8601String();
            $newDoc = $this->firestore->collection($this->collectionName)->add($data);

            return response()->json([
                'message' => 'Proveedor creado',
                'id' => $newDoc->id()
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $doc = $this->firestore->collection($this->collectionName)->document($id)->snapshot();

            if (!$doc->exists()) {
                return response()->json(['message' => 'Proveedor no encontrado'], 404);
            }

            $data = $doc->data();
            $data['id'] = $doc->id();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $docRef = $this->firestore->collection($this->collectionName)->document($id);
            $docRef->set($request->all(), ['merge' => true]); // Merge evita borrar campos que no envíes

            return response()->json(['message' => 'Proveedor actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->firestore->collection($this->collectionName)->document($id)->delete();
            return response()->json(['message' => 'Proveedor eliminado']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}