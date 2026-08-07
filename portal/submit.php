<?php
include "../short.php";
require_once 'access/config.php';
include_once('access/session.php');

function pih_tracking_normalize(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value));
}

function pih_tracking_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function pih_tracking_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$record = null;
$errorMessage = '';
$lookupSummary = '';
$patientId = '';
$searchName = '';

$userColumns = [];
$columnResult = $conn->query('SHOW COLUMNS FROM user');
if ($columnResult) {
    while ($column = $columnResult->fetch_assoc()) {
        $columnName = isset($column['Field']) ? (string) $column['Field'] : '';
        if ($columnName !== '') {
            $userColumns[$columnName] = true;
        }
    }
    $columnResult->free();
}
$hasFirstName = isset($userColumns['first_name']);
$hasLastName = isset($userColumns['last_name']);

$requestPatientId = '';
$requestSearchName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $requestPatientId = pih_tracking_normalize((string) ($_POST['dropdown'] ?? ''));
    $requestSearchName = pih_tracking_normalize((string) ($_POST['search'] ?? ''));
} elseif (isset($_GET['cid']) || isset($_GET['name'])) {
    $requestPatientId = pih_tracking_normalize((string) ($_GET['cid'] ?? ''));
    $requestSearchName = pih_tracking_normalize((string) ($_GET['name'] ?? ''));
}

if ($requestPatientId !== '' || $requestSearchName !== '') {
    $patientId = $requestPatientId;
    $searchName = $requestSearchName;

    if ($patientId === '') {
        $errorMessage = 'ERROR: Enter the patient ID.';
    } elseif ($searchName === '') {
        $errorMessage = 'ERROR: Enter a first name or surname.';
    } else {
        $lookupSummary = 'name ' . $searchName;

        $namePattern = '%' . pih_tracking_lower($searchName) . '%';

        $sql = 'SELECT * FROM user WHERE cid = ?';
        if ($hasFirstName && $hasLastName) {
            $sql .= ' AND (LOWER(name) LIKE ? OR LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ?)';
        } else {
            $sql .= ' AND LOWER(name) LIKE ?';
        }

        $sql .= ' LIMIT 1';

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if ($hasFirstName && $hasLastName) {
                $stmt->bind_param('ssss', $patientId, $namePattern, $namePattern, $namePattern);
            } else {
                $stmt->bind_param('ss', $patientId, $namePattern);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            if ($result instanceof mysqli_result) {
                $record = $result->fetch_assoc() ?: null;
            }
            $stmt->close();
        } else {
            $errorMessage = 'Unable to prepare the tracking query.';
        }
    }
}

$siteTitle = 'Patient Clearance Online Checker';
$displayName = $record !== null ? trim((string) ($record['name'] ?? '')) : '';
$displayFirstName = $record !== null ? trim((string) ($record['first_name'] ?? '')) : '';
$displayLastName = $record !== null ? trim((string) ($record['last_name'] ?? '')) : '';
$composedName = trim(implode(' ', array_filter([$displayFirstName, $displayLastName], static function ($part) {
    return $part !== '';
})));
if ($displayName === '' && $composedName !== '') {
    $displayName = $composedName;
}
$displayId = $record !== null ? trim((string) ($record['cid'] ?? '')) : $patientId;
$displayImage = $record !== null ? basename((string) ($record['image'] ?? '')) : '';
$patientEmail = $record !== null ? trim((string) ($record['patient_email'] ?? $record['type'] ?? '')) : '';
$doctorName = $record !== null ? trim((string) ($record['coldate'] ?? '')) : '';
$patientIllness = $record !== null ? trim((string) ($record['rank'] ?? '')) : '';
$patientTreatment = $record !== null ? trim((string) ($record['phone'] ?? '')) : '';
$nurseName = $record !== null ? trim((string) ($record['status'] ?? '')) : '';
$wardNumber = $record !== null ? trim((string) ($record['amt'] ?? '')) : '';
$roomNumber = $record !== null ? trim((string) ($record['rate'] ?? '')) : '';
$ongoingCost = $record !== null ? trim((string) ($record['paydate'] ?? '')) : '';
$doctorComments = $record !== null ? trim((string) ($record['dur'] ?? '')) : '';
$remarks = $record !== null ? trim((string) ($record['remark'] ?? '')) : '';

$logoUrl = isset($url) && $url !== '' ? rtrim($url, '/') . '/static/images/NY_Imaging_Specialists.png' : 'static/images/NY_Imaging_Specialists.png';
$homeUrl = isset($url) && $url !== '' ? rtrim($url, '/') . '/index.php' : 'index.php';
$publicUrl = isset($url) && $url !== '' ? rtrim($url, '/') . '/portal/index.php' : 'index.php';
$downloadName = $searchName !== '' ? $searchName : ($displayLastName !== '' ? $displayLastName : $displayFirstName);
$downloadPdfUrl = 'download_result_pdf.php?cid=' . rawurlencode($displayId) . '&name=' . rawurlencode($downloadName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo pih_tracking_escape($siteTitle); ?></title>
    <link rel="icon" href="<?php echo pih_tracking_escape(isset($url) && $url !== '' ? rtrim($url, '/') . '/static/images/mri.png' : 'static/images/mri.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --track-bg: #eef4f9;
            --track-surface: #ffffff;
            --track-ink: #17324d;
            --track-muted: #62748b;
            --track-line: #d8e3ee;
            --track-brand: #0f4c81;
            --track-accent: #1f7cc1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--track-ink);
            background:
                radial-gradient(900px 360px at -8% -12%, rgba(31, 124, 193, 0.16), transparent 60%),
                radial-gradient(850px 320px at 105% 0%, rgba(15, 76, 129, 0.15), transparent 60%),
                var(--track-bg);
        }

        .tracking-page {
            min-height: 100vh;
            padding: 28px 18px 36px;
        }

        .tracking-shell {
            width: min(1180px, 100%);
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid var(--track-line);
            border-radius: 22px;
            box-shadow: 0 20px 48px rgba(10, 34, 59, 0.12);
            backdrop-filter: blur(8px);
            overflow: hidden;
        }

        .tracking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            padding: 28px 30px 22px;
            background: linear-gradient(135deg, rgba(15, 76, 129, 0.08), rgba(31, 124, 193, 0.03));
            border-bottom: 1px solid var(--track-line);
        }

        .tracking-kicker {
            margin: 0 0 8px;
            color: var(--track-accent);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .tracking-header h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.1;
        }

        .tracking-summary {
            margin: 10px 0 0;
            color: var(--track-muted);
            max-width: 760px;
            line-height: 1.55;
        }

        .tracking-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tracking-body {
            padding: 26px 30px 30px;
        }

        .tracking-hero-card,
        .tracking-detail-card {
            border: 1px solid var(--track-line);
            border-radius: 18px;
            overflow: hidden;
            background: var(--track-surface);
            box-shadow: 0 8px 24px rgba(10, 34, 59, 0.06);
        }

        .tracking-hero-card .card-body,
        .tracking-detail-card .card-body {
            padding: 20px;
        }

        .tracking-hero-card {
            margin-bottom: 18px;
        }

        .tracking-card-title {
            margin: 0 0 14px;
            font-size: 18px;
            font-weight: 800;
            color: var(--track-ink);
        }

        .tracking-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .tracking-meta-item {
            border: 1px solid #e2eaf3;
            border-radius: 14px;
            padding: 12px 14px;
            background: #fbfdff;
        }

        .tracking-meta-label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--track-muted);
        }

        .tracking-meta-value {
            font-size: 15px;
            font-weight: 700;
            color: var(--track-ink);
            word-break: break-word;
        }

        .tracking-grid {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 18px;
        }

        .tracking-side,
        .tracking-main {
            min-width: 0;
        }

        .tracking-photo {
            width: 100%;
            aspect-ratio: 4 / 4.5;
            border-radius: 16px;
            object-fit: cover;
            background: #f3f7fb;
            border: 1px solid #dbe5ef;
        }

        .tracking-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0f5b2e;
            background: #e7f7ec;
            margin-top: 14px;
        }

        .tracking-side-details {
            margin-top: 16px;
            display: grid;
            gap: 10px;
        }

        .tracking-side-detail {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fbfe;
            border: 1px solid #e5edf5;
            font-size: 14px;
        }

        .tracking-side-detail span:first-child {
            color: var(--track-muted);
            font-weight: 600;
        }

        .tracking-side-detail span:last-child {
            text-align: right;
            font-weight: 700;
            color: var(--track-ink);
        }

        .tracking-section {
            margin-bottom: 18px;
        }

        .tracking-section:last-child {
            margin-bottom: 0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .detail-item {
            border: 1px solid #e2eaf3;
            border-radius: 14px;
            padding: 12px 14px;
            background: #fbfdff;
            min-height: 100%;
        }

        .detail-item .label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--track-muted);
        }

        .detail-item .value {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.5;
            word-break: break-word;
        }

        .tracking-empty {
            border: 1px dashed #bfd0e0;
            border-radius: 18px;
            background: #fcfeff;
        }

        .tracking-empty .card-body {
            padding: 28px;
            text-align: center;
        }

        .tracking-empty h2 {
            margin-bottom: 10px;
            font-size: 24px;
        }

        .tracking-empty p {
            margin: 0 auto 18px;
            max-width: 620px;
            color: var(--track-muted);
        }

        .tracking-alert {
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .tracking-footer {
            padding: 14px 30px 28px;
            color: var(--track-muted);
            font-size: 13px;
            border-top: 1px solid var(--track-line);
        }


        @media print {
            @page {
                size: A4;
                margin: 14mm;
            }

            .noprint {
                display: none !important;
            }

            body {
                background: #ffffff;
                color: #1b2430;
                font-size: 10.5pt;
            }

            .tracking-page {
                padding: 0;
            }

            .tracking-shell {
                box-shadow: none;
                border: 1px solid #d5dce5;
                border-radius: 0;
                background: #ffffff;
                width: 100%;
            }

            .tracking-header,
            .tracking-body {
                padding: 14px 16px;
            }

            .tracking-header {
                background: none;
                border-bottom: 2px solid #26384d;
            }

            .tracking-header h1 {
                font-size: 22px;
            }

            .tracking-summary {
                color: #2f4155;
            }

            .tracking-grid {
                grid-template-columns: 220px minmax(0, 1fr);
            }

            .tracking-photo {
                aspect-ratio: auto;
                max-height: 220px;
            }

            .tracking-card-title {
                font-size: 16px;
            }

            .tracking-detail-card,
            .tracking-hero-card,
            .detail-item,
            .tracking-side-detail {
                break-inside: avoid;
                box-shadow: none;
            }

            .tracking-footer {
                padding: 8px 16px 12px;
            }
        }

        @media (max-width: 980px) {
            .tracking-header {
                flex-direction: column;
            }

            .tracking-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .tracking-page {
                padding: 14px;
            }

            .tracking-header,
            .tracking-body,
            .tracking-footer {
                padding-left: 18px;
                padding-right: 18px;
            }

            .tracking-meta,
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .tracking-header h1 {
                font-size: 26px;
            }

            .tracking-side {
                max-width: 310px;
                margin: 0 auto;
            }

            .tracking-photo {
                aspect-ratio: 4 / 3;
                max-height: 240px;
            }

            .tracking-side-detail {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
<main class="tracking-page">
    <section class="tracking-shell" aria-label="Patient Clearance Online Checker result">
        <header class="tracking-header">
            <div>
                <p class="tracking-kicker"><?php echo pih_tracking_escape($siteTitle); ?></p>
                <h1>Tracking Result</h1>
                <p class="tracking-summary">
                    <?php if ($record !== null) { ?>
                        Matching record found for patient ID <strong><?php echo pih_tracking_escape($displayId); ?></strong>
                        <?php if ($lookupSummary !== '') { ?>using <?php echo pih_tracking_escape($lookupSummary); ?><?php } ?>.
                    <?php } elseif ($errorMessage !== '') { ?>
                        <?php echo pih_tracking_escape($errorMessage); ?>
                    <?php } else { ?>
                        Use the lookup form to check a patient record with the patient ID and either the first name or surname.
                    <?php } ?>
                </p>
            </div>

            <div class="tracking-actions noprint">
                <a href="<?php echo pih_tracking_escape($publicUrl); ?>" class="btn btn-outline-primary">New Search</a>
                <?php if ($record !== null) { ?>
                    <a href="<?php echo pih_tracking_escape($downloadPdfUrl); ?>" class="btn btn-primary">Download PDF</a>
                <?php } ?>
            </div>
        </header>

        <div class="tracking-body">
            <?php if ($errorMessage !== '') { ?>
                <div class="alert alert-warning tracking-alert"><?php echo pih_tracking_escape($errorMessage); ?></div>
            <?php } ?>

            <?php if ($record !== null) { ?>
                <div class="tracking-grid">
                    <aside class="tracking-side">
                        <div class="tracking-hero-card">
                            <div class="card-body">
                                <img class="tracking-photo" src="access/img/<?php echo pih_tracking_escape($displayImage); ?>" alt="<?php echo pih_tracking_escape($displayName); ?>">
                                <span class="tracking-badge">Verified Record</span>

                                <div class="tracking-side-details">
                                    <div class="tracking-side-detail">
                                        <span>Patient ID</span>
                                        <span><?php echo pih_tracking_escape($displayId); ?></span>
                                    </div>
                                    <div class="tracking-side-detail">
                                        <span>Patient Name</span>
                                        <span><?php echo pih_tracking_escape($displayName); ?></span>
                                    </div>
                                    <div class="tracking-side-detail">
                                        <span>Patient Email</span>
                                        <span><?php echo pih_tracking_escape($patientEmail !== '' ? $patientEmail : 'Not provided'); ?></span>
                                    </div>
                                    <div class="tracking-side-detail">
                                        <span>Doctor Name</span>
                                        <span><?php echo pih_tracking_escape($doctorName !== '' ? $doctorName : 'Not provided'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <section class="tracking-main">
                        <div class="tracking-detail-card tracking-section">
                            <div class="card-body">
                                <h2 class="tracking-card-title">Patient Data</h2>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">First Name</span>
                                        <span class="value"><?php echo pih_tracking_escape($displayFirstName !== '' ? $displayFirstName : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Surname</span>
                                        <span class="value"><?php echo pih_tracking_escape($displayLastName !== '' ? $displayLastName : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient Full Name</span>
                                        <span class="value"><?php echo pih_tracking_escape($displayName !== '' ? $displayName : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient ID</span>
                                        <span class="value"><?php echo pih_tracking_escape($displayId !== '' ? $displayId : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Doctor Name</span>
                                        <span class="value"><?php echo pih_tracking_escape($doctorName !== '' ? $doctorName : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient Illness</span>
                                        <span class="value"><?php echo pih_tracking_escape($patientIllness !== '' ? $patientIllness : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient Treatment</span>
                                        <span class="value"><?php echo pih_tracking_escape($patientTreatment !== '' ? $patientTreatment : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Patient Email</span>
                                        <span class="value"><?php echo pih_tracking_escape($patientEmail !== '' ? $patientEmail : 'Not provided'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tracking-detail-card tracking-section">
                            <div class="card-body">
                                <h2 class="tracking-card-title">Ward Information</h2>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">Nurse Name</span>
                                        <span class="value"><?php echo pih_tracking_escape($nurseName !== '' ? $nurseName : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Hospital Ward Number</span>
                                        <span class="value"><?php echo pih_tracking_escape($wardNumber !== '' ? $wardNumber : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Hospital Room Number</span>
                                        <span class="value"><?php echo pih_tracking_escape($roomNumber !== '' ? $roomNumber : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Total On Going Cost</span>
                                        <span class="value"><?php echo pih_tracking_escape($ongoingCost !== '' ? $ongoingCost : 'Not provided'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tracking-detail-card">
                            <div class="card-body">
                                <h2 class="tracking-card-title">Doctor's Comments and Remarks</h2>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">Doctor's Comments</span>
                                        <span class="value"><?php echo pih_tracking_escape($doctorComments !== '' ? $doctorComments : 'Not provided'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Remarks</span>
                                        <span class="value"><?php echo pih_tracking_escape($remarks !== '' ? $remarks : 'Not provided'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            <?php } else { ?>
                <div class="tracking-empty">
                    <div class="card-body">
                        <h2>No matching record found</h2>
                        <p>
                            The patient ID and name combination did not match any record. Try again using the patient ID and either the first name or surname exactly as recorded.
                        </p>
                        <a class="btn btn-primary" href="<?php echo pih_tracking_escape($publicUrl); ?>">Try Another Search</a>
                    </div>
                </div>
            <?php } ?>
        </div>

        <footer class="tracking-footer">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <span>Patient Clearance Online Checker</span>
                <span><?php echo date('Y'); ?> <?php echo pih_tracking_escape((string) ($name ?? '')); ?></span>
            </div>
        </footer>
    </section>
</main>

<?php if (!empty($tawk)) {
    echo $tawk;
} ?>
</body>
</html>
