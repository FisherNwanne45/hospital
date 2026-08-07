<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/auth.php';

pih_admin_require_super_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pih_admin_validate_csrf()) {
    pih_admin_set_flash('error', 'Impersonation request could not be verified.');
    header('Location: admin_users.php');
    exit();
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    pih_admin_set_flash('error', 'Database connection is unavailable.');
    header('Location: admin_users.php');
    exit();
}

if (pih_admin_is_impersonating()) {
    pih_admin_set_flash('error', 'Return to your super admin account before starting another impersonation session.');
    header('Location: index.php');
    exit();
}

$targetId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($targetId <= 0) {
    pih_admin_set_flash('error', 'No admin account was selected for impersonation.');
    header('Location: admin_users.php');
    exit();
}

$target = pih_admin_fetch_user_by_id($conn, $targetId);
if (!$target) {
    pih_admin_set_flash('error', 'Selected admin account was not found.');
    header('Location: admin_users.php');
    exit();
}

if (pih_admin_user_is_super($target)) {
    pih_admin_set_flash('error', 'Only regular admin accounts can be impersonated.');
    header('Location: admin_users.php');
    exit();
}

if ((string) ($target['status'] ?? '') !== 'active') {
    pih_admin_set_flash('error', 'Only active admin accounts can be impersonated.');
    header('Location: admin_users.php');
    exit();
}

$_SESSION['pih_impersonator_id'] = (int) ($_SESSION['login_user_id'] ?? 0);
$_SESSION['pih_impersonator_username'] = (string) ($_SESSION['login_user'] ?? '');
$_SESSION['pih_impersonator_role'] = (string) ($_SESSION['login_role'] ?? '');
$_SESSION['pih_impersonator_is_super_admin'] = (int) ($_SESSION['login_is_super_admin'] ?? 0);

pih_admin_set_login_session($target);
session_regenerate_id(true);

pih_admin_set_flash('success', 'You are now impersonating admin: ' . (string) $target['username']);
header('Location: index.php');
exit();
