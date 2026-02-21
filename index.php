<?php
require_once __DIR__ . '/auth_middleware.php';

// Require authentication
$user = AuthMiddleware::requireAuth();

if ($user['role'] === 'admin') {
    header('Location: /admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body
    style="display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; background-color: #f3f4f6;">
    <div
        style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center;">
        <h2>Welcome,
            <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>!
        </h2>
        <p style="color: #6b7280; margin-top: 1rem;">You are successfully logged in.</p>
        <button onclick="logout()"
            style="margin-top: 1.5rem; padding: 0.5rem 1rem; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;">Logout</button>
    </div>

    <script>
        async function logout() {
            try {
                await fetch('/api/logout.php');
                localStorage.removeItem('user');
                window.location.href = '/login.php';
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = '/login.php';
            }
        }
    </script>
</body>

</html>