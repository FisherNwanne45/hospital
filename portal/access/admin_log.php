<?php
include('adminsession.php');
require_once 'config.php';
require_once __DIR__ . '/../../includes/email.php';
require_once __DIR__ . '/admin_ui.php';

$db = isset($conn) && $conn instanceof mysqli ? $conn : null;
$rows = [];

pih_admin_require_super_admin();

if ($db instanceof mysqli) {
    pih_admin_ensure_login_schema($db);
    pih_admin_ensure_activity_log_schema($db);

    $result = $db->query('SELECT id, actor_id, actor_username, action, subject_type, subject_id, details, created_at FROM admin_activity_log ORDER BY created_at DESC, id DESC LIMIT 100');
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
}

pih_admin_render_start(
    'Admin Log',
    'Review the latest admin and patient activity captured by the portal',
    'admin_log',
    [
        ['href' => 'index.php', 'icon' => 'icon-dashboard', 'label' => 'Dashboard'],
        ['href' => 'admin_users.php', 'icon' => 'icon-group', 'label' => 'Admin Users'],
    ]
);
?>
<div class="module">
    <div class="module-head">
        <h3>Admin Activity Log</h3>
    </div>
    <div class="module-body">
        <style>
            .audit-table-wrap {
                overflow-x: auto;
                border: 1px solid #dbe5f0;
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
            }

            .audit-table {
                width: 100%;
                margin: 0;
            }

            .audit-table th,
            .audit-table td {
                vertical-align: top;
                white-space: nowrap;
            }

            .audit-table td.details {
                white-space: normal;
                min-width: 280px;
            }
        </style>

        <p class="muted">Showing the 100 most recent entries.</p>

        <div class="audit-table-wrap">
            <table class="table table-striped audit-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th class="details">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows === []) { ?>
                        <tr><td colspan="5">No activity has been recorded yet.</td></tr>
                    <?php } else { ?>
                        <?php foreach ($rows as $row) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(trim((string) ($row['actor_username'] ?? '')) !== '' ? (string) $row['actor_username'] : 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($row['action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(trim((string) ($row['subject_type'] ?? '')) . ' ' . trim((string) ($row['subject_id'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="details"><?php echo htmlspecialchars((string) ($row['details'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php pih_admin_render_end(); ?>