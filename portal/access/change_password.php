<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/admin_ui.php';

$message = '';
$messageClass = 'alert alert-error';
$flash = pih_admin_consume_flash();
if (is_array($flash) && isset($flash['message'])) {
    $message = (string) $flash['message'];
    $messageClass = ($flash['type'] ?? '') === 'success' ? 'alert alert-success' : 'alert alert-error';
}

if (count($_POST) > 0) {
    if (!pih_admin_validate_csrf()) {
        $message = 'Security check failed. Please refresh and try again.';
        $messageClass = 'alert alert-error';
    } else {
        $newUsername = trim((string) ($_POST['username'] ?? ''));
        $newPassword = (string) ($_POST['password'] ?? '');

        if ($newUsername === '' || $newPassword === '') {
            $message = 'Username and password are required.';
            $messageClass = 'alert alert-error';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $conn->prepare('UPDATE login SET username = ?, password = ? WHERE id = ?');
            if ($update) {
                $update->bind_param('ssi', $newUsername, $newHash, $id);
                $update->execute();
                $update->close();
                $_SESSION['login_user'] = $newUsername;
                $_SESSION['pih_admin_last_activity'] = time();
                pih_admin_set_flash('success', 'Credentials updated successfully. Your session is still active.');
                header('Location: change_password.php');
                exit();
            }
            $message = 'Unable to update credentials.';
            $messageClass = 'alert alert-error';
        }
    }
}

$row = ['id' => $id, 'username' => ($login_session ?? '')];

pih_admin_render_start(
    'Change Password',
    'Update the admin username and password for secure portal access',
    'change_password',
    [
        ['href' => 'identity.php', 'icon' => 'icon-plus', 'label' => 'New Patient', 'primary' => true],
        ['href' => 'site_settings.php', 'icon' => 'icon-cog', 'label' => 'Site Settings'],
    ]
);
?>

<div class="module">
    <div class="module-head">
        <h3>Change Password</h3>
    </div>
    <div class="module-body">
        <?php if ($message !== '') { ?>
            <div class="<?php echo htmlspecialchars($messageClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form class="form-horizontal row-fluid" name="frmUser" method="post" action="">
            <?php echo pih_admin_csrf_input(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">

            <div class="control-group">
                <label class="control-label" for="current-username">Current Username</label>
                <div class="controls">
                    <input type="text" id="current-username" placeholder="<?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?>" class="span8" disabled>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="username">Change Username</label>
                <div class="controls">
                    <input class="span8" id="username" type="text" placeholder="Enter New Username" name="username" value="<?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="password">Change Password</label>
                <div class="controls">
                    <input type="password" id="password" placeholder="Enter New Password" class="span8" name="password" value="" autocomplete="new-password">
                </div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="submit" value="Submit" class="btn btn-success">Save Credentials</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php pih_admin_render_end(); ?>
