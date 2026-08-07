<?php
include_once('session.php');
require_once __DIR__ . '/auth.php';
$error = ''; // Variable To Store Error Message
$db = null;

if (isset($connection) && $connection instanceof mysqli) {
    $db = $connection;
} elseif (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
}

if ($db instanceof mysqli) {
    pih_admin_ensure_login_schema($db);
}

if (isset($_POST['submit'])) {
    if (!($db instanceof mysqli)) {
        $error = 'Database connection is unavailable';
    } elseif (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Username or Password is invalid';
    } else {
        $username = trim((string) $_POST['username']);
        $password = (string) $_POST['password'];

        $stmt = $db->prepare("SELECT id, username, password, is_super_admin, CASE WHEN is_super_admin = 1 THEN 'super_admin' ELSE 'admin' END AS role, status FROM login WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            $isValid = false;
            if ($row) {
                $storedPassword = (string) $row['password'];
                $userStatus = strtolower(trim((string) ($row['status'] ?? 'active')));

                if ($userStatus !== 'active') {
                    $error = 'This admin account is inactive. Contact a super admin.';
                }

                if ($error === '' && password_verify($password, $storedPassword)) {
                    $isValid = true;
                } elseif ($error === '' && hash_equals($storedPassword, $password)) {
                    // Backward compatibility for old plaintext passwords.
                    $isValid = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upgrade = $db->prepare('UPDATE login SET password = ? WHERE id = ?');
                    if ($upgrade) {
                        $uid = (int) $row['id'];
                        $upgrade->bind_param('si', $newHash, $uid);
                        $upgrade->execute();
                        $upgrade->close();
                    }
                }
            }

            if ($isValid) {
                session_regenerate_id(true);
                pih_admin_set_login_session($row);
                pih_admin_clear_impersonation();
                pih_admin_mark_last_login($db, (int) $row['id']);
                header('Location: index.php');
                exit();
            }
        }

        $error = 'Username or Password is invalid';
    }
}
