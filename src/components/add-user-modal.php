<?php
// Ensure $pdo is available (assuming included in a page with config.php)
// Fetch teams for the dropdown
if (!isset($teams)) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name ASC");
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $teams = [];
    }
}
?>

<!-- Add User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="btn-close-modal" onclick="closeCreateUserModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addUserForm" onsubmit="handleSaveUser(event)">
            <input type="hidden" name="userId" id="userId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullName" class="form-control" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="johndoe" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <input type="text" name="role" id="userRole" class="form-control" list="role-suggestions"
                        placeholder="e.g. user, admin" required>
                    <datalist id="role-suggestions">
                        <option value="user">
                        <option value="admin">
                        <option value="manager">
                    </datalist>
                </div>

                <div class="form-group">
                    <label class="form-label">Assign Team</label>
                    <div class="select-wrapper">
                        <select name="team_id" id="teamSelect" class="form-control">
                            <option value="">No Team</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo htmlspecialchars($team['id']); ?>">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down select-icon"></i>
                    </div>
                    <small class="form-text text-muted">Assigning a team will generate a unique ID like
                        "TeamName001".</small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="saveUserBtn">Create User</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* basic modal styles reusing existing CSS if available, or providing fallbacks */
    .select-wrapper {
        position: relative;
    }

    .select-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: var(--text-light);
    }

    .form-text {
        font-size: 0.85rem;
        color: var(--text-light);
        margin-top: 0.25rem;
    }
</style>

<script>
    function openUserModal(user = null, teamId = null, teamName = null) {
        const form = document.getElementById('addUserForm');
        form.reset();

        const modalTitle = document.querySelector('.modal-title');
        const submitBtn = document.getElementById('saveUserBtn');
        const teamSelect = document.getElementById('teamSelect');
        const roleInput = document.getElementById('userRole');
        const userIdInput = document.getElementById('userId');
        const passwordGroup = document.querySelector('input[name="password"]').closest('.form-group');

        // Helper to toggle visibility of form groups
        const toggleGroup = (element, show) => {
            const group = element.closest('.form-group');
            if (group) {
                group.style.display = show ? 'block' : 'none';
            }
        };

        if (user) {
            // EDIT MODE
            modalTitle.textContent = 'Edit User';
            submitBtn.textContent = 'Save Changes';
            userIdInput.value = user.id;

            // Populate fields
            form.elements['fullName'].value = user.full_name || '';
            form.elements['username'].value = user.username || '';
            form.elements['email'].value = user.email || '';
            form.elements['role'].value = user.role || '';
            form.elements['team_id'].value = user.team_id || '';

            // Password is optional in edit mode
            passwordGroup.style.display = 'none'; // Hide password field
            form.elements['password'].removeAttribute('required');

            // Handle Team/Role fields according to context
            if (teamId) {
                // If editing within team context, lock to that team
                teamSelect.value = teamId;
                toggleGroup(teamSelect, false);

                // If role was auto-set based on team name logic
                if (user.role && teamName && user.role === teamName.split(' ')[0]) {
                    toggleGroup(roleInput, false);
                } else {
                    toggleGroup(roleInput, true);
                }
            } else {
                toggleGroup(teamSelect, true);
                toggleGroup(roleInput, true);
            }

        } else {
            // CREATE MODE
            modalTitle.textContent = 'Add New User';
            submitBtn.textContent = 'Create User';
            userIdInput.value = '';

            passwordGroup.style.display = 'block';
            form.elements['password'].setAttribute('required', 'true');

            // Handle Team Pre-selection
            if (teamId) {
                teamSelect.value = teamId;
                teamSelect.setAttribute('readonly', 'true');
                toggleGroup(teamSelect, false);
                Array.from(teamSelect.options).forEach(opt => {
                    if (opt.value != teamId) opt.disabled = true;
                });
            } else {
                teamSelect.removeAttribute('readonly');
                Array.from(teamSelect.options).forEach(opt => opt.disabled = false);
                toggleGroup(teamSelect, true);
            }

            // Handle Role Pre-fill
            if (teamName) {
                const firstWord = teamName.split(' ')[0];
                roleInput.value = firstWord;
                roleInput.setAttribute('readonly', 'true');
                toggleGroup(roleInput, false);
            } else {
                roleInput.removeAttribute('readonly');
                toggleGroup(roleInput, true);
            }
        }

        document.getElementById('createUserModal').classList.add('active');
    }

    function closeCreateUserModal() {
        document.getElementById('createUserModal').classList.remove('active');
    }

    // Close on outside click
    document.getElementById('createUserModal').addEventListener('click', function (e) {
        if (e.target === this) closeCreateUserModal();
    });

    async function handleSaveUser(e) {
        e.preventDefault();

        const form = e.target;
        const btn = document.getElementById('saveUserBtn');
        const originalText = btn.textContent;
        const userId = document.getElementById('userId').value;
        const isEdit = !!userId;

        // Collect data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Add ID if editing
        if (isEdit) data.id = userId; // Use 'id' for consistency with API expectations for PUT

        try {
            btn.textContent = isEdit ? 'Saving...' : 'Creating...';
            btn.disabled = true;

            const response = await fetch('/api/users.php', {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                if (typeof Toast !== 'undefined') {
                    Toast.success('Success', result.message);
                }

                closeCreateUserModal();
                if (typeof loadUsers === 'function') {
                    setTimeout(loadUsers, 1000); // Wait a bit for toast
                } else {
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                throw new Error(result.message || 'Operation failed');
            }
        } catch (error) {
            if (typeof Toast !== 'undefined') {
                Toast.error('Error', error.message);
            } else {
                console.error('Error:', error.message);
            }
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }
</script>