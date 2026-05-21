<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../common/db.php';

$db     = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true);
if (!is_array($data)) $data = [];

$id     = isset($_GET['id'])     ? (int) $_GET['id']     : null;
$action = isset($_GET['action']) ? (string) $_GET['action'] : null;


function getUsers($db) {
    $sql    = 'SELECT id, name, email, is_admin, created_at FROM users';
    $params = [];

    if (!empty($_GET['search'])) {
        $sql            .= ' WHERE name LIKE :search OR email LIKE :search';
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    $allowedSort = ['name', 'email', 'is_admin'];
    if (!empty($_GET['sort']) && in_array($_GET['sort'], $allowedSort, true)) {
        $order = (!empty($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';
        $sql  .= ' ORDER BY ' . $_GET['sort'] . ' ' . $order;
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse($rows, 200);
}


function getUserById($db, $id) {
    $stmt = $db->prepare('SELECT id, name, email, is_admin, created_at FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse('User not found', 404);
    }
    sendResponse($row, 200);
}


function createUser($db, $data) {
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse('Name, email and password are required', 400);
    }

    $name     = trim((string) $data['name']);
    $email    = trim((string) $data['email']);
    $password = (string) $data['password'];

    if (!validateEmail($email)) {
        sendResponse('Invalid email format', 400);
    }
    if (strlen($password) < 8) {
        sendResponse('Password must be at least 8 characters', 400);
    }

    $check = $db->prepare('SELECT id FROM users WHERE email = :email');
    $check->bindValue(':email', $email);
    $check->execute();
    if ($check->fetch()) {
        sendResponse('Email already exists', 409);
    }

    $isAdmin = isset($data['is_admin']) && (int) $data['is_admin'] === 1 ? 1 : 0;
    $hash    = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        'INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, :is_admin)'
    );
    $stmt->bindValue(':name',     $name);
    $stmt->bindValue(':email',    $email);
    $stmt->bindValue(':password', $hash);
    $stmt->bindValue(':is_admin', $isAdmin, PDO::PARAM_INT);

    if ($stmt->execute()) {
        sendResponse(['id' => (int) $db->lastInsertId()], 201);
    }
    sendResponse('Failed to create user', 500);
}


function updateUser($db, $data) {
    if (empty($data['id'])) {
        sendResponse('User id is required', 400);
    }
    $id = (int) $data['id'];

    $check = $db->prepare('SELECT id FROM users WHERE id = :id');
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) {
        sendResponse('User not found', 404);
    }

    $sets   = [];
    $params = [':id' => $id];

    if (isset($data['name'])) {
        $sets[]            = 'name = :name';
        $params[':name']   = trim((string) $data['name']);
    }
    if (isset($data['email'])) {
        $email = trim((string) $data['email']);
        if (!validateEmail($email)) {
            sendResponse('Invalid email format', 400);
        }
        $dup = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $dup->bindValue(':email', $email);
        $dup->bindValue(':id',    $id, PDO::PARAM_INT);
        $dup->execute();
        if ($dup->fetch()) {
            sendResponse('Email already used by another user', 409);
        }
        $sets[]           = 'email = :email';
        $params[':email'] = $email;
    }
    if (isset($data['is_admin'])) {
        $sets[]              = 'is_admin = :is_admin';
        $params[':is_admin'] = (int) $data['is_admin'] === 1 ? 1 : 0;
    }

    if (empty($sets)) {
        sendResponse('No fields to update', 200);
    }

    $sql  = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    if ($stmt->execute()) {
        sendResponse('User updated', 200);
    }
    sendResponse('Failed to update user', 500);
}


function deleteUser($db, $id) {
    if (empty($id)) {
        sendResponse('User id is required', 400);
    }

    $check = $db->prepare('SELECT id FROM users WHERE id = :id');
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) {
        sendResponse('User not found', 404);
    }

    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        sendResponse('User deleted', 200);
    }
    sendResponse('Failed to delete user', 500);
}


function changePassword($db, $data) {
    if (empty($data['id']) || !isset($data['current_password']) || !isset($data['new_password'])) {
        sendResponse('id, current_password and new_password are required', 400);
    }
    $id      = (int) $data['id'];
    $current = (string) $data['current_password'];
    $new     = (string) $data['new_password'];

    if (strlen($new) < 8) {
        sendResponse('New password must be at least 8 characters', 400);
    }

    $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse('User not found', 404);
    }
    if (!password_verify($current, $row['password'])) {
        sendResponse('Current password is incorrect', 401);
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $upd  = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
    $upd->bindValue(':password', $hash);
    $upd->bindValue(':id',       $id, PDO::PARAM_INT);
    if ($upd->execute()) {
        sendResponse('Password updated', 200);
    }
    sendResponse('Failed to update password', 500);
}


try {
    if ($method === 'GET') {
        if ($id) {
            getUserById($db, $id);
        } else {
            getUsers($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'change_password') {
            changePassword($db, $data);
        } else {
            createUser($db, $data);
        }
    } elseif ($method === 'PUT') {
        updateUser($db, $data);
    } elseif ($method === 'DELETE') {
        deleteUser($db, $id);
    } else {
        sendResponse('Method not allowed', 405);
    }
} catch (PDOException $e) {
    error_log('Admin API DB error: ' . $e->getMessage());
    sendResponse('Database error', 500);
} catch (Exception $e) {
    sendResponse($e->getMessage(), 500);
}


function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if ($statusCode < 400) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => $data]);
    }
    exit;
}


function validateEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}


function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim((string) $data)), ENT_QUOTES, 'UTF-8');
}
