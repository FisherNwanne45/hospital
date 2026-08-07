<?php
include('adminsession.php');
include_once 'config.php';
require_once __DIR__ . '/../../includes/email.php';
require_once __DIR__ . '/admin_ui.php';

$message = '';
$error = '';
$recordId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
if ($db instanceof mysqli) {
    pih_email_ensure_schema($db);
    pih_admin_ensure_patient_name_schema($db);
}

function pih_patient_fetch_record(mysqli $db, int $recordId): ?array
{
    if ($recordId <= 0) {
        return null;
    }

    $load = $db->prepare('SELECT * FROM user WHERE id = ? LIMIT 1');
    if (!$load) {
        return null;
    }

    $load->bind_param('i', $recordId);
    $load->execute();
    $result = $load->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $load->close();

    return is_array($row) ? $row : null;
}

function pih_patient_value(?array $row, string $key): string
{
    return trim((string) ($row[$key] ?? ''));
}

function pih_patient_split_name(string $fullName): array
{
    $fullName = trim($fullName);
    if ($fullName === '') {
        return ['', ''];
    }

    $parts = preg_split('/\s+/', $fullName) ?: [];
    if ($parts === []) {
        return ['', ''];
    }

    $first = (string) array_shift($parts);
    $last = trim(implode(' ', $parts));
    return [$first, $last];
}

$row = $recordId > 0 && $db instanceof mysqli ? pih_patient_fetch_record($db, $recordId) : null;

if (count($_POST) > 0) {
    if (!pih_admin_validate_csrf()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $updateId = (int) ($_POST['id'] ?? 0);
        $beforeRow = $db instanceof mysqli ? pih_patient_fetch_record($db, $updateId) : null;
        $stmt = $conn->prepare('UPDATE user SET coldate = ?, amt = ?, patient_email = ?, type = ?, dur = ?, rate = ?, phone = ?, first_name = ?, last_name = ?, name = ?, rank = ?, cid = ?, status = ?, paydate = ? WHERE id = ?');

        if ($stmt) {
            $coldate = trim((string) ($_POST['coldate'] ?? ''));
            $amt = trim((string) ($_POST['amt'] ?? ''));
            $patientEmail = trim((string) ($_POST['patient_email'] ?? $_POST['type'] ?? ''));
            $dur = trim((string) ($_POST['dur'] ?? ''));
            $rate = trim((string) ($_POST['rate'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $name = trim(implode(' ', array_filter([$firstName, $lastName], static function ($part) {
                return $part !== '';
            })));
            $rank = trim((string) ($_POST['rank'] ?? ''));
            $cid = trim((string) ($_POST['cid'] ?? ''));
            $status = trim((string) ($_POST['status'] ?? ''));
            $paydate = trim((string) ($_POST['paydate'] ?? ''));
            $stmt->bind_param('ssssssssssssssi', $coldate, $amt, $patientEmail, $patientEmail, $dur, $rate, $phone, $firstName, $lastName, $name, $rank, $cid, $status, $paydate, $updateId);

            if ($stmt->execute()) {
                $message = 'Patient record updated successfully.';
                $recordId = $updateId;

                $afterRow = $db instanceof mysqli ? pih_patient_fetch_record($db, $updateId) : null;
                $changedFields = [];
                $trackedFields = [
                    'cid' => 'Patient ID Number',
                    'first_name' => 'First Name',
                    'last_name' => 'Surname',
                    'name' => 'Patient Full Name',
                    'rank' => 'Patient Illness',
                    'phone' => 'Patient Treatment',
                    'coldate' => 'Doctor Name',
                    'patient_email' => 'Patient Email',
                    'status' => 'Nurse Name',
                    'amt' => 'Hospital Ward Number',
                    'rate' => 'Hospital Room Number',
                    'paydate' => 'Total On Going Cost',
                    'dur' => "Doctor's Comments",
                ];

                foreach ($trackedFields as $field => $label) {
                    $beforeValue = pih_patient_value($beforeRow, $field);
                    $afterValue = pih_patient_value($afterRow, $field);
                    if ($beforeValue !== $afterValue) {
                        $changedFields[] = $label;
                    }
                }

                pih_admin_log_action(
                    $db,
                    'patient_update',
                    'patient',
                    (string) $cid,
                    $changedFields !== []
                        ? 'Updated fields: ' . implode(', ', $changedFields)
                        : 'Saved patient record with no tracked field changes'
                );

                $recipientEmail = '';
                if ($patientEmail !== '' && filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
                    $recipientEmail = $patientEmail;
                } else {
                    $fallbackEmail = trim((string) ($beforeRow['patient_email'] ?? $beforeRow['type'] ?? ''));
                    if ($fallbackEmail !== '' && filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
                        $recipientEmail = $fallbackEmail;
                    }
                }

                if ($recipientEmail !== '' && $changedFields !== []) {
                    $template = pih_email_get_template($db, 'patient_updated');
                    if ($template) {
                        $changeListHtml = '<ul style="margin:16px 0 0;padding-left:20px;">';
                        $changeListText = '';
                        foreach ($changedFields as $changeLabel) {
                            $changeListHtml .= '<li style="margin-bottom:8px;"><strong>' . htmlspecialchars($changeLabel, ENT_QUOTES, 'UTF-8') . '</strong></li>';
                            $changeListText .= $changeLabel . "\n";
                        }
                        $changeListHtml .= '</ul>';

                        $templateVars = [
                            'site_name' => (string) pih_get_setting('site_name', 'Private Imaging Healthcare', $db),
                            'patient_name' => $name,
                            'patient_email' => $patientEmail !== '' ? $patientEmail : $recipientEmail,
                            'patient_id' => $cid,
                            'change_list_html' => $changeListHtml,
                            'change_list_text' => trim($changeListText),
                        ];
                        $subject = pih_email_render_string((string) $template['subject'], $templateVars, false);
                        $htmlBody = pih_email_render_string((string) $template['html_body'], $templateVars, true, ['change_list_html']);
                        $textBody = pih_email_render_string((string) $template['text_body'], $templateVars, false);
                        $sendResult = pih_email_send_message($db, $recipientEmail, $subject, $htmlBody, $textBody);
                        if ($sendResult['success']) {
                            $message .= ' Update notification sent to ' . $recipientEmail . '.';
                        } else {
                            $message .= ' Update notification could not be sent: ' . $sendResult['message'];
                        }

                    }
                }
            } else {
                $error = 'Unable to update patient record.';
            }

            $stmt->close();
        } else {
            $error = 'Unable to prepare patient update.';
        }
    }
}

if ($recordId > 0 && $db instanceof mysqli) {
    $row = pih_patient_fetch_record($db, $recordId);
}

if (!$row) {
    $error = $error === '' ? 'Patient record not found.' : $error;
    $row = [
        'id' => $recordId,
        'cid' => '',
        'first_name' => '',
        'last_name' => '',
        'name' => '',
        'rank' => '',
        'phone' => '',
        'coldate' => '',
        'patient_email' => '',
        'type' => '',
        'status' => '',
        'amt' => '',
        'rate' => '',
        'paydate' => '',
        'dur' => '',
    ];
}

if ($row) {
    $firstName = trim((string) ($row['first_name'] ?? ''));
    $lastName = trim((string) ($row['last_name'] ?? ''));
    if ($firstName === '' && $lastName === '') {
        [$firstName, $lastName] = pih_patient_split_name((string) ($row['name'] ?? ''));
    }
    $row['first_name'] = $firstName;
    $row['last_name'] = $lastName;
    $row['name'] = trim(implode(' ', array_filter([$firstName, $lastName], static function ($part) {
        return $part !== '';
    })));
}

pih_admin_render_start(
    'Edit Patient',
    'Update patient profile, treatment details, doctor information, and ward records',
    'dashboard',
    [
        ['href' => 'index.php', 'icon' => 'icon-dashboard', 'label' => 'Dashboard'],
        ['href' => 'identity.php', 'icon' => 'icon-plus', 'label' => 'New Patient', 'primary' => true],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>Edit Patient Details<?php if (!empty($row['name'])) { echo ' of ' . htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8'); } ?></h3>
    </div>
    <div class="module-body">
        <?php if ($message !== '') { ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>
        <?php if ($error !== '') { ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php } ?>

        <form class="form-horizontal row-fluid" method="post" action="">
            <?php echo pih_admin_csrf_input(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">

            <div class="control-group">
                <label class="control-label" for="cid">Patient ID Number</label>
                <div class="controls"><input type="text" name="cid" id="cid" value="<?php echo htmlspecialchars((string) $row['cid'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="first_name">First Name</label>
                <div class="controls"><input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars((string) ($row['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="span8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="last_name">Surname</label>
                <div class="controls"><input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars((string) ($row['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="span8" required></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="rank">Patient Illness</label>
                <div class="controls"><input type="text" name="rank" id="rank" value="<?php echo htmlspecialchars((string) $row['rank'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="phone">Patient Treatment</label>
                <div class="controls"><input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars((string) $row['phone'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="coldate">Doctor Name</label>
                <div class="controls"><input type="text" name="coldate" id="coldate" value="<?php echo htmlspecialchars((string) $row['coldate'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="patient_email">Patient Email</label>
                <div class="controls"><input type="email" name="patient_email" id="patient_email" value="<?php echo htmlspecialchars((string) pih_patient_value($row, 'patient_email') ?: pih_patient_value($row, 'type'), ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>

            <hr>
            <h4>Ward Information</h4>

            <div class="control-group">
                <label class="control-label" for="status">Nurse Name</label>
                <div class="controls"><input type="text" name="status" id="status" value="<?php echo htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="amt">Hospital Ward Number</label>
                <div class="controls"><input type="text" name="amt" id="amt" value="<?php echo htmlspecialchars((string) $row['amt'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="rate">Hospital Room Number</label>
                <div class="controls"><input type="text" name="rate" id="rate" value="<?php echo htmlspecialchars((string) $row['rate'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="paydate">Total On Going Cost</label>
                <div class="controls"><input type="text" name="paydate" id="paydate" value="<?php echo htmlspecialchars((string) $row['paydate'], ENT_QUOTES, 'UTF-8'); ?>" class="span8"></div>
            </div>
            <div class="control-group">
                <label class="control-label" for="dur">Doctor's Comments</label>
                <div class="controls"><textarea rows="4" name="dur" id="dur" class="span8"><?php echo htmlspecialchars((string) $row['dur'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <button type="submit" name="submit" value="Submit" class="btn btn-success">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php pih_admin_render_end(); ?>
