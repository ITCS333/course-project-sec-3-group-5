<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "../../common/db.php";

$method = $_SERVER["REQUEST_METHOD"];
$data = json_decode(file_get_contents("php://input"), true) ?? [];

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function clean($value) {
    return htmlspecialchars(strip_tags(trim($value)));
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

try {
    global $pdo;

    if ($method === "GET") {
        if (isset($_GET["id"])) {
            $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = ?");
            $stmt->execute([$_GET["id"]]);
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resource) {
                sendResponse(["success" => false, "message" => "Resource not found"], 404);
            }

            sendResponse(["success" => true, "data" => $resource]);
        }

        if (isset($_GET["action"]) && $_GET["action"] === "comments") {
            $resourceId = $_GET["resource_id"] ?? null;

            if (!$resourceId || !is_numeric($resourceId)) {
                sendResponse(["success" => false, "message" => "Invalid resource ID"], 400);
            }

            $stmt = $pdo->prepare("SELECT * FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC");
            $stmt->execute([$resourceId]);
            sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        $search = $_GET["search"] ?? "";
        $sort = $_GET["sort"] ?? "created_at";
        $order = strtolower($_GET["order"] ?? "desc");

        $allowedSort = ["title", "created_at"];
        $allowedOrder = ["asc", "desc"];

        if (!in_array($sort, $allowedSort)) $sort = "created_at";
        if (!in_array($order, $allowedOrder)) $order = "desc";

        if ($search !== "") {
            $stmt = $pdo->prepare("SELECT * FROM resources 
                                   WHERE title LIKE ? OR description LIKE ?
                                   ORDER BY $sort $order");
            $stmt->execute(["%$search%", "%$search%"]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM resources ORDER BY $sort $order");
            $stmt->execute();
        }

        sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === "POST") {
        if (isset($_GET["action"]) && $_GET["action"] === "comment") {
            $resourceId = $data["resource_id"] ?? null;
            $author = clean($data["author"] ?? "");
            $text = clean($data["text"] ?? "");

            if (!$resourceId || !is_numeric($resourceId) || $author === "" || $text === "") {
                sendResponse(["success" => false, "message" => "Missing comment fields"], 400);
            }

            $check = $pdo->prepare("SELECT id FROM resources WHERE id = ?");
            $check->execute([$resourceId]);

            if (!$check->fetch()) {
                sendResponse(["success" => false, "message" => "Resource not found"], 404);
            }

            $stmt = $pdo->prepare("INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)");
            $stmt->execute([$resourceId, $author, $text]);

            sendResponse(["success" => true, "message" => "Comment added", "id" => $pdo->lastInsertId()], 201);
        }

        $title = clean($data["title"] ?? "");
        $description = clean($data["description"] ?? "");
        $link = trim($data["link"] ?? "");

        if ($title === "" || $link === "") {
            sendResponse(["success" => false, "message" => "Title and link are required"], 400);
        }

        if (!validateUrl($link)) {
            sendResponse(["success" => false, "message" => "Invalid URL"], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $link]);

        sendResponse(["success" => true, "message" => "Resource created", "id" => $pdo->lastInsertId()], 201);
    }

    if ($method === "PUT") {
        $id = $data["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            sendResponse(["success" => false, "message" => "Invalid resource ID"], 400);
        }

        $check = $pdo->prepare("SELECT id FROM resources WHERE id = ?");
        $check->execute([$id]);

        if (!$check->fetch()) {
            sendResponse(["success" => false, "message" => "Resource not found"], 404);
        }

        $title = clean($data["title"] ?? "");
        $description = clean($data["description"] ?? "");
        $link = trim($data["link"] ?? "");

        if ($title === "" || $link === "") {
            sendResponse(["success" => false, "message" => "Title and link are required"], 400);
        }

        if (!validateUrl($link)) {
            sendResponse(["success" => false, "message" => "Invalid URL"], 400);
        }

        $stmt = $pdo->prepare("UPDATE resources SET title = ?, description = ?, link = ? WHERE id = ?");
        $stmt->execute([$title, $description, $link, $id]);

        sendResponse(["success" => true, "message" => "Resource updated"]);
    }

    if ($method === "DELETE") {
        if (isset($_GET["action"]) && $_GET["action"] === "delete_comment") {
            $commentId = $_GET["comment_id"] ?? null;

            if (!$commentId || !is_numeric($commentId)) {
                sendResponse(["success" => false, "message" => "Invalid comment ID"], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM comments_resource WHERE id = ?");
            $stmt->execute([$commentId]);

            sendResponse(["success" => true, "message" => "Comment deleted"]);
        }

        $id = $_GET["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            sendResponse(["success" => false, "message" => "Invalid resource ID"], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
        $stmt->execute([$id]);

        sendResponse(["success" => true, "message" => "Resource deleted"]);
    }

    sendResponse(["success" => false, "message" => "Method not allowed"], 405);

} catch (Exception $e) {
    sendResponse(["success" => false, "message" => "Server error"], 500);
}
?>
