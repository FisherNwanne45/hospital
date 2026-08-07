<?php
include('adminsession.php');
require_once 'config.php';
require_once __DIR__ . '/admin_ui.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !pih_admin_validate_csrf()) {
    pih_admin_set_flash('error', 'The delete request could not be verified.');
    header('Location: index.php');
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    pih_admin_set_flash('error', 'No patient record was selected.');
    header('Location: index.php');
    exit();
}

$sql = 'DELETE FROM user WHERE id = ?';
$delete = $conn->prepare($sql);

if (!$delete) {
    pih_admin_set_flash('error', 'Unable to prepare patient deletion.');
    header('Location: index.php');
    exit();
}

$delete->bind_param('i', $id);
$delete->execute();

pih_admin_set_flash(
    $delete->affected_rows > 0 ? 'success' : 'info',
    $delete->affected_rows > 0 ? 'Patient record deleted successfully.' : 'No patient record was deleted.'
);

$delete->close();
header('Location: index.php');
exit();
