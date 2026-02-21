<?php
/**
 * Create Default Admin User
 * Run this script to create an admin account for first-time login
 */

require_once __DIR__ . '/config.php';

echo "=== Create Admin User ===\n\n";

// Default admin credentials
$username = 'admin';
$email = 'admin@leadmaintenance.com';
$password = 'admin123'; // Change this after first login!
$full_name = 'System Administrator';
$role = 'admin';

try {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        echo "Creating users table...\n";
        $userTableSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255),
            role VARCHAR(50) DEFAULT 'user',
            is_active BOOLEAN DEFAULT TRUE,
            two_fa_secret VARCHAR(32) NULL,
            two_fa_enabled BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($userTableSQL);
        echo "✓ Users table created\n\n";
    }

    // Check if admin already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $checkStmt->execute(['username' => $username, 'email' => $email]);

    if ($checkStmt->fetch()) {
        echo "⚠ Admin user already exists!\n";
        echo "\nExisting credentials:\n";
        echo "Username: $username\n";
        echo "Email: $email\n";
        exit(0);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert admin user
    $insertStmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, is_active)
        VALUES (:username, :email, :password, :full_name, :role, 1)
    ");

    $insertStmt->execute([
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
        'full_name' => $full_name,
        'role' => $role
    ]);

    echo "✓ Admin user created successfully!\n\n";
    echo "Login credentials:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "⚠ IMPORTANT: Change the default password after first login!\n\n";
    echo "You can now login at: http://localhost:8000/login.php\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>