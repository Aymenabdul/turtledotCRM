<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_middleware.php';

$user = AuthMiddleware::requireAuthAPI();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $teamId = $_GET['team_id'] ?? null;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = $_GET['q'] ?? '';

        if (!$teamId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Team ID required']);
            exit;
        }

        // Search condition
        $where = "WHERE team_id = :team_id";
        $params = [':team_id' => $teamId];
        if ($search) {
            $where .= " AND title LIKE :search";
            $params[':search'] = "%$search%";
        }

        // Count total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM word_documents $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Fetch docs with author names
        $stmt = $pdo->prepare("
            SELECT d.*, 
                   u.full_name as author_name,
                   u2.full_name as updated_by_name,
                   u3.full_name as assigned_by_name
            FROM word_documents d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN users u2 ON d.updated_by = u2.id
            LEFT JOIN users u3 ON d.assigned_by = u3.id
            $where
            ORDER BY d.updated_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $docs = $stmt->fetchAll();

        // Format docs (handle assigned_to JSON)
        foreach ($docs as &$doc) {
            $doc['assigned_users'] = json_decode($doc['assigned_to'] ?? '[]', true);
        }

        echo json_encode([
            'success' => true,
            'data' => $docs,
            'pagination' => [
                'current' => $page,
                'pages' => ceil($total / $limit),
                'total' => $total
            ]
        ]);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['team_id']) || !isset($data['title'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO word_documents (team_id, title, content, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['team_id'],
            $data['title'],
            $data['content'] ?? '',
            $user['user_id'],
            $user['user_id']
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

    } elseif ($method === 'PATCH') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $_GET['id'] ?? $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        $fields = [];
        $params = [];
        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $params[] = $data['title'];
        }
        if (isset($data['content'])) {
            $fields[] = "content = ?";
            $params[] = $data['content'];
        }
        if (isset($data['assigned_to'])) {
            $fields[] = "assigned_to = ?";
            $params[] = json_encode($data['assigned_to']);
        }
        if (isset($data['assigned_by'])) {
            $fields[] = "assigned_by = ?";
            $params[] = $data['assigned_by'];
        }

        $fields[] = "updated_by = ?";
        $params[] = $user['user_id'];
        $fields[] = "updated_at = NOW()";

        $stmt = $pdo->prepare("UPDATE word_documents SET " . implode(', ', $fields) . " WHERE id = ?");
        $params[] = $id;
        $stmt->execute($params);

        echo json_encode(['success' => true]);

    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM word_documents WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
