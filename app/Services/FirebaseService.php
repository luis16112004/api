<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $databaseUrl;
    protected $auth;
    protected $apiKey;
    protected $credentials;
    protected $accessToken;
    
    public function __construct()
    {
        // 1. Intentar leer desde Variable de Entorno (Ideal para Railway)
        $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');
        
        $credentials = null;

        if (!empty($credentialsJson)) {
            // Si existe la variable, la usamos decodificándola
            $credentials = json_decode($credentialsJson, true);
        } else {
            // 2. Si no hay variable, buscamos el archivo (Ideal para Local)
            $keyFilePath = storage_path('app/firebase_credentials.json');
            if (file_exists($keyFilePath)) {
                $credentials = json_decode(file_get_contents($keyFilePath), true);
            }
        }

        if (!$credentials) {
            Log::error('Firebase credentials not found in ENV or File.');
            throw new \Exception('Firebase credentials not found. Please set FIREBASE_CREDENTIALS_JSON in Railway variables.');
        }

        $this->credentials = $credentials;
        
        // Set Realtime Database URL
        $this->databaseUrl = env('FIREBASE_DATABASE_URL', 'https://backendjaminfirebase-default-rtdb.firebaseio.com/');
        
        // Initialize Firebase Auth (Admin SDK) - still needed for user management
        $factory = (new Factory)->withServiceAccount($credentials);
        $this->auth = $factory->createAuth();

        // Get Web API Key from env
        $this->apiKey = env('FIREBASE_API_KEY');
        
        // Get access token for Realtime Database
        $this->accessToken = $this->getAccessToken();
    }
    
    /**
     * Get access token for Realtime Database using service account
     */
    private function getAccessToken()
    {
        try {
            $now = time();
            $exp = $now + 3600; // Token expires in 1 hour
            
            // Create JWT
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];
            
            $payload = [
                'iss' => $this->credentials['client_email'],
                'sub' => $this->credentials['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $exp,
                'iat' => $now,
                'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email'
            ];
            
            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
            
            $signatureInput = $headerEncoded . '.' . $payloadEncoded;
            
            // Sign with private key - need to create a resource from the key string
            $privateKeyResource = openssl_pkey_get_private($this->credentials['private_key']);
            if (!$privateKeyResource) {
                throw new \Exception('Failed to load private key: ' . openssl_error_string());
            }
            
            openssl_sign($signatureInput, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
            openssl_free_key($privateKeyResource);
            
            $signatureEncoded = $this->base64UrlEncode($signature);
            
            $jwt = $signatureInput . '.' . $signatureEncoded;
            
            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);
            
            if ($response->failed()) {
                Log::error('Failed to get access token', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to get access token: ' . $response->body());
            }
            
            $tokenData = $response->json();
            if (!isset($tokenData['access_token'])) {
                throw new \Exception('No access token in response: ' . json_encode($tokenData));
            }
            
            return $tokenData['access_token'];
            
        } catch (\Exception $e) {
            Log::error('Error getting access token: ' . $e->getMessage());
            throw new \Exception('Failed to authenticate with Firebase: ' . $e->getMessage());
        }
    }
    
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Make authenticated request to Realtime Database
     */
    private function dbRequest(string $method, string $path, $data = null)
    {
        $url = rtrim($this->databaseUrl, '/') . '/' . ltrim($path, '/') . '.json';
        
        // Add access token as query parameter (Realtime Database uses query param, not header)
        $url .= '?access_token=' . urlencode($this->accessToken);
        
        $response = null;
        
        switch (strtoupper($method)) {
            case 'GET':
                $response = Http::get($url);
                break;
            case 'PUT':
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->put($url, $data);
                break;
            case 'PATCH':
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->patch($url, $data);
                break;
            case 'DELETE':
                $response = Http::delete($url);
                break;
            case 'POST':
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $data);
                break;
            default:
                throw new \Exception("Unsupported HTTP method: {$method}");
        }
        
        if ($response->failed()) {
            Log::error('Realtime Database request failed', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Database request failed: ' . $response->body());
        }
        
        // For DELETE requests, return null
        if (strtoupper($method) === 'DELETE') {
            return null;
        }
        
        $result = $response->json();
        
        // Realtime Database returns null for non-existent paths
        return $result;
    }
    
    // --- User Methods ---
    public function createUser(array $data)
    {
        // 1. Create user in Firebase Authentication
        $userProperties = [
            'email' => $data['email'],
            'emailVerified' => false,
            'password' => $data['password'],
            'displayName' => $data['name'],
            'disabled' => false,
        ];
        try {
            $createdUser = $this->auth->createUser($userProperties);
            $firebaseUid = $createdUser->uid;
        } catch (\Exception $e) {
            throw new \Exception('Error creating user in Firebase Auth: ' . $e->getMessage());
        }
        
        // 2. Create user in Realtime Database
        $now = new \DateTime();
        $nowStr = $now->format('Y-m-d H:i:s');
        
        $userData = [
            'id' => $firebaseUid,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'vendedor',
            'createdAt' => $nowStr,
            'updatedAt' => $nowStr,
        ];
        
        $this->dbRequest('PUT', "users/{$firebaseUid}", $userData);
        
        return $userData;
    }
    
    public function loginUser(string $email, string $password)
    {
        if (!$this->apiKey) {
            throw new \Exception('FIREBASE_API_KEY not set in .env');
        }
        $response = Http::post('https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=' . $this->apiKey, [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]);
        if ($response->failed()) {
            throw new \Exception('Invalid credentials or Firebase error: ' . $response->body());
        }
        $authData = $response->json();
        $uid = $authData['localId'];
        
        // Get user details from Realtime Database (includes role)
        $user = $this->findUserById($uid);
        if (!$user) {
            $user = [
                'id' => $uid,
                'email' => $email,
                'name' => $authData['displayName'] ?? 'User',
                'role' => 'vendedor', // Fallback role
            ];
        }
        return $user;
    }
    
    public function findUserById(string $id)
    {
        $data = $this->dbRequest('GET', "users/{$id}");
        
        // Realtime Database returns null if path doesn't exist
        if ($data !== null && !empty($data)) {
            $data['id'] = $id;
            return $data;
        }
        return null;
    }
    
    public function findUserByEmail(string $email)
    {
        $users = $this->dbRequest('GET', 'users');
        
        // Realtime Database returns null if path doesn't exist
        if ($users !== null && is_array($users)) {
            foreach ($users as $id => $userData) {
                if (isset($userData['email']) && $userData['email'] === $email) {
                    $userData['id'] = $id;
                    return $userData;
                }
            }
        }
        
        return null;
    }
    
    public function updateUserPassword(string $uid, string $newPassword)
    {
        try {
            $this->auth->changeUserPassword($uid, $newPassword);
        } catch (\Exception $e) {
            throw new \Exception('Error updating password in Firebase Auth: ' . $e->getMessage());
        }
    }

    public function updateUserProfile(string $uid, array $data)
    {
        // 1. Update Auth Profile (DisplayName) if provided
        if (isset($data['name'])) {
            try {
                $this->auth->updateUser($uid, ['displayName' => $data['name']]);
            } catch (\Exception $e) {
                throw new \Exception('Error updating user profile in Firebase Auth: ' . $e->getMessage());
            }
        }

        // 2. Update Realtime Database
        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        
        if (!empty($updateData)) {
            $updateData['updatedAt'] = (new \DateTime())->format('Y-m-d H:i:s');
            $this->dbRequest('PATCH', "users/{$uid}", $updateData);
        }
        
        return $this->findUserById($uid);
    }

    public function deleteUserAccount(string $uid)
    {
        // 1. Delete from Firebase Auth
        try {
            $this->auth->deleteUser($uid);
        } catch (\Exception $e) {
            throw new \Exception('Error deleting user from Firebase Auth: ' . $e->getMessage());
        }

        // 2. Delete from Realtime Database
        $this->dbRequest('DELETE', "users/{$uid}");

        // 3. Delete all tokens for this user
        $tokens = $this->dbRequest('GET', 'personal_access_tokens');
        if ($tokens !== null && is_array($tokens)) {
            foreach ($tokens as $tokenId => $tokenData) {
                if (isset($tokenData['user_id']) && $tokenData['user_id'] === $uid) {
                    $this->dbRequest('DELETE', "personal_access_tokens/{$tokenId}");
                }
            }
        }
    }
    
    // --- Helper ---
    private function normalizeData(array $data)
    {
        // For Realtime Database, data is already in simple format
        return $data;
    }
    
    public function createToken(string $userId)
    {
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        
        $tokenData = [
            'token' => $hashedToken,
            'user_id' => $userId,
            'createdAt' => $now,
            'last_used_at' => $now,
        ];
        
        // Generate a unique ID for the token
        $tokenId = Str::random(20);
        $this->dbRequest('PUT', "personal_access_tokens/{$tokenId}", $tokenData);

        return $token;
    }

    public function deleteToken(string $token)
    {
        $hashedToken = hash('sha256', $token);
        $tokens = $this->dbRequest('GET', 'personal_access_tokens');
        
        // Realtime Database returns null if path doesn't exist
        if ($tokens !== null && is_array($tokens)) {
            foreach ($tokens as $tokenId => $tokenData) {
                if (isset($tokenData['token']) && $tokenData['token'] === $hashedToken) {
                    $this->dbRequest('DELETE', "personal_access_tokens/{$tokenId}");
                    return;
                }
            }
        }
    }
    
    public function verifyToken(string $token)
    {
        $hashedToken = hash('sha256', $token);
        $tokens = $this->dbRequest('GET', 'personal_access_tokens');
        
        // Realtime Database returns null if path doesn't exist
        if ($tokens !== null && is_array($tokens)) {
            foreach ($tokens as $tokenId => $tokenData) {
                if (isset($tokenData['token']) && $tokenData['token'] === $hashedToken) {
                    return $tokenData['user_id'] ?? null;
                }
            }
        }
        return null;
    }
    
    public function createProvider(array $data)
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        
        // Generate a unique ID for the provider
        $providerId = Str::random(20);
        $this->dbRequest('PUT', "providers/{$providerId}", $data);
        
        $data['id'] = $providerId;
        return $data;
    }

    public function getProviders(string $userId = null)
    {
        $providersData = $this->dbRequest('GET', 'providers');
        $providers = [];
        
        // Realtime Database returns null if path doesn't exist
        if ($providersData !== null && is_array($providersData)) {
            foreach ($providersData as $id => $providerData) {
                if ($userId === null || (isset($providerData['userId']) && $providerData['userId'] === $userId)) {
                    $providerData['id'] = $id;
                    $providers[] = $providerData;
                }
            }
        }

        return $providers;
    }

    public function getProvider(string $id)
    {
        $data = $this->dbRequest('GET', "providers/{$id}");
        
        // Realtime Database returns null if path doesn't exist
        if ($data !== null && !empty($data)) {
            $data['id'] = $id;
            return $data;
        }
        
        return null;
    }

    public function updateProvider(string $id, array $data)
    {
        // Check if provider exists
        $existing = $this->getProvider($id);
        if (!$existing) {
            throw new \Exception('Provider not found');
        }

        $data['updatedAt'] = (new \DateTime())->format('Y-m-d H:i:s');
        $this->dbRequest('PATCH', "providers/{$id}", $data);

        return $this->getProvider($id);
    }

    public function deleteProvider(string $id)
    {
        $this->dbRequest('DELETE', "providers/{$id}");
    }
}
