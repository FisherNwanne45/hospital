CREATE TABLE IF NOT EXISTS email_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    template_name VARCHAR(190) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html_body MEDIUMTEXT NOT NULL,
    text_body MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `user`
    ADD COLUMN `patient_email` VARCHAR(255) NULL AFTER `type`;

UPDATE `user`
SET `patient_email` = NULLIF(TRIM(`type`), '')
WHERE (`patient_email` IS NULL OR TRIM(`patient_email`) = '')
  AND TRIM(`type`) <> '';

INSERT INTO email_templates (template_key, template_name, subject, html_body, text_body) VALUES
('patient_created', 'Patient Created Notification', 'Your patient record has been created at {{site_name}}', '<p>Hello {{patient_name}},</p><p>Your patient record has been created at <strong>{{site_name}}</strong>.</p><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px 0;"><strong>Patient ID</strong></td><td style="padding:6px 0;">{{patient_id}}</td></tr><tr><td style="padding:6px 0;"><strong>Patient Email</strong></td><td style="padding:6px 0;">{{patient_email}}</td></tr><tr><td style="padding:6px 0;"><strong>Doctor Name</strong></td><td style="padding:6px 0;">{{doctor_name}}</td></tr></table><p>If you did not expect this message, please contact {{site_name}}.</p>', 'Hello {{patient_name}},\n\nYour patient record has been created at {{site_name}}.\n\nPatient ID: {{patient_id}}\nPatient Email: {{patient_email}}\nDoctor Name: {{doctor_name}}\n\nIf you did not expect this message, please contact {{site_name}}.')
ON DUPLICATE KEY UPDATE template_name = VALUES(template_name), subject = VALUES(subject), html_body = VALUES(html_body), text_body = VALUES(text_body);

INSERT INTO email_templates (template_key, template_name, subject, html_body, text_body) VALUES
('patient_updated', 'Patient Updated Notification', 'Your patient record was updated at {{site_name}}', '<p>Hello {{patient_name}},</p><p>Your patient record was updated at <strong>{{site_name}}</strong>.</p><p>The following details changed:</p>{{change_list_html}}<p>Updated by: {{updated_by}}</p>', 'Hello {{patient_name}},\n\nYour patient record was updated at {{site_name}}.\n\nThe following details changed:\n{{change_list_text}}\nUpdated by: {{updated_by}}'),
('contact_clinic', 'Clinic Contact Form Notification', 'New clinic contact request from {{contact_name}}', '<p>A new contact request was submitted through the clinic contact page.</p><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px 0;"><strong>Name</strong></td><td style="padding:6px 0;">{{contact_name}}</td></tr><tr><td style="padding:6px 0;"><strong>Email</strong></td><td style="padding:6px 0;">{{contact_email}}</td></tr><tr><td style="padding:6px 0;"><strong>Phone</strong></td><td style="padding:6px 0;">{{contact_phone}}</td></tr><tr><td style="padding:6px 0;"><strong>Message</strong></td><td style="padding:6px 0;">{{contact_message}}</td></tr></table>', 'A new contact request was submitted through the clinic contact page.\n\nName: {{contact_name}}\nEmail: {{contact_email}}\nPhone: {{contact_phone}}\nMessage: {{contact_message}}'),
('contact_hospital', 'Hospital Contact Form Notification', 'New hospital contact request from {{contact_name}}', '<p>A new contact request was submitted through the hospital contact page.</p><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:6px 0;"><strong>Name</strong></td><td style="padding:6px 0;">{{contact_name}}</td></tr><tr><td style="padding:6px 0;"><strong>Email</strong></td><td style="padding:6px 0;">{{contact_email}}</td></tr><tr><td style="padding:6px 0;"><strong>Phone</strong></td><td style="padding:6px 0;">{{contact_phone}}</td></tr><tr><td style="padding:6px 0;"><strong>Message</strong></td><td style="padding:6px 0;">{{contact_message}}</td></tr></table>', 'A new contact request was submitted through the hospital contact page.\n\nName: {{contact_name}}\nEmail: {{contact_email}}\nPhone: {{contact_phone}}\nMessage: {{contact_message}}')
ON DUPLICATE KEY UPDATE template_name = VALUES(template_name), subject = VALUES(subject), html_body = VALUES(html_body), text_body = VALUES(text_body);
