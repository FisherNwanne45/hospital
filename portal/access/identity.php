<?php
include('adminsession.php');
require_once 'config.php';
require_once __DIR__ . '/../../includes/email.php';
require_once __DIR__ . '/admin_ui.php';

$message = '';
$error = '';

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
if ($db instanceof mysqli) {
    pih_email_ensure_schema($db);
    pih_admin_ensure_patient_name_schema($db);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $targetDir = __DIR__ . '/img/';
        $cid = trim((string) ($_POST['cid'] ?? ''));
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $name = trim(implode(' ', array_filter([$firstName, $lastName], static function ($part) {
            return $part !== '';
        })));
        $rank = trim((string) ($_POST['rank'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $patientEmail = trim((string) ($_POST['patient_email'] ?? $_POST['type'] ?? ''));
        $amt = trim((string) ($_POST['amt'] ?? ''));
        $rate = trim((string) ($_POST['rate'] ?? ''));
        $dur = trim((string) ($_POST['dur'] ?? ''));
        $coldate = trim((string) ($_POST['coldate'] ?? ''));
        $paydate = trim((string) ($_POST['paydate'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? ''));
        $remark = trim((string) ($_POST['remark'] ?? ''));

        if ($cid === '' || $name === '' || !isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
            $error = 'Patient ID, first name or surname, and passport image are required.';
        } else {
            $parts = explode('.', (string) $_FILES['image']['name']);
            $ext = strtolower((string) end($parts));
            $imgname = time() . '.' . $ext;
            $targetFile = $targetDir . $imgname;
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];

            $duplicateCheck = $conn->prepare('SELECT id FROM user WHERE cid = ? LIMIT 1');
            $existing = false;
            if ($duplicateCheck) {
                $duplicateCheck->bind_param('s', $cid);
                $duplicateCheck->execute();
                $dupResult = $duplicateCheck->get_result();
                $existing = $dupResult && $dupResult->num_rows > 0;
                $duplicateCheck->close();
            }

            if ($existing) {
                $error = 'Tracking number already taken.';
            } elseif (!in_array($ext, $allowedTypes, true)) {
                $error = 'Only JPG, JPEG, PNG, GIF, and BMP files are allowed.';
            } elseif ((int) $_FILES['image']['size'] > 2000000) {
                $error = 'The uploaded file is too large.';
            } elseif (getimagesize($_FILES['image']['tmp_name']) === false) {
                $error = 'Uploaded file is not a valid image.';
            } else {
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $stmt = $conn->prepare('INSERT INTO user (image, cid, first_name, last_name, name, rank, phone, patient_email, type, amt, rate, dur, coldate, paydate, status, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('ssssssssssssssss', $imgname, $cid, $firstName, $lastName, $name, $rank, $phone, $patientEmail, $patientEmail, $amt, $rate, $dur, $coldate, $paydate, $status, $remark);
                        if ($stmt->execute()) {
                            $message = 'Patient record created successfully.';

                            pih_admin_log_action(
                                $db,
                                'patient_create',
                                'patient',
                                $cid,
                                'Created patient record with fields: Patient ID, First Name, Surname, Patient Full Name, Patient Illness, Patient Treatment, Doctor Name, Patient Email, Nurse Name, Hospital Ward Number, Hospital Room Number, Total On Going Cost, Doctor\'s Comments, Remarks'
                            );

                            if ($patientEmail !== '' && filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
                                $template = pih_email_get_template($db, 'patient_created');
                                if ($template) {
                                    $templateVars = [
                                        'site_name' => (string) pih_get_setting('site_name', 'Private Imaging Healthcare', $db),
                                        'patient_name' => $name,
                                        'patient_email' => $patientEmail,
                                        'patient_id' => $cid,
                                        'doctor_name' => $coldate,
                                    ];
                                    $subject = pih_email_render_string((string) $template['subject'], $templateVars, false);
                                    $htmlBody = pih_email_render_string((string) $template['html_body'], $templateVars, true);
                                    $textBody = pih_email_render_string((string) $template['text_body'], $templateVars, false);
                                    $sendResult = pih_email_send_message($db, $patientEmail, $subject, $htmlBody, $textBody);
                                    if ($sendResult['success']) {
                                        $message .= ' Email notification sent to ' . $patientEmail . '.';
                                    } else {
                                        $message .= ' Email notification could not be sent: ' . $sendResult['message'];
                                    }
                                }
                            }
                        } else {
                            $error = 'Unable to create patient record.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Unable to prepare patient record insertion.';
                    }
                } else {
                    $error = 'Unable to upload passport image.';
                }
            }
        }
    }
}

pih_admin_render_start(
    'Create Patient',
    'Add a new patient record, doctor assignment, ward details, and passport image',
    'identity',
    [
        ['href' => 'index.php', 'icon' => 'icon-dashboard', 'label' => 'Dashboard'],
        ['href' => 'site_settings.php', 'icon' => 'icon-cog', 'label' => 'Site Settings'],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>Create New Patient</h3>
    </div>
    <div class="module-body">
        <?php if ($message !== '') { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form class="form-horizontal row-fluid" method="post" action="identity.php" enctype="multipart/form-data">
            <?php echo pih_admin_csrf_input(); ?>
            <div class="control-group">
                <label class="control-label" for="cid">Patient ID No.</label>
                <div class="controls"><input type="text" name="cid" id="cid" placeholder="Enter Patient ID No." class="span8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="image">Passport</label>
                <div class="controls"><input type="file" name="image" id="image" class="span8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="first_name">First Name</label>
                <div class="controls"><input type="text" name="first_name" id="first_name" placeholder="Enter patient's first name" class="span8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="last_name">Surname</label>
                <div class="controls"><input type="text" name="last_name" id="last_name" placeholder="Enter patient's surname" class="span8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="rank">Patient Illness</label>
                <div class="controls"><input type="text" name="rank" id="rank" placeholder="Enter Patient Illness" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="phone">Patient Treatment</label>
                <div class="controls"><input type="text" name="phone" id="phone" placeholder="Enter Patient Treatment" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="coldate">Doctor Name</label>
                <div class="controls"><input type="text" name="coldate" id="coldate" placeholder="e.g Dr Dre" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="patient_email">Patient Email</label>
                <div class="controls"><input type="email" name="patient_email" id="patient_email" placeholder="Enter the patient email" class="span8"></div>
            </div>

            <hr>
            <h4>Ward Information</h4>

            <div class="control-group">
                <label class="control-label" for="status">Nurse Name</label>
                <div class="controls"><input type="text" name="status" id="status" placeholder="Enter Nurse Name" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="amt">Hospital Ward Number</label>
                <div class="controls"><input type="text" name="amt" id="amt" placeholder="Enter Hospital Ward Number" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="rate">Hospital Room Number</label>
                <div class="controls"><input type="text" name="rate" id="rate" placeholder="Enter Hospital Room Number" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="paydate">Total On Going Cost</label>
                <div class="controls"><input type="text" name="paydate" id="paydate" placeholder="e.g $2000" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="dur">Doctor's Comments</label>
                <div class="controls"><textarea name="dur" id="dur" placeholder="Enter Doctor's Comments ..." class="span8" rows="4"></textarea></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="remark">Remarks</label>
                <div class="controls"><textarea name="remark" id="remark" placeholder="Additional notes or remarks" class="span8" rows="4"></textarea></div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <button name="save" type="submit" class="btn btn-success">Create Patient</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php pih_admin_render_end(); ?>
