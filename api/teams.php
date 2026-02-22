<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_middleware.php';

$user = AuthMiddleware::requireAuthAPI();

try {
    $stmt = $pdo->query("SELECT * FROM teams ORDER BY name ASC");
    $teams = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'teams' => $teams
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
