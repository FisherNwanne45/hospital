<?php
include('adminsession.php');
require_once 'config.php';
require_once __DIR__ . '/../../includes/email.php';
require_once __DIR__ . '/admin_ui.php';

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
$message = '';
$error = '';

if ($db instanceof mysqli) {
    pih_email_ensure_schema($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!($db instanceof mysqli)) {
        $error = 'Database connection is unavailable.';
    } elseif (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $templateKey = trim((string) ($_POST['template_key'] ?? ''));
        $action = isset($_POST['reset_template']) ? 'reset' : 'save';
        $defaults = pih_email_default_template($templateKey);

        if ($templateKey === '') {
            $error = 'No template was selected.';
        } elseif ($action === 'reset') {
            if (pih_email_save_template($db, $defaults)) {
                $message = 'Template reset to its default content.';
            } else {
                $error = 'Unable to reset the template.';
            }
        } else {
            $template = [
                'template_key' => $templateKey,
                'template_name' => trim((string) ($_POST['template_name'] ?? $defaults['template_name'])),
                'subject' => trim((string) ($_POST['subject'] ?? $defaults['subject'])),
                'html_body' => trim((string) ($_POST['html_body'] ?? $defaults['html_body'])),
                'text_body' => trim((string) ($_POST['text_body'] ?? $defaults['text_body'])),
            ];

            if ($template['template_name'] === '' || $template['subject'] === '' || $template['html_body'] === '' || $template['text_body'] === '') {
                $error = 'Template name, subject, HTML body, and text body are required.';
            } elseif (pih_email_save_template($db, $template)) {
                $message = 'Template saved successfully.';
            } else {
                $error = 'Unable to save the template.';
            }
        }
    }
}

$templates = $db instanceof mysqli ? pih_email_get_templates($db) : [];

pih_admin_render_start(
    'Email Templates',
    'Edit branded HTML templates used by patient and contact notifications',
    'email_templates',
    [
        ['href' => 'index.php', 'icon' => 'icon-dashboard', 'label' => 'Dashboard'],
        ['href' => 'smtp_settings.php', 'icon' => 'icon-envelope', 'label' => 'SMTP Settings'],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>Email Template Library</h3>
    </div>
    <div class="module-body">
        <style>
            .template-grid {
                display: grid;
                gap: 18px;
            }

            .template-card {
                border: 1px solid #dce6f2;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
                overflow: hidden;
            }

            .template-card-head {
                padding: 16px 18px;
                background: #f7fbff;
                border-bottom: 1px solid #e4ecf6;
            }

            .template-card-head h4 {
                margin: 0;
                font-size: 18px;
            }

            .template-card-head p {
                margin: 4px 0 0;
                color: #66758c;
                font-size: 13px;
            }

            .template-card-body {
                padding: 18px;
            }

            .template-fields {
                display: grid;
                gap: 14px;
            }

            .template-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                margin-top: 12px;
            }

            .template-help {
                margin: 0 0 18px;
                color: #66758c;
                font-size: 13px;
                line-height: 1.6;
            }

            .template-placeholder-list {
                margin: 0 0 20px;
                padding: 14px 16px;
                background: #f7fbff;
                border: 1px solid #dbe5f0;
                border-radius: 14px;
                color: #32506e;
                font-size: 13px;
                line-height: 1.7;
            }
        </style>

        <?php if ($message !== '') { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <p class="template-help">Use these templates for PHPMailer-based notifications. The HTML body is wrapped with the portal branding, logo, and footer disclaimer when an email is sent.</p>
        <div class="template-placeholder-list">
            <strong>Common placeholders:</strong> {{site_name}}, {{patient_name}}, {{patient_email}}, {{patient_id}}, {{doctor_name}}, {{change_list_html}}, {{change_list_text}}, {{contact_name}}, {{contact_email}}, {{contact_phone}}, {{contact_message}}
        </div>

        <div class="template-grid">
            <?php foreach ($templates as $template) { ?>
                <div class="template-card">
                    <div class="template-card-head">
                        <h4><?php echo htmlspecialchars((string) ($template['template_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p>Template key: <?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="template-card-body">
                        <form method="post" action="">
                            <?php echo pih_admin_csrf_input(); ?>
                            <input type="hidden" name="template_key" value="<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="template-fields">
                                <div class="control-group">
                                    <label class="control-label" for="template_name_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Template Name</label>
                                    <div class="controls"><input class="span12" type="text" id="template_name_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" name="template_name" value="<?php echo htmlspecialchars((string) ($template['template_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="subject_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Subject</label>
                                    <div class="controls"><input class="span12" type="text" id="subject_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" name="subject" value="<?php echo htmlspecialchars((string) ($template['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="html_body_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">HTML Body</label>
                                    <div class="controls"><textarea class="span12" rows="8" id="html_body_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" name="html_body"><?php echo htmlspecialchars((string) ($template['html_body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="text_body_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Text Body</label>
                                    <div class="controls"><textarea class="span12" rows="6" id="text_body_<?php echo htmlspecialchars((string) ($template['template_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" name="text_body"><?php echo htmlspecialchars((string) ($template['text_body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                                </div>
                            </div>
                            <div class="template-actions">
                                <button type="submit" name="save_template" value="1" class="btn btn-success">Save Template</button>
                                <button type="submit" name="reset_template" value="1" class="btn btn-warning" onclick="return confirm('Reset this template to its default content?');">Reset to Default</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php pih_admin_render_end(); ?>