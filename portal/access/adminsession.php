<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../short.php';
require_once __DIR__ . '/auth.php';

$login_session = '';
$id = 0;

$connection = (isset($conn) && $conn instanceof mysqli)
    ? $conn
    : mysqli_connect($servername, $username, $password, $dbname);

if (!$connection) {
    die('Connection failed: ' . mysqli_connect_error());
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

pih_admin_ensure_login_schema($connection);

$sessionTimeoutSeconds = pih_admin_get_timeout_seconds($connection);
$lastActivity = isset($_SESSION['pih_admin_last_activity']) ? (int) $_SESSION['pih_admin_last_activity'] : 0;

if ($lastActivity > 0 && (time() - $lastActivity) > $sessionTimeoutSeconds) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}

if (empty($_SESSION['login_user'])) {
    header('Location: login.php');
    exit();
}

$user_check = $_SESSION['login_user'];
$stmt = $connection->prepare("SELECT id, username, is_super_admin, CASE WHEN is_super_admin = 1 THEN 'super_admin' ELSE 'admin' END AS role, status FROM login WHERE username = ? LIMIT 1");

if (!$stmt) {
    header('Location: login.php');
    exit();
}

$stmt->bind_param('s', $user_check);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    unset($_SESSION['login_user']);
    header('Location: login.php');
    exit();
}

$status = strtolower(trim((string) ($row['status'] ?? 'active')));
if ($status !== 'active') {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit();
}

$login_session = $row['username'];
$id = (int) $row['id'];
$_SESSION['login_user_id'] = $id;
$_SESSION['login_is_super_admin'] = (int) ($row['is_super_admin'] ?? 0);
$_SESSION['login_role'] = ((int) ($_SESSION['login_is_super_admin'] ?? 0) === 1) ? 'super_admin' : 'admin';
$_SESSION['pih_admin_last_activity'] = time();

$login_role = (string) ($_SESSION['login_role'] ?? 'admin');
$is_impersonating = pih_admin_is_impersonating();
$impersonator_username = isset($_SESSION['pih_impersonator_username']) ? (string) $_SESSION['pih_impersonator_username'] : '';
