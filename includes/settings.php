<?php

function pih_get_default_settings()
{
    return [
        'site_name' => 'Private Imaging healthcare Center',
        'site_short_name' => 'VPH',
        'site_addr_primary' => "1290 Hornby Street, 2nd Floor,\nVancouver, BC V6Z 1W2, Canada",
        'site_addr_secondary' => '438 N Frederick Ave, Gaithersburg, MD 20877, United States',
        'site_phone_primary' => '+1 (236)-260-1221',
        'site_phone_secondary' => '+1 (236)-260-1221',
        'site_url' => 'http://localhost/privateimaginghealthcare',
        'site_host' => 'privateimaginghealthcare.com',
        'site_email_primary' => 'info@privateimaginghealthcare.com',
        'site_email_secondary' => 'support@privateimaginghealthcare.com',
        'site_logo_path' => 'static/images/NY_Imaging_Specialists.png',
        'site_favicon_path' => 'static/images/mri.png',
        'installation_complete' => '0',
        'install_completed_at' => '',
        'site_theme' => 'xray_theme',
        'admin_session_timeout_minutes' => '30',
        'livechat_script' => '<script type="text/javascript">\nvar _smartsupp = _smartsupp || {};\n_smartsupp.key = "2700a30912884265ff0495e8334f7342bfc3a179";\nwindow.smartsupp||(function(d) {\n  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];\n  s=d.getElementsByTagName("script")[0];c=d.createElement("script");\n  c.type="text/javascript";c.charset="utf-8";c.async=true;\n  c.src="https://www.smartsuppchat.com/loader.js?";s.parentNode.insertBefore(c,s);\n})(document);\n</script>\n<noscript> Powered by <a href="https://www.smartsupp.com" target="_blank">Smartsupp</a></noscript>',
        'theme_primary' => '#0f4c81',
        'theme_accent' => '#1f7cc1',
        'theme_surface' => '#f6fbff',
        'theme_text' => '#102a43',
        'theme_muted' => '#486581',
        'theme_palettes_json' => '',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password_enc' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => '',
        'smtp_auth' => '1',
        'smtp_timeout' => '30',
        'smtp_encryption' => 'starttls',
        'smtp_test_to' => ''
    ];
}

function pih_get_frontend_theme_definitions()
{
    $themes = [];
    $defaultPalettes = [
        'xray_theme' => [
            'primary' => '#0f4c81',
            'accent' => '#1f7cc1',
            'surface' => '#f6fbff',
            'text' => '#102a43',
            'muted' => '#486581',
        ],
        'clinic_theme' => [
            'primary' => '#176b87',
            'accent' => '#64ccc5',
            'surface' => '#f4fcfb',
            'text' => '#0e3440',
            'muted' => '#49727a',
        ],
        'hospital_theme' => [
            'primary' => '#1f3a5f',
            'accent' => '#4a90e2',
            'surface' => '#f3f7fb',
            'text' => '#172b45',
            'muted' => '#5b7087',
        ],
    ];
    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        return $themes;
    }

    $themesDir = $root . '/themes';
    if (!is_dir($themesDir)) {
        return $themes;
    }

    $entries = scandir($themesDir);
    if (!is_array($entries)) {
        return $themes;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $themeAbs = $themesDir . '/' . $entry;
        if (!is_dir($themeAbs)) {
            continue;
        }

        $headerRel = 'themes/' . $entry . '/header.php';
        $footerRel = 'themes/' . $entry . '/footer.php';
        $cssRel = 'themes/' . $entry . '/theme.css';

        if (!is_file($root . '/' . $headerRel) || !is_file($root . '/' . $footerRel)) {
            continue;
        }

        $label = ucwords(str_replace(['-', '_'], ' ', $entry));
        $previewPath = 'index.php';
        $previewColors = isset($defaultPalettes[$entry]) ? $defaultPalettes[$entry] : [
            'primary' => '#0f4c81',
            'accent' => '#1f7cc1',
            'surface' => '#f6fbff',
            'text' => '#102a43',
            'muted' => '#486581',
        ];
        $themeJson = $themeAbs . '/theme.json';
        if (is_file($themeJson)) {
            $jsonRaw = file_get_contents($themeJson);
            if ($jsonRaw !== false) {
                $json = json_decode($jsonRaw, true);
                if (is_array($json) && !empty($json['name']) && is_string($json['name'])) {
                    $label = trim($json['name']);
                }

                if (is_array($json) && !empty($json['preview_path']) && is_string($json['preview_path'])) {
                    $previewPath = ltrim(trim($json['preview_path']), '/');
                    if ($previewPath === '') {
                        $previewPath = 'index.php';
                    }
                }

                if (is_array($json) && isset($json['preview_colors']) && is_array($json['preview_colors'])) {
                    $provided = $json['preview_colors'];
                    $keys = ['primary', 'accent', 'surface', 'text', 'muted'];
                    foreach ($keys as $colorKey) {
                        if (isset($provided[$colorKey]) && is_string($provided[$colorKey]) && trim($provided[$colorKey]) !== '') {
                            $previewColors[$colorKey] = trim($provided[$colorKey]);
                        }
                    }
                }
            }
        }

        $themes[$entry] = [
            'label' => $label,
            'header' => $headerRel,
            'footer' => $footerRel,
            'stylesheet' => is_file($root . '/' . $cssRel) ? $cssRel : '',
            'screenshot' => 'themes/' . $entry . '/screenshot.png',
            'body_class' => 'theme-' . str_replace('_', '-', strtolower($entry)),
            'preview_path' => $previewPath,
            'preview_colors' => $previewColors,
        ];
    }

    return $themes;
}

function pih_get_frontend_theme($themeKey)
{
    $themes = pih_get_frontend_theme_definitions();
    $defaultKey = 'xray_theme';
    $candidate = is_string($themeKey) ? trim($themeKey) : '';

    if ($candidate === 'blue_clinic') {
        $candidate = 'xray_theme';
    }

    if ($candidate !== '' && array_key_exists($candidate, $themes)) {
        return $themes[$candidate] + ['key' => $candidate];
    }

    if (array_key_exists($defaultKey, $themes)) {
        return $themes[$defaultKey] + ['key' => $defaultKey];
    }

    $firstKey = array_key_first($themes);
    if (is_string($firstKey)) {
        return $themes[$firstKey] + ['key' => $firstKey];
    }

    return [
        'key' => $defaultKey,
        'label' => 'Xray Theme',
        'header' => 'themes/xray_theme/header.php',
        'footer' => 'themes/xray_theme/footer.php',
        'stylesheet' => 'themes/xray_theme/theme.css',
        'screenshot' => 'themes/xray_theme/screenshot.png',
        'body_class' => 'theme-xray-theme',
        'preview_path' => 'index.php',
        'preview_colors' => [
            'primary' => '#0f4c81',
            'accent' => '#1f7cc1',
            'surface' => '#f6fbff',
            'text' => '#102a43',
            'muted' => '#486581',
        ],
    ];
}

function pih_get_theme_default_palette(string $themeKey, ?array $themes = null): array
{
    $themeList = is_array($themes) ? $themes : pih_get_frontend_theme_definitions();
    $fallback = [
        'primary' => '#0f4c81',
        'accent' => '#1f7cc1',
        'surface' => '#f6fbff',
        'text' => '#102a43',
        'muted' => '#486581',
    ];

    if (isset($themeList[$themeKey]) && is_array($themeList[$themeKey])) {
        $previewColors = isset($themeList[$themeKey]['preview_colors']) && is_array($themeList[$themeKey]['preview_colors'])
            ? $themeList[$themeKey]['preview_colors']
            : [];

        $basePalette = pih_sanitize_theme_palette($previewColors, $fallback);
        $extraFields = pih_get_theme_extra_color_definitions($themeKey);
        foreach ($extraFields as $extraKey => $extraMeta) {
            $basePalette[$extraKey] = isset($extraMeta['default']) && is_string($extraMeta['default'])
                ? strtolower(trim($extraMeta['default']))
                : '#000000';
        }

        return $basePalette;
    }

    return $fallback;
}

function pih_get_theme_extra_color_definitions(string $themeKey): array
{
    $definitions = [
        'xray_theme' => [
            'topbar_gradient_start' => [
                'label' => 'Topbar Gradient Start',
                'description' => 'Starting color for the xray topbar gradient.',
                'default' => '#0f4c81',
            ],
            'topbar_gradient_end' => [
                'label' => 'Topbar Gradient End',
                'description' => 'Ending color for the xray topbar gradient.',
                'default' => '#1f7cc1',
            ],
            'footer_strip_bg' => [
                'label' => 'Footer Strip Background',
                'description' => 'Background color for the xray copyright strip area.',
                'default' => '#e9f4ff',
            ],
            'legacy_emphasis_color' => [
                'label' => 'Legacy Inline Emphasis Color',
                'description' => 'Controls legacy inline rgb(110,85,196) accents used on older xray pages (CTA backgrounds, highlight links, and button borders).',
                'default' => '#6e55c4',
            ],
        ],
        'clinic_theme' => [
            'topbar_gradient_start' => [
                'label' => 'Topbar Gradient Start',
                'description' => 'Starting color for the clinic topbar gradient.',
                'default' => '#0f4c81',
            ],
            'topbar_gradient_end' => [
                'label' => 'Topbar Gradient End',
                'description' => 'Ending color for the clinic topbar gradient.',
                'default' => '#1f7cc1',
            ],
            'clinic_cta_brand' => [
                'label' => 'Clinic CTA Brand Color',
                'description' => 'Button color used by clinic CTA blocks and related call-to-action links.',
                'default' => '#176b87',
            ],
            'footer_strip_bg' => [
                'label' => 'Footer Strip Background',
                'description' => 'Background color for the clinic copyright strip area.',
                'default' => '#e9f4ff',
            ],
        ],
        'hospital_theme' => [
            'copyright_bg' => [
                'label' => 'Copyright Bar Background',
                'description' => 'Background color for the hospital footer copyright bar.',
                'default' => '#0f213a',
            ],
        ],
    ];

    return isset($definitions[$themeKey]) && is_array($definitions[$themeKey])
        ? $definitions[$themeKey]
        : [];
}

function pih_sanitize_theme_palette(array $candidate, array $fallback): array
{
    $hexPattern = '/^#[0-9a-fA-F]{6}$/';
    $sanitized = [];

    foreach ($fallback as $key => $fallbackColor) {
        $rawValue = isset($candidate[$key]) ? trim((string) $candidate[$key]) : '';
        $fallbackValue = trim((string) $fallbackColor);
        $sanitized[$key] = preg_match($hexPattern, $rawValue) ? strtolower($rawValue) : strtolower($fallbackValue);
    }

    return $sanitized;
}

function pih_get_theme_palettes_map(array $settings, ?string $activeThemeKey = null): array
{
    $themes = pih_get_frontend_theme_definitions();
    $themeKeys = array_keys($themes);
    if (empty($themeKeys)) {
        $themeKeys = ['xray_theme'];
    }

    $activeKey = trim((string) ($activeThemeKey ?? ($settings['site_theme'] ?? 'xray_theme')));
    if (!in_array($activeKey, $themeKeys, true)) {
        $activeKey = in_array('xray_theme', $themeKeys, true) ? 'xray_theme' : (string) $themeKeys[0];
    }

    $legacyPalette = pih_sanitize_theme_palette([
        'primary' => (string) ($settings['theme_primary'] ?? '#0f4c81'),
        'accent' => (string) ($settings['theme_accent'] ?? '#1f7cc1'),
        'surface' => (string) ($settings['theme_surface'] ?? '#f6fbff'),
        'text' => (string) ($settings['theme_text'] ?? '#102a43'),
        'muted' => (string) ($settings['theme_muted'] ?? '#486581'),
    ], pih_get_theme_default_palette($activeKey, $themes));

    $rawJson = trim((string) ($settings['theme_palettes_json'] ?? ''));
    $stored = [];
    if ($rawJson !== '') {
        $decoded = json_decode($rawJson, true);
        if (is_array($decoded)) {
            $stored = $decoded;
        }
    }

    $result = [];
    foreach ($themeKeys as $themeKey) {
        $themeKey = (string) $themeKey;
        $fallback = pih_get_theme_default_palette($themeKey, $themes);

        if (isset($stored[$themeKey]) && is_array($stored[$themeKey])) {
            $result[$themeKey] = pih_sanitize_theme_palette($stored[$themeKey], $fallback);
            continue;
        }

        // Preserve existing live colors for active theme during migration.
        $result[$themeKey] = $themeKey === $activeKey
            ? pih_sanitize_theme_palette($legacyPalette, $fallback)
            : $fallback;
    }

    return $result;
}

function pih_get_theme_palette_for_key(string $themeKey, array $settings): array
{
    $theme = pih_get_frontend_theme($themeKey);
    $resolvedKey = isset($theme['key']) ? (string) $theme['key'] : 'xray_theme';
    $paletteMap = pih_get_theme_palettes_map($settings, $resolvedKey);

    if (isset($paletteMap[$resolvedKey]) && is_array($paletteMap[$resolvedKey])) {
        return $paletteMap[$resolvedKey];
    }

    return pih_get_theme_default_palette($resolvedKey);
}

function pih_get_theme_color_field_definitions(string $themeKey): array
{
    $defaults = [
        'primary' => [
            'label' => 'Primary Brand Areas',
            'description' => 'Main structural and call-to-action surfaces for this theme.',
        ],
        'accent' => [
            'label' => 'Accent and Interactive Areas',
            'description' => 'Links, secondary buttons, and interactive highlights.',
        ],
        'surface' => [
            'label' => 'Page and Section Surfaces',
            'description' => 'Background surfaces for pages and light content zones.',
        ],
        'text' => [
            'label' => 'Primary Text Areas',
            'description' => 'Headings and important foreground text.',
        ],
        'muted' => [
            'label' => 'Muted and Support Text Areas',
            'description' => 'Secondary text, metadata, and softer labels.',
        ],
    ];

    $definitions = [
        'xray_theme' => [
            'primary' => [
                'label' => 'Topbar Gradient Base and Primary Buttons',
                'description' => 'Applies to topbar brand tone, primary buttons, and principal action anchors.',
            ],
            'accent' => [
                'label' => 'Links, Icon Highlights, and Secondary Contrast',
                'description' => 'Used by text links, icon accents, and secondary attention elements.',
            ],
            'surface' => [
                'label' => 'Body Background and Card Canvas',
                'description' => 'Controls page canvas feel and light section backgrounds.',
            ],
            'text' => [
                'label' => 'Main Heading and Content Text',
                'description' => 'Used for readable high-priority text content across pages.',
            ],
            'muted' => [
                'label' => 'Meta Text and Subtle UI Copy',
                'description' => 'Used for descriptive, secondary, and supporting copy.',
            ],
        ],
        'clinic_theme' => [
            'primary' => [
                'label' => 'Clinic Header Bar and Core CTA Backgrounds',
                'description' => 'Drives top navigation bar treatment and core clinic call-to-action blocks.',
            ],
            'accent' => [
                'label' => 'CTA Contrast, Inline Links, and Icon Signals',
                'description' => 'Used for interactive accents and contrast treatments on key touchpoints.',
            ],
            'surface' => [
                'label' => 'Clinical Surface Tone for Sections',
                'description' => 'Sets neutral medical-style background tone for page and section surfaces.',
            ],
            'text' => [
                'label' => 'Clinical Readability Text',
                'description' => 'Primary readability color for headings and core content text.',
            ],
            'muted' => [
                'label' => 'Supportive Clinical Metadata Text',
                'description' => 'Softer text for labels, metadata, and reduced visual emphasis.',
            ],
        ],
        'hospital_theme' => [
            'primary' => [
                'label' => 'Hospital Header, Footer, and Banner Base',
                'description' => 'Applies to major hospital structural zones including top, header, and footer blocks.',
            ],
            'accent' => [
                'label' => 'Portal Action, Secondary Buttons, and Link Highlights',
                'description' => 'Used for patient portal emphasis, secondary actions, and highlighted links.',
            ],
            'surface' => [
                'label' => 'Hospital Content Surfaces and Card Backdrops',
                'description' => 'Controls body background and the light tone behind cards and content modules.',
            ],
            'text' => [
                'label' => 'Hospital Headings and Core Foreground Text',
                'description' => 'Primary foreground color for headings and priority content.',
            ],
            'muted' => [
                'label' => 'Informational and Auxiliary Text',
                'description' => 'Used for subdued text such as metadata, helper labels, and non-primary copy.',
            ],
        ],
    ];

    $selected = isset($definitions[$themeKey]) && is_array($definitions[$themeKey])
        ? $definitions[$themeKey]
        : [];

    $result = [];
    foreach (['primary', 'accent', 'surface', 'text', 'muted'] as $key) {
        $current = isset($selected[$key]) && is_array($selected[$key]) ? $selected[$key] : [];
        $fallback = $defaults[$key];

        $result[$key] = [
            'label' => isset($current['label']) && is_string($current['label']) && trim($current['label']) !== ''
                ? trim($current['label'])
                : $fallback['label'],
            'description' => isset($current['description']) && is_string($current['description']) && trim($current['description']) !== ''
                ? trim($current['description'])
                : $fallback['description'],
        ];
    }

    $extraFields = pih_get_theme_extra_color_definitions($themeKey);
    foreach ($extraFields as $extraKey => $extraMeta) {
        if (!is_array($extraMeta)) {
            continue;
        }

        $result[$extraKey] = [
            'label' => isset($extraMeta['label']) ? (string) $extraMeta['label'] : ucwords(str_replace('_', ' ', $extraKey)),
            'description' => isset($extraMeta['description']) ? (string) $extraMeta['description'] : '',
        ];
    }

    return $result;
}

function pih_get_db_connection()
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    $configPath = __DIR__ . '/../portal/access/config.php';
    if (!file_exists($configPath)) {
        return null;
    }

    require $configPath;
    if (isset($conn) && $conn instanceof mysqli) {
        $db = $conn;
        return $db;
    }

    return null;
}

function pih_ensure_settings_table($conn)
{
    if (!($conn instanceof mysqli)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value MEDIUMTEXT NULL,
        setting_type VARCHAR(20) NOT NULL DEFAULT 'string',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return (bool) $conn->query($sql);
}

function pih_seed_defaults($conn)
{
    if (!($conn instanceof mysqli)) {
        return;
    }

    pih_ensure_settings_table($conn);
    $defaults = pih_get_default_settings();

    $stmt = $conn->prepare(
        'INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_key = setting_key'
    );

    if (!$stmt) {
        return;
    }

    $type = 'string';
    foreach ($defaults as $key => $value) {
        $stmt->bind_param('sss', $key, $value, $type);
        $stmt->execute();
    }

    $stmt->close();
}

function pih_get_legacy_short_settings()
{
    return [
        'site_name' => 'Private Imaging healthcare Center ',
        'site_short_name' => 'VPH',
        'site_addr_primary' => "1290 Hornby Street, 2nd Floor,\nVancouver, BC V6Z 1W2, Canada",
        'site_addr_secondary' => '438 N Frederick Ave, Gaithersburg, MD 20877, United States',
        'site_phone_primary' => '+1 (236)-260-1221',
        'site_phone_secondary' => '+1 (236)-260-1221',
        'site_url' => 'http://localhost/privateimaginghealthcare',
        'site_host' => 'privateimaginghealthcare.com',
        'site_email_primary' => 'info@privateimaginghealthcare.com',
        'site_email_secondary' => 'support@privateimaginghealthcare.com',
        'livechat_script' => '<script type="text/javascript">\nvar _smartsupp = _smartsupp || {};\n_smartsupp.key = "2700a30912884265ff0495e8334f7342bfc3a179";\nwindow.smartsupp||(function(d) {\n  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];\n  s=d.getElementsByTagName("script")[0];c=d.createElement("script");\n  c.type="text/javascript";c.charset="utf-8";c.async=true;\n  c.src="https://www.smartsuppchat.com/loader.js?";s.parentNode.insertBefore(c,s);\n})(document);\n</script>\n<noscript> Powered by <a href="https://www.smartsupp.com" target="_blank">Smartsupp</a></noscript>'
    ];
}

function pih_seed_legacy_short_values_once($conn)
{
    if (!($conn instanceof mysqli)) {
        return;
    }

    pih_ensure_settings_table($conn);

    $markerKey = 'legacy_short_seeded';
    $check = $conn->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    if ($check) {
        $check->bind_param('s', $markerKey);
        $check->execute();
        $result = $check->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $check->close();

        if ($row && (string) ($row['setting_value'] ?? '') === '1') {
            return;
        }
    }

    $legacy = pih_get_legacy_short_settings();
    $stmt = $conn->prepare(
        'INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)'
    );

    if (!$stmt) {
        return;
    }

    $type = 'string';
    foreach ($legacy as $key => $value) {
        $stmt->bind_param('sss', $key, $value, $type);
        $stmt->execute();
    }

    $markerValue = '1';
    $stmt->bind_param('sss', $markerKey, $markerValue, $type);
    $stmt->execute();
    $stmt->close();
}

function pih_get_settings_map($conn = null)
{
    $defaults = pih_get_default_settings();
    $db = $conn instanceof mysqli ? $conn : pih_get_db_connection();

    if (!($db instanceof mysqli)) {
        return $defaults;
    }

    pih_ensure_settings_table($db);
    pih_seed_defaults($db);
    pih_seed_legacy_short_values_once($db);

    $settings = $defaults;
    $result = $db->query('SELECT setting_key, setting_value FROM site_settings');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $key = (string) ($row['setting_key'] ?? '');
            $value = (string) ($row['setting_value'] ?? '');

            if (in_array($key, ['site_addr_primary', 'site_addr_secondary', 'livechat_script'], true)) {
                $value = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $value);
                $value = str_replace(["\r\n", "\r"], "\n", $value);
            }

            $settings[$key] = $value;
        }
        $result->free();
    }

    return $settings;
}

function pih_get_setting($key, $default = null, $conn = null)
{
    $defaults = pih_get_default_settings();
    $fallback = array_key_exists($key, $defaults) ? $defaults[$key] : $default;
    $all = pih_get_settings_map($conn);

    return array_key_exists($key, $all) ? $all[$key] : $fallback;
}

function pih_set_setting($key, $value, $type = 'string', $conn = null)
{
    $db = $conn instanceof mysqli ? $conn : pih_get_db_connection();
    if (!($db instanceof mysqli)) {
        return false;
    }

    pih_ensure_settings_table($db);

    $stmt = $db->prepare(
        'INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sss', $key, $value, $type);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function pih_is_installation_complete($conn = null): bool
{
    $value = trim((string) pih_get_setting('installation_complete', '0', $conn));
    return $value === '1';
}

function pih_mark_installation_complete($conn = null): bool
{
    $db = $conn instanceof mysqli ? $conn : pih_get_db_connection();
    if (!($db instanceof mysqli)) {
        return false;
    }

    $ok = pih_set_setting('installation_complete', '1', 'int', $db);
    $ok = pih_set_setting('install_completed_at', date('Y-m-d H:i:s'), 'string', $db) && $ok;
    return $ok;
}
