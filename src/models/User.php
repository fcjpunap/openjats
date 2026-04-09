<?php
/**
 * Modelo User - Gestión de usuarios
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = :identifier OR email = :identifier) AND active = TRUE",
            ['identifier' => $username]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Actualizar último login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );
            
            // No retornar el hash de contraseña
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }
    
    public function register($data) {
        // Verificar si el usuario ya existe
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = :username OR email = :email",
            ['username' => $data['username'], 'email' => $data['email']]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'El usuario o email ya existe'];
        }
        
        // Hash de la contraseña
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        try {
            $userId = $this->db->insert('users', $data);
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear usuario'];
        }
    }
    
    public function getUserById($id) {
        $user = $this->db->fetchOne(
            "SELECT id, username, email, full_name, role, created_at, last_login FROM users WHERE id = :id",
            ['id' => $id]
        );
        return $user;
    }
    
    public function updateProfile($userId, $data) {
        return $this->db->update('users', $data, 'id = :id', ['id' => $userId]);
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
        }
        
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->update('users', 
            ['password_hash' => $newHash],
            'id = :id',
            ['id' => $userId]
        );
        
        return ['success' => true];
    }
    
    public function getAllUsers() {
        return $this->db->fetchAll(
            "SELECT id, username, email, full_name, role, active, created_at, last_login 
             FROM users ORDER BY created_at DESC"
        );
    }
}
