<?php
include('adminsession.php');
include_once('config.php');
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/admin_ui.php';

/** @var mysqli|null $db */
$db = isset($conn) && $conn instanceof mysqli ? $conn : null;

function pih_handle_upload(string $fieldName, string $targetDirAbs): array
{
    if (!isset($_FILES[$fieldName]) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
        return [false, ''];
    }

    $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'];
    $originalName = (string) $_FILES[$fieldName]['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        return [false, 'Unsupported file type.'];
    }

    if (!is_dir($targetDirAbs)) {
        mkdir($targetDirAbs, 0775, true);
    }

    $safeName = $fieldName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetAbs = rtrim($targetDirAbs, '/') . '/' . $safeName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetAbs)) {
        return [false, 'Upload failed.'];
    }

    return [true, 'media/assets/branding/' . $safeName];
}

function pih_sync_legacy_site_table(mysqli $conn, array $settings): void
{
    try {
        $check = $conn->query("SHOW TABLES LIKE 'site'");
        if (!$check || $check->num_rows < 1) {
            return;
        }

        $requiredColumns = ['id', 'name', 'addr', 'phone', 'email'];
        $columns = [];
        $columnsResult = $conn->query("SHOW COLUMNS FROM site");
        if ($columnsResult) {
            while ($col = $columnsResult->fetch_assoc()) {
                $columns[] = (string) ($col['Field'] ?? '');
            }
            $columnsResult->free();
        }

        foreach ($requiredColumns as $colName) {
            if (!in_array($colName, $columns, true)) {
                return;
            }
        }

        $firstRow = $conn->query('SELECT id FROM site ORDER BY id ASC LIMIT 1');
        if (!$firstRow || $firstRow->num_rows < 1) {
            return;
        }

        $site = $firstRow->fetch_assoc();
        $siteId = (int) $site['id'];

        $stmt = $conn->prepare('UPDATE site SET name = ?, addr = ?, phone = ?, email = ? WHERE id = ?');
        if ($stmt) {
            $name = (string) ($settings['site_name'] ?? '');
            $addr = (string) ($settings['site_addr_primary'] ?? '');
            $phone = (string) ($settings['site_phone_primary'] ?? '');
            $email = (string) ($settings['site_email_primary'] ?? '');
            $stmt->bind_param('ssssi', $name, $addr, $phone, $email, $siteId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        return;
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!($db instanceof mysqli)) {
        $error = 'Database connection is unavailable.';
    } elseif (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    }

    $settingKeys = [
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
        'livechat_script'
    ];

    if ($error === '') {
        foreach ($settingKeys as $key) {
            $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
            pih_set_setting($key, $value, 'string', $db);
        }

        $brandingAbs = __DIR__ . '/../../media/assets/branding';

        [$logoOk, $logoResult] = pih_handle_upload('site_logo', $brandingAbs);
        if ($logoOk) {
            pih_set_setting('site_logo_path', $logoResult, 'string', $db);
        } elseif ($logoResult !== '') {
            $error = $logoResult;
        }

        [$iconOk, $iconResult] = pih_handle_upload('site_favicon', $brandingAbs);
        if ($iconOk) {
            pih_set_setting('site_favicon_path', $iconResult, 'string', $db);
        } elseif ($iconResult !== '') {
            $error = $error === '' ? $iconResult : $error . ' ' . $iconResult;
        }

        $settingsAfterSave = pih_get_settings_map($db);
        pih_sync_legacy_site_table($db, $settingsAfterSave);

        if ($error === '') {
            $message = 'Settings saved successfully.';
        }
    }
}

$settings = pih_get_settings_map($db);
$logoPath = trim((string) ($settings['site_logo_path'] ?? ''));
$faviconPath = trim((string) ($settings['site_favicon_path'] ?? ''));

pih_admin_render_start(
    'Site Settings',
    'Manage branding, contact details, and live chat configuration',
    'site_settings',
    [
        ['href' => 'identity.php', 'icon' => 'icon-plus', 'label' => 'New Patient', 'primary' => true],
        ['href' => 'index.php', 'icon' => 'icon-dashboard', 'label' => 'Dashboard'],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>Unified Site Settings</h3>
    </div>
    <div class="module-body">
        <style>
            .branding-preview {
                margin-top: 10px;
                border: 1px solid #e2ebf5;
                border-radius: 12px;
                background: #f7fbff;
                padding: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .branding-preview img {
                display: block;
                object-fit: contain;
            }

            .branding-preview.logo img {
                max-width: 220px;
                max-height: 64px;
            }

            .branding-preview.favicon img {
                width: 32px;
                height: 32px;
            }

            .preview-note {
                margin-top: 8px;
                font-size: 12px;
                color: #66758c;
                font-weight: 600;
            }

            .preview-empty {
                font-size: 12px;
                color: #66758c;
                font-weight: 600;
            }

            .branding-file-input {
                width: min(460px, 100%);
                max-width: 100%;
                display: block;
                height: auto;
                min-height: 40px;
                line-height: 1.3;
                padding: 7px 10px !important;
            }
        </style>

        <?php if ($message !== '') { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form class="form-horizontal row-fluid" method="post" enctype="multipart/form-data">
            <?php echo pih_admin_csrf_input(); ?>
            <h4>Identity</h4>
            <div class="control-group"><label class="control-label">Site Name</label><div class="controls"><input class="span8" type="text" name="site_name" value="<?php echo htmlspecialchars((string) $settings['site_name'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Short Name</label><div class="controls"><input class="span8" type="text" name="site_short_name" value="<?php echo htmlspecialchars((string) $settings['site_short_name'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Site URL</label><div class="controls"><input class="span8" type="text" name="site_url" value="<?php echo htmlspecialchars((string) $settings['site_url'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Host Name</label><div class="controls"><input class="span8" type="text" name="site_host" value="<?php echo htmlspecialchars((string) $settings['site_host'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>

            <hr>
            <h4>Contacts</h4>
            <div class="control-group"><label class="control-label">Primary Email</label><div class="controls"><input class="span8" type="email" name="site_email_primary" value="<?php echo htmlspecialchars((string) $settings['site_email_primary'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Secondary Email</label><div class="controls"><input class="span8" type="email" name="site_email_secondary" value="<?php echo htmlspecialchars((string) $settings['site_email_secondary'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Primary Phone</label><div class="controls"><input class="span8" type="text" name="site_phone_primary" value="<?php echo htmlspecialchars((string) $settings['site_phone_primary'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Secondary Phone</label><div class="controls"><input class="span8" type="text" name="site_phone_secondary" value="<?php echo htmlspecialchars((string) $settings['site_phone_secondary'], ENT_QUOTES, 'UTF-8'); ?>"></div></div>
            <div class="control-group"><label class="control-label">Primary Address</label><div class="controls"><textarea class="span8" rows="3" name="site_addr_primary"><?php echo htmlspecialchars((string) $settings['site_addr_primary'], ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>
            <div class="control-group"><label class="control-label">Secondary Address</label><div class="controls"><textarea class="span8" rows="3" name="site_addr_secondary"><?php echo htmlspecialchars((string) $settings['site_addr_secondary'], ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>

            <hr>
            <h4>Branding</h4>
            <div class="control-group">
                <label class="control-label">Logo</label>
                <div class="controls">
                    <input class="branding-file-input" type="file" name="site_logo" accept=".png,.jpg,.jpeg,.gif,.webp,.svg">
                    <div class="branding-preview logo">
                        <?php if ($logoPath !== '') { ?>
                            <img src="../../<?php echo htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Site Logo">
                        <?php } else { ?>
                            <div class="preview-empty">No logo uploaded yet.</div>
                        <?php } ?>
                    </div>
                    <div class="preview-note">Preview is constrained for a realistic header size.</div>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Favicon</label>
                <div class="controls">
                    <input class="branding-file-input" type="file" name="site_favicon" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,.ico">
                    <div class="branding-preview favicon">
                        <?php if ($faviconPath !== '') { ?>
                            <img src="../../<?php echo htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Site Favicon">
                        <?php } else { ?>
                            <div class="preview-empty">No favicon uploaded yet.</div>
                        <?php } ?>
                    </div>
                    <div class="preview-note">Favicon preview is shown at standard 32x32.</div>
                </div>
            </div>

            <hr>
            <h4>Live Chat Script</h4>
            <div class="control-group"><label class="control-label">Script</label><div class="controls"><textarea class="span8" rows="8" name="livechat_script"><?php echo htmlspecialchars((string) $settings['livechat_script'], ENT_QUOTES, 'UTF-8'); ?></textarea></div></div>

            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-success">Save Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
$extraScripts = '';

pih_admin_render_end($extraScripts);
?>
