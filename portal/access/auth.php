<?php

require_once __DIR__ . '/../../includes/settings.php';

function pih_admin_ensure_login_schema(mysqli $db): void
{
    $createSql = "CREATE TABLE IF NOT EXISTS login (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(120) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_super_admin TINYINT(1) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->query($createSql);

    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM login');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $name = isset($row['Field']) ? (string) $row['Field'] : '';
            if ($name !== '') {
                $columns[$name] = true;
            }
        }
        $result->free();
    }

    if (!isset($columns['role'])) {
        $db->query("ALTER TABLE login ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'admin' AFTER password");
    }
    if (!isset($columns['is_super_admin'])) {
        $db->query('ALTER TABLE login ADD COLUMN is_super_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password');
    }
    if (!isset($columns['status'])) {
        if (isset($columns['is_super_admin'])) {
            $db->query("ALTER TABLE login ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER is_super_admin");
        } else {
            $db->query("ALTER TABLE login ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER role");
        }
    }
    if (!isset($columns['created_at'])) {
        $db->query('ALTER TABLE login ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    }
    if (!isset($columns['updated_at'])) {
        $db->query('ALTER TABLE login ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
    if (!isset($columns['last_login'])) {
        $db->query('ALTER TABLE login ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL');
    }

    if (isset($columns['role'])) {
        $db->query("UPDATE login SET is_super_admin = CASE WHEN LOWER(TRIM(role)) IN ('super_admin', '1', 'true', 'yes') THEN 1 ELSE 0 END WHERE role IS NOT NULL");
        $db->query("UPDATE login SET role = CASE WHEN is_super_admin = 1 THEN 'super_admin' ELSE 'admin' END");
    }
    $db->query("UPDATE login SET status = 'active' WHERE status IS NULL OR TRIM(status) = ''");

    pih_admin_ensure_activity_log_schema($db);
    pih_admin_ensure_patient_name_schema($db);
}

function pih_admin_ensure_patient_name_schema(mysqli $db): void
{
    $createSql = "CREATE TABLE IF NOT EXISTS user (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        image VARCHAR(255) DEFAULT NULL,
        cid VARCHAR(100) NOT NULL DEFAULT '',
        first_name VARCHAR(150) NOT NULL DEFAULT '',
        last_name VARCHAR(150) NOT NULL DEFAULT '',
        name VARCHAR(255) NOT NULL DEFAULT '',
        rank VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        patient_email VARCHAR(255) DEFAULT NULL,
        type VARCHAR(255) DEFAULT NULL,
        amt DECIMAL(10,2) DEFAULT NULL,
        rate DECIMAL(10,2) DEFAULT NULL,
        dur VARCHAR(50) DEFAULT NULL,
        coldate DATE DEFAULT NULL,
        paydate DATE DEFAULT NULL,
        status VARCHAR(100) DEFAULT NULL,
        remark TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_cid (cid),
        INDEX idx_user_status (status),
        INDEX idx_user_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->query($createSql);

    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM user');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $name = isset($row['Field']) ? (string) $row['Field'] : '';
            if ($name !== '') {
                $columns[$name] = true;
            }
        }
        $result->free();
    }

    if (!isset($columns['first_name'])) {
        $db->query('ALTER TABLE user ADD COLUMN first_name VARCHAR(150) NOT NULL DEFAULT "" AFTER cid');
    }
    if (!isset($columns['last_name'])) {
        $db->query('ALTER TABLE user ADD COLUMN last_name VARCHAR(150) NOT NULL DEFAULT "" AFTER first_name');
    }
    if (!isset($columns['image'])) {
        $db->query('ALTER TABLE user ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER id');
    }
    if (!isset($columns['rank'])) {
        $db->query('ALTER TABLE user ADD COLUMN rank VARCHAR(255) DEFAULT NULL AFTER name');
    }
    if (!isset($columns['phone'])) {
        $db->query('ALTER TABLE user ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER rank');
    }
    if (!isset($columns['patient_email'])) {
        $db->query('ALTER TABLE user ADD COLUMN patient_email VARCHAR(255) DEFAULT NULL AFTER phone');
    }
    if (!isset($columns['type'])) {
        $db->query('ALTER TABLE user ADD COLUMN type VARCHAR(255) DEFAULT NULL AFTER patient_email');
    }
    if (!isset($columns['amt'])) {
        $db->query('ALTER TABLE user ADD COLUMN amt DECIMAL(10,2) DEFAULT NULL AFTER type');
    }
    if (!isset($columns['rate'])) {
        $db->query('ALTER TABLE user ADD COLUMN rate DECIMAL(10,2) DEFAULT NULL AFTER amt');
    }
    if (!isset($columns['dur'])) {
        $db->query('ALTER TABLE user ADD COLUMN dur VARCHAR(50) DEFAULT NULL AFTER rate');
    }
    if (!isset($columns['coldate'])) {
        $db->query('ALTER TABLE user ADD COLUMN coldate DATE DEFAULT NULL AFTER dur');
    }
    if (!isset($columns['paydate'])) {
        $db->query('ALTER TABLE user ADD COLUMN paydate DATE DEFAULT NULL AFTER coldate');
    }
    if (!isset($columns['status'])) {
        $db->query('ALTER TABLE user ADD COLUMN status VARCHAR(100) DEFAULT NULL AFTER paydate');
    }
    if (!isset($columns['remark'])) {
        $db->query('ALTER TABLE user ADD COLUMN remark TEXT DEFAULT NULL AFTER status');
    }

    $db->query("UPDATE user SET first_name = TRIM(SUBSTRING_INDEX(TRIM(name), ' ', 1)) WHERE (first_name IS NULL OR TRIM(first_name) = '') AND name IS NOT NULL AND TRIM(name) <> ''");
    $db->query("UPDATE user SET last_name = TRIM(SUBSTRING(TRIM(name), CHAR_LENGTH(SUBSTRING_INDEX(TRIM(name), ' ', 1)) + 1)) WHERE (last_name IS NULL OR TRIM(last_name) = '') AND name IS NOT NULL AND TRIM(name) <> ''");
}

function pih_admin_ensure_activity_log_schema(mysqli $db): void
{
    $createSql = "CREATE TABLE IF NOT EXISTS admin_activity_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        actor_id INT UNSIGNED NOT NULL DEFAULT 0,
        actor_username VARCHAR(120) NOT NULL DEFAULT '',
        action VARCHAR(80) NOT NULL,
        subject_type VARCHAR(80) NOT NULL DEFAULT '',
        subject_id VARCHAR(80) NOT NULL DEFAULT '',
        details TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_actor_id (actor_id),
        INDEX idx_action (action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->query($createSql);
}

function pih_admin_log_action(mysqli $db, string $action, string $subjectType = '', string $subjectId = '', string $details = '', ?int $actorId = null, string $actorUsername = ''): bool
{
    pih_admin_ensure_activity_log_schema($db);

    $resolvedActorId = $actorId !== null ? $actorId : (int) ($_SESSION['login_user_id'] ?? 0);
    $resolvedActorUsername = $actorUsername !== '' ? $actorUsername : (string) ($_SESSION['login_user'] ?? '');

    $stmt = $db->prepare('INSERT INTO admin_activity_log (actor_id, actor_username, action, subject_type, subject_id, details) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('isssss', $resolvedActorId, $resolvedActorUsername, $action, $subjectType, $subjectId, $details);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function pih_admin_get_timeout_seconds(mysqli $db): int
{
    $minutesRaw = (string) pih_get_setting('admin_session_timeout_minutes', '30', $db);
    $minutes = (int) $minutesRaw;
    if ($minutes < 5) {
        $minutes = 5;
    }
    if ($minutes > 240) {
        $minutes = 240;
    }

    return $minutes * 60;
}

function pih_admin_fetch_user_by_username(mysqli $db, string $username): ?array
{
    $stmt = $db->prepare("SELECT id, username, password, is_super_admin, CASE WHEN is_super_admin = 1 THEN 'super_admin' ELSE 'admin' END AS role, status, last_login FROM login WHERE username = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return is_array($row) ? $row : null;
}

function pih_admin_fetch_user_by_id(mysqli $db, int $id): ?array
{
    $stmt = $db->prepare("SELECT id, username, password, is_super_admin, CASE WHEN is_super_admin = 1 THEN 'super_admin' ELSE 'admin' END AS role, status, last_login FROM login WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return is_array($row) ? $row : null;
}

function pih_admin_user_is_super(array $user): bool
{
    if (array_key_exists('is_super_admin', $user)) {
        return ((int) $user['is_super_admin']) === 1;
    }

    $legacyRole = strtolower(trim((string) ($user['role'] ?? '')));
    return in_array($legacyRole, ['super_admin', '1', 'true', 'yes'], true);
}

function pih_admin_is_super_admin(): bool
{
    if (isset($_SESSION['login_is_super_admin'])) {
        return ((int) $_SESSION['login_is_super_admin']) === 1;
    }

    $role = isset($_SESSION['login_role']) ? (string) $_SESSION['login_role'] : '';
    return $role === 'super_admin';
}

function pih_admin_is_impersonating(): bool
{
    return !empty($_SESSION['pih_impersonator_id']);
}

function pih_admin_require_super_admin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!pih_admin_is_super_admin()) {
        if (function_exists('pih_admin_set_flash')) {
            pih_admin_set_flash('error', 'Super admin access is required for that page.');
        }
        header('Location: index.php');
        exit();
    }
}

function pih_admin_count_super_admins(mysqli $db): int
{
    $result = $db->query("SELECT COUNT(*) AS total FROM login WHERE is_super_admin = 1 AND status = 'active'");
    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();
    $result->free();
    return isset($row['total']) ? (int) $row['total'] : 0;
}

function pih_admin_set_login_session(array $user): void
{
    $isSuperAdmin = pih_admin_user_is_super($user) ? 1 : 0;
    $_SESSION['login_user'] = (string) ($user['username'] ?? '');
    $_SESSION['login_user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['login_is_super_admin'] = $isSuperAdmin;
    $_SESSION['login_role'] = $isSuperAdmin === 1 ? 'super_admin' : 'admin';
    $_SESSION['pih_admin_last_activity'] = time();
}

function pih_admin_clear_impersonation(): void
{
    unset($_SESSION['pih_impersonator_id'], $_SESSION['pih_impersonator_username'], $_SESSION['pih_impersonator_role'], $_SESSION['pih_impersonator_is_super_admin']);
}

function pih_admin_mark_last_login(mysqli $db, int $id): void
{
    $stmt = $db->prepare('UPDATE login SET last_login = NOW() WHERE id = ?');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
