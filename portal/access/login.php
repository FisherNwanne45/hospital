<?php
require_once __DIR__ . '/../../short.php';
include('log.php'); // Includes Login Script

if (isset($_SESSION['login_user'])) {
    header("location: index.php");
}

$timeoutNotice = isset($_GET['timeout']) && (string) $_GET['timeout'] === '1';

$siteUrl = isset($url) ? (string) $url : '';
$siteName = isset($name) ? (string) $name : 'Private Imaging healthcare Center';
$siteLogoPath = isset($logo_path) ? ltrim((string) $logo_path, '/') : 'static/images/NY_Imaging_Specialists.png';
$siteFaviconPath = isset($favicon_path) ? ltrim((string) $favicon_path, '/') : 'static/images/mri.png';
$livechat = str_replace('\\n', "\n", (string) ($tawk ?? ''));
$stylePath = __DIR__ . '/style.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : (string) time();
$inlineLoginCss = is_readable($stylePath) ? (string) file_get_contents($stylePath) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator Panel</title>
    <link href="<?php echo htmlspecialchars(rtrim($siteUrl, '/') . '/portal/access/style.css?v=' . $styleVersion, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" type="text/css">
    <?php if ($inlineLoginCss !== '') { ?>
        <style><?php echo $inlineLoginCss; ?></style>
    <?php } ?>
    <link rel="icon" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/') . '/' . $siteFaviconPath, ENT_QUOTES, 'UTF-8'); ?>" />
</head>

<body>
    <div class="login-shell">
        <div class="login-ambient"></div>
        <main class="login-grid">
            <section class="brand-panel">
                <a class="brand-mark" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/index.php">
                    <img src="<?php echo htmlspecialchars(rtrim($siteUrl, '/') . '/' . $siteLogoPath, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?> Logo" />
                </a>
                <h1><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>Secure staff access portal for managing patients, records, and site settings.</p>
                <a class="back-link" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>">Back to main website</a>
            </section>

            <section class="login-card-wrap">
                <div class="login-card">
                    <h2>Staff Login</h2>
                    <p class="subhead">Use your authorized account credentials.</p>

                    <form action="" method="post" autocomplete="off">
                        <label for="name">Username</label>
                        <input id="name" name="username" placeholder="Enter username" type="text" required>

                        <label for="password">Password</label>
                        <input id="password" name="password" placeholder="Enter password" type="password" required>

                        <button name="submit" type="submit">Sign In</button>
                    </form>

                    <?php if ($timeoutNotice) { ?>
                        <div class="alert-error">Your session expired due to inactivity. Please sign in again.</div>
                    <?php } ?>

                    <?php if (!empty($error)) { ?>
                        <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php } ?>
                </div>
            </section>
        </main>
    </div>

    <?php if ($livechat !== '') {
        echo $livechat;
    } ?>

</body>

</html>