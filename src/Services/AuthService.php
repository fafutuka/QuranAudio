<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Exception;

class AuthService
{
    private DatabaseService $db;
    private string $jwtSecret;
    private int $jwtExpiry;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
        $config = require __DIR__ . '/../config/app.php';
        $this->jwtSecret = $config['jwt_secret'];
        $this->jwtExpiry = $config['jwt_expiry'];
    }

    public function register(array $data): array
    {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role_slug = $data['role'] ?? 'moderator'; // Default to moderator

        if (empty($name) || empty($email) || empty($password)) {
            return ['error' => 'Name, email, and password are required'];
        }

        // Check if user exists
        $existing = $this->db->runQuery("SELECT id FROM users WHERE email = ?", [$email]);
        if (!empty($existing)) {
            return ['error' => 'Email already registered'];
        }

        // Get role ID
        $role = $this->db->runQuery("SELECT id FROM roles WHERE slug = ?", [$role_slug]);
        if (empty($role)) {
            return ['error' => 'Invalid role specified'];
        }
        $role_id = $role[0]['id'];

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $userId = $this->db->runQuery(
            "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)",
            [$name, $email, $hashedPassword, $role_id]
        );

        if (!$userId) {
            return ['error' => 'Failed to create user'];
        }

        return ['message' => 'User registered successfully', 'user_id' => $userId];
    }

    public function login(string $email, string $password): array
    {
        $users = $this->db->runQuery(
            "SELECT u.*, r.slug as role_slug, r.name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ?",
            [$email]
        );

        if (empty($users)) {
            return ['error' => 'Invalid credentials'];
        }

        $userData = $users[0];
        if (!password_verify($password, $userData['password'])) {
            return ['error' => 'Invalid credentials'];
        }

        $user = new User($userData);
        $token = $this->generateToken($user);

        return [
            'token' => $token,
            'user' => $user->toArray()
        ];
    }

    private function generateToken(User $user): string
    {
        $payload = [
            'iat' => time(),
            'exp' => time() + $this->jwtExpiry,
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role_slug
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}
