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
            SELECT l.*, u.full_name as assigned_to_name 
            FROM leads l
            LEFT JOIN users u ON l.assigned_to = u.id
            WHERE l.team_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([$teamId]);
        $leads = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $leads]);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['team_id']) || !isset($data['full_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO leads (team_id, full_name, email, phone, status, source, assigned_to, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['team_id'],
            $data['full_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['status'] ?? 'new',
            $data['source'] ?? null,
            !empty($data['assigned_to']) ? $data['assigned_to'] : null,
            $data['notes'] ?? null
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
        foreach (['full_name', 'email', 'phone', 'status', 'source', 'assigned_to', 'notes'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = ($field === 'assigned_to' && empty($data[$field])) ? null : $data[$field];
            }
        }

        if (empty($fields)) {
            echo json_encode(['success' => true, 'message' => 'No changes']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE leads SET " . implode(', ', $fields) . " WHERE id = ?");
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

        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
