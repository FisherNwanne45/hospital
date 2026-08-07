<?php
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/portal/access/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function pih_install_extract_config_value(string $configRaw, string $varName): ?string
{
    $pattern = '/\\$' . preg_quote($varName, '/') . '\\s*=\\s*(["\'])(.*?)\\1\\s*;/s';
    if (!preg_match($pattern, $configRaw, $matches)) {
        return null;
    }

    return stripcslashes((string) $matches[2]);
}

function pih_install_parse_db_config(string $configPath): array
{
    if (!is_file($configPath) || !is_readable($configPath)) {
        return ['ok' => false, 'values' => [], 'error' => 'Database config file is missing or unreadable.'];
    }

    $raw = file_get_contents($configPath);
    if ($raw === false) {
        return ['ok' => false, 'values' => [], 'error' => 'Unable to read database config file.'];
    }

    $host = pih_install_extract_config_value($raw, 'servername');
    $user = pih_install_extract_config_value($raw, 'username');
    $pass = pih_install_extract_config_value($raw, 'password');
    $name = pih_install_extract_config_value($raw, 'dbname');

    if ($host === null || $user === null || $pass === null || $name === null) {
        return ['ok' => false, 'values' => [], 'error' => 'Could not parse DB credentials from portal/access/config.php.'];
    }

    return [
        'ok' => true,
        'values' => [
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'name' => $name,
        ],
        'error' => '',
    ];
}

function pih_install_write_db_config(string $configPath, string $host, string $user, string $pass, string $name): bool
{
    $dir = dirname($configPath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    $content = "<?php\n"
        . '$servername = ' . var_export($host, true) . ";\n"
        . '$username = ' . var_export($user, true) . ";\n"
        . '$password = ' . var_export($pass, true) . ";\n"
        . '$dbname = ' . var_export($name, true) . ";\n\n"
        . "// Create connection\n"
        . '$conn = new mysqli($servername, $username, $password, $dbname);' . PHP_EOL . PHP_EOL
        . "// Check connection" . PHP_EOL
        . 'if ($conn->connect_error) {' . PHP_EOL
        . '    die("Connection failed: " . $conn->connect_error);' . PHP_EOL
        . '}' . PHP_EOL;

    return file_put_contents($configPath, $content) !== false;
}

function pih_install_run_preflight(string $configPath): array
{
    $checks = [];
    $blockingFailure = false;
    $db = null;

    $pushCheck = static function (string $label, bool $ok, string $detail, bool $required = true) use (&$checks, &$blockingFailure): void {
        $checks[] = [
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
            'required' => $required,
        ];
        if ($required && !$ok) {
            $blockingFailure = true;
        }
    };

    $phpVersionOk = version_compare(PHP_VERSION, '8.0.0', '>=');
    $pushCheck('PHP version >= 8.0', $phpVersionOk, 'Detected PHP ' . PHP_VERSION . '.');

    foreach (['mysqli', 'json', 'mbstring', 'fileinfo'] as $ext) {
        $pushCheck('Extension: ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'Loaded.' : 'Not loaded.');
    }
    $opensslLoaded = extension_loaded('openssl');
    $pushCheck('Extension: openssl', $opensslLoaded, $opensslLoaded ? 'Loaded.' : 'Not loaded (needed for SMTP secret encryption).');

    $sessionPath = (string) session_save_path();
    if ($sessionPath === '') {
        $sessionPath = sys_get_temp_dir();
    }
    $sessionPathOk = is_dir($sessionPath) && is_writable($sessionPath);
    $pushCheck('Session storage writable', $sessionPathOk, 'Session path: ' . $sessionPath . '.');

    $mediaAssetsAbs = __DIR__ . '/media/assets';
    $mediaAssetsOk = is_dir($mediaAssetsAbs) && is_writable($mediaAssetsAbs);
    $pushCheck('Assets directory writable', $mediaAssetsOk, 'Required path: media/assets.');

    $brandingAbs = __DIR__ . '/media/assets/branding';
    $brandingOk = true;
    if (!is_dir($brandingAbs)) {
        $brandingOk = mkdir($brandingAbs, 0775, true);
    }
    $brandingOk = $brandingOk && is_dir($brandingAbs) && is_writable($brandingAbs);
    $pushCheck('Branding directory writable', $brandingOk, 'Required path: media/assets/branding.');

    $configReadable = is_file($configPath) && is_readable($configPath);
    $pushCheck('Database config readable', $configReadable, 'Expected file: portal/access/config.php.');

    $parsed = pih_install_parse_db_config($configPath);
    $pushCheck('Database config parse', (bool) ($parsed['ok'] ?? false), (string) ($parsed['error'] !== '' ? $parsed['error'] : 'Credentials found.'));

    if (($parsed['ok'] ?? false) && isset($parsed['values']) && is_array($parsed['values']) && extension_loaded('mysqli')) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $db = @new mysqli(
            (string) ($parsed['values']['host'] ?? ''),
            (string) ($parsed['values']['user'] ?? ''),
            (string) ($parsed['values']['pass'] ?? ''),
            (string) ($parsed['values']['name'] ?? '')
        );

        $connected = $db instanceof mysqli && $db->connect_errno === 0;
        $connectDetail = $connected
            ? 'Connected to database successfully.'
            : 'Connection failed: ' . (($db instanceof mysqli) ? $db->connect_error : 'Unknown connection error.');
        $pushCheck('Database connection', $connected, $connectDetail);

        if ($connected && $db instanceof mysqli) {
            $db->set_charset('utf8mb4');
            $queryOk = false;
            $result = $db->query('SELECT DATABASE() AS active_db');
            if ($result instanceof mysqli_result) {
                $queryOk = true;
                $result->free();
            }
            $pushCheck('Database query permission', $queryOk, $queryOk ? 'Basic query test passed.' : 'Unable to run basic query.');
            if (!$queryOk) {
                try {
                    if ($db instanceof mysqli) {
                        $db->close();
                    }
                } catch (Throwable $e) {
                    // Ignore already-closed connection errors during preflight cleanup.
                }
                $db = null;
            }
        } else {
            try {
                if ($db instanceof mysqli) {
                    $db->close();
                }
            } catch (Throwable $e) {
                // Ignore already-closed connection errors during preflight cleanup.
            }
            $db = null;
        }
    }

    return [
        'ok' => !$blockingFailure,
        'checks' => $checks,
        'db' => $db,
    ];
}

function pih_install_build_fix_hints(array $checks): array
{
    $failedByLabel = [];
    foreach ($checks as $check) {
        if (!is_array($check) || empty($check['required']) || !empty($check['ok'])) {
            continue;
        }

        $label = isset($check['label']) ? (string) $check['label'] : '';
        if ($label !== '') {
            $failedByLabel[$label] = true;
        }
    }

    $hints = [];

    if (isset($failedByLabel['Assets directory writable']) || isset($failedByLabel['Branding directory writable'])) {
        $hints[] = [
            'title' => 'Grant Apache write access to media assets',
            'note' => 'XAMPP Apache runs as the daemon user on this machine. Give group ownership to daemon and keep group write enabled.',
            'commands' => [
                'sudo chgrp -R daemon /Applications/XAMPP/xamppfiles/htdocs/privateimaginghealthcare/media/assets',
                'sudo chmod -R 775 /Applications/XAMPP/xamppfiles/htdocs/privateimaginghealthcare/media/assets',
            ],
        ];
    }

    if (isset($failedByLabel['Session storage writable'])) {
        $hints[] = [
            'title' => 'Fix PHP session storage permissions',
            'note' => 'Allow Apache to write to XAMPP temp/session path.',
            'commands' => [
                'sudo chgrp -R daemon /Applications/XAMPP/xamppfiles/temp',
                'sudo chmod -R 775 /Applications/XAMPP/xamppfiles/temp',
            ],
        ];
    }

    if (isset($failedByLabel['Database config readable'])) {
        $hints[] = [
            'title' => 'Allow Apache to read DB config',
            'note' => 'Ensure config file permissions allow read access for web server group.',
            'commands' => [
                'sudo chgrp daemon /Applications/XAMPP/xamppfiles/htdocs/privateimaginghealthcare/portal/access/config.php',
                'sudo chmod 640 /Applications/XAMPP/xamppfiles/htdocs/privateimaginghealthcare/portal/access/config.php',
            ],
        ];
    }

    if (isset($failedByLabel['Database config parse'])) {
        $hints[] = [
            'title' => 'Fix DB config variable format',
            'note' => 'Installer expects these variables in portal/access/config.php: $servername, $username, $password, $dbname.',
            'commands' => [],
        ];
    }

    if (isset($failedByLabel['Database connection']) || isset($failedByLabel['Database query permission'])) {
        $hints[] = [
            'title' => 'Validate database credentials and grants',
            'note' => 'Confirm DB host/username/password/database are correct and the DB user can connect and query that database.',
            'commands' => [],
        ];
    }

    return $hints;
}

$configPath = __DIR__ . '/portal/access/config.php';
$dbConfigDefaults = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'hospital',
];
$parsedDbConfig = pih_install_parse_db_config($configPath);
if (($parsedDbConfig['ok'] ?? false) && isset($parsedDbConfig['values']) && is_array($parsedDbConfig['values'])) {
    $dbConfigDefaults = [
        'host' => (string) ($parsedDbConfig['values']['host'] ?? 'localhost'),
        'user' => (string) ($parsedDbConfig['values']['user'] ?? 'root'),
        'pass' => (string) ($parsedDbConfig['values']['pass'] ?? ''),
        'name' => (string) ($parsedDbConfig['values']['name'] ?? 'hospital'),
    ];
}

$dbConfigNotice = '';
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($requestMethod === 'POST') {
    $postedDbHost = trim((string) ($_POST['db_host'] ?? ''));
    $postedDbUser = trim((string) ($_POST['db_username'] ?? ''));
    $postedDbPass = (string) ($_POST['db_password'] ?? '');
    $postedDbName = trim((string) ($_POST['db_name'] ?? ''));

    if ($postedDbHost !== '' || $postedDbUser !== '' || $postedDbName !== '') {
        if ($postedDbHost === '' || $postedDbUser === '' || $postedDbName === '') {
            $dbConfigNotice = 'Database host, username, and database name are required when saving database settings.';
        } elseif (pih_install_write_db_config($configPath, $postedDbHost, $postedDbUser, $postedDbPass, $postedDbName)) {
            $dbConfigDefaults = [
                'host' => $postedDbHost,
                'user' => $postedDbUser,
                'pass' => $postedDbPass,
                'name' => $postedDbName,
            ];
            $dbConfigNotice = 'Database settings were saved to portal/access/config.php.';
        } else {
            $dbConfigNotice = 'Unable to write database settings to portal/access/config.php.';
        }
    }
}

$preflight = pih_install_run_preflight($configPath);
$preflightChecks = is_array($preflight['checks'] ?? null) ? $preflight['checks'] : [];
$preflightOk = (bool) ($preflight['ok'] ?? false);
$preflightFixHints = pih_install_build_fix_hints($preflightChecks);

$step = isset($_GET['step']) && $_GET['step'] === 'configure' ? 'configure' : 'preflight';
if (!$preflightOk) {
    $step = 'preflight';
}

/** @var mysqli|null $db */
$db = isset($preflight['db']) && $preflight['db'] instanceof mysqli ? $preflight['db'] : null;
$error = '';
$message = '';
$completed = false;

if (!isset($_SESSION['pih_install_csrf']) || !is_string($_SESSION['pih_install_csrf'])) {
    $_SESSION['pih_install_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['pih_install_csrf'];

function pih_install_upload_asset(string $fieldName, string $prefix, string $targetDirAbs): array
{
    if (!isset($_FILES[$fieldName]) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
        return [false, '', ''];
    }

    $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'];
    $originalName = (string) ($_FILES[$fieldName]['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return [false, '', 'Unsupported file type for ' . $fieldName . '.'];
    }

    if (!is_dir($targetDirAbs) && !mkdir($targetDirAbs, 0775, true) && !is_dir($targetDirAbs)) {
        return [false, '', 'Unable to create branding directory.'];
    }

    $safeName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetAbs = rtrim($targetDirAbs, '/') . '/' . $safeName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetAbs)) {
        return [false, '', 'Unable to upload ' . $fieldName . '.'];
    }

    return [true, 'media/assets/branding/' . $safeName, ''];
}

function pih_install_copy_default_asset(string $sourceRel, string $prefix, string $targetDirAbs): array
{
    $sourceAbs = __DIR__ . '/' . ltrim($sourceRel, '/');
    if (!is_file($sourceAbs)) {
        return [false, '', 'Default asset not found: ' . $sourceRel];
    }

    if (!is_dir($targetDirAbs) && !mkdir($targetDirAbs, 0775, true) && !is_dir($targetDirAbs)) {
        return [false, '', 'Unable to create branding directory.'];
    }

    $ext = strtolower(pathinfo($sourceAbs, PATHINFO_EXTENSION));
    $safeName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetAbs = rtrim($targetDirAbs, '/') . '/' . $safeName;

    if (!copy($sourceAbs, $targetAbs)) {
        return [false, '', 'Unable to copy default asset: ' . $sourceRel];
    }

    return [true, 'media/assets/branding/' . $safeName, ''];
}

$defaults = pih_get_default_settings();
$fields = [
    'site_name' => (string) ($defaults['site_name'] ?? ''),
    'site_short_name' => (string) ($defaults['site_short_name'] ?? ''),
    'site_addr_primary' => (string) ($defaults['site_addr_primary'] ?? ''),
    'site_addr_secondary' => (string) ($defaults['site_addr_secondary'] ?? ''),
    'site_phone_primary' => (string) ($defaults['site_phone_primary'] ?? ''),
    'site_phone_secondary' => (string) ($defaults['site_phone_secondary'] ?? ''),
    'site_url' => (string) ($defaults['site_url'] ?? ''),
    'site_host' => (string) ($defaults['site_host'] ?? ''),
    'site_email_primary' => (string) ($defaults['site_email_primary'] ?? ''),
    'site_email_secondary' => (string) ($defaults['site_email_secondary'] ?? ''),
    'admin_username' => 'admin',
];

if ($db instanceof mysqli) {
    foreach ($fields as $key => $fallback) {
        if (strpos($key, 'admin_') === 0) {
            continue;
        }
        $fields[$key] = (string) pih_get_setting($key, $fallback, $db);
    }
    $completed = pih_is_installation_complete($db);
}

if ($requestMethod === 'POST' && !$completed && $step === 'configure') {
    $postedCsrf = isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : '';
    if (!hash_equals($csrf, $postedCsrf)) {
        $error = 'Security check failed. Refresh and try again.';
    } elseif (!$preflightOk) {
        $error = 'Pre-installation checks failed. Resolve the failed checks before running installation.';
    } elseif (!($db instanceof mysqli)) {
        $error = 'Database connection is unavailable. Verify portal/access/config.php.';
    } else {
        $keysToSave = [
            'site_name',
            'site_short_name',
            'site_addr_primary',
            'site_addr_secondary',
            'site_phone_primary',
            'site_phone_secondary',
            'site_url',
            'site_host',
            'site_email_primary',
            'site_email_secondary',
        ];

        foreach ($keysToSave as $key) {
            $fields[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        $fields['admin_username'] = trim((string) ($_POST['admin_username'] ?? 'admin'));
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        $adminPasswordConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

        if ($fields['site_name'] === '' || $fields['site_url'] === '' || $fields['site_email_primary'] === '') {
            $error = 'Site Name, Site URL, and Primary Email are required.';
        } elseif (!filter_var($fields['site_url'], FILTER_VALIDATE_URL)) {
            $error = 'Site URL must be a valid absolute URL (for example: http://localhost/privateimaginghealthcare).';
        } elseif (!filter_var($fields['site_email_primary'], FILTER_VALIDATE_EMAIL) || !filter_var($fields['site_email_secondary'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Primary and secondary emails must both be valid email addresses.';
        }

        $parsedHost = parse_url($fields['site_url'], PHP_URL_HOST);
        if ($error === '' && $fields['site_host'] === '' && is_string($parsedHost)) {
            $fields['site_host'] = $parsedHost;
        }

        pih_admin_ensure_login_schema($db);
        $adminCount = 0;
        $adminCountResult = $db->query('SELECT COUNT(*) AS total FROM login');
        if ($adminCountResult instanceof mysqli_result) {
            $row = $adminCountResult->fetch_assoc();
            $adminCount = isset($row['total']) ? (int) $row['total'] : 0;
            $adminCountResult->free();
        }

        if ($error === '' && $adminCount === 0) {
            if ($fields['admin_username'] === '') {
                $error = 'Admin username is required for first-time installation.';
            } elseif (strlen($adminPassword) < 8) {
                $error = 'Admin password must be at least 8 characters.';
            } elseif (!hash_equals($adminPassword, $adminPasswordConfirm)) {
                $error = 'Admin password confirmation does not match.';
            }
        }

        $brandingAbs = __DIR__ . '/media/assets/branding';
        $logoPath = '';
        $faviconPath = '';

        if ($error === '') {
            [$logoUploaded, $logoUploadedPath, $logoUploadError] = pih_install_upload_asset('site_logo', 'logo', $brandingAbs);
            if ($logoUploadError !== '') {
                $error = $logoUploadError;
            } elseif ($logoUploaded) {
                $logoPath = $logoUploadedPath;
            } else {
                [$logoCopied, $logoCopiedPath, $logoCopyError] = pih_install_copy_default_asset((string) ($defaults['site_logo_path'] ?? 'static/images/NY_Imaging_Specialists.png'), 'logo', $brandingAbs);
                if (!$logoCopied) {
                    $error = $logoCopyError;
                } else {
                    $logoPath = $logoCopiedPath;
                }
            }
        }

        if ($error === '') {
            [$iconUploaded, $iconUploadedPath, $iconUploadError] = pih_install_upload_asset('site_favicon', 'favicon', $brandingAbs);
            if ($iconUploadError !== '') {
                $error = $iconUploadError;
            } elseif ($iconUploaded) {
                $faviconPath = $iconUploadedPath;
            } else {
                [$iconCopied, $iconCopiedPath, $iconCopyError] = pih_install_copy_default_asset((string) ($defaults['site_favicon_path'] ?? 'static/images/mri.png'), 'favicon', $brandingAbs);
                if (!$iconCopied) {
                    $error = $iconCopyError;
                } else {
                    $faviconPath = $iconCopiedPath;
                }
            }
        }

        if ($error === '') {
            $db->begin_transaction();
            try {
                pih_ensure_settings_table($db);
                pih_seed_defaults($db);

                foreach ($keysToSave as $key) {
                    pih_set_setting($key, $fields[$key], 'string', $db);
                }

                pih_set_setting('site_logo_path', $logoPath, 'string', $db);
                pih_set_setting('site_favicon_path', $faviconPath, 'string', $db);
                pih_set_setting('livechat_script', '', 'string', $db);

                if ($adminCount === 0) {
                    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare('INSERT INTO login (username, password, is_super_admin, role, status) VALUES (?, ?, 1, "super_admin", "active")');
                    if (!$stmt) {
                        throw new RuntimeException('Unable to create admin account.');
                    }
                    $stmt->bind_param('ss', $fields['admin_username'], $passwordHash);
                    if (!$stmt->execute()) {
                        $stmt->close();
                        throw new RuntimeException('Unable to create admin account.');
                    }

                    $insertId = $stmt->insert_id;
                    $stmt->close();

                    if ($insertId > 0) {
                        $db->query('UPDATE login SET role = CASE WHEN is_super_admin = 1 THEN "super_admin" ELSE "admin" END WHERE id = ' . (int) $insertId);
                    }
                }

                if (!pih_email_ensure_schema($db)) {
                    $dbError = trim((string) $db->error);
                    $detail = $dbError !== '' ? ' MySQL error: ' . $dbError : '';
                    throw new RuntimeException('Unable to initialize email template schema.' . $detail);
                }

                if (!pih_mark_installation_complete($db)) {
                    throw new RuntimeException('Unable to mark installation as complete.');
                }

                $db->commit();
                $completed = true;
                $message = 'Installation completed successfully. You can now sign in to the admin portal.';
            } catch (Throwable $e) {
                $db->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$siteUrlForLinks = $fields['site_url'] !== '' ? rtrim($fields['site_url'], '/') : '';
$loginUrl = $siteUrlForLinks !== '' ? $siteUrlForLinks . '/portal/access/login.php' : '/portal/access/login.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Installer</title>
    <style>
        :root {
            --ink: #102a43;
            --muted: #5f7288;
            --line: #d8e3ef;
            --brand: #12548a;
            --brand-soft: #2a7fbb;
            --bg: #edf4fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1000px 420px at -10% -20%, rgba(18, 84, 138, 0.22), transparent 62%),
                radial-gradient(920px 380px at 120% 120%, rgba(42, 127, 187, 0.16), transparent 62%),
                var(--bg);
        }

        .wrap {
            max-width: 980px;
            margin: 36px auto;
            padding: 0 16px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 20px 32px rgba(16, 42, 67, 0.08);
            overflow: hidden;
        }

        .head {
            background: linear-gradient(135deg, var(--brand), var(--brand-soft));
            color: #fff;
            padding: 20px 22px;
        }

        .head h1 {
            margin: 0;
            font-size: 25px;
        }

        .head p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.92);
            font-size: 14px;
        }

        .body {
            padding: 20px 22px 24px;
        }

        .hint {
            margin: 0 0 16px;
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }

        .ok,
        .err {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .ok {
            background: #eaf8f1;
            border: 1px solid #c3ead6;
            color: #0f6b45;
        }

        .err {
            background: #fff0f1;
            border: 1px solid #f3c6ca;
            color: #9b1f2f;
        }

        .preflight {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 14px;
            background: #fbfdff;
        }

        .preflight h2 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .preflight-table {
            width: 100%;
            border-collapse: collapse;
        }

        .preflight-table th,
        .preflight-table td {
            text-align: left;
            padding: 8px 6px;
            border-bottom: 1px solid #e7eef5;
            vertical-align: top;
            font-size: 13px;
        }

        .preflight-table th {
            color: var(--muted);
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            border-radius: 12px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .badge-ok {
            background: #ddf4e8;
            color: #0f6b45;
        }

        .badge-bad {
            background: #ffe1e3;
            color: #9b1f2f;
        }

        .badge-req {
            background: #edf4fb;
            color: #12548a;
        }

        .fixbox {
            border: 1px solid #f3c6ca;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 14px;
            background: #fff7f7;
        }

        .fixbox h3 {
            margin: 0 0 8px;
            font-size: 15px;
            color: #9b1f2f;
        }

        .fix-item {
            margin: 10px 0 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #efc6cb;
        }

        .fix-item:last-child {
            border-bottom: 0;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .fix-title {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .fix-note {
            color: #6d2b33;
            font-size: 13px;
            margin: 0 0 6px;
            line-height: 1.5;
        }

        .cmd {
            margin: 0;
            padding: 8px 10px;
            border-radius: 8px;
            background: #201316;
            color: #fbecef;
            font-size: 12px;
            font-family: Menlo, Monaco, Consolas, monospace;
            overflow-x: auto;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 16px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 13px;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 11px;
            font-size: 14px;
            color: var(--ink);
            background: #fff;
        }

        textarea {
            min-height: 86px;
            resize: vertical;
        }

        .sec {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
        }

        .sec h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .small {
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
            line-height: 1.5;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            border: 1px solid transparent;
            border-radius: 9px;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-light {
            background: #fff;
            border-color: var(--line);
            color: var(--ink);
        }

        .btn-disabled,
        .btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            pointer-events: none;
        }

        @media (max-width: 760px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <h1>Application Installer</h1>
                <p>Configure core branding and contact settings for this deployment.</p>
            </div>
            <div class="body">
                <?php if ($message !== '') { ?>
                    <div class="ok"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <?php if ($error !== '') { ?>
                    <div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <?php if ($completed) { ?>
                    <div class="actions">
                        <a class="btn btn-primary"
                            href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>">Go to Admin Login</a>
                    </div>
                <?php } elseif ($step === 'preflight') { ?>
                    <div class="preflight">
                        <h2>Step 1: Environment & Database Preflight</h2>
                        <?php if ($dbConfigNotice !== '') { ?>
                            <div class="ok" style="margin-bottom:12px;">
                                <?php echo htmlspecialchars($dbConfigNotice, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php } ?>
                        <form method="post" action="?step=preflight" style="margin-bottom:14px;">
                            <div class="grid">
                                <div>
                                    <label for="db_host">Database Host</label>
                                    <input id="db_host" name="db_host" type="text"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['host'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="db_username">Database Username</label>
                                    <input id="db_username" name="db_username" type="text"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['user'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="db_password">Database Password</label>
                                    <input id="db_password" name="db_password" type="password"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['pass'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="db_name">Database Name</label>
                                    <input id="db_name" name="db_name" type="text"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="actions" style="margin-top:12px;">
                                <button class="btn btn-primary" type="submit">Save &amp; Test Database</button>
                            </div>
                        </form>
                        <table class="preflight-table">
                            <thead>
                                <tr>
                                    <th>Check</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preflightChecks as $check) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) ($check['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($check['ok'])) { ?>
                                                <span class="badge badge-ok">PASS</span>
                                            <?php } else { ?>
                                                <span class="badge badge-bad">FAIL</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) ($check['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($check['required'])) { ?>
                                                <span class="badge badge-req">Required</span>
                                            <?php } else { ?>
                                                Optional
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!$preflightOk && !empty($preflightFixHints)) { ?>
                        <div class="fixbox">
                            <h3>Fix Failed Checks</h3>
                            <?php foreach ($preflightFixHints as $hint) { ?>
                                <div class="fix-item">
                                    <div class="fix-title">
                                        <?php echo htmlspecialchars((string) ($hint['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <p class="fix-note">
                                        <?php echo htmlspecialchars((string) ($hint['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if (!empty($hint['commands']) && is_array($hint['commands'])) { ?>
                                        <?php foreach ($hint['commands'] as $cmd) { ?>
                                            <pre class="cmd"><?php echo htmlspecialchars((string) $cmd, ENT_QUOTES, 'UTF-8'); ?></pre>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <div class="actions">
                        <form method="get" action="" style="margin:0;display:inline;">
                            <input type="hidden" name="step" value="configure">
                            <button class="btn btn-primary" type="submit"
                                <?php echo $preflightOk ? '' : 'disabled'; ?>>Next</button>
                        </form>
                        <a class="btn btn-light" href="install.php">Re-check Preflight</a>
                    </div>
                <?php } else { ?>
                    <p class="hint">Step 2: Complete installation settings. Theme colors, session timeout defaults, and
                        email template defaults are retained from the current database defaults. Live chat is
                        intentionally left empty during installation and can be configured later in admin settings.</p>
                    <form method="post" enctype="multipart/form-data" action="?step=configure">
                        <input type="hidden" name="_csrf"
                            value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="sec" style="margin-top:0;padding-top:0;border-top:0;">
                            <h2>Database Connection</h2>
                            <div class="grid">
                                <div>
                                    <label for="db_host">Database Host</label>
                                    <input id="db_host" name="db_host" type="text"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['host'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="db_username">Database Username</label>
                                    <input id="db_username" name="db_username" type="text"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['user'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="db_password">Database Password</label>
                                    <input id="db_password" name="db_password" type="password"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['pass'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="db_name">Database Name</label>
                                    <input id="db_name" name="db_name" type="text"
                                        value="<?php echo htmlspecialchars($dbConfigDefaults['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="grid">
                            <div>
                                <label for="site_name">Site Name</label>
                                <input id="site_name" name="site_name" type="text"
                                    value="<?php echo htmlspecialchars($fields['site_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    required>
                            </div>
                            <div>
                                <label for="site_short_name">Site Short Name</label>
                                <input id="site_short_name" name="site_short_name" type="text"
                                    value="<?php echo htmlspecialchars($fields['site_short_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="full">
                                <label for="site_addr_primary">Primary Address</label>
                                <textarea id="site_addr_primary"
                                    name="site_addr_primary"><?php echo htmlspecialchars($fields['site_addr_primary'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div class="full">
                                <label for="site_addr_secondary">Secondary Address</label>
                                <textarea id="site_addr_secondary"
                                    name="site_addr_secondary"><?php echo htmlspecialchars($fields['site_addr_secondary'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            <div>
                                <label for="site_phone_primary">Primary Phone</label>
                                <input id="site_phone_primary" name="site_phone_primary" type="text"
                                    value="<?php echo htmlspecialchars($fields['site_phone_primary'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div>
                                <label for="site_phone_secondary">Secondary Phone</label>
                                <input id="site_phone_secondary" name="site_phone_secondary" type="text"
                                    value="<?php echo htmlspecialchars($fields['site_phone_secondary'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div>
                                <label for="site_email_primary">Primary Email</label>
                                <input id="site_email_primary" name="site_email_primary" type="email"
                                    value="<?php echo htmlspecialchars($fields['site_email_primary'], ENT_QUOTES, 'UTF-8'); ?>"
                                    required>
                            </div>
                            <div>
                                <label for="site_email_secondary">Secondary Email</label>
                                <input id="site_email_secondary" name="site_email_secondary" type="email"
                                    value="<?php echo htmlspecialchars($fields['site_email_secondary'], ENT_QUOTES, 'UTF-8'); ?>"
                                    required>
                            </div>
                            <div>
                                <label for="site_url">Site URL</label>
                                <input id="site_url" name="site_url" type="url"
                                    value="<?php echo htmlspecialchars($fields['site_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                    required>
                            </div>
                            <div>
                                <label for="site_host">Site Host</label>
                                <input id="site_host" name="site_host" type="text"
                                    value="<?php echo htmlspecialchars($fields['site_host'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="small">If empty, host is auto-derived from Site URL.</div>
                            </div>
                        </div>

                        <div class="sec">
                            <h2>Branding Assets</h2>
                            <div class="grid">
                                <div>
                                    <label for="site_logo">Logo File (optional upload)</label>
                                    <input id="site_logo" name="site_logo" type="file"
                                        accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                                    <div class="small">If omitted, current default logo is copied into
                                        media/assets/branding and mapped.</div>
                                </div>
                                <div>
                                    <label for="site_favicon">Favicon File (optional upload)</label>
                                    <input id="site_favicon" name="site_favicon" type="file"
                                        accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.ico">
                                    <div class="small">If omitted, current default favicon is copied into
                                        media/assets/branding and mapped.</div>
                                </div>
                            </div>
                        </div>

                        <div class="sec">
                            <h2>Admin Account</h2>
                            <div class="small">If no admin exists yet, these fields are used to create the first super
                                admin.</div>
                            <div class="grid" style="margin-top:8px;">
                                <div>
                                    <label for="admin_username">Admin Username</label>
                                    <input id="admin_username" name="admin_username" type="text"
                                        value="<?php echo htmlspecialchars($fields['admin_username'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="admin_password">Admin Password</label>
                                    <input id="admin_password" name="admin_password" type="password"
                                        autocomplete="new-password">
                                </div>
                                <div>
                                    <label for="admin_password_confirm">Confirm Admin Password</label>
                                    <input id="admin_password_confirm" name="admin_password_confirm" type="password"
                                        autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" type="submit"
                                <?php echo $preflightOk ? '' : 'disabled'; ?>>Run Installation</button>
                        </div>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
</body>

</html>