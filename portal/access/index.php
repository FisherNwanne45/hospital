<?php
include('adminsession.php');
include('config.php');
require_once __DIR__ . '/admin_ui.php';

$patients = [];
$result = $conn->query("SELECT * FROM user ORDER BY id DESC");
if ($result) {
    while ($entry = $result->fetch_assoc()) {
        $patients[] = $entry;
    }
}

$totalPatients = count($patients);
$recentAdmissions = min(5, $totalPatients);
$doctorIndex = [];
$wardIndex = [];

foreach ($patients as $patient) {
    $doctorName = trim((string) ($patient['coldate'] ?? ''));
    $wardName = trim((string) ($patient['amt'] ?? ''));

    if ($doctorName !== '') {
        $doctorIndex[strtolower($doctorName)] = true;
    }
    if ($wardName !== '') {
        $wardIndex[strtolower($wardName)] = true;
    }
}

$doctorCount = count($doctorIndex);
$wardCount = count($wardIndex);
$flash = pih_admin_consume_flash();
$currentRole = (string) ($_SESSION['login_role'] ?? 'admin');
$isImpersonating = !empty($_SESSION['pih_impersonator_id']);
$impersonatorName = (string) ($_SESSION['pih_impersonator_username'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="../../templates/equinox/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link type="text/css" href="edmin/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" href="edmin/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
    <link type="text/css" href="edmin/images/icons/css/font-awesome.css" rel="stylesheet">
    <style>
        :root {
            --admin-ink: #0f172a;
            --admin-muted: #66758c;
            --admin-line: #dbe5f0;
            --admin-brand: #113f6b;
            --admin-brand-soft: #2e78b4;
            --admin-bg: #eef4fa;
            --admin-sidebar: #0d2744;
            --admin-sidebar-soft: #14385f;
            --admin-success: #138a5b;
            --admin-danger: #bb2d3b;
            --sidebar-open-width: 276px;
            --sidebar-closed-width: 88px;
            --topbar-height: 74px;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }

        body {
            font-family: 'Manrope', sans-serif;
            color: var(--admin-ink);
            background:
                radial-gradient(1100px 480px at -10% -20%, rgba(46, 120, 180, 0.24), transparent 60%),
                radial-gradient(1000px 420px at 110% 100%, rgba(17, 63, 107, 0.18), transparent 60%),
                var(--admin-bg);
        }

        .admin-shell {
            min-height: 100vh;
            display: flex;
        }

        .admin-sidebar {
            width: var(--sidebar-open-width);
            min-height: 100vh;
            background: linear-gradient(180deg, var(--admin-sidebar), var(--admin-sidebar-soft));
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
            box-shadow: 12px 0 32px rgba(13, 39, 68, 0.16);
            transition: width 220ms ease, transform 220ms ease;
            overflow: hidden;
        }

        .admin-sidebar-inner {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 18px 16px;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 54px;
            margin-bottom: 22px;
        }

        .admin-brand-badge {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.12);
            font-size: 20px;
        }

        .admin-brand-copy h1 {
            margin: 0;
            font-size: 17px;
            line-height: 1.2;
            color: #fff;
        }

        .admin-brand-copy p {
            margin: 4px 0 0;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.72);
        }

        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;
            font-weight: 600;
            transition: background 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .admin-nav a i {
            width: 18px;
            text-align: center;
            font-size: 16px;
        }

        .admin-nav a:hover,
        .admin-nav a.is-active {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            transform: translateX(1px);
        }

        .admin-sidebar-footer {
            margin-top: auto;
            padding-top: 16px;
        }

        .admin-sidebar-footer-card {
            border-radius: 16px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.88);
            font-size: 13px;
            line-height: 1.6;
        }

        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-open-width);
            transition: margin-left 220ms ease;
            min-width: 0;
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            height: var(--topbar-height);
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(219, 229, 240, 0.9);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 22px;
        }

        .admin-topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .sidebar-toggle {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--admin-line);
            background: #fff;
            color: var(--admin-brand);
            font-size: 18px;
            cursor: pointer;
        }

        .admin-page-title h2 {
            margin: 0;
            font-size: 22px;
            line-height: 1.2;
        }

        .admin-page-title p {
            margin: 2px 0 0;
            color: var(--admin-muted);
            font-size: 13px;
        }

        .admin-topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-chip {
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--admin-line);
            background: #fff;
            font-size: 13px;
            color: var(--admin-muted);
        }

        .admin-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid var(--admin-line);
            background: #fff;
            color: var(--admin-ink);
        }

        .admin-action--primary {
            background: linear-gradient(90deg, var(--admin-brand), var(--admin-brand-soft));
            border: 0;
            color: #fff;
            box-shadow: 0 10px 18px rgba(17, 63, 107, 0.2);
        }

        .admin-content {
            padding: 24px 22px 30px;
        }

        .flash {
            margin-bottom: 18px;
            border-radius: 14px;
            padding: 14px 16px;
            background: #eefbf4;
            color: var(--admin-success);
            border: 1px solid #cdeedb;
            font-weight: 600;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .kpi-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #e4ecf6;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.06);
        }

        .kpi-card-label {
            color: var(--admin-muted);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .kpi-card-value {
            margin-top: 12px;
            font-size: 34px;
            line-height: 1;
            font-weight: 800;
            color: var(--admin-brand);
        }

        .kpi-card-note {
            margin-top: 8px;
            font-size: 13px;
            color: var(--admin-muted);
        }

        .panel-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.9fr);
            gap: 18px;
            align-items: start;
        }

        .panel,
        .table-panel {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #e4ecf6;
            border-radius: 20px;
            box-shadow: 0 20px 38px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #ebf0f6;
        }

        .panel-head h3 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
        }

        .panel-head p {
            margin: 4px 0 0;
            font-size: 13px;
            color: var(--admin-muted);
        }

        .panel-body {
            padding: 18px 20px 20px;
        }

        .quick-actions {
            display: grid;
            gap: 12px;
        }

        .quick-action-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border-radius: 16px;
            border: 1px solid #e7eef7;
            background: #fbfdff;
            text-decoration: none;
            color: var(--admin-ink);
        }

        .quick-action-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(180deg, rgba(17, 63, 107, 0.1), rgba(46, 120, 180, 0.14));
            color: var(--admin-brand);
            font-size: 18px;
        }

        .quick-action-card strong {
            display: block;
            font-size: 15px;
        }

        .quick-action-card span {
            display: block;
            margin-top: 2px;
            font-size: 13px;
            color: var(--admin-muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-wrap .dataTables_wrapper {
            display: grid;
            gap: 12px;
        }

        .table-wrap .dt-top,
        .table-wrap .dt-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .table-wrap .dataTables_length,
        .table-wrap .dataTables_filter,
        .table-wrap .dataTables_info,
        .table-wrap .dataTables_paginate {
            float: none;
            margin: 0;
        }

        .table-wrap .dataTables_length label,
        .table-wrap .dataTables_filter label {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-muted);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .table-wrap .dataTables_length select,
        .table-wrap .dataTables_filter input {
            margin: 0;
            border: 1px solid #d8e3ef;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fff;
            color: var(--admin-ink);
            box-shadow: none;
            width: auto;
        }

        .table-wrap .dataTables_filter input {
            min-width: 220px;
        }

        .table-wrap .dataTables_info {
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-muted);
        }

        .table-wrap .dataTables_paginate {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .table-wrap .dataTables_paginate .paginate_button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 9px;
            border: 1px solid #d9e6f3;
            background: #f8fbff;
            color: #20496f;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            line-height: 1;
            cursor: pointer;
        }

        .table-wrap .dataTables_paginate .paginate_button:hover {
            background: #edf5ff;
            border-color: #bfd5ea;
            color: #113f6b;
        }

        .table-wrap .dataTables_paginate .paginate_button.current,
        .table-wrap .dataTables_paginate .paginate_button.active {
            background: #113f6b;
            border-color: #113f6b;
            color: #fff;
        }

        .table-wrap .dataTables_paginate .paginate_button.disabled {
            opacity: 0.45;
            cursor: default;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid #ebf0f6;
            text-align: left;
            vertical-align: middle;
            font-size: 14px;
        }

        .admin-table thead th {
            background: #f5f9fd;
            color: #25466b;
            font-size: 12px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .patient-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            object-fit: cover;
            background: #edf4fa;
            border: 1px solid #dce6f2;
        }

        .patient-id {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            background: #eef6ff;
            color: #21507f;
            font-weight: 700;
            font-size: 12px;
        }

        .row-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #dbe5f0;
            background: #fff;
            color: var(--admin-ink);
        }

        .btn-inline--danger {
            color: var(--admin-danger);
            border-color: #f0c8cd;
            background: #fff7f8;
        }

        .empty-state {
            padding: 34px 20px;
            text-align: center;
            color: var(--admin-muted);
        }

        .sidebar-collapsed .admin-sidebar {
            width: var(--sidebar-closed-width);
        }

        .sidebar-collapsed .admin-main {
            margin-left: var(--sidebar-closed-width);
        }

        .sidebar-collapsed .admin-brand-copy,
        .sidebar-collapsed .admin-nav span,
        .sidebar-collapsed .admin-sidebar-footer {
            display: none;
        }

        .sidebar-collapsed .admin-nav a {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        .mobile-sidebar-open .admin-sidebar {
            transform: translateX(0);
        }

        .admin-overlay {
            display: none;
        }

        @media (max-width: 1100px) {
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .panel-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 860px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: min(84vw, 290px);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                z-index: 35;
            }

            .mobile-sidebar-open .admin-overlay {
                display: block;
            }

            .admin-topbar {
                padding: 0 14px;
            }

            .admin-content {
                padding: 18px 14px 26px;
            }

            .table-wrap .dataTables_filter input {
                min-width: 150px;
            }

            .admin-topbar-right .admin-chip {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .admin-page-title p { display: none; }
            .admin-action span { display: none; }
        }
    </style>
</head>
<body>
    <div class="admin-shell" id="adminShell">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-sidebar-inner">
                <div class="admin-brand">
                    <div class="admin-brand-badge"><i class="icon-dashboard"></i></div>
                    <div class="admin-brand-copy">
                        <h1>Admin Dashboard</h1>
                        <p>Private Imaging Healthcare</p>
                    </div>
                </div>

                <nav class="admin-nav">
                    <a class="is-active" href="index.php"><i class="icon-dashboard"></i><span>Dashboard</span></a>
                    <a href="identity.php"><i class="icon-plus"></i><span>Create Patient</span></a>
                    <?php if ($currentRole === 'super_admin') { ?>
                        <a href="site_settings.php"><i class="icon-cog"></i><span>Site Settings</span></a>
                        <a href="admin_users.php"><i class="icon-group"></i><span>Admin Users</span></a>
                        <a href="email_templates.php"><i class="icon-edit"></i><span>Email Templates</span></a>
                        <a href="smtp_settings.php"><i class="icon-envelope"></i><span>SMTP Settings</span></a>
                        <a href="theme_settings.php"><i class="icon-tint"></i><span>Theme Settings</span></a>
                    <?php } else { ?>
                        <a href="site_settings.php"><i class="icon-cog"></i><span>Site Settings</span></a>
                    <?php } ?>
                    <?php if ($isImpersonating) { ?>
                        <a href="stop_impersonation.php"><i class="icon-undo"></i><span>Return to Super Admin</span></a>
                    <?php } ?>
                    <a href="change_password.php"><i class="icon-lock"></i><span>Change Password</span></a>
                    <a href="logout.php"><i class="icon-signout"></i><span>Logout</span></a>
                </nav>

                <div class="admin-sidebar-footer">
                    <div class="admin-sidebar-footer-card">
                        Signed in as<br>
                        <strong><?php echo htmlspecialchars($login_session ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                </div>
            </div>
        </aside>

        <div class="admin-overlay" id="adminOverlay"></div>

        <main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                        <i class="icon-reorder"></i>
                    </button>
                    <div class="admin-page-title">
                        <h2>Dashboard</h2>
                        <p>Overview of patients, doctors, wards, and recent activity</p>
                    </div>
                </div>
                <div class="admin-topbar-right">
                    <div class="admin-chip">Signed in: <?php echo htmlspecialchars($login_session ?? '', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8'); ?>)</div>
                    <a class="admin-action admin-action--primary" href="identity.php"><i class="icon-plus"></i><span>New Patient</span></a>
                    <?php if ($currentRole === 'super_admin') { ?>
                        <a class="admin-action" href="site_settings.php"><i class="icon-cog"></i><span>Settings</span></a>
                        <a class="admin-action" href="theme_settings.php"><i class="icon-tint"></i><span>Theme</span></a>
                    <?php } else { ?>
                        <a class="admin-action" href="site_settings.php"><i class="icon-cog"></i><span>Settings</span></a>
                    <?php } ?>
                </div>
            </header>

            <section class="admin-content">
                <?php if ($isImpersonating) { ?>
                    <div class="flash" style="background:#fff5db;border-color:#f1d28a;color:#7f5f00;">
                        You are impersonating an admin account. Original super admin: <?php echo htmlspecialchars($impersonatorName, ENT_QUOTES, 'UTF-8'); ?>.
                        <a href="stop_impersonation.php" style="font-weight:800;color:#6a4f00;">Return now</a>
                    </div>
                <?php } ?>

                <?php if ($flash && !empty($flash['message'])) { ?>
                    <div class="flash <?php echo !empty($flash['type']) ? 'flash-' . htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8') : ''; ?>"><?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-label">Total Patients</div>
                        <div class="kpi-card-value"><?php echo $totalPatients; ?></div>
                        <div class="kpi-card-note">All active records in the system</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-label">Doctors</div>
                        <div class="kpi-card-value"><?php echo $doctorCount; ?></div>
                        <div class="kpi-card-note">Unique doctor names across records</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-label">Wards</div>
                        <div class="kpi-card-value"><?php echo $wardCount; ?></div>
                        <div class="kpi-card-note">Distinct ward numbers referenced</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-label">Recent Entries</div>
                        <div class="kpi-card-value"><?php echo $recentAdmissions; ?></div>
                        <div class="kpi-card-note">Most recently added patient records</div>
                    </div>
                </div>

                <div class="panel-grid">
                    <section class="table-panel">
                        <div class="panel-head">
                            <div>
                                <h3>Patient Registry</h3>
                                <p>Review, edit, and manage all patient records</p>
                            </div>
                        </div>
                        <div class="panel-body table-wrap">
                            <?php if ($totalPatients === 0) { ?>
                                <div class="empty-state">No patient records found yet.</div>
                            <?php } else { ?>
                                <table class="admin-table datatable-1">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Reference</th>
                                            <th>Illness</th>
                                            <th>Ward</th>
                                            <th>Doctor</th>
                                            <th style="text-align:right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patients as $patient) { ?>
                                            <tr>
                                                <td>
                                                    <div class="patient-cell">
                                                        <img class="patient-avatar" src="img/<?php echo htmlspecialchars((string) $patient['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $patient['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars((string) $patient['name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                            <span style="color: var(--admin-muted); font-size: 12px;">Treatment: <?php echo htmlspecialchars((string) $patient['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="patient-id"><?php echo htmlspecialchars((string) $patient['cid'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                <td><?php echo htmlspecialchars((string) $patient['rank'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) $patient['amt'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) $patient['coldate'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <div class="row-actions">
                                                        <a class="btn-inline" href="edit.php?id=<?php echo (int) $patient['id']; ?>"><i class="icon-edit"></i> Edit</a>
                                                        <form method="post" action="delete.php" style="margin:0;" onsubmit="return confirm('Are you sure?');">
                                                            <?php echo pih_admin_csrf_input(); ?>
                                                            <input type="hidden" name="id" value="<?php echo (int) $patient['id']; ?>">
                                                            <button type="submit" class="btn-inline btn-inline--danger"><i class="icon-trash"></i> Delete</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </section>

                    <aside class="panel">
                        <div class="panel-head">
                            <div>
                                <h3>Quick Actions</h3>
                                <p>Common admin tasks</p>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div class="quick-actions">
                                <a class="quick-action-card" href="identity.php">
                                    <div class="quick-action-icon"><i class="icon-plus"></i></div>
                                    <div>
                                        <strong>Create New Patient</strong>
                                        <span>Add a new patient record with passport and ward details.</span>
                                    </div>
                                </a>
                                <a class="quick-action-card" href="site_settings.php">
                                    <div class="quick-action-icon"><i class="icon-cog"></i></div>
                                    <div>
                                        <strong>Manage Site Settings</strong>
                                        <span>Update branding, contact info, and live chat configuration.</span>
                                    </div>
                                </a>
                                <?php if ($currentRole === 'super_admin') { ?>
                                    <a class="quick-action-card" href="admin_users.php">
                                        <div class="quick-action-icon"><i class="icon-group"></i></div>
                                        <div>
                                            <strong>Manage Admin Users</strong>
                                            <span>Create, edit, delete, and impersonate regular admin accounts.</span>
                                        </div>
                                    </a>
                                    <a class="quick-action-card" href="email_templates.php">
                                        <div class="quick-action-icon"><i class="icon-edit"></i></div>
                                        <div>
                                            <strong>Edit Email Templates</strong>
                                            <span>Control branded patient and contact notification emails.</span>
                                        </div>
                                    </a>
                                    <a class="quick-action-card" href="smtp_settings.php">
                                        <div class="quick-action-icon"><i class="icon-envelope"></i></div>
                                        <div>
                                            <strong>SMTP Delivery Settings</strong>
                                            <span>Configure PHPMailer SMTP transport and test mail delivery.</span>
                                        </div>
                                    </a>
                                    <a class="quick-action-card" href="theme_settings.php">
                                        <div class="quick-action-icon"><i class="icon-tint"></i></div>
                                        <div>
                                            <strong>Theme and Timeout</strong>
                                            <span>Manage frontend colors, active theme, and idle timeout policy.</span>
                                        </div>
                                    </a>
                                <?php } ?>
                                <a class="quick-action-card" href="change_password.php">
                                    <div class="quick-action-icon"><i class="icon-lock"></i></div>
                                    <div>
                                        <strong>Secure Credentials</strong>
                                        <span>Change the admin username and password for this portal.</span>
                                    </div>
                                </a>
                                <a class="quick-action-card" href="logout.php">
                                    <div class="quick-action-icon"><i class="icon-signout"></i></div>
                                    <div>
                                        <strong>Logout</strong>
                                        <span>Exit the admin area and return to the public website.</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </main>
    </div>

    <script src="edmin/scripts/jquery-1.9.1.min.js" type="text/javascript"></script>
    <script src="edmin/scripts/jquery-ui-1.10.1.custom.min.js" type="text/javascript"></script>
    <script src="edmin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="edmin/scripts/datatables/jquery.dataTables.js" type="text/javascript"></script>
    <script>
        (function () {
            var shell = document.getElementById('adminShell');
            var toggle = document.getElementById('sidebarToggle');
            var overlay = document.getElementById('adminOverlay');
            var mobileBreakpoint = window.matchMedia('(max-width: 860px)');
            var storageKey = 'pih-admin-sidebar-collapsed';

            function applyDesktopPreference() {
                if (mobileBreakpoint.matches) {
                    shell.classList.remove('sidebar-collapsed');
                    return;
                }
                if (window.localStorage.getItem(storageKey) === '1') {
                    shell.classList.add('sidebar-collapsed');
                } else {
                    shell.classList.remove('sidebar-collapsed');
                }
            }

            toggle.addEventListener('click', function () {
                if (mobileBreakpoint.matches) {
                    shell.classList.toggle('mobile-sidebar-open');
                    return;
                }
                shell.classList.toggle('sidebar-collapsed');
                window.localStorage.setItem(storageKey, shell.classList.contains('sidebar-collapsed') ? '1' : '0');
            });

            overlay.addEventListener('click', function () {
                shell.classList.remove('mobile-sidebar-open');
            });

            window.addEventListener('resize', applyDesktopPreference);
            applyDesktopPreference();

            if (window.jQuery && window.jQuery.fn.dataTable) {
                window.jQuery('.datatable-1').dataTable({
                    sDom: '<"dt-top"lf>t<"dt-bottom"ip>',
                    iDisplayLength: 10,
                    aLengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    oLanguage: {
                        sLengthMenu: 'Show _MENU_ entries',
                        sSearch: 'Search:'
                    },
                    fnDrawCallback: function (settings) {
                        var wrapper = settings.nTableWrapper;
                        var paginate = wrapper ? wrapper.querySelector('.dataTables_paginate') : null;
                        if (!paginate) {
                            return;
                        }

                        var displayLength = settings._iDisplayLength > 0 ? settings._iDisplayLength : 0;
                        var filteredRecords = settings.fnRecordsDisplay();
                        var pageCount = displayLength > 0 ? Math.ceil(filteredRecords / displayLength) : 1;
                        var currentPage = displayLength > 0 ? Math.floor(settings._iDisplayStart / displayLength) : 0;

                        paginate.style.display = pageCount > 1 ? 'inline-flex' : 'none';

                        var prevBtn = paginate.querySelector('.previous');
                        var nextBtn = paginate.querySelector('.next');

                        if (prevBtn) {
                            prevBtn.style.display = currentPage > 0 ? 'inline-flex' : 'none';
                        }
                        if (nextBtn) {
                            nextBtn.style.display = currentPage < pageCount - 1 ? 'inline-flex' : 'none';
                        }
                    }
                });
            }
        })();
    </script>
</body>
</html>
