<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/../../includes/settings.php';
require_once __DIR__ . '/../../includes/email.php';
require_once __DIR__ . '/admin_ui.php';
require_once __DIR__ . '/auth.php';

pih_admin_require_super_admin();

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
if ($db instanceof mysqli) {
    pih_admin_ensure_login_schema($db);
    pih_email_ensure_schema($db);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!($db instanceof mysqli)) {
        $error = 'Database connection is unavailable.';
    } elseif (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $smtpHost = trim((string) ($_POST['smtp_host'] ?? ''));
        $smtpPort = (int) ($_POST['smtp_port'] ?? 587);
        $smtpUsername = trim((string) ($_POST['smtp_username'] ?? ''));
        $smtpPassword = (string) ($_POST['smtp_password'] ?? '');
        $smtpFromEmail = trim((string) ($_POST['smtp_from_email'] ?? ''));
        $smtpFromName = trim((string) ($_POST['smtp_from_name'] ?? ''));
        $smtpAuth = isset($_POST['smtp_auth']) ? '1' : '0';
        $smtpTimeout = (int) pih_get_setting('smtp_timeout', '30', $db);
        $smtpEncryption = strtolower(trim((string) ($_POST['smtp_encryption'] ?? 'starttls')));
        $smtpTestTo = trim((string) ($_POST['smtp_test_to'] ?? ''));

        $allowedEncryption = ['none', 'ssl', 'tls', 'starttls'];
        if (!in_array($smtpEncryption, $allowedEncryption, true)) {
            $smtpEncryption = 'starttls';
        }

        if ($smtpPort <= 0) {
            $smtpPort = $smtpEncryption === 'ssl' ? 465 : 587;
        }
        if ($smtpTimeout < 5) {
            $smtpTimeout = 5;
        }
        if ($smtpTimeout > 120) {
            $smtpTimeout = 120;
        }

        pih_set_setting('smtp_host', $smtpHost, 'string', $db);
        pih_set_setting('smtp_port', (string) $smtpPort, 'int', $db);
        pih_set_setting('smtp_username', $smtpUsername, 'string', $db);
        pih_set_setting('smtp_from_email', $smtpFromEmail, 'string', $db);
        pih_set_setting('smtp_from_name', $smtpFromName, 'string', $db);
        pih_set_setting('smtp_auth', $smtpAuth, 'int', $db);
        pih_set_setting('smtp_encryption', $smtpEncryption, 'string', $db);
        pih_set_setting('smtp_test_to', $smtpTestTo, 'string', $db);

        if ($smtpPassword !== '') {
            $encrypted = pih_smtp_encrypt($db, $smtpPassword);
            if ($encrypted === '') {
                $error = 'Unable to encrypt SMTP password on this server.';
            } else {
                pih_set_setting('smtp_password_enc', $encrypted, 'string', $db);
            }
        }

        if ($error === '' && isset($_POST['save'])) {
            $message = 'SMTP settings saved successfully.';
        }

        if ($error === '' && isset($_POST['send_test'])) {
            $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
            if (is_file($autoloadPath)) {
                require_once $autoloadPath;
            }

            if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                $error = 'PHPMailer is not installed. Add it with Composer before running test email.';
            } elseif ($smtpHost === '' || $smtpUsername === '' || $smtpFromEmail === '' || $smtpTestTo === '') {
                $error = 'Host, username, from email, and test recipient are required for test email.';
            } else {
                $decryptedPassword = $smtpPassword !== '' ? $smtpPassword : pih_smtp_decrypt($db, (string) pih_get_setting('smtp_password_enc', '', $db));

                try {
                    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mailer->isSMTP();
                    $mailer->Host = $smtpHost;
                    $mailer->Port = $smtpPort;
                    $mailer->SMTPAuth = $smtpAuth === '1';
                    $mailer->Username = $smtpUsername;
                    $mailer->Password = $decryptedPassword;
                    $mailer->Timeout = $smtpTimeout;

                    if ($smtpEncryption === 'ssl') {
                        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } elseif ($smtpEncryption === 'tls' || $smtpEncryption === 'starttls') {
                        $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    }

                    $mailer->setFrom($smtpFromEmail, $smtpFromName !== '' ? $smtpFromName : 'Private Imaging Healthcare');
                    $mailer->addAddress($smtpTestTo);
                    $mailer->isHTML(true);
                    $mailer->Subject = 'SMTP Test Message';
                    $mailer->Body = '<p>This is a successful SMTP test email from the admin panel.</p>';
                    $mailer->AltBody = 'This is a successful SMTP test email from the admin panel.';

                    $mailer->send();
                    $message = 'Test email sent successfully to ' . $smtpTestTo . '.';
                } catch (Throwable $th) {
                    $error = 'Test email failed: ' . $th->getMessage();
                }
            }
        }
    }
}

$settings = pih_get_settings_map($db);
$smtpPortCurrent = (int) ($settings['smtp_port'] ?? 587);
$smtpEncryptionCurrent = (string) ($settings['smtp_encryption'] ?? 'starttls');
$smtpAuthCurrent = (string) ($settings['smtp_auth'] ?? '1');

pih_admin_render_start(
    'SMTP Settings',
    'Configure PHPMailer SMTP transport and verify delivery with a test email',
    'smtp_settings',
    [
        ['href' => 'admin_users.php', 'icon' => 'icon-group', 'label' => 'Admin Users'],
        ['href' => 'email_templates.php', 'icon' => 'icon-edit', 'label' => 'Email Templates'],
        ['href' => 'theme_settings.php', 'icon' => 'icon-paint-brush', 'label' => 'Theme Settings'],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>SMTP Mail Delivery</h3>
    </div>
    <div class="module-body">
        <style>
            .smtp-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(260px, 1fr));
                gap: 14px 18px;
                align-items: start;
            }

            .smtp-grid .control-group {
                margin-bottom: 0;
            }

            .smtp-col-span-2 {
                grid-column: 1 / -1;
            }

            .smtp-help-note {
                display: block;
                margin-top: 6px;
                font-size: 11px;
                line-height: 1.4;
                color: #6b7c90;
            }

            .smtp-actions {
                margin-top: 14px;
            }

            .smtp-actions .controls {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            @media (max-width: 900px) {
                .smtp-grid {
                    grid-template-columns: 1fr;
                }

                .smtp-col-span-2 {
                    grid-column: auto;
                }
            }
        </style>

        <?php if ($message !== '') { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form class="form-horizontal row-fluid" method="post" action="">
            <?php echo pih_admin_csrf_input(); ?>

            <div class="smtp-grid">
                <div class="control-group">
                    <label class="control-label">SMTP Host</label>
                    <div class="controls"><input class="span12" type="text" name="smtp_host" value="<?php echo htmlspecialchars((string) ($settings['smtp_host'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <div class="control-group">
                    <label class="control-label">SMTP Port</label>
                    <div class="controls"><input class="span12" type="number" name="smtp_port" value="<?php echo (int) $smtpPortCurrent; ?>"></div>
                </div>

                <div class="control-group">
                    <label class="control-label">Encryption</label>
                    <div class="controls">
                        <select class="span12" name="smtp_encryption">
                            <option value="none" <?php echo $smtpEncryptionCurrent === 'none' ? 'selected' : ''; ?>>None</option>
                            <option value="ssl" <?php echo $smtpEncryptionCurrent === 'ssl' ? 'selected' : ''; ?>>SSL (Implicit TLS)</option>
                            <option value="tls" <?php echo $smtpEncryptionCurrent === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="starttls" <?php echo $smtpEncryptionCurrent === 'starttls' ? 'selected' : ''; ?>>STARTTLS</option>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label">SMTP Auth</label>
                    <div class="controls">
                        <label class="checkbox inline"><input type="checkbox" name="smtp_auth" value="1" <?php echo $smtpAuthCurrent === '1' ? 'checked' : ''; ?>> Require SMTP authentication</label>
                        <span class="smtp-help-note">Optional: turn this off only if your mail provider supports unauthenticated relay from this server.</span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label">SMTP Username</label>
                    <div class="controls"><input class="span12" type="text" name="smtp_username" value="<?php echo htmlspecialchars((string) ($settings['smtp_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <div class="control-group">
                    <label class="control-label">SMTP Password</label>
                    <div class="controls">
                        <input class="span12" type="password" name="smtp_password" value="">
                        <span class="smtp-help-note">Leave blank to keep the existing stored password.</span>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label">From Email</label>
                    <div class="controls"><input class="span12" type="email" name="smtp_from_email" value="<?php echo htmlspecialchars((string) ($settings['smtp_from_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <div class="control-group">
                    <label class="control-label">From Name</label>
                    <div class="controls"><input class="span12" type="text" name="smtp_from_name" value="<?php echo htmlspecialchars((string) ($settings['smtp_from_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
            </div>

            <hr>
            <h4>Test Email</h4>
            <div class="smtp-grid">
                <div class="control-group smtp-col-span-2">
                    <label class="control-label">Recipient</label>
                    <div class="controls"><input class="span12" type="email" name="smtp_test_to" value="<?php echo htmlspecialchars((string) ($settings['smtp_test_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
            </div>

            <div class="control-group smtp-actions">
                <div class="controls">
                    <button type="submit" name="save" value="1" class="btn btn-success">Save SMTP Settings</button>
                    <button type="submit" name="send_test" value="1" class="btn btn-info">Save and Send Test</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php pih_admin_render_end(); ?>
