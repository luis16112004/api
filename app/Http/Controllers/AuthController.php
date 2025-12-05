<?php
namespace App\Http\Controllers;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function register(Request $request)
    {
        Log::info('📝 Register attempt', ['email' => $request->email]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            //'role' => 'required|in:admin,vendedor', // Validación de rol
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }
        try {
            $user = $this->firebaseService->createUser([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'role' => $request->role, // Pasar rol
            ]);
            $token = $this->firebaseService->createToken($user['id']);
            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            Log::error('❌ Register error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al registrar usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function login(Request $request)
    {
        // ... (Tu código de login existente) ...
        // Al llamar a $this->firebaseService->loginUser(...), ahora traerá el rol.
        
        // Copia tu función login actual aquí.
        Log::info('🔐 Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip()
        ]);
        // Validación
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            Log::warning('❌ Validation failed', $validator->errors()->toArray());
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }
        try {
            // Autenticar con Firebase Auth
            $user = $this->firebaseService->loginUser($request->email, $request->password);
            // Crear token interno
            $token = $this->firebaseService->createToken($user['id']);
            Log::info('✅ Login successful', ['user_id' => $user['id']]);
            return response()->json([
                'message' => 'Login exitoso',
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
             // ... (Manejo de errores igual que antes) ...
             return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // ... (Resto de métodos) ...
    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();
            if ($token) {
                $this->firebaseService->deleteToken($token);
            }
            
            return response()->json([
                'message' => 'Logout exitoso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cerrar sesión',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            
            $uid = $this->firebaseService->verifyToken($token);
            if (!$uid) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            $this->firebaseService->updateUserPassword($uid, $request->password);

            return response()->json([
                'message' => 'Contraseña actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Change password error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al cambiar la contraseña',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar perfil (nombre)
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $token = $request->bearerToken();
            $uid = $this->firebaseService->verifyToken($token);
            if (!$uid) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            $user = $this->firebaseService->updateUserProfile($uid, ['name' => $request->name]);

            return response()->json([
                'message' => 'Perfil actualizado exitosamente',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Update profile error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al actualizar perfil',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cuenta
     */
    public function deleteAccount(Request $request)
    {
        // Validación de confirmación
        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|in:eliminarcuenta',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Debes escribir "eliminarcuenta" para confirmar.'
            ], 422);
        }

        try {
            $token = $request->bearerToken();
            $uid = $this->firebaseService->verifyToken($token);
            if (!$uid) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            $this->firebaseService->deleteUserAccount($uid);

            return response()->json([
                'message' => 'Cuenta eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Delete account error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al eliminar cuenta',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}