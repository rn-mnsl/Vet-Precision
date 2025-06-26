<?php
// This file will handle all database requests for patients (clients).

// Make sure the path to your init file is correct.
// It should establish a database connection, typically into a $pdo variable.
require_once '../../../config/init.php';

// Set the content type to JSON for all responses
header('Content-Type: application/json');

// Get the requested action, or default to null if not set
$action = $_REQUEST['action'] ?? null;

try {
    // Route the request to the correct function based on the 'action' parameter
    switch ($action) {
        case 'fetch':
            fetch_clients($pdo);
            break;
        case 'create':
            create_client($pdo);
            break;
        case 'update':
            update_client($pdo);
            break;
        case 'delete':
            // We will perform a "soft delete" by deactivating the user
            delete_client($pdo);
            break;
        default:
            // If the action is invalid, return an error
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (PDOException $e) {
    // If a database error occurs, return a server error status and message
    http_response_code(500);
    // In a production environment, you should log this error instead of showing it to the user
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Fetches all clients from the database by joining users and owners tables.
 */
function fetch_clients($pdo) {
    // Get parameters from the request, with defaults
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $offset = ($page - 1) * $limit;

    // Base query
    $baseQuery = "
        FROM users u
        LEFT JOIN owners o ON u.user_id = o.user_id
        WHERE u.role = 'client'
    ";

    // Search condition
    $searchWhere = "";
    $params = [];
    if (!empty($search)) {
        $searchWhere = "AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ? OR o.city LIKE ? OR o.phone LIKE ?)";
        $searchTerm = "%{$search}%";
        // We need one search term for each 'LIKE' clause
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }
    
    // 1. Get the total number of records that match the search
    $countStmt = $pdo->prepare("SELECT COUNT(u.user_id) " . $baseQuery . $searchWhere);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();

    // 2. Get the paginated data
    $dataQuery = "
        SELECT 
            u.user_id, u.first_name, u.last_name, u.email, u.is_active, 
            u.created_at AS user_created_at, o.owner_id, o.phone, o.address, o.city
        " . $baseQuery . $searchWhere . "
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Add limit and offset to parameters, ensuring they are treated as integers
    $dataParams = array_merge($params, [$limit, $offset]);
    
    $stmt = $pdo->prepare($dataQuery);

    // Bind parameters one by one to ensure correct types (string for search, int for limit/offset)
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $dataParams[$i], PDO::PARAM_STR);
    }
    if (!empty($params)) {
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return a structured response with data and pagination info
    echo json_encode([
        'success' => true, 
        'data' => $clients,
        'totalRecords' => (int)$totalRecords // Total matching records for pagination
    ]);
}

/**
 * Creates a new client by inserting into both users and owners tables.
 */
function create_client($pdo) {
    // In a real application, you must validate and sanitize all inputs!
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    
    // WARNING: For demonstration only. In production, generate a secure, random password
    // and implement a proper user registration flow (e.g., email verification).
    $password = password_hash('password123', PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    // 1. Create the user record
    $stmtUser = $pdo->prepare(
        "INSERT INTO users (email, password, role, first_name, last_name, is_active) 
         VALUES (?, ?, 'client', ?, ?, 1)"
    );
    $stmtUser->execute([$email, $password, $firstName, $lastName]);
    $userId = $pdo->lastInsertId();

    // 2. Create the owner record linked to the new user
    $stmtOwner = $pdo->prepare(
        "INSERT INTO owners (user_id, phone, address, city) VALUES (?, ?, ?, ?)"
    );
    $stmtOwner->execute([$userId, $phone, $address, $city]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Client created successfully.']);
}

/**
 * Updates an existing client's information in the users and owners tables.
 */
function update_client($pdo) {
    $userId = $_POST['user_id'] ?? null;
    if (!$userId) {
        throw new Exception("User ID is required for update.");
    }
    
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $isActive = $_POST['is_active'] ?? 1;

    $pdo->beginTransaction();

    // 1. Update the user's details
    $stmtUser = $pdo->prepare(
        "UPDATE users SET first_name = ?, last_name = ?, email = ?, is_active = ? WHERE user_id = ?"
    );
    $stmtUser->execute([$firstName, $lastName, $email, $isActive, $userId]);
    
    // 2. Update the owner's details. Using INSERT...ON DUPLICATE KEY UPDATE is robust.
    // This requires `user_id` to be a UNIQUE key in the `owners` table.
    $stmtOwner = $pdo->prepare(
        "INSERT INTO owners (user_id, phone, address, city) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE phone = VALUES(phone), address = VALUES(address), city = VALUES(city)"
    );
    $stmtOwner->execute([$userId, $phone, $address, $city]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Client updated successfully.']);
}

/**
 * Soft-deletes a client by setting their 'is_active' flag to 0.
 */
function delete_client($pdo) {
    $userId = $_POST['user_id'] ?? null;
    if (!$userId) {
        throw new Exception("User ID is required for deletion.");
    }

    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Client has been deactivated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Client not found or already inactive.']);
    }
}

?>