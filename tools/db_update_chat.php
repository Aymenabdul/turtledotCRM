<?php
require_once __DIR__ . '/../config.php';

try {
    // Check if 'channel' column exists in chat_messages table
    $stmt = $pdo->query("SHOW COLUMNS FROM chat_messages LIKE 'channel'");
    $result = $stmt->fetch();

    if (!$result) {
        // Add column if it doesn't exist
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN channel VARCHAR(50) DEFAULT 'general' AFTER team_id");
        echo "Column 'channel' added successfully.";
    } else {
        echo "Column 'channel' already exists.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>