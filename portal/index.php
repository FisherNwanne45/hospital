<?php
require_once __DIR__ . '/../short.php';

$siteUrl = rtrim((string) ($url ?? ''), '/');
$siteName = trim((string) ($name ?? 'Private Imaging healthcare Center'));
$logoPath = trim((string) ($logo_path ?? 'static/images/NY_Imaging_Specialists.png'));
$faviconPath = trim((string) ($favicon_path ?? 'static/images/mri.png'));

$isAbsolute = static function (string $path): bool {
    return (bool) preg_match('~^(?:https?:)?//~i', $path);
};

$resolveAsset = static function (string $path) use ($siteUrl, $isAbsolute): string {
    if ($path === '') {
        return '';
    }
    if ($isAbsolute($path)) {
        return $path;
    }
    return ($siteUrl !== '' ? $siteUrl : '') . '/' . ltrim($path, '/');
};

$logoUrl = $resolveAsset($logoPath);
$faviconUrl = $resolveAsset($faviconPath);
$homeUrl = $siteUrl !== '' ? $siteUrl . '/index.php' : '../index.php';
$staffUrl = $siteUrl !== '' ? $siteUrl . '/portal/access/login.php' : './access/login.php';
$livechat = str_replace('\\n', "\n", (string) ($tawk ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Portal | <?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="Secure patient details lookup portal.">
    <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        :root {
            --portal-bg: #f4f7fb;
            --portal-surface: #ffffff;
            --portal-ink: #10223b;
            --portal-muted: #546a85;
            --portal-line: #d6e0ec;
            --portal-brand: #0f4c81;
            --portal-brand-strong: #0c3d67;
            --portal-accent: #1f7cc1;
            --portal-alert: #fff3cd;
            --portal-alert-line: #efd78d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--portal-ink);
            background:
                radial-gradient(900px 360px at -5% -10%, rgba(31, 124, 193, 0.16), transparent 60%),
                radial-gradient(800px 320px at 105% 120%, rgba(15, 76, 129, 0.18), transparent 60%),
                var(--portal-bg);
            min-height: 100vh;
        }

        .portal-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .portal-shell {
            width: min(980px, 100%);
            background: var(--portal-surface);
            border: 1px solid var(--portal-line);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(10, 34, 59, 0.12);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .portal-brand {
            background: linear-gradient(160deg, #0f4c81 0%, #1f7cc1 100%);
            color: #ffffff;
            padding: 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
        }

        .portal-brand img {
            max-width: 240px;
            width: 100%;
            height: auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 8px 10px;
        }

        .portal-logo {
            display: inline-block;
            margin-bottom: 20px;
        }

        .portal-brand h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.15;
        }

        .portal-brand p {
            margin: 10px 0 0;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.55;
            font-size: 15px;
        }

        .portal-points {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
            font-size: 14px;
        }

        .portal-points li {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .portal-points li::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #ffffff;
            opacity: 0.92;
            flex-shrink: 0;
        }

        .portal-main {
            padding: 34px;
        }

        .portal-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .portal-link {
            color: var(--portal-accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .portal-link:hover {
            text-decoration: underline;
        }

        .portal-main h2 {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        .portal-sub {
            margin: 8px 0 20px;
            color: var(--portal-muted);
            line-height: 1.55;
        }

        .portal-alert {
            border: 1px solid var(--portal-alert-line);
            background: var(--portal-alert);
            color: #7a5b12;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .portal-form {
            display: grid;
            gap: 16px;
        }

        .portal-field label {
            display: inline-block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: var(--portal-ink);
        }

        .portal-field input {
            width: 100%;
            border: 1px solid #c6d4e3;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 15px;
            color: var(--portal-ink);
            transition: border-color 140ms ease, box-shadow 140ms ease;
        }

        .portal-field input:focus {
            border-color: var(--portal-accent);
            box-shadow: 0 0 0 4px rgba(31, 124, 193, 0.16);
            outline: none;
        }

        .portal-submit {
            border: 0;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(90deg, var(--portal-brand), var(--portal-accent));
            cursor: pointer;
            transition: transform 120ms ease, filter 120ms ease;
        }

        .portal-submit:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
        }

        .portal-submit:active {
            transform: translateY(0);
        }

        .portal-foot {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--portal-muted);
        }

        @media (max-width: 900px) {
            .portal-shell {
                grid-template-columns: 1fr;
            }

            .portal-brand,
            .portal-main {
                padding: 24px;
            }

            .portal-brand h1 {
                font-size: 25px;
            }
        }
    </style>
</head>
<body>
    <main class="portal-wrap">
        <section class="portal-shell" aria-label="Patient portal lookup">
            <aside class="portal-brand">
                <div>
                    <a class="portal-logo" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to site home">
                        <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?> logo">
                    </a>
                    <h1>Patient Portal</h1>
                    <p>Securely check patient treatment and ward details using official patient credentials.</p>
                </div>

                <ul class="portal-points" aria-label="Portal highlights">
                    <li>Fast patient detail lookup</li>
                    <li>Private session flow</li>
                    <li>Mobile-optimized form experience</li>
                </ul>
            </aside>

            <section class="portal-main">
                <div class="portal-top">
                    <a class="portal-link" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Back to Website</a>
                    <a class="portal-link" href="<?php echo htmlspecialchars($staffUrl, ENT_QUOTES, 'UTF-8'); ?>">Staff Login</a>
                </div>

                <h2>Check Patient Details</h2>
                <p class="portal-sub">Enter the patient first name or surname together with the patient ID. The name field can be either one.</p>

                <div class="portal-alert" role="note">Use the patient ID and a name value that appears on the record. The lookup accepts either first name or surname in the same field.</div>

                <form class="portal-form" action="submit.php" method="post" autocomplete="off">
                    <div class="portal-field">
                        <label for="patient-name">First Name or Surname</label>
                        <input id="patient-name" name="search" type="text" placeholder="e.g. Jane or Doe" required>
                    </div>

                    <div class="portal-field">
                        <label for="patient-id">Patient ID</label>
                        <input id="patient-id" name="dropdown" type="text" placeholder="e.g. PT-20419" required>
                    </div>

                    <button class="portal-submit" type="submit" name="submit">Check Details</button>
                </form>

                <div class="portal-foot">
                    <span>Need assistance? Contact your facility administrator.</span>
                    <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </section>
        </section>
    </main>

    <?php if ($livechat !== '') {
        echo $livechat;
    } ?>
</body>
</html>