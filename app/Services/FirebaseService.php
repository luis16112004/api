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
        // ANTES:
// $keyFilePath = storage_path('app/firebase_credentials.json');

// AHORA (Copia y pega esto):
        $keyFilePath = env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase_credentials.json'));
        #$keyFilePath = storage_path('app/firebase_credentials.json');
        
        if (!file_exists($keyFilePath)) {
            Log::error('Firebase credentials file not found at: ' . $keyFilePath);
            throw new \Exception('Firebase credentials not found');
        }

        // Initialize Firestore
        $this->db = new FirestoreClient([
            'keyFilePath' => $keyFilePath
        ]);

        // Initialize Firebase Auth (Admin SDK)
        $factory = (new Factory)->withServiceAccount($keyFilePath);
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

        // 2. Create user document in Firestore (without password)
        $collection = $this->db->collection('users');
        $now = new \DateTime();
        
        $userData = [
            'id' => $firebaseUid, // Sync ID with Auth UID
            'name' => $data['name'],
            'email' => $data['email'],
            'createdAt' => $now,
            'updatedAt' => $now,
        ];

        // Use set() with the UID as document ID
        $collection->document($firebaseUid)->set($userData);

        return [
            'id' => $firebaseUid,
            'name' => $data['name'],
            'email' => $data['email'],
            'createdAt' => $now->format('Y-m-d H:i:s'),
            'updatedAt' => $now->format('Y-m-d H:i:s'),
        ];
    }

    public function loginUser(string $email, string $password)
    {
        if (!$this->apiKey) {
            throw new \Exception('FIREBASE_API_KEY not set in .env');
        }

        // Verify password using Firebase REST API
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

        // Get user details from Firestore
        $user = $this->findUserById($uid);

        if (!$user) {
            // If user exists in Auth but not in Firestore (legacy?), create it
            $user = [
                'id' => $uid,
                'email' => $email,
                'name' => $authData['displayName'] ?? 'User',
            ];
        }

        return $user;
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

    // --- Token Methods ---

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

    // --- Provider Methods ---

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
