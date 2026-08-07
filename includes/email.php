<?php

require_once __DIR__ . '/settings.php';

if (!function_exists('pih_smtp_secret')) {
    function pih_smtp_secret(mysqli $db): string
    {
        $siteHost = (string) pih_get_setting('site_host', 'privateimaginghealthcare.com', $db);
        return hash('sha256', $siteHost . '|' . __DIR__ . '|pih-smtp-secret', true);
    }
}

if (!function_exists('pih_smtp_encrypt')) {
    function pih_smtp_encrypt(mysqli $db, string $plainText): string
    {
        if ($plainText === '' || !function_exists('openssl_encrypt')) {
            return '';
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plainText, 'aes-256-cbc', pih_smtp_secret($db), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return '';
        }

        return base64_encode($iv . $cipher);
    }
}

if (!function_exists('pih_smtp_decrypt')) {
    function pih_smtp_decrypt(mysqli $db, string $encoded): string
    {
        if ($encoded === '' || !function_exists('openssl_decrypt')) {
            return '';
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) <= 16) {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', pih_smtp_secret($db), OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : (string) $plain;
    }
}

function pih_email_table_exists(mysqli $db, string $tableName): bool
{
    $stmt = $db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function pih_email_column_exists(mysqli $db, string $tableName, string $columnName): bool
{
    $stmt = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result instanceof mysqli_result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function pih_email_default_templates(): array
{
    return [
        'patient_created' => [
            'template_key' => 'patient_created',
            'template_name' => 'Patient Created Notification',
            'subject' => 'Your patient record has been created at {{site_name}}',
            'html_body' => '<p>Hello {{patient_name}},</p><p>Your patient record has been created at <strong>{{site_name}}</strong>.</p><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px 0;"><strong>Patient ID</strong></td><td style="padding:6px 0;">{{patient_id}}</td></tr><tr><td style="padding:6px 0;"><strong>Patient Email</strong></td><td style="padding:6px 0;">{{patient_email}}</td></tr><tr><td style="padding:6px 0;"><strong>Doctor Name</strong></td><td style="padding:6px 0;">{{doctor_name}}</td></tr></table><p>If you did not expect this message, please contact {{site_name}}.</p>',
            'text_body' => "Hello {{patient_name}},\n\nYour patient record has been created at {{site_name}}.\n\nPatient ID: {{patient_id}}\nPatient Email: {{patient_email}}\nDoctor Name: {{doctor_name}}\n\nIf you did not expect this message, please contact {{site_name}}.",
        ],
        'patient_updated' => [
            'template_key' => 'patient_updated',
            'template_name' => 'Patient Updated Notification',
            'subject' => 'Your patient record was updated at {{site_name}}',
            'html_body' => '<p>Hello {{patient_name}},</p><p>Your patient record was updated at <strong>{{site_name}}</strong>.</p><p>Please log in to the patient portal to review the latest updates.</p><p>The updated fields are:</p>{{change_list_html}}',
            'text_body' => "Hello {{patient_name}},\n\nYour patient record was updated at {{site_name}}. Please log in to the patient portal to review the latest updates.\n\nThe updated fields are:\n{{change_list_text}}",
        ],
        'contact_clinic' => [
            'template_key' => 'contact_clinic',
            'template_name' => 'Clinic Contact Form Notification',
            'subject' => 'New clinic contact request from {{contact_name}}',
            'html_body' => '<p>A new contact request was submitted through the clinic contact page.</p><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px 0;"><strong>Name</strong></td><td style="padding:6px 0;">{{contact_name}}</td></tr><tr><td style="padding:6px 0;"><strong>Email</strong></td><td style="padding:6px 0;">{{contact_email}}</td></tr><tr><td style="padding:6px 0;"><strong>Phone</strong></td><td style="padding:6px 0;">{{contact_phone}}</td></tr><tr><td style="padding:6px 0;"><strong>Message</strong></td><td style="padding:6px 0;">{{contact_message}}</td></tr></table>',
            'text_body' => "A new contact request was submitted through the clinic contact page.\n\nName: {{contact_name}}\nEmail: {{contact_email}}\nPhone: {{contact_phone}}\nMessage: {{contact_message}}",
        ],
        'contact_hospital' => [
            'template_key' => 'contact_hospital',
            'template_name' => 'Hospital Contact Form Notification',
            'subject' => 'New hospital contact request from {{contact_name}}',
            'html_body' => '<p>A new contact request was submitted through the hospital contact page.</p><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px 0;"><strong>Name</strong></td><td style="padding:6px 0;">{{contact_name}}</td></tr><tr><td style="padding:6px 0;"><strong>Email</strong></td><td style="padding:6px 0;">{{contact_email}}</td></tr><tr><td style="padding:6px 0;"><strong>Phone</strong></td><td style="padding:6px 0;">{{contact_phone}}</td></tr><tr><td style="padding:6px 0;"><strong>Message</strong></td><td style="padding:6px 0;">{{contact_message}}</td></tr></table>',
            'text_body' => "A new contact request was submitted through the hospital contact page.\n\nName: {{contact_name}}\nEmail: {{contact_email}}\nPhone: {{contact_phone}}\nMessage: {{contact_message}}",
        ],
    ];
}

function pih_email_default_template(string $templateKey): array
{
    $templates = pih_email_default_templates();
    return $templates[$templateKey] ?? [
        'template_key' => $templateKey,
        'template_name' => ucwords(str_replace(['-', '_'], ' ', $templateKey)),
        'subject' => '{{site_name}} notification',
        'html_body' => '<p>{{content}}</p>',
        'text_body' => '{{content}}',
    ];
}

function pih_email_ensure_schema(mysqli $db): bool
{
    static $done = false;

    if ($done) {
        return true;
    }

    if (!pih_email_table_exists($db, 'email_templates')) {
        $createSql = "CREATE TABLE IF NOT EXISTS email_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_key VARCHAR(100) NOT NULL UNIQUE,
            template_name VARCHAR(190) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            html_body MEDIUMTEXT NOT NULL,
            text_body MEDIUMTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$db->query($createSql)) {
            return false;
        }
    }

    if (pih_email_table_exists($db, 'user')) {
        if (!pih_email_column_exists($db, 'user', 'patient_email')) {
            if (!$db->query('ALTER TABLE `user` ADD COLUMN `patient_email` VARCHAR(255) NULL AFTER `type`')) {
                return false;
            }
        }

        if (pih_email_column_exists($db, 'user', 'patient_email') && pih_email_column_exists($db, 'user', 'type')) {
            $db->query("UPDATE `user` SET `patient_email` = NULLIF(TRIM(`type`), '') WHERE (`patient_email` IS NULL OR TRIM(`patient_email`) = '') AND TRIM(`type`) <> ''");
        }
    }

    pih_email_seed_templates($db);
    $done = true;

    return true;
}

function pih_email_seed_templates(mysqli $db): void
{
    $defaults = pih_email_default_templates();

    $stmt = $db->prepare('INSERT INTO email_templates (template_key, template_name, subject, html_body, text_body) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE template_name = VALUES(template_name), subject = VALUES(subject), html_body = VALUES(html_body), text_body = VALUES(text_body)');
    if (!$stmt) {
        return;
    }

    foreach ($defaults as $template) {
        $stmt->bind_param(
            'sssss',
            $template['template_key'],
            $template['template_name'],
            $template['subject'],
            $template['html_body'],
            $template['text_body']
        );
        $stmt->execute();
    }

    $stmt->close();
}

function pih_email_get_template(mysqli $db, string $templateKey): ?array
{
    $stmt = $db->prepare('SELECT template_key, template_name, subject, html_body, text_body, updated_at FROM email_templates WHERE template_key = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $templateKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (is_array($row)) {
        return $row;
    }

    $fallback = pih_email_default_template($templateKey);
    return [
        'template_key' => $fallback['template_key'],
        'template_name' => $fallback['template_name'],
        'subject' => $fallback['subject'],
        'html_body' => $fallback['html_body'],
        'text_body' => $fallback['text_body'],
        'updated_at' => null,
    ];
}

function pih_email_get_templates(mysqli $db): array
{
    $templates = [];
    $result = $db->query('SELECT template_key, template_name, subject, html_body, text_body, updated_at FROM email_templates ORDER BY template_key ASC');
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
    }

    if ($templates === []) {
        foreach (pih_email_default_templates() as $template) {
            $templates[] = [
                'template_key' => $template['template_key'],
                'template_name' => $template['template_name'],
                'subject' => $template['subject'],
                'html_body' => $template['html_body'],
                'text_body' => $template['text_body'],
                'updated_at' => null,
            ];
        }
    }

    return $templates;
}

function pih_email_save_template(mysqli $db, array $template): bool
{
    $stmt = $db->prepare('INSERT INTO email_templates (template_key, template_name, subject, html_body, text_body) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE template_name = VALUES(template_name), subject = VALUES(subject), html_body = VALUES(html_body), text_body = VALUES(text_body)');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'sssss',
        $template['template_key'],
        $template['template_name'],
        $template['subject'],
        $template['html_body'],
        $template['text_body']
    );
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

function pih_email_render_string(string $template, array $variables, bool $escapeHtml = true, array $rawKeys = []): string
{
    $replacements = [];

    foreach ($variables as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        if (in_array($key, $rawKeys, true)) {
            $replacements[$placeholder] = (string) $value;
            continue;
        }

        $stringValue = is_scalar($value) ? (string) $value : '';
        $replacements[$placeholder] = $escapeHtml ? htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8') : $stringValue;
    }

    return strtr($template, $replacements);
}

function pih_email_html_wrapper(mysqli $db, string $innerHtml, string $subject = ''): string
{
    $settings = pih_get_settings_map($db);
    $siteName = htmlspecialchars((string) ($settings['site_name'] ?? 'Private Imaging Healthcare'), ENT_QUOTES, 'UTF-8');
    $siteUrl = rtrim((string) ($settings['site_url'] ?? ''), '/');
    $logoPath = trim((string) ($settings['site_logo_path'] ?? ''));
    $disclaimer = 'This is an automated email from ' . $siteName . '. Please do not reply directly to this message.';

    if ($logoPath !== '') {
        if (preg_match('/^https?:\/\//i', $logoPath)) {
            $logoUrl = $logoPath;
        } elseif ($siteUrl !== '') {
            $logoUrl = $siteUrl . '/' . ltrim($logoPath, '/');
        } else {
            $logoUrl = $logoPath;
        }
    } else {
        $logoUrl = '';
    }

    $subjectEscaped = htmlspecialchars($subject !== '' ? $subject : $siteName, ENT_QUOTES, 'UTF-8');
    $logoMarkup = $logoUrl !== '' ? '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $siteName . '" style="max-width:180px;height:auto;display:block;margin:0 auto 18px;">' : '<div style="text-align:center;font-size:22px;font-weight:800;color:#113f6b;margin-bottom:18px;">' . $siteName . '</div>';

    return '<!doctype html><html><body style="margin:0;padding:0;background:#eef4fa;font-family:Arial,Helvetica,sans-serif;color:#102a43;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef4fa;padding:32px 16px;"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #dce6f2;box-shadow:0 18px 32px rgba(15,23,42,0.08);"><tr><td style="padding:28px 28px 0;">' . $logoMarkup . '</td></tr><tr><td style="padding:0 28px 16px;"><h1 style="margin:0;font-size:24px;line-height:1.3;color:#113f6b;">' . $subjectEscaped . '</h1></td></tr><tr><td style="padding:0 28px 28px;font-size:15px;line-height:1.7;color:#24415f;">' . $innerHtml . '</td></tr><tr><td style="padding:18px 28px;background:#f7fbff;border-top:1px solid #e5eef7;font-size:12px;line-height:1.6;color:#6b7c90;">' . htmlspecialchars($disclaimer, ENT_QUOTES, 'UTF-8') . '</td></tr></table></td></tr></table></body></html>';
}

function pih_email_normalize_text(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
}

function pih_contact_extract_value(array $source, array $keys): string
{
    foreach ($source as $fieldKey => $fieldValue) {
        $normalizedKey = strtolower((string) $fieldKey);

        foreach ($keys as $key) {
            $needle = strtolower((string) $key);
            if ($normalizedKey === $needle || str_ends_with($normalizedKey, '-' . $needle) || str_ends_with($normalizedKey, '_' . $needle) || str_contains($normalizedKey, $needle)) {
                if (is_array($fieldValue)) {
                    continue;
                }

                $value = trim((string) $fieldValue);
                if ($value !== '') {
                    return $value;
                }
            }
        }
    }

    return '';
}

function pih_contact_recipient_email(mysqli $db): string
{
    $settings = pih_get_settings_map($db);
    $primary = trim((string) ($settings['site_email_primary'] ?? ''));
    if ($primary !== '' && filter_var($primary, FILTER_VALIDATE_EMAIL)) {
        return $primary;
    }

    $secondary = trim((string) ($settings['site_email_secondary'] ?? ''));
    if ($secondary !== '' && filter_var($secondary, FILTER_VALIDATE_EMAIL)) {
        return $secondary;
    }

    return '';
}

function pih_contact_handle_submission(mysqli $db, string $templateKey, array $source): array
{
    if (!pih_email_ensure_schema($db)) {
        return ['success' => false, 'message' => 'Email templates are unavailable.'];
    }

    $contactName = pih_contact_extract_value($source, ['first-name', 'last-name', 'name']);
    $contactEmail = pih_contact_extract_value($source, ['email-address', 'email']);
    $contactPhone = pih_contact_extract_value($source, ['tel-number', 'phone', 'telephone']);
    $contactMessage = pih_contact_extract_value($source, ['message', 'type-your-message']);

    if ($contactName === '' || $contactEmail === '' || $contactMessage === '') {
        return ['success' => false, 'message' => 'Please complete your name, email, and message.'];
    }

    if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $recipientEmail = pih_contact_recipient_email($db);
    if ($recipientEmail === '') {
        return ['success' => false, 'message' => 'A contact recipient email address is not configured.'];
    }

    $template = pih_email_get_template($db, $templateKey);
    if (!is_array($template)) {
        $template = pih_email_default_template($templateKey);
    }

    $variables = [
        'site_name' => (string) pih_get_setting('site_name', 'Private Imaging Healthcare', $db),
        'contact_name' => $contactName,
        'contact_email' => $contactEmail,
        'contact_phone' => $contactPhone,
        'contact_message' => $contactMessage,
    ];

    $subject = pih_email_render_string((string) ($template['subject'] ?? ''), $variables);
    $htmlBody = pih_email_render_string((string) ($template['html_body'] ?? ''), $variables, true);
    $textBody = pih_email_render_string((string) ($template['text_body'] ?? ''), $variables, false);

    return pih_email_send_message(
        $db,
        $recipientEmail,
        $subject,
        $htmlBody,
        $textBody,
        [
            'reply_to' => $contactEmail,
            'reply_to_name' => $contactName,
        ]
    );
}

function pih_email_send_message(mysqli $db, string $recipientEmail, string $subject, string $htmlBody, string $textBody = '', array $options = []): array
{
    $recipientEmail = trim($recipientEmail);
    $subject = trim($subject);

    if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid recipient email address is required.'];
    }

    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return ['success' => false, 'message' => 'PHPMailer is not installed.'];
    }

    $settings = pih_get_settings_map($db);
    $smtpHost = trim((string) ($settings['smtp_host'] ?? ''));
    $smtpPort = (int) ($settings['smtp_port'] ?? 587);
    $smtpUsername = trim((string) ($settings['smtp_username'] ?? ''));
    $smtpPassword = pih_smtp_decrypt($db, (string) ($settings['smtp_password_enc'] ?? ''));
    $smtpFromEmail = trim((string) ($settings['smtp_from_email'] ?? ''));
    $smtpFromName = trim((string) ($settings['smtp_from_name'] ?? ''));
    $smtpAuth = (string) ($settings['smtp_auth'] ?? '1') === '1';
    $smtpEncryption = strtolower(trim((string) ($settings['smtp_encryption'] ?? 'starttls')));
    $smtpTimeout = (int) ($settings['smtp_timeout'] ?? 30);

    if ($smtpHost === '' || $smtpFromEmail === '') {
        return ['success' => false, 'message' => 'SMTP host and from email must be configured before sending mail.'];
    }

    try {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->Port = $smtpPort > 0 ? $smtpPort : 587;
        $mailer->SMTPAuth = $smtpAuth;
        $mailer->Username = $smtpUsername;
        $mailer->Password = $smtpPassword;
        $mailer->Timeout = $smtpTimeout > 0 ? $smtpTimeout : 30;

        if ($smtpEncryption === 'ssl') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtpEncryption === 'tls' || $smtpEncryption === 'starttls') {
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($smtpFromEmail, $smtpFromName !== '' ? $smtpFromName : 'Private Imaging Healthcare');
        $mailer->addAddress($recipientEmail);

        if (!empty($options['reply_to']) && filter_var((string) $options['reply_to'], FILTER_VALIDATE_EMAIL)) {
            $replyName = isset($options['reply_to_name']) ? (string) $options['reply_to_name'] : '';
            $mailer->addReplyTo((string) $options['reply_to'], $replyName);
        }

        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = pih_email_html_wrapper($db, $htmlBody, $subject);
        $mailer->AltBody = $textBody !== '' ? $textBody : pih_email_normalize_text($htmlBody);
        $mailer->send();

        return ['success' => true, 'message' => 'Message sent successfully.'];
    } catch (Throwable $th) {
        return ['success' => false, 'message' => $th->getMessage()];
    }
}
