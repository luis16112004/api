<?php
namespace App\Services;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Http;
class FirebaseService
{
    protected $db;
    protected $auth;
    protected $apiKey;
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

        // Initialize Firestore
        $this->db = new FirestoreClient([
            'keyFile' => $credentials  // Usamos 'keyFile' que acepta array
        ]);

        // Initialize Firebase Auth (Admin SDK)
        $factory = (new Factory)->withServiceAccount($credentials);
        $this->auth = $factory->createAuth();

        // Get Web API Key from env
        $this->apiKey = env('FIREBASE_API_KEY');
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
        // 2. Create user document in Firestore
        $collection = $this->db->collection('users');
        $now = new \DateTime();
        
        $userData = [
            'id' => $firebaseUid,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? 'vendedor', // Default role
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
        $collection->document($firebaseUid)->set($userData);
        return [
            'id' => $firebaseUid,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $userData['role'],
            'createdAt' => $now->format('Y-m-d H:i:s'),
            'updatedAt' => $now->format('Y-m-d H:i:s'),
        ];
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
        // Get user details from Firestore (includes role)
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
        $doc = $this->db->collection('users')->document($id)->snapshot();
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            return $this->normalizeData($data);
        }
        return null;
    }
     public function findUserByEmail(string $email)
    {
        $collection = $this->db->collection('users');
        $query = $collection->where('email', '=', $email)->documents();
        
        foreach ($query as $document) {
            $data = $document->data();
            $data['id'] = $document->id();
            return $this->normalizeData($data);
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

        // 2. Update Firestore Document
        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        // Add other fields here if needed in the future

        if (!empty($updateData)) {
            $updateData['updatedAt'] = new \DateTime();
            $this->db->collection('users')->document($uid)->set($updateData, ['merge' => true]);
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

        // 2. Delete from Firestore
        $this->db->collection('users')->document($uid)->delete();

        // 3. Optional: Delete all tokens for this user
        $tokens = $this->db->collection('personal_access_tokens')->where('user_id', '=', $uid)->documents();
        foreach ($tokens as $token) {
            $token->reference()->delete();
        }
    }
    // --- Helper ---
    private function normalizeData(array $data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof \Google\Cloud\Core\Timestamp) {
                $data[$key] = $value->get()->format('Y-m-d H:i:s');
            }
        }
        return $data;
    }
    public function createToken(string $userId)
    {
        $token = Str::random(64);
        $this->db->collection('personal_access_tokens')->add([
            'token' => hash('sha256', $token),
            'user_id' => $userId,
            'createdAt' => new \DateTime(),
            'last_used_at' => new \DateTime(), // Keep snake_case for internal fields if preferred, or switch all
        ]);

        return $token;
    }


    public function deleteToken(string $token)
    {
        $hashedToken = hash('sha256', $token);
        $query = $this->db->collection('personal_access_tokens')
            ->where('token', '=', $hashedToken)
            ->documents();

        foreach ($query as $document) {
            $document->reference()->delete();
        }
    }
    
    // Para referencia, aquí están los métodos de token necesarios para el middleware:
    public function verifyToken(string $token)
    {
        $hashedToken = hash('sha256', $token);
        $query = $this->db->collection('personal_access_tokens')
            ->where('token', '=', $hashedToken)
            ->documents();
        foreach ($query as $document) {
            return $document->data()['user_id'];
        }
        return null;
    }
    public function createProvider(array $data)
    {
        $now = new \DateTime();
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        
        $newProvider = $this->db->collection('providers')->add($data);
        
        $data['id'] = $newProvider->id();
        $data['createdAt'] = $now->format('Y-m-d H:i:s');
        $data['updatedAt'] = $now->format('Y-m-d H:i:s');
        
        return $data;
    }

    public function getProviders(string $userId = null)
    {
        $collection = $this->db->collection('providers');
        
        if ($userId) {
            $query = $collection->where('userId', '=', $userId);
        } else {
            $query = $collection;
        }

        $documents = $query->documents();
        $providers = [];
        
        foreach ($documents as $document) {
            $data = $document->data();
            $data['id'] = $document->id();
            $providers[] = $this->normalizeData($data);
        }

        return $providers;
    }

    public function getProvider(string $id)
    {
        $doc = $this->db->collection('providers')->document($id)->snapshot();
        
        if ($doc->exists()) {
            $data = $doc->data();
            $data['id'] = $doc->id();
            return $this->normalizeData($data);
        }
        
        return null;
    }

    public function updateProvider(string $id, array $data)
    {
        $docRef = $this->db->collection('providers')->document($id);
        
        if (!$docRef->snapshot()->exists()) {
            throw new \Exception('Provider not found');
        }

        $data['updatedAt'] = new \DateTime();
        $docRef->set($data, ['merge' => true]);

        return $this->getProvider($id);
    }

    public function deleteProvider(string $id)
    {
        $docRef = $this->db->collection('providers')->document($id);
        $docRef->delete();
    }
}