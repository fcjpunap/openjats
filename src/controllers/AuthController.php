<?php
/**
 * Controlador AuthController - Manejo de autenticación
 */

require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userModel = new User();
    }
    
    public function login() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['username']) || !isset($input['password'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        $user = $this->userModel->login($input['username'], $input['password']);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
        }
    }
    
    public function logout() {
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
    
    public function checkAuth() {
        header('Content-Type: application/json');
        
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'] ?? '',
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
    }
    
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'No autenticado']);
                exit;
            } else {
                header('Location: /login.php');
                exit;
            }
        }
    }
    
    public function requireRole($role) {
        $this->requireAuth();
        if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            die('Acceso denegado');
        }
    }
    
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
