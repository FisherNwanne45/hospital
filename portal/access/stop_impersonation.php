<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/auth.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    pih_admin_set_flash('error', 'Database connection is unavailable.');
    header('Location: logout.php');
    exit();
}

if (!pih_admin_is_impersonating()) {
    pih_admin_set_flash('info', 'No impersonation session is active.');
    header('Location: index.php');
    exit();
}

$impersonatorId = (int) ($_SESSION['pih_impersonator_id'] ?? 0);
if ($impersonatorId <= 0) {
    pih_admin_set_flash('error', 'Unable to recover the super admin session. Please log in again.');
    header('Location: logout.php');
    exit();
}

$superUser = pih_admin_fetch_user_by_id($conn, $impersonatorId);
if (!$superUser || !pih_admin_user_is_super($superUser) || (string) ($superUser['status'] ?? '') !== 'active') {
    pih_admin_set_flash('error', 'Original super admin account is not available. Please log in again.');
    header('Location: logout.php');
    exit();
}

pih_admin_set_login_session($superUser);
pih_admin_clear_impersonation();
session_regenerate_id(true);

pih_admin_set_flash('success', 'Returned to super admin session: ' . (string) $superUser['username']);
header('Location: admin_users.php');
exit();
