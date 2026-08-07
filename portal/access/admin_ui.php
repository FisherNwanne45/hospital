<?php

function pih_admin_get_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['pih_admin_csrf_token'])) {
        $_SESSION['pih_admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['pih_admin_csrf_token'];
}

function pih_admin_csrf_input(): string
{
    $token = htmlspecialchars(pih_admin_get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function pih_admin_validate_csrf(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $sessionToken = isset($_SESSION['pih_admin_csrf_token']) ? (string) $_SESSION['pih_admin_csrf_token'] : '';

    return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function pih_admin_set_flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['pih_admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pih_admin_consume_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['pih_admin_flash']) || !is_array($_SESSION['pih_admin_flash'])) {
        return null;
    }

    $flash = $_SESSION['pih_admin_flash'];
    unset($_SESSION['pih_admin_flash']);
    return $flash;
}

function pih_admin_nav_items()
{
    $isSuperAdmin = function_exists('pih_admin_is_super_admin')
        ? pih_admin_is_super_admin()
        : (((int) ($_SESSION['login_is_super_admin'] ?? 0)) === 1 || (string) ($_SESSION['login_role'] ?? '') === 'super_admin');
    $isImpersonating = !empty($_SESSION['pih_impersonator_id']);

    $items = [
        'dashboard' => ['href' => 'index.php', 'icon' => 'icon-dashboard', 'label' => 'Dashboard'],
        'identity' => ['href' => 'identity.php', 'icon' => 'icon-plus', 'label' => 'Create Patient'],
        'change_password' => ['href' => 'change_password.php', 'icon' => 'icon-lock', 'label' => 'Change Password'],
    ];

    if ($isSuperAdmin) {
        $items['site_settings'] = ['href' => 'site_settings.php', 'icon' => 'icon-cog', 'label' => 'Site Settings'];
        $items['admin_users'] = ['href' => 'admin_users.php', 'icon' => 'icon-group', 'label' => 'Admin Users'];
        $items['admin_log'] = ['href' => 'admin_log.php', 'icon' => 'icon-list-alt', 'label' => 'Admin Log'];
        $items['email_templates'] = ['href' => 'email_templates.php', 'icon' => 'icon-edit', 'label' => 'Email Templates'];
        $items['smtp_settings'] = ['href' => 'smtp_settings.php', 'icon' => 'icon-envelope', 'label' => 'SMTP Settings'];
        $items['theme_settings'] = ['href' => 'theme_settings.php', 'icon' => 'icon-tint', 'label' => 'Theme Settings'];
    } else {
        $items['site_settings'] = ['href' => 'site_settings.php', 'icon' => 'icon-cog', 'label' => 'Site Settings'];
    }

    if ($isImpersonating) {
        $items['stop_impersonation'] = ['href' => 'stop_impersonation.php', 'icon' => 'icon-undo', 'label' => 'Return to Super Admin'];
    }

    $items['logout'] = ['href' => 'logout.php', 'icon' => 'icon-signout', 'label' => 'Logout'];

    return $items;
}

function pih_admin_render_start($pageTitle, $pageSubtitle, $activeNav, $actions = [])
{
    $navItems = pih_admin_nav_items();
    $loginSession = htmlspecialchars((string) ($GLOBALS['login_session'] ?? ''), ENT_QUOTES, 'UTF-8');
    $isSuperAdmin = ((int) ($GLOBALS['login_is_super_admin'] ?? ($_SESSION['login_is_super_admin'] ?? 0))) === 1;
    $role = htmlspecialchars($isSuperAdmin ? 'super_admin' : 'admin', ENT_QUOTES, 'UTF-8');
    $isImpersonating = !empty($_SESSION['pih_impersonator_id']);
    $impersonator = htmlspecialchars((string) ($_SESSION['pih_impersonator_username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $titleEscaped = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    $subtitleEscaped = htmlspecialchars($pageSubtitle, ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titleEscaped; ?></title>
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

        .admin-shell { min-height: 100vh; display: flex; }

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

        .admin-nav { display: flex; flex-direction: column; gap: 8px; }

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

        .admin-nav a i { width: 18px; text-align: center; font-size: 16px; }

        .admin-nav a:hover,
        .admin-nav a.is-active {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            transform: translateX(1px);
        }

        .admin-sidebar-footer { margin-top: auto; padding-top: 16px; }

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

        .admin-page-title h2 { margin: 0; font-size: 22px; line-height: 1.2; }
        .admin-page-title p { margin: 2px 0 0; color: var(--admin-muted); font-size: 13px; }

        .admin-topbar-right { display: flex; align-items: center; gap: 10px; }

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

        .admin-content { padding: 24px 22px 30px; }

        .panel,
        .table-panel,
        .module {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #e4ecf6;
            border-radius: 20px;
            box-shadow: 0 20px 38px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .module-head,
        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid #ebf0f6;
            background: #fff;
        }

        .module-head h3,
        .panel-head h3 {
            margin: 0;
            font-size: 20px;
            line-height: 1.2;
            color: var(--admin-ink);
            text-shadow: none;
        }

        .panel-head p { margin: 4px 0 0; font-size: 13px; color: var(--admin-muted); }

        .module-body,
        .panel-body {
            padding: 20px;
            background: transparent;
        }

        .control-group { margin-bottom: 16px; }
        .control-label { color: var(--admin-muted); font-weight: 600; }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"],
        input[type="color"],
        textarea {
            border-radius: 10px !important;
            border: 1px solid var(--admin-line) !important;
            box-shadow: none !important;
            padding: 10px 12px !important;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            border-color: var(--admin-brand-soft) !important;
            box-shadow: 0 0 0 3px rgba(61, 127, 181, 0.2) !important;
        }

        .btn.btn-success {
            border: 0;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--admin-brand), var(--admin-brand-soft));
            padding: 11px 20px;
            font-weight: 700;
            text-shadow: none;
            box-shadow: 0 8px 14px rgba(17, 71, 122, 0.24);
        }

        .alert {
            border-radius: 10px;
            border-width: 1px;
            padding: 12px 14px;
            margin-bottom: 16px;
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

        .impersonation-banner {
            margin-bottom: 18px;
            border-radius: 14px;
            padding: 14px 16px;
            background: #fff5db;
            color: #7f5f00;
            border: 1px solid #f1d28a;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .impersonation-banner .btn-link {
            font-weight: 800;
            color: #6a4f00;
        }

        .flash.flash-error {
            background: #fff1f2;
            color: var(--admin-danger);
            border: 1px solid #f2c5cb;
        }

        .flash.flash-info {
            background: #eef6ff;
            color: var(--admin-brand);
            border: 1px solid #cfe1f7;
        }

        .sidebar-collapsed .admin-sidebar { width: var(--sidebar-closed-width); }
        .sidebar-collapsed .admin-main { margin-left: var(--sidebar-closed-width); }
        .sidebar-collapsed .admin-brand-copy,
        .sidebar-collapsed .admin-nav span,
        .sidebar-collapsed .admin-sidebar-footer { display: none; }
        .sidebar-collapsed .admin-nav a { justify-content: center; padding-left: 0; padding-right: 0; }

        .mobile-sidebar-open .admin-sidebar { transform: translateX(0); }
        .admin-overlay { display: none; }

        .footer { margin-top: 20px; background: transparent; border: 0; color: #5b6b82; padding: 0 22px 22px; }

        .admin-content .dataTables_wrapper {
            display: grid;
            gap: 12px;
        }

        .admin-content .dt-top,
        .admin-content .dt-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .admin-content .dataTables_length,
        .admin-content .dataTables_filter,
        .admin-content .dataTables_info,
        .admin-content .dataTables_paginate {
            float: none;
            margin: 0;
        }

        .admin-content .dataTables_length label,
        .admin-content .dataTables_filter label {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-muted);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .admin-content .dataTables_length select,
        .admin-content .dataTables_filter input {
            margin: 0;
            border: 1px solid #d8e3ef;
            border-radius: 10px;
            padding: 8px 10px;
            background: #fff;
            color: var(--admin-ink);
            box-shadow: none;
            width: auto;
        }

        .admin-content .dataTables_filter input {
            min-width: 220px;
        }

        .admin-content .dataTables_info {
            font-size: 13px;
            font-weight: 600;
            color: var(--admin-muted);
        }

        .admin-content .dataTables_paginate {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .admin-content .dataTables_paginate .paginate_button {
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

        .admin-content .dataTables_paginate .paginate_button:hover {
            background: #edf5ff;
            border-color: #bfd5ea;
            color: #113f6b;
        }

        .admin-content .dataTables_paginate .paginate_button.current,
        .admin-content .dataTables_paginate .paginate_button.active {
            background: #113f6b;
            border-color: #113f6b;
            color: #fff;
        }

        .admin-content .dataTables_paginate .paginate_button.disabled {
            opacity: 0.45;
            cursor: default;
        }

        @media (max-width: 860px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: min(84vw, 290px);
            }
            .admin-main { margin-left: 0; }
            .admin-overlay {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                z-index: 35;
            }
            .mobile-sidebar-open .admin-overlay { display: block; }
            .admin-topbar { padding: 0 14px; }
            .admin-content { padding: 18px 14px 26px; }
            .admin-topbar-right .admin-chip { display: none; }
            .footer { padding: 0 14px 20px; }

            .admin-content .dataTables_filter input {
                min-width: 150px;
            }
        }

        @media (max-width: 640px) {
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
                <?php foreach ($navItems as $navKey => $navItem) { ?>
                    <a class="<?php echo $navKey === $activeNav ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars($navItem['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="<?php echo htmlspecialchars($navItem['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                        <span><?php echo htmlspecialchars($navItem['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php } ?>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="admin-sidebar-footer-card">
                    Signed in as<br>
                    <strong><?php echo $loginSession; ?></strong>
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
                    <h2><?php echo $titleEscaped; ?></h2>
                    <p><?php echo $subtitleEscaped; ?></p>
                </div>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-chip">Signed in: <?php echo $loginSession; ?> (<?php echo $role; ?>)</div>
                <?php foreach ($actions as $action) { ?>
                    <a class="admin-action<?php echo !empty($action['primary']) ? ' admin-action--primary' : ''; ?>" href="<?php echo htmlspecialchars((string) $action['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($action['icon'])) { ?><i class="<?php echo htmlspecialchars((string) $action['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i><?php } ?>
                        <span><?php echo htmlspecialchars((string) $action['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php } ?>
            </div>
        </header>

        <section class="admin-content">
            <?php if ($isImpersonating) { ?>
                <div class="impersonation-banner">
                    <span>You are impersonating an admin account. Original super admin: <?php echo $impersonator; ?></span>
                    <a class="btn-link" href="stop_impersonation.php">Return now</a>
                </div>
            <?php } ?>
            <?php $flash = pih_admin_consume_flash(); ?>
            <?php if ($flash && !empty($flash['message'])) { ?>
                <div class="flash <?php echo !empty($flash['type']) ? 'flash-' . htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8') : ''; ?>"><?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>
    <?php
}

function pih_admin_render_end($extraScripts = '')
{
    ?>
        </section>
        <div class="footer">
            <div class="container-fluid">
                <b class="copyright">&copy; 2020 - 2026 Fisher Designs </b>All rights reserved.
            </div>
        </div>
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
            if (!shell) {
                return;
            }
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

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (mobileBreakpoint.matches) {
                    shell.classList.toggle('mobile-sidebar-open');
                    return;
                }
                shell.classList.toggle('sidebar-collapsed');
                window.localStorage.setItem(storageKey, shell.classList.contains('sidebar-collapsed') ? '1' : '0');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function () {
                shell.classList.remove('mobile-sidebar-open');
            });
        }

        window.addEventListener('resize', applyDesktopPreference);
        applyDesktopPreference();

        if (window.jQuery && window.jQuery.fn.dataTable) {
            window.jQuery('.datatable-1').each(function () {
                var node = this;
                var isInitialized = false;

                if (window.jQuery.fn.DataTable && typeof window.jQuery.fn.DataTable.isDataTable === 'function') {
                    isInitialized = window.jQuery.fn.DataTable.isDataTable(node);
                } else if (window.jQuery.fn.dataTable && typeof window.jQuery.fn.dataTable.fnIsDataTable === 'function') {
                    isInitialized = window.jQuery.fn.dataTable.fnIsDataTable(node);
                }

                if (isInitialized) {
                    return;
                }

                window.jQuery(node).dataTable({
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
            });
        }
    })();
</script>
<?php echo $extraScripts; ?>
</body>
</html>
    <?php
}