<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\EmailExists;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        // Como ya arreglaste XAMPP, podemos cargar todo nativo
        $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        
        $this->auth = $factory->createAuth();
        $this->firestore = $factory->createFirestore()->database();
    }

    // REGISTRO: Crea Auth + Documento en Firestore 'users'
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        try {
            // 1. Crear el usuario en el sistema de Autenticación
            $userProperties = [
                'email' => $request->email,
                'emailVerified' => false,
                'password' => $request->password,
                'displayName' => $request->name,
                'disabled' => false,
            ];

            $createdUser = $this->auth->createUser($userProperties);

            // 2. Guardar los datos extra en la colección 'users' (Gracias a gRPC esto es fácil)
            $userData = [
                'id' => $createdUser->uid,
                'name' => $request->name,
                'email' => $request->email,
                'createdAt' => now()->toIso8601String(),
                'updatedAt' => now()->toIso8601String(),
            ];

            // Guardamos en la colección 'users' usando el UID como llave del documento
            $this->firestore->collection('users')->document($createdUser->uid)->set($userData);

            return response()->json([
                'message' => 'Usuario registrado correctamente',
                'uid' => $createdUser->uid
            ], 201);

        } catch (EmailExists $e) {
            return response()->json(['error' => 'Este correo ya está registrado'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // LOGIN: Validar contraseña y devolver token
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // NOTA: El SDK de Admin NO permite loguear con contraseña (es por seguridad).
            // Usamos la API REST de Google solo para este paso.
            $apiKey = env('FIREBASE_API_KEY');
            
            $response = Http::post("https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}", [
                'email' => $request->email,
                'password' => $request->password,
                'returnSecureToken' => true
            ]);

            if ($response->failed()) {
                return response()->json(['message' => 'Credenciales incorrectas'], 401);
            }

            $authData = $response->json();
            $uid = $authData['localId'];

            // 3. (Opcional) Traemos los datos del usuario desde Firestore para devolverlos
            // Esto confirma que tu conexión a la BD 'users' funciona perfecto
            $userDoc = $this->firestore->collection('users')->document($uid)->snapshot();
            $userData = $userDoc->exists() ? $userDoc->data() : [];

            return response()->json([
                'message' => 'Login exitoso',
                'token' => $authData['idToken'], // Token para que Flutter lo use
                'user' => $userData
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}