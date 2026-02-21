<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_middleware.php';

$user = AuthMiddleware::requireAuthAPI();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $teamId = $_GET['team_id'] ?? null;
        if (!$teamId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Team ID required']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT t.*, u.full_name as assigned_to_name, c.full_name as creator_name 
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN users c ON t.created_by = c.id
            WHERE t.team_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$teamId]);
        $tasks = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $tasks]);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['team_id']) || !isset($data['title'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (team_id, title, description, priority, status, due_date, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['team_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'todo',
            !empty($data['due_date']) ? $data['due_date'] : null,
            !empty($data['assigned_to']) ? $data['assigned_to'] : null,
            $user['user_id']
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

    } elseif ($method === 'PATCH') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        $fields = [];
        $params = [];
        foreach (['title', 'description', 'priority', 'status', 'due_date', 'assigned_to'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = ($field === 'due_date' || $field === 'assigned_to' ? (empty($data[$field]) ? null : $data[$field]) : $data[$field]);
            }
        }

        if (empty($fields)) {
            echo json_encode(['success' => true, 'message' => 'No changes']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ?");
        $params[] = $id;
        $stmt->execute($params);

        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
