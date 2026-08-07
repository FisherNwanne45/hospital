<?php
$previewThemeKey = isset($_GET['__theme_preview']) ? trim((string) $_GET['__theme_preview']) : '';
if ($previewThemeKey !== '' && preg_match('/^[a-z0-9_-]+$/i', $previewThemeKey)) {
    $GLOBALS['pih_theme_override'] = $previewThemeKey;
}

require_once __DIR__ . '/short.php';

$themeKey = isset($site_theme['key']) ? (string) $site_theme['key'] : 'xray_theme';
$primaryRoot = __DIR__ . '/themes/' . $themeKey;
$primaryRootReal = realpath($primaryRoot);
$fallbackRootReal = realpath(__DIR__ . '/themes/xray_theme');

if ($primaryRootReal === false || !is_dir($primaryRootReal) || $fallbackRootReal === false || !is_dir($fallbackRootReal)) {
    http_response_code(500);
    echo 'Theme directory is not available.';
    exit;
}

$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

$previewPathParam = isset($_GET['__theme_preview_path']) ? trim((string) $_GET['__theme_preview_path']) : '';
if ($previewPathParam !== '') {
    $requestPath = '/' . ltrim($previewPathParam, '/');
}

if ($scriptDir !== '' && $scriptDir !== '/' && strpos($requestPath, $scriptDir) === 0) {
    $requestPath = substr($requestPath, strlen($scriptDir));
}

$requestPath = '/' . ltrim($requestPath, '/');
if ($requestPath === '/' || $requestPath === '') {
    $requestPath = '/index.php';
} elseif (substr($requestPath, -1) === '/') {
    $requestPath .= 'index.php';
}

$relative = ltrim($requestPath, '/');
if (strpos($relative, '..') !== false) {
    http_response_code(400);
    echo 'Invalid path.';
    exit;
}

$candidateReal = null;

$relativeCandidates = [$relative];
if (substr($relative, -4) === '.php') {
    $relativeCandidates[] = substr($relative, 0, -4) . '.html';
}

function pih_find_theme_file($themeRootReal, $relativeCandidates)
{
    foreach ($relativeCandidates as $relativeCandidate) {
        $candidate = realpath($themeRootReal . '/' . $relativeCandidate);
        if ($candidate !== false && strpos($candidate, $themeRootReal) === 0 && is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function pih_inject_theme_branding($html, $themeKey)
{
    global $url, $logo_path, $favicon_path, $name, $email, $phone, $addr;
    global $theme_primary, $theme_accent, $theme_surface, $theme_text, $theme_muted, $theme_css_path, $theme_extra_colors;

    $themeCssHref = '';
    if (is_string($theme_css_path) && trim($theme_css_path) !== '') {
        $themeCssHref = rtrim((string) $url, '/') . '/' . ltrim((string) $theme_css_path, '/');
    }

    $extraVars = '';
    if (is_array($theme_extra_colors)) {
        foreach ($theme_extra_colors as $extraKey => $extraValue) {
            $safeKey = preg_replace('/[^a-z0-9_-]/i', '', (string) $extraKey);
            if ($safeKey === '') {
                continue;
            }

            $cssVarName = '--theme-' . str_replace('_', '-', strtolower($safeKey));
            $extraVars .= $cssVarName . ':' . htmlspecialchars((string) $extraValue, ENT_QUOTES, 'UTF-8') . ';';
        }
    }

    $dynamicHead = '<style>:root{--brand-primary:' . htmlspecialchars((string) $theme_primary, ENT_QUOTES, 'UTF-8') . ';--brand-accent:' . htmlspecialchars((string) $theme_accent, ENT_QUOTES, 'UTF-8') . ';--brand-surface:' . htmlspecialchars((string) $theme_surface, ENT_QUOTES, 'UTF-8') . ';--brand-text:' . htmlspecialchars((string) $theme_text, ENT_QUOTES, 'UTF-8') . ';--brand-muted:' . htmlspecialchars((string) $theme_muted, ENT_QUOTES, 'UTF-8') . ';' . $extraVars . '}</style>';
    if ($themeCssHref !== '') {
        $dynamicHead .= '<link rel="stylesheet" href="' . htmlspecialchars($themeCssHref, ENT_QUOTES, 'UTF-8') . '">';
    }

    if (stripos($html, '</head>') !== false) {
        $html = preg_replace('/<\/head>/i', $dynamicHead . '</head>', $html, 1);
    }

    $faviconUrl = rtrim((string) $url, '/') . '/' . ltrim((string) $favicon_path, '/');
    $html = preg_replace('/<link\s+rel="shortcut icon"\s+href="[^"]*"\s*>/i', '<link rel="shortcut icon" href="' . htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8') . '">', $html, 1);

    if ($themeKey === 'hospital_theme') {
        $logoUrl = rtrim((string) $url, '/') . '/' . ltrim((string) $logo_path, '/');
        $safeName = htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars((string) $email, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars((string) $phone, ENT_QUOTES, 'UTF-8');
        $safeAddr = htmlspecialchars((string) $addr, ENT_QUOTES, 'UTF-8');

        $bridgeScript = '<script>(function(){'
            . 'var logo=document.querySelector(".logo_sec img");if(logo){logo.src="' . addslashes($logoUrl) . '";logo.alt="' . addslashes($safeName) . '";}'
            . 'var logoLink=document.querySelector(".logo_sec a");if(logoLink){logoLink.href="' . addslashes(rtrim((string) $url, '/')) . '/index.php";logoLink.title="' . addslashes($safeName) . '";}'
            . 'var title=document.querySelector("title");if(title){title.textContent="' . addslashes($safeName) . '";}'
            . 'var emailNode=document.querySelector(".top-address .address-box:nth-child(1) a");if(emailNode){emailNode.href="mailto:' . addslashes($safeEmail) . '";emailNode.textContent="' . addslashes($safeEmail) . '";}'
            . 'var phoneNode=document.querySelector(".top-address .address-box:nth-child(2) a");if(phoneNode){phoneNode.href="tel:' . addslashes($safePhone) . '";phoneNode.textContent="' . addslashes($safePhone) . '";}'
            . 'var addrNode=document.querySelector(".top-address .address-box:nth-child(3) p");if(addrNode){addrNode.textContent="' . addslashes($safeAddr) . '";}'
            . 'var copyNode=document.querySelector(".copyright p");if(copyNode){copyNode.textContent="' . addslashes('© Copyright, ' . (string) $name . ', All Rights Reserved.') . '";}'
            . '})();</script>';

        if (stripos($html, '</body>') !== false) {
            $html = preg_replace('/<\/body>/i', $bridgeScript . '</body>', $html, 1);
        } else {
            $html .= $bridgeScript;
        }
    }

    return $html;
}

$candidateReal = pih_find_theme_file($primaryRootReal, $relativeCandidates);

if ($candidateReal === null) {
    $candidateReal = pih_find_theme_file($fallbackRootReal, $relativeCandidates);
}

if ($candidateReal === null) {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}

$ext = strtolower((string) pathinfo($candidateReal, PATHINFO_EXTENSION));
if ($ext !== 'php') {
    if ($ext === 'html' || $ext === 'htm') {
        $content = file_get_contents($candidateReal);
        if ($content === false) {
            http_response_code(500);
            echo 'Unable to render page.';
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo pih_inject_theme_branding($content, $themeKey);
        exit;
    }

    $mime = function_exists('mime_content_type') ? (string) mime_content_type($candidateReal) : 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($candidateReal);
    exit;
}

$previousCwd = getcwd();
if ($previousCwd !== false) {
    chdir(dirname($candidateReal));
}

require basename($candidateReal);

if ($previousCwd !== false) {
    chdir($previousCwd);
}
