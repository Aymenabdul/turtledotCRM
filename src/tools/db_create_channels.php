<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_channel (team_id, name)
    )");

    // Add default 'General' channel for existing teams
    $teams = $pdo->query("SELECT id FROM teams")->fetchAll();
    foreach ($teams as $team) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO channels (team_id, name) VALUES (?, 'General')");
        $stmt->execute([$team['id']]);
    }

    echo "Channels table created and initialized.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>