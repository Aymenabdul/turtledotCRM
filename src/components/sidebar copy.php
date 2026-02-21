<?php
/**
 * Admin Sidebar Component
 * Modern, premium sidebar navigation for admin users
 * 
 * @param array $user - Current logged-in user data
 */

function renderSidebar($user = null)
{
    if (!$user) {
        return;
    }

    // Get current page for active state
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>
    <!-- Admin Sidebar CSS -->
    <link rel="stylesheet" href="/src/css/admin/sidebar.css">

    <!-- Admin Sidebar -->
    <aside class="sidebar" id="sidebar">
        <script>
            // Apply collapsed state immediately to prevent flash
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.getElementById('sidebar').classList.add('collapsed');
            }
        </script>
        <!-- Sidebar Header with Gradient Background -->
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-logo-wrapper">
                    <img src="/assets/images/turtle_logo.png" alt="Turtle Dot" class="sidebar-logo">
                </div>
                <div class="sidebar-brand-text">
                    <img src="/assets/images/textlogo.png" alt="Turtle Dot" class="sidebar-text-logo">
                </div>
            </div>
        </div>

        <button class="sidebar-collapse-btn" onclick="toggleSidebar()" title="Toggle Sidebar">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <!-- Sidebar Navigation -->
        <nav class="sidebar-nav">
            <!-- Main Section -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>

                <a href="/index.php" class="nav-item <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                    <div class="nav-item-icon">
                        <i class="fa-solid fa-house"></i>
                    </div>
                    <span class="nav-item-text">Dashboard</span>
                    <div class="nav-item-indicator"></div>
                </a>

                <a href="/manage-teams.php"
                    class="nav-item <?php echo $currentPage == 'manage-teams.php' ? 'active' : ''; ?>">
                    <div class="nav-item-icon">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <span class="nav-item-text">Manage Teams</span>
                    <div class="nav-item-indicator"></div>
                </a>
            </div>

            <!-- Teams Section -->
            <?php
            // Fetch teams for sidebar
            global $pdo;
            $sidebarTeams = [];
            if (isset($pdo)) {
                try {
                    $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name ASC");
                    $sidebarTeams = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // Silent fail for sidebar
                }
            }
            ?>

            <?php if (!empty($sidebarTeams)): ?>
                <div class="nav-section">
                    <span class="nav-section-title">Teams</span>
                    <?php foreach ($sidebarTeams as $team): ?>
                        <a href="/team-dashboard.php?id=<?php echo $team['id']; ?>"
                            class="nav-item <?php echo ($currentPage == 'team-dashboard.php' && isset($_GET['id']) && $_GET['id'] == $team['id']) ? 'active' : ''; ?>">
                            <div class="nav-item-icon">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <span class="nav-item-text"><?php echo htmlspecialchars($team['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <!-- User Profile Card -->
            <div class="sidebar-user-card">
                <div class="sidebar-user-avatar">
                    <span class="avatar-text"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></span>
                    <div class="avatar-status"></div>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div class="sidebar-user-role">
                        <i class="fa-solid fa-shield-halved"></i>
                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                    </div>
                </div>
                <button class="user-menu-btn" onclick="toggleUserMenu(event)">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
            </div>

            <!-- Logout Button -->
            <button onclick="logout()" class="sidebar-logout-btn">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span class="logout-text">Logout</span>
            </button>
        </div>
    </aside>



    <script>
        function toggleUserMenu(event) {
            event.stopPropagation();
            // Add user menu functionality here
            console.log('User menu clicked');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');

            // Save state preference
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
    </script>
    <?php
}
?>