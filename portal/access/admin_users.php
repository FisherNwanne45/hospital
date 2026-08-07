<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/auth.php';

pih_admin_require_super_admin();

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
$message = '';
$error = '';

if (!($db instanceof mysqli)) {
    $error = 'Database connection is unavailable.';
} else {
    pih_admin_ensure_login_schema($db);
}

function pih_normalize_is_super_admin(string $value): int
{
    return ($value === 'super_admin' || $value === '1') ? 1 : 0;
}

function pih_normalize_status(string $status): string
{
    return $status === 'inactive' ? 'inactive' : 'active';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

        if ($action === 'create') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $isSuperAdmin = pih_normalize_is_super_admin((string) ($_POST['role'] ?? 'admin'));
            $status = pih_normalize_status((string) ($_POST['status'] ?? 'active'));

            if ($username === '' || $password === '') {
                $error = 'Username and password are required to create an admin account.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                $exists = $db->prepare('SELECT id FROM login WHERE username = ? LIMIT 1');
                if ($exists) {
                    $exists->bind_param('s', $username);
                    $exists->execute();
                    $dup = $exists->get_result();
                    $alreadyUsed = $dup && $dup->num_rows > 0;
                    $exists->close();

                    if ($alreadyUsed) {
                        $error = 'That username already exists.';
                    }
                }

                if ($error === '') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $db->prepare('INSERT INTO login (username, password, is_super_admin, status) VALUES (?, ?, ?, ?)');
                    if ($insert) {
                        $insert->bind_param('ssis', $username, $passwordHash, $isSuperAdmin, $status);
                        if ($insert->execute()) {
                            pih_admin_set_flash('success', 'Admin account created successfully.');
                            $insert->close();
                            header('Location: admin_users.php');
                            exit();
                        }
                        $insert->close();
                    }

                    $error = 'Unable to create admin account.';
                }
            }
        }

        if ($action === 'update') {
            $targetId = (int) ($_POST['id'] ?? 0);
            $username = trim((string) ($_POST['username'] ?? ''));
            $isSuperAdmin = pih_normalize_is_super_admin((string) ($_POST['role'] ?? 'admin'));
            $status = pih_normalize_status((string) ($_POST['status'] ?? 'active'));
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $selfId = (int) ($_SESSION['login_user_id'] ?? 0);

            if ($targetId <= 0 || $username === '') {
                $error = 'Invalid admin account update request.';
            } else {
                $targetUser = pih_admin_fetch_user_by_id($db, $targetId);
                if (!$targetUser) {
                    $error = 'Admin account was not found.';
                }
            }

            if ($error === '' && isset($targetUser) && is_array($targetUser)) {
                if (pih_admin_user_is_super($targetUser) && ($isSuperAdmin !== 1 || $status !== 'active')) {
                    $superCount = pih_admin_count_super_admins($db);
                    if ($superCount <= 1) {
                        $error = 'At least one active super admin must always remain.';
                    }
                }

                if ($selfId === $targetId && $isSuperAdmin !== 1) {
                    $error = 'You cannot remove your own super admin role.';
                }
            }

            if ($error === '') {
                $exists = $db->prepare('SELECT id FROM login WHERE username = ? AND id <> ? LIMIT 1');
                if ($exists) {
                    $exists->bind_param('si', $username, $targetId);
                    $exists->execute();
                    $dup = $exists->get_result();
                    $alreadyUsed = $dup && $dup->num_rows > 0;
                    $exists->close();
                    if ($alreadyUsed) {
                        $error = 'That username is already in use by another account.';
                    }
                }
            }

            if ($error === '') {
                if ($newPassword !== '' && strlen($newPassword) < 8) {
                    $error = 'If provided, the new password must be at least 8 characters long.';
                } else {
                    if ($newPassword !== '') {
                        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $update = $db->prepare('UPDATE login SET username = ?, is_super_admin = ?, status = ?, password = ? WHERE id = ?');
                        if ($update) {
                            $update->bind_param('sissi', $username, $isSuperAdmin, $status, $newHash, $targetId);
                            $ok = $update->execute();
                            $update->close();
                            if ($ok) {
                                pih_admin_set_flash('success', 'Admin account updated successfully.');
                                header('Location: admin_users.php');
                                exit();
                            }
                        }
                    } else {
                        $update = $db->prepare('UPDATE login SET username = ?, is_super_admin = ?, status = ? WHERE id = ?');
                        if ($update) {
                            $update->bind_param('sisi', $username, $isSuperAdmin, $status, $targetId);
                            $ok = $update->execute();
                            $update->close();
                            if ($ok) {
                                pih_admin_set_flash('success', 'Admin account updated successfully.');
                                header('Location: admin_users.php');
                                exit();
                            }
                        }
                    }

                    $error = 'Unable to update admin account.';
                }
            }
        }

        if ($action === 'delete') {
            $targetId = (int) ($_POST['id'] ?? 0);
            $selfId = (int) ($_SESSION['login_user_id'] ?? 0);

            if ($targetId <= 0) {
                $error = 'Invalid account delete request.';
            } elseif ($targetId === $selfId) {
                $error = 'You cannot delete your own logged-in account.';
            } else {
                $targetUser = pih_admin_fetch_user_by_id($db, $targetId);
                if (!$targetUser) {
                    $error = 'Admin account was not found.';
                }
            }

            if ($error === '' && isset($targetUser) && is_array($targetUser)) {
                if (pih_admin_user_is_super($targetUser)) {
                    $superCount = pih_admin_count_super_admins($db);
                    if ($superCount <= 1) {
                        $error = 'At least one active super admin must always remain.';
                    }
                }
            }

            if ($error === '') {
                $delete = $db->prepare('DELETE FROM login WHERE id = ?');
                if ($delete) {
                    $delete->bind_param('i', $targetId);
                    if ($delete->execute()) {
                        pih_admin_set_flash('success', 'Admin account deleted successfully.');
                        $delete->close();
                        header('Location: admin_users.php');
                        exit();
                    }
                    $delete->close();
                }

                $error = 'Unable to delete admin account.';
            }
        }
    }
}

$admins = [];
if ($db instanceof mysqli) {
    $list = $db->query("SELECT id, username, is_super_admin, CASE WHEN is_super_admin = 1 THEN 'super_admin' ELSE 'admin' END AS role, status, last_login, updated_at FROM login ORDER BY is_super_admin DESC, id ASC");
    if ($list) {
        while ($row = $list->fetch_assoc()) {
            $admins[] = $row;
        }
        $list->free();
    }
}

$hasImpersonableAdmin = false;
foreach ($admins as $adminRow) {
    if (!pih_admin_user_is_super($adminRow) && (string) ($adminRow['status'] ?? '') === 'active') {
        $hasImpersonableAdmin = true;
        break;
    }
}

pih_admin_render_start(
    'Admin Users',
    'Create, update, delete, and impersonate admin accounts',
    'admin_users',
    [
        ['href' => 'theme_settings.php', 'icon' => 'icon-paint-brush', 'label' => 'Theme Settings'],
        ['href' => 'smtp_settings.php', 'icon' => 'icon-envelope', 'label' => 'SMTP Settings'],
    ]
);
?>
<style>
    .admin-users-table th,
    .admin-users-table td {
        vertical-align: top;
    }

    .admin-users-table td {
        min-width: 120px;
    }

    .admin-users-table .admin-users-field {
        width: 100%;
        box-sizing: border-box;
        margin: 0;
    }

    .admin-users-table .admin-users-password {
        min-width: 200px;
    }

    .admin-users-table .admin-users-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .admin-users-table .admin-users-actions form {
        margin: 0;
    }
</style>

<div class="module">
    <div class="module-head">
        <h3>Create Admin Account</h3>
    </div>
    <div class="module-body">
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form class="form-horizontal row-fluid" method="post" action="">
            <?php echo pih_admin_csrf_input(); ?>
            <input type="hidden" name="action" value="create">

            <div class="control-group">
                <label class="control-label" for="username">Username</label>
                <div class="controls"><input class="span6" id="username" type="text" name="username" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="password">Password</label>
                <div class="controls"><input class="span6" id="password" type="password" name="password" minlength="8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="role">Role</label>
                <div class="controls">
                    <select class="span4" id="role" name="role">
                        <option value="admin" selected>Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="status">Status</label>
                <div class="controls">
                    <select class="span4" id="status" name="status">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-success">Create Admin</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="module" style="margin-top: 20px;">
    <div class="module-head">
        <h3>Manage Admin Accounts</h3>
    </div>
    <div class="module-body table">
        <?php if (!$hasImpersonableAdmin) { ?>
            <div class="alert alert-info">No active regular admin account is currently available for Login as Admin. Create one or switch an existing account role to Admin and set status to Active.</div>
        <?php } ?>

        <table class="table table-bordered table-striped datatable-1 admin-users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin) { ?>
                    <?php $updateFormId = 'update-admin-' . (int) $admin['id']; ?>
                    <tr>
                        <td><?php echo (int) $admin['id']; ?></td>
                        <td>
                            <input type="text" name="username" form="<?php echo htmlspecialchars($updateFormId, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $admin['username'], ENT_QUOTES, 'UTF-8'); ?>" class="span12 admin-users-field">
                        </td>
                        <td>
                                <select name="role" form="<?php echo htmlspecialchars($updateFormId, ENT_QUOTES, 'UTF-8'); ?>" class="span12 admin-users-field">
                                    <option value="admin" <?php echo ((string) $admin['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="super_admin" <?php echo ((string) $admin['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                                </select>
                        </td>
                        <td>
                                <select name="status" form="<?php echo htmlspecialchars($updateFormId, ENT_QUOTES, 'UTF-8'); ?>" class="span12 admin-users-field">
                                    <option value="active" <?php echo ((string) $admin['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ((string) $admin['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($admin['last_login'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                                <form id="<?php echo htmlspecialchars($updateFormId, ENT_QUOTES, 'UTF-8'); ?>" method="post" action="">
                                    <?php echo pih_admin_csrf_input(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo (int) $admin['id']; ?>">
                                    <input type="password" name="new_password" class="span12 admin-users-field admin-users-password" placeholder="New password (optional)">
                                    <div class="admin-users-actions" style="margin-top:8px;">
                                        <button type="submit" class="btn btn-mini btn-info">Update</button>
                                    </div>
                            </form>

                            <div class="admin-users-actions" style="margin-top:8px;">
                            <?php if (!pih_admin_user_is_super($admin) && (string) $admin['status'] === 'active') { ?>
                                <form method="post" action="impersonate.php" style="margin:0; display:inline;">
                                    <?php echo pih_admin_csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $admin['id']; ?>">
                                    <button type="submit" class="btn btn-mini btn-warning">Login as This Admin</button>
                                </form>
                            <?php } ?>

                            <?php if ((int) $admin['id'] !== (int) ($_SESSION['login_user_id'] ?? 0)) { ?>
                                <form method="post" action="" style="margin:0; display:inline;" onsubmit="return confirm('Delete this admin account?');">
                                    <?php echo pih_admin_csrf_input(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $admin['id']; ?>">
                                    <button type="submit" class="btn btn-mini btn-danger">Delete</button>
                                </form>
                            <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php pih_admin_render_end(); ?>
