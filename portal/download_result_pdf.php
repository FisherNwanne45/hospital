<?php
declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/access/config.php';
require_once __DIR__ . '/../short.php';

function pih_pdf_normalize(string $value): string
{
    return trim((string) preg_replace('/\s+/', ' ', $value));
}

function pih_pdf_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function pih_pdf_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function pih_pdf_filename(string $patientId): string
{
    $base = $patientId !== '' ? $patientId : 'record';
    $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base);
    return 'Patient-Result-' . trim((string) $safe, '-');
}

function pih_pdf_data_uri_from_path(string $path): string
{
    $real = realpath($path);
    if ($real === false || !is_file($real)) {
        return '';
    }

    $raw = file_get_contents($real);
    if ($raw === false) {
        return '';
    }

    $ext = strtolower((string) pathinfo($real, PATHINFO_EXTENSION));
    $mime = 'image/png';
    if (in_array($ext, ['jpg', 'jpeg'], true)) {
        $mime = 'image/jpeg';
    } elseif ($ext === 'gif') {
        $mime = 'image/gif';
    } elseif ($ext === 'webp') {
        $mime = 'image/webp';
    }

    return 'data:' . $mime . ';base64,' . base64_encode($raw);
}

function pih_pdf_resolve_local_path(string $path): string
{
    $real = realpath($path);
    return ($real !== false && is_file($real)) ? str_replace('\\', '/', $real) : '';
}

  function pih_pdf_data_uri_from_setting(string $value): string
  {
    $raw = trim($value);
    if ($raw === '') {
      return '';
    }

    $normalized = str_replace('\\', '/', $raw);
    if (preg_match('#^https?://#i', $normalized) === 1) {
      $urlPath = (string) parse_url($normalized, PHP_URL_PATH);
      $normalized = $urlPath !== '' ? $urlPath : $normalized;
    }

    if (strpos($normalized, '/privateimaginghealthcare/') !== false) {
      $normalized = (string) substr($normalized, strpos($normalized, '/privateimaginghealthcare/') + strlen('/privateimaginghealthcare/'));
    }

    $normalized = ltrim($normalized, '/');
    if ($normalized === '') {
      return '';
    }

    return pih_pdf_data_uri_from_path(dirname(__DIR__) . '/' . $normalized);
  }

  function pih_pdf_resolve_path_from_setting(string $value): string
  {
    $raw = trim($value);
    if ($raw === '') {
      return '';
    }

    $normalized = str_replace('\\', '/', $raw);
    if (preg_match('#^https?://#i', $normalized) === 1) {
      $urlPath = (string) parse_url($normalized, PHP_URL_PATH);
      $normalized = $urlPath !== '' ? $urlPath : $normalized;
    }

    if (strpos($normalized, '/privateimaginghealthcare/') !== false) {
      $normalized = (string) substr($normalized, strpos($normalized, '/privateimaginghealthcare/') + strlen('/privateimaginghealthcare/'));
    }

    $normalized = ltrim($normalized, '/');
    if ($normalized === '') {
      return '';
    }

    return pih_pdf_resolve_local_path(dirname(__DIR__) . '/' . $normalized);
  }

  function pih_pdf_placeholder_svg(string $label): string
  {
    $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="520" height="220" viewBox="0 0 520 220">'
      . '<rect width="520" height="220" fill="#f2f6fb"/>'
      . '<rect x="1" y="1" width="518" height="218" fill="none" stroke="#c6d4e2"/>'
      . '<text x="260" y="110" font-size="22" text-anchor="middle" fill="#5d738a" font-family="Arial, sans-serif">'
      . $safeLabel
      . '</text>'
      . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
  }

$patientId = pih_pdf_normalize((string) ($_GET['cid'] ?? ''));
$searchName = pih_pdf_normalize((string) ($_GET['name'] ?? ''));

if ($patientId === '' || $searchName === '') {
    http_response_code(400);
    echo 'Missing required parameters.';
    exit();
}

$columns = [];
$columnResult = $conn->query('SHOW COLUMNS FROM user');
if ($columnResult) {
    while ($column = $columnResult->fetch_assoc()) {
        $columnName = isset($column['Field']) ? (string) $column['Field'] : '';
        if ($columnName !== '') {
            $columns[$columnName] = true;
        }
    }
    $columnResult->free();
}

$hasFirstName = isset($columns['first_name']);
$hasLastName = isset($columns['last_name']);

$namePattern = '%' . pih_pdf_lower($searchName) . '%';
$sql = 'SELECT * FROM user WHERE cid = ?';
if ($hasFirstName && $hasLastName) {
    $sql .= ' AND (LOWER(name) LIKE ? OR LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ?)';
} else {
    $sql .= ' AND LOWER(name) LIKE ?';
}
$sql .= ' LIMIT 1';

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo 'Unable to prepare query.';
    exit();
}

if ($hasFirstName && $hasLastName) {
    $stmt->bind_param('ssss', $patientId, $namePattern, $namePattern, $namePattern);
} else {
    $stmt->bind_param('ss', $patientId, $namePattern);
}

$stmt->execute();
$result = $stmt->get_result();
$record = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!is_array($record)) {
    http_response_code(404);
    echo 'No matching patient record found.';
    exit();
}

$displayFirstName = trim((string) ($record['first_name'] ?? ''));
$displayLastName = trim((string) ($record['last_name'] ?? ''));
$displayName = trim((string) ($record['name'] ?? ''));
if ($displayName === '') {
    $displayName = trim(implode(' ', array_filter([$displayFirstName, $displayLastName], static function ($part) {
        return $part !== '';
    })));
}

$displayId = trim((string) ($record['cid'] ?? $patientId));
$patientEmail = trim((string) ($record['patient_email'] ?? $record['type'] ?? ''));
$doctorName = trim((string) ($record['coldate'] ?? ''));
$patientIllness = trim((string) ($record['rank'] ?? ''));
$patientTreatment = trim((string) ($record['phone'] ?? ''));
$nurseName = trim((string) ($record['status'] ?? ''));
$wardNumber = trim((string) ($record['amt'] ?? ''));
$roomNumber = trim((string) ($record['rate'] ?? ''));
$ongoingCost = trim((string) ($record['paydate'] ?? ''));
$doctorComments = trim((string) ($record['dur'] ?? ''));
$remarks = trim((string) ($record['remark'] ?? ''));

$dompdfWorkDir = '/Applications/XAMPP/xamppfiles/temp/pih_dompdf';
if (!is_dir($dompdfWorkDir)) {
  @mkdir($dompdfWorkDir, 0777, true);
}

if (!is_dir($dompdfWorkDir) || !is_writable($dompdfWorkDir)) {
  $dompdfWorkDir = '/Applications/XAMPP/xamppfiles/temp';
}

$logoPath = pih_pdf_resolve_path_from_setting((string) ($logo_path ?? ''));
if ($logoPath === '') {
  $logoPath = pih_pdf_resolve_local_path(dirname(__DIR__) . '/static/images/NY_Imaging_Specialists.png');
}
$patientImagePath = pih_pdf_resolve_local_path(__DIR__ . '/access/img/' . basename((string) ($record['image'] ?? '')));

$siteName = trim((string) ($name ?? 'Private Imaging healthcare Center'));
$sitePhone = trim((string) ($phone ?? ''));
$siteEmail = trim((string) ($email ?? ''));
$siteAddress = str_replace(["\\n", "\\r\\n", "\\r"], "\n", trim((string) ($addr ?? '')));

$reportReference = 'PCR-' . date('Ymd') . '-' . ($displayId !== '' ? $displayId : 'NA');
$generatedAt = date('F j, Y g:i A');
$filename = pih_pdf_filename($displayId) . '.pdf';

$trackResultUrl = rtrim((string) ($url ?? ''), '/') . '/portal/submit.php?cid=' . rawurlencode($displayId) . '&name=' . rawurlencode($searchName);
if (trim($trackResultUrl) === '/portal/submit.php?cid=' . rawurlencode($displayId) . '&name=' . rawurlencode($searchName)) {
    $trackResultUrl = 'http://localhost/privateimaginghealthcare/portal/submit.php?cid=' . rawurlencode($displayId) . '&name=' . rawurlencode($searchName);
}

$qrOptions = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel' => QRCode::ECC_M,
    'scale' => 6,
]);
$qrCode = new QRCode($qrOptions);
$qrImageData = $qrCode->render($trackResultUrl);
$qrBinary = '';
if (str_starts_with($qrImageData, 'data:image')) {
  $parts = explode(',', $qrImageData, 2);
  if (count($parts) === 2) {
    $decoded = base64_decode($parts[1], true);
    if ($decoded !== false) {
      $qrBinary = $decoded;
    }
  }
} else {
  $qrBinary = $qrImageData;
}

$qrPath = '';
if ($qrBinary !== '') {
  $qrTempFile = rtrim($dompdfWorkDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'qr_' . uniqid('', true) . '.png';
  if (@file_put_contents($qrTempFile, $qrBinary) !== false) {
    $qrPath = pih_pdf_resolve_local_path($qrTempFile);
  }
}

$contactLines = [];
if ($siteAddress !== '') {
  $contactLines[] = pih_pdf_escape($siteAddress);
}
if ($sitePhone !== '') {
  $contactLines[] = 'Phone: ' . pih_pdf_escape($sitePhone);
}
if ($siteEmail !== '') {
  $contactLines[] = 'Email: ' . pih_pdf_escape($siteEmail);
}
$contactHtml = implode('<br>', $contactLines);

$logoHtml = $logoPath !== ''
  ? '<img class="logo" src="' . pih_pdf_escape($logoPath) . '" alt="Facility logo">'
  : '<div class="logo-fallback">Facility Logo</div>';

$patientPhotoHtml = $patientImagePath !== ''
  ? '<img class="patient-photo" src="' . pih_pdf_escape($patientImagePath) . '" alt="Patient image">'
  : '<div class="patient-photo-fallback">Patient image not available</div>';

$qrHtml = $qrPath !== ''
  ? '<img src="' . pih_pdf_escape($qrPath) . '" alt="Result QR code">'
  : '<div class="qr-fallback">QR unavailable</div>';

$html = '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <style>
    @page { size: A4; margin: 14mm; }
    body { font-family: Helvetica, Arial, sans-serif; color: #1f2f3f; font-size: 11px; margin: 0; background: #fff; }
    .report { border: 1px solid #d5dce5; padding: 16px; }
    .header { border-bottom: 2px solid #23384d; padding-bottom: 10px; margin-bottom: 10px; }
    .header-top { width: 100%; }
    .header-top:after { content: ""; display: block; clear: both; }
    .logo-wrap { float: left; width: 48%; }
    .logo { width: 170px; height: auto; }
    .logo-fallback { width: 170px; height: 52px; border: 1px solid #c7d5e3; color: #56708a; line-height: 52px; text-align: center; font-size: 11px; }
    .doc-title { margin-top: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #26435e; }
    .contact { float: right; width: 48%; text-align: right; color: #4c6178; line-height: 1.45; white-space: pre-line; }
    .meta-row { margin-top: 10px; display: table; width: 100%; table-layout: fixed; border-spacing: 8px 0; }
    .meta-box { display: table-cell; border: 1px solid #d4dde8; background: #f8fbff; border-radius: 6px; padding: 8px; }
    .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7f94; margin-bottom: 3px; }
    .meta-value { font-size: 12px; font-weight: 700; color: #243b53; }
    .body-grid { margin-top: 10px; display: table; width: 100%; table-layout: fixed; }
    .left-col { display: table-cell; width: 28%; vertical-align: top; padding-right: 10px; }
    .right-col { display: table-cell; width: 72%; vertical-align: top; }
    .patient-photo { width: 100%; height: auto; max-height: 210px; border: 1px solid #cfd9e5; border-radius: 6px; }
    .patient-photo-fallback { width: 100%; height: 210px; border: 1px solid #cfd9e5; border-radius: 6px; color: #5c7389; font-size: 11px; text-align: center; line-height: 210px; background: #f3f7fb; }
    .qr-block { margin-top: 12px; border: 1px solid #d7dfe8; border-radius: 6px; padding: 8px; text-align: center; background: #fcfeff; }
    .qr-block img { width: 110px; height: 110px; display: block; margin: 0 auto; }
    .qr-fallback { width: 110px; height: 110px; margin: 0 auto; border: 1px solid #d1dce8; color: #627a91; font-size: 10px; line-height: 110px; }
    .qr-text { margin-top: 5px; font-size: 9.5px; color: #546a80; line-height: 1.35; word-break: break-word; }
    .section { border: 1px solid #d8e0ea; border-radius: 6px; margin-bottom: 10px; overflow: hidden; }
    .section-title { margin: 0; padding: 8px 10px; background: #f4f8fc; color: #24405b; font-size: 11px; letter-spacing: 0.06em; text-transform: uppercase; }
    .fields { padding: 8px 10px; }
    .field { margin-bottom: 7px; }
    .field:last-child { margin-bottom: 0; }
    .field-label { font-size: 9.5px; color: #687d93; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
    .field-value { font-size: 11.5px; color: #1f3349; line-height: 1.45; }
    .two-col .col { display: inline-block; width: 48%; vertical-align: top; }
    .two-col .col + .col { margin-left: 3%; }
    .footer { margin-top: 12px; border-top: 1px solid #d4dde8; padding-top: 8px; }
    .sign-row { display: table; width: 100%; table-layout: fixed; border-spacing: 8px 0; }
    .sign { display: table-cell; border: 1px solid #d7dfe8; border-radius: 6px; padding: 10px; min-height: 70px; vertical-align: top; }
    .sign-title { font-size: 10px; color: #4c6178; text-transform: uppercase; }
    .sign-line { margin-top: 30px; border-top: 1px solid #3b4f63; padding-top: 4px; font-size: 10px; color: #3d4f62; }
    .confidential { margin-top: 8px; font-size: 9px; color: #61758a; line-height: 1.4; }
  </style>
</head>
<body>
  <div class="report">
    <div class="header">
      <div class="header-top">
        <div class="logo-wrap">
          ' . $logoHtml . '
          <div class="doc-title">Patient Clearance Result</div>
        </div>
        <div class="contact">' . $contactHtml . '</div>
      </div>
      <div class="meta-row">
        <div class="meta-box"><div class="meta-label">Reference</div><div class="meta-value">' . pih_pdf_escape($reportReference) . '</div></div>
        <div class="meta-box"><div class="meta-label">Generated</div><div class="meta-value">' . pih_pdf_escape($generatedAt) . '</div></div>
        <div class="meta-box"><div class="meta-label">Patient ID</div><div class="meta-value">' . pih_pdf_escape($displayId) . '</div></div>
      </div>
    </div>

    <div class="body-grid">
      <div class="left-col">
        ' . $patientPhotoHtml . '
        <div class="qr-block">
          ' . $qrHtml . '
          <div class="qr-text">Scan to verify this result:<br>' . pih_pdf_escape($trackResultUrl) . '</div>
        </div>
      </div>

      <div class="right-col">
        <div class="section">
          <h3 class="section-title">Patient Details</h3>
          <div class="fields two-col">
            <div class="col">
              <div class="field"><div class="field-label">First Name</div><div class="field-value">' . pih_pdf_escape($displayFirstName !== '' ? $displayFirstName : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Patient Full Name</div><div class="field-value">' . pih_pdf_escape($displayName !== '' ? $displayName : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Doctor Name</div><div class="field-value">' . pih_pdf_escape($doctorName !== '' ? $doctorName : 'Not provided') . '</div></div>
            </div>
            <div class="col">
              <div class="field"><div class="field-label">Surname</div><div class="field-value">' . pih_pdf_escape($displayLastName !== '' ? $displayLastName : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Nurse Name</div><div class="field-value">' . pih_pdf_escape($nurseName !== '' ? $nurseName : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Patient Email</div><div class="field-value">' . pih_pdf_escape($patientEmail !== '' ? $patientEmail : 'Not provided') . '</div></div>
            </div>
          </div>
        </div>

        <div class="section">
          <h3 class="section-title">Clinical Notes</h3>
          <div class="fields two-col">
            <div class="col">
              <div class="field"><div class="field-label">Patient Illness</div><div class="field-value">' . pih_pdf_escape($patientIllness !== '' ? $patientIllness : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Patient Treatment</div><div class="field-value">' . pih_pdf_escape($patientTreatment !== '' ? $patientTreatment : 'Not provided') . '</div></div>
            </div>
            <div class="col">
              <div class="field"><div class="field-label">Doctor\'s Comments</div><div class="field-value">' . pih_pdf_escape($doctorComments !== '' ? $doctorComments : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Remarks</div><div class="field-value">' . pih_pdf_escape($remarks !== '' ? $remarks : 'Not provided') . '</div></div>
            </div>
          </div>
        </div>

        <div class="section">
          <h3 class="section-title">Ward and Billing</h3>
          <div class="fields two-col">
            <div class="col">
              <div class="field"><div class="field-label">Hospital Ward Number</div><div class="field-value">' . pih_pdf_escape($wardNumber !== '' ? $wardNumber : 'Not provided') . '</div></div>
              <div class="field"><div class="field-label">Hospital Room Number</div><div class="field-value">' . pih_pdf_escape($roomNumber !== '' ? $roomNumber : 'Not provided') . '</div></div>
            </div>
            <div class="col">
              <div class="field"><div class="field-label">Total On Going Cost</div><div class="field-value">' . pih_pdf_escape($ongoingCost !== '' ? $ongoingCost : 'Not provided') . '</div></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="footer">
      <div class="sign-row">
        <div class="sign">
          <div class="sign-title">Attending Physician</div>
          <div class="sign-line">' . pih_pdf_escape($doctorName !== '' ? $doctorName : 'Doctor Name') . '</div>
        </div>
        <div class="sign">
          <div class="sign-title">Authorized By</div>
          <div class="sign-line">Medical Records Unit</div>
        </div>
      </div>
      <div class="confidential">Confidential medical record. Scan the QR code to view the tracked result page associated with this document.</div>
    </div>
  </div>
</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('chroot', '/Applications/XAMPP/xamppfiles');
$options->set('tempDir', $dompdfWorkDir);
$options->set('fontDir', $dompdfWorkDir);
$options->set('fontCache', $dompdfWorkDir);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream($filename, ['Attachment' => true]);
exit();
