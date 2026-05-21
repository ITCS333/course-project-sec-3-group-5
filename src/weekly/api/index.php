<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Weekly Course Breakdown API
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../common/db.php';

$db = getDBConnection();

$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents("php://input");

$data = json_decode($rawData, true);

if (!$data) {
    $data = [];
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$weekId = $_GET['week_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;



// ======================================================
// GET ALL WEEKS
// ======================================================

function getAllWeeks(PDO $db): void
{
    $stmt = $db->query("
        SELECT
            id,
            title,
            start_date,
            description,
            links
        FROM weeks
        ORDER BY start_date ASC
    ");

    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeks as &$week) {

        $week['links'] =
            json_decode($week['links'], true) ?? [];
    }

    sendResponse([
        'success' => true,
        'data' => $weeks
    ]);
}



// ======================================================
// GET WEEK BY ID
// ======================================================

function getWeekById(PDO $db, $id): void
{
    if (!$id || !is_numeric($id)) {

        sendResponse([
            'success' => false,
            'message' => 'Invalid ID'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT *
        FROM weeks
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($week) {

        $week['links'] =
            json_decode($week['links'], true) ?? [];

        sendResponse([
            'success' => true,
            'data' => $week
        ]);

    } else {

        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
    }
}



// ======================================================
// CREATE WEEK
// ======================================================

function createWeek(PDO $db, array $data): void
{
    if (
        empty($data['title']) ||
        empty($data['start_date'])
    ) {

        sendResponse([
            'success' => false,
            'message' => 'Missing fields'
        ], 400);
    }

    $title =
        sanitizeInput($data['title']);

    $start_date =
        sanitizeInput($data['start_date']);

    $description =
        sanitizeInput($data['description'] ?? '');

    $links =
        json_encode($data['links'] ?? []);

    $stmt = $db->prepare("
        INSERT INTO weeks
        (title, start_date, description, links)
        VALUES (?, ?, ?, ?)
    ");

    $success = $stmt->execute([
        $title,
        $start_date,
        $description,
        $links
    ]);

    if ($success) {

        sendResponse([
            'success' => true,
            'id' => $db->lastInsertId()
        ], 201);

    } else {

        sendResponse([
            'success' => false
        ], 500);
    }
}



// ======================================================
// UPDATE WEEK
// ======================================================

function updateWeek(PDO $db, array $data): void
{
    if (empty($data['id'])) {

        sendResponse([
            'success' => false,
            'message' => 'Missing ID'
        ], 400);
    }

    $stmt = $db->prepare("
        UPDATE weeks
        SET
            title = ?,
            start_date = ?,
            description = ?,
            links = ?
        WHERE id = ?
    ");

    $success = $stmt->execute([

        sanitizeInput($data['title']),
        sanitizeInput($data['start_date']),
        sanitizeInput($data['description']),
        json_encode($data['links'] ?? []),
        $data['id']

    ]);

    if ($success) {

        sendResponse([
            'success' => true
        ]);

    } else {

        sendResponse([
            'success' => false
        ], 500);
    }
}



// ======================================================
// DELETE WEEK
// ======================================================

function deleteWeek(PDO $db, $id): void
{
    $stmt = $db->prepare("
        DELETE FROM weeks
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    sendResponse([
        'success' => true
    ]);
}



// ======================================================
// GET COMMENTS
// ======================================================

function getCommentsByWeek(PDO $db, $weekId): void
{
    $stmt = $db->prepare("
        SELECT
            id,
            week_id,
            author,
            text,
            created_at
        FROM comments_week
        WHERE week_id = ?
        ORDER BY created_at ASC
    ");

    $stmt->execute([$weekId]);

    $comments =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $comments
    ]);
}



// ======================================================
// CREATE COMMENT
// ======================================================

function createComment(PDO $db, array $data): void
{
    $stmt = $db->prepare("
        INSERT INTO comments_week
        (week_id, author, text)
        VALUES (?, ?, ?)
    ");

    $success = $stmt->execute([

        $data['week_id'],
        sanitizeInput($data['author']),
        sanitizeInput($data['text'])

    ]);

    if ($success) {

        sendResponse([
            'success' => true,
            'data' => [
                'week_id' => $data['week_id'],
                'author' => $data['author'],
                'text' => $data['text']
            ]
        ], 201);

    } else {

        sendResponse([
            'success' => false
        ], 500);
    }
}



// ======================================================
// DELETE COMMENT
// ======================================================

function deleteComment(PDO $db, $commentId): void
{
    $stmt = $db->prepare("
        DELETE FROM comments_week
        WHERE id = ?
    ");

    $stmt->execute([$commentId]);

    sendResponse([
        'success' => true
    ]);
}



// ======================================================
// ROUTER
// ======================================================

try {

    if ($method === 'GET') {

        if ($action === 'comments') {

            getCommentsByWeek($db, $weekId);

        } elseif ($id) {

            getWeekById($db, $id);

        } else {

            getAllWeeks($db);
        }

    } elseif ($method === 'POST') {

        if ($action === 'comment') {

            createComment($db, $data);

        } else {

            createWeek($db, $data);
        }

    } elseif ($method === 'PUT') {

        updateWeek($db, $data);

    } elseif ($method === 'DELETE') {

        if ($action === 'delete_comment') {

            deleteComment($db, $commentId);

        } else {

            deleteWeek($db, $id);
        }

    }

} catch (Exception $e) {

    sendResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}



// ======================================================
// HELPERS
// ======================================================

function sendResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT
    );

    exit;
}

function sanitizeInput(string $data): string
{
    return htmlspecialchars(
        strip_tags(trim($data)),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
