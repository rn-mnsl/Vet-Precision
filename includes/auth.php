<?php
// Authentication functions

// Login user
function login($email, $password, $role = null) {
    global $pdo;
    
    // Get user by email and optional role
    $query = "
        SELECT u.*, o.owner_id
        FROM users u
        LEFT JOIN owners o ON u.user_id = o.user_id
        WHERE u.email = :email AND u.is_active = 1";

    if ($role !== null) {
        $query .= " AND u.role = :role";
    }

    $stmt = $pdo->prepare($query);
    $params = ['email' => $email];
    if ($role !== null) {
        $params['role'] = $role;
    }
    $stmt->execute($params);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['owner_id'] = $user['owner_id'];
    $_SESSION['last_activity'] = time();
    
    return ['success' => true];
}

// Register new user
function register($data) {
    global $pdo;
    
    // Validate data
    $errors = validateRegistration($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check if email exists
    if (emailExists($data['email'])) {
        return ['success' => false, 'errors' => ['email' => 'Email already registered']];
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, role, first_name, last_name)
            VALUES (:email, :password, 'client', :first_name, :last_name)
        ");
        
        $stmt->execute([
            'email' => $data['email'],
            'password' => $hashedPassword,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Insert owner record
        $stmt = $pdo->prepare("
            INSERT INTO owners (user_id, phone, address, city)
            VALUES (:user_id, :phone, :address, :city)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null
        ]);
        
        $pdo->commit();
        
        return ['success' => true, 'user_id' => $userId];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

// Logout user
function logout() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Check if current user is staff
function isStaff() {
    return isLoggedIn() && $_SESSION['role'] === 'staff';
}

// Check if current user is client
function isClient() {
    return isLoggedIn() && $_SESSION['role'] === 'client';
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, o.*
        FROM users u
        LEFT JOIN owners o ON u.user_id = o.user_id
        WHERE u.user_id = :user_id
    ");
    $stmt->execute(['user_id' => getCurrentUserId()]);
    
    return $stmt->fetch();
}

// Update user password
function updatePassword($userId, $currentPassword, $newPassword) {
    global $pdo;
    
    // Get user
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Current password is incorrect'];
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
    $stmt->execute([
        'password' => $hashedPassword,
        'user_id' => $userId
    ]);
    
    return ['success' => true];
}

