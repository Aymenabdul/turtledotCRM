<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS channel_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT NOT NULL,
        user_id INT NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_member (channel_id, user_id),
        FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    echo "channel_members table created successfully.\n";

    // Auto-add all team members to existing channels
    $channels = $pdo->query("SELECT c.id AS channel_id, c.team_id FROM channels c")->fetchAll();
    foreach ($channels as $ch) {
        $users = $pdo->prepare("SELECT id FROM users WHERE team_id = ?");
        $users->execute([$ch['team_id']]);
        foreach ($users->fetchAll() as $u) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO channel_members (channel_id, user_id) VALUES (?, ?)");
            $stmt->execute([$ch['channel_id'], $u['id']]);
        }
    }

    echo "Existing team members added to their channels.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>