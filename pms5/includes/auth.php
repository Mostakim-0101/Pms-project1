<?php
/**
 * Secure Authentication System
 * Handles user authentication, session management, and role-based access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection ($conn)
require_once __DIR__ . '/../config.php';

class Auth {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Register a new patient with secure password hashing
     */
    public function registerPatient($full_name, $email, $password) {
        try {
            // Normalize inputs
            $full_name = trim((string)$full_name);
            $email     = strtolower(trim((string)$email));

            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT patient_id FROM Patients WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new patient
            $stmt = $this->conn->prepare("INSERT INTO Patients (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $patient_id = $this->conn->insert_id;
                
                // Create default role for patient
                $this->assignRole($patient_id, 'Patient', 'patient');
                
                return ['success' => true, 'message' => 'Registration successful', 'patient_id' => $patient_id];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Authenticate user login
     * NOTE: Doctors & Admins use a fixed default password 'pass1234'.
     *       Patients use hashed passwords stored in the DB.
     */
    public function login($email, $password, $user_type = 'Patient') {
        try {
            // Normalize inputs
            $email = strtolower(trim((string)$email));
            $password = (string)$password;

            // Build dynamic table/id safely
            $allowed_types = ['Patient','Doctor','Admin'];
            if (!in_array($user_type, $allowed_types, true)) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            $table   = $user_type . 's';                  // Patients / Doctors / Admins
            $id_field = strtolower($user_type) . '_id';   // patient_id / doctor_id / admin_id
            
            // Fetch user by email
            $sql = "SELECT {$id_field}, full_name, email, password FROM {$table} WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            $user = $result->fetch_assoc();

            // Doctors & Admins → fixed default password
            if ($user_type === 'Doctor' || $user_type === 'Admin') {
                if ($password === 'pass1234') {
                    $this->createSession($user[$id_field], $user_type, $user['full_name'], $user['email']);
                    return [
                        'success'   => true, 
                        'message'   => 'successful',
                        'user_id'   => $user[$id_field],
                        'user_type' => $user_type,
                        'full_name' => $user['full_name']
                    ];
                }
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Patients → verify against hash
            if (password_verify($password, $user['password'])) {
                $this->createSession($user[$id_field], $user_type, $user['full_name'], $user['email']);
                return [
                    'success'   => true, 
                    'message'   => 'successful',
                    'user_id'   => $user[$id_field],
                    'user_type' => $user_type,
                    'full_name' => $user['full_name']
                ];
            }

            return ['success' => false, 'message' => 'Invalid credentials'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create secure session
     */
    private function createSession($user_id, $user_type, $full_name, $email) {
        // Generate secure session ID
        $session_id = bin2hex(random_bytes(32));
        
        // Store session data in PHP session
        $_SESSION['user_id']    = $user_id;
        $_SESSION['user_type']  = $user_type;
        $_SESSION['full_name']  = $full_name;
        $_SESSION['email']      = $email;
        $_SESSION['session_id'] = $session_id;
        $_SESSION['login_time'] = time();
        
        // Store session in database for security
        $ip_address = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours (DATETIME)

        $stmt = $this->conn->prepare("
            INSERT INTO UserSessions (session_id, user_id, user_type, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        // session_id(s), user_id(i), user_type(s), ip_address(s), user_agent(s), expires_at(s)
        $stmt->bind_param("sissss", $session_id, $user_id, $user_type, $ip_address, $user_agent, $expires_at);
        $stmt->execute();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id'   => $_SESSION['user_id'],
            'user_type' => $_SESSION['user_type'],
            'full_name' => $_SESSION['full_name'],
            'email'     => $_SESSION['email']
        ];
    }
    
    /**
     * Check user role and permissions
     */
    public function hasRole($required_role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        $user_type = $_SESSION['user_type'];
        return $user_type === $required_role;
    }
    
    /**
     * Require specific role (redirect if not authorized)
     */
    public function requireRole($required_role, $redirect_url = 'login.php') {
        if (!$this->hasRole($required_role)) {
            header("Location: $redirect_url");
            exit();
        }
    }
    
    /**
     * Assign role to user
     */
    public function assignRole($user_id, $user_type, $role_name, $permissions = null) {
        try {
            $permissions_json = $permissions ? json_encode($permissions) : null;
            $stmt = $this->conn->prepare("
                INSERT INTO UserRoles (user_id, user_type, role_name, permissions)
                VALUES (?, ?, ?, ?)
            ");
            // user_id(i), user_type(s), role_name(s), permissions(s or null)
            $stmt->bind_param("isss", $user_id, $user_type, $role_name, $permissions_json);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Logout user and destroy session
     */
    public function logout() {
        if (isset($_SESSION['session_id'])) {
            // Remove session from database
            $stmt = $this->conn->prepare("DELETE FROM UserSessions WHERE session_id = ?");
            $stmt->bind_param("s", $_SESSION['session_id']);
            $stmt->execute();
        }
        // Destroy session
        session_destroy();
        session_start();
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions() {
        $this->conn->query("DELETE FROM UserSessions WHERE expires_at < NOW()");
    }
    
    /**
     * Validate session security
     */
    public function validateSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            SELECT expires_at 
            FROM UserSessions 
            WHERE session_id = ? AND user_id = ? AND user_type = ?
        ");
        $stmt->bind_param("sis", $_SESSION['session_id'], $_SESSION['user_id'], $_SESSION['user_type']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->logout();
            return false;
        }
        
        $session = $result->fetch_assoc();
        if (strtotime($session['expires_at']) < time()) {
            $this->logout();
            return false;
        }
        
        return true;
    }
}

// Initialize Auth instance
$auth = new Auth($conn);

// Clean expired sessions on each request
$auth->cleanExpiredSessions();

// Validate current session if logged in
if ($auth->isLoggedIn()) {
    $auth->validateSession();
}
